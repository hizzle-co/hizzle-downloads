<?php
/**
 * Uninstall plugin.
 *
 * Uninstalling the plugin deletes products, pages, tables, and options.
 *
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb, $wp_version;

// Admin tables.
require_once dirname( __FILE__ ) . '/src/Installer.php';
Hizzle\Downloads\Installer::drop_tables();

// Options.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'hizzle\_downloads\_%';" );

// Clear any cached data that has been removed.
wp_cache_flush();
