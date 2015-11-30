<?php
namespace PMC\Theme_Unit_Test;

class Tags_Importer extends PMC_Singleton {

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

			if ( empty( $tag_json ) || empty( $tag_json['name'] ) ) {

				error_log( $time . ' No tag data Passed. ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return false;
			}

			$term_id = wpcom_vip_term_exists( $tag_json['name'], 'post_tag' );

			if ( empty( $term_id ) ) {

				$tag_array = array(
					'term_id'     => 0,
					'name'        => $tag_json['name'],
					'description' => $tag_json['description'],
				);

				$term_id = wp_insert_term( $tag_array['name'], 'post_tag', $tag_array );

				if ( is_a( $term_id, 'WP_Error' ) ) {

					error_log( $time . ' -- ' . $term_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

					return $term_id;

				} else {

					error_log( "{$time} -- Tag **-- {$tag_json['name']} --** added with ID = {$term_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

					return $term_id['term_id'];
				}
			} else {

				error_log( "{$time} -- Exists Tag **-- {$tag_json['name']} --** with ID = {$term_id['term_id']}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

				return $term_id['term_id'];
			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return false;

		}

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

		if ( empty( $tags_json ) || ! is_array( $tags_json ) ) {
			return $tag_ids;
		}

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

		return $this->instant_tags_import( $api_data );

	}
}
