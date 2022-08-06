<?php

namespace Hizzle\Downloads;

use \Hizzle\Store\Record;

/**
 * Links a downloadable file to an external product or order.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Download link class.
 */
class Download_Link extends Record {

	/**
	 * Get the associated file ID.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_file_id( $context = 'view' ) {
		return $this->get_prop( 'file_id', $context );
	}

	/**
	 * Set the associated file ID.
	 *
	 * @param int $value File ID.
	 */
	public function set_file_id( $file_id ) {
		$this->set_prop( 'file_id', absint( $file_id ) );
	}

	/**
	 * Get the associated external id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_external_id( $context = 'view' ) {
		return $this->get_prop( 'external_id', $context );
	}

	/**
	 * Set the associated external id.
	 *
	 * @param string $external_id Order item ID.
	 */
	public function set_external_id( $order_item_id ) {
		$this->set_prop( 'external_id', sanitize_text_field( $order_item_id ) );
	}

	/**
	 * Get the source.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_source( $context = 'view' ) {
		return $this->get_prop( 'source', $context );
	}

	/**
	 * Set the source.
	 *
	 * @param string $source Order item ID.
	 */
	public function set_source( $source ) {
		$this->set_prop( 'source', sanitize_text_field( $source ) );
	}

}
