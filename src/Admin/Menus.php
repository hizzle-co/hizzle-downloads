<?php

namespace Hizzle\Downloads\Admin;

/**
 * Admin menus handler
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin menus class.
 */
class Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( $this, 'downloads_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 25 );

		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {

		add_menu_page(
			__( 'Hizzle Downloads', 'hizzle-downloads' ),
			__( 'Hizzle Downloads', 'hizzle-downloads' ),
			'manage_options',
			'hizzle-downloads',
			null,
			'dashicons-open-folder',
			'33.33333334'
		);

	}

	/**
	 * Add downloads item.
	 */
	public function downloads_menu() {
		add_submenu_page(
			'hizzle-downloads',
			__( 'Hizzle Pay Downloads', 'hizzle-downloads' ),
			__( 'Downloads', 'hizzle-downloads' ),
			'manage_options',
			'hizzle-downloads',
			array( $this, 'downloads_page' )
		);
	}

	/**
	 * Displays the downloads page.
	 */
	public function downloads_page() {
		do_action( 'hizzle_admin_display_downloads' );
	}

	/**
	 * Registers the settings menu.
	 */
	public function settings_menu() {
		add_submenu_page(
			'hizzle-downloads',
			__( 'Hizzle Pay Settings', 'hizzle-downloads' ),
			__( 'Settings', 'hizzle-downloads' ),
			'manage_options',
			'hizzle-download-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Displays the settings page.
	 */
	public function settings_page() {
		do_action( 'hizzle_downloads_admin_display_settings' );
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'hizzle_downloads_per_page', 'hizzle_downloads_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

}
