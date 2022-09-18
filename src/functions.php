<?php

use \Hizzle\Store;
use \Hizzle\Downloads\Download;
use \Hizzle\Downloads\Download_Log;

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
	return Store\Collection::instance( 'hizzle_download_' . $name );
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
	return hizzle_downloads_query( 'files', $args, $return );
}

/**
 * Returns an array of available downloads grouped by category.
 *
 * @return array
 */
function hizzle_get_available_downloads() {

	static $grouped;

	if ( ! $grouped ) {

		/** @var Download[] $downloads */
		$downloads = hizzle_get_downloads(
			array(
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			)
		);

		// Group by category.
		$grouped = array();

		foreach ( $downloads as $download ) {
			if ( $download->current_user_can_download() ) {
				$grouped[ $download->get_category() ] = isset( $grouped[ $download->get_category() ] ) ? $grouped[ $download->get_category() ] : array();

				$grouped[ $download->get_category() ][ $download->get_id() ] = $download;
			}
		}

		$grouped = apply_filters( 'hizzle_get_available_downloads', $grouped, $downloads );
	}

	return $grouped;
}


/**
 * Fetch download by download ID.
 *
 * @param int|Download $download_id Download ID object.
 * @return Download|WP_Error Download object if found, error object if not found.
 */
function hizzle_get_download( $download_id = 0 ) {
	return hizzle_downloads_get_record( $download_id, 'files' );
}

/**
 * Fetch download by file name.
 *
 * @param string $file_name File name.
 * @return Download|WP_Error Download object if found, error object if not found.
 */
function hizzle_get_download_by_file_name( $file_name ) {
    return hizzle_get_download( hizzle_downloads_get_collection( 'files' )->get_id_by_prop( 'file_name', sanitize_text_field( $file_name ) ) );
}

/**
 * Fetch download by git URL.
 *
 * @param string $git_url Git repo URL.
 * @return Download|WP_Error Download object if found, error object if not found.
 */
function hizzle_get_download_by_git_url( $git_url ) {
    return hizzle_get_download( hizzle_downloads_get_collection( 'files' )->get_id_by_prop( 'git_url', sanitize_text_field( strtolower( trailingslashit( $git_url ) ) ) ) );
}

/**
 * Deletes a download.
 *
 * @param int|string|Download $download_id Download ID object.
 * @return bool|WP_Error True if deleted, error object if not.
 */
function hizzle_delete_download( $download_id ) {
	$download = hizzle_get_download( $download_id );

	if ( ! is_wp_error( $download ) ) {
		return $download->delete();
	}

	return $download;
}

/**
 * Returns the download method to use for the site.
 *
 * @since  1.0.0
 * @return string
 */
function hizzle_download_method() {
    $download_method = 'xsendfile';

    if ( get_option( 'hizzle_downloads_xsendfile_missing' ) ) {
        $download_method = 'force';
    }

	return apply_filters( 'hizzle_download_method', $download_method );
}

/**
 * Queries the download logs database.
 *
 * @param array $args Query arguments.
 * @param string $return See hizzle_downloads_query for allowed values.
 * @return int|array|Download_Log[]|Store\Query|WP_Error
 */
function hizzle_get_download_logs( $args = array(), $return = 'results' ) {
	return hizzle_downloads_query( 'logs', $args, $return );
}

/**
 * Fetch download link by link ID.
 *
 * @param int|string|Download_Log $log_id Log ID.
 * @return Download_Log|WP_Error log object if found, error object if not found.
 */
function hizzle_get_download_log( $log_id = 0 ) {
	return hizzle_downloads_get_record( $log_id, 'logs' );
}

/**
 * Deletes a download log.
 *
 * @param int|string|Download_Log $download_log Download log id or object.
 * @return bool|WP_Error True if deleted, error object if not.
 */
function hizzle_delete_download_log( $download_log ) {
	$log = hizzle_get_download_log( $download_log );

	if ( ! is_wp_error( $log ) ) {
		return $log->delete();
	}

	return $log;
}

/**
 * Get current user IP Address.
 *
 * @return string
 */
function hizzle_downloads_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	return '';
}

/**
 * Prepares users for use in select boxes.
 *
 * @return array
 */
function hizzle_prepare_users_for_select() {
    $users = get_users(
        array(
            'fields'  => array( 'ID', 'user_login', 'display_name' ),
            'orderby' => 'display_name',
		    'order'   => 'ASC',
        )
    );

    $prepared = array();
    foreach ( $users as $user ) {
        $prepared[ $user->ID ] = $user->display_name . ' (' . $user->user_login . ')';
    }

    return $prepared;
}

