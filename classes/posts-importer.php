<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Posts_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06, for PPT-5077, Archana Mandhare
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
		
	}

	/**
	 * Insert a Post Meta to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13, for PPT-5077, Archana Mandhare
	 *
	 * @param array containing Post Meta data
	 *
	 * @return int|WP_Error The Meta data Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_post_meta( $post_ID, $meta_data ) {

		try {

			$meta_data_ID = add_post_meta( $post_ID, $meta_data['key'], $meta_data['value'], true );

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $meta_data_ID;

	}


	/**
	 * Insert a new Post to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13, for PPT-5077, Archana Mandhare
	 *
	 * @params  @type array   $post_json   containing Post data
	 * @type int $author_id Author Id
	 * @type array $cat_IDs Array of Category Ids associated with the post
	 *
	 * @return int|WP_Error The Post Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_post( $post_json, $author_id, $cat_IDs ) {

		global $wpdb;

		$time = date( '[d/M/Y:H:i:s]' );

		$post_ID = 0;

		try {

			$query = $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', sanitize_title_with_dashes( $post_json['title'] ) );

			$wpdb->query( $query );

			if ( $wpdb->num_rows ) {

				$post_ID = $wpdb->get_var( $query );

				error_log( "{$time} -- Exists {$post_json['type']} **-- {$post_json['title']} --** with ID = {$post_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			} else {

				$post_data = array(
					'post_status'   => $post_json['status'],
					'post_type'     => $post_json['type'],
					'post_author'   => $author_id,
					'ping_status'   => get_option( 'default_ping_status' ),
					'post_parent'   => ( false === $post_json['parent'] ) ? 0 : $post_json['parent'],
					'menu_order'    => $post_json['menu_order'],
					'to_ping'       => $post_json['pings_open'],
					'post_password' => $post_json['password'],
					'post_excerpt'  => $post_json['excerpt'],
					'import_id'     => $post_json['ID'],
					'post_content'  => $post_json['content'],
					'post_title'    => $post_json['title'],
					'post_date'     => $post_json['date'],
					'post_modified' => $post_json['modified'],
					'post_category' => $cat_IDs,
				);

				$post_ID = wp_insert_post( $post_data );

				if ( false !== $post_json['sticky'] ) {

					stick_post( $post_ID );

				}

				if ( is_a( $post_ID, "WP_Error" ) ) {

					error_log( $time . " -- " . $post_ID->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				} else {

					error_log( "{$time} -- {$post_json['type']} **-- {$post_json['title']} --** added with ID = {$post_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );
				}

			}

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $post_ID;
	}


	/**
	 * Assemble post data from API and inserts new post.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13, for PPT-5077, Archana Mandhare
	 *
	 * @param array json_decode() array of post object
	 *
	 * @return array of posts ids on success.
	 * @todo - Find ways to insert post as an object along with all its terms and meta rather than creating an array from json_data
	 *
	 */
	public function instant_posts_import( $posts_json ) {

		$post_ids = array();

		$time = date( '[d/M/Y:H:i:s]' );


		if ( ! empty( $posts_json ) ) {

			$post_json = $posts_json[0];

			$this->save_post_type( $post_json['type'] );
		}

		foreach ( $posts_json as $post_json ) {

			try {

				if ( ! empty( $post_json['author'] ) ) {
					// Save Author to DB and attach its ID to the post object
					$author_ID = get_user_by( 'login', $post_json['author']['login'] );
				}

				if ( empty( $author_ID ) ) {
					$author_ID = get_current_user_id();
				}
				// save Categories associated with the post.
				$cat_ids = array();
				if ( ! empty( $post_json['categories'] ) ) {
					foreach ( $post_json['categories'] as $key => $post_category ) {
						$cat_ids[] = Categories_Importer::get_instance()->save_category( $post_category );
					}
				}

				// Save post and get its ID in order to save other meta data related to it.
				$post_ID = $this->_save_post( $post_json, $author_ID, $cat_ids );

				if ( $post_ID ) {

					$post_ids[ $post_json['ID'] ] = $post_ID;

					// save tags associated with the post.
					if ( ! empty ( $post_json['tags'] ) ) {

						foreach ( $post_json['tags'] as $key => $terms ) {

							wp_set_post_terms( $post_ID, $terms['name'], 'post_tag' );

						}

					}

					// save Post Meta associated with the post.
					if ( ! empty ( $post_json['metadata'] ) ) {

						foreach ( $post_json['metadata'] as $post_metadata ) {

							$meta_ids[] = $this->_save_post_meta( $post_ID, $post_metadata );

						}

					}

					// Save the featured image of the post
					if ( ! empty ( $post_json['featured_image'] ) ) {

						Attachments_Importer::get_instance()->save_featured_image( $post_json['featured_image'], $post_ID );

					}

					if ( ! empty( $post_json['attachments'] ) ) {
						// save attachments associated with the post.
						$attachment_ids[] = Attachments_Importer::get_instance()->call_import_route( $post_json['attachments'], $post_ID );

					}

					if ( ! empty( $post_json['comment_count'] ) ) {

						$comments_ids[] = Comments_Importer::get_instance()->call_json_api_route( $post_json['ID'], $post_ID );

					}

				}

			} catch ( \Exception $ex ) {

				error_log( $time . " -- " . esc_html( $ex->get_error_message() ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				continue;

			}

		}

		return $post_ids;
	}


	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-15, for PPT-5077, Archana Mandhare
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		wp_defer_term_counting( true );

		wp_defer_comment_counting( true );

		$inserted_posts = $this->instant_posts_import( $api_data );

		wp_defer_term_counting( false );

		wp_defer_comment_counting( false );

		return $inserted_posts;


	}

	public function save_post_type( $post_type ) {

		if ( ! post_type_exists( $post_type ) ) {

			$args = array(
				'public' => true,
				'label'  => $post_type
			);

			register_post_type( $post_type, $args );
		}

	}


	/**
	 * Return the endpoint configuration values for Post and all the allowed Custom Post types
	 * that are required to make a call to the API
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14, for PPT-5077, Archana Mandhare
	 *
	 * @param array $args contains the Domain that is required to indentify the client and get its details
	 *
	 * @return array The array containing the client configuration details
	 *
	 *
	 */
	public function get_post_routes() {

		// Fetch the posts and the custom post types.
		$allowed_types = array( 'page', 'post' );

		$allowed_post_types = apply_filters( 'rest_api_allowed_post_types', $allowed_types );

		$route_post_types = array_unique( $allowed_post_types );

		foreach ( $route_post_types as $route_post_type ) {
			$post_type = array(
				'post' => array(
					"access_token" => false,
					"query_params" => array(
						"type" => $route_post_type,
					)
				)
			);

			$post_routes[] = $post_type;
		}

		return $post_routes;

	}


}
