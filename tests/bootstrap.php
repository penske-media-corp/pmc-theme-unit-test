<?php

// need to check if bootstrap need to be ignore
if ( ! defined( 'WP_TEST_IGNORE_BOOTSTRAP' ) ) {
	$_tests_dir = getenv( 'WP_TESTS_DIR' );
	if ( ! $_tests_dir ) {
		$_tests_dir = dirname( __DIR__ ) . '/tests/phpunit';
	}
	require_once $_tests_dir . '/includes/functions.php';
}

// need to use enclosure function here to avoid function name conflict when unit test are reference from root
tests_add_filter( 'after_setup_theme', function () {

	// suppress warning and only reports errors
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
	$plugins_dir = dirname( dirname( dirname( __DIR__ ) ) );
	require_once( $plugins_dir . '/plugins/vip-init.php' );

	add_filter( 'pmc_xmlrpc_client_credentials', function () {

		$xmlrpc_args['server']   = "http://vip.tests.com/xmlrpc.php";
		$xmlrpc_args['username'] = 'abc';
		$xmlrpc_args['password'] = 'abc';

		return $xmlrpc_args;
	} );

	// Load required plugins here
	wpcom_vip_load_plugin( 'pmc-theme-unit-test', 'pmc-plugins' );

	update_option( \PMC\Theme_Unit_Test\Config::api_credentials, [
		Config::api_domain        => 'vip.tests.com',
		Config::api_client_id     => '',
		Config::api_client_secret => '',
		Config::api_redirect_uri  => '',
		Config::access_token_key  => ''
	] );

} );

if ( ! defined( 'WP_TEST_IGNORE_BOOTSTRAP' ) ) {
	require $_tests_dir . '/includes/bootstrap.php';
	// Disable the deprecated warnings (problem with WP3.7.1 and php 5.5)
	PHPUnit_Framework_Error_Deprecated::$enabled = false;
}

//EOF

