<?php

namespace PMC\Theme_Unit_Test\XML_RPC;

require_once( ABSPATH . WPINC . '/class-IXR.php' );
require_once( ABSPATH . WPINC . '/class-wp-http-ixr-client.php' );

use PMC\Theme_Unit_Test\Logger\Status;

class Client extends \WP_HTTP_IXR_Client {

	protected $username = '';
	protected $password = '';
	protected $blog_id  = 0;
	public $cache_key   = '';
	public $error;

	/**
	 * @since UNKNOWN Corey Gilmore
	 *
	 * @param $server string
	 * @param $username string
	 * @param $password string
	 * @param $blog_id int
	 * @param $path string|bool
	 * @param $port string|bool
	 * @param $timeout int
	 *
	 */
	function __construct( $server = '', $username = '', $password = '', $blog_id = 0, $path = false, $port = false, $timeout = 30 ) {

		$status = Status::get_instance();

		$xmlrpc_args = array(
			'server'   => $server,
			'username' => $username,
			'password' => $password,
			'blog_id'  => $blog_id,
			'path'     => $path,
			'port'     => $port,
			'timeout'  => $timeout,
		);
		$xmlrpc_args = apply_filters( 'pmc_xmlrpc_client_credentials', $xmlrpc_args );

		if ( empty( $xmlrpc_args['server'] ) || empty( $xmlrpc_args['username'] ) || empty( $xmlrpc_args['password'] ) ) {
			$status->log_to_file( get_called_class() . ': Missing credentials.' );

			return new \WP_Error( 'unknown_exception', get_called_class() . ': Missing credentials.' );
		}

		$this->username = $xmlrpc_args['username'];
		$this->password = $xmlrpc_args['password'];
		$this->blog_id  = $xmlrpc_args['blog_id'];

		// Set a basic cache key to use with all of our requests -- devs should override this
		unset( $xmlrpc_args['password'] );
		$this->cache_key = md5( __FILE__ . serialize( $xmlrpc_args ) ); // @codingStandardsIgnoreLine

		parent::__construct( $xmlrpc_args['server'], $xmlrpc_args['path'], $xmlrpc_args['port'], $xmlrpc_args['timeout'] );
		// bypass default element limit of 30000
		add_filter( 'xmlrpc_element_limit', '__return_false' );
	}

	/**
	 * @since UNKNOWN Corey Gilmore
	 *
	 * @param $method string
	 * @param $args array
	 *
	 * @return mixed
	 */
	public function send_request( $method, $args ) {
		$response = $this->query( $method, $args );
		if ( $response ) {
			$response = $this->message->params[0];
		}

		return $response;
	}

	/**
	 * @since UNKNOWN Corey Gilmore
	 */
	public function query() {
		$args   = func_get_args();
		$method = array_shift( $args );
		$args   = (array) array_shift( $args );

		$default_args = array( $this->blog_id, $this->username, $this->password );
		$args         = array_merge( $default_args, (array) $args );

		return parent::query( $method, $args );
	}

