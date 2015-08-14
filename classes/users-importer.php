<?php
namespace PMC\Theme_Unit_Test;

class Users_Importer extends PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {
	}

	/**
	 * Insert a new user to the DB.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
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

			$user_ID = ( ! empty( $user_ID ) ) ? $user_ID : 0;

			error_log( $time . ' -- User ID =' . $user_ID . ' logging ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			$user_data = array(
				'ID'            => $user_ID,
				'user_login'    => $user_info['login'],
				'user_name'     => $user_info['name'],
				'user_nicename' => $user_info['nice_name'],
				'user_url'      => $user_info['URL'],
				'user_email'    => $user_info['email'],
			);

			if ( ! empty( $user_info['roles'] ) ) {

				$role = $user_info['roles'][0];

				$role_obj = get_role( $role );

				if ( empty( $role_obj ) ) {
					$role = 'editor';
				}

				$user_data['role'] = $role;

			}

			$user_ID = wp_insert_user( $user_data );

			if ( is_a( $user_ID, 'WP_Error' ) ) {

				error_log( $time . ' -- ' . $user_ID->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			} else {

				error_log( "{$time} -- User **-- {$user_info['name']} --** added with ID = {$user_ID}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

			}
		} catch ( \Exception $e ) {

			error_log( $time . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return $user_ID;
	}


	/**
	 * Assemble user data from API and inserts new user.
	 *
	 * @since 2015-07-13
	 *
	 * @version 2015-07-13 Archana Mandhare - PPT-5077
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
	 * @since 2015-07-15
	 *
	 * @version 2015-07-15 Archana Mandhare - PPT-5077
	 *
	 * @params array $api_data data returned from the REST API that needs to be imported
	 *
	 */
	public function call_import_route( $api_data ) {

		return $this->instant_users_import( $api_data );

	}

}





