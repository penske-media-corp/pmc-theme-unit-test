<?php
namespace PMC\Theme_Unit_Test\Rest_API;

use PMC\Theme_Unit_Test\PMC_Singleton;
use PMC\Theme_Unit_Test\Importer\Users;
use PMC\Theme_Unit_Test\Importer\Menus;
use PMC\Theme_Unit_Test\Importer\Tags;
use PMC\Theme_Unit_Test\Importer\Categories;
use PMC\Theme_Unit_Test\Importer\Posts;
use PMC\Theme_Unit_Test\Settings\Config;

class Router extends PMC_Singleton {

	/**
	 * Just to make sure that if no class to save data
	 * gets called then this method will return data as is.
	 *
	 * @since 2015-07-16
	 * @version 2015-07-16 Archana Mandhare PPT-5077
	 *
	 * @param array data returned from json rest api
	 * @return array
	 */
	private function _call_import_route( $api_data ) {
		return $api_data;
	}


	/**
	 * Make calls to REST API and get access endpoints
	 *
	 * This method will make a call to the public REST API
	 * and fetch data from live site and save to the current site DB.
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 * @param string $route - the name of the endpoint route that needs to be appended to the API URL
	 * @param array $query_params the query params that need to be passed to the API
	 * @param string $route_index the index key of the returned json data from the API that we need to save
	 * bool $access_token true if oAuth access token is required to fetch data. Default is false.
	 * @return array
	 */
	private function _access_endpoint( $route, $query_params = array(), $route_index = '' ) {

		$api_data = O_Auth::get_instance()->access_endpoint( $route, $query_params, $route_index );
		if ( is_wp_error( $api_data ) ) {
			return $api_data;
		} else {
			switch ( $route ) {
				case 'users':
					$route_class = Users::get_instance();
					break;
				case 'menus':
					$route_class = Menus::get_instance();
					break;
				case 'tags':
					$route_class = Tags::get_instance();
					break;
				case 'categories':
					$route_class = Categories::get_instance();
					break;
				case 'posts':
					$route_class = Posts::get_instance();
					break;
				default:
					$route_class = $this;
					break;
			}
			return $route_class->call_import_route( $api_data );
		}

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
	 * @params string $route it is the endpoint name - e.g users, menu, categories, tags etc
	 * @return array of entity IDs that got saved.
	 */
	public function call_rest_api_all_route( $route ) {
		foreach ( Config::$all_routes as $routes ) {
			if ( ! empty( $routes[ $route ] ) ) {
				$route_params = $routes[ $route ];
				break;
			}
		}
		if ( ! empty( $route_params ) ) {
			$query_params = array();
			if ( ! empty( $route_params['query_params'] ) ) {
				$query_params = $route_params['query_params'];
			}
			return $this->_access_endpoint( $route, $query_params, $route );
		}
		return false;
	}

	/**
	 * Access posts endpoints to make call to REST API
	 *
	 * This method will make a call to the public REST API
	 * and fetch data posts and custom posts data from live site and save to the current site DB.
	 *
	 * @since 2015-08-12
	 * @version 2015-08-12 Archana Mandhare PPT-5077
	 *
	 * @params string $route it is post_type for the post endpoint
	 * @return array of entity IDs that got saved.
	 *
	 */
	public function call_rest_api_posts_route( $route ) {
		return $this->_access_endpoint( 'posts', array( 'type' => $route, 'number' => Config::post_count ), 'posts' );
	}


	/**
	 * Access posts endpoints to make call to REST API for specific posts
	 *
	 * This method will make a call to the public REST API
	 * and fetch posts based on their ID from live site and save to the current site DB.
	 *
	 * @since 2015-11-30
	 * @version 2015-11-30 Archana Mandhare - PMCVIP-177
	 *
	 * @params string $post_ids the array of post_ids to pull data for
	 * @return array of entity IDs that got saved.
	 *
	 */
	public function call_rest_api_single_posts( $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			$api_data[] = $this->_access_endpoint( 'posts', array( 'post_id' => $post_id ), 'posts' );
		}
		return $api_data;
	}
}





