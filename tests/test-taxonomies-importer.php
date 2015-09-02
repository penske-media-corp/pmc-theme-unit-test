<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Taxonomies_Importer
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Taxonomies_Importer extends WP_UnitTestCase {

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
	 * @covers Taxonomies_Importer::get_instance()
	 */
	public function test_get_instance() {

		$tax_importer = PMC\Theme_Unit_Test\Taxonomies_Importer::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Taxonomies_Importer', $tax_importer );
	}

}