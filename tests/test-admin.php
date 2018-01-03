<?php

/**
 *
 * Unit test for class Admin
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */

/**
 * @group test_import
 */
class Test_Import extends WP_UnitTestCase {

	function setUp() {
		// to speeed up unit test, we bypass files scanning on upload folder
		self::$ignore_files = true;
		parent::setUp();
	}

	function remove_added_uploads() {
		// To prevent all upload files from deletion, since set $ignore_files = true
		// we override the function and do nothing here
	}

	/**
	 * @covers Admin::_init()
	 *
	 */
	public function test_init() {

		$admin = PMC\Theme_Unit_Test\Admin\Import::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Admin\Import', $admin );

		$filters = array(
			'init'                                       => 'on_wp_init',
			'admin_init'                                 => 'action_admin_init',
			'admin_menu'                                 => 'add_admin_menu',
			'admin_enqueue_scripts'                      => 'load_assets',
			'wp_ajax_import_all_data_from_production'    => 'import_all_data_from_production',
			'wp_ajax_import_posts_data_from_production'  => 'import_posts_data_from_production',
			'wp_ajax_import_xmlrpc_data_from_production' => 'import_xmlrpc_data_from_production',
			'wp_ajax_get_client_configuration_details'   => 'get_client_configuration_details',
		);

		foreach ( $filters as $filter => $listener ) {
			$this->assertGreaterThanOrEqual(
				10,
				has_action( $filter, array( $admin, $listener ) ),
				sprintf( 'Admin::_init failed registering filter/action "%1$s" to Admin::%2$s', $filter, $listener )
			);
		}
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Admin\Import::action_admin_init()
	 */
	public function test_action_admin_init() {

		global $new_whitelist_options;

		$admin = PMC\Theme_Unit_Test\Admin\Import::get_instance();
		$admin->action_admin_init();
		$this->assertTrue( in_array( 'pmc_domain_creds', $new_whitelist_options['pmc_domain_creds'] ) );

	}


	/**
	 * @covers PMC\Theme_Unit_Test\Admin\Import::add_admin_menu()
	 */
	public function test_add_admin_menu() {

		$menu_slug   = plugin_basename( 'data-import' );
		$parent_slug = plugin_basename( 'tools.php' );

		$hookname = get_plugin_page_hookname( $menu_slug, $parent_slug );

		$this->assertTrue( ( 'tools_page_data-import' !== $hookname ) );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Admin\Import::load_assets()
	 */
	public function test_load_assets() {

		$admin = PMC\Theme_Unit_Test\Admin\Import::get_instance();
		$admin->load_assets( 'tools_page_data-import' );

		$this->assertTrue( wp_style_is( 'pmc_theme_unit_test_admin_css', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'pmc_theme_unit_test_admin_js', 'enqueued' ) );

	}

}
