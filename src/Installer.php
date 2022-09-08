<?php

namespace Hizzle\Downloads;

use \Hizzle\Downloads\Admin\Notices;

/**
 * Installation related functionality.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Installer Class.
 */
class Installer {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'hizzle_downloads_verify_db_tables', array( __CLASS__, 'admin_verify_base_tables' ) );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Check the plugin version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( self::needs_db_update() ) {
			self::install();
			do_action( 'hizzle_downloads_updated' );
		}
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  1.0.0
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = (int) get_option( 'hizzle_downloads_db_version', null );
		return empty( $current_db_version ) || $current_db_version < HIZZLE_DOWNLOADS_DB_VERSION;
	}

	/**
	 * Install.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'hizzle_downloads_installing' ) ) {
			return;
		}

		// Prevent other instances from running simultaneously.
		set_transient( 'hizzle_downloads_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		// Upgrade from current db version.
		self::upgrade_from( (int) get_option( 'hizzle_downloads_db_version', null ) );

		// Verify that all tables were created.
		self::verify_base_tables( true );

		// Allow other instances to run.
		delete_transient( 'hizzle_downloads_installing' );

		// Fired after install or upgrade.
		do_action( 'hizzle_downloads_installed' );
	}

	/**
	 * Fired when the admin clicks on the verify tables button.
	 */
	public static function admin_verify_base_tables() {

		$missing_tables = self::verify_base_tables( true );
		if ( 0 < count( $missing_tables ) ) {

			Notices::add_custom_notice(
				'verify_base_tables_error',
				array(
					'type'    => 'error',
					'content' => __( 'An error occurred while creating required database tables.', 'hizzle-downloads' ),
				)
			);

		} else {

			Notices::add_custom_notice(
				'verify_base_tables_ok',
				array(
					'type'    => 'success',
					'content' => __( 'Successfuly created the required database tables.', 'hizzle-downloads' ),
				)
			);

		}

		wp_safe_redirect( remove_query_arg( array( 'hizzle_downloads_admin_action', 'hizzle_downloads_nonce' ) ) );
		exit;
	}

	/**
	 * Check if all the base tables are present.
	 *
	 * @param bool $execute Whether to execute get_schema queries as well.
	 *
	 * @return array List of querues.
	 */
	public static function verify_base_tables( $execute = false ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( $execute ) {
			self::create_tables();
		}

		$queries        = dbDelta( self::get_schema(), false );
		$missing_tables = array();
		foreach ( $queries as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$missing_tables[] = $table_name;
			}
		}

		if ( 0 < count( $missing_tables ) ) {
			Notices::add_notice( 'base_tables_missing' );
			update_option( 'hizzle_downloads_schema_missing_tables', $missing_tables );
		} else {
			Notices::remove_notice( 'base_tables_missing' );
			delete_option( 'hizzle_downloads_schema_missing_tables' );
			self::update_db_version();
		}

		return $missing_tables;
	}

	/**
     * Upgrades the database.
     *
     * @param int $upgrade_from The current database version.
     */
    public static function upgrade_from( $upgrade_from = null ) {

		if ( is_null( $upgrade_from ) ) {
			$upgrade_from = (int) get_option( 'hizzle_downloads_db_version', null );
		}

        $method = "upgrade_from_$upgrade_from";

        if ( is_callable( array( __CLASS__, $method ) ) ) {
            self::$method();
        }

    }

	/**
     * Do a fresh install.
     *
     */
    protected static function upgrade_from_0() {
		self::create_files();
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		update_option( 'hizzle_downloads_db_version', is_null( $version ) ? HIZZLE_DOWNLOADS_DB_VERSION : $version );
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );

	}

	/**
	 * Get Table schema.
	 *
	 * @return array
	 */
	private static function get_schema() {
		return hizzle_downloads()->store->get_schema();
	}

	/**
	 * Returns a list of db tables.
	 *
	 * @return array db tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}hizzle_download_files",
			"{$wpdb->prefix}hizzle_download_links",
		);

		return apply_filters( 'hizzle_downloads_install_get_tables', $tables );
	}

	/**
	 * Drop db tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param array $tables List of tables that will be deleted by WP.
	 *
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}

	/**
	 * Create files/directories.
	 *
	 * @param string $base_dir
	 */
	public static function prepare_upload_dir( $base_dir ) {

		$files = array(
			array(
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'file'    => '.htaccess',
				'content' => 'redirect' === hizzle_download_method() ? 'Options -Indexes' : 'deny from all',
			),
		);

		foreach ( $files as $file ) {
			$file_path = trailingslashit( $base_dir ) . $file['file'];

			if ( ! file_exists( $file_path ) && wp_mkdir_p( $base_dir ) ) {
				self::create_file( $file_path, $file['content'] );
			}
		}

	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {

		// Install files and folders for uploading files and prevent hotlinking.
		$upload_dir = wp_get_upload_dir();

		self::prepare_upload_dir( $upload_dir['basedir'] . '/hizzle_uploads' );

	}

	/**
	 * Creates a file
	 *
	 * @param string $file_path
	 * @param string $file_contents
	 */
	public static function create_file( $file_path, $file_content ) {
		$file_handle = @fopen( $file_path, 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( $file_handle ) {
			fwrite( $file_handle, $file_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		} else {
			hizzle_downloads()->logger->error( sprintf( 'Could not create %s file.', $file_path ), 'hizzle_downloads' );
		}
	}

}
