<?php
namespace PMC\Theme_Unit_Test\Rest_API;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Settings\Config;
use PMC\Theme_Unit_Test\Logger\Status;

class O_Auth {

	use Singleton;

	/**
	 * Authorise the request using the secret key and save the access token
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 * @param $code string
	 *
	 * @return bool
	 */
	public function fetch_access_token( $code ) {

		$status = Status::get_instance();

		$api_credentials     = get_option( Config::api_credentials );
		$client_id = $api_credentials[Config::api_client_id ];
		$client_secret = $api_credentials[Config::api_client_secret ];
		$redirect_uri = $api_credentials[Config::api_redirect_uri ];

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $redirect_uri ) || empty( $code ) ) {
			$status->log_to_file( 'Admin Settings form input date not saved. Please try saving the credentials again.' );

			return false;
		}

		try {
			$params   = array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => $redirect_uri,
			);

			$args     = array(
				'timeout' => 500,
				'body'    => $params,
			);

			$response = wp_remote_post( esc_url_raw( Config::REQUEST_TOKEN_URL ), $args );

			if ( is_wp_error( $response ) ) {
				$status->log_to_file( ' fetch_access_token() Failed -- ' . $response->get_error_message() );

				return false;
			}

			$response_body = wp_remote_retrieve_body( $response );

			$auth          = json_decode( $response_body );

			if ( empty( $auth->access_token ) ) {

				$status->log_to_file( ' fetch_access_token() Failed -- ' . $response_body );

				return false;
			}

			update_option( Config::access_token_key, $auth->access_token );

			return true;

		} catch ( \Exception $ex ) {
			$status->log_to_file( ' fetch_access_token() Failed -- ' . $ex->getMessage() );

			return false;
		}
	}

	/**
	 * Authorise the request using the secret key and save the access token
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 */
	public function get_authorization_code() {

		$status       = Status::get_instance();

		$api_credentials = get_option( Config::api_credentials );
		$client_id       = $api_credentials[ Config::api_client_id ];
		$redirect_uri    = $api_credentials[ Config::api_redirect_uri ];

		if ( empty( $client_id ) || empty( $redirect_uri ) ) {
			$status->log_to_file( ' Admin Settings Form values not saved. Please try saving the credentials again. ' );

			return false;
		}
		try {
			$args          = array(
				'response_type' => 'code',
				'scope'         => 'global',
				'client_id'     => $client_id,
				'redirect_uri'  => $redirect_uri,
			);
			$query_param   = http_build_query( $args );
			$authorize_url = Config::AUTHORIZE_URL . '?' . $query_param;
			wp_redirect( esc_url_raw( $authorize_url ) );
			exit;
		} catch ( \Exception $ex ) {
			$status->log_to_file( ' fetch_access_token() Failed -- ' . $ex->getMessage() );

			return false;
		}
	}

	/**
	 * Access the API end point to pull data based on the route being passed
	 * Always access the endpoint with request header
	 * containing Authorization Bearer access token
	 *
	 * @since 2015-07-14
	 * @version 2015-07-14 Archana Mandhare PPT-5077
	 * @version 2015-11-30 Archana Mandhare - PMCVIP-177
	 *
	 * @param string $route - the endpoint name that needs to be called
	 * @param array $query_params array of query arguments that needs to be passed
	 * @param string $route_name
	 *
	 * @return array The json data returned from the API end point
	 */
	public function access_endpoint( $route, $query_params = array(), $route_name = '' ) {

		$status = Status::get_instance();
		$domain = get_option( Config::api_domain );

		if ( empty( $domain ) ) {
			$status->log_to_file( ' Domain is not set. Please try saving it from the settings form . -- ' . $route_name );
			return new \WP_Error( 'unauthorized_access', 'No Domain set. Please Try again. --' );
		}

		$route_name         = ! empty( $route_name ) ? $route_name : $route;
		$saved_access_token = get_option( Config::access_token_key );

		if ( empty( $saved_access_token ) ) {
			$status->log_to_file( ' ERROR --  No saved access token. Access denied . -- ' . $route_name );
			return new \WP_Error( 'unauthorized_access', '  No access token. Please get access token. ' );
		}

		try {

			$headers = $this->_get_required_header();

			if ( ! empty( $query_params['post_id'] ) ) {
				$post_id = $query_params['post_id'];
				unset( $query_params['post_id'] );
			}

			$query_params = $this->_get_query_params( $query_params );
			$api_url      = $this->_get_api_url( $domain, $route, $query_params, $post_id );

			/**
			 * Do not remove the below comments @codingStandardsIgnoreStart and @codingStandardsIgnoreEnd
			 * Recommended function is vip_safe_wp_remote_get() but since it has a max timeout of 3 secs which
			 * is not feasible since the response time is way ahead 3 secs here and I am unable to fetch data
			 * if I use vip_safe_wp_remote_get()
			 */
			// @codingStandardsIgnoreStart
			$response = wp_remote_get( esc_url_raw( $api_url ), $headers );
			// @codingStandardsIgnoreEnd

			if ( empty( $response ) ) {
				$status->log_to_file( $api_url . ' $$$$  No Data returned. Please Try again. -- ' );
				return new \WP_Error( 'unauthorized_access', $api_url . '$$$$  No Data returned. Please Try again. --' );
			}

			$response = wp_remote_retrieve_body( $response );
			$data     = json_decode( $response, true );

			if ( empty( $data ) ){
				$status->log_to_file( 'No Data returned ##### unauthorized_access for route ###### ' . $route_name . ' and api url = ' . $api_url );
				return new \WP_Error( 'unauthorized_access', $route_name . ' Failed with Exception - ' . $data['body']['message'] );
			} else if ( 200 !== $data['code'] ) {
				$status->log_to_file( 'No Data returned ##### unauthorized_access for route ###### ' . $route_name . json_encode( $data ) . ' and api url = ' . $api_url );
				return new \WP_Error( 'unauthorized_access', $route_name . ' Failed with Exception - ' . $data['body']['message'] );
			}

			if ( array_key_exists( $route_name, $data['body'] ) ) {
				return $data['body'][ $route_name ];
			} else {
				$return_val = array( 0 => $data['body'] );
				return $return_val;
			}

		} catch ( \Exception $ex ) {
			$status->log_to_file( 'API route Failed -- ' . $ex->getMessage() );
		}

		return false;
	}

	/**
	 * get the URL of the API based on the query params
	 *
	 * @since 2015-11-30
	 * @version 2015-11-30 Archana Mandhare - PMCVIP-177
	 *
	 * @param $domain string
	 * @param $route string
	 * @param $query_params string
	 * @param $post_id string
	 *
	 * @return string
	 */
	private function _get_api_url( $domain, $route, $query_params, $post_id = '' ) {

		if ( ! empty( $post_id ) && 'posts' === $route ) {
			return trim( Config::REST_BASE_URL, '/' ) . '/' . $domain . '/' . trim( $route, '/' ) . '/' . $post_id . '/?' . $query_params;
		} else {
			return trim( Config::REST_BASE_URL, '/' ) . '/' . $domain . '/' . trim( $route, '/' ) . '/?' . $query_params;
		}
	}

	/**
	 * Return the header information that needs to be passed to the API endpoint
	 *
	 * @since 2015-07-14
	 * @version 2015-07-14 Archana Mandhare PPT-5077
	 *
	 * @return array The header data that needs to be passed to the API
	 */
	private function _get_required_header() {

		$args               = array(
			'timeout' => 500,
		);
		$saved_access_token = get_option( Config::access_token_key );
		if ( ! empty( $saved_access_token ) ) {
			$args = array(
				'timeout' => 500,
				'headers' => array(
					'authorization' => 'Bearer ' . $saved_access_token,
				),
			);
		}

		return $args;
	}

	/**
	 * Returns the query params the need to be passed to the API endpoint
	 *
	 * @since 2015-07-14
	 * @version 2015-07-14 Archana Mandhare PPT-5077
	 *
	 * @param array $params array of query arguments that needs to be passed
	 *
	 * @return string The query string that should be passed to the API
	 *
	 */
	private function _get_query_params( $params = array() ) {
		$defaults     = array( 'http_envelope' => 'true' );
		$query_params = wp_parse_args( $params, $defaults );

		return http_build_query( $query_params );
	}

	/**
	 * Returns if the saved token is valid or not
	 *
	 * @since 2015-08-14
	 * @version 2015-08-14 Archana Mandhare PPT-5077
	 *
	 * @param $count int
	 *
	 * @return bool - true if token is valid else false
	 */
	public function is_valid_token( $count = 1 ) {

		$status = Status::get_instance();

		$api_credentials =  get_option( Config::api_credentials );
		$client_id    = $api_credentials[ Config::api_client_id ];
		$access_token = $api_credentials[ Config::access_token_key ];

		if ( empty( $client_id ) || empty( $access_token ) ) {
			return false;
		}

		$query  = array(
			'client_id' => (string) $client_id,
			'token'     => $access_token,
		);

		$params = http_build_query( $query );

		$args   = array(
			'timeout' => 500,
		);

		/**
		 * Do not remove the below comments @codingStandardsIgnoreStart and @codingStandardsIgnoreEnd
		 * Recommended function is vip_safe_wp_remote_get() but since it has a max timeout of 3 secs which
		 * is not feasible since the response time is way ahead 3 secs here and I am unable to fetch data
		 * if I use vip_safe_wp_remote_get()
		 */
		// @codingStandardsIgnoreStart
		$response = wp_remote_get( esc_url_raw( Config::VALIDATE_TOKEN_URL ) . '?' . $params, $args );
		// @codingStandardsIgnoreEnd

		if ( is_wp_error( $response ) ) {
			$status->log_to_file( 'Failed to validate token giving error ' . $response->get_error_message() );
			$count ++;
			if ( $count <= 3 ) {
				$this->is_valid_token( $count );
			}

			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		if ( ! empty( $response_body ) ) {
			$token_info = json_decode( $response_body, true );
			if ( ! empty( $token_info['client_id'] ) && $client_id === $token_info['client_id'] ) {
				return true;
			}
		}

		return false;
	}
}
