<?php

namespace Hizzle\Downloads;

use \Hizzle\Store\Record;

/**
 * Logs file downloads.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Logs file downloads.
 */
class Download_Log extends Record {

	/**
	 * Get the download date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_timestamp( $context = 'view' ) {
		return $this->get_prop( 'timestamp', $context );
	}

	/**
	 * Set the download date.
	 *
	 * @param int|\Hizzle\Store\Date_Time|null $timestamp The download date.
	 */
	public function set_timestamp( $timestamp ) {
		$this->set_date_prop( 'timestamp', $timestamp );
	}

	/**
	 * Get the user id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int|null
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_prop( 'user_id', $context );
	}

	/**
	 * Set the user id.
	 *
	 * @param int|null $user_id The user id.
	 */
	public function set_user_id( $user_id ) {
		$this->set_prop( 'user_id', empty( $user_id ) ? null : absint( $user_id ) );
	}

	/**
	 * Get the file id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int|null
	 */
	public function get_file_id( $context = 'view' ) {
		return $this->get_prop( 'file_id', $context );
	}

	/**
	 * Set the file id.
	 *
	 * @param int|null $file_id The file id.
	 */
	public function set_file_id( $file_id ) {
		$this->set_prop( 'file_id', empty( $file_id ) ? null : absint( $file_id ) );
	}

	/**
	 * Get the user IP address.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string|null
	 */
	public function get_user_ip_address( $context = 'view' ) {
		return $this->get_prop( 'user_ip_address', $context );
	}

	/**
	 * Set the user IP address.
	 *
	 * @param string|null $user_ip_address The user ip address.
	 */
	public function set_user_ip_address( $user_ip_address ) {
		$this->set_prop( 'user_ip_address', empty( $user_ip_address ) ? null : sanitize_text_field( $user_ip_address ) );
	}

}
