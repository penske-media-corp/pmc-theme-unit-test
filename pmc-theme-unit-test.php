<?php
/*
 * Plugin Name: PMC Theme Unit Test
 * Description: A plugin that uses VIP Wordpress REST API version 1.1 and XML-RPC to get json data backup from VIP live site to dump on QA or local test sites for performing Unit Testing on a Theme
 * Version: 2.0
 * Author: PMC, Archana Mandhare
 * License: PMC proprietary.  All rights reserved.
 */

/* Local plugin meta data constants */
define( 'PMC_THEME_UNIT_TEST_ROOT', __DIR__ );
define( 'PMC_THEME_UNIT_TEST_VERSION', '2.0' );
define( 'PMC_THEME_UNIT_TEST_ERROR_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/logs/error.csv' );

function pmc_theme_unit_test_loader() {

	require_once PMC_THEME_UNIT_TEST_ROOT . '/dependencies.php';

	if ( is_admin() ) {
		\PMC\Theme_Unit_Test\Admin\Import::get_instance();
		\PMC\Theme_Unit_Test\Admin\Login::get_instance();
		\PMC\Theme_Unit_Test\Settings\Config_Helper::get_instance();
	}
}

pmc_theme_unit_test_loader();


// Setting a custom timeout value for cURL. Using a high value for priority to ensure the function runs after any other added to the same action hook.
add_action('http_api_curl', 'sar_custom_curl_timeout', 9999, 1);
function sar_custom_curl_timeout( $handle ){
	curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
	curl_setopt( $handle, CURLOPT_TIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
}
// Setting custom timeout for the HTTP request
add_filter( 'http_request_timeout', 'sar_custom_http_request_timeout', 9999 );
function sar_custom_http_request_timeout( $timeout_value ) {
	return 30; // 30 seconds. Too much for production, only for testing.
}
// Setting custom timeout in HTTP request args
add_filter('http_request_args', 'sar_custom_http_request_args', 9999, 1);
function sar_custom_http_request_args( $r ){
	$r['timeout'] = 30; // 30 seconds. Too much for production, only for testing.
	return $r;
}