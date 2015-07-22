<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Users_Importer extends PMC_Singleton {

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
	 * Insert a new user to the DB.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13, for PPT-5077, Archana Mandhare
	 *
	 * @param array containing user data
	 *
	 * @return int|WP_Error The user Id on success. The value 0 or WP_Error on failure.
	 * @todo - Find ways to insert user as an object mapped from json_decode along with all its roles and meta data rather than creating an array from json_data
	 *
	 */
	public function save_user( $user_info ) {

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$user_ID = username_exists( $user_info['login'] );

			if ( empty( $user_ID ) ) {

				$user_data = array(
					'user_login'    => $user_info['login'],
					'user_name'     => $user_info['name'],
					'user_nicename' => $user_info['nice_name'],
					'user_url'      => $user_info['URL'],
					'user_email'    => $user_info['email'],
				);

				if ( ! empty( $user_info['roles'] ) ) {
					$user_data['role'] = $user_info['roles'];
				}

				$user_ID = wp_insert_user( $user_data );

				if ( is_a( $user_ID, "WP_Error" ) ) {

					error_log( $time . " -- " . $user_ID->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				} else {

					error_log( "{$time} -- User **-- {$user_info['name']} --** added with ID = {$user_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				}

			} else {

				error_log( "{$time} -- Exists User **-- {$user_info['name']} --** with ID = {$user_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}


		} catch ( \Exception $e ) {

			error_log( $time . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $user_ID;
	}


	/**
	 * Assemble user data from API and inserts new user.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-13, for PPT-5077, Archana Mandhare
	 *
	 * @param array json_decode() array of user object
	 *
	 * @return array of Users ids on success.
	 */
	public function instant_users_import( $json_data ) {

		$user_ids = array();

		foreach ( $json_data as $user_data ) {

			$user_ids[] = $this->save_user( $user_data );

		}

		return $user_ids;
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

		return $this->instant_users_import( $api_data );

	}

}