	/**
	 * Get the last XMLRPC error
	 *
	 * @since UNKNOWN Corey Gilmore
	 * @return string|bool
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
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $filter array
	 *
	 * @return array
	 */
	public function get_taxonomies( $filter = array() ) {

		$args       = array();
		$cache_key  = md5( $this->cache_key . serialize( $args ) ); // @codingStandardsIgnoreLine
		$taxonomies = get_transient( $cache_key );
		if ( empty( $taxonomies ) ) {
			$taxonomies = $this->send_request( 'wp.getTaxonomies', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $taxonomies, 300 );
			}
		}
		return $taxonomies;
	}

	/**
	 * Get the single custom taxonomy for the site
	 *
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $taxonomy string
	 *
	 * @return object
	 */
	public function get_taxonomy( $taxonomy ) {

		$args      = array( $taxonomy );
		$cache_key = md5( $this->cache_key . serialize( $args ) ); // @codingStandardsIgnoreLine
		$taxonomy  = get_transient( $cache_key );
		if ( empty( $taxonomy ) ) {
			$taxonomy = $this->send_request( 'wp.getTaxonomy', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $taxonomy, 300 );
			}
		}
		return $taxonomy;
	}

	/**
	 * Get the all the terms for a particular custom taxonomy for the site
	 *
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $taxonomies array
	 * @param $filter array
	 *
	 * @return array
	 */
	public function get_terms( $taxonomies, $filter = array() ) {

		$args      = array(
			$taxonomies,
			$filter,
		);
		$cache_key = md5( $this->cache_key . serialize( $args ) );  // @codingStandardsIgnoreLine
		$terms     = get_transient( $cache_key );
		if ( empty( $terms ) ) {
			$terms = $this->send_request( 'wp.getTerms', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $terms, 300 );
			}
		}

		return $terms;

	}

	/**
	 * Get the single custom taxonomy term for the site
	 *
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $taxonomy string
	 * @param $term string
	 *
	 * @return object
	 */
	public function get_term( $taxonomy, $term ) {

		$args      = array(
			$taxonomy,
			$term,
		);
		$cache_key = md5( $this->cache_key . serialize( $args ) ); // @codingStandardsIgnoreLine
		$term      = get_transient( $cache_key );
		if ( empty( $term ) ) {
			$term = $this->send_request( 'wp.getTerm', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $term, 300 );
			}
		}

		return $term;
	}

	/**
	 * Wrapper for get_options() function to get all options without any filter
	 *
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @return array
	 *
	 */
	public function get_all_options() {
		return $this->get_options( array(), false );
	}

	/**
	 * Get all site options that are not blacklisted from the wp_options table
	 * To blacklist use 'options_export_blacklist' filter so that those will not be exported.
	 *
	 * @since 2015-07-22
	 * @version 2015-07-22 Archana Mandhare PPT-5077
	 *
	 * @param $filter array $args the arguments containing credentials and filter to get option
	 * @param $default string|bool default value to return if no data found
	 *
	 * @return array $options array of option_name and values
	 */
	public function get_options( $filter, $default = false ) {

		$default_filter = array();
		$filter         = wp_parse_args( $filter, $default_filter );
		$args           = array(
			$filter,
		);
		$cache_key      = md5( $this->cache_key . serialize( $args ) );
		$options        = get_transient( $cache_key );
		if ( empty( $options ) ) {
			$pmc_method = $this->method_exists( 'pmc.getOptions' );
			if ( $pmc_method ) {
				$options_json = $this->send_request( 'pmc.getOptions', $args );
				$options_data = json_decode( $options_json, true );

				return $options_data;
			} else {
				$options                 = $this->send_request( 'wp.getOptions', $args );
				$options_data['options'] = $options;
			}
			if ( empty( $this->error ) ) {
				// If nothing set then return the default value
				if ( empty( $options ) ) {
					$options = $default;
				} else {
					// not using cache
					set_transient( $cache_key, $options, 300 );
				}
			}
		}

		return $options;
	}

	/**
	 * Get the custom taxonomy term and custom fields for the posts
	 *
	 * @since 2015-08-10
	 * @version 2015-08-10 Archana Mandhare PPT-5077
	 *
	 * @param $post_id int
	 * @param $fields array
	 *
	 * @return object
	 *
	 */
	public function get_post_custom_data( $post_id, $fields ) {

		$args      = array(
			$post_id,
			$fields,
		);
		$cache_key = md5( $this->cache_key . serialize( $args ) );
		$post_meta = get_transient( $cache_key );
		if ( empty( $post_meta ) ) {
			$post_meta = $this->send_request( 'wp.getPost', $args );
			if ( empty( $this->error ) ) {
				// not using cache
				set_transient( $cache_key, $post_meta, 300 );
			}
		}

		return $post_meta;
	}

	/**
	 * Check we have the method listed in the xmlrpc
	 *
	 * @since 2015-09-03
	 * @version 2015-09-03 Archana Mandhare - PPT-5077
	 *
	 * @param $method_name string
	 *
	 * @return bool
	 *
	 */
	public function method_exists( $method_name ) {

		$args   = array();
		$status = $this->send_request( 'system.listMethods', $args );

		if ( is_array( $status ) ) {
			$key = array_search( $method_name, (array) $status, true );
			if ( $key ) {
				return true;
			}
		}

		return false;
	}
}
