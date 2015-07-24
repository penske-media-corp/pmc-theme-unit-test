<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Taxonomies_Importer extends PMC_Singleton {

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
	 * Insert a new Taxonomy to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Taxonomy data
	 *
	 * @return int|WP_Error The taxonomy Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_taxonomy( $taxonomy_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$taxonomy_id = taxonomy_exists( $taxonomy_json['name'] );

			if ( ! $taxonomy_id ) {

				register_taxonomy(  $taxonomy_json['name'] ,
					$taxonomy_json['object_type'] ,
					array(
						'label'        => $taxonomy_json['label'] ,
						'labels'       => $taxonomy_json['labels'],
						'show_ui'      => $taxonomy_json['show_ui'],
						'hierarchical' => $taxonomy_json['hierarchical'],
						'capabilities' => $taxonomy_json['cap'] ,
					)
				);


				if ( is_a( $taxonomy_id, "WP_Error" ) ) {

					error_log( $time . " -- " . $taxonomy_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				} else {

					$taxonomy_id = true;
					error_log( "{$time} -- Taxonomy **-- {$taxonomy_json['name']} --** added." . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				}
			} else {

				error_log( "{$time} -- Exists Taxonomy **-- {$taxonomy_json['name']} --**" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $taxonomy_id;
	}


	/**
	 * Assemble Taxonomies data from XMLRPC and inserts new Taxonomy.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Taxonomy object
	 *
	 * @return array of Taxonomies ids on success.
	 *
	 */
	public function instant_taxonomies_import( $taxonomies_json ) {

		$taxonomies_info = array();

		foreach ( $taxonomies_json as $taxonomy_json ) {

			// Don't save taxonomy category or post_tag since its built-in.
			if( 'category' === $taxonomy_json['name'] || 'post_tag' === $taxonomy_json['name'] ) {
				continue;
			}
			$taxonomies_info[] = $this->save_taxonomy( $taxonomy_json );

		}

		return $taxonomies_info;

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

		return $this->instant_taxonomies_import( $api_data );

	}


}
