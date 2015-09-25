<?php
namespace PMC\Theme_Unit_Test;

class Admin extends PMC_Singleton {

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	protected function _init() {

		$this->_setup_hooks();

	}

	/**
	 * Setup Hooks required to create admin page
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 * @version 2015-07-30 Amit Gupta PPT-5077 - consolidated multiple 'init' listeners into one
	 */
	protected function _setup_hooks() {

		add_action( 'init', array( $this, 'on_wp_init' ) );

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

		add_action( 'wp_ajax_import_all_data_from_production', array( $this, 'import_all_data_from_production' ) );

		add_action( 'wp_ajax_import_posts_data_from_production', array( $this, 'import_posts_data_from_production' ) );

		add_action( 'wp_ajax_change_credentials', array( $this, 'change_credentials' ) );

		add_action( 'wp_ajax_import_xmlrpc_data_from_production', array( $this, 'import_xmlrpc_data_from_production' ) );

		add_action( 'wp_ajax_get_client_configuration_details', array( $this, 'get_client_configuration_details' ) );

	}

	/**
	 * Enqueue styles and scripts required for the admin page
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 * @param string $hook that helps determine which admin page it is
	 */
	public function load_assets( $hook ) {

		if ( 'tools_page_data-import' !== $hook ) {
			return;
		}

		wp_register_style( 'pmc_theme_unit_test_admin_css', plugins_url( 'pmc-theme-unit-test/assets/css/admin-ui.css', PMC_THEME_UNIT_TEST_ROOT ), false, PMC_THEME_UNIT_TEST_VERSION );

		wp_enqueue_style( 'pmc_theme_unit_test_admin_css' );

		wp_register_script( 'pmc_theme_unit_test_admin_js', plugins_url( 'pmc-theme-unit-test/assets/js/admin-ui.js', PMC_THEME_UNIT_TEST_ROOT ), array( 'jquery' ), PMC_THEME_UNIT_TEST_VERSION );

		wp_localize_script(
			'pmc_theme_unit_test_admin_js',
			'pmc_unit_test_ajax',
			array(
				'admin_url'           => admin_url( 'admin-ajax.php' ),
				'import_nOnce'        => wp_create_nonce( 'import-from-production' ),
				'import_xmlrpc_nOnce' => wp_create_nonce( 'import-xmlrpc-from-production' ),
				'import_posts_nOnce'  => wp_create_nonce( 'import-posts-from-production' ),
				'client_nOnce'        => wp_create_nonce( 'get-client-config-details' ),
				'change_nOnce'        => wp_create_nonce( 'change-credentials' ),
				'AUTHORIZE_URL'       => Config::AUTHORIZE_URL,
			)
		);

		wp_enqueue_script( 'pmc_theme_unit_test_admin_js' );

	}

	/**
	 * This function is called on 'init' and does the initialization stuff
	 *
	 * @since 2015-07-30 Amit Gupta PPT-5077
	 *
	 * @return void
	 */
	public function on_wp_init() {
		$this->register_post_types_for_import();
		$this->register_taxonomies_for_import();
		setcookie( 'oauth_redirect', get_admin_url() . 'tools.php?page=data-import', time() + 60 * 60 * 24 * 30, '/', Config::COOKIE_DOMAIN );
	}

	/**
	 * Register custom post tyes on init hook as per wordpress codex documentation
	 *
	 * @see https://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 * @since 2015-07-30
	 *
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 */
	public function register_post_types_for_import() {

		if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		$custom_post_types = apply_filters( 'pmc_custom_post_types_to_import', array() );

		$custom_post_types = apply_filters( 'rest_api_allowed_post_types', $custom_post_types );

		$custom_post_types = array_unique( $custom_post_types );

		if ( ! empty( $custom_post_types ) ) {

			foreach ( $custom_post_types as $post_type ) {

				Posts_Importer::get_instance()->save_post_type( $post_type );

			}
		}

	}

	/**
	 * Register taxonomies on init hook as per wordpress codex documentation
	 *
	 * @see https://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	 * @since 2015-07-30
	 *
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 */
	public function register_taxonomies_for_import() {

		if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$custom_taxonomies = apply_filters( 'pmc_custom_taxonomies_to_import', array() );

		if ( ! empty( $custom_taxonomies ) ) {

			foreach ( $custom_taxonomies as $key => $taxonomy ) {

				Taxonomies_Importer::get_instance()->save_taxonomy( $taxonomy );

			}
		}

	}


	/**
	 * Add Admin page to Menu in Dashboard
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	function add_admin_menu() {

		add_submenu_page( 'tools.php', 'Sync from Production', 'Sync from Production', 'manage_options', 'data-import', array( $this, 'data_import_options' ) );

	}

	/**
	 * Settings page for registering credentials
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	public function action_admin_init() {

		register_setting( 'pmc_domain_creds', 'pmc_domain_creds', array(
			$this,
			'pmc_domain_creds_sanitize_callback',
		) );

	}


	/**
	 * Callback function to setup the Admin UI
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	function data_import_options() {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );

		}

		$token_created = false;
		$code         = filter_input( INPUT_GET, 'code', FILTER_DEFAULT );
		if ( ! empty( $code ) ) {
			$token_created = REST_API_oAuth::get_instance()->fetch_access_token( $code );
		}

		$show_cred_form = false;
		$authorize_url  = '';
		$show_form      = get_option( Config::show_form );

		$saved_access_token = get_option( Config::access_token_key );
		$is_valid_token     = REST_API_oAuth::get_instance()->is_valid_token();

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
			'show_cred_form'   => $show_cred_form,
			'show_data_import' => ! $show_cred_form,
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
		echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/admin-ui.php', $args );
		// @codingStandardsIgnoreEnd

	}

	private function _get_auth_details() {

		$details_in_DB = true;
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
				$details_in_DB = false;
				break;
			}
		}

		if ( $details_in_DB ) {
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
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
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
			return REST_API_oAuth::get_instance()->get_authorization_code();
		} else {
			return true;
		}
	}

	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	public function import_all_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-from-production', 'import_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[] = REST_API_Router::get_instance()->call_rest_api_all_route( $route );
		}
		ob_clean();
		wp_send_json( $return_info );
		unset( $return_info );
		wp_die();

	}

	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	public function import_posts_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-posts-from-production', 'import_posts_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[] = REST_API_Router::get_instance()->call_rest_api_posts_route( $route );
		}
		ob_clean();
		wp_send_json( $return_info );
		unset( $return_info );
		wp_die();
	}


	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	public function import_xmlrpc_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-xmlrpc-from-production', 'import_xmlrpc_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[] = XMLRPC_Router::get_instance()->call_xmlrpc_api_route( $route );
		}
		ob_clean();
		wp_send_json( $return_info );
		unset( $return_info );
		wp_die();

	}

	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 */
	public function get_client_configuration_details() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		check_ajax_referer( 'get-client-config-details', 'client_nOnce' );

		$client_details['xmlrpc_routes'] = Config_Helper::get_xmlrpc_routes();

		$client_details['all_routes'] = Config_Helper::get_all_routes();

		$client_details['post_routes'] = Config_Helper::get_posts_routes();

		ob_clean();
		wp_send_json( $client_details );
		unset( $client_details );
		wp_die();

	}

	/**
	 * Ajax call made from the Admin UI to indicate change of credentials
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
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
}    //end class

//EOF
