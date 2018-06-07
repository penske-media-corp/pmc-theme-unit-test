<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Logger\Status;

class Terms {

	use Singleton;

	const LOG_NAME = 'tags';

	/**
	 * Insert a new Taxonomy Term to the DB.
	 *
	 * @since 2015-07-21
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 *
	 * @param array containing Taxonomy Term data
	 *
	 * @return int|WP_Error The taxonomy Term Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_taxonomy_terms( array $term_details ) {

		$status = Status::get_instance();

		$term_id = 0;

		$term_array = array(
			'term_id'       => 0,
			'name'          => '',
			'description'   => '',
			'error_message' => '',
		);

		try {
			if ( empty( $term_details ) ) {
				$tag_array['error_message'] = 'NO TERM DETAILS PASSED BY API';
				$status->save_current_log( self::LOG_NAME, array( 0 => $term_array ) );

				return false;
			}

			$exists_taxonomy = taxonomy_exists( $term_details['taxonomy'] );

			if ( false === $exists_taxonomy ) {
				$tag_array['error_message'] = 'Taxonomy -- ' . $term_details['taxonomy'] . ' --  does not exists';
				$status->save_current_log( self::LOG_NAME, array( 0 => $term_array ) );

				return false;
			}

			$term_id = wpcom_vip_term_exists( $term_details['slug'], $term_details['taxonomy'] );

			if ( empty( $term_id ) && $exists_taxonomy ) {

				$term_array['name']        = $term_details['name'];
				$term_array['description'] = $term_details['description'];

				$term = wp_insert_term(
					$term_details['name'],
					$term_details['taxonomy'],
					array(
						'description' => $term_details['description'],
						'slug'        => $term_details['slug'],
					)
				);

				if ( is_wp_error( $term ) ) {
					$term_array['error_message'] = $term->get_error_message();
				} else {
					$term_id               = $term['term_id'];
					$term_array['term_id'] = $term_id;
				}

			} else {
				$term_array['error_message'] = 'Term Already Exists. Skipped Inserting';
				$term_id                     = $term_id['term_id'];
			}

			$status->save_current_log( self::LOG_NAME, array( $term_id => $term_array ) );

			return $term_id;

		} catch ( \Exception $e ) {

			$term_array['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $term_id => $term_array ) );

			return false;

		}
	}

	/**
	 * Assemble Taxonomies Term data from XMLRPC and inserts new Taxonomy.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of Taxonomy Term object
	 *
	 * @return array of Taxonomy Term ids on success.
	 *
	 */
	public function instant_terms_import( $terms ) {

		$terms_info = array();

		if ( empty( $terms ) || ! is_array( $terms ) ) {
			return $terms_info;
		}

		foreach ( $terms as $term ) {
			$terms_info[] = $this->save_taxonomy_terms( $term );
		}

		return $terms_info;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @params array $api_data data returned from XMLRPC call that needs to be imported
	 */
	public function call_import_route( $api_data ) {
		return $this->instant_terms_import( $api_data );
	}

}
