<?php

namespace Hizzle\Downloads\Admin;

/**
 * Display notices in admin
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin notices Class.
 */
class Notices {

	/**
	 * Stores notices.
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Array of notices - name => callback.
	 *
	 * @var array
	 */
	private static $core_notices = array(
		'base_tables_missing' => 'base_tables_missing_notice',
	);

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$notices = (array) get_option( 'hizzle_downloads_admin_notices', array() );

		add_action( 'hizzle_downloads_admin_hide_notice', array( __CLASS__, 'admin_hide_notice' ) );

		// Display notices to admins.
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
		}

	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'hizzle_downloads_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices = array();
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 */
	public static function add_notice( $name ) {
		self::$notices = array_unique( array_merge( self::get_notices(), array( $name ) ) );

		self::store_notices();
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 */
	public static function remove_notice( $name ) {
		self::$notices = array_diff( self::get_notices(), array( $name ) );
		delete_option( 'hizzle_downloads_admin_notice_' . $name );

		self::store_notices();
	}

	/**
	 * See if a notice is being shown.
	 *
	 * @param string $name Notice name.
	 *
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices(), true );
	}

	/**
	 * Returns the URL to hide a given notice.
	 *
	 * @param $name Notice name.
	 */
	public static function get_notice_hide_url( $name, $base_url = false ) {
		return Admin::action_url( 'hide_notice', add_query_arg( 'hizzle_download_notice', rawurlencode( $name ), $base_url ) );
	}

	/**
	 * Hide a notice when clicked by admin.
	 *
	 * @param array $args
	 */
	public static function admin_hide_notice( $args ) {
		if ( isset( $args['hizzle_download_notice'] ) ) {
			self::hide_notice( sanitize_text_field( $args['hizzle_download_notice'] ) );
			wp_safe_redirect( remove_query_arg( array( 'hizzle_download_notice', 'hizzle_download_admin_action', 'hizzle_download_nonce' ) ) );
			exit;
		}
	}

	/**
	 * Hide a single notice.
	 *
	 * @param $name Notice name.
	 */
	public static function hide_notice( $name ) {
		self::remove_notice( $name );

		update_user_meta( get_current_user_id(), 'hizzle_download_dismissed_' . $name . '_notice', true );

		do_action( 'hizzle_download_hide_' . $name . '_notice' );
	}

	/**
	 * Checks if a given notice was hidden.
	 *
	 * @param $name Notice name.
	 */
	public static function is_notice_hidden( $name ) {
		return (bool) get_user_meta( get_current_user_id(), 'hizzle_download_dismissed_' . $name . '_notice', true );
	}

	/**
	 * Add notices if needed.
	 */
	public static function add_notices() {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {

			// Abort if the user has already dismissed the notice.
			if ( self::is_notice_hidden( $notice ) ) {
				continue;
			}

			if ( ! empty( self::$core_notices[ $notice ] ) ) {
				add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'output_custom_notices' ) );
			}
		}

	}

	/**
	 * Add a custom notice.
	 *
	 * @param string $name   Notice name.
	 * @param array|string  $notice Notice args.
	 */
	public static function add_custom_notice( $name, $notice ) {
		self::add_notice( $name );

		if ( is_string( $notice ) ) {
			$notice = array(
				'type' => 'info',
				'msg'  => $notice,
			);
		}

		update_option( 'hizzle_download_admin_notice_' . $name, wp_kses_post_deep( $notice ) );
	}

	/**
	 * Output any stored custom notices.
	 */
	public static function output_custom_notices() {
		$notices = self::get_notices();

		foreach ( $notices as $notice ) {

			// Skip over core notices.
			if ( isset( self::$core_notices[ $notice ] ) ) {
				continue;
			}

			// Fetch notice data.
			$notice_data = get_option( 'hizzle_download_admin_notice_' . $notice );

			if ( is_array( $notice_data ) ) {
				include dirname( __FILE__ ) . '/views/html-notice-custom.php';
			}

			// Do not show the notice again.
			self::remove_notice( $notice );

		}
	}

	/**
	 * Notice about secure connection.
	 */
	public static function secure_connection_notice() {
		if ( is_ssl() ) {
			self::remove_notice( 'no_secure_connection' );
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-secure-connection.php';
	}

	/**
	 * Notice about base tables missing.
	 */
	public static function base_tables_missing_notice() {
		include dirname( __FILE__ ) . '/views/html-notice-base-table-missing.php';
	}

}
