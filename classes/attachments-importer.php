<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Attachments_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
	}

	/**
	 * Insert a new attachment to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @params  @type array   $attachment_json   containing attachment data
	 *          @type int $post_id Post Id this attachment is associated with
	 *
	 *
	 * @return int|WP_Error The attachment Id on success. The value 0 or WP_Error on failure.
	 *
	 */
	private function _save_attachment( $image_url, $post_ID ) {

		$time          = date( '[d/M/Y:H:i:s]' );

		$attachment_id = 0;

		try {

			$upload_dir = wp_upload_dir();

			$filename = basename( $image_url );

			if ( wp_mkdir_p( $upload_dir['path'] ) ) {

				$file = $upload_dir['path'] . '/' . $filename;

			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}

			if ( ! file_exists( $file ) ) {

				$image_data = file_get_contents( $image_url );

				if ( false === $image_data ) {
					throw new \Exception( $time . ' No Image data returned for image URL ' . $image_url );
				}

				file_put_contents( $file, $image_data );

				$wp_filetype = wp_check_filetype( $filename, null );

				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				$attachment_id = wp_insert_attachment( $attachment, $file, $post_ID );

				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				$attach_data = wp_generate_attachment_metadata( $attachment_id, $file );

				wp_update_attachment_metadata( $attachment_id, $attach_data );

				if ( is_a( $attachment_id, 'WP_Error' ) ) {

					error_log( $time . ' -- ' . $attachment_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				} else {

					error_log( "{$time} -- Attachment URL **-- { $image_url } --** added with ID = {$attachment_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				}
			}
		} catch ( \Exception $e ) {

			error_log( $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $attachment_id;
	}


	/**
	 * Create a media library image from the given URL and attach it as featured image of the post.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @params @type int $author_id Author Id
	 *         @type string $image_url URL of the image
	 * @return int|WP_Error The Meta data Id on success. The value 0 or WP_Error on failure.
	 *
	 */

	public function save_featured_image( $image_url, $post_ID ) {

		$post_meta_id = 0;

		try {

			$attach_id = $this->_save_attachment( $image_url, $post_ID );

			if ( ! empty( $attach_id ) ) {
				$post_meta_id = set_post_thumbnail( $post_ID, $attach_id );

			}
		} catch ( \Exception $e ) {

			error_log( 'Save Featured Image Failed with Error ---- ' . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $post_meta_id;

	}

	/**
	 * Assemble Attachments data from API and inserts new attachments.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13 Archana Mandhare - PPT-5077
	 *
	 * @param array json_decode() array of Attachment object
	 *
	 * @return array of Attachments ids on success.
	 */
	public function instant_attachments_import( $attachments_json, $post_ID ) {

		$attachments_info = array();

		foreach ( $attachments_json as $key => $attachment_json ) {

			$attachments_id = $this->_save_attachment( $attachment_json['URL'], $post_ID );

			if ( ! empty( $attachments_id ) ) {

				$attachments_info[] = $attachments_id;

			}
		}

		return $attachments_info;
	}

	/**
	 * Route the call to the import function for this class
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-16 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data, $post_ID, $domain=''  ) {

		return $this->instant_attachments_import( $api_data, $post_ID );

	}


}
