<?php

namespace Hizzle\Downloads\Admin;

use \Hizzle\Downloads\Download;

/**
 * Displays a list of all downloads.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Downloads table class.
 */
class Downloads_Table extends \WP_List_Table {

	/**
	 * Query
	 *
	 * @var   \Hizzle\Store\Query
	 * @since 1.0.0
	 */
	public $query;

	/**
	 * Total downloads
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
	public $per_page = 20;

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

		$this->per_page = $this->get_items_per_page( 'hizzle_downloads_per_page', 20 );

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
				hizzle_delete_download( $id );
			}

			Notices::add_custom_notice( 'deleted_downloads', __( 'The selected downloads have been deleted.', 'hizzle-downloads' ) );

		}

		do_action( 'hizzle_downloads_process_bulk_action', $action, $this );
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		$this->query = hizzle_get_downloads(
			array(
				'paged'   => $this->get_pagenum(),
				'number'  => $this->per_page,
				'orderby' => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
	 * @param Download $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'file_name':
				return sprintf(
					'<div class="download-row-name-wrapper"><div class="row-title"><a href="%s"><strong>%s</strong></a></div><div class="row-actions">%s</div></div>',
					esc_url( $item->get_edit_url() ),
					esc_html( $item->get_file_name() ),
					$this->get_download_row_actions( $item )
				);

			case 'file_url':
				return esc_url( $item->get_file_url() );

			case 'download_count':
				return (int) $item->get_download_count();

			case 'menu_order':
				return (int) $item->get_menu_order();

			case 'category':
				$category = $item->get_category();
				return empty( $category ) ? '&mdash;' : esc_html( $category );

		}

		// Allow plugins to display custom columns.
		do_action( "hizzle_display_downloads_table_$column_name", $item );

	}

	/**
	 * Returns available row actions.
	 *
	 * @param Download $item item.
	 * @return string
	 */
	public function get_download_row_actions( $item ) {

		$actions = array(

			'id'     => sprintf(
				// translators: Download ID.
				esc_html__( 'ID: %d', 'hizzle-downloads' ),
				absint( $item->get_id() )
			),

			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->get_edit_url() ),
				esc_html__( 'Edit', 'hizzle-downloads' )
			),

			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(%s)">%s</a>',
				esc_url( $item->get_delete_url() ),
				// translators: File name.
				esc_attr( sprintf( __( 'Are you sure you want to delete %s?', 'hizzle-downloads' ), $item->get_file_name() ) ),
				esc_html__( 'Delete', 'hizzle-downloads' )
			),

		);

		return $this->row_actions( apply_filters( 'hizzle_download_row_actions', $actions, $item ) );

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
		 * Filters the bulk table actions shown on the downloads table.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_hizzle_downloads_table_bulk_actions', $actions );

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
			'cb'             => '<input type="checkbox" />',
			'file_name'      => __( 'File Name', 'hizzle-downloads' ),
			'file_url'       => __( 'File URL', 'hizzle-downloads' ),
			'category'       => __( 'Category', 'hizzle-downloads' ),
			'download_count' => __( 'Download Count', 'hizzle-downloads' ),
			'menu_order'     => __( 'Priority', 'hizzle-downloads' ),
		);

		/**
		 * Filters the columns shown in the downloads list table.
		 *
		 * @param array $columns Downloads table columns.
		 */
		return apply_filters( 'manage_hizzle_downloads_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'file_name'      => array( 'file_name', true ),
			'file_url'       => array( 'file_url', true ),
			'download_count' => array( 'download_count', true ),
			'menu_order'     => array( 'menu_order', true ),
			'category'       => array( 'category', true ),
		);

		/**
		 * Filters the sortable columns in the downloads table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_hizzle_downloads_sortable_table_columns', $sortable );
	}

}
