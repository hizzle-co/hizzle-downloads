<?php

namespace Hizzle\Downloads;

/**
 * Updates the downloadable file when a GitHub release is published.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Updates the downloadable file when a GitHub release is published.
 */
class GitHub_Updater {

	/**
	 * The namespace for the REST API.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The base for the REST API.
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Loads the class.
	 *
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace . '/v1';
		$this->rest_base = 'github-updater';

		// Register rest routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// Background updates.
		add_action( 'hizzle_downloads_process_github_release', array( $this, 'process_bg_release' ), 10, 2 );
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Fired when a GitHub release is published.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_release_webhook' ),
					'permission_callback' => array( $this, 'validate_webhook_signature' ),
					'args'                => array(
						'action'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => __( 'The action that was performed.', 'hizzle-downloads' ),
						),
						'release'    => array(
							'required'          => true,
							'type'              => 'object',
							'sanitize_callback' => 'wp_unslash',
							'description'       => __( 'The release object.', 'hizzle-downloads' ),
						),
						'repository' => array(
							'required'          => true,
							'type'              => 'object',
							'sanitize_callback' => 'wp_unslash',
							'description'       => __( 'The repository object.', 'hizzle-downloads' ),
						),
					),
				),
			)
		);

		// Fired when admin requests a manual update.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/manual-update',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'manual_update' ),
					'permission_callback' => array( $this, 'can_update_download' ),
					'args'                => array(
						'repository' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => __( 'The repository to fetch.', 'hizzle-downloads' ),
						),
						'tag'        => array(
							'required'          => false,
							'default'           => 'latest',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => __( 'The tag to fetch.', 'hizzle-downloads' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Validate's a request's signature.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function validate_webhook_signature( $request ) {

		// Fetch the signature from the request.
		$signature = $request->get_header( 'X-Hub-Signature-256' );
		$expected  = 'sha256=' . hash_hmac( 'sha256', $request->get_body(), HIZZLE_DOWNLOADS_GITHUB_WEBHOOK_TOKEN );

		if ( empty( $signature ) || ! hash_equals( $signature, $expected ) ) {
			return new \WP_Error( 'unauthorised', 'Signature does not match.', array( 'status' => 401 ) );
		}

		return true;
	}

    /**
	 * Processes a GitHub release webhook.
	 *
     * @param \WP_REST_Request $request
	 */
	public function process_release_webhook( $request ) {

		// Ensure this is a release event...
		if ( 'release' !== $request->get_header( 'X-GitHub-Event' ) ) {
			return rest_ensure_response( array( 'message' => 'Not a release event.' ) );
		}

		// And that the release is published.
		if ( 'released' !== $request->get_param( 'action' ) ) {
			return rest_ensure_response( array( 'message' => 'Unsupported release action.' ) );
		}

		// Process the release in the background to allow for release assets to be uploaded first.
		if ( function_exists( 'schedule_noptin_background_action' ) ) {
			$result = schedule_noptin_background_action( time() + 10, 'hizzle_downloads_process_github_release', $request['repository']['full_name'] );
		} else {
			$result = wp_schedule_single_event( time() + 10, 'hizzle_downloads_process_github_release', array( $request['repository']['full_name'] ) );
		}

		return rest_ensure_response( $result );
    }

