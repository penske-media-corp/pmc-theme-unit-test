<?php
namespace PMC\Theme_Unit_Test;

class Config {


	const REST_BASE_URL = 'https://public-api.wordpress.com/rest/v1/sites/';

	const REQUEST_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token';

	const AUTHORIZE_URL = 'https://public-api.wordpress.com/oauth2/authorize';

	const AUTHENTICATE_URL = 'https://public-api.wordpress.com/oauth2/authenticate';

	const VALIDATE_URL = 'https://public-api.wordpress.com/oauth2/token-info';

	public static $rest_api_auth = array(
		"YOURDOMAIN_1.com" => array(
			"client_id"     => "",
			"client_secret" => "",
			"redirect_uri"  => ""
		),
		"YOURDOMAIN_2.com" => array(
			"client_id"     => "",
			"client_secret" => "",
			"redirect_uri"  => ''
		),

	);

	public static $xmlrpc_auth = array(
		"YOURDOMAIN_1.com" => array(
			"username" => "",
			"password" => "",
		),
		"YOURDOMAIN_2.com" => array(
			"username" => "",
			"password" => "",
		),
	);

	public static $all_routes = array(
		0 => array(
			"users" => array(
				"access_token" => true,
				"query_params" => array(
					"authors_only" => false,
				)
			)
		),
		1 => array(
			"menus" => array(
				"access_token" => true,
				"query_params" => array()
			)
		),
		2 => array(
			"tags" => array(
				"access_token" => false,
				"query_params" => array()
			)
		),
		3 => array(
			"categories" => array(
				"access_token" => false,
				"query_params" => array()
			)
		),
	);

	public static $xmlrpc_routes = array( "taxonomies", "options" );

}
