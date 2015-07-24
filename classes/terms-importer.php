<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Terms_Importer extends PMC_Singleton {

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
	 * Insert a new Taxonomy Term to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Taxonomy Term data
	 *
	 * @return int|WP_Error The taxonomy Term Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_taxonomy_terms( $term_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$term_id = term_exists( $term_json['name'], $term_json['taxonomy'] );

			if ( ! $term_id ) {

				$term_id = wp_insert_term(
					$term_json['name'],
					$term_json['taxonomy'],
					array(
						'description'   => $term_json['description'],
						'slug'          => $term_json['slug'],
					)
				);


				if ( is_a( $term_id, "WP_Error" ) ) {

					error_log( $time . " -- " . $term_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
					return $term_id;

				} else {

					error_log( "{$time} -- Term **-- {$term_json['name']} --** for Taxonomy **-- {$term_json['taxonomy']} **-- added with ID = {$term_id["term_id"]}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );
				}
			} else {

				error_log( "{$time} -- Exists Term **-- {$term_json['name']} --** for Taxonomy **-- {$term_json['taxonomy']} **--  with ID = {$term_id["term_id"]}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $term_id["term_id"];
	}


	/**
	 * Assemble Taxonomies Term data from XMLRPC and inserts new Taxonomy.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Taxonomy Term object
	 *
	 * @return array of Taxonomy Term ids on success.
	 *
	 */
	public function instant_terms_import( $terms_json ) {

		$terms_info = array();

		foreach ( $terms_json as $term_json ) {

			$terms_info[] = $this->save_taxonomy_terms( $term_json );

		}

		return $terms_info;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from XMLRPC call that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		return $this->instant_terms_import( $api_data );

	}


}
