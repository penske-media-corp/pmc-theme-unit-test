<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Logger\Status;

class Categories {

	use Singleton;

	const LOG_NAME = 'categories';

	/**
	 * Insert a new Category Tag to the DB.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array containing category data
	 *
	 * @return int|WP_Error The category Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_category( $category_json ) {

		$status = Status::get_instance();

		$category_id = 0;

		$category_array = array(
			'cat_ID'               => 0,
			'cat_name'             => 0,
			'category_description' => 0,
			'category_nicename'    => 0,
			'taxonomy'             => 'category',
			'error_message'        => '',
		);

		try {
			if ( empty( $category_json ) || empty( $category_json['name'] ) ) {

				$category_array['error_message'] = 'NO CATEGORIES DETAILS PASSED BY API';
				$status->save_current_log( self::LOG_NAME, array( 0 => $category_array ) );

				return false;

			}

			$category_term = wpcom_vip_term_exists( $category_json['name'], 'category' );

			if ( empty( $category_term ) ) {

				$category_array = array(
					'cat_ID'               => 0,
					'cat_name'             => $category_json['name'],
					'category_description' => $category_json['description'],
					'category_nicename'    => $category_json['name'],
					'taxonomy'             => 'category',
				);

				$category_id = wp_insert_category( $category_array );

				if ( is_wp_error( $category_id ) ) {
					$category_array['error_message'] = $category_id->get_error_message();
					$category_id                     = 0;
				}
			} else {
				$category_id                     = $category_term['term_id'];
				$category_array['error_message'] = 'Category Already Exists. Skipped Inserting';
			}

			$category_array['cat_name'] = $category_json['name'];
			$category_array['cat_ID']   = $category_id;
			$status->save_current_log( self::LOG_NAME, array( $category_id => $category_array ) );

			return $category_id;

		} catch ( \Exception $e ) {

			$category_array['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $category_id => $category_array ) );

			return false;

		}

	}


	/**
	 * Assemble Categories data from API and inserts new category.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of Category object
	 *
	 * @return array of Categories ids on success.
	 * @todo - Find ways to insert categories as an object along with all its terms and meta rather than creating an array from json_data
	 *
	 */
	public function instant_categories_import( $categories_json ) {

		$categories_info = array();
		if ( empty( $categories_json ) || ! is_array( $categories_json ) ) {
			return $categories_info;
		}
		foreach ( $categories_json as $category_json ) {
			$categories_info[] = $this->save_category( $category_json );
		}

		return $categories_info;
	}


	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @param $api_data array data returned from the REST API that needs to be imported
	 *
	 * @return array
	 *
	 */
	public function call_import_route( $api_data ) {
		return $this->instant_categories_import( $api_data );
	}
}
