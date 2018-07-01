<?php

namespace PMC\Theme_Unit_Test\Settings;

use PMC\Theme_Unit_Test\Traits\Singleton;
use Psy\Exception\ErrorException;

class Config_Helper {

	use Singleton;

	/**
	 * Hook in the methods during initialization.
	 *
	 * @since 2015-07-21
	 * @version 2015-07-21 Archana Mandhare PPT-5077
	 */
	protected function __construct() {
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
	 *
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
	 *
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
		$domain_routes = [];
		if ( ! empty( Config::$all_routes ) ) {
			foreach ( Config::$all_routes as $route ) {
				$route_name      = array_keys( (array) $route );
				$domain_routes[] = reset( $route_name );
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
		$xmlrpc_routes = [];
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
		$allowed_types        = apply_filters( 'pmc_custom_post_types_to_import', [] );
		$allowed_custom_types = apply_filters( 'rest_api_allowed_post_types', $allowed_types );
		return array_unique( (array) $allowed_custom_types );

	}

	/**
	 * Method to compare two arrays and return an array with the values of second array updated with values of corresponding keys of first array.
	 * Any key in $args which does not exist in $defaults would be discarded.
	 *
	 * @since 2018-01-24 Amit Gupta
	 *
	 * @param array $args An array of arguments which is to be parsed
	 * @param array $defaults An array of default arguments which are to be updated with values of corresponding keys in $args
	 *
	 * @return array
	 */
	public static function parse_whitelisted_args( array $args, array $defaults = [] ) {

		if ( empty( $defaults ) ) {
			return [];
		}

		$updated_args = $defaults; //lets use defaults as starting point

		$whitelisted_keys = array_keys( (array) $defaults );

		for ( $i = 0; $i < count( $whitelisted_keys ); $i++ ) {

			$key = $whitelisted_keys[ $i ];

			if ( isset( $args[ $key ] ) ) {
				$updated_args[ $key ] = $args[ $key ];
			}

			unset( $key );

		}

		return $updated_args;

	}

	/**
	 * A template function so that we don't have to put inline HTML.
	 * This will parse a template and add data to it using its variables.
	 *
	 * @param string $path template path for include
	 * @param array $variables Array containing variables and data for template
	 * @param boolean $echo Set this to TRUE if the template output is to be sent to browser. Default is FALSE.
	 * @param array $options An array of additional options
	 *
	 * @return string
	 * @throws \ErrorException
	 * @throws \ErrorException
	 *
	 * @since 2013-01-24 mjohnson
	 * @version 2017-09-21 Amit Gupta - added 3rd parameter to allow the method to output template instead of returning HTML
	 * @version 2018-01-24 Amit Gupta - added 4th parameter to specify options and added search and loading of templates from parent theme if not in current theme
	 *
	 */
	public static function render_template( $path, array $variables = [], $echo = false, array $options = [] ) {

		/*
		 * Parse the options with the whitelist so that only
		 * the whitelisted options remain in the array and
		 * missing options are added with default values.
		 * Any options not defined in defaults here would be
		 * discarded. This is an inclusive behaviour which
		 * wp_parse_args() does not support.
		 */
		$options = static::parse_whitelisted_args( $options, [
			'is_relative_path' => false,
		] );

		// Set options into individual vars
		$is_relative_path = ( true === $options['is_relative_path'] );

		if ( true !== $is_relative_path && ( ! file_exists( $path ) || 0 !== validate_file( $path ) ) ) {

			/*
			 * Invalid template path
			 * Throw an exception if current env is not production
			 * else silently bail out on production
			 */
			throw new \ErrorException( sprintf( 'Template %s doesn\'t exist', basename( $path ) ) );

		}

		/*
		 * If relative path to template has been passed then
		 * we will look for template in child theme and parent theme
		 */
		if ( true === $is_relative_path ) {

			$template_path = locate_template( [ static::unleadingslashit( $path ) ], false );

			if ( empty( $template_path ) ) {

				/*
				 * Can't find template in child theme & parent theme
				 * Throw an exception if current env is not production
				 * else silently bail out on production
				 */
				throw new \ErrorException( sprintf( 'Template %s doesn\'t exist', basename( $path ) ) );

			}

			$path = $template_path;

			unset( $template_path );

		}

		if ( ! empty( $variables ) ) {
			extract( $variables, EXTR_SKIP ); // @codingStandardsIgnoreLine
		}

		if ( true === $echo ) {
			// load template and output the data
			require $path;  // @codingStandardsIgnoreLine
			return ''; //job done, bail out
		}

		ob_start();
		require $path;  // @codingStandardsIgnoreLine load template output in buffer
		return ob_get_clean();

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
			$elt    = str_replace( '"', '', $elt );
			$temp[] = '"' . addslashes( $elt ) . '"';
		}
		$string = implode( ',', $temp ) . "\n";

		return $string;

	}
}
