<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Menus_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06, for PPT-5077, Archana Mandhare
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {

	}

	private function _get_menu_url( $live_url ) {

		$url_host     = parse_url( $live_url, PHP_URL_HOST );
		$current_host = parse_url( get_home_url(), PHP_URL_HOST );
		$current_url  = str_replace( $url_host, $current_host, $live_url );

		return $current_url;

	}

	private function _get_type_object_id( $content_id, $type_family, $type ) {

		$menu_item_object_id = 0;

		if ( 'taxonomy' === $type_family ) {

			$menu_item_object_id = XMLRPC_Importer::get_instance()->get_taxonomy_term_by_id( $type, $content_id );

			return $menu_item_object_id;

		} else if ( 'post_type' === $type_family ) {

			$page_ids            = $this->call_post_json_api_route( $type , $content_id);
			$menu_item_object_id = $page_ids[0];

		}

		return $menu_item_object_id;

	}

	private function _save_menu( $menu_json ) {

		$menu_name = $menu_json["name"];
		// Does the menu exist already?
		$menu_exists = wp_get_nav_menu_object( $menu_name );

		// If it doesn't exist, let's create it.
		if ( ! $menu_exists ) {

			$menu_id = wp_create_nav_menu( $menu_name );

			if ( ! empty( $menu_json["items"] ) ) {

				foreach ( $menu_json["items"] as $menu_item ) {
					// Set up default BuddyPress links and add them to the menu.
					$menu_item_db_id = wp_update_nav_menu_item( $menu_id, 0, array(
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
				}
			}

			wp_set_object_terms( $menu_item_db_id, $menu_id, 'nav_menu' );
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


		}
	}

	/**
	 * Assemble Nav Menu data from API and inserts new Nav Menu.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-20, for PPT-5077, Archana Mandhare
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
	 * @version 1.0, 2015-07-16, for PPT-5077, Archana Mandhare
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {
		return $api_data;

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
	 * @version 1.0, 2015-07-06, for PPT-5077, Archana Mandhare
	 *
	 */
	public function call_post_json_api_route( $type, $post_id ) {

		$query_params = array( 'type' => $type );
		$route        = "posts/" . $post_id;
		$pages        = REST_API_oAuth::get_instance()->access_endpoint( $route, $query_params, 'posts', false );

		if ( is_wp_error( $pages ) ) {

			return $pages;

		} elseif ( ! empty( $pages ) ) {

			return Posts_Importer::get_instance()->call_import_route( $pages );

		} else {

			return new \WP_Error( 'unauthorized_access', " Failed to attach menu to page object with id - " . $post_id );

		}

	}


}
