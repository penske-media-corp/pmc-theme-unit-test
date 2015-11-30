<?php
namespace PMC\Theme_Unit_Test;

class Posts_Importer extends PMC_Singleton {

	/**
	 * Insert a Post Meta to the DB.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array containing Post Meta data
	 *
	 * @return int|WP_Error The Meta data Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_post_meta( $post_ID, $meta_data ) {

		try {

			$meta_data_ID = add_post_meta( $post_ID, $meta_data['key'], $meta_data['value'], true );
			if( ! $meta_data_ID ) {
				$meta_data_ID = update_post_meta( $post_ID, $meta_data['key'], $meta_data['value'], '');
			}

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $meta_data_ID;

	}


	/**
	 * Insert a new Post to the DB.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @params  @type array   $post_json   containing Post data
	 * @type int $author_id Author Id
	 * @type array $cat_IDs Array of Category Ids associated with the post
	 *
	 * @return int|WP_Error The Post Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_post( $post_json, $author_id = 0, $cat_ids_arr = array(), $post_type = 'post' ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$post_obj = wpcom_vip_get_page_by_title( $post_json['title'], OBJECT, $post_type );

			if ( ! empty( $post_obj ) ) {

				error_log( "{$time} -- Exists Post **-- {$post_json['title']} --** with ID = {$post_obj->ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE );

				return $post_obj->ID;

			} else {

				$post_data = array(
					'post_status'   => $post_json['status'],
					'post_type'     => $post_json['type'],
					'post_author'   => ! empty( $author_id ) ? $author_id : get_current_user_id(),
					'ping_status'   => get_option( 'default_ping_status' ),
					'post_parent'   => ( false === $post_json['parent'] ) ? 0 : $post_json['parent'],
					'menu_order'    => $post_json['menu_order'],
					'post_password' => $post_json['password'],
					'post_excerpt'  => $post_json['excerpt'],
					'import_id'     => $post_json['ID'],
					'post_content'  => $post_json['content'],
					'post_title'    => $post_json['title'],
					'post_date'     => $post_json['date'],
					'post_modified' => $post_json['modified'],
					'post_category' => $cat_ids_arr,
				);

				$post_ID = wp_insert_post( $post_data );

				if ( false !== $post_json['sticky'] ) {

					stick_post( $post_ID );

				}

				if ( is_wp_error( $post_ID ) ) {

					error_log( $time . ' -- ' . $post_ID->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

					return $post_ID;

				} else {

					error_log( "{$time} -- {$post_json['type']} **-- {$post_json['title']} --** added with ID = {$post_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

					return $post_ID;
				}
			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return false;

		}

	}


	/**
	 * Assemble post data from API and inserts new post.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
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

		if ( empty( $posts_json ) || ! is_array( $posts_json ) ) {
			return $post_ids;
		}

		foreach ( $posts_json as $post_json ) {

			try {

				if ( ! empty( $post_json['author'] ) ) {
					$author = get_user_by( 'login', $post_json['author']['login'] );
					if ( $author ) {
						$author_ID = $author->ID;
					} else {
						// Save Author to DB and attach its ID to the post object
						$author_ID = Users_Importer::get_instance()->save_user( $post_json['author'] );
					}
				}

				if ( empty( $post_json['author'] ) || empty( $author_ID ) || is_wp_error( $author_ID ) ) {
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
				$post_ID = $this->save_post( $post_json, $author_ID, $cat_ids, $post_json['type'] );

				if ( $post_ID ) {

					$post_ids[ $post_json['ID'] ] = $post_ID;

					// save tags associated with the post.
					if ( ! empty( $post_json['tags'] ) ) {

						foreach ( $post_json['tags'] as $key => $terms ) {

							wp_set_post_terms( $post_ID, $terms['name'], 'post_tag' );

						}
					}

					// save Post Meta associated with the post.
					if ( ! empty( $post_json['metadata'] ) ) {

						foreach ( $post_json['metadata'] as $post_metadata ) {

							$old_meta_ids[] = $post_metadata['id'];
							$meta_ids[]     = $this->_save_post_meta( $post_ID, $post_metadata );

						}
					}

					// Fetch the custom taxonomy terms and custom fields for this post using XMLRPC.
					$params = array( 'post_id' => $post_json['ID'] );

					$post_meta_data = XMLRPC_Router::get_instance()->call_xmlrpc_api_route( 'posts', $params );

					// Save the custom taxonomy terms for this post.
					if ( ! empty( $post_meta_data ) && is_array( $post_meta_data ) ) {

						// Expecting only one value in $post_meta_data with 0 index since this is only for one post
						// Save all the terms
						foreach ( $post_meta_data[0]['terms'] as $custom_term ) {

							// post_tag and category fetched separately from REST API. We save only the custom taxonomy terms here
							if ( ! in_array( $custom_term['taxonomy'], Config::$default_taxonomies ) ) {

								$term_id = Terms_Importer::get_instance()->save_taxonomy_terms( $custom_term );
								wp_set_object_terms( $post_ID, array( $custom_term['name'] ), $custom_term['taxonomy'], true );

							}
						}
						// Save all the custom fields
						foreach ( $post_meta_data[0]['custom_fields'] as $custom_field ) {

							if ( empty( $old_meta_ids ) || ( is_array( $old_meta_ids ) && ! in_array( $custom_field['id'], $old_meta_ids ) ) ) {
								$meta_ids[] = $this->_save_post_meta( $post_ID, $custom_field );
							}
						}
					}

					// Save the featured image of the post
					if ( ! empty( $post_json['featured_image'] ) ) {

						Attachments_Importer::get_instance()->save_featured_image( $post_json['featured_image'], $post_ID );

					}

					if ( ! empty( $post_json['attachments'] ) ) {
						// save attachments associated with the post.
						$attachment_ids[] = Attachments_Importer::get_instance()->call_import_route( $post_json['attachments'], $post_ID );

					}

					if ( ! empty( $post_json['comment_count'] ) ) {

						$comments_ids[] = Comments_Importer::get_instance()->call_rest_api_route( $post_json['ID'], $post_ID );

					}
				}
			} catch ( \Exception $ex ) {

				error_log( $time . ' -- ' . esc_html( $ex->get_error_message() ) . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				continue;

			}

		}

		return $post_ids;
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

		wp_defer_term_counting( true );

		wp_defer_comment_counting( true );

		$inserted_posts = $this->instant_posts_import( $api_data );

		wp_defer_term_counting( false );

		wp_defer_comment_counting( false );

		return $inserted_posts;

	}

	/**
	 * If the post type does not exists register the new Custom Post Type
	 *
	 * @since 2015-07-21
	 *
	 * @version 2015-07-21 Archana Mandhare - PPT-5077
	 *
	 * @params string $post_type post type name to register
	 *
	 */
	public function save_post_type( $post_type ) {

		if ( ! post_type_exists( $post_type ) ) {

			$args = array(
				'public' => true,
				'label'  => $post_type,
			);

			register_post_type( $post_type, $args );
		}

	}
}
