<?php

namespace PMC\Theme_Unit_Test\Importer;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Logger\Status;

class Users {

	use Singleton;

	const LOG_NAME = 'users';

	/**
	 * Insert a new user to the DB.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
	 *
	 * @param array containing user data
	 *
	 * @return int|WP_Error The user Id on success. The value 0 or WP_Error on failure.
	 * @todo - Find ways to insert user as an object mapped from json_decode along with all its roles and meta data rather than creating an array from json_data
	 */
	public function save_user( $user_info ) {

		$status = Status::get_instance();

		$user_id = 0;

		$user_data = array(
			'ID'            => $user_id,
			'user_login'    => 0,
			'user_name'     => 0,
			'first_name'    => 0,
			'last_name'     => 0,
			'user_nicename' => 0,
			'user_url'      => 0,
			'user_email'    => 0,
			'user_pass'     => 0,
			'error_message' => '',
		);

		try {

			if ( empty( $user_info ) ) {
				$user_data['error_message'] = 'NO USER DETAILS PASSED BY API';
				$status->save_current_log( self::LOG_NAME, array( $user_id => $user_data ) );

				return false;
			}

			$user_id   = username_exists( $user_info['login'] );
			$user_id   = ( ! empty( $user_id ) ) ? $user_id : 0;
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
				$user_data['error_message'] = $user_id->get_error_message();
				$user_id                    = 0;
			}

			$user_data['user_pass'] = '';
			$status->save_current_log( self::LOG_NAME, array( $user_id => $user_data ) );

			return $user_id;

		} catch ( \Exception $e ) {
			$user_data['error_message'] = $e->getMessage();
			$user_data['user_pass']     = '';
			$status->save_current_log( self::LOG_NAME, array( $user_id => $user_data ) );

			return false;
		}
	}


	/**
	 * Assemble user data from API and inserts new user.
	 *
	 * @since 2015-07-13
	 * @version 2015-07-13 Archana Mandhare PPT-5077
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
	 * @version 2015-07-15 Archana Mandhare PPT-5077
	 *
	 * @param array $api_data data returned from the REST API that needs to be imported
	 *
	 * @return array
	 */
	public function call_import_route( $api_data ) {
		return $this->instant_users_import( $api_data );
	}
}





