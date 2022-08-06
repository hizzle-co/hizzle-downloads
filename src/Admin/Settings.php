<?php

namespace Hizzle\Downloads\Admin;

/**
 * Contains the main settings admin class
 *
 * @package    Hizzle Downloads
 * @subpackage Admin
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The settings admin Class.
 *
 */
class Settings {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Save settings.
		add_action( 'hizzle_downloads_admin_save_settings', array( $this, 'save_settings' ) );

		// Display setings.
		add_action( 'hizzle_downloads_admin_display_settings', array( $this, 'display_settings' ) );

	}

	/**
	 * Displays the settings admin page.
	 *
	 */
	public function display_settings() {
		require_once dirname( __FILE__ ) . '/views/html-settings-form.php';
	}

	/**
	 * Saves submitted settings.
	 *
	 * @param  array   $data Settings data.
	 * @since  1.0.0
	 */
	public function save_settings( $data ) {
		// Nonce and capability were checked in Admin::maybe_do_action();

		update_option( 'hizzle_download_options', wp_kses_post_deep( $data['hizzle_downloads'] ) );

		Notices::add_custom_notice( 'changes_saved', __( 'Your changes were saved successfully.', 'hizzle-downloads' ) );

	}

}
