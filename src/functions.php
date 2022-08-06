<?php

use \Hizzle\Store;
use \Hizzle\Downloads\Download;
use \Hizzle\Downloads\Download_Link;

/**
 * Core Functions
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves all plugin options
 *
 * @return  array   options
 * @since   1.0.0
 */
function hizzle_downloads_get_options() {
	$options = get_option( 'hizzle_downloads_options', array() );
	return is_array( $options ) ? $options : array();
}

/**
 * Retrieves an option value from the db
 *
 * @return  mixed|null  Option value or null
 * @param   string $key The option key.
 * @param   mixed  $default The default value for the option.
 * @since   1.0.0
 */
function hizzle_downloads_get_option( $key, $default = null ) {

	// Prepare value.
	$options = hizzle_downloads_get_options();
	$value   = isset( $options[ $key ] ) ? $options[ $key ] : $default;

	// Filter by key.
	$value = apply_filters( "hizzle_downloads_get_option_$key", $value );

	// General filter.
	return apply_filters( 'hizzle_downloads_get_option', $value, $key );

}

/**
 * Updates an option value int the db
 *
 * @param   string $key The option key.
 * @param   mixed  $value The option value.
 * @since   1.0.0
 */
function hizzle_downloads_update_option( $key, $value ) {

	// Prepare value.
	$options = hizzle_downloads_get_options();

	$options[ $key ] = $value;

	update_option( 'hizzle_downloads_options', $options );

}

/**
 * Fetches data stored on disk.
 *
 * @since 1.0.0
 *
 * @param string $key Type of data to fetch.
 * @return mixed Fetched data.
 */
function hizzle_downloads_get_data( $key ) {

    // Try fetching it from the cache.
    $data = wp_cache_get( "hizzle_downloads-data-$key", 'hizzle_downloads' );
    if ( $data ) {
        return $data;
    }

    $data = apply_filters( "hizzle_downloads_get_$key", include plugin_dir_path( __FILE__ ) . "data/$key.php" );
	wp_cache_set( "hizzle_downloads-data-$key", $data, 'hizzle_downloads', MINUTE_IN_SECONDS );

	return $data;
}

/**
 * Retrieves a store by its namespace.
 *
 * This function is not meant to be called directly. Please use the helper functions.
 *
 * @return Store\Store
 * @throws Store\Store_Exception
 */
function hizzle_downloads_get_store() {
    return hizzle_downloads()->store;
}

/**
 * Retrieves a collection by its name.
 *
 * @param string $name Name of the collection. E.g, downloads.
 * @return Store\Collection
 * @throws Store\Store_Exception
 */
function hizzle_downloads_get_collection( $name ) {
	return Store\Collection::instance( 'hizz_down_' . $name );
}

/**
 * Retrieves a single record when given the record ID.
 *
 * @param Store\Record|int $record_id The record ID.
 * @param string $collection_name The collection name.
 * @return Store\Record|WP_Error record object if found, error object if not found.
 */
function hizzle_downloads_get_record( $record_id, $collection_name ) {

	// No need to refetch the record if it's already an object.
	if ( is_a( $record_id, '\Hizzle\Store\Record' ) ) {
		return $record_id;
	}

	// Convert posts to IDs.
	if ( is_a( $record_id, 'WP_Post' ) ) {
		$record_id = $record_id->ID;
	}

	try {
		return hizzle_downloads_get_collection( $collection_name )->get( $record_id );
	} catch ( Hizzle\Store\Store_Exception $e ) {
		return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
	}

}

/**
 * Queries a store.
 *
 * This function is not meant to be called directly. Please use the helper functions.
 *
 * @param string $collection The collection name.
 * @param array $args Query arguments.
 * @param string $return 'results' returns the found records, 'count' returns the total count, 'aggregate' runs an aggregate query, while 'query' returns query object.
 *
 * @return int|array|Store\Record[]|Store\Query|WP_Error
 */
