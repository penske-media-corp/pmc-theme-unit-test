<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Config_Helper extends \PMC_Singleton {

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21, for PPT-5077, Archana Mandhare
	 * @todo - Add functions and params that are required at _init
	 */
	public function _init() {

		$this->_setup_hooks();
	}

	protected function _setup_hooks() {

		add_filter( 'pmc_theme_ut_xmlrpc_client_auth', array(
			$this,
			'filter_pmc_theme_ut_xmlrpc_client_auth'
		), 10, 1 );

		add_filter( 'pmc_theme_ut_domains', array( $this, 'filter_pmc_theme_ut_domains' ) );

		add_filter( 'pmc_theme_ut_domain_routes', array( $this, 'filter_pmc_theme_ut_domain_routes' ) );

		add_filter( 'pmc_theme_ut_xmlrpc_routes', array( $this, 'filter_pmc_theme_ut_xmlrpc_routes' ) );

		add_filter( 'pmc_theme_ut_endpoints_config', array( $this, 'filter_pmc_theme_ut_endpoints_config' ), 10, 2 );

		add_filter( 'pmc_theme_ut_posts_routes', array( $this, 'filter_pmc_theme_ut_posts_routes' ) );

	}

	/**
	 * The xmlrpc client credentials to get access to the server
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22, for PPT-5077, Archana Mandhare
	 *
	 * @param array $domain The server to pull data from
	 *
	 * @return array $xmlrpc_args The array containing the credentials
	 *
	 *
	 */
	public function filter_pmc_theme_ut_xmlrpc_client_auth( $xmlrpc_args, $domain = '' ) {

		if ( ! empty( $domain ) ) {

			$xmlrpc_args = array(
				'server' => "http://{ $domain }/xmlrpc.php",
				'username' => Config::$xmlrpc_auth[ $domain ]["username"],
				'password' => Config::$xmlrpc_auth[ $domain ]["password"],
			);

		}

		return $xmlrpc_args;
	}

	/**
	 * The list of domains we can pull data from
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22, for PPT-5077, Archana Mandhare
	 *
	 * @param array $domain_list The array containing the domain details
	 *
	 * @return array $domain_list The array containing the domain details
	 *
	 *
	 */
	public function filter_pmc_theme_ut_domains( $domain_list ) {

		$domain_config = Config::$rest_api_auth;

		foreach ( $domain_config as $key => $value ) {
			$domain_list[] = $key;
		}

		return array_unique( $domain_list );
	}

	/**
	 * Return the oAuth client details for the domain that is being passed.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-22, for PPT-5077, Archana Mandhare
	 *
	 * @param array $client_configuration The array containing the client details
	 *        array $args contains the Domain that is required to indentify the client and get its details
	 *
	 * @return array $client_configuration The array containing the client details
	 *
	 *
	 */
	public function filter_pmc_theme_ut_endpoints_config( $client_configuration, $args ) {

		if ( ! empty( $args['domain'] ) && ! empty( Config::$rest_api_auth ) ) {

			$domain = $args['domain'];

			$client_auth = Config::$rest_api_auth;

			$client_configuration['client_id'] = $client_auth[ $domain ]['client_id'];
			$client_configuration['redirect_uri'] = $client_auth[ $domain ]['redirect_uri'];

		}

		return $client_configuration;
	}

	public function filter_pmc_theme_ut_domain_routes( $domain_routes ) {

		if ( ! empty( Config::$all_routes ) ) {
			$domain_routes = Config::$all_routes;
		}

		return $domain_routes;

	}

	public function filter_pmc_theme_ut_xmlrpc_routes( $xmlrpc_routes ) {

		if ( ! empty( Config::$xmlrpc_routes ) ) {
			$xmlrpc_routes = Config::$xmlrpc_routes;
		}

		return $xmlrpc_routes;

	}


	public function filter_pmc_theme_ut_posts_routes( $posts_routes = array() ) {

		// Fetch the posts and the custom post types.
		$allowed_types = array( 'page', 'post' );

		$allowed_custom_types = apply_filters( 'rest_api_allowed_post_types', $allowed_types );

		$route_post_types = array_unique( $allowed_custom_types );

		foreach ( $route_post_types as $route_post_type ) {
			$post_type = array(
				'posts' => array(
					"access_token" => false,
					"query_params" => array(
						"type" => $route_post_type,
					)
				)
			);

			$posts_routes[] = $post_type;
		}

		return $posts_routes;

	}

	public static function get_domains() {

		$domain_config = Config::$rest_api_auth;

		foreach ( $domain_config as $key => $value ) {
			$domain_list[] = $key;
		}

		return $domain_list;
	}

	/**
	 * Return the oAuth client details for the domain that is being passed.
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14, for PPT-5077, Archana Mandhare
	 *
	 * @param array $args contains the Domain that is required to indentify the client and get its details
	 *
	 * @return array The array containing the client details
	 *
	 *
	 */
	public static function get_client_config_details( $args ) {

		$domain = $args['domain'];

		$client_auth = Config::$rest_api_auth;

		$client_configuration["config_oauth"] = $client_auth[ $domain ];

		return $client_configuration;

	}

	/**
	 * Return the endpoint routes that need to be accessed with this API
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-14, for PPT-5077, Archana Mandhare
	 *
	 */
	public static function get_client_all_routes() {

		return Config::$all_routes;

	}

	/**
	 * Return the endpoint routes for xmlrpc that need to be accessed with this API
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21, for PPT-5077, Archana Mandhare
	 *
	 */
	public static function get_xmlrpc_routes() {

		return Config::$xmlrpc_routes;

	}

	/**
	 * Return the username for xmlrpc credentials
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21, for PPT-5077, Archana Mandhare
	 *
	 */
	public static function get_xmlrpc_username( $domain ) {

		$username_auth = Config::$xmlrpc_auth;
		$username      = $username_auth[ $domain ]["username"];

		return $username;

	}

	/**
	 * Return the password for xmlrpc credentials
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-21, for PPT-5077, Archana Mandhare
	 *
	 */
	public static function get_xmlrpc_password( $domain ) {

		$password_auth = Config::$xmlrpc_auth;
		$password      = $password_auth[ $domain ]["password"];

		return $password;

	}
}