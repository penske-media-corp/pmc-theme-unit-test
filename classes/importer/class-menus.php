<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Rest_API\O_Auth;
use PMC\Theme_Unit_Test\XML_RPC\Service;
use PMC\Theme_Unit_Test\Logger\Status;

class Menus {

	use Singleton;

	const LOG_NAME = 'menus';

	/**
	 * Get the ID of the type of object the menu is associated with
	 * e.g Taxonomy Term ID or Page ID or Post ID etc
	 *
	 * @since 2015-07-20
	 * @version 2015-07-20 Archana Mandhare PPT-5077
	 *
	 * @param @type array the $menu_item array,
	 *
	 * @return int|WP_Error The menu item object id on success. The value 0 or WP_Error on failure.
	 */
	private function _get_type_object_id( array $menu_item ) {

		$status              = Status::get_instance();
		$menu_item_object_id = 0;
		$content_id          = $menu_item['content_id'];
		$type_family         = $menu_item['type_family'];
		$type                = $menu_item['type'];
		$url                 = $menu_item['url'];

		$menu_log_data = array(
			'menu-item-object-id'   => 0,
			'menu-item-object'      => 0,
			'menu-item-type'        => $menu_item['type'],
			'menu-item-title'       => 0,
			'menu-item-url'         => $menu_item['url'],
			'menu-item-description' => 0,
			'menu-item-attr-title'  => 0,
			'menu-item-target'      => 0,
			'menu-item-classes'     => 0,
			'menu-item-xfn'         => 0,
			'menu-item-parent-id'   => $content_id,
			'menu-item-status'      => 0,
			'error_message'         => '',
		);

		// if the type is taxonomy make XMLRPC call or else make REST API call for post_type
		switch ( $type_family ) {
			case 'taxonomy' :
				$menu_item_object_id = Service::get_instance()->get_taxonomy_term_by_id( $type, $content_id );
				break;
			case 'post_type':
				$menu_item_object_id = wpcom_vip_url_to_postid( $url );
				if ( 0 === $menu_item_object_id ) {
					$menu_item_object_id = $this->call_posts_rest_api_route( $type, $content_id );
				}
				break;
		}

		if ( is_wp_error( $menu_item_object_id ) ) {
			$menu_log_data['error_message'] = $menu_item_object_id->get_error_message();
			$menu_item_object_id            = 0;
		} else if ( ! is_int( intval( $menu_item_object_id ) ) ) {
			$menu_log_data['error_message'] = ' -- Failed to import menu item ' . $content_id;
			$menu_item_object_id            = 0;
		} else {
			$menu_log_data['menu-item-object-id'] = $menu_item_object_id;
		}

		$status->save_current_log( self::LOG_NAME, array( $menu_item_object_id => $menu_log_data ) );

		return intval( $menu_item_object_id );
	}

