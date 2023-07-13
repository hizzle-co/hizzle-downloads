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
				'type'        => 'VARCHAR',
				'length'      => 200,
				'nullable'    => false,
				'description' => __( 'The URL of the file.', 'hizzle-downloads' ),
			),

			'git_url'        => array(
				'type'        => 'VARCHAR',
				'length'      => 200,
				'nullable'    => true,
				'description' => __( 'The GitHub repo URL.', 'hizzle-downloads' ),
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
			'unique'  => array( 'file_name', 'git_url' ),
		),
	),

	// Store download logs.
	'logs'  => array(
		'singular_name' => 'log',
		'object'        => '\Hizzle\Downloads\Download_Log',
		'props'         => array(

			'id'              => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'extra'       => 'AUTO_INCREMENT',
				'description' => __( 'Unique identifier for this resource.', 'hizzle-downloads' ),
			),

			'timestamp'       => array(
				'type'        => 'DATETIME',
				'description' => __( 'The download date.', 'hizzle-downloads' ),
				'nullable'    => false,
			),

			'file_id'         => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => false,
				'description' => __( 'The file ID.', 'hizzle-downloads' ),
			),

			'user_id'         => array(
				'type'        => 'BIGINT',
				'length'      => 20,
				'nullable'    => true,
				'description' => __( 'The user ID.', 'hizzle-downloads' ),
			),

			'user_ip_address' => array(
				'type'        => 'VARCHAR',
				'length'      => 100,
				'nullable'    => true,
				'description' => __( 'The ip address that made the download.', 'hizzle-downloads' ),
			),

		),

		'keys'          => array(
			'primary'   => array( 'id' ),
			'file_id'   => array( 'file_id' ),
			'timestamp' => array( 'timestamp' ),
		),
	),

);