function hizzle_downloads_query( $collection_name, $args = array(), $return = 'results' ) {

	// Do not retrieve all fields if we just want the count.
	if ( 'count' === $return ) {
		$args['fields'] = 'id';
		$args['number'] = 1;
	}

	// Do not count all matches if we just want the results.
	if ( 'results' === $return ) {
		$args['count_total'] = false;
	}

	// Run the query.
	try {
		$query = hizzle_downloads_get_collection( $collection_name )->query( $args );

		if ( 'results' === $return ) {
			return $query->get_results();
		}

		if ( 'count' === $return ) {
			return $query->get_total();
		}

		if ( 'aggregate' === $return ) {
			return $query->get_aggregate();
		}

		return $query;
	} catch ( Store\Store_Exception $e ) {
		return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
	}

}

/**
 * Queries the downloads database.
 *
 * @param array $args Query arguments.
 * @param string $return See hizzle_downloads_query for allowed values.
 * @return int|array|Download[]|Store\Query|WP_Error
 */
function hizzle_get_downloads( $args = array(), $return = 'results' ) {
	return hizzle_downloads_query( 'downloads', $args, $return );
}

/**
 * Fetch download by download ID.
 *
 * @param int|string|Download $download_id Download ID object.
 * @return Download|WP_Error Download object if found, error object if not found.
 */
function hizzle_downloads_get_download( $download_id = 0 ) {
	return hizzle_downloads_get_record( $download_id, 'downloads' );
}

/**
 * Deletes a download.
 *
 * @param int|string|Download $download_id Download ID object.
 * @return bool|WP_Error True if deleted, error object if not.
 */
function hizzle_delete_download( $download_id ) {
	$download = hizzle_downloads_get_download( $download_id );

	if ( ! is_wp_error( $download ) ) {
		return $download->delete();
	}

	return $download;
}

/**
 * Deletes a product's downloads.
 *
 * @param int $product_id Product ID.
 */
function hizzle_downloads_delete_product_downloads( $product_id ) {
	global $wpdb;

	$wpdb->delete( $wpdb->prefix . 'hizzle_downloads_product_downloads', array( 'product_id' => $product_id ) );
}

/**
 * Deletes a download's products.
 *
 * @param int $download_id Download IDs.
 */
function hizzle_delete_download_products( $download_id ) {
	global $wpdb;

	$wpdb->delete( $wpdb->prefix . 'hizzle_downloads_product_downloads', array( 'file_id' => $download_id ) );
}

/**
 * Retrieves a product's downloads.
 *
 * @param int   $product_id Product ID.
 * @return int[] Download file IDs.
 */
function hizzle_downloads_get_product_downloads( $product_id ) {
	global $wpdb;

	$file_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT file_id FROM {$wpdb->prefix}hizzle_downloads_product_downloads WHERE product_id = %d",
			$product_id
		)
	);

	return wp_parse_id_list( $file_ids );
}

/**
 * Retrieves a download's products.
 *
 * @param int   $download_id Download IDs.
 * @return int[] Product IDs.
 */
function hizzle_downloads_get_download_products( $download_id ) {
	global $wpdb;

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT product_id FROM {$wpdb->prefix}hizzle_downloads_product_downloads WHERE file_id = %d",
			$download_id
		)
	);

	return wp_parse_id_list( $product_ids );
}

/**
 * Syncs product and download relations.
 *
 * @param int   $product_id Product ID.
 * @param int[] $downloadable_files Downloadable file IDs.
 */
function hizzle_downloads_update_product_downloads( $product_id, $downloadable_files ) {
	global $wpdb;

	$product_id         = absint( $product_id );
	$downloadable_files = array_filter( wp_parse_id_list( $downloadable_files ) );

	// Ensure we have a product.
	if ( empty( $product_id ) ) {
		return;
	}

	// Delete old relationships.
	hizzle_downloads_delete_product_downloads( $product_id );

	// Insert new relationships.
	foreach ( $downloadable_files as $download_id ) {
		$wpdb->insert(
			$wpdb->prefix . 'hizzle_downloads_product_downloads',
			array(
				'product_id' => $product_id,
				'file_id'    => $download_id,
			),
			array(
				'%d',
				'%d',
			)
		);
	}
}

