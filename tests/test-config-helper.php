<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Config_Helper
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Config_Helper extends WP_UnitTestCase {

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
	 * @covers Config_Helper::_init
	 */
	public function test_init() {

		$config_helper = PMC\Theme_Unit_Test\Config_Helper::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Config_Helper', $config_helper );


		$filters = array(
			'pmc_custom_post_types_to_import' => 'filter_pmc_custom_post_types_to_import',
			'pmc_custom_taxonomies_to_import' => 'filter_pmc_custom_taxonomies_to_import',
		);

		foreach ( $filters as $filter => $listener ) {
			$this->assertGreaterThanOrEqual(
				10,
				has_action( $filter, array( $config_helper, $listener ) ),
				sprintf( 'Config_Helper::_init failed registering filter/action "%1$s" to Config_Helper::%2$s', $filter, $listener )
			);
		}
	}

}
