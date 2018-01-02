<?php
namespace PMC\Theme_Unit_Test\Settings;

use PMC\Theme_Unit_Test\Traits\Singleton;

class Config_Helper {

	use Singleton;

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 2015-07-21
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 */
	protected function _init() {
		$this->_setup_hooks();
	}

	/**
	 * Setup Hooks required in Config Helper class
	 *
	 * @since 2015-07-21
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 */
	protected function _setup_hooks() {
		add_filter( 'pmc_custom_post_types_to_import', array( $this, 'filter_pmc_custom_post_types_to_import' ) );
		add_filter( 'pmc_custom_taxonomies_to_import', array( $this, 'filter_pmc_custom_taxonomies_to_import' ) );
	}

	/**
	 * Get all the custom post types that need to be registered in init hook in admin
	 *
	 * @since 2015-07-30
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 *
	 * @param $post_types array
	 * @return array
	 */
	public function filter_pmc_custom_post_types_to_import( $post_types ) {
		$custom_posttypes = Config::$custom_posttypes;
		if ( ! empty( $custom_posttypes ) ) {
			foreach ( $custom_posttypes as $post_type ) {
				$post_types[] = $post_type;
			}
		}
		return $post_types;
	}

	/**
	 * Get all the custom taxonomies that need to be registered in init hook in admin
	 *
	 * @since 2015-07-30
	 * @version 2015-07-30 Archana Mandhare PPT-5077
	 *
	 * @param $taxonomies array containing the details required to register taxonomy
	 * @return array
	 */
	public function filter_pmc_custom_taxonomies_to_import( $taxonomies ) {
		$custom_taxonomies = Config::$custom_taxonomies;
		if ( ! empty( $custom_taxonomies ) ) {
			foreach ( $custom_taxonomies as $taxonomy ) {
				$taxonomies[] = $taxonomy;
			}
		}
		return $taxonomies;
	}

	/**
	 * Return the endpoint routes that need to be accessed
	 * from the REST API using the Config::$all_routes array
	 *
	 * We can modify the Config::$all_routes array to fetch just the required data.
	 *
	 * @since 2015-07-14
	 * @version 2015-07-14 Archana Mandhare PPT-5077
	 *
	 * @return array
	 */
	public static function get_all_routes() {
		$domain_routes = array();
		if ( ! empty( Config::$all_routes ) ) {
			foreach ( Config::$all_routes as $route ) {
				$route_name      = array_keys( $route );
				$domain_routes[] = $route_name[0];
			}
		}
		return $domain_routes;
	}

	/**
	 * Return the endpoint routes for xmlrpc that need to be accessed with this API
	 *
	 * @since 2015-07-21
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 *
	 * @return array
	 */
	public static function get_xmlrpc_routes() {
		$xmlrpc_routes = array();
		if ( ! empty( Config::$xmlrpc_routes ) ) {
			$xmlrpc_routes = Config::$xmlrpc_routes;
		}
		return $xmlrpc_routes;
	}

	/**
	 * Return the endpoint routes for Post and allowed Custom Post types
	 * that are required to make a call to the REST API
	 * Use 'rest_api_allowed_post_types' filter to allow CPT support
	 *
	 * @since 2015-07-14
	 * @version 2015-07-14 Archana Mandhare PPT-5077
	 *
	 * @return array
	 */
	public static function get_posts_routes() {
		// Fetch the posts and the custom post types.
		$allowed_types = apply_filters( 'pmc_custom_post_types_to_import', array() );
		$allowed_custom_types = apply_filters( 'rest_api_allowed_post_types', $allowed_types );
		$route_post_types = array_unique( $allowed_custom_types );
		return $route_post_types;
	}

	/**
	 * A template function so that we don't have to put inline HTML.
	 * This will parse a template and add data to it using its variables.
	 *
	 * @param string $path template path for include
	 * @param array $variables Array containing variables and data for template
	 * @param bool $echo - whether to echo or return the contents of the template file
	 *
	 * @return string
	 * @throws \Exception
	 *
	 * @since 2013-01-24 mjohnson
	 */
	public static function render_template( $path, array $variables = array(), $echo = false ) {
		if ( ! file_exists( $path ) ) {
			throw new \Exception( sprintf( 'Template %s doesn\'t exist', basename( $path ) ) );
			return;
		}

		if ( ! empty( $variables ) ) {
			extract( $variables, EXTR_SKIP );
		}

		ob_start();

		require $path;    //better to fail with an error than to continue with incorrect/wierd data

		$output = ob_get_clean();

		if ( true !== $echo ) {
			return $output;
		}

		echo $output;	// Output escaped in template
	}

	/**
	 * Array to CSV converter
	 *
	 * @since 2016-01-6
	 * @version 2016-01-6 Archana Mandhare PMCVIP-113
	 *
	 * @param $array array
	 *
	 * @return string
	 */
	public static function array_to_csv( $array ) {

		// Grab the first element to build the header
		$arr = array_shift( $array );

		$temp = array();

		foreach ( $arr as $key => $data ) {
			$temp[] = $key;
		}

		$csv = implode( ',', $temp ) . "\n";

		// Add the data from the first element
		$csv .= self::to_csv_line( $arr );

		// Add the data for the rest
		foreach ( $array as $arr ) {
			$csv .= self::to_csv_line( $arr );
		}

		return $csv;
	}

	/**
	 * Create a CSV on each line
	 *
	 * @since 2016-01-6
	 * @version 2016-01-6 Archana Mandhare PMCVIP-113
	 *
	 * @param $array array
	 *
	 * @return string
	 */
	public static function to_csv_line( $array ) {

		$temp = array();
		foreach ( $array as $elt ) {
			$elt = str_replace('"', "", $elt);
			$temp[] = '"' . addslashes( $elt ) . '"';
		}
		$string = implode( ',', $temp ) . "\n";

		return $string;

	}
}
