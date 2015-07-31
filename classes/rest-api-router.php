<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class REST_API_Router extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
	}


	/**
	 * Just to make sure that if no class to save data
	 * gets called then this method will return data as is.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-16 Archana Mandhare - PPT-5077
	 */
	private function _call_import_route( $api_data, $domain = '' ) {

		return $api_data;

	}


	/**
	 * Make calls to REST API and get access endpoints
	 *
	 * This method will make a call to the public REST API
	 * and fetch data from live site and save to the current site DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 * @params string $route - the name of the endpoint route that needs to be appended to the API URL
	 * array $query_params the query params that need to be passed to the API
	 * string $route_index the index key of the returned json data from the API that we need to save
	 * bool $access_token true if oAuth access token is required to fetch data. Default is false.
	 *
	 */
	private function _access_endpoint( $domain, $route, $query_params = array(), $route_index, $access_token ) {

		$api_data = REST_API_oAuth::get_instance()->access_endpoint( $domain, $route, $query_params, $route_index, $access_token );

		if ( is_wp_error( $api_data ) ) {

			return $api_data;

		} else {

			switch ( $route ) {

				case 'users':
					$route_class = Users_Importer::get_instance();
					break;

				case 'menus':
					$route_class = Menus_Importer::get_instance();
					break;

				case 'tags':
					$route_class = Tags_Importer::get_instance();
					break;

				case 'categories':
					$route_class = Categories_Importer::get_instance();
					break;

				case 'posts':
					$route_class = Posts_Importer::get_instance();
					break;

				default:
					$route_class = $this;
					break;
			}

			return $route_class->call_import_route( $api_data, $domain );

		}

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
	public function call_rest_api_route( $params ) {

		if ( ! empty( $params['domain'] ) ) {
			$domain = $params['domain'];
		} else {
			$domain = '';
		}

		$route = strtolower( $params['route']['name'] );

		$access_token = (bool) $params['route']['access_token'];

		$query_params = array();

		if ( ! empty( $params['route']['query_params'] ) ) {

			$query_params = $params['route']['query_params'];

		}

		if ( ! empty( $params['route']['route_index'] ) ) {
			$route_index = $params['route']['route_index'];
		} else {
			$route_index = $route;
		}

		if ( $access_token ) {
			// Initialize the oAuth params and set access token to be used by the routes
			REST_API_oAuth::get_instance()->initialize_params( $params );

		}

		$data_ids[] = $this->_access_endpoint( $domain, $route, $query_params, $route_index, $access_token );

		return $data_ids;
	}

}





