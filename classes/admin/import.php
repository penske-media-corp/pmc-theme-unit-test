<?php
namespace PMC\Theme_Unit_Test\Admin;

use PMC\Theme_Unit_Test\PMC_Singleton as PMC_Singleton;
use PMC\Theme_Unit_Test\Settings\Config as Config;
use PMC\Theme_Unit_Test\Importer\Posts as Posts;
use PMC\Theme_Unit_Test\Importer\Taxonomies as Taxonomies;
use PMC\Theme_Unit_Test\REST_API\Router as Router;
use PMC\Theme_Unit_Test\XML_RPC\Service as Service;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'init', array( $this, 'on_wp_init' ) );
		add_action( 'wp_ajax_get_custom_post_types', array( $this, 'get_custom_post_types' ) );
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

		if ( 'toplevel_page_pmc_theme_unit_test' !== $hook ) {
			return;
		}

		wp_register_style( 'pmc_theme_unit_test_admin_css', plugins_url( 'pmc-theme-unit-test/assets/css/admin-ui.css', PMC_THEME_UNIT_TEST_ROOT ), false, PMC_THEME_UNIT_TEST_VERSION );

		wp_enqueue_style( 'pmc_theme_unit_test_admin_css' );

		wp_register_script( 'pmc_theme_unit_test_admin_js', plugins_url( 'pmc-theme-unit-test/assets/js/import.js', PMC_THEME_UNIT_TEST_ROOT ), array( 'jquery' ), PMC_THEME_UNIT_TEST_VERSION );

		wp_localize_script(
			'pmc_theme_unit_test_admin_js',
			'pmc_unit_test_ajax',
			array(
				'admin_url'           => admin_url( 'admin-ajax.php' ),
				'post_types_nOnce'    => wp_create_nonce( 'custom-post-types' ),
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
		setcookie( 'oauth_redirect', get_admin_url() . 'admin.php?page=pmc_theme_unit_test', time() + 60 * 60 * 24 * 30, '/', Config::COOKIE_DOMAIN );
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

	public function form_submit(){
		$types = empty( $_GET['types'] ) ? 0 :  (int) $_GET['types'] ;
		switch ( $types ) {
			case 1:
				$this->import_default_items();
				break;
			case 2:
				$this->import_custom_items();
				break;
			default:
				break;
		}
	}

	public function import_default_items(){

		check_admin_referer( 'import-content' );

		$routes = $_POST['content'];

		foreach( $routes as $route ){
			$route = sanitize_text_field( $route );
			if ( ! empty( $route ) ) {
				if( 'post' === $route ) {
					$this->import_posts( $route );
				} else {
					$return_info[ $route ] = Router::get_instance()->call_rest_api_all_route( $route );
				}
			}
		}

	}

	public function import_custom_items(){

		check_admin_referer( 'import-custom-content' );

		$routes = $_POST['custom-content'];

		foreach( $routes as $route ) {
			$route = sanitize_text_field( $route );

			switch ( $route ) {
				case 'post-types':
					$routes = $_POST['custom-post-types'];

					if ( ! empty( $routes ) ) {
						foreach( $routes as $route ){
							$route = sanitize_text_field( $route );
							$this->import_posts($route);
						}
					}
					break;
				case 'taxonomies':
					$this->import_custom_taxonomies();
					break;
				case 'options':
					$this->import_options();
					break;
				default:
					break;
			}
		}
	}

	public function import_posts( $route ) {
		$return_info[ $route ] = Router::get_instance()->call_rest_api_posts_route( $route );
		$return_info[ $route ] = $this->import_theme_specific_posts( $route, $return_info );
	}

	public function import_custom_taxonomies() {
		$return_info[ 'taxonomies' ] = Service::get_instance()->call_xmlrpc_api_route( 'taxonomies' );
	}

	public function import_options() {
		$return_info[ 'options' ] = Service::get_instance()->call_xmlrpc_api_route( 'options' );
	}

	/**
	 * Import data specified by the theme that should be pulled on every load
	 * For example things that are required to setup the home page
	 *
	 * @since 2015-11-28
	 * @version 2015-11-28 Archana Mandhare - PMCVIP-177
	 *
	 * @param array $import_data the ids of posts content imported previously
	 * @param array $route post_type for which meta data posts should be imported
	 *
	 * @return array
	 *
	 */
	public function import_theme_specific_posts( $route, $import_data ) {

		// The theme should implement this filter to tell this plugin what it needs to pull from live to set itself up.
		// This filter should return the list of post ids that needs to be pulled
		$post_ids = apply_filters( 'pmc_theme_unit_test_get_required_post_ids_for_post_types', array(), $route );

		if ( empty( $post_ids ) ) {
			return $import_data;
		}

		$imported_data[] = Router::get_instance()->call_rest_api_single_posts( $post_ids );

		$import_data = array_merge( $import_data, $imported_data );

		return $import_data;

	}

	/**
	 * Return all the custom post types that are allowed by the REST API for importing content
	 * @since 2016-07-22
	 * @version 2016-07-22 Archana Mandhare - PMCVIP-1950
	 *
	 * @return array
	 */
	public function get_custom_post_types() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		check_ajax_referer( 'custom-post-types', 'post_types_nOnce' );

		$all_post_types = apply_filters( 'pmc_custom_post_types_to_import', array() );

		$all_post_types = apply_filters( 'rest_api_allowed_post_types', $all_post_types );

		$all_post_types = array_unique( $all_post_types );

		foreach( $all_post_types as $key => $value ){
			if ( ! in_array( $value, array( 'post', 'page', 'attachment' ) ) ) {
				$custom_post_types[$value] = $value;
			}
		}

		if ( ! empty( $custom_post_types ) ) {
			wp_send_json( $custom_post_types );
		}
	}

}    //end class

//EOF
