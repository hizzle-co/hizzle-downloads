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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );

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

		// Register block.
		$this->register_block_type();

		// Register shortcode.
		add_shortcode( 'hizzle-downloads', array( $this, 'shortcode' ) );
		add_shortcode( 'hizzle-download', array( $this, 'download_link_shortcode' ) );

		// Init action.
		do_action( 'hizzle_downloads_init' );

	}

	/**
	 * Register the available downloads block type.
	 */
	public function register_block_type() {

		// Bail if register_block_type does not exist (available since WP 5.0)
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Fire pre-registration hook.
		do_action( 'before_register_hizzle_downloads_block_type', $this );

		// Register the block type.
		register_block_type(
			'hizzle/downloads',
			array(
				'render_callback' => array( $this, 'shortcode' ),
			)
		);

		// Fire post-registration hook.
		do_action( 'after_register_hizzle_downloads_block_type', $this );

	}

	/**
	 * Load gutenberg files
	 *
	 */
	public function enqueue_gutenberg_assets() {

		wp_enqueue_script(
			'hizzle-downloads-block',
			$this->plugin_url() . '/assets/block.js',
			array( 'wp-blocks', 'wp-element' ),
			filemtime( $this->plugin_path() . '/assets/block.js' ),
			true
		);

		$locale = array(
			'blockName'        => __( 'Hizzle > Available Downloads', 'hizzle-downloads' ),
			'blockDescription' => __( 'Displays a list of available downloads for the current user', 'hizzle-downloads' ),
			'placeholderText'  => __( 'Available downloads will appear here', 'hizzle-downloads' ),
		);

		wp_localize_script( 'hizzle-downloads-block', 'hizzleDownloads', $locale );
	}

	/**
	 * Generates the available downloads shortcode.
	 *
	 * @param array $attributes
	 * @param string $content
	 * @return string
	 */
	public function shortcode( $atts = array(), $content = '' ) {
		$downloads = hizzle_get_available_downloads();
		$html      = apply_filters( 'hizzle_downloads_shortcode_html', null, $downloads, $atts, $content );

		if ( ! is_null( $html ) ) {
			return $html;
		}

		ob_start();
		hizzle_downloads_display_downloads( $downloads, $atts );
		return ob_get_clean();
	}

	/**
	 * Generates the download link shortcode.
	 *
	 * @param array $attributes
	 * @param string $content
	 * @return string
	 */
	public function download_link_shortcode( $attributes = array(), $content = '' ) {
		$download_id = isset( $attributes['id'] ) ? $attributes['id'] : null;
		$download    = hizzle_get_download( $download_id );

		// Abort if the current user can't download the file.
		if ( is_wp_error( $download ) || ! $download->exists() || ! $download->current_user_can_download() ) {
			return '';
		}

		$atts = array(
			'id'     => 'hizzle-download-link-' . $download->get_id() . '-' . wp_unique_id(),
			'target' => isset( $attributes['target'] ) ? $attributes['target'] : false,
			'class'  => isset( $attributes['class'] ) ? $attributes['class'] : 'hizzle-download-link',
		);

		$anchor = empty( $content ) ? __( 'Download', 'hizzle-downloads' ) : $content;

		return sprintf(
			'<a href="%s" class="%s" id="%s" %s>%s</a>',
			esc_url( $download->get_download_url() ),
			esc_attr( $atts['class'] ),
			esc_attr( $atts['id'] ),
			$atts['target'] ? 'target="_blank"' : '',
			wp_kses_post( $anchor )
		);
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
