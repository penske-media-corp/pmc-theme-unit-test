<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Comments_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-16 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
	}


	/**
	 * Insert a new Comment to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @params  @type array   $comment_json   containing Comment data
	 * @type int $post_ID Post Id this comment is associated with
	 *
	 *
	 * @return int|WP_Error The Comment Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	public function save_comment( $comment_json, $post_ID ) {

		$time = date( '[d/M/Y:H:i:s]' );

		$comment_id = 0;

		try {

			$comment_data = array(
				'comment_post_ID'      => $post_ID,
				'comment_author'       => $comment_json['author']['name'],
				'comment_author_email' => $comment_json['author']['email'],
				'comment_author_url'   => $comment_json['author']['URL'],
				'comment_content'      => $comment_json['content'],
				'comment_type'         => $comment_json['type'],
				'comment_parent'       => ( false === $comment_json['parent'] ) ? 0 : $comment_json['parent'],
				'comment_status'       => $comment_json['status'],
				'comment_date'         => $comment_json['date'],
				'user_id'              => get_current_user_id(),
			);

			$comment_id = wp_insert_comment( $comment_data );

			if ( is_a( $comment_id, "WP_Error" ) ) {

				error_log( $time . " -- " . $comment_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			} else {

				error_log( "{$time} -- Comment from author **-- {$comment_json['author']['name'] } --** added with ID = {$comment_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}

		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $comment_id;
	}


	/**
	 * Assemble user data from API and inserts post comments.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of comments object
	 *
	 * @return array of Comments ids on success.
	 * @todo - Find ways to insert comments as an object mapped from json_decode
	 *
	 */
	public function instant_comments_import( $json_data, $post_ID ) {

		$comments_ids = array();

		foreach ( $json_data as $comment_data ) {

			$comments_ids[] = $this->save_comment( $comment_data, $post_ID );

		}

		return $comments_ids;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data, $post_ID, $domain = '' ) {

		return $this->instant_comments_import( $api_data, $post_ID );

	}

	/**
	 * Access endpoints to make call to REST API
	 *
	 * This method will make a call to the public REST API
	 * and fetch data from live site and save to the current site DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function call_rest_api_route( $old_post_id, $new_post_ID ) {

		//Fetch comment for each post and save to the DB
		$route = "posts/{$old_post_id}/replies";

		$comments = REST_API_oAuth::get_instance()->access_endpoint( $route, array(), "comments", false );

		if ( ! empty( $comments ) ) {

			$this->call_import_route( $comments, $new_post_ID );

		}

	}


}
