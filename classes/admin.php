<?php
namespace PMC\Theme_Unit_Test;

use \PMC;
use \PMC_Singleton;

class Admin extends PMC_Singleton {

	/**
	 * Add domains for which you want to pull data from
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-17 Archana Mandhare - PPT-5077
	 *
	 */
	private $_domains;

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	protected function _init() {

		$this->_setup_hooks();

	}

	/**
	 * Setup Hooks required to create admin page
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 * @version 2015-07-30 Amit Gupta - consolidated multiple 'init' listeners into one
	 *
	 */
	protected function _setup_hooks() {

		add_action( 'init',  array( $this, 'on_wp_init' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );

		add_action( "wp_ajax_import_data_from_production", array( $this, "import_data_from_production" ) );

		add_action( "wp_ajax_import_xmlrpc_data_from_production", array(
			$this,
			"import_xmlrpc_data_from_production"
		) );

		add_action( "wp_ajax_get_client_configuration_details", array( $this, "get_client_configuration_details" ) );

	}

	/**
	 * This function is called on 'init' and does the initialization stuff
	 *
	 * @since 2015-07-30 Amit Gupta
	 *
	 * @return void
	 */
	public function on_wp_init() {
		$this->register_post_types_for_import();
		$this->register_taxonomies_for_import();
	}

	/**
	 * Register custom post tyes on init hook as per wordpress codex documentation
	 * @see https://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-30 Archana Mandhare PPT-5077
	 *
	 */
	public function register_post_types_for_import() {

		if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		$custom_post_types = apply_filters( 'pmc_theme_ut_custom_post_types_to_import', array() );

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
	 * @see https://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-30 Archana Mandhare PPT-5077
	 *
	 */
	public function register_taxonomies_for_import() {

		if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$custom_taxonomies = apply_filters( 'pmc_theme_ut_custom_taxonomies_to_import', array() );

		if ( ! empty( $custom_taxonomies ) ) {

			foreach ( $custom_taxonomies as $key => $taxonomy ) {

				Taxonomies_Importer::get_instance()->save_taxonomy( $taxonomy );

			}

		}

	}

	/**
	 * Enqueue styles and scripts required for the admin page
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 * @param string $hook that helps determine which admin page it is
	 *
	 */
	public function load_assets( $hook ) {

		if ( 'tools_page_data-import' != $hook ) {

			return;

		}

		wp_register_style( 'pmc_theme_unit_test_admin_css', plugins_url( 'pmc-theme-unit-test/assets/css/admin-ui.css', PMC_THEME_UNIT_TEST_ROOT ), false, PMC_THEME_UNIT_TEST_VERSION );

		wp_enqueue_style( 'pmc_theme_unit_test_admin_css' );

		wp_register_script( 'pmc_theme_unit_test_admin_js', plugins_url( 'pmc-theme-unit-test/assets/js/admin-ui.js', PMC_THEME_UNIT_TEST_ROOT ), array( 'jquery' ), PMC_THEME_UNIT_TEST_VERSION );

		wp_localize_script( 'pmc_theme_unit_test_admin_js', 'pmc_unit_test_ajax', array(
			'admin_url'           => admin_url( 'admin-ajax.php' ),
			'import_nOnce'        => wp_create_nonce( 'import-from-production' ),
			'import_xmlrpc_nOnce' => wp_create_nonce( 'import-xmlrpc-from-production' ),
			'client_nOnce'        => wp_create_nonce( 'get-client-config-details' ),
			'API'                 => Config::AUTHORIZE_URL,
		) );

		wp_enqueue_script( 'pmc_theme_unit_test_admin_js' );

	}

	/**
	 * Add Admin page to Menu in Dashboard
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	function add_admin_menu() {

		add_management_page( 'Sync from Production', 'Sync from Production', 'manage_options', 'data-import', array(
			$this,
			'data_import_options'
		) );

	}

	/**
	 * Callback function to setup the Admin UI
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	function data_import_options() {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

		}

		$this->_domains = apply_filters( 'pmc_theme_ut_domains', array() );

		echo Config_Helper::render_template( PMC_THEME_UNIT_TEST_ROOT . '/templates/admin-ui.php', array( "domains" => $this->_domains ) );

	}

	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function import_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		if ( empty( $_POST["import_nOnce"] ) || ! wp_verify_nonce( $_POST["import_nOnce"], 'import-from-production' ) || empty( $_POST["domain"] ) ) {

			return;

		}

		$return_info = '';

		if ( ! empty( $_POST["route"] ) ) {

			foreach ( $_POST["route"] as $key => $value ) {

				$route['name'] = sanitize_text_field( $key );

				$route['access_token'] = sanitize_text_field( $value['access_token'] );

				if ( array_key_exists( 'query_params', $value ) ) {

					foreach ( $value['query_params'] as $query_key => $query_value ) {

						$route['query_params'][ $query_key ] = sanitize_text_field( $query_value );

					}

				}

			}

			$params = array(
				'domain' => sanitize_text_field( $_POST["domain"] ),
				'code'   => sanitize_text_field( $_POST['code'] ),
				'route'  => $route,
			);

			$return_info = REST_API_Router::get_instance()->call_rest_api_route( $params );

		}

		ob_clean();
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $return_info );
		wp_die();

	}


	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function import_xmlrpc_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		if ( empty( $_POST["import_xmlrpc_nOnce"] ) || ! wp_verify_nonce( $_POST["import_xmlrpc_nOnce"], 'import-xmlrpc-from-production' ) || empty( $_POST["domain"] ) ) {

			return;

		}

		$params = array(
			'domain' => sanitize_text_field( $_POST["domain"] ),
			'route'  => sanitize_text_field( $_POST["route"] ),
		);

		$return_info = XMLRPC_Router::get_instance()->call_xmlrpc_api_route( $params );


		ob_clean();
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $return_info );
		wp_die();

	}


	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 1.0
	 *
	 * @version 1.0, 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public function get_client_configuration_details() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		if ( empty( $_POST["client_nOnce"] ) || ! wp_verify_nonce( $_POST["client_nOnce"], 'get-client-config-details' ) ) {

			return;

		}
		$domain = sanitize_text_field( $_POST["domain"] );

		$params = array(
			'domain' => $domain,
		);

		$client_details["config_oauth"] = apply_filters( 'pmc_theme_ut_endpoints_config', array(), $params );

		$client_details["xmlrpc_routes"] = apply_filters( 'pmc_theme_ut_xmlrpc_routes', array() );

		$client_details["all_routes"] = apply_filters( 'pmc_theme_ut_domain_routes', array() );

		$client_details["post_routes"] = apply_filters( 'pmc_theme_ut_posts_routes', array() );


		ob_clean();
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $client_details );
		wp_die();

	}

}	//end class

//EOF
