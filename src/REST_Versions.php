<?php

namespace Hizzle\Downloads;

/**
 * Handles downloadable software versions.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles downloadable software versions.
 */
class REST_Versions {

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
		$this->rest_base = 'versions';

		// Register rest routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Returns current download versions.
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_versions' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'downloads' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => __( 'A comma-separated list of git URLs or file IDs.', 'hizzle-downloads' ),
						),
					),
				),
			)
		);

	}

	/**
	 * Retrieves all downloadable files.
	 *
	 * @param \WP_REST_Request $request
	 * @since 1.0.0
	 */
	public function get_versions( $request ) {

		$downloads = wp_parse_list( rawurldecode( $request['downloads'] ) );

		if ( empty( $downloads ) ) {
			return rest_ensure_response( array() );
		}

		$prepared = array();

		foreach ( $downloads as $download ) {

			if ( is_numeric( $download ) ) {
				$download_file = hizzle_get_download( absint( $download ) );
			} elseif ( false === strpos( $download, '/' ) ) {
				$download_file = hizzle_get_download_by_file_name( $download );
			} else {
				$git_url = $download;

				if ( 0 !== strpos( $git_url, 'http' ) ) {
					$git_url = 'https://github.com/' . $git_url;
				}

				$download_file = hizzle_get_download_by_git_url( $git_url );
			}

			if ( is_wp_error( $download_file ) ) {
				$prepared[ $download ] = array(
					'error' => $download_file->get_error_message(),
				);
				continue;
			}

			if ( empty( $download_file ) || ! $download_file->exists() ) {
				$prepared[ $download ] = array(
					'error'      => __( 'Download file not found.', 'hizzle-downloads' ),
					'error_code' => 'download_file_not_found',
				);
				continue;
			}

			$prepared[ $download ] = $download_file->get_version_info();
		}

        return rest_ensure_response( apply_filters( 'hizzle_get_downloadable_versions_rest', $prepared, $downloads ) );

    }
}
