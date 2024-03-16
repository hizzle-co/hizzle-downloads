<?php

namespace Hizzle\Downloads;

/**
 * Contains the REST API manager class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The REST API manager class.
 *
 */
class REST {

    /**
     * Route controller classes.
     *
     * @param \Hizzle\Store\REST_Controller[]
     */
    public $controllers;

    /**
	 * Loads the class.
	 *
	 * @param \Hizzle\Store\Store $store Data store.
	 */
	public function __construct( $store ) {

        // Init the controller for each collection.
		foreach ( $store->get_collections() as $collection ) {

			// Ignore events that are not associated with any CRUD class.
			if ( empty( $collection->object ) ) {
				continue;
			}

			// Init the controller class.
            $this->controllers[ $collection->get_name() ] = new \Hizzle\Store\REST_Controller( $store->get_namespace(), $collection->get_name() );
		}

		// GitHub updater.
		if ( hizzle_downloads_using_github_updater() ) {
			add_filter( 'hizzle_logger_admin_show_menu', '__return_true' );
			$this->controllers['github_updater'] = new GitHub_Updater( $store->get_namespace() );
		}

		// Software versions.
		if ( apply_filters( 'hizzle_downloads_register_software_versions_rest', hizzle_downloads_using_github_updater() ) ) {
			$this->controllers['software_versions'] = new REST_Versions( $store->get_namespace() );
		}

        // Fire action hook.
        do_action( 'hizzle_downloads_rest_init', $this );
	}

}
