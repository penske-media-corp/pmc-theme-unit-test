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
