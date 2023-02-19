<?php

namespace Hizzle\Downloads\Admin;

/**
 * Admin section
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin main class
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Local path to this plugin's admin directory
	 *
	 * @access      public
	 * @since       1.0.0
	 * @var         string|null
	 */
	public $admin_path = null;

	/**
	 * Web path to this plugin's admin directory
	 *
	 * @access public
	 * @since  1.0.0
	 * @var    string|null
	 */
	public $admin_url = null;

	/**
	 * Admin menu handler.
	 *
	 * @var Menus
	 */
	public $admin_menus;

	/**
	 * Admin downloads.
	 *
	 * @var Downloads
	 */
	public $downloads;

	/**
	 * Admin settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Admin reports.
	 *
	 * @var Reports
	 */
	public $reports;

	/**
	 * Constructor
	 *
	 * All plugins have already been loaded at this point.
	 */
	public function __construct() {

		do_action( 'hizzle_downloads_before_admin_load', $this );

		// Set global variables.
		$this->admin_path = plugin_dir_path( __FILE__ );
		$this->admin_url  = plugins_url( '/', __FILE__ );

		// Init class vars.
		$this->admin_menus = new Menus();
		$this->downloads   = new Downloads();
		$this->settings    = new Settings();

		// Init notices.
		Notices::init();

		// Add hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqeue_scripts' ), 0 );
		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );
		add_action( 'hizzle_downloads_admin_delete_download', array( $this, 'admin_delete_download' ) );

		do_action( 'hizzle_downloads_after_admin_load', $this );
	}

	/**
	 * Register admin scripts
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function enqeue_scripts() {
		$screen  = get_current_screen();
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : HIZZLE_DOWNLOADS_VERSION;
		$prefix  = sanitize_title( __( 'Hizzle Downloads', 'hizzle-downloads' ) );

		if ( $screen && false !== strpos( $screen->id, $prefix ) ) {

			// Load admin CSS && JS.
			wp_enqueue_style( 'hizzle-downloads-admin', hizzle_downloads()->plugin_url() . '/assets/admin.css', array(), $version );
			wp_enqueue_style( 'select2', hizzle_downloads()->plugin_url() . '/assets/select2.min.css', array(), '4.1.0' );
			wp_enqueue_script( 'select2', hizzle_downloads()->plugin_url() . '/assets/select2.min.js', array( 'jquery' ), '4.1.0', true );

			// Dowload editing scripts.
			if ( isset( $_GET['hizzle_download'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_media();
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'vue', hizzle_downloads()->plugin_url() . '/assets/vue.js', array(), '3.2.37', true );
				wp_enqueue_script( 'hizzle-edit-download', hizzle_downloads()->plugin_url() . '/assets/edit-download.js', array( 'jquery', 'vue', 'wp-api-fetch', 'wp-i18n' ), $version, true );
			}

			// Settings.
			if ( $prefix . '_page_hizzle-download-settings' === $screen->id ) {
				wp_enqueue_script( 'hizzle-settings', hizzle_downloads()->plugin_url() . '/assets/settings.js', array( 'jquery' ), $version, true );
			}
		}

	}

	/**
	 * Returns an admin action URL.
	 *
	 * @param  string $action Action name.
	 * @param  string $base_url Optional base URL. Defaults to current URL.
	 * @since  1.0.0
	 * @return string
	 */
	public static function action_url( $action, $base_url = false ) {

		return add_query_arg(
			array(
				'hizzle_downloads_admin_action' => rawurlencode( $action ),
				'hizzle_downloads_nonce'        => rawurlencode( wp_create_nonce( sanitize_key( "hizzle_downloads_{$action}_nonce" ) ) ),
			),
			$base_url
		);

	}

	/**
	 * Displays a hidden actions field.
	 *
	 * @param  string $action Action name.
	 * @since  1.0.0
	 * @return string
	 */
	public static function action_field( $action ) {
		printf(
			'<input type="hidden" name="hizzle_downloads_admin_action" value="%s" />',
			esc_attr( $action )
		);

		wp_nonce_field( "hizzle_downloads_{$action}_nonce", 'hizzle_downloads_nonce' );

	}

	/**
	 * Does an action
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function maybe_do_action() {

		// Make sure that the user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if there is an action.
		if ( empty( $_REQUEST['hizzle_downloads_admin_action'] ) ) {
			return;
		}

		// Check the nonce.
		$action = sanitize_text_field( wp_unslash( $_REQUEST['hizzle_downloads_admin_action'] ) );
		if ( ! isset( $_REQUEST['hizzle_downloads_nonce'] ) || ! wp_verify_nonce( $_REQUEST['hizzle_downloads_nonce'], sanitize_key( "hizzle_downloads_{$action}_nonce" ) ) ) {
			return;
		}

		// Do the action.
		$data = empty( $_POST ) ? urldecode_deep( $_GET ) : wp_unslash( $_POST );
		do_action( "hizzle_downloads_admin_{$action}", wp_kses_post_deep( $data ), $this );

	}

	/**
	 * Deletes a download.
	 *
	 * @param  array $data Data.
	 */
	public function admin_delete_download( $data ) {

		if ( true === hizzle_delete_download( $data['hizzle_download'] ) ) {
			Notices::add_custom_notice( 'deleted_download', __( 'Download deleted.', 'hizzle-downloads' ) );
		} else {
			Notices::add_custom_notice( 'error_deleting_downloading', __( 'Download could not be deleted.', 'hizzle-downloads' ) );
		}

		wp_safe_redirect(
			remove_query_arg(
				array(
					'hizzle_download',
					'hizzle_downloads_admin_action',
					'hizzle_downloads_nonce',
				)
			)
		);
		exit;
	}
}