/**
 * Retrieves the allowed restrictions for a file.
 *
 * @return array
 */
function hizzle_downloads_get_conditional_logic_rules() {

    $rules = array(
        'user_role'  => array(
            'label'       => __( 'User Role', 'hizzle-downloads' ),
            'description' => __( 'Restrict access to this file to specific user roles.', 'hizzle-downloads' ),
            'options'     => wp_roles()->get_names(),
        ),
        'user_id'    => array(
            'label'       => __( 'User', 'hizzle-downloads' ),
            'description' => __( 'Restrict access to this file to specific users.', 'hizzle-downloads' ),
            'options'     => hizzle_prepare_users_for_select(),
        ),
        'ip_address' => array(
            'label'       => __( 'IP Address', 'hizzle-downloads' ),
            'description' => __( 'Restrict access to this file to specific IP addresses.', 'hizzle-downloads' ),
        ),
    );

	// Add newsletter subscription status.
	if ( function_exists( 'noptin' ) ) {
		$rules['noptin'] = array(
			'label'       => __( 'Newsletter Status', 'hizzle-downloads' ),
			'description' => __( 'Restrict access to this file to users who are subscribed to your newsletter.', 'hizzle-downloads' ),
			'options'     => array(
				'subscribed'   => __( 'Subscribed', 'hizzle-downloads' ),
				'unsubscribed' => __( 'Unsubscribed', 'hizzle-downloads' ),
			),
		);
	}

	// TODO: Add support for WooCommerce subscriptions.
    return apply_filters( 'hizzle_downloads_get_conditional_logic_rules', $rules );
}

/**
 * Checks the IP address conditional rule.
 *
 * @param bool $is_valid Whether or not the rule is valid.
 * @param  array $rule The rule to check.
 * @return bool
 */
function hizzle_download_conditional_logic_rule_met_ip_address( $is_valid, $rule ) {
	return hizzle_downloads_get_ip_address() === trim( $rule['value'] );
}
add_filter( 'hizzle_download_conditional_logic_rule_met_ip_address', 'hizzle_download_conditional_logic_rule_met_ip_address', 10, 2 );

/**
 * Checks the user id conditional rule.
 *
 * @param bool $is_valid Whether or not the rule is valid.
 * @param  array $rule The rule to check.
 * @return bool
 */
function hizzle_download_conditional_logic_rule_met_user_id( $is_valid, $rule ) {
	return get_current_user_id() === intval( $rule['value'] );
}
add_filter( 'hizzle_download_conditional_logic_rule_met_user_id', 'hizzle_download_conditional_logic_rule_met_user_id', 10, 2 );

/**
 * Checks the user role conditional rule.
 *
 * @param bool $is_valid Whether or not the rule is valid.
 * @param  array $rule The rule to check.
 * @return bool
 */
function hizzle_download_conditional_logic_rule_met_user_role( $is_valid, $rule ) {
	$user      = wp_get_current_user();
	$user_role = empty( $user ) ? '' : current( $user->roles );

	return trim( $rule['value'] ) === $user_role;
}
add_filter( 'hizzle_download_conditional_logic_rule_met_user_role', 'hizzle_download_conditional_logic_rule_met_user_role', 10, 2 );

/**
 * Checks the newsletter subscription rule.
 *
 * @param bool $is_valid Whether or not the rule is valid.
 * @param  array $rule The rule to check.
 * @return bool
 */
function hizzle_download_conditional_logic_rule_met_noptin( $is_valid, $rule ) {

	$is_subscribed = function_exists( 'noptin_is_subscriber' ) && noptin_is_subscriber();

	return 'subscribed' === $rule['value'] ? $is_subscribed : ! $is_subscribed;
}
add_filter( 'hizzle_download_conditional_logic_rule_met_noptin', 'hizzle_download_conditional_logic_rule_met_noptin', 10, 2 );

/**
 * Displays available downloads.
 *
 * @param array $downloads
 */
function hizzle_downloads_display_downloads( $downloads ) {
	include plugin_dir_path( __FILE__ ) . 'html-display-downloads.php';
}

/**
 * Checks whether or not we're using the GitHub updater.
 *
 * @return bool
 */
function hizzle_downloads_using_github_updater() {
	return defined( 'HIZZLE_DOWNLOADS_GITHUB_WEBHOOK_TOKEN' ) && defined( 'HIZZLE_DOWNLOADS_GITHUB_ACCESS_TOKEN' );
}
