<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\XML_RPC\Service;
use PMC\Theme_Unit_Test\Logger\Status;
use PMC\Theme_Unit_Test\Settings\Config;

class Posts {

	use Singleton;

	const LOG_NAME = 'post';

	private $_post_log_data = array(
		'post_status'        => '',
		'post_type'          => '',
		'post_author'        => '',
		'ping_status'        => '',
		'post_parent'        => '',
		'menu_order'         => '',
		'post_password'      => '',
		'post_excerpt'       => '',
		'import_id'          => 0,
		'post_content'       => '',
		'post_title'         => '',
		'post_date'          => '',
		'post_modified'      => '',
		'post_category'      => '',
		'error_message'      => '',
		'meta_error_message' => '',
	);

	/**
	 * Insert a Post Meta to the DB.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param int $post_id
	 * @param array containing Post Meta data
	 *
	 * @return int|WP_Error The Meta data Id on success. The value 0 or WP_Error on failure.
	 */
	private function _save_post_meta( $post_id, $meta_data ) {

		$status = Status::get_instance();
		try {
			$meta_data_id = add_post_meta( $post_id, $meta_data['key'], $meta_data['value'], true );
			if ( ! $meta_data_id ) {
				$previous_value = get_post_meta( $post_id, $meta_data['key'], true );
				update_post_meta( $post_id, $meta_data['key'], $meta_data['value'], $previous_value );
			}
		} catch ( \Exception $e ) {
			$this->_post_log_data['meta_error_message'] = $this->_post_log_data['meta_error_message'] . ' -- ' . $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_log_data ) );
		}

		return $meta_data_id;
	}


	/**
	 * Insert a new Post to the DB.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array $post_json containing Post data
	 * @param int $author_id Author Id
	 * @param array $cat_ids_arr Array of Category Ids associated with the post
	 * @param string $post_type
	 *
	 * @return int|WP_Error The Post Id on success. The value 0 or WP_Error on failure.
	 */
	public function save_post( $post_json, $author_id = 0, $cat_ids_arr = array(), $post_type = 'post' ) {

		$status = Status::get_instance();

		$post_id = 0;

		$post_data = $this->_post_log_data;

		try {

			$post_obj = wpcom_vip_get_page_by_title( $post_json['title'], OBJECT, $post_type );

			if ( ! empty( $post_obj ) ) {

				$post_data['post_title']    = $post_json['title'];
				$post_data['import_id']     = $post_json['ID'];
				$post_data['error_message'] = 'POST already Exists. Skipped Inserting.';
				$status->save_current_log( self::LOG_NAME, array( $post_obj->ID => $post_data ) );

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
					'post_content'  => apply_filters( 'pmc_process_post_content', $post_json['content'], $post_json['type'] ),
					'post_title'    => $post_json['title'],
					'post_date'     => $post_json['date'],
					'post_modified' => $post_json['modified'],
					'post_category' => $cat_ids_arr,
				);

				$post_id = wp_insert_post( $post_data );

				if ( is_wp_error( $post_id ) ) {
					$post_data['error_message'] = $post_id->get_error_message();
					$post_id                    = $post_json['ID'];
				} else {
					if ( false !== $post_json['sticky'] ) {
						stick_post( $post_id );
					}
				}

				$status->save_current_log( self::LOG_NAME, array( $post_id => $post_data ) );

				return $post_id;
			}
		} catch ( \Exception $e ) {

			$post_data['error_message'] = $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $post_id => $post_data ) );

			return false;

		}
	}


	/**
	 * Assemble post data from API and inserts new post.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of post object
	 *
	 * @return array of posts ids on success.
	 * @todo - Find ways to insert post as an object along with all its terms and meta rather than creating an array from json_data
	 */
	public function instant_posts_import( $posts_json ) {

		$post_ids = array();
		$post_id  = 0;
		$status   = Status::get_instance();

		if ( empty( $posts_json ) || ! is_array( $posts_json ) ) {
			return $post_ids;
		}
		foreach ( $posts_json as $post_json ) {
			try {

				$params = array( 'post_id' => $post_json['ID'] );

				if ( ! empty( $post_json['author'] ) ) {
					$author = get_user_by( 'login', $post_json['author']['login'] );
					if ( $author ) {
						$author_id = $author->ID;
					} else {
						// Save Author to DB and attach its ID to the post object
						$author_id = Users::get_instance()->save_user( $post_json['author'] );
					}
				}
				if ( empty( $post_json['author'] ) || empty( $author_id ) || is_wp_error( $author_id ) ) {
					$author_id = get_current_user_id();
				}
				// save Categories associated with the post.
				$cat_ids = array();
				if ( ! empty( $post_json['categories'] ) ) {
					foreach ( $post_json['categories'] as $key => $post_category ) {
						$cat_ids[] = Categories::get_instance()->save_category( $post_category );
					}
				}
				// Save post and get its ID in order to save other meta data related to it.
				$post_id = $this->save_post( $post_json, $author_id, $cat_ids, $post_json['type'] );
				if ( ! empty( $post_id ) ) {
					$post_ids[ $post_json['ID'] ] = $post_id;
					// save tags associated with the post.
					if ( ! empty( $post_json['tags'] ) ) {
						foreach ( $post_json['tags'] as $key => $terms ) {
							wp_set_post_terms( $post_id, $terms['name'], 'post_tag' );
						}
					}
					// save Post Meta associated with the post.
					if ( ! empty( $post_json['metadata'] ) ) {
						foreach ( $post_json['metadata'] as $post_metadata ) {
							$old_meta_ids[] = $post_metadata['id'];
							$meta_ids[]     = $this->_save_post_meta( $post_id, $post_metadata );
						}
					}

					// Fetch the custom taxonomy terms and custom fields for this post using XMLRPC.
					$this->_maybe_call_xmlrpc_routes( $params, $post_json, $post_id );

					// Save the featured image of the post
					if ( ! empty( $post_json['featured_image'] ) ) {
						Attachments::get_instance()->save_featured_image( $post_json['featured_image'], $post_id );
					}

					if ( ! empty( $post_json['attachments'] ) ) {
						// save attachments associated with the post.
						$attachment_ids[] = Attachments::get_instance()->call_import_route( $post_json['attachments'], $post_id );
					}

					if ( ! empty( $post_json['comment_count'] ) ) {
						$comments_ids[] = Comments::get_instance()->call_rest_api_route( $post_json['ID'], $post_id );
					}
				}
			} catch ( \Exception $ex ) {

				$this->_post_log_data['meta_error_message'] = $this->_post_log_data['meta_error_message'] . ' -- ' . $ex->get_error_message();
				$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_log_data ) );

				continue;
			}
		}

		return $post_ids;
	}

	protected function _maybe_call_xmlrpc_routes( $params, $post_json, $post_id ) {

		try {

			$status = Status::get_instance();

			$xmlrpc = Service::get_instance()->is_xmlrpc_valid();

			if ( ! $xmlrpc ) {
				return;
			}

			$post_meta_data = Service::get_instance()->call_xmlrpc_api_route( 'posts', $params );

			if ( is_wp_error( $post_meta_data ) ) {

				$this->_post_log_data['meta_error_message'] = $this->_post_log_data['meta_error_message'] . ' -- ' . $post_meta_data->get_error_message();
				$status->save_current_log( self::LOG_NAME, array( $post_json['ID'] => $this->_post_log_data ) );

			} elseif ( ! empty( $post_meta_data ) && is_array( $post_meta_data ) ) {

				if ( is_wp_error( $post_meta_data[0] ) ) {

					$this->_post_log_data['meta_error_message'] = $this->_post_log_data['meta_error_message'] . ' -- ' . $post_meta_data[0]->get_error_message();
					$status->save_current_log( self::LOG_NAME, array( $post_json['ID'] => $this->_post_log_data ) );

				} else {

					// Save the custom taxonomy terms for this post.
					// Expecting only one value in $post_meta_data with 0 index since this is only for one post
					// Save all the terms
					foreach ( $post_meta_data[0]['terms'] as $custom_term ) {

						// post_tag and category fetched separately from REST API. We save only the custom taxonomy terms here
						if ( ! in_array( $custom_term['taxonomy'], (array) Config::$default_taxonomies, true ) ) {
							$term_id = Terms::get_instance()->save_taxonomy_terms( $custom_term );
							wp_set_object_terms( $post_id, array( $custom_term['name'] ), $custom_term['taxonomy'], true );
						}

					}

					// Save all the custom fields
					foreach ( $post_meta_data[0]['custom_fields'] as $custom_field ) {
						if ( empty( $old_meta_ids ) || ( is_array( $old_meta_ids ) && ! in_array( $custom_field['id'], (array) $old_meta_ids, true ) ) ) {
							$meta_ids[] = $this->_save_post_meta( $post_id, $custom_field );
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			// @TOdo : do nothing for now but handle better in future
			return;
		}
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-15
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @param array $api_data data returned from the REST API that needs to be imported
	 *
	 * @return array
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
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 *
	 * @param string $post_type post type name to register
	 * @param array $args post type arguments
	 *
	 */
	public function save_post_type( $post_type, $args = [] ) {

		if ( post_type_exists( $post_type ) ) {
			return;
		}

		$default_args = array(
			'public' => true,
			'label'  => $post_type,
		);

		if ( ! empty( $args ) ) {
			$args = wp_parse_args( $args, $default_args );
		}

		register_post_type( $post_type, $args );
	}

	public function get_post_log_data() {
		return $this->_post_log_data;
	}

}

Custom_Posts::get_instance();
