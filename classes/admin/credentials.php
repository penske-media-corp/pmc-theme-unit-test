<?php
namespace PMC\Theme_Unit_Test\Admin;

use \PMC\Theme_Unit_Test\PMC_Singleton as PMC_Singleton;
use \PMC\Theme_Unit_Test\Settings\Config as Config;
use \PMC\Theme_Unit_Test\Settings\Config_Helper as Config_Helper;
use \PMC\Theme_Unit_Test\REST_API\O_Auth as O_Auth;
use \PMC\Theme_Unit_Test\REST_API\Router as Router;


class Credentials extends PMC_Singleton {

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	protected function _init() {

		$this->_setup_hooks();

	}

	/**
	 * Setup Hooks required to create admin page
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 * @version 2015-07-30 Amit Gupta PPT-5077 - consolidated multiple 'init' listeners into one
	 */
	protected function _setup_hooks() {

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add Admin page to Menu in Dashboard
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	function add_admin_menu() {

		add_submenu_page( 'tools.php', 'Sync from Production', 'Sync from Production', 'manage_options', 'data-import', array( $this, 'data_import_options' ) );

	}

	/**
	 * Settings page for registering credentials
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	public function action_admin_init() {

		register_setting( 'pmc_domain_creds', 'pmc_domain_creds', array( $this, 'pmc_domain_creds_sanitize_callback' ) );

	}


	/**
	 * Callback function to setup the Admin UI
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	function data_import_options() {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );

		}

		$code         = filter_input( INPUT_GET, 'code', FILTER_DEFAULT );
		if ( ! empty( $code ) ) {
			$token_created = O_Auth::get_instance()->fetch_access_token( $code );
		}

		$show_cred_form = false;
		$authorize_url  = '';
		$show_form      = get_option( Config::show_form );

		$saved_access_token = get_option( Config::access_token_key );
		$is_valid_token     = O_Auth::get_instance()->is_valid_token();

		if ( 1 === intval( $show_form ) || ( empty( $saved_access_token ) || ! $is_valid_token ) ) {

			// get the credential details
			$creds_details = $this->_get_auth_details();

			if ( is_array( $creds_details ) && ! empty( $creds_details['client_id'] ) && ! empty( $creds_details['redirect_uri'] ) ) {
				$auth_args = array(
					'response_type' => 'code',
					'scope'         => 'global',
					'client_id'     => $creds_details['client_id'],
					'redirect_uri'  => $creds_details['redirect_uri'],
				);
			} else {
				$auth_args = array(
					'response_type' => 'code',
					'scope'         => 'global',
				);
			}

			$query_params   = http_build_query( $auth_args );
			$authorize_url  = Config::AUTHORIZE_URL . '?' . $query_params;
			$show_cred_form = true;
			update_option( Config::show_form, 0, false );

		}

		$args = array(
			'authorize_url'    => esc_url( $authorize_url ),
		);

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
		if($show_cred_form){
			echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/admin-credentials.php', $args );
		} else {
			echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/admin-import.php', $args );
		}
		// @codingStandardsIgnoreEnd

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

		$creds_details = array(
			'domain'          => get_option( Config::api_domain ),
			'client_id'       => get_option( Config::api_client_id ),
			'client_secret'   => get_option( Config::api_client_secret ),
			'redirect_uri'    => get_option( Config::api_redirect_uri ),
			'xmlrpc_username' => get_option( Config::api_xmlrpc_username ),
			'xmlrpc_password' => get_option( Config::api_xmlrpc_password ),
		);

		foreach ( $creds_details as $key => $value ) {
			if ( empty( $value ) ) {
				$details_in_db = false;
				break;
			}
		}

		if ( $details_in_db ) {
			return $creds_details;
		} else {
			// If details not in DB fetch from file.
			if ( file_exists( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' ) ) {

				$creds_details = $this->read_credentials_from_json_file( PMC_THEME_UNIT_TEST_ROOT . '/auth.json' );

				// the array values are already sanitized
				if ( is_array( $creds_details ) ) {
					$file_args = array(
						'domain'          => ! empty( $creds_details['domain'] ) ? $creds_details['domain'] : '',
						'client_id'       => ! empty( $creds_details['client_id'] ) ? $creds_details['client_id'] : '',
						'client_secret'   => ! empty( $creds_details['client_secret'] ) ? $creds_details['client_secret'] : '',
						'redirect_uri'    => ! empty( $creds_details['redirect_uri'] ) ? $creds_details['redirect_uri'] : '',
						'xmlrpc_username' => ! empty( $creds_details['xmlrpc_username'] ) ? $creds_details['xmlrpc_username'] : '',
						'xmlrpc_password' => ! empty( $creds_details['xmlrpc_password'] ) ? $creds_details['xmlrpc_password'] : '',
					);

					return $creds_details;
				} else {
					return false;
				}
			} else {
				// not file present
				return false;
			}
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

		$contents    = file_get_contents( $credentials_file );
		$json        = json_decode( $contents, true );
		$rest_auth   = true;
		$xmlrpc_auth = true;

		foreach ( $json as $key => $value ) {
			if ( 'rest-api' === $key ) {
				$domain        = sanitize_text_field( wp_unslash( $value['domain'] ) );
				$client_id     = sanitize_text_field( wp_unslash( $value['client_id'] ) );
				$client_secret = sanitize_text_field( wp_unslash( $value['client_secret'] ) );
				$redirect_uri  = sanitize_text_field( wp_unslash( $value['redirect_uri'] ) );
				if ( empty( $domain ) || empty( $client_id ) || empty( $client_secret ) || empty( $redirect_uri ) ) {
					$rest_auth = false;
				} else {
					$creds_details['domain']        = $domain;
					$creds_details['client_id']     = $client_id;
					$creds_details['client_secret'] = $client_secret;
					$creds_details['redirect_uri']  = $redirect_uri;
				}
			}
			if ( 'xmlrpc' === $key ) {
				$xmlrpc_username = sanitize_text_field( wp_unslash( $value['xmlrpc_username'] ) );
				$xmlrpc_password = sanitize_text_field( wp_unslash( $value['xmlrpc_password'] ) );

				if ( empty( $xmlrpc_username ) || empty( $xmlrpc_password ) ) {
					$xmlrpc_auth = true;
				} else {
					$creds_details['xmlrpc_username'] = $xmlrpc_username;
					$creds_details['xmlrpc_password'] = $xmlrpc_password;
				}
			}
		}

		if ( $rest_auth && $xmlrpc_auth ) {
			return $creds_details;
		} else {
			return false;
		}
	}

	/**
	 * Admin UI credentials form post function
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function pmc_domain_creds_sanitize_callback() {

		$creds_details['domain']          = filter_input( INPUT_POST, 'domain' );
		$creds_details['client_id']       = filter_input( INPUT_POST, 'client_id' );
		$creds_details['client_secret']   = filter_input( INPUT_POST, 'client_secret' );
		$creds_details['redirect_uri']    = filter_input( INPUT_POST, 'redirect_uri' );
		$creds_details['xmlrpc_username'] = filter_input( INPUT_POST, 'xmlrpc_username' );
		$creds_details['xmlrpc_password'] = filter_input( INPUT_POST, 'xmlrpc_password' );
		//$creds_details['code']            = filter_input( INPUT_POST, 'code' );

		$this->save_credentials_to_db( $creds_details );
	}


	/**
	 * Save the credentials to the database
	 *
	 * @since 2015-09-01
	 *
	 * @version 2015-09-01 Archana Mandhare - PPT-5366
	 */
	public function save_credentials_to_db( $creds_details = array(), $doing_cli = false ) {

		$creds_details = array_map( 'wp_unslash', $creds_details );
		$creds_details = array_map( 'sanitize_text_field', $creds_details );

		$saved_xmlrpc_username = get_option( Config::api_xmlrpc_username );
		if ( ! empty( $creds_details['xmlrpc_username'] ) && $saved_xmlrpc_username !== $creds_details['xmlrpc_username'] ) {
			update_option( Config::api_xmlrpc_username, $creds_details['xmlrpc_username'] );
		}

		$saved_xmlrpc_password = get_option( Config::api_xmlrpc_password );
		if ( ! empty( $creds_details['xmlrpc_password'] ) && $saved_xmlrpc_password !== $creds_details['xmlrpc_password'] ) {
			update_option( Config::api_xmlrpc_password, $creds_details['xmlrpc_password'] );
		}

		if ( empty( $creds_details['domain'] )
		     || empty( $creds_details['client_id'] )
		     || empty( $creds_details['client_secret'] )
		     || empty( $creds_details['redirect_uri'] )
		) {
			return false;
		}

		$fetch_token  = false;
		$saved_domain = get_option( Config::api_domain );
		if ( $saved_domain !== $creds_details['domain'] ) {
			update_option( Config::api_domain, $creds_details['domain'] );
			$fetch_token = true;
		}
		$saved_client_id = get_option( Config::api_client_id );
		if ( $saved_client_id !== $creds_details['client_id'] ) {
			update_option( Config::api_client_id, $creds_details['client_id'] );
			$fetch_token = true;
		}
		$saved_client_secret = get_option( Config::api_client_secret );
		if ( $saved_client_secret !== $creds_details['client_secret'] ) {
			update_option( Config::api_client_secret, $creds_details['client_secret'] );
			$fetch_token = true;
		}
		$saved_redirect_uri = get_option( Config::api_redirect_uri );
		if ( $saved_redirect_uri !== $creds_details['redirect_uri'] ) {
			update_option( Config::api_redirect_uri, $creds_details['redirect_uri'] );
			$fetch_token = true;
		}

		$access_token = get_option( Config::access_token_key );
		if ( empty( $access_token ) ) {
			$fetch_token = true;
		}

		if ( $fetch_token && ! $doing_cli ) {
			return O_Auth::get_instance()->get_authorization_code();
		} else {
			return true;
		}
	}

	/**
	 * Ajax call made from the Admin UI to indicate change of credentials
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	public function change_credentials() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		check_ajax_referer( 'change-credentials', 'change_nOnce' );

		update_option( Config::show_form, 1, false );

		ob_clean();
		wp_send_json( array( 'success' => 1 ) );
		wp_die();

	}

	/**
	 * Import data specified by the theme that should be pulled on every load
	 * For example things that are required to setup the home page
	 *
	 * @since 2015-11-28
	 *
	 * @version 2015-11-28 Archana Mandhare - PMCVIP-177
	 *
	 * @param array $import_data the ids imported previously
	 * @param array $route post_type for which meta data posts should be imported
	 *
	 * @return array
	 *
	 */
	public function import_theme_specific_posts( $route, $import_data ) {

		// The theme should implement this filter to tell this plugin
		// what it needs to pull from live to set itself up. This filter should return the list of post ids that needs to be pulled
		$post_ids = apply_filters( 'pmc_theme_unit_test_get_required_post_ids_for_post_types', array(), $route );

		if ( empty( $post_ids ) ) {
			return $import_data;
		}

		$imported_data[] = Router::get_instance()->call_rest_api_single_posts( $post_ids );

		$import_data = array_merge( $import_data, $imported_data );

		return $import_data;

	}
}    //end class

//EOF
