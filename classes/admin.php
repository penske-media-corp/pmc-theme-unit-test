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

		add_action(
			'wp_ajax_import_xmlrpc_data_from_production', array(
				$this,
				'import_xmlrpc_data_from_production',
			)
		);

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
			'pmc_theme_unit_test_admin_js', 'pmc_unit_test_ajax', array(
				'admin_url'           => admin_url( 'admin-ajax.php' ),
				'import_nOnce'        => wp_create_nonce( 'import-from-production' ),
				'import_xmlrpc_nOnce' => wp_create_nonce( 'import-xmlrpc-from-production' ),
				'import_posts_nOnce'  => wp_create_nonce( 'import-posts-from-production' ),
				'client_nOnce'        => wp_create_nonce( 'get-client-config-details' ),
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

		add_submenu_page( 'tools.php', 'Sync from Production', 'Sync from Production', 'manage_options', 'data-import', array(
				$this,
				'data_import_options',
			)
		);

	}


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
		$show_cred_form     = false;
		$authorize_url      = '';
		$saved_access_token = get_option( Config::access_token_key );
		$is_valid_token     = REST_API_oAuth::get_instance()->is_valid_token();

		if ( empty( $saved_access_token ) || ! $is_valid_token ) {
			$args = array(
				'response_type' => 'code',
				'scope'         => 'global',
			);

			$query_params   = http_build_query( $args );
			$authorize_url  = Config::AUTHORIZE_URL . '?' . $query_params;
			$show_cred_form = true;
		}

		$args = array(
			'show_cred_form'   => $show_cred_form,
			'show_data_import' => ! $show_cred_form,
			'authorize_url'    => esc_url( $authorize_url ),
		);

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

	public function pmc_domain_creds_sanitize_callback() {

		// sanitize domain name
		$domain = filter_input( INPUT_POST, 'domain' );
		$domain = sanitize_text_field( wp_unslash( $domain ) );

		if ( ! empty( $domain ) ) {
			update_option( Config::api_domain, $domain );
		}

		// sanitize client id
		$client_id = filter_input( INPUT_POST, 'client_id' );
		$client_id = sanitize_text_field( wp_unslash( $client_id ) );

		if ( ! empty( $client_id ) ) {
			update_option( Config::api_client_id, $client_id );
		}

		// sanitize client secret
		$client_secret = filter_input( INPUT_POST, 'client_secret' );
		$client_secret = sanitize_text_field( wp_unslash( $client_secret ) );

		if ( ! empty( $client_secret ) ) {
			update_option( Config::api_client_secret, $client_secret );
		}

		// sanitize redirect uri
		$redirect_uri = filter_input( INPUT_POST, 'redirect_uri' );
		$redirect_uri = sanitize_text_field( wp_unslash( $redirect_uri ) );

		if ( ! empty( $redirect_uri ) ) {
			update_option( Config::api_redirect_uri, $redirect_uri );
		}

		// sanitize xmlrpc_username
		$xmlrpc_username = filter_input( INPUT_POST, 'xmlrpc_username' );
		$xmlrpc_username = sanitize_text_field( wp_unslash( $xmlrpc_username ) );

		if ( ! empty( $xmlrpc_username ) ) {
			update_option( Config::api_xmlrpc_username, $xmlrpc_username );
		}

		// sanitize xmlrpc_password
		$xmlrpc_password = filter_input( INPUT_POST, 'xmlrpc_password' );
		$xmlrpc_password = sanitize_text_field( wp_unslash( $xmlrpc_password ) );

		if ( ! empty( $xmlrpc_password ) ) {
			update_option( Config::api_xmlrpc_password, $xmlrpc_password );
		}

		// sanitize code
		$code = filter_input( INPUT_POST, 'code' );
		$code = sanitize_text_field( wp_unslash( $code ) );

		if ( ! empty( $client_id ) && ! empty( $client_secret ) && ! empty( $redirect_uri ) && ! empty( $code ) ) {
			$token_saved = REST_API_oAuth::get_instance()->fetch_access_token( $code );
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

		wp_send_json( $return_info );
		exit();

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

		wp_send_json( $return_info );
		exit();
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

		wp_send_json( $return_info );
		exit();

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

		wp_send_json( $client_details );
		exit();

	}
}    //end class

//EOF
