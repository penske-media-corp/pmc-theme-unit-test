<?php
namespace PMC\Theme_Unit_Test;

class Users_Importer extends PMC_Singleton {

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

			if ( empty( $user_info ) ) {

				error_log( $time . ' No User data Passed. ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return false;

			}
			$user_id = username_exists( $user_info['login'] );

			$user_id = ( ! empty( $user_id ) ) ? $user_id : 0;

			$user_data = array(
				'ID'            => $user_id,
				'user_login'    => $user_info['login'],
				'user_name'     => $user_info['name'],
				'first_name'    => $user_info['first_name'],
				'last_name'     => $user_info['last_name'],
				'user_nicename' => $user_info['nice_name'],
				'user_url'      => $user_info['URL'],
				'user_email'    => $user_info['email'],
				'user_pass'     => $user_info['login'],
			);

			if ( ! empty( $user_info['roles'] ) && is_array( $user_info['roles'] ) ) {
				$role = $user_info['roles'][0];
				// Check if the role exists
				$role_obj = get_role( $role );
				if ( empty( $role_obj ) ) {
					$role = 'editor';
				}
				$user_data['role'] = $role;
			}

			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {

				error_log( $time . ' -- ' . $user_id->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return $user_id;

			} else {

				error_log( "{$time} -- User **-- {$user_info['name']} --** added with ID = {$user_id}" . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				return $user_id;
			}
		} catch ( \Exception $e ) {

			error_log( $time . $e->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return false;

		}
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
	public function instant_users_import( $users_json ) {

		$user_ids = array();

		if ( empty( $users_json ) || ! is_array( $users_json ) ) {
			return $user_ids;
		}

		foreach ( $users_json as $user_data ) {

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





