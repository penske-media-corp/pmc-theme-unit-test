<?php

namespace PMC\Theme_Unit_Test\Admin;

use PMC\Theme_Unit_Test\Traits\Singleton;
use \PMC\Theme_Unit_Test\Settings\Config;
use \PMC\Theme_Unit_Test\Settings\Config_Helper;
use \PMC\Theme_Unit_Test\REST_API\O_Auth;

class Login {

	use Singleton;

	protected $_credentials_args = [
		Config::API_DOMAIN,
		Config::API_CLIENT_ID,
		Config::API_CLIENT_SECRET,
		Config::API_REDIRECT_URI,
		Config::API_XMLRPC_USERNAME,
		Config::API_XMLRPC_PASSWORD,
	];

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	protected function __construct() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks required to create admin page
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 * @version 2015-07-30 Amit Gupta PPT-5077 - consolidated multiple 'init' listeners into one
	 */
	protected function _setup_hooks() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_filter( 'https_local_ssl_verify', '__return_false' );
		add_filter( 'block_local_requests', '__return_false' );
	}

	/**
	 * Add Admin page to Menu in Dashboard
	 *
	 * @since 2016-07-21
	 *
	 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
	 */
	function add_admin_menu() {
		add_menu_page( 'Import Production Data', 'Import Production Data', 'publish_posts', 'pmc_theme_unit_test', [
			$this,
			'import_options'
		] );
		add_submenu_page( 'pmc_theme_unit_test', 'Login', 'Login', 'publish_posts', 'content-login', [
			$this,
			'login_options'
		] );
	}

	/**
	 * Callback function for the Menu Page
	 *
	 * @since 2016-07-21
	 *
	 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
	 */
	public function import_options() {

		$saved_access_token = get_option( Config::ACCESS_TOKEN_KEY );

		// If we have access token saved then show the import page else show the login form
		if ( ! empty( $saved_access_token ) ) {

			Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/import.php', [], true );

			Import::get_instance()->form_submit();

		} else {

			$this->login_options();

		}
	}

	/**
	 * Callback function to setup the Admin UI
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	function login_options() {

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$args      = array();
		$show_form = get_option( Config::SHOW_FORM );
		$code      = filter_input( INPUT_GET, 'code', FILTER_DEFAULT );

		if ( ! empty( $code ) ) {
			$token_created = O_Auth::get_instance()->fetch_access_token( $code );
		}

		$auth_args = array(
			'response_type' => 'code',
			'scope'         => 'global',
		);

		$change_credentials = filter_input( INPUT_GET, 'change' );
		$saved_access_token = get_option( Config::ACCESS_TOKEN_KEY );
		$is_valid_token     = O_Auth::get_instance()->is_valid_token();

		if ( 1 === intval( $show_form ) || ( empty( $saved_access_token ) || ! $is_valid_token || ! empty( $change_credentials ) ) ) {

			// get the credential details
			$creds_details = $this->_get_auth_details();

			if ( is_array( $creds_details ) && ! empty( $creds_details[ Config::API_CLIENT_ID ] ) && ! empty( $creds_details[ Config::API_REDIRECT_URI ] ) ) {

				$auth_args = array_merge( $auth_args, array(
					Config::api_client_id    => $creds_details[ Config::API_CLIENT_ID ],
					Config::API_REDIRECT_URI => $creds_details[ Config::API_REDIRECT_URI ],
				) );

			}

			$query_params = http_build_query( $auth_args );

			$authorize_url = Config::AUTHORIZE_URL . '?' . $query_params;

			$args = array_merge( $args, array( 'authorize_url' => esc_url( $authorize_url ) ) );

			if ( ! empty( $creds_details ) && is_array( $creds_details ) ) {

				$args = array_merge( $args, $creds_details );

			}

			/*
			 * Do not remove the below comments @codingStandardsIgnoreStart and @codingStandardsIgnoreEnd
			 * since in Travis build it fails giving error :
			 *        Expected next thing to be an escaping function (see Codex for 'Data Validation'), not ')'
			 * while I have already escaped the $args array above.
			 */
			// @codingStandardsIgnoreStart
			Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/login.php', $args, true );
			// @codingStandardsIgnoreEnd
		} else {

			Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/import.php', [], true );

		}

	}

	/**
	 * Get the authentication details for the current theme
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	private function _get_auth_details() {

		$details_in_db = true;

		//fetch details from DB
		$creds_details = get_option( Config::API_CREDENTIALS );

		if ( ! empty( $creds_details ) ) {
			foreach ( $creds_details as $key => $value ) {
				if ( empty( $value ) ) {
					$details_in_db = false;
					break;
				}
			}

			if ( $details_in_db ) {
				return $creds_details;
			}
		}

		// Is there any filter that has the credentials.
		$credentials = apply_filters( 'pmc_theme_unit_test_default_credentials', false );
		if ( ! empty( $credentials ) ) {
			$creds_details[ Config::API_DOMAIN ]        = $credentials['domain'];
			$creds_details[ Config::API_CLIENT_ID ]     = $credentials['client_id'];
			$creds_details[ Config::API_CLIENT_SECRET ] = $credentials['client_secret'];
			$creds_details[ Config::API_REDIRECT_URI ]  = $credentials['redirect_uri'];

			return $creds_details;
		}

		// Fetch the credentails from a file.
		$file_exists = file_exists( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' );

		if ( ! $file_exists ) {
			// not file present
			return false;
		}

		$creds_details = $this->read_credentials_from_json_file( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' );

		// the array values are already sanitized
		if ( empty( $creds_details ) || ! is_array( $creds_details ) ) {
			return false;
		}

		foreach ( $this->_credentials_args as $key ) {
			if ( ! array_key_exists( $key, $creds_details ) || empty( $creds_details[ $key ] ) ) {
				$invalid_credentials = true;
				break;
			}
		}

		if ( $invalid_credentials ) {
			return false;
		} else {
			return $creds_details;
		}

	}

	/**
	 * Settings page for registering credentials
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	public function action_admin_init() {

		register_setting( 'pmc_domain_creds', 'pmc_domain_creds', array(
			$this,
			'pmc_domain_creds_sanitize_callback'
		) );
	}

	/**
	 * Admin UI credentials form post function
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function pmc_domain_creds_sanitize_callback() {

		$creds_details[ Config::API_DOMAIN ]          = filter_input( INPUT_POST, Config::API_DOMAIN );
		$creds_details[ Config::API_CLIENT_ID ]       = filter_input( INPUT_POST, Config::API_CLIENT_ID );
		$creds_details[ Config::API_CLIENT_SECRET ]   = filter_input( INPUT_POST, Config::API_CLIENT_SECRET );
		$creds_details[ Config::API_REDIRECT_URI ]    = filter_input( INPUT_POST, Config::API_REDIRECT_URI );
		$creds_details[ Config::API_XMLRPC_USERNAME ] = filter_input( INPUT_POST, Config::API_XMLRPC_USERNAME );
		$creds_details[ Config::API_XMLRPC_PASSWORD ] = filter_input( INPUT_POST, Config::API_XMLRPC_PASSWORD );

		$is_saved = $this->save_credentials_to_db( $creds_details );

		if ( $is_saved ) {
			wp_safe_redirect( get_admin_url() . 'admin.php?page=pmc_theme_unit_test' );
			exit;
		}
	}

	/**
	 * Save the credentials to the database
	 *
	 * @since 2015-09-01
	 *
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function save_credentials_to_db( $creds_details = array(), $doing_cli = false ) {

		$call_api      = false;
		$creds_details = array_map( 'wp_unslash', (array) $creds_details );
		$creds_details = array_map( 'sanitize_text_field', (array) $creds_details );
		$access_token  = get_option( Config::ACCESS_TOKEN_KEY );
		$domain        = get_option( Config::API_DOMAIN );

		if ( empty( $creds_details[ Config::API_DOMAIN ] )
		|| empty( $creds_details[ Config::API_CLIENT_ID ] )
		|| empty( $creds_details[ Config::API_CLIENT_SECRET ] )
		|| empty( $creds_details[ Config::API_REDIRECT_URI ] ) ) {
			return false;
		}

		if ( empty( $access_token ) || empty( $domain ) ) {

			if ( ! empty( $creds_details ) && is_array( $creds_details ) ) {
				foreach ( $creds_details as $key => $value ) {
					update_option( $key, $value );
				}
			}

			update_option( Config::API_CREDENTIALS, $creds_details );
			$call_api = true;
		}

		if ( $call_api && ! $doing_cli ) {
			return O_Auth::get_instance()->get_authorization_code();
		} else {
			return true;
		}
	}

	/**
	 * Read credentials form file and return array
	 *
	 * @since 2015-09-02
	 *
	 * @version 2015-09-02 Archana Mandhare - PPT-5366
	 *
	 * @param string File that has credentials
	 *
	 * @return array $creds_details that has all the required credentials to fetch access token
	 */
	public function read_credentials_from_json_file( $credentials_file ) {

		$contents    = wpcom_vip_file_get_contents( $credentials_file );
		$json        = json_decode( $contents, true );
		$rest_auth   = true;
		$xmlrpc_auth = true;

		foreach ( $json as $key => $value ) {

			$creds_details = array_map( 'wp_unslash', (array) $value );
			$creds_details = array_map( 'sanitize_text_field', (array) $creds_details );

			if ( 'rest-api' === $key ) {

				if ( empty( $creds_details[ Config::API_DOMAIN ] )
				|| empty( $creds_details[ Config::API_CLIENT_ID ] )
				|| empty( $creds_details[ Config::API_CLIENT_SECRET ] )
				|| empty( $creds_details[ Config::API_REDIRECT_URI ] ) ) {
					$rest_auth = false;
				}
			}

			if ( 'xmlrpc' === $key ) {

				if ( empty( $creds_details[ Config::API_XMLRPC_USERNAME ] ) || empty( $creds_details[ Config::API_XMLRPC_PASSWORD ] ) ) {
					$xmlrpc_auth = false;
				}
			}
		}

		if ( $rest_auth && $xmlrpc_auth ) {
			return $creds_details;
		} else {
			return false;
		}
	}

} //end class

//EOF
