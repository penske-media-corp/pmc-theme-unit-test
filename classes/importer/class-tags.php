<?php
namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Logger\Status;

class Tags {

	use Singleton;

	const LOG_NAME = 'tags';

	/**
	 * Insert a new Post Tag to the DB.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array containing tag data
	 *
	 * @return int|WP_Error The tag Id on success. The value 0 or WP_Error on failure.
	 */
	public function save_tag( $tag_json ) {

		$status = Status::get_instance();

		$term_id = 0;

		$tag_array = array(
			'term_id'       => 0,
			'name'          => '',
			'description'   => '',
			'error_message' => '',
		);

		try {
			if ( empty( $tag_json ) || empty( $tag_json['name'] ) ) {
				$tag_array['error_message'] = 'NO TAGS DETAILS PASSED BY API';
				$status->save_current_log( self::LOG_NAME, array( 0 => $tag_array ) );
				return false;
			}

			$term = wpcom_vip_term_exists( $tag_json['name'], 'post_tag' );

			if ( empty( $term ) ) {

				$tag_array = array(
					'term_id'     => 0,
					'name'        => $tag_json['name'],
					'description' => $tag_json['description'],
				);

				$term   = wp_insert_term( $tag_array['name'], 'post_tag', $tag_array );

				if ( is_wp_error( $term ) ) {
					$tag_array['error_message'] = $term->get_error_message();
				} else {
					$term_id = $term['term_id'];
				}

			} else {
				$term_id = $term['term_id'];
				$tag_array['error_message'] = 'Term Already Exists. Skipped Inserting';
			}

			$tag_array['name'] = $tag_json['name'];
			$tag_array['term_id'] = $term_id;

			$status->save_current_log( self::LOG_NAME, array( $term_id => $tag_array ) );
			return $term_id;

		} catch ( \Exception $e ) {

			$tag_array['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $term_id => $tag_array ) );
			return false;

		}
	}

	/**
	 * Assemble tags data from API and inserts new tags.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of tag object
	 *
	 * @return array of Tags ids on success.
	 * @todo - Find ways to insert tags as an object along with all its terms and meta rather than creating an array from json_data
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
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 */
	public function call_import_route( $api_data ) {
		return $this->instant_tags_import( $api_data );
	}
}
