<?php
/**
 * Dependencies for Plugin.
 */

if ( is_admin() ) {
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/plugin-autoloader.php';
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/traits/trait-singleton.php';
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/process/class-pmc-async-request.php';
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/process/class-pmc-background-process.php';
	require_once PMC_THEME_UNIT_TEST_ROOT . '/classes/process/class-background-data-import.php';
}

// add WP-CLI command support
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PMC_THEME_UNIT_TEST_ROOT . '/wpcli/pmc-theme-unit-test-wp-cli.php';
}