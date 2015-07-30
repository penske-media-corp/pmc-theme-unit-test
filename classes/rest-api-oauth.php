<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class REST_API_oAuth extends PMC_Singleton {

	protected $_client_id;

	protected $_redirect_uri;

	protected $_client_secret;

	protected $_access_token;

	protected $_code;

	protected $_access_token_key;

	public $domain;

	/**
	 * Setup Hooks.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	protected function _init() {
	}

	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

	/**
	 * Initialize all the class variables and get the access token
	 * required for oAuth Authenticated REST API calls
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function initialize_params( $args ) {

		$this->set_domain( $args['domain'] );

		$this->_code = $args['code'];

		$client_details = apply_filters( 'pmc_theme_ut_endpoints_config', array(), $args );

		if ( ! empty( $client_details ) ) {

			$this->_client_id = $client_details['client_id'];

			$this->_client_secret = $client_details['client_secret'];

			$this->_redirect_uri = $client_details['redirect_uri'];

			if ( $args['route']['access_token'] === 'true' && empty( $this->_access_token ) ) {

				$this->_access_token_key = $this->_client_id . '_' . $args['domain'];

				$this->_access_token = $this->_get_saved_token();

				if ( empty( $this->_access_token ) ) {

					$this->_fetch_access_token();

				}
			}
		}

	}

	/**
	 * Authorise the request using the secret key access token
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	private function _get_authorization_code() {

		if ( ! empty( $this->_access_token ) && $this->is_token_valid() ) {

			return;

		}

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$params = array(
				'client_id'     => $this->_client_id,
				'response_type' => 'code',
				'redirect_uri'  => $this->_redirect_uri,
			);

			$args = array(
				'method'  => 'GET',
				'timeout' => 500,
				'body'    => $params,
			);

			$response = wp_remote_get( Config::AUTHORIZE_URL, $args );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$code = $_REQUEST['code'];

			$response_body = wp_remote_retrieve_body( $response );

			$auth = json_decode( $response_body );

			$this->_code = $auth->code;

		} catch ( \Exception $ex ) {

			error_log( $time . ' _get_authorization_code() Failed -- ' . $ex->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

	}

	private function _get_saved_token() {

		return get_option( $this->_access_token_key );

	}

	/**
	 * Authorise the request using the secret key and save the access token
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	private function _fetch_access_token() {

		$time = date( '[d/M/Y:H:i:s]' );

		if ( ! empty( $this->_access_token ) ) {

			error_log( $time . '##### valid token ###### ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return;

		}

		try {

			$params = array(
				'client_id'     => $this->_client_id,
				'client_secret' => $this->_client_secret,
				'grant_type'    => 'authorization_code',
				'code'          => $this->_code,
				'redirect_uri'  => $this->_redirect_uri,
			);

			$args = array(
				'method'  => 'POST',
				'timeout' => 500,
				'body'    => $params,
			);

			$response = wp_remote_post( Config::REQUEST_TOKEN_URL, $args );

			if ( is_wp_error( $response ) ) {

				error_log( $time . ' fetch_access_token() Failed -- ' . $response->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return $response;
			}

			$response_body = wp_remote_retrieve_body( $response );

			$auth = json_decode( $response_body );

			if ( empty( $auth->access_token ) ) {

				error_log( $time . ' fetch_access_token() Failed -- ' . $response_body . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return new \WP_Error( 'unauthorized_access',' fetch_access_token() Failed -- ' . $response_body );
			}

			$this->_access_token = $auth->access_token;

			update_option( $this->_access_token_key, $this->_access_token );

		} catch ( \Exception $ex ) {

			error_log( $time . ' fetch_access_token() Failed -- ' . $ex->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );
		}

	}

	/**
	 * Check if the access token is valid
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 */
	public function is_token_valid() {

		if ( empty( $this->_access_token ) ) {
			$this->_access_token = get_option( $this->_access_token_key );
		}

		if ( empty( $this->_access_token ) || empty( $this->_client_id ) ) {
			return false;
		}

		$time = date( '[d/M/Y:H:i:s]' );

		try {

			$params = array(
				'client_id' => $this->_client_id,
				'token'     => $this->_access_token,
			);

			$args = array(
				'method'  => 'POST',
				'timeout' => 500,
				'body'    => $params,
			);

			$valid_token = wp_remote_post( Config::VALIDATE_URL, $args );

			if ( is_wp_error( $valid_token ) ) {

				error_log( $time . ' is_token_valid() Failed -- ' . $valid_token->get_error_message() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

				return $valid_token;

			} else {

				$response_body = wp_remote_retrieve_body( $valid_token );

				$validate_response = json_decode( $response_body, true );

				return ( ! empty( $validate_response['scope'] ) );

			}
		} catch ( \Exception $ex ) {

			error_log( $time . ' is_token_valid() Failed -- ' . $ex->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

	}

	/**
	 * Access the API end point to pull data based on the route being passed
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 * @param string $route - the endpoint name that needs to be called
	 * array $query_params array of query arguments that needs to be passed
	 *
	 * @return array The json data returned from the API end point
	 *
	 */
	public function access_endpoint( $domain, $route, $query_params = array(), $route_name = '', $token_required = false ) {

		$time = date( '[d/M/Y:H:i:s]' );

		if ( empty( $route_name ) ) {

			$route_name = $route;

		}

		if ( empty( $domain ) && empty( $this->domain ) ) {

			error_log( $time . ' $$$$  No Domain set for route . -- ' . $route_name . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return new \WP_Error( 'unauthorized_access', '  No Domain Set. Please set a domain. ' );

		} else if ( empty( $domain ) && ! empty( $this->domain ) ) {

			$domain = $this->domain;

		}

		try {

			if ( ! empty( $domain ) ) {

				$options = $this->_get_required_header( $token_required );

				$query_params = $this->_get_query_params( $query_params );

				$api_url = trim( Config::REST_BASE_URL, '/' ) . '/' . $domain . '/' . trim( $route, '/' ) . '/?' . $query_params;

				$context = stream_context_create( $options );

				$response = file_get_contents(
					esc_url_raw( $api_url ),
					false,
					$context
				);

				if ( empty( $response ) ) {

					error_log( $time . $api_url . ' $$$$  No Data returned. Please Try again. -- ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

					return new \WP_Error( 'unauthorized_access', $api_url . '  No Data returned. Please Try again. ' );

				}

				$data = json_decode( $response, true );

				if ( $data['code'] != 200 ) {

					return new \WP_Error( 'unauthorized_access', $route_name . ' Failed with Exception - ' . $data['body']['message'] );
				}

				error_log( $time . $api_url . ' $$$$ Data fetched -- ' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE );

				return $data['body'][ $route_name ];
			}
		} catch ( \Exception $ex ) {

			error_log( $time . $api_url . ' Failed -- ' . $ex->getMessage() . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

		}

		return false;

	}

	/**
	 * Return the header information that needs to be passed to the API endpoint
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 * @return array The header data that needs to be passed to the API
	 *
	 */
	private function _get_required_header( $token_required ) {

		if ( $token_required ) {

			if ( empty( $this->_access_token ) ) {

				//@todo : fetching access token requires code param that I cannot get from server side and need to find ways to get it
				$options = array(
					'http' =>
						array(
							'ignore_errors' => true,
						),
				);

			} else {

				$options = array(
					'http' =>
						array(
							'ignore_errors' => true,
							'header'        =>
								array(
									0 => 'authorization: Bearer ' . $this->_access_token,
								),
						),
				);
			}
		} else {

			$options = array(
				'http' =>
					array(
						'ignore_errors' => true,
					),
			);

		}

		return $options;
	}

	/**
	 * Returns the query params the need to be passed to the API endpoint
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 * @param array $params array of query arguments that needs to be passed
	 *
	 * @return string The query string that should be passed to the API
	 *
	 */
	private function _get_query_params( $params = array() ) {

		$defaults = array( 'http_envelope' => 'true' );

		$query_params = wp_parse_args( $params, $defaults );

		return http_build_query( $query_params );

	}

	/**
	 * Authenticate the current user
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 */
	public function authenticate_me() {

		$options = $this->_get_required_header( true );

		$query_params = $this->_get_query_params();

		$api_url = trim( Config::REST_BASE_URL, '/' ) . '/me/?' . $query_params;

		$context = stream_context_create( $options );

		$response = file_get_contents(
			esc_url_raw( $api_url ),
			false,
			$context
		);

		$data = json_decode( $response, true );

		if ( $data['code'] != 200 ) {
			return new \WP_Error( 'unauthorized_access', $data['body']['message'] );
		}

		return $data['body'];

	}


}
