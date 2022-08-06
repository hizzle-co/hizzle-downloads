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

		// Delete download links.
		hizzle_delete_download_links( $this->get_id() );

		// Delete the file.
		return parent::delete( $force_delete );

	}

}
