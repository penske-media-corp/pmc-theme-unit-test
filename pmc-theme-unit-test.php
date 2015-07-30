<?php
/*
 Plugin Name: PMC Theme Unit Test
 Description: A plugin that uses VIP Wordpress REST API and XMLRPC to get json data backup from VIP live site to dump on QA for performing Unit Test on a Theme
 Version: 1.0
 Author: PMC, Archana Mandhare
 License: PMC proprietary.  All rights reserved.
*/

/* Local plugin meta data constants */
define ( 'PMC_THEME_UNIT_TEST_ROOT', __DIR__ );
define ( 'PMC_THEME_UNIT_TEST_VERSION', '1.0' );
define ( 'PMC_THEME_UNIT_TEST_IMPORT_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/import.log' );
define ( 'PMC_THEME_UNIT_TEST_ERROR_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/error.log' );
define ( 'PMC_THEME_UNIT_TEST_DUPLICATE_LOG_FILE', PMC_THEME_UNIT_TEST_ROOT . '/duplicate.log' );



function pmc_theme_unit_test_loader() {

	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/autoloader.php';
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/pmc-singleton.php';

	\PMC\Theme_Unit_Test\Admin::get_instance();
	\PMC\Theme_Unit_Test\Config_Helper::get_instance();

}

pmc_theme_unit_test_loader();
