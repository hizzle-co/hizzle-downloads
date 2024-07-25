<?php
/**
 * Plugin Name: Hizzle Downloads
 * Plugin URI: https://hizzle.co/download-manager/
 * Description: A lightweight download manager plugin.
 * Version: 1.1.1
 * Author: Hizzle
 * Author URI: https://hizzle.co
 * Text Domain: hizzle-downloads
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.0
 *
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
if ( ! defined( 'HIZZLE_DOWNLOADS_PLUGIN_FILE' ) ) {
	define( 'HIZZLE_DOWNLOADS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'HIZZLE_DOWNLOADS_VERSION' ) ) {
	define( 'HIZZLE_DOWNLOADS_VERSION', '1.1.1' );
}

if ( ! defined( 'HIZZLE_DOWNLOADS_DB_VERSION' ) ) {
	define( 'HIZZLE_DOWNLOADS_DB_VERSION', 3 );
}

// Include the auto loader.
require 'vendor/autoload.php';

/**
 * Returns the main plugin instance.
 *
 * @since  1.0.0
 * @return Hizzle\Downloads\Plugin
 */
function hizzle_downloads() {
	return Hizzle\Downloads\Plugin::instance();
}

// Kickstart the plugin.
hizzle_downloads();
