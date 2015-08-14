<?php
namespace PMC\Theme_Unit_Test;

class Tags_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
	}

	/**
	 * Insert a new Post Tag to the DB.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array containing tag data
	 *
	 * @return int|WP_Error The tag Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_tag( $tag_json ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$term_id = term_exists( $tag_json['name'], 'post_tag' );

			if ( ! $term_id ) {

				$tag_array = array(
					'term_id'     => 0,
					'name'        => $tag_json['name'],
					'description' => $tag_json['description'],
				);

				$tag_info = wp_insert_term( $tag_array['name'], 'post_tag', $tag_array );

				$term_id = $tag_info['term_id'];

				if ( is_a( $tag_info, 'WP_Error' ) ) {

					error_log( $time . ' -- ' . $tag_info->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
					return $tag_info;
				} else {

					$tag_ids[] = $tag_info['term_id'];

					error_log( "{$time} -- Tag **-- {$tag_json['name']} --** added with ID = {$term_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );
				}
			} else {

				error_log( "{$time} -- Exists Tag **-- {$tag_json['name']} --** with ID = {$term_id['term_id']}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $term_id;
	}

	/**
	 * Assemble tags data from API and inserts new tags.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of tag object
	 *
	 * @return array of Tags ids on success.
	 * @todo - Find ways to insert tags as an object along with all its terms and meta rather than creating an array from json_data
	 *
	 */
	public function instant_tags_import( $tags_json ) {

		$tag_ids = array();

		foreach ( $tags_json as $tag_json ) {

			$tag_ids[] = $this->save_tag( $tag_json );

		}

		return $tag_ids;
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

		if ( $api_data ) {

			return $this->instant_tags_import( $api_data );

		}

	}


}
