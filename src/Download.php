<?php

namespace Hizzle\Downloads;

use \Hizzle\Store\Record;

/**
 * Container for downloads.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Download class.
 */
class Download extends Record {

	/**
	 * Get the file name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_file_name( $context = 'view' ) {
		return $this->get_prop( 'file_name', $context );
	}

	/**
	 * Set the file name.
	 *
	 * @param string $file_name The file name.
	 */
	public function set_file_name( $file_name ) {
		$this->set_prop( 'file_name', sanitize_text_field( $file_name ) );
	}

	/**
	 * Parse file path/url and see if its remote or local.
	 *
	 * @return array
	 */
	public function parse_file_path() {
		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		/**
		 * Replace uploads dir, site url etc with absolute counterparts if we can.
		 * Note the str_replace on site_url is on purpose, so if https is forced
		 * via filters we can still do the string replacement on a HTTP file.
		 */
		$replacements = array(
			$wp_uploads_url                                                   => $wp_uploads_dir,
			network_site_url( '/', 'https' )                                  => ABSPATH,
			str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
			site_url( '/', 'https' )                                          => ABSPATH,
			str_replace( 'https:', 'http:', site_url( '/', 'http' ) )         => ABSPATH,
		);

		$count            = 0;
		$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $this->get_file_url(), $count );
		$parsed_file_path = wp_parse_url( $file_path );
		$remote_file      = null === $count || 0 === $count; // Remote file only if there were no replacements.

		// Paths that begin with '//' are always remote URLs.
		if ( '//' === substr( $file_path, 0, 2 ) ) {
			$file_path = ( is_ssl() ? 'https:' : 'http:' ) . $file_path;

			/**
			 * Filter the remote filepath for download.
			 *
			 * @since 1.0.0
			 * @param string $file_path File path.
			 */
			return array(
				'remote_file' => true,
				'file_path'   => apply_filters( 'hizzle_download_parse_remote_file_path', $file_path, true, $this ),
			);
		}

		// See if path needs an abspath prepended to work.
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;

		} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
			$remote_file = false;
			$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

			// Check if we have an absolute path.
		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
		}

		/**
		* Filter the filepath for download.
		*
		* @since 1.0.0
		* @param string  $file_path File path.
		* @param bool $remote_file Remote File Indicator.
		*/
		return array(
			'remote_file' => $remote_file,
			'file_path'   => apply_filters( 'hizzle_download_parse_file_path', $file_path, $remote_file, $this ),
		);
	}

	/**
	 * Get the file URL.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_file_url( $context = 'view' ) {
		return $this->get_prop( 'file_url', $context );
	}

	/**
	 * Set the file URL.
	 *
	 * @param string $file_url File URL.
	 */
	public function set_file_url( $file_url ) {
		$this->set_prop( 'file_url', sanitize_text_field( $file_url ) );
	}

	/**
	 * Get the file category.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_category( $context = 'view' ) {
		return $this->get_prop( 'category', $context );
	}

	/**
	 * Set the file category.
	 *
	 * @param string $category File category.
	 */
	public function set_category( $category ) {
		$this->set_prop( 'category', sanitize_text_field( $category ) );
	}

	/**
	 * Get the download count.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_download_count( $context = 'view' ) {
		return $this->get_prop( 'download_count', $context );
	}

	/**
	 * Set the download count.
	 *
	 * @param int $download_count Download count.
	 */
	public function set_download_count( $download_count ) {
		$this->set_prop( 'download_count', absint( $download_count ) );
	}

	/**
	 * Get the menu order.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Set the menu order.
	 *
	 * @param int $menu_order Menu order.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Retrieves the conditional logic.
	 *
	 * @return array
	 */
	public function get_conditional_logic() {
		$conditional_logic = $this->get_meta( '_conditional_logic' );

		if ( ! is_array( $conditional_logic ) ) {
			$conditional_logic = array();
		}

		$conditional_logic = wp_parse_args(
			$conditional_logic,
			array(
				'enabled' => false,
				'action'  => 'allow',
				'type'    => 'all',
				'rules'   => array(),
			)
		);

		$conditional_logic['enabled'] = (bool) $conditional_logic['enabled'];
		return $conditional_logic;
	}

	/**
	 * Checks if conditional logic is enabled.
	 *
	 * @return bool
	 */
	public function has_conditional_logic() {
		$conditional_logic = $this->get_conditional_logic();
		return ! empty( $conditional_logic['enabled'] );
	}

	/**
	 * Checks if the file is downloadable.
	 *
	 * @return bool
	 */
	public function is_downloadable() {
		$file_url        = $this->get_file_url();
		$is_downloadable = ( ! empty( $file_url ) && $this->exists() );
		return apply_filters( 'hizzle_downloads_is_downloadable', $is_downloadable, $this );
	}

	/**
	 * Returns the download URL.
	 *
	 * @return string
	 */
	public function get_download_url() {
		$url = add_query_arg( array( 'hizzle_download_file' => $this->get_id() ), home_url() );
		return apply_filters( 'hizzle_downloads_download_url', $url, $this );
	}

	/**
	 * Returns the edit URL.
	 *
	 * @return string
	 */
	public function get_edit_url() {
		return add_query_arg( 'hizzle_download', $this->get_id(), admin_url( 'admin.php?page=hizzle-downloads' ) );
	}

	/**
	 * Returns the delete URL.
	 *
	 * @return string
	 */
	public function get_delete_url() {
		return \Hizzle\Downloads\Admin\Admin::action_url( 'delete_download', $this->get_edit_url() );
	}

	/**
	 * Deletes a downloadable file.
	 *
	 * @since  1.0.0
	 * @param  bool $force_delete Should the data be deleted permanently.
	 * @return bool result
	 */
	public function delete( $force_delete = false ) {
		global $wpdb;

		// Delete download logs.
		$wpdb->delete(
			$wpdb->prefix . 'hizzle_download_logs',
			array( 'file_id' => $this->get_id() ),
			array( '%d' )
		);

		// Delete the file.
		return parent::delete( $force_delete );

	}

	/**
	 * Track a file download.
	 *
	 * @since 1.0.0
	 * @param int    $user_id         Id of the user performing the download.
	 * @param string $user_ip_address IP Address of the user performing the download.
	 */
	public function track_download( $user_id = null, $user_ip_address = null ) {

		// Ensure the file exists.
		if ( ! ( $this->exists() ) ) {
			return;
		}

		// Track download in download log.
		$download_log = hizzle_get_download_log();
		$download_log->set_timestamp( time() );
		$download_log->set_file_id( $this->get_id() );
		$download_log->set_user_id( $user_id );
		$download_log->set_user_ip_address( $user_ip_address );
		$download_log->save();
	}

}
