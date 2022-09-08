<?php

namespace Hizzle\Downloads\Admin;

use \Hizzle\Downloads\Download;

/**
 * Contains the main downloads admin class
 *
 * @package    Hizzle
 * @subpackage Downloads
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The downloads admin Class.
 *
 */
class Downloads {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Save a download.
		add_action( 'hizzle_downloads_admin_save_download', array( $this, 'save_download' ) );

		// Display downloads.
		add_action( 'hizzle_admin_display_downloads', array( $this, 'display_downloads' ) );

		// Uploads.
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_filter( 'wp_unique_filename', array( $this, 'update_filename' ), 10, 3 );
		add_action( 'media_upload_hizzle_downloadable_file', array( $this, 'upload_downloadable_file' ) );
	}

	/**
	 * Displays the downloads admin page.
	 *
	 */
	public function display_downloads() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		// Either display a list of downloads or the download edit screen.
		if ( isset( $_GET['hizzle_download'] ) ) {
			$download_id = absint( $_GET['hizzle_download'] );
			$download    = hizzle_get_download( $download_id );
			$this->edit_download( $download );
		} else {
			$this->list_downloads();
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Displays the download edit screen.
	 *
	 * @param Download|\WP_Error $download The download to edit.
	 */
	public function edit_download( $download ) {

		// Abort if WP_Error.
		if ( is_wp_error( $download ) ) {
			echo wp_kses_post( $download->get_error_message() );
			return;
		}

		// Display the edit screen.
		require_once dirname( __FILE__ ) . '/views/html-edit-download.php';
	}

	/**
	 * Displays a list of available downloads.
	 *
	 */
	public function list_downloads() {

		// Display the list of downloads.
		require_once dirname( __FILE__ ) . '/views/html-downloads-list.php';
	}

	/**
	 * Saves a submitted download.
	 *
	 * @param  array   $data Download data.
	 * @since  1.0.0
	 */
	public function save_download( $data ) {
		// Nonce and capability were checked in Admin::maybe_do_action();

		// Abort if no download file.
		if ( ! isset( $data['hizzle_download_id'] ) || empty( $data['hizzle_downloads'] ) ) {
			return;
		}

		// Does the download exist.
		$download = hizzle_get_download( $data['hizzle_download_id'] );

		if ( is_wp_error( $download ) ) {
			Notices::add_custom_notice( $download->get_error_code(), $download->get_error_message() );
			return;
		}

		// Update the download.
		$props = isset( $data['hizzle_downloads'] ) ? $data['hizzle_downloads'] : array();
		$download->set_props(
			array(
				'file_name'  => isset( $props['file_name'] ) ? $props['file_name'] : null,
				'file_url'   => isset( $props['file_url'] ) ? $props['file_url'] : null,
				'git_url'    => isset( $props['git_url'] ) ? $props['git_url'] : null,
				'category'   => isset( $props['category'] ) ? $props['category'] : null,
				'menu_order' => isset( $props['menu_order'] ) ? $props['menu_order'] : null,
				'password'   => isset( $props['password'] ) ? $props['password'] : null,
			),
			true
		);

		// GitHub updates.
		if ( isset( $props['git_update_key'] ) && ! empty( $props['git_update_key'] ) ) {
			$download->add_git_info( get_transient( $props['git_update_key'] ) );
			delete_transient( $props['git_update_key'] );
		}

		// Conditional logic.
		$download->update_meta( '_conditional_logic', isset( $props['conditional_logic'] ) ? $props['conditional_logic'] : array() );

		// Save the download file.
		$result = $download->save();

		if ( is_wp_error( $result ) ) {
			Notices::add_custom_notice( $result->get_error_code(), $result->get_error_message() );
			return;
		}

		// Add notice.
		if ( $download->exists() ) {

			Notices::add_custom_notice( 'changes_saved', __( 'Download saved successfully.', 'hizzle-downloads' ) );
			wp_safe_redirect( esc_url_raw( $download->get_edit_url() ) );
			exit;
		} else {
			Notices::add_custom_notice( 'error_saving', __( 'Error saving the download.', 'hizzle-downloads' ) );
		}

	}

	/**
	 * Change upload dir for downloadable files.
	 *
	 * @param array $pathdata Array of paths.
	 * @return array
	 */
	public function upload_dir( $pathdata ) {

		// phpcs:disable WordPress.Security.NonceVerification.Missing

		if ( isset( $_POST['type'] ) && 'hizzle_downloadable_file' === $_POST['type'] ) {

			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/hizzle_uploads';
				$pathdata['url']    = $pathdata['url'] . '/hizzle_uploads';
				$pathdata['subdir'] = '/hizzle_uploads';
			} else {
				$new_subdir = '/hizzle_uploads' . $pathdata['subdir'];

				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
			}
		}
		return $pathdata;

		// phpcs:enable WordPress.Security.NonceVerification.Missing

	}

	/**
	 * Change filename for new uploads and prepend unique chars for security.
	 *
	 * @param string $full_filename Original filename.
	 * @param string $ext           Extension of file.
	 * @param string $dir           Directory path.
	 *
	 * @return string New filename with unique hash.
	 * @since 1.0.0
	 */
	public function update_filename( $full_filename, $ext, $dir ) {

		// Ensure this is our upload and that file names should be randomized.
		if ( ! strpos( $dir, 'hizzle_uploads' ) || ! apply_filters( 'hizzle_downloads_randomize_file_name', true ) ) {
			return $full_filename;
		}

		return $this->unique_filename( $full_filename, $ext );
	}

	/**
	 * Change filename to append random text.
	 *
	 * @param string $full_filename Original filename with extension.
	 * @param string $ext           Extension.
	 *
	 * @return string Modified filename.
	 */
	public function unique_filename( $full_filename, $ext ) {
		$ideal_random_char_length = 6;   // Not going with a larger length because then downloaded filename will not be pretty.
		$max_filename_length      = 255; // Max file name length for most file systems.
		$length_to_prepend        = min( $ideal_random_char_length, $max_filename_length - strlen( $full_filename ) - 1 );

		if ( 1 > $length_to_prepend ) {
			return $full_filename;
		}

		$suffix   = strtolower( wp_generate_password( $length_to_prepend, false, false ) );
		$filename = $full_filename;

		if ( strlen( $ext ) > 0 ) {
			$filename = substr( $filename, 0, strlen( $filename ) - strlen( $ext ) );
		}

		$full_filename = str_replace(
			$filename,
			"$filename-$suffix",
			$full_filename
		);

		return $full_filename;
	}

	/**
	 * Run a filter when uploading a downloadable product.
	 */
	public function hizzle_downloadable_file() {
		do_action( 'media_upload_file' );
	}

}
