<?php
namespace PMC\Theme_Unit_Test;

class Taxonomies_Importer extends PMC_Singleton {

	/**
	 * Insert a new Taxonomy to the DB.
	 *
	 * @since 2015-07-21
	 *
	 * @version 2015-07-21 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Taxonomy data
	 *
	 * @return int|WP_Error The taxonomy Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_taxonomy( $taxonomy_json ) {

		global $wp_taxonomies;
		$time               = date( '[d/M/Y:H:i:s]' );
		$built_in_posttypes = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' );
		try {

			if ( empty( $taxonomy_json ) ) {

				error_log( $time . ' No Taxonomy data Passed. ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return false;
			}

			$taxonomy_id = taxonomy_exists( $taxonomy_json['name'] );

			if ( false === $taxonomy_id ) {

				if ( in_array( $taxonomy_json['object_type'], $built_in_posttypes ) ) {

					$args = array(
						'label'             => ( ! empty( $taxonomy_json['label'] ) ) ? $taxonomy_json['label'] : $taxonomy_json['name'],
						'labels'            => ( ! empty( $taxonomy_json['labels'] ) ) ? $taxonomy_json['labels'] : $taxonomy_json['name'],
						'show_ui'           => ( ! empty( $taxonomy_json['show_ui'] ) ) ? $taxonomy_json['show_ui'] : null,
						'public'            => ( ! empty( $taxonomy_json['public'] ) ) ? $taxonomy_json['public'] : true,
						'hierarchical'      => ( ! empty( $taxonomy_json['hierarchical'] ) ) ? $taxonomy_json['hierarchical'] : false,
						'show_in_menu'      => ( ! empty( $taxonomy_json['show_in_menu'] ) ) ? $taxonomy_json['show_in_menu'] : null,
						'show_in_nav_menus' => ( ! empty( $taxonomy_json['show_in_nav_menus'] ) ) ? $taxonomy_json['show_in_nav_menus'] : null,
						'capabilities'      => ( ! empty( $taxonomy_json['capabilities'] ) ) ? $taxonomy_json['capabilities'] : array(),
						'query_var'         => ( ! empty( $taxonomy_json['query_var'] ) ) ? $taxonomy_json['query_var'] : true,
						'sort'              => ( ! empty( $taxonomy_json['sort'] ) ) ? $taxonomy_json['sort'] : true,
						'args'              => ( ! empty( $taxonomy_json['args'] ) ) ? $taxonomy_json['args'] : array(),
						'rewrite'           => ( ! empty( $taxonomy_json['rewrite'] ) ) ? $taxonomy_json['rewrite'] : array(),
					);

					register_taxonomy( $taxonomy_json['name'], $taxonomy_json['object_type'], $args );

				} else {

					register_taxonomy_for_object_type( $taxonomy_json['name'], $taxonomy_json['object_type'] );

				}

				$taxonomy_id = taxonomy_exists( $taxonomy_json['name'] );

				if ( is_wp_error( $taxonomy_id ) ) {

					error_log( $time . ' -- ' . $taxonomy_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

					return false;

				} else if ( false !== $taxonomy_id ) {

					error_log( "{$time} -- Taxonomy **-- {$taxonomy_json['name']} --** added." . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

					return $wp_taxonomies[ $taxonomy_json['name'] ];
				}
			} else {

				error_log( "{$time} -- Exists Taxonomy **-- {$taxonomy_json['name']} --**" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

				return $wp_taxonomies[ $taxonomy_json['name'] ];
			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}
	}


	/**
	 * Assemble Taxonomies data from XMLRPC and inserts new Taxonomy.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Taxonomy object
	 *
	 * @return array of Taxonomies ids on success.
	 *
	 */
	public function instant_taxonomies_import( $taxonomies_json ) {

		$taxonomies_info = array();

		if ( empty( $taxonomies_json ) || ! is_array( $taxonomies_json ) ) {
			return $taxonomies_info;
		}

		foreach ( $taxonomies_json as $taxonomy_json ) {

			// Don't save taxonomy category or post_tag since its built-in.
			if ( in_array( $taxonomy_json['name'], Config::$default_taxonomies ) ) {
				continue;
			}
			$taxonomies_info[] = $this->save_taxonomy( $taxonomy_json );

		}

		return $taxonomies_info;

	}


	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 *
	 * @version 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from XMLRPC call that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		return $this->instant_taxonomies_import( $api_data );
	}
}
