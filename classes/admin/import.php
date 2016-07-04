<?php
namespace PMC\Theme_Unit_Test\Admin;

use \PMC\Theme_Unit_Test\PMC_Singleton as PMC_Singleton;
use \PMC\Theme_Unit_Test\Settings\Config as Config;
use \PMC\Theme_Unit_Test\Settings\Config_Helper as Config_Helper;

use \PMC\Theme_Unit_Test\Importer\Posts as Posts;
use \PMC\Theme_Unit_Test\Importer\Taxonomies as Taxonomies;
use \PMC\Theme_Unit_Test\REST_API\Router as Router;
use \PMC\Theme_Unit_Test\XML_RPC\Service as Service;

class Import extends PMC_Singleton {

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

		add_action( 'init', array( $this, 'on_wp_init' ) );

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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
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

				Posts::get_instance()->save_post_type( $post_type );

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

				Taxonomies::get_instance()->save_taxonomy( $taxonomy );

			}
		}

	}

	/**
	 * Ajax call made from the Admin UI button to fetch data and save to current site DB
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 */
	public function import_all_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-from-production', 'import_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[ $route ] = Router::get_instance()->call_rest_api_all_route( $route );
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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 */
	public function import_posts_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-posts-from-production', 'import_posts_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[ $route ] = Router::get_instance()->call_rest_api_posts_route( $route );
			$return_info[ $route ] = $this->import_theme_specific_posts( $route, $return_info );
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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 */
	public function import_xmlrpc_data_from_production() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier

		check_ajax_referer( 'import-xmlrpc-from-production', 'import_xmlrpc_nOnce' );

		$route = filter_input( INPUT_POST, 'route' );

		$route = isset( $route ) ? sanitize_text_field( wp_unslash( $route ) ) : '';

		if ( ! empty( $route ) ) {
			$return_info[ $route ] = Service::get_instance()->call_xmlrpc_api_route( $route );
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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
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
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
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
