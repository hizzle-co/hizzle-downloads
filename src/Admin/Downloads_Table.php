<?php

namespace Hizzle\Downloads\Admin;

use \Hizzle\Downloads\Download;

/**
 * Displays a list of all downloads.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Downloads table class.
 */
class Downloads_Table extends \Hizzle\Store\List_Table {

	/**
	 * Constructor function.
	 *
	 */
	public function __construct() {
		parent::__construct( \Hizzle\Store\Collection::instance( 'hizzle_download_files' ) );
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
