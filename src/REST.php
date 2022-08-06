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
     * @param \Hizzle\Store\REST[]
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
            $this->controllers[ $collection->get_name() ] = new \Hizzle\Store\REST( $store->get_namespace(), $collection->get_name() );
		}

        // Fire action hook.
        do_action( 'hizzle_downloads_rest_init', $this );
	}

}
