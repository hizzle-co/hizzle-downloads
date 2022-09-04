<?php

namespace Hizzle\Downloads\Admin;

use \Hizzle\Downloads\Download_Log;

/**
 * Displays a list of all downloads.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Download logs table class.
 */
class Download_Logs_Table extends \Hizzle\Store\List_Table {

	/**
	 * Constructor function.
	 *
	 */
	public function __construct() {
		parent::__construct( \Hizzle\Store\Collection::instance( 'hizzle_download_logs' ) );
	}

	/**
	 * Returns available row actions.
	 *
	 * @param Download_Log $item item.
	 * @return string
	 */
	public function get_download_row_actions( $item ) {

		$actions = array(

			'id' => sprintf(
				// translators: Download ID.
				esc_html__( 'ID: %d', 'hizzle-downloads' ),
				absint( $item->get_id() )
			),

		);

		return $this->row_actions( apply_filters( 'hizzle_download_log_row_actions', $actions, $item ) );

	}

	/**
	 * Displays the user_id column.
	 *
	 * @param  Download_Log $item item.
	 * @return string
	 */
	public function column_user_id( $item ) {
		$user = get_user_by( 'id', $item->get_user_id() );

		if ( empty( $user ) ) {
			return '&mdash;';
		}

		// Return a link to the user's profile.
		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( get_edit_user_link( $user->ID ) ),
			esc_html( $user->display_name . '(' . $user->user_login . ')' )
		);
	}

	/**
	 * Displays the timestamp column.
	 *
	 * @param  Download_Log $item item.
	 * @return string
	 */
	public function column_timestamp( $item ) {
		$date = $item->get_timestamp();
		$date = empty( $date ) ? '&mdash;' : $date->date_i18n( 'F j, Y @ g:i a' );
		return sprintf(
			'<div class="download-log-row-timestamp-wrapper"><div class="row-title"><strong>%s</strong></div><div class="row-actions">%s</div></div>',
			esc_html( $date ),
			$this->get_download_row_actions( $item )
		);
	}

	/**
	 * Displays the user_ip_address column.
	 *
	 * @param  Download_Log $item item.
	 * @return string
	 */
	public function column_user_ip_address( $item ) {
		return esc_html( $item->get_user_ip_address() );
	}

	/**
	 * Displays the file_id column.
	 *
	 * @param  Download_Log $item item.
	 * @return string
	 */
	public function column_file_id( $item ) {
		$file = hizzle_get_download( $item->get_file_id() );

		if ( is_wp_error( $file ) || ! $file->exists() ) {
			return '&mdash;';
		}

		// Return a link to the file's edit page.
		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $file->get_edit_url() ),
			esc_html( $file->get_file_name() )
		);
	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'timestamp'       => __( 'Timestamp', 'hizzle-downloads' ),
			'file_id'         => __( 'File', 'hizzle-downloads' ),
			'user_id'         => __( 'User', 'hizzle-downloads' ),
			'user_ip_address' => __( 'User IP Address', 'hizzle-downloads' ),
		);

		/**
		 * Filters the columns shown in the downloads list table.
		 *
		 * @param array $columns Downloads table columns.
		 */
		return apply_filters( 'manage_hizzle_download_logs_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'timestamp'       => array( 'timestamp', true ),
			'file_id'         => array( 'file_id', false ),
			'user_id'         => array( 'user_id', false ),
			'user_ip_address' => array( 'user_ip_address', false ),
		);

		/**
		 * Filters the sortable columns in the download logs table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_hizzle_download_logs_sortable_table_columns', $sortable );
	}

}
