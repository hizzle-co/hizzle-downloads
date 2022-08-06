<?php
/**
 * Database schema.
 *
 * Returns an array of the database schema.
 *
 */

defined( 'ABSPATH' ) || exit;

return array(

	// Downloadable files can be linked to any products and are stored in a separate table.
	'files' => array(

		'object'        => '\Hizzle\Downloads\Download',
		'singular_name' => 'download',
		'props'         => array(

			'id'             => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'extra'       => 'AUTO_INCREMENT',
				'description' => __( 'Unique identifier for this resource.', 'hizzle-downloads' ),
			),

			'file_name'      => array(
				'type'        => 'VARCHAR',
				'length'      => 200,
				'nullable'    => false,
				'description' => __( 'The name of the file.', 'hizzle-downloads' ),
			),

			'file_url'       => array(
				'type'        => 'TEXT',
				'nullable'    => false,
				'description' => __( 'The URL of the file.', 'hizzle-downloads' ),
			),

			'category'       => array(
				'type'        => 'VARCHAR',
				'length'      => 20,
				'nullable'    => true,
				'description' => __( 'Optionally group the file by category', 'hizzle-downloads' ),
			),

			'download_count' => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'default'     => 0,
				'description' => __( 'The number of times the file has been downloaded.', 'hizzle-downloads' ),
			),

			'menu_order'     => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'default'     => 0,
				'description' => __( 'The priority order of the file.', 'hizzle-downloads' ),
			),

			'metadata'       => array(
				'type'        => 'TEXT',
				'description' => __( 'A key value array of additional metadata about the download', 'hizzle-downloads' ),
			),
		),

		'keys'          => array(
			'primary' => array( 'id' ),
		),
	),

	// Store the links between downloadable files and products / orders.
	'links' => array(
		'singular_name' => 'link',
		'props'         => array(

			'id'          => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'extra'       => 'AUTO_INCREMENT',
				'description' => __( 'Unique identifier for this resource.', 'hizzle-downloads' ),
			),

			'file_id'     => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'description' => __( 'The file ID.', 'hizzle-downloads' ),
			),

			'external_id' => array(
				'type'        => 'VARCHAR',
				'length'      => 20,
				'nullable'    => false,
				'description' => __( 'The external id or slug.', 'hizzle-downloads' ),
			),

			'source'      => array(
				'type'        => 'VARCHAR',
				'length'      => 20,
				'nullable'    => false,
				'description' => __( 'The source, e.g wp_user or product id.', 'hizzle-downloads' ),
			),

			'metadata'    => array(
				'type'        => 'TEXT',
				'description' => __( 'A key value array of additional metadata about the download permission', 'hizzle-downloads' ),
			),

		),

		'keys'          => array(
			'primary'  => array( 'id' ),
			'file_id'  => array( 'file_id' ),
			'external' => array( 'external_id', 'source' ),
		),
	),

);
