<?php

namespace PMC\Theme_Unit_Test\Logger;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Settings\Config;

class Status {

	use Singleton;

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	protected function __construct() {
	}

	/**
	 * Log the status of the import to a file depending on the type of log
	 *
	 * @since 2016-07-25
	 * @version 2016-07-25 Archana Mandhare PMCVIP-1950
	 *
	 * @param string $message
	 *
	 */
	public function log_to_file( $message ) {
		if ( ! empty ( $message ) ) {
			$time = date( '[d/M/Y:H:i:s]' );
			error_log( $time . ', ' . $message . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}
	}

	/**
	 * Save log messages to post meta
	 *
	 * @since 2016-07-26
	 * @version 2016-07-26 Archana Mandhare PMCVIP-1950
	 *
	 * @param string $log_type
	 * @param array $log_value
	 *
	 * @return mixed
	 *
	 */
	public function save_current_log( $log_type, $log_value ) {

		$logger = $this->get_current_log_post();

		if ( empty( $logger ) ) {
			return false;
		}

		$meta = get_post_meta( $logger->ID, $log_type, true );
		if ( ! empty( $meta ) ) {
			$log_value = array_merge( $meta, $log_value );
		}

		$meta = update_post_meta( $logger->ID, $log_type, $log_value );

		return $meta;
	}

	/**
	 * Get log messages saved to post meta
	 *
	 * @since 2016-07-26
	 * @version 2016-07-26 Archana Mandhare PMCVIP-1950
	 *
	 * @param string $log_type
	 *
	 * @return mixed
	 *
	 */
	public function get_current_log( $log_type = '' ) {

		$logger = $this->get_current_log_post();

		if ( empty( $logger ) ) {
			return false;
		}

		if ( empty( $log_type ) ) {
			$meta = get_post_meta( $logger->ID );
		} else {
			$meta = get_post_meta( $logger->ID, $log_type, true );
		}

		return $meta;
	}

	/**
	 * Get current post that contains the log messages
	 *
	 * @since 2016-07-26
	 * @version 2016-07-26 Archana Mandhare PMCVIP-1950
	 *
	 * @return WP_Post
	 *
	 */
	public function get_current_log_post() {

		$post_id = get_option( Config::import_log );

		if ( empty( $post_id ) ) {
			return false;
		}
		$logger = get_post( $post_id );

		return $logger;

	}

	/**
	 * Delete all previous meta keys to save new keys
	 *
	 * @since 2016-07-26
	 * @version 2016-07-26 Archana Mandhare PMCVIP-1950
	 *
	 */
	public function clean_log() {

		$post_id = get_option( Config::import_log );

		$meta = $this->get_current_log();

		foreach ( $meta as $key => $val ) {
			delete_post_meta( $post_id, $key );
		}

	}

}    //end class

//EOF
