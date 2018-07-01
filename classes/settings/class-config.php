<?php

namespace PMC\Theme_Unit_Test\Settings;

class Config {

	const REST_BASE_URL = 'https://public-api.wordpress.com/rest/v1.1/sites/';

	const REQUEST_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token';

	const AUTHORIZE_URL = 'https://public-api.wordpress.com/oauth2/authorize';

	const VALIDATE_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token-info';

	const REST_URL_HOST = 'public-api.wordpress.com';

	const COOKIE_DOMAIN = '.vip.local';

	const ACCESS_TOKEN_KEY = 'rest_api_access_token';

	const API_DOMAIN = 'api_domain';

	const API_CLIENT_ID = 'api_client_id';

	const API_CLIENT_SECRET = 'api_client_secret';

	const API_REDIRECT_URI = 'api_redirect_uri';

	const API_XMLRPC_USERNAME = 'api_xmlrpc_username';

	const API_XMLRPC_PASSWORD = 'api_xmlrpc_password';

	const SHOW_FORM = 'show_data_import_form';

	const IMPORT_LOG = 'import_log_post_id';

	const API_CREDENTIALS = 'api_credentials';

	const POST_COUNT = 10;

	const ATTACHMENT_COUNT = 2;

	public static $all_routes = array(
		0 => array(
			'users' => array(
				'query_params' => array(
					'authors_only' => true,
				),
			),
		),
		1 => array(
			'menus' => array(
				'query_params' => array(),
			),
		),
		2 => array(
			'tags' => array(
				'query_params' => array(),
			),
		),
		3 => array(
			'categories' => array(
				'query_params' => array(),
			),
		),
	);

	public static $xmlrpc_routes = array( 'taxonomies', 'options' );

	public static $custom_posttypes = array( 'post', 'page' );

	public static $custom_taxonomies = array();

	public static $default_taxonomies = array( 'post_tag', 'category' );

}
