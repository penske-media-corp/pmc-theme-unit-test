<?php

namespace PMC\Theme_Unit_Test;

require_once( ABSPATH . WPINC . '/class-IXR.php' );
require_once( ABSPATH . WPINC . '/class-wp-http-ixr-client.php' );

class XMLRPC_Client extends \WP_HTTP_IXR_Client {

	protected $username = '';
	protected $password = '';
	protected $blog_id = 0;
	public $cache_key = '';
	public $error;

	function __construct( $server = '', $username = '', $password = '', $blog_id = 0, $path = false, $port = false, $timeout = 30 ) {
		$xmlrpc_args = array(
			'server'   => $server,
			'username' => $username,
			'password' => $password,
			'blog_id'  => $blog_id,
			'path'     => $path,
			'port'     => $port,
			'timeout'  => $timeout,
		);
		$xmlrpc_args = apply_filters( 'pmc_tut_xmlrpc_client_credentials', $xmlrpc_args );

		// @todo Corey Gilmore throw a WP Error here instead
		if ( empty( $xmlrpc_args['server'] ) || empty( $xmlrpc_args['username'] ) || empty( $xmlrpc_args['password'] ) ) {

			$time = date( '[d/M/Y:H:i:s]' );
			error_log( $time . " -- " . get_called_class() . ': Missing credentials.' . PHP_EOL, 3, PMC_THEME_UNIT_TEST_ERROR_LOG_FILE );

			return new \WP_Error('unknown_exception', get_called_class() . ': Missing credentials.');
			}

		$this->username = $xmlrpc_args['username'];
		$this->password = $xmlrpc_args['password'];
		$this->blog_id  = $xmlrpc_args['blog_id'];

		// Set a basic cache key to use with all of our requests -- devs should override this
		unset( $xmlrpc_args['password'] );
		$this->cache_key = md5( __FILE__ . serialize( $xmlrpc_args ) );

		parent::__construct( $xmlrpc_args['server'], $xmlrpc_args['path'], $xmlrpc_args['port'], $xmlrpc_args['timeout'] );
		// bypass default element limit of 30000
		add_filter( 'xmlrpc_element_limit', '__return_false' );
	}

	/**
	 *
	 *
	 * @since UNKNOWN Corey Gilmore
	 *
	 */
	public function send_request( $method, $args ) {
		if ( $response = $this->query( $method, $args ) ) {
			$response = $this->message->params[0];
		}

		return $response;
	}

	/**
	 *
	 *
	 * @since UNKNOWN Corey Gilmore
	 *
	 */
	public function query() {
		$args   = func_get_args();
		$method = array_shift( $args );
		$args   = (array) array_shift( $args );

		$default_args = array( $this->blog_id, $this->username, $this->password, );
		$args         = array_merge( $default_args, (array) $args );

		return parent::query( $method, $args );
	}

	/**
	 * Get the last XMLRPC error
	 *
	 * @since UNKNOWN Corey Gilmore
	 *
	 */
	public function get_last_error() {
		if ( ! empty( $this->error ) && isset( $this->error->message ) ) {
			return $this->error->message;
		}

		return false;
	}

	/**
	 * Get the custom taxonomies for the site
	 *
	 * @since 1.0
	 * @version 1.0 for PPT-5077, Archana Mandhare
	 *
	 */
	public function get_taxonomies( $filter = array() ) {

		$args = array();

		$cache_key  = md5( $this->cache_key . serialize( $args ) );
		$taxonomies = get_transient( $cache_key );
		if ( empty( $taxonomies ) ) {
			$taxonomies = $this->send_request( 'wp.getTaxonomies', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $taxonomies, 300 );
			}
		} else {
			// using cache
		}

		return $taxonomies;
	}

	/**
	 * Get the single custom taxonomy for the site
	 *
	 * @since 1.0
	 * @version 1.0 for PPT-5077, Archana Mandhare
	 *
	 */
	public function get_taxonomy( $taxonomy ) {

		$args       = array( $taxonomy );
		$cache_key  = md5( $this->cache_key . serialize( $args ) );
		$taxonomy = get_transient( $cache_key );
		if ( empty( $taxonomy ) ) {
			$taxonomy = $this->send_request( 'wp.getTaxonomy', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $taxonomy, 300 );
			}
		} else {
			// using cache
		}

		return $taxonomy;
	}

	/**
	 * Get the all the terms for a particular custom taxonomy for the site
	 *
	 * @since 1.0
	 * @version 1.0 for PPT-5077, Archana Mandhare
	 *
	 */
	public function get_terms( $taxonomies, $filter = array() ) {

		$args      = array(
			$taxonomies,
			$filter
		);
		$cache_key = md5( $this->cache_key . serialize( $args ) );
		$terms     = get_transient( $cache_key );
		if ( empty( $terms ) ) {
			$terms = $this->send_request( 'wp.getTerms', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $terms, 300 );
			}
		} else {
			// using cache
		}

		return $terms;
	}

	/**
	 * Get the single custom taxonomy term for the site
	 *
	 * @since 1.0
	 * @version 1.0 for PPT-5077, Archana Mandhare
	 *
	 */
	public function get_term( $term, $taxonomy ) {

		$args = array(
			$taxonomy,
			$term
		);

		$cache_key = md5( $this->cache_key . serialize( $args ) );
		$term     = get_transient( $cache_key );
		if ( empty( $term ) ) {
			$term = $this->send_request( 'wp.getTerm', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $term, 300 );
			}
		} else {
			// using cache
		}

		return $term;
	}

	/**
	 * Wrapper for get_options() function to get all options without any filter
	 *
	 * @since 1.0
	 * @version 1.0 2015-07-22 Archana Mandhare PPT-5077
	 *
	 */
	public function get_all_options() {
		return $this->get_options( array(), false );
	}

	/**
	 * Get all site options that are not blacklisted from the wp_options table
	 * To blacklist use 'options_export_blacklist' filter so that those will not be exported.
	 *
	 * @since 1.0
	 * @version 1.0 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @params array $args the arguments containing credentials and filter to get option
	 *         string|bool $default default value to return if no data found
	 *
	 * @return array $options array of option_name and values
	 *
	 */
	public function get_options( $filter, $default = false ) {

		$default_filter = array();

		$filter = wp_parse_args( $filter, $default_filter );

		$args = array(
			$filter,
		);

		$cache_key = md5( $this->cache_key . serialize( $args ) );
		$options   = get_transient( $cache_key );
		if ( empty( $options ) ) {
			$options = $this->send_request( 'pmc.getOptions', $args );
			if ( empty( $this->error ) ) {

				// If nothing set then return the default value
				if ( empty( $options ) ) {
					$options = $default;
				} else {
					// not using cache
					set_transient( $cache_key, $options, 300 );
				}
			}
		} else {
			// using cache
		}

		return $options;
	}
}