/**
 * Syncs download and product relations.
 *
 * @param int   $download_id Download ID.
 * @param int[] $product_ids Product ids.
 */
function hizzle_downloads_update_download_products( $download_id, $product_ids ) {
	global $wpdb;

	$download_id = absint( $download_id );
	$product_ids = array_filter( wp_parse_id_list( $product_ids ) );

	// Ensure we have a download.
	if ( empty( $download_id ) ) {
		return;
	}

	// Delete old relationships.
	hizzle_delete_download_products( $download_id );

	// Insert new relationships.
	foreach ( $product_ids as $product_id ) {
		$wpdb->insert(
			$wpdb->prefix . 'hizzle_downloads_product_downloads',
			array(
				'product_id' => $product_id,
				'file_id'    => $download_id,
			),
			array(
				'%d',
				'%d',
			)
		);
	}

}

/**
 * Queries the download links database.
 *
 * @param array $args Query arguments.
 * @param string $return See hizzle_downloads_query for allowed values.
 * @return int|array|Download_Link[]|Store\Query|WP_Error
 */
function hizzle_get_download_links( $args = array(), $return = 'results' ) {
	return hizzle_downloads_query( 'links', $args, $return );
}

/**
 * Fetch download link by link ID.
 *
 * @param int|string|Download_Link $link_id Permission ID.
 * @return Download_Link|WP_Error permission object if found, error object if not found.
 */
function hizzle_get_download_link( $link_id = 0 ) {
	return hizzle_downloads_get_record( $link_id, 'links' );
}

/**
 * Updates the download links database.
 *
 * @param int $file_id The file ID to update links for.
 * @param array $links The links to update. An array containing sources as keys and external_ids as values.
 */
function hizzle_update_download_links( $file_id, $new_links ) {

    /**
     * Fetch all links.
     *
     * @var Download_Link[] $links
     */
    $links = hizzle_get_download_links( array( 'file_id' => $file_id ) );

    // Group by source and ID.
    $grouped = array();

    foreach ( $links as $link ) {
        $grouped[ $link->get_source() ] = isset( $grouped[ $link->get_source() ] ) ? $grouped[ $link->get_source() ] : array();

        $grouped[ $link->get_source() ][ $link->get_external_id() ] = $link;
    }

    // Loop through the new links.
    foreach ( $new_links as $source => $external_ids ) {
        $external_ids = array_filter( $external_ids );
        $source       = sanitize_text_field( $source );

        // Loop through the external IDs.
        foreach ( $external_ids as $external_id ) {

            // If the link does not exist, create it.
            if ( ! isset( $grouped[ $source ][ $external_id ] ) ) {
                $new_link = hizzle_get_download_link();

                $new_link->set_file_id( $file_id );
                $new_link->set_source( $source );
                $new_link->set_external_id( $external_id );
            } else {

                // Else, remove it from the list of saved links.
                unset( $grouped[ $source ][ $external_id ] );
            }
        }

        // Delete any remaining links.
        foreach ( $grouped[ $source ] as $link ) {
            $link->delete();
        }
    }
}

/**
 * Deltes a file's download links.
 *
 * @param int $file_id The file ID to delete links for.
 */
function hizzle_delete_download_links( $file_id ) {

    /**
     * Fetch all links.
     *
     * @var Download_Link[] $links
     */
    $links = hizzle_get_download_links( array( 'file_id' => $file_id ) );

    // Loop through the links and delete them.
    foreach ( $links as $link ) {
        $link->delete();
    }
}

/**
 * Returns the download method to use for the site.
 *
 * @since  1.0.0
 * @return string
 */
function hizzle_download_method() {
	return apply_filters( 'hizzle_download_method', 'force' );
}
