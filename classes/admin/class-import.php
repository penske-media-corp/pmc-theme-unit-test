<?php

namespace PMC\Theme_Unit_Test\Admin;

use PMC\Theme_Unit_Test\Traits\Singleton;
use PMC\Theme_Unit_Test\Settings\Config;
use PMC\Theme_Unit_Test\Importer\Posts;
use PMC\Theme_Unit_Test\Importer\Taxonomies;
use PMC\Theme_Unit_Test\REST_API\Router;
use PMC\Theme_Unit_Test\Settings\Config_Helper;
use PMC\Theme_Unit_Test\XML_RPC\Service;
use PMC\Theme_Unit_Test\Logger\Status;
use PMC\Theme_Unit_Test\Background\Background_Data_Import;


class Import {

	use Singleton;

	const IMPORT_REPORT = 'import_report';

	public $process;

	/**
	 * Add methods that need to run on class initialization
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 */
	protected function __construct() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks required to create admin page
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 * @version 2015-07-30 Amit Gupta PPT-5077 - consolidated multiple 'init' listeners into one
	 *
	 */
	protected function _setup_hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'init', array( $this, 'on_wp_init' ) );
		add_action( 'pmc_theme_unit_test_import_process', array( $this, 'process_import_request' ), 10, 1 );

		// Ajax callbacks
		add_action( 'wp_ajax_get_custom_post_types', array( $this, 'get_custom_post_types' ) );
		add_action( 'wp_ajax_import_report', array( $this, 'import_report' ) );

	}


	/**
	 * Enqueue styles and scripts required for the admin page
	 *
	 * @since 2015-07-06
	 * @version 2015-07-06 Archana Mandhare PPT-5077
	 *
	 * @param string $hook that helps determine which admin page it is
	 *
	 */
	public function load_assets( $hook ) {

		if ( 'toplevel_page_pmc_theme_unit_test' !== $hook ) {
			return;
		}
		wp_register_style( 'pmc_theme_unit_test_admin_css', plugins_url( 'pmc-theme-unit-test/assets/css/admin-ui.css', PMC_THEME_UNIT_TEST_ROOT ), false, PMC_THEME_UNIT_TEST_VERSION );
		wp_enqueue_style( 'pmc_theme_unit_test_admin_css' );
		wp_enqueue_style( 'jquery-ui-progressbar-css', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css' );

		wp_enqueue_script( array( 'jquery-ui-progressbar' ) );
		wp_register_script( 'pmc_theme_unit_test_admin_js', plugins_url( 'pmc-theme-unit-test/assets/js/import.js', PMC_THEME_UNIT_TEST_ROOT ), array( 'jquery' ), PMC_THEME_UNIT_TEST_VERSION );
		wp_localize_script(
			'pmc_theme_unit_test_admin_js',
			'pmc_unit_test_ajax',
			array(
				'admin_url'        => admin_url( 'admin-ajax.php' ),
				'post_types_nOnce' => wp_create_nonce( 'custom-post-types' ),
				'import_nOnce'     => wp_create_nonce( 'import-nones' ),
			)
		);
		wp_enqueue_script( 'pmc_theme_unit_test_admin_js' );
	}

	/**
	 * This function is called on 'init' and does the initialization stuff
	 *
	 * @since 2015-07-30
	 * @version 2015-07-30 Amit Gupta PPT-5077
	 * @return void
	 */
	public function on_wp_init() {
		$this->register_post_types_for_import();
		$this->register_taxonomies_for_import();
		setcookie( 'oauth_redirect', get_admin_url() . 'admin.php?page=pmc_theme_unit_test', time() + 60 * 60 * 24 * 30, '/', Config::COOKIE_DOMAIN );
		$this->process_handler();
	}

	public function process_handler() {
		if ( empty( $this->process ) ) {
			$this->process = new Background_Data_Import();
		}
	}

	public function get_background_process() {
		return $this->process;
	}

	/**
	 * Register custom post tyes on init hook as per wordpress codex documentation
	 *
	 * @see https://codex.wordpress.org/Function_Reference/register_post_type
	 * @since 2015-07-30
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 *
	 */
	public function register_post_types_for_import() {

		if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// register our reporting post type
		Posts::get_instance()->save_post_type( self::IMPORT_REPORT, array(
			'labels'           => array(
				'name'          => __( 'Import Report' ),
				'singular_name' => __( 'Import Report' ),
			),
			'capability_type'  => 'post',
			'public'           => true,
			'show_ui'          => false,
			'delete_with_user' => true,
			'supports'         => array(
				'title',
				'editor',
				'author',
				'page-attributes',
				'custom-fields',
				'comments',
				'revisions'
			),
			'taxonomies'       => array( 'category', 'post_tag' )
		) );

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
	 * @since 2015-07-30
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 *
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
	 * Process frontend form submit
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 *
	 */
	public function form_submit() {

		$types = empty( $_GET['types'] ) ? 0 : absint( $_GET['types'] );
		if ( empty( $types ) ) {
			$cron_jobs = get_option( 'cron' );

			//var_dump($cron_jobs);
			return;
		}

		$log_post = get_option( CONFIG::import_log );

		if ( empty( $log_post ) ) {
			// Create a custom post for saving import log report
			$post_json = array(
				'status'     => 'publish',
				'type'       => self::IMPORT_REPORT,
				'parent'     => 0,
				'menu_order' => false,
				'password'   => false,
				'excerpt'    => 'import',
				'ID'         => 0,
				'content'    => 'Import',
				'title'      => 'IMPORT REPORT ' . date( "Y-m-d H:i:s" ),
				'date'       => date( "Y-m-d H:i:s" ),
				'modified'   => date( "Y-m-d H:i:s" ),
				'sticky'     => false,
			);

			$log_post = Posts::get_instance()->save_post( $post_json, get_current_user_id(), array(), SELF::IMPORT_REPORT );

			if ( is_wp_error( $log_post ) ) {
				return;
			}

			update_option( CONFIG::import_log, $log_post );

		} else {
			Status::get_instance()->clean_log();
		}

		switch ( $types ) {
			case 1:
				$this->import_default_items( $_POST );
				break;
			case 2:
				$this->import_custom_items( $_POST );
				break;
			default:
				break;
		}
	}

	public function process_import_request( $args ) {

		switch ( $args['types'] ) {
			case 1:
				$this->import_default_items( $args['POST'] );
				break;
			case 2:
				$this->import_custom_items( $args['POST'] );
				break;
			default:
				break;
		}
	}


	/**
	 * Import the default wordpress items such as Users, Menu, Tags, Categories, posts, pages etc
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 *
	 * @param array $data Form post data
	 *
	 */
	public function import_default_items( $data ) {

		check_admin_referer( 'import-content' );
		$routes = $data['content'];
		if ( empty( $routes ) ) {
			return;
		}
		foreach ( $routes as $route ) {
			$route = sanitize_text_field( $route );
			if ( ! empty( $route ) ) {
				if ( 'post' === $route ) {
					$this->import_posts( $route );
				} else {
					$return_info[ $route ] = Router::get_instance()->call_rest_api_all_route( $route );
				}
			}
		}
	}

	/**
	 * Import the custom wordpress items such as Custom Post types, Custom Taxonomies and Options
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 *
	 * @param array $data Form post data
	 *
	 */
	public function import_custom_items( $data ) {

		check_admin_referer( 'import-custom-content' );
		$custom_content = $data['custom-content'];
		if ( empty( $custom_content ) ) {
			return;
		}
		foreach ( $custom_content as $custom_type ) {
			$custom_type = sanitize_text_field( $custom_type );
			switch ( $custom_type ) {
				case 'post-types':
					$custom_post_types = $data['custom-post-types'];
					if ( ! empty( $custom_post_types ) ) {
						foreach ( $custom_post_types as $custom_post_type ) {
							$custom_post_type = sanitize_text_field( $custom_post_type );
							$this->import_posts( $custom_post_type );
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

	/**
	 * Import posts using the REST API
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 *
	 * @param string $route
	 */
	public function import_posts( $route ) {
		$return_info[ $route ] = Router::get_instance()->call_rest_api_posts_route( $route );
		$return_info[ $route ] = $this->import_theme_specific_posts( $route, $return_info );
	}

	/**
	 * Import custom taxonomies using the XMLRPC
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 */
	public function import_custom_taxonomies() {
		$return_info['taxonomies'] = Service::get_instance()->call_xmlrpc_api_route( 'taxonomies' );
	}

	/**
	 * Import WordPress options using the XMLRPC
	 *
	 * @since 2016-07-24
	 * @version 2016-07-24 Archana Mandhare PMCVIP-1950
	 */
	public function import_options() {
		$return_info['options'] = Service::get_instance()->call_xmlrpc_api_route( 'options' );
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
	 */
	public function import_theme_specific_posts( $route, $import_data ) {

		// The theme should implement this filter to tell this plugin what it needs to pull from live to set itself up.
		// This filter should return the list of post ids that needs to be pulled
		$post_ids = apply_filters( 'pmc_theme_unit_test_get_required_post_ids_for_post_types', array(), $route );
		if ( empty( $post_ids ) ) {
			return $import_data;
		}

		$imported_data[] = Router::get_instance()->call_rest_api_single_posts( $post_ids );
		$import_data     = array_merge( $import_data, $imported_data );

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
		foreach ( $all_post_types as $key => $value ) {
			if ( ! in_array( $value, array( 'post', 'page', 'attachment' ) ) ) {
				$custom_post_types[ $value ] = $value;
			}
		}
		if ( ! empty( $custom_post_types ) ) {
			wp_send_json( $custom_post_types );
		} else {
			wp_send_json( [] );
		}
	}

	public function import_report() {

		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		check_ajax_referer( 'import-nones', 'import_nOnce' );

		$csv_files = array();
		$lob       = get_bloginfo( 'name' );
		$date      = date( 'Y-m-d' );

		$log_meta = Status::get_instance()->get_current_log();

		if ( ! empty( $log_meta ) ) {

			foreach ( $log_meta as $key => $value ) {
				$filename = $lob . '-' . $date . '-file-' . $key . '.csv';
				if ( ! empty( $value ) ) {

					$csv_files[ $filename ] = array();
					$headers_added          = false;

					foreach ( $value as $data ) {

						$array_data = maybe_unserialize( $data );
						$meta_data  = array();

						if ( 'post' === $key ) {
							foreach ( $array_data as $post_id => $post_data ) {

								if ( ! $headers_added ) {
									$meta_data[]   = array_keys( $array_data[ $post_id ] );
									$headers_added = true;
								}
								$meta_data[] = array_values( $array_data[ $post_id ] );
							}
						} else {

							if ( ! $headers_added ) {
								$meta_data[]   = array_keys( $array_data[0] );
								$headers_added = true;
							}

							foreach ( $array_data as $column_value ) {
								$meta_data[] = array_values( $column_value );
							}
						}

						$csv_files[ $filename ] = Config_Helper::array_to_csv( $meta_data );

					}
				}
			}
		}

		$success = ! empty( $csv_files ) ? true : false;

		$message = ! $success ? 'No report generated' : 'download user report';

		wp_send_json( array(
			'success' => $success,
			'files'   => $csv_files,
			'message' => $message,
		) );

		exit();
	}

}    //end class

//EOF
