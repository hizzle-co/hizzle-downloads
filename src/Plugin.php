<?php

namespace Hizzle\Downloads;

use \Hizzle\Store\Store;
use \Hizzle\Store\Webhooks;

/**
 * Contains the main plugin class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin Class.
 *
 */
class Plugin {

	/**
	 * Admin handler.
	 *
	 * This is only set on admin screens.
	 *
	 * @var   Admin\Admin
	 * @since 1.0.0
	 */
	public $admin;

	/**
	 * The data store.
	 *
	 * @var Store
	 */
	public $store;

	/**
	 * Webhooks manager.
	 *
	 * @var Webhooks
	 */
	public $webhooks;

	/**
	 * REST API manager.
	 *
	 * @var REST
	 */
	public $rest_api;

	/**
	 * Logger.
	 *
	 * @var \Hizzle\Logger\Logger
	 */
	public $logger;

	/**
	 * Download handler.
	 *
	 * @var Download_Handler
	 */
	public $handler;

	/**
	 * Stores the main plugin instance.
	 *
	 * @access      private
	 * @var         Plugin $instance The main plugin instance.
	 * @since       1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      Plugin The main plugin instance.
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		$includes = plugin_dir_path( __FILE__ );

		// Functions.
		require_once $includes . 'functions.php';

	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'init', array( $this, 'init' ), 0 );

	}

	/**
	 * When WP has loaded all plugins, trigger the `hizzle_downloads_loaded` hook.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {

		do_action( 'hizzle_downloads_before_load' );

		if ( is_admin() ) {
			$this->admin = new Admin\Admin();
		}

		// Init the data store.
		$this->store = new Store( 'hizzle_download', apply_filters( 'hizzle_downloads_database_schema', hizzle_downloads_get_data( 'db-schema' ) ) );
		$this->store = apply_filters( 'hizzle_downloads_db_store', $this->store );

		// Init the webhooks manager.
		$this->webhooks = new Webhooks( $this->store );

		// Init the REST API.
		$this->rest_api = new REST( $this->store );

		// Logger.
		$this->logger = apply_filters( 'hizzle_downloads_logger', \Hizzle\Logger\Logger::get_instance() );

		// Download handler.
		$this->handler = new Download_Handler();

		// Maybe install.
		Installer::init();

		do_action( 'hizzle_downloads_loaded' );
	}

	/**
	 * Init after WordPress inits.
	 */
	public function init() {

		// Before init action.
		do_action( 'before_hizzle_downloads_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'hizzle_downloads_init' );

	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/plugins/hizzle-downloads-LOCALE.mo
	 *      - WP_PLUGIN_DIR/hizzle-downloads/languages/hizzle-downloads-LOCALE.mo
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'hizzle-downloads',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);

	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', HIZZLE_DOWNLOADS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( HIZZLE_DOWNLOADS_PLUGIN_FILE ) );
	}

}
