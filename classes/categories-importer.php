<?php
namespace PMC\Theme_Unit_Test;

class Categories_Importer extends PMC_Singleton {

	/**
	 * Insert a new Category Tag to the DB.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array containing category data
	 *
	 * @return int|WP_Error The category Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_category( $category_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			if ( empty( $category_json ) || empty( $category_json['name'] ) ) {

				error_log( $time . ' No Category data Passed. ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return false;
			}
			$category_info = wpcom_vip_term_exists( $category_json['name'], 'category' );

			if ( empty( $category_info ) ) {

				$category_array = array(
					'cat_ID'               => 0,
					'cat_name'             => $category_json['name'],
					'category_description' => $category_json['description'],
					'category_nicename'    => $category_json['name'],
					'taxonomy'             => 'category',
				);

				$category_info = wp_insert_category( $category_array );

				if ( is_wp_error( $category_info ) ) {

					error_log( $time . ' -- ' . $category_info->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

					return $category_info;
				} else {

					error_log( "{$time} -- Category **-- {$category_json['name']} --** added with ID = {$category_info}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

					return $category_info;
				}
			} else {

				error_log( "{$time} -- Exists Category **-- {$category_json['name']} --** with ID = {$category_info['term_id']}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

				return $category_info['term_id'];
			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return false;

		}

	}


	/**
	 * Assemble Categories data from API and inserts new category.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
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
	 *
	 * @version 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		return $this->instant_categories_import( $api_data );

	}
}
