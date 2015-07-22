<?php
/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Admin
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
namespace PMC\Theme_Unit_Test;

class Test_Admin extends WP_UnitTestCase {
	/**
	 * @covers Admin::get_instance()
	 */
	public function test_get_instance() {

		$admin = Admin::get_instance();
		$this->assertInstanceOf( 'Admin', $admin );

	}

	/**
	 * @covers Admin::setup_hooks()
	 */
	public function test_setup_hooks() {
		$admin = Admin::get_instance();

		$filters = array(
			'admin_menu'                                 => 'add_admin_menu',
			'admin_enqueue_scripts'                      => 'load_assets',
			'wp_ajax_import_data_from_production'        => 'import_data_from_production',
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
	 * @covers Admin::load_assets()
	 */
	public function test_load_assets() {

		$this->assertTrue( wp_style_is( 'pmc_theme_unit_test_admin_css', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'pmc_theme_unit_test_admin_js', 'enqueued' ) );

	}

	/**
	 * @covers Admin::add_admin_menu()
	 */
	public function add_admin_menu() {

		$menu_slug   = plugin_basename( 'data-import' );
		$parent_slug = plugin_basename( 'tools.php' );

		$hookname = get_plugin_page_hookname( $menu_slug, $parent_slug );

		$this->assetTrue( ( 'tools_page_data-import' !== $hookname ) );

	}


}
