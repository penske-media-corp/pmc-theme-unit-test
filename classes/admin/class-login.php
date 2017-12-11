<?php
namespace PMC\Theme_Unit_Test\Admin;

use PMC\Theme_Unit_Test\Traits\Singleton;
use \PMC\Theme_Unit_Test\Settings\Config;
use \PMC\Theme_Unit_Test\Settings\Config_Helper;
use \PMC\Theme_Unit_Test\REST_API\O_Auth;

class Login {

	use Singleton;

	protected $_credentials_args = [
		Config::api_domain,
		Config::api_client_id,
		Config::api_client_secret,
		Config::api_redirect_uri,
		Config::api_xmlrpc_username,
		Config::api_xmlrpc_password,
	];

	/**
	 * Add methods that need to run on class initialization
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	protected function _init() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks required to create admin page
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 * @version 2015-07-30 Amit Gupta PPT-5077 - consolidated multiple 'init' listeners into one
	 */
	protected function _setup_hooks() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add Admin page to Menu in Dashboard
	 * @since 2016-07-21
	 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
	 */
	function add_admin_menu() {
		add_menu_page( 'Import Production Content', 'Import Production Content', 'publish_posts', 'pmc_theme_unit_test', array( $this, 'import_options' ) );
		add_submenu_page( 'pmc_theme_unit_test', 'Login', 'Login', 'publish_posts', 'content-login', array( $this, 'login_options' ) );
	}

	/**
	 * Callback function for the Menu Page
	 * @since 2016-07-21
	 * @version 2016-07-21 Archana Mandhare PMCVIP-1950
	 */
	public function import_options() {

		$saved_access_token = get_option( Config::access_token_key );

		// If we have access token saved then show the import page else show the login form
		if ( ! empty( $saved_access_token ) ) {

			echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/import.php' );

			Import::get_instance()->form_submit();

		} else {

			$this->login_options();

		}
	}

	/**
	 * Callback function to setup the Admin UI
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	function login_options() {

		if ( ! current_user_can( 'publish_posts' ) ) {

			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );

		}

		$args = array();

		$auth_args = array(
			'response_type' => 'code',
			'scope'         => 'global',
		);

		$change_credentials = filter_input( INPUT_GET, 'change' );

		$saved_access_token = get_option( Config::access_token_key );

		$is_valid_token = O_Auth::get_instance()->is_valid_token();

		// get the credential details
		$creds_details = $this->_get_auth_details();

		if ( ( empty( $saved_access_token ) || ! $is_valid_token || ! empty( $change_credentials ) ) ) {

			if ( is_array( $creds_details ) && ! empty( $creds_details[ Config::api_client_id ] ) && ! empty( $creds_details[ Config::api_redirect_uri ] ) ) {

				$auth_args = array_merge( $auth_args, array(
					Config::api_client_id    => $creds_details[ Config::api_client_id ],
					Config::api_redirect_uri => $creds_details[ Config::api_redirect_uri ],
				) );

			}

			$query_params = http_build_query( $auth_args );

			$authorize_url = Config::AUTHORIZE_URL . '?' . $query_params;

			$args = array_merge( $args, array( 'authorize_url' => esc_url( $authorize_url ) ) );

		}

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
		echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/login.php', $args );
		// @codingStandardsIgnoreEnd

	}

	/**
	 * Get the authentication details for the current theme
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	private function _get_auth_details() {

		$details_in_db = true;

		//fetch details from DB
		$creds_details = get_option( Config::api_credentials );

		foreach ( $creds_details as $key => $value ) {

			if ( empty( $value ) ) {
				$details_in_db = false;
				break;
			}

		}

		if ( $details_in_db ) {
			return $creds_details;
		}

		$file_exists = file_exists( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' );

		if( ! $file_exists ) {
			// not file present
			return false;
		}

		$creds_details = $this->read_credentials_from_json_file( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' );

		// the array values are already sanitized
		if ( ! is_array( $creds_details ) ) {
			return false;
		}

		foreach( $this->_credentials_args as $key ) {
			if ( ! array_key_exists( $key, $creds_details ) || empty( $creds_details[ $key ] ) ) {
				$invalid_credentials = true;
				break;
			}
		}

		if( $invalid_credentials ) {
			return false;
		} else {
			return $creds_details;
		}

		return false;

	}

	/**
	 * Settings page for registering credentials
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	public function action_admin_init() {

		register_setting( 'pmc_domain_creds', 'pmc_domain_creds', array( $this, 'pmc_domain_creds_sanitize_callback' ) );
	}

	/**
	 * Admin UI credentials form post function
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function pmc_domain_creds_sanitize_callback() {

		$creds_details[ Config::api_domain ]          = filter_input( INPUT_POST, Config::api_domain );
		$creds_details[ Config::api_client_id ]       = filter_input( INPUT_POST, Config::api_client_id );
		$creds_details[ Config::api_client_secret ]   = filter_input( INPUT_POST, Config::api_client_secret );
		$creds_details[ Config::api_redirect_uri ]    = filter_input( INPUT_POST, Config::api_redirect_uri );
		$creds_details[ Config::api_xmlrpc_username ] = filter_input( INPUT_POST, Config::api_xmlrpc_username );
		$creds_details[ Config::api_xmlrpc_password ] = filter_input( INPUT_POST, Config::api_xmlrpc_password );

		$is_saved = $this->save_credentials_to_db( $creds_details );

		if ( $is_saved ) {

			wp_redirect( get_admin_url() . 'admin.php?page=pmc_theme_unit_test' );
			exit;

		}
	}

	/**
	 * Save the credentials to the database
	 *
	 * @since 2015-09-01
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function save_credentials_to_db( $creds_details = array(), $doing_cli = false ) {

		$call_api      = false;
		$creds_details = array_map( 'wp_unslash', $creds_details );
		$creds_details = array_map( 'sanitize_text_field', $creds_details );
		$access_token  = get_option( Config::access_token_key );

		if ( empty( $creds_details[ Config::api_domain ] )
		     || empty( $creds_details[ Config::api_client_id ] )
		     || empty( $creds_details[ Config::api_client_secret ] )
		     || empty( $creds_details[ Config::api_redirect_uri ] )
		) {
			return false;
		}

		if ( empty( $access_token ) ) {

			update_option( Config::api_credentials, $creds_details );

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
	 * @version 2015-09-02 Archana Mandhare - PPT-5366
	 *
	 * @param string File that has credentials
	 * @return array $creds_details that has all the required credentials to fetch access token
	 */
	public function read_credentials_from_json_file( $credentials_file ) {

		$contents    = file_get_contents( $credentials_file );
		$json        = json_decode( $contents, true );
		$rest_auth   = true;
		$xmlrpc_auth = true;

		foreach ( $json as $key => $value ) {

			$creds_details = array_map( 'wp_unslash', $value );
			$creds_details = array_map( 'sanitize_text_field', $creds_details );

			if ( 'rest-api' === $key ) {

				if ( empty( $creds_details[ Config::api_domain ] )
				     || empty( $creds_details[ Config::api_client_id ] )
				     || empty( $creds_details[ Config::api_client_secret ] )
				     || empty( $creds_details[ Config::api_redirect_uri ] ) ) {
					$rest_auth = false;
				}
			}

			if ( 'xmlrpc' === $key ) {

				if ( empty( $creds_details[ Config::api_xmlrpc_username ] ) || empty( $creds_details[ Config::api_xmlrpc_password ] ) ) {
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
