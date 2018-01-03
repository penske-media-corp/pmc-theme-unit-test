<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Logger\Status;

class Attachments {

	use Singleton;

	const LOG_NAME = 'attachments';

	private $_post_data = array(
		'image_url'                => '',
		'attachment_error_message' => '',
	);

	/**
	 * Download an image from the specified URL and attach it to a post.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param @type array   $attachment_json   containing attachment data
	 * @param @type int $post_id Post Id this attachment is associated with
	 *
	 * @return int|WP_Error The attachment Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_attachment( $image_url, $post_id ) {

		$status = Status::get_instance();
		try {
			$attachment_id = media_sideload_image( $image_url, $post_id );
			if ( is_wp_error( $attachment_id ) ) {
				$this->_post_data['attachment_error_message'] = $this->_post_data['attachment_error_message'] . ' -- ' . $attachment_id->get_error_message();
			} else {
				$this->_post_data['image_url'] = $attachment_id;
			}

			$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_data ) );

		} catch ( \Exception $e ) {
			$this->_post_data['attachment_error_message'] = $this->_post_data['attachment_error_message'] . ' -- ' . $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_data ) );

			return false;
		}

		return $attachment_id;

	}


	/**
	 * Create a media library image from the given URL and attach it as featured image of the post.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param @type int $author_id Author Id
	 * @param @type string $image_url URL of the image
	 *
	 * @return int|WP_Error The Meta data Id on success. The value 0 or WP_Error on failure.
	 *
	 */

	public function save_featured_image( $image_url, $post_id ) {

		$status = Status::get_instance();

		try {

			if ( empty( $image_url ) || empty( $post_id ) ) {

				$this->_post_data['attachment_error_message'] = $this->_post_data['attachment_error_message'] . ' --  No Image URL and Post ID passed to save attachment';
				$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_data ) );

				return false;
			}

			// @todo - add title, caption, description to the attachment
			$attachment_html = $this->_save_attachment( $image_url, $post_id );

			if ( is_wp_error( $attachment_html ) || empty( $attachment_html ) ) {

				$this->_post_data['attachment_error_message'] = $this->_post_data['attachment_error_message'] . ' Image URL not uploaded ' . $image_url;
				$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_data ) );

				return false;

			}
			// then find the last image added to the post attachments
			$attachments = get_posts( array(
				'numberposts'    => '1',
				'post_parent'    => $post_id,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => 'ASC',
			) );

			if ( sizeof( $attachments ) > 0 ) {
				// set image as the post thumbnail
				return set_post_thumbnail( $post_id, $attachments[0]->ID );
			} else {
				return false;
			}

		} catch ( \Exception $e ) {

			$this->_post_data['attachment_error_message'] = $this->_post_data['attachment_error_message'] . ' -- ' . $e->getMessage();
			$status->save_current_log( self::LOG_NAME, array( $post_id => $this->_post_data ) );

			return false;

		}
	}

	/**
	 * Assemble Attachments data from API and inserts new attachments.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array json_decode() array of Attachment object
	 * @param int $post_id
	 *
	 * @return array of Attachments ids on success.
	 */
	public function instant_attachments_import( $attachments_json, $post_id ) {

		$attachments_info = array();
		if ( empty( $attachments_json ) || ! is_array( $attachments_json ) ) {
			return $attachments_info;
		}

		$count = 0;
		foreach ( $attachments_json as $key => $attachment_json ) {
			// fetch only 5 attachments
			if ( $count > 5 ) {
				break;
			}
			$attachments_id = $this->_save_attachment( $attachment_json['URL'], $post_id );
			if ( ! empty( $attachments_id ) ) {
				$attachments_info[] = $attachments_id;
			}
			$count ++;
		}

		return $attachments_info;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 2015-07-16
	 * @version 2015-07-16 Archana Mandhare PPT-5077
	 *
	 * @param array $api_data data returned from the REST API that needs to be imported
	 * @param int $post_id
	 *
	 * @return array
	 *
	 */
	public function call_import_route( $api_data, $post_id ) {
		return $this->instant_attachments_import( $api_data, $post_id );
	}

}
