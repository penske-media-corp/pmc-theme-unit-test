<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Menus_Importer extends PMC_Singleton {

	private $_domain;

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {

	}

	/**
	 * Get the URL of the current item by replacing the imported URL with the current domain
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param @type string The original live URL
	 *
	 * @return string The local URL
	 *
	 */
	private function _get_menu_url( $live_url ) {

		$url_host     = parse_url( $live_url, PHP_URL_HOST );
		$current_host = parse_url( get_home_url(), PHP_URL_HOST );
		$current_url  = str_replace( $url_host, $current_host, $live_url );

		return $current_url;

	}

	/**
	 * Get the ID of the type of object the menu is associated with
	 * e.g Taxonomy Term ID or Page ID or Post ID etc
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param @type int the type object ID,
	 *
	 * @type string the type_family such as Taxonomy, Page or Post
	 * @type the type value as string such as Awards, Page title etc
	 *
	 * @return int|WP_Error The menu item object id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _get_type_object_id( $content_id, $type_family, $type ) {

		$menu_item_object_id = 0;

		if ( 'taxonomy' === $type_family ) {

			$menu_item_object_id = XMLRPC_Importer::get_instance()->get_taxonomy_term_by_id( $type, $content_id );

		} else if ( 'post_type' === $type_family ) {

			$menu_item_object_id = $this->call_post_rest_api_route( $type, $content_id );

		}

		return $menu_item_object_id;

	}

	/**
	 * Insert a new Menu Item to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-28 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Menu Item meta data
	 *
	 * @return int|WP_Error The Menu Item Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_menu_item( $menu_id, $menu_item, $menu_item_id = 0 ) {

		$menu_item_db_id = 0;
		$time            = date( '[d/M/Y:H:i:s]' );

		try {

			$menu_item_db_id = wp_update_nav_menu_item( $menu_id, $menu_item_id, array(
				'menu-item-object-id'   => $this->_get_type_object_id( $menu_item['content_id'], $menu_item['type_family'], $menu_item['type'] ),
				'menu-item-object'      => $menu_item['type'],
				'menu-item-type'        => $menu_item['type_family'],
				'menu-item-title'       => $menu_item['name'],
				'menu-item-url'         => $this->_get_menu_url( $menu_item['url'] ),
				'menu-item-description' => $menu_item['description'],
				'menu-item-attr-title'  => $menu_item['link_title'],
				'menu-item-target'      => $menu_item['link_target'],
				'menu-item-classes'     => $menu_item['classes'],
				'menu-item-xfn'         => $menu_item['xfn'],
			) );

			if ( is_wp_error( $menu_item_db_id ) ) {

				error_log( $time . " WP_Error -- " . $menu_item_db_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			} else {

				wp_set_object_terms( $menu_item_db_id, $menu_id, 'nav_menu' );
				error_log( "{$time} -- Menu Item Added **-- {$menu_item['name']} --** with ID = {$menu_item_db_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}

		} catch ( \Exception $e ) {

			error_log( $time . " -- " . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $menu_item_db_id;
	}

	/**
	 * Insert a new Menu and new Menu Item to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Menu meta data
	 *
	 * @return int|WP_Error The Menu Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_menu( $menu_json ) {

		$menu_name = $menu_json["name"];
		$items     = array();
		$time      = date( '[d/M/Y:H:i:s]' );

		try {
			// Does the menu exist already?
			$menu_exists = wp_get_nav_menu_object( $menu_name );


			// If it doesn't exist, let's create it.
			if ( ! $menu_exists ) {
				$menu_id = wp_create_nav_menu( $menu_name );
			} else {
				$menu_id = $menu_exists->term_id;

				error_log( "{$time} -- Exists Menu **-- {$menu_name} --** with ID = {$menu_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

				$items = wp_get_nav_menu_items( $menu_id );
			}

			if ( ! empty( $menu_json["items"] ) ) {

				foreach ( $menu_json["items"] as $menu_item ) {
					$menu_item_id = 0;
					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( $item['post_title'] === $menu_item['name'] ) {
								$menu_item_id = $item->ID;
							}
						}
					}

					$this->_save_menu_item( $menu_id, $menu_item, $menu_item_id );
				}
			}

			// Grab the theme locations and assign our newly-created menu
			if ( ! empty( $menu_json["locations"] ) ) {

				$menu_locations[] = $menu_json["locations"];

				foreach ( $menu_locations as $menu_location ) {

					if ( ! has_nav_menu( $menu_location ) ) {
						$locations                   = get_theme_mod( 'nav_menu_locations' );
						$locations[ $menu_location ] = $menu_id;
						set_theme_mod( 'nav_menu_locations', $locations );

					}
				}
			}
		} catch ( \Exception $e ) {

			error_log( $time . " -- " . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}

	}

	/**
	 * Assemble Nav Menu data from API and inserts new Nav Menu.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Nav Menu object
	 *
	 * @return array of Nav Menus ids on success.
	 */
	public function instant_menus_import( $menus_json ) {

		$menus_info = array();

		foreach ( $menus_json as $menu_json ) {

			$menu_id = $this->_save_menu( $menu_json );

			if ( ! empty( $menu_id ) ) {

				$menus_info[] = $menu_id;

			}

		}

		return $menus_info;
	}


	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-16 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data, $domain = '' ) {

		$this->_domain = $domain;

		return $this->instant_menus_import( $api_data );

	}

	/**
	 * Access endpoints to make call to REST API
	 *
	 * This method will make a call to the public REST API
	 * and fetch data from live site and save to the current site DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function call_post_rest_api_route( $type, $post_id ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$params['route']  = array(
				"name"         => "posts/" . $post_id,
				"access_token" => false,
				"query_params" => array(
					"type" => $type,
				),
				"route_index"  => 'posts'
			);
			$params['domain'] = $this->_domain;

			$pages = REST_API_Router::get_instance()->call_rest_api_route( $params );

			if ( is_wp_error( $pages ) ) {

				error_log( $time . " -- " . $pages->get_error_messages() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return $pages;

			} else if ( empty( $pages ) ) {

				return new \WP_Error( 'unauthorized_access', " Failed to attach menu to " . $type . " object with id - " . $post_id );

			}

		} catch ( \Exception $e ) {
			error_log( $time . " -- " . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}

		return $pages[ $post_id ];
	}


}
