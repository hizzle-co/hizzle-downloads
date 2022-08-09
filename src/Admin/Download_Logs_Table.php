<?php

namespace Hizzle\Downloads\Admin;

use \Hizzle\Downloads\Download_Log;

/**
 * Displays a list of all downloads.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Download logs table class.
 */
class Download_Logs_Table extends \WP_List_Table {

	/**
	 * Query
	 *
	 * @var   \Hizzle\Store\Query
	 * @since 1.0.0
	 */
	public $query;

	/**
	 * Total logs
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $total;

	/**
	 * Per page.
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $per_page = 25;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->per_page = $this->get_items_per_page( 'hizzle_download_logs_per_page', 25 );

		$this->process_bulk_action();

		$this->prepare_query();

		$this->prepare_items();
	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {

		$action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['id'] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->current_action();

		if ( 'delete' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				hizzle_delete_download_log( (int) $id );
			}

			Notices::add_custom_notice( 'deleted_download_logs', __( 'The selected download logs have been deleted.', 'hizzle-downloads' ) );

		}

		do_action( 'hizzle_download_logs_process_bulk_action', $action, $this );
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		$this->query = hizzle_get_download_logs(
			array(
				'paged'   => $this->get_pagenum(),
				'number'  => $this->per_page,
				'orderby' => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'timestamp', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'order'   => isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'search'  => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			),
			'query'
		);

		$this->total = $this->query->get_total();
		$this->items = $this->query->get_results();

	}

	/**
	 * Displays a column.
	 *
	 * @param Download_Log $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {
		// Allow plugins to display custom columns.
		do_action( "hizzle_display_downloads_table_$column_name", $item );
	}

	/**
	 * Returns available row actions.
	 *
	 * @param Download_Log $item item.
	 * @return string
	 */
	public function get_download_row_actions( $item ) {

		$actions = array(

			'id'     => sprintf(
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
	 * This is how checkbox column renders.
	 *
	 * @param  Download $item item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->get_id() ) );
	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'hizzle-downloads' ),
		);

		/**
		 * Filters the bulk table actions shown on the download logs table.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_hizzle_download_logs_table_bulk_actions', $actions );

	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->total );
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total / $this->per_page ),
			)
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