	/**
	 * Checks if the current user can update the download.
	 *
	 */
	public function can_update_download() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'unauthorised', 'You are not allowed to do this.', rest_authorization_required_code() );
		}

		return true;
	}

	/**
	 * Processes a GitHub release in the background.
	 *
	 * @param object $release
	 * @param string $repo
	 */
	public function process_bg_release( $repo ) {
		$release = $this->get_release( "https://api.github.com/repos/$repo/releases/latest" );

		// Ensure that it was successful.
		if ( is_wp_error( $release ) ) {
			hizzle_downloads()->logger->error( $release->get_error_message(), 'hizzle_downloads' );
			return;
		}

		$result = $this->process_release( $release, $repo );

		if ( is_wp_error( $result ) ) {
			hizzle_downloads()->logger->error( $result->get_error_message(), 'hizzle_downloads' );
		}
	}

	/**
	 * Processes a manual refresh REST request.
	 *
     * @param \WP_REST_Request $request
	 */
	public function manual_update( $request ) {

		// Prepare args.
		$repo = $request->get_param( 'repository' );
		$tag  = $request->get_param( 'tag' );

		// Remove https://github.com from the URL.
		$repo = untrailingslashit( str_replace( 'https://github.com/', '', strtolower( $repo ) ) );

		// Fetch the release.
		if ( 'latest' === $tag || empty( $tag ) ) {
			$release = $this->get_release( "https://api.github.com/repos/$repo/releases/latest" );
		} else {
			$release = $this->get_release( "https://api.github.com/repos/$repo/releases/tags/$tag" );
		}

		// Ensure that it was successful.
		if ( is_wp_error( $release ) ) {
			return $release;
		}

		// Process the release.
		return rest_ensure_response( $this->process_release( $release, $repo ) );

    }

    /**
	 * Retrieves a GitHub release.
	 *
	 * @param string $url
	 */
	public function get_release( $url ) {

		$release = $this->get( $url );
		$error   = new \WP_Error( 'no_release', 'Unable to fetch release.', array( 'status' => 400 ) );

		if ( is_wp_error( $release ) ) {
            return $error;
        }

		$release   = json_decode( wp_remote_retrieve_body( $release ) );
		return empty( $release ) || empty( $release->tag_name ) ? $error : $release;

	}

	/**
	 * Processes a release and updates the database.
	 *
	 * @param object $release
	 * @param string $repo
	 */
	public function process_release( $release, $repo ) {

		// Ensure we have an object.
		$release = (object) $release;

		// Prepare the data to use.
		$_repo = explode( '/', $repo );
		$data  = array(
			'assets_url'  => $release->assets_url,
			'zipball_url' => $release->zipball_url,
			'tag_name'    => $release->tag_name,
			'body'        => $release->body,
			'assets'      => $release->assets,
			'repo'        => array(
				'url'       => 'https://github.com/' . $repo . '/',
				'full_name' => $repo,
				'owner'     => $_repo[0],
				'name'      => $_repo[1],
			),
		);

		// Prepare the data to insert into the database.
        $prepared = $this->prepare_github_download_data( $data );

        // Fetch the download file.
		$download_file = hizzle_get_download_by_git_url( 'https://github.com/' . $repo );

		if ( ! is_wp_error( $download_file ) && $download_file->exists() ) {
			$download_file->add_git_info( $prepared );
			$download_file->save();
			delete_transient( $repo . $release->tag_name );
		} else {
			set_transient( $repo . $release->tag_name, $prepared, DAY_IN_SECONDS );
		}

		do_action( 'hizzle_downloads_processed_new_release', $download_file, $prepared, $data );

		return rest_ensure_response(
			array(
				'message'    => sprintf( 'Successfully processed release %s.', $release->tag_name ),
				'update_key' => $repo . $release->tag_name,
				'file_url'   => isset( $prepared['file_url'] ) ? $prepared['file_url'] : '',
			)
		);

	}

	/**
	 * Prepares release data.
	 *
	 * @param array $release
	 */
	protected function prepare_github_download_data( $release ) {

		$version = str_replace( 'v', '', $release['tag_name'] );
		$data    = array(
			'version'   => $version,
			'changelog' => $release['body'], // The release body contains the changelog.
		);

		$zip_file = $this->get_asset_url( $release );

		if ( is_array( $zip_file ) ) {
			$data = array_merge( $data, $zip_file );
			do_action( 'hizzle_downloads_downloaded_github_release_asset', $data, $release );

			if ( $zip_file['is_zip'] ) {
				$data = $this->add_changelog( $data );
			}
		}

		// Parse changelog markdown.
		if ( ! empty( $data['changelog'] ) ) {
			$parsedown         = new Parsedown();
			$data['changelog'] = $parsedown->text( $data['changelog'] );
		}

		return $data;
	}

	/**
	 * Retrieves the asset URL.
	 *
	 * @param array $release
	 * @return array|false asset details or false on error.
	 */
	protected function get_asset_url( $release ) {
		$upload_dir = wp_get_upload_dir();
		$dir_url    = $upload_dir['baseurl'] . "/hizzle_uploads/{$release['repo']['owner']}";
		$dir_path   = $upload_dir['basedir'] . "/hizzle_uploads/{$release['repo']['owner']}";

		// Prepare the upload dir.
		Installer::prepare_upload_dir( $dir_path );

		// Prepare release details.
		if ( ! empty( $release['assets'] ) ) {
			$processed = $this->process_release_asset( (object) $release['assets'][0], $dir_url, $dir_path );

			if ( $processed ) {
				return $processed;
			}
		}

		return $this->download_zipball( $release, $dir_url, $dir_path );

	}

	/**
	 * Processes a normal release asset.
	 *
	 * @param object $asset
	 * @param string $dir_url
	 * @param string $dir_path
	 * @return array|false asset details or false on error.
	 */
	protected function process_release_asset( $asset, $dir_url, $dir_path ) {

		// Ensure that the extension is allowed.
		$wp_filetype = wp_check_filetype( $asset->name );
		if ( ! $wp_filetype['ext'] ) {
			hizzle_downloads()->logger->error( 'The file extension for ' . $asset->name . ' is not allowed.', 'hizzle_downloads' );
			return false;
		}

		// Download the asset.
		$file_data = $this->get(
			$asset->url,
			array(
				'headers' => array(
					'Accept' => 'application/octet-stream',
				),
			)
		);

		if ( is_wp_error( $file_data ) ) {
			hizzle_downloads()->logger->error( $file_data->get_error_message() . ' when fetching ' . $asset->browser_download_url, 'hizzle_downloads' );
			return false;
		}

		// Retrieve the body.
		$file_data = wp_remote_retrieve_body( $file_data );
		if ( empty( $file_data ) ) {
			hizzle_downloads()->logger->error( 'Failed to retrieve the file data for' . $asset->browser_download_url . ' .', 'hizzle_downloads' );
			return false;
		}

		// Prepare args.
		$extension = strtolower( $wp_filetype['ext'] );
		$filename  = str_replace( '.' . $extension, '', strtolower( $asset->name ) );

		// Copy the file to the upload dir.
		Installer::create_file(
			$dir_path . '/' . strtolower( $asset->name ),
			$file_data
		);

		// Return the new file URL.
		return array(
			'file_path'        => $dir_path . '/' . strtolower( $asset->name ),
			'file_url'         => $dir_url . '/' . strtolower( $asset->name ),
			'root_folder_name' => $filename,
			'is_zip'           => 'zip' === $extension,
			'is_asset'         => true,
		);

	}

	/**
	 * Downloads the source code from a GitHub release.
	 *
	 * @param array $release
	 * @param string $dir_url
	 * @param string $dir_path
	 * @return array|false asset details or false on error.
	 */
	protected function download_zipball( $release, $dir_url, $dir_path ) {

		// Prepare args.
		$download_url = 'https://github.com/' . $release['repo']['full_name'] . '/archive/' . $release['tag_name'] . '.zip';
		$filename     = $release['repo']['name'] . '-' . $release['tag_name'];
		$alt_name     = $release['repo']['name'] . '-' . str_replace( 'v', '', $release['tag_name'] );
		$repo_name    = $release['repo']['name'];
		$zip_path     = $dir_path . '/' . $repo_name . '.zip';

		// Download the source code.
		$file_data = $this->get( $download_url );

		if ( is_wp_error( $file_data ) ) {
			hizzle_downloads()->logger->error( $file_data->get_error_message() . ' when fetching ' . $download_url, 'hizzle_downloads' );
			return false;
		}

		// Retrieve the body.
		$file_data = wp_remote_retrieve_body( $file_data );
		if ( empty( $file_data ) ) {
			hizzle_downloads()->logger->error( 'Failed to retrieve the file data for' . $download_url . ' .', 'hizzle_downloads' );
			return false;
		}

		// Copy the file to the upload dir.
		Installer::create_file( $zip_path, $file_data );

		// Rename the root folder to $release['repo']['name'].
		if ( class_exists( 'ZipArchive' ) ) {

			$zip = new \ZipArchive();
			$res = $zip->open( $zip_path );
			if ( true === $res ) {

				// Rename the root folder.
				$changed = false;
				for ( $i = 0; $i < $zip->numFiles; $i++ ) {
					$item_name = $zip->getNameIndex( $i );

					// Check if the file starts with the release tag name.
					if ( 0 === strpos( $item_name, $filename ) ) {
						$zip->renameIndex( $i, substr_replace( $item_name, $repo_name, 0, strlen( $filename ) ) );
						$changed = true;
					} elseif ( 0 === strpos( $item_name, $alt_name ) ) {
						$zip->renameIndex( $i, substr_replace( $item_name, $repo_name, 0, strlen( $alt_name ) ) );
						$changed = true;
					}
				}

				$zip->close();

				if ( $changed ) {
					$filename = $repo_name;
				}
			}
		}

		// Return the new file URL.
		return array(
			'file_path'        => $zip_path,
			'file_url'         => $dir_url . '/' . $repo_name . '.zip',
			'root_folder_name' => $filename,
			'is_zip'           => true,
		);

	}

	/**
	 * Reads changelog from the zip file.
	 *
	 * @param array $release_details
	 * @return array $release_details
	 */
	protected function add_changelog( $release_details ) {

		$files = array(
			$release_details['root_folder_name'] . '/CHANGELOG.txt',
			$release_details['root_folder_name'] . '/CHANGELOG.md',
			'CHANGELOG.txt',
			'CHANGELOG.md',
		);

		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new \ZipArchive();
			if ( true === $zip->open( $release_details['file_path'] ) ) {

				foreach ( $files as $file ) {
					$content = $zip->getFromName( $file );
					if ( ! empty( $content ) ) {
						$release_details['changelog'] = $content;
						break;
					}
				}

				$zip->close();
			}
		}

		return $release_details;
	}

    /**
	 * Sends a GET request to GitHub
	 *
	 * @param string $url
	 * @param array  $args
	 */
	public function get( $url, $args = array() ) {
		return wp_remote_get( $url, $this->add_github_headers( $args ) );
	}

	/**
	 * Sends a post request to GitHub
	 *
	 * @param string $url
	 * @param array  $args
	 */
	public function post( $url, $args = array() ) {
		return wp_remote_post( $url, $this->add_github_headers( $args ) );
	}

    /**
	 * Adds GitHub auth headers to a request.
	 *
	 * @param array $args The request
	 */
	public function add_github_headers( $args = array() ) {

		if ( empty( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$args['headers']['Authorization'] = 'Bearer ' . HIZZLE_DOWNLOADS_GITHUB_ACCESS_TOKEN;
		return $args;
	}
}
