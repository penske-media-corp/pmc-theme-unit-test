<?php

namespace PMC\Theme_Unit_Test\Background;

use PMC\Theme_Unit_Test\Rest_API\O_Auth;
use PMC\Theme_Unit_Test\Importer\Users;
use PMC\Theme_Unit_Test\Importer\Menus;
use PMC\Theme_Unit_Test\Importer\Tags;
use PMC\Theme_Unit_Test\Importer\Categories;
use PMC\Theme_Unit_Test\Importer\Posts;

class Background_Data_Import extends PMC_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'import_data_process';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$api_data = O_Auth::get_instance()->access_endpoint( $item['route'], $item['query_params'], $item['route_index'] );
		if ( is_wp_error( $api_data ) ) {
			return $api_data;
		} else {
			switch ( $item['route'] ) {
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
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}

}