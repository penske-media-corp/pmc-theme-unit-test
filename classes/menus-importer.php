<?php
namespace PMC\Theme_Unit_Test;

class Menus_Importer extends PMC_Singleton {

	/**
	 * Get the URL of the current item by replacing the imported URL with the current domain
	 *
	 * @since 2015-07-20
	 *
	 * @version 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param @type string The original live URL
	 *
	 * @return string The local URL
	 *
	 */
	private function _get_menu_url( $live_url ) {

		$url_host     = parse_url( $live_url, PHP_URL_HOST );
		$current_host = parse_url( get_home_url(), PHP_URL_HOST );
		$current_url  = str_ireplace( $url_host, $current_host, $live_url );

		return ( ! empty( $current_url ) ) ? $current_url : get_home_url();

	}

	/**
	 * Get the ID of the type of object the menu is associated with
	 * e.g Taxonomy Term ID or Page ID or Post ID etc
	 *
	 * @since 2015-07-20
	 *
	 * @version 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param @type int the type object ID,
	 *
	 * @type string the type_family such as Taxonomy, Page or Post
	 * @type the type value as string such as Awards, Page title etc
	 *
	 * @return int|WP_Error The menu item object id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _get_type_object_id( $content_id, $type_family, $type, $url ) {

		$time = date( '[d/M/Y:H:i:s]' );

		$menu_item_object_id = 0;

		// if the type is taxonomy make XMLRPC call or else make REST API call for post_type
		switch ( $type_family ) {

			case 'taxonomy' :
				$menu_item_object_id = XMLRPC_Router::get_instance()->get_taxonomy_term_by_id( $type, $content_id );
				break;

			case 'post_type':
				$menu_item_object_id = wpcom_vip_url_to_postid( $url );
				if ( 0 === $menu_item_object_id ) {

					$menu_item_object_id = $this->call_post_rest_api_route( $type, $content_id );

				}
				break;
		}

		if ( is_wp_error( $menu_item_object_id ) ) {

			error_log( $time . ' -- ' . $menu_item_object_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return 0;
		}

		return $menu_item_object_id;

	}

	/**
	 * Insert a new Menu Item to the DB.
	 *
	 * @since 2015-07-28
	 *
	 * @version 2015-07-28 Archana Mandhare - PPT-5077
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

			if ( 'custom' !== $menu_item['type_family'] ) {

				//fetch the type object from API if it is not present in the current site;
				$url = $this->_get_menu_url( $menu_item['url'] );
				$type_id = $this->_get_type_object_id( $menu_item['content_id'], $menu_item['type_family'], $menu_item['type'], $menu_item['url'] );
				if ( empty( $type_id ) || is_wp_error( $type_id ) ) {
					return false;
				}
			} else {

				$url     = $menu_item['url'];
				$type_id = $menu_item['id'];

			}
			$_menu_item_classes = maybe_unserialize( $menu_item['classes'] );

			if ( is_array( $_menu_item_classes ) ) {
				$_menu_item_classes = implode( ' ', $_menu_item_classes );
			}

			// create the menu item array
			$args = array(
				'menu-item-object-id'   => $type_id,
				'menu-item-object'      => $menu_item['type'],
				'menu-item-type'        => $menu_item['type_family'],
				'menu-item-title'       => $menu_item['name'],
				'menu-item-url'         => $url,
				'menu-item-description' => $menu_item['description'],
				'menu-item-attr-title'  => $menu_item['link_title'],
				'menu-item-target'      => $menu_item['link_target'],
				'menu-item-classes'     => $_menu_item_classes,
				'menu-item-xfn'         => $menu_item['xfn'],
			);

			$menu_item_db_id = wp_update_nav_menu_item( $menu_id, $menu_item_id, $args );

			$setup_args = array(
				'db_id'            => $menu_item_db_id,
				'object_id'        => $type_id,
				'type'             => $menu_item['type_family'],
				'object'           => $menu_item['type'],
				'type_label'       => $menu_item['name'],
				'post_parent'      => 0, //@todo get the parent of the current object
				'menu_item_parent' => $menu_id,
				'url'              => $url,
				'title'            => $menu_item['name'],
				'target'           => $menu_item['link_target'],
				'attr_title'       => $menu_item['link_title'],
				'classes'          => $_menu_item_classes,
				'xfn'              => $menu_item['xfn'],
				'description'      => $menu_item['description'],
				'post_type'        => 'nav_menu_item',
			);

			$menu_list_item = wp_setup_nav_menu_item( $setup_args );

			if ( is_wp_error( $menu_item_db_id ) ) {

				error_log( $time . ' WP_Error -- ' . $menu_item_db_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			} else {

				wp_set_object_terms( $menu_item_db_id, $menu_id, 'nav_menu' );

				error_log( $time . ' -- Menu Item Added **-- ' . $menu_item['name'] . ' --** with ID = {$menu_item_db_id}' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}
		} catch ( \Exception $e ) {

			error_log( $time . ' -- ' . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $menu_item_db_id;
	}

	/**
	 * Insert a new Menu and new Menu Item to the DB.
	 *
	 * @since 2015-07-20
	 *
	 * @version 2015-07-20 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Menu meta data
	 *
	 * @return int|WP_Error The Menu Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_menu( $menu_json ) {

		$menu_name = $menu_json['name'];

		$time = date( '[d/M/Y:H:i:s]' );

		try {
			// Does the menu exist already?
			$menu_exists = wp_get_nav_menu_object( $menu_name );

			// If it exists, lets delete and recreate
			if ( ! empty( $menu_exists ) ) {

				wp_delete_nav_menu( $menu_exists->term_id );

			}

			$menu_id = wp_create_nav_menu( $menu_name );

			if ( is_wp_error( $menu_id ) ) {
				error_log( $time . '-- Menu Failed ' . $menu_json['name'] . '**--** with message  = ' . $menu_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
			}
			if ( ! empty( $menu_json['items'] ) ) {

				foreach ( $menu_json['items'] as $menu_item ) {

					//loop through and save the menu items
					$this->_save_menu_item( $menu_id, $menu_item, 0 );
				}
			}

			// Grab the theme locations and assign our newly-created menu
			if ( ! empty( $menu_json['locations'] ) ) {

				$menu_locations = $menu_json['locations'];

				foreach ( $menu_locations as $menu_location ) {

					if ( ! has_nav_menu( $menu_location ) ) {
						$locations                   = get_theme_mod( 'nav_menu_locations' );
						$locations[ $menu_location ] = $menu_id;
						set_theme_mod( 'nav_menu_locations', $locations );

					}
				}
			}
		} catch ( \Exception $e ) {

			error_log( $time . ' -- ' . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}

	}

	/**
	 * Assemble Nav Menu data from API and inserts new Nav Menu.
	 *
	 * @since 2015-07-20
	 *
	 * @version 2015-07-20 Archana Mandhare - PPT-5077
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
	 * @since 2015-07-16
	 *
	 * @version 2015-07-16 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		return $this->instant_menus_import( $api_data );

	}

	/**
	 * Access endpoints to make call to REST API
	 *
	 * This method will make a call to the public REST API
	 * and fetch data from live site and save to the current site DB.
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function call_posts_rest_api_route( $type, $post_id ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$pages = REST_API_oAuth::get_instance()->access_endpoint( 'posts/' . $post_id . '/', array( 'type' => $type ), 'posts', false );

			$author_ID = get_current_user_id();

			$page_ID = Posts_Importer::get_instance()->save_post( $pages[0], $author_ID, array(), $type );

			if ( is_wp_error( $pages ) ) {

				error_log( $time . ' -- ' . $pages->get_error_messages() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return $pages;

			} else if ( empty( $pages ) ) {

				return new \WP_Error( 'unauthorized_access', ' Failed to attach menu to post_type ' . $type . ' object with id - ' . $post_id );

			}

			error_log( $time . $type . ' -- Fetched for Menu with ID ' . $page_ID . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

		} catch ( \Exception $e ) {
			error_log( $time . ' -- ' . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}

		return $page_ID;
	}


}