	/**
	 * Insert a new Menu Item to the DB.
	 *
	 * @since 2015-07-28
	 * @version 2015-07-28 Archana Mandhare PPT-5077
	 *
	 * @param array containing Menu Item meta data
	 *
	 * @return int|WP_Error The Menu Item Id on success. The value 0 or WP_Error on failure.
	 */
	private function _save_menu_item( $menu_id, array $menu_item ) {

		$status = Status::get_instance();

		$menu_log_data = array(
			'menu-item-object-id'   => 0,
			'menu-item-object'      => 0,
			'menu-item-type'        => 0,
			'menu-item-title'       => 0,
			'menu-item-url'         => 0,
			'menu-item-description' => 0,
			'menu-item-attr-title'  => 0,
			'menu-item-target'      => 0,
			'menu-item-classes'     => 0,
			'menu-item-xfn'         => 0,
			'menu-item-parent-id'   => $menu_id,
			'menu-item-status'      => 0,
			'error_message'         => '',
		);

		try {

			if ( empty( $menu_item ) || ! is_array( $menu_item ) ) {

				$menu_log_data['error_message'] = ' No Menu Item Provided ';
				$status->save_current_log( self::LOG_NAME, array( $menu_id => $menu_log_data ) );

				return false;
			}

			if ( 'taxonomy' === $menu_item['type_family'] || 'post_type' === $menu_item['type_family'] ) {

				//fetch the type object from API if it is not present in the current site;
				$type_id = $this->_get_type_object_id( $menu_item );

				if ( empty( $type_id ) || is_wp_error( $type_id ) ) {

					$menu_log_data['error_message'] = ' Menu Item of URL ' . $menu_item['url'] . ' Not imported from server ';
					$status->save_current_log( self::LOG_NAME, array( $menu_id => $menu_log_data ) );
					return false;
				}

				if ( 'taxonomy' === $menu_item['type_family'] ) {
					$url = wpcom_vip_get_term_link( $type_id, $menu_item['type'] );
				} else if ( 'post_type' === $menu_item['type_family'] ) {
					$url = get_permalink( $type_id );
				}

			} else {
				$url     = $menu_item['url'];
				$type_id = 0;
			}

			$_menu_item_classes = maybe_unserialize( $menu_item['classes'] );
			if ( is_array( $_menu_item_classes ) ) {
				$_menu_item_classes = implode( ' ', $_menu_item_classes );
			}
			// create the menu item array
			$args = array(
				'menu-item-object-id'   => $type_id,
				'menu-item-parent-id'   => $menu_id,
				'menu-item-title'       => $menu_item['name'],
				'menu-item-url'         => $url,
				'menu-item-object'      => $menu_item['type'],
				'menu-item-type'        => $menu_item['type_family'],
				'menu-item-description' => $menu_item['description'],
				'menu-item-attr-title'  => $menu_item['link_title'],
				'menu-item-target'      => $menu_item['link_target'],
				'menu-item-classes'     => $_menu_item_classes,
				'menu-item-xfn'         => $menu_item['xfn'],
				'menu-item-status'      => 'publish',
			);

			$menu_item_db_id = wp_update_nav_menu_item( $menu_id, 0, $args );
			$menu_log_data   = $args;
			if ( is_wp_error( $menu_item_db_id ) ) {
				$menu_log_data['error_message'] = $menu_item_db_id->get_error_message();
			} else {
				if ( 'taxonomy' === $menu_item['type_family'] ) {
					wp_set_object_terms( $menu_id, $type_id, 'nav_menu', true );
				}
				if ( ! empty( $menu_item['items'] ) ) {
					foreach ( $menu_item['items'] as $menu_child_item ) {
						//$this->_save_menu_item( $menu_item_db_id, $menu_child_item );
					}
				}
				$menu_log_data['error_message'] = '';
			}

			$status->save_current_log( self::LOG_NAME, array( $menu_item_db_id => $menu_log_data ) );

			return $menu_item_db_id;

		} catch ( \Exception $e ) {

			$menu_log_data['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( 0 => $menu_log_data ) );

			return false;
		}
	}

	/**
	 * Insert a new Menu and new Menu Item to the DB.
	 *
	 * @since 2015-07-20
	 * @version 2015-07-20 Archana Mandhare PPT-5077
	 *
	 * @param array containing Menu meta data
	 *
	 * @return int|WP_Error The Menu Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_menu( array $menu_data ) {

		$menu_name = $menu_data['name'];

		$status = Status::get_instance();

		$menu_log_data = array(
			'name'          => 0,
			'error_message' => '',
		);

		try {

			if ( empty( $menu_data ) ) {

				$menu_log_data['error_message'] = ' No Menu data Provided ';
				$status->save_current_log( self::LOG_NAME, array( 0 => $menu_log_data ) );

				return false;
			}

			// Does the menu exist already?
			$menu_object = wp_get_nav_menu_object( $menu_name );

			// If it exists, lets delete and recreate
			if ( ! empty( $menu_object ) ) {
				wp_delete_nav_menu( $menu_object->term_id );
			}

			$menu_id = wp_create_nav_menu( $menu_name );

			if ( is_wp_error( $menu_id ) ) {
				$menu_log_data['name']          = $menu_name;
				$menu_log_data['error_message'] = '-- Menu Failed ' . $menu_name . '**--** with message  = ' . $menu_id->get_error_message();
				$status->save_current_log( self::LOG_NAME, array( 0 => $menu_log_data ) );

				return false;
			}


			if ( ! empty( $menu_data['items'] ) ) {
				foreach ( $menu_data['items'] as $menu_item ) {
					$this->_save_menu_item( $menu_id, $menu_item );
				}
			}

			// Grab the theme locations and assign our newly-created menu
			if ( ! empty( $menu_data['locations'] ) ) {
				$menu_locations = $menu_data['locations'];
				foreach ( $menu_locations as $menu_location ) {
					if ( ! has_nav_menu( $menu_location ) ) {
						$locations                   = get_theme_mod( 'nav_menu_locations' );
						$locations[ $menu_location ] = $menu_id;
						set_theme_mod( 'nav_menu_locations', $locations );
					}
				}
			}

			return $menu_id;

		} catch ( \Exception $e ) {
			$menu_log_data['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( 0 => $menu_log_data ) );

			return false;
		}
	}

	/**
	 * Assemble Nav Menu data from API and inserts new Nav Menu.
	 *
	 * @since 2015-07-20
	 * @version 2015-07-20 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of Nav Menu object
	 *
	 * @return array of Nav Menus ids on success.
	 */
	public function instant_menus_import( array $menu_items = [] ) {

		$menu_info = array();

		if ( empty( $menu_items ) || ! is_array( $menu_items ) ) {
			return $menu_info;
		}

		foreach ( $menu_items as $menu ) {
			try {
				$menu_id = $this->save_menu( $menu );
				if ( ! empty( $menu_id ) ) {
					$menu_info[] = $menu_id;
				}
			} catch ( \Exception $ex ) {
				continue;
			}
		}

		return $menu_info;
	}


	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-16
	 * @version 2015-07-16 Archana Mandhare PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 */
	public function call_posts_rest_api_route( $type, $post_id ) {

		$status = Status::get_instance();

		$post_data['error_message'] = '';

		try {

			$pages = O_Auth::get_instance()->access_endpoint( 'posts/' . $post_id . '/', array( 'type' => $type ), 'posts', true );

			if ( is_wp_error( $pages ) ) {

				$post_data                  = Posts::get_instance()->get_post_log_data();
				$post_data['error_message'] = $pages->get_error_messages();
				$status->save_current_log( 'post', array( 0 => $post_data ) );

				return $pages;

			} else if ( empty( $pages ) ) {

				$post_data['error_message'] = ' Failed to attach menu to post_type ' . $type . ' object with id - ' . $post_id;
				$status->save_current_log( self::LOG_NAME, array( 0 => $post_data ) );

				return new \WP_Error( 'unauthorized_access', ' Failed to attach menu to post_type ' . $type . ' object with id - ' . $post_id );

			}

			$author_id = get_current_user_id();
			$page_id   = Posts::get_instance()->save_post( $pages[0], $author_id, array(), $type );

			$post_data['name']          = $type;
			$post_data['error_message'] = ' -- ' . $type . ' -- Fetched for Menu with ID ' . $page_id;
			$status->save_current_log( self::LOG_NAME, array( $page_id => $post_data ) );

		} catch ( \Exception $e ) {

			$post_data['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( 0 => $post_data ) );

		}

		return $page_id;
	}
}
