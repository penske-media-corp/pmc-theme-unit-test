<?php
/*
 * Plugin Name: PMC Theme Unit Test
 * Description: A plugin that uses VIP Wordpress REST API version 1.1 and XML-RPC to get json data backup from VIP live site to dump on QA or local test sites for performing Unit Testing on a Theme
 * Version: 1.0
 * Author: PMC, Archana Mandhare
 * License: PMC proprietary.  All rights reserved.
 */

/* Local plugin meta data constants */
define( 'PMC_THEME_UNIT_TEST_ROOT', __DIR__ );
define( 'PMC_THEME_UNIT_TEST_VERSION', '1.0' );
define( 'PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/import.log' );
define( 'PMC_THEME_UNIT_TEST_ERROR_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/error.log' );
define( 'PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/duplicate.log' );


function pmc_theme_unit_test_loader() {

	if ( is_admin() ) {
		require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/unit-test-autoloader.php';

		\PMC\Theme_Unit_Test\Admin\Import::get_instance();
		\PMC\Theme_Unit_Test\Admin\Credentials::get_instance();
		\PMC\Theme_Unit_Test\Settings\Config_Helper::get_instance();

	}

	// add WP-CLI command support
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once PMC_THEME_UNIT_TEST_ROOT . '/wpcli/pmc-theme-unit-test-wp-cli.php';
	}
}

pmc_theme_unit_test_loader();
