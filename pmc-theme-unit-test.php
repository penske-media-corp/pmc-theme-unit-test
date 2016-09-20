<?php
/*
 Plugin Name: PMC Theme Unit Test
 Description: A plugin that uses VIP Wordpress REST API and XMLRPC to get json data backup from VIP live site to dump on QA for performing Unit Test on a Theme
 Version: 1.0
 Author: PMC, Archana Mandhare
 License: PMC proprietary.  All rights reserved.
*/

/* Local plugin meta data constants */
define( 'PMC_THEME_UNIT_TEST_ROOT', __DIR__ );
define( 'PMC_THEME_UNIT_TEST_VERSION', '1.0' );
define( 'PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/import.log' );
define( 'PMC_THEME_UNIT_TEST_ERROR_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/error.log' );
define( 'PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/duplicate.log' );



function pmc_theme_unit_test_loader() {

	if ( is_admin() ) {
		require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/autoloader.php';

		\PMC\Theme_Unit_Test\Admin::get_instance();
		\PMC\Theme_Unit_Test\Config_Helper::get_instance();
	}

	// add WP-CLI command support
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once PMC_THEME_UNIT_TEST_ROOT . '/wpcli/pmc-theme-unit-test-wp-cli.php';
	}

}

function pmc_theme_redirectme() {
	// implement /redirectme
	if ( false !== stripos( $_SERVER['REQUEST_URI'], '/redirectme' ) && ( ! empty( $_COOKIE['oauth_redirect'] ) || ! empty( $_GET['to'] ) ) ) {

		if ( ! empty( $_GET['code'] ) ) {

			$code           = sanitize_text_field( $_GET[ 'code' ] );
			$oauth_redirect = sanitize_text_field( !empty( $_GET['to'] ) ? $_GET['to'] : $_COOKIE['oauth_redirect'] );
			$redirect_url   = $oauth_redirect . '&code=' . $code;

			// IMPORTANT: we don't want to call wp_safe_redirect here to prevent any filter from modifying our url
			wp_redirect( $redirect_url, 302 );
			exit;

		}
	}
}
add_action( 'init', 'pmc_theme_redirectme' );

pmc_theme_unit_test_loader();
