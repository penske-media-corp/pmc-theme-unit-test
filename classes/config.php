<?php
namespace PMC\Theme_Unit_Test;

class Config {


	const REST_BASE_URL = 'https://public-api.wordpress.com/rest/v1.1/sites/';

	const REQUEST_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token';

	const AUTHORIZE_URL = 'https://public-api.wordpress.com/oauth2/authorize';

	const VALIDATE_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token-info';

	const access_token_key = 'rest_api_access_token';
	const api_domain = 'rest_api_domain';
	const api_client_id = 'rest_api_client_id';
	const api_client_secret = 'rest_api_client_secret';
	const api_redirect_uri = 'rest_api_redirect_uri';
	const api_xmlrpc_username = 'xmlrpc_username';
	const api_xmlrpc_password = 'xmlrpc_password';

	public static $all_routes = array(
		0 => array(
			'users' => array(
				'access_token' => true,
				'query_params' => array(
					'authors_only' => false,
				),
			),
		),
		1 => array(
			'menus' => array(
				'access_token' => true,
				'query_params' => array(),
			),
		),
		2 => array(
			'tags' => array(
				'access_token' => false,
				'query_params' => array(),
			),
		),
		3 => array(
			'categories' => array(
				'access_token' => false,
				'query_params' => array(),
			),
		),
	);

	public static $xmlrpc_routes = array( 'taxonomies', 'options' );

	public static $custom_posttypes = array( 'post', 'page' );

	public static $custom_taxonomies = array(
		0 => array(
			'name'    => 'vertical',
			'args'    => array( 'orderby' => 'term_order' ),
			'rewrite' => array( 'slug' => 'vertical' ),
		),
		1 => array(
			'name'    => 'editorial',
			'args'    => array( 'orderby' => 'term_order' ),
			'rewrite' => array( 'slug' => 'editorial' ),
		)
	);

	public static $default_taxonomies = array( 'post_tag', 'category' );

}
