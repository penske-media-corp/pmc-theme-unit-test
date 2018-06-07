<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Categories
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Categories extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Categories::get_instance()
	 */
	public function test_get_instance() {

		$categories_importer = PMC\Theme_Unit_Test\Importer\Categories::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Categories', $categories_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Categories::get_instance()->save_category
	 */
	public function test_save_category() {

		$categories_importer = PMC\Theme_Unit_Test\Importer\Categories::get_instance();

		// TEST CASE 1: Empty data
		$cat_data = array();
		$tag_id   = $categories_importer->save_category( $cat_data );
		$this->assertFalse( $tag_id, "No Category Data provided" );

		//  TEST CASE 2: Valid data
		$cat_data = array(
			'name'        => 'Category 1',
			'description' => 'This is Category 1 desc',
		);
		$cat_id   = $categories_importer->save_category( $cat_data );
		$this->assertTrue( is_int( $cat_id ), "Category not inserted with a valid cat ID " );
		$this->assertNotNull( term_exists( $cat_data['name'], 'category' ), "Category not inserted with name Category 1." );

		// TEST CASE 3: invalid OR bad data
		$cat_data = array(
			'name'        => '',
			'description' => 'This is Category 2 desc',
		);
		$cat_id   = $categories_importer->save_category( $cat_data );
		$this->assertFalse( $cat_id, "Category inserted with a bad Data" );

		//  TEST CASE 4: duplicate data - should return the ID as is
		$cat = $this->factory->category->create_and_get( array(
			'name'        => 'Category 3',
			'description' => 'This is Category 3 desc'
		) );

		$cat_data = array(
			'name'        => 'Category 3',
			'description' => 'This is Category 3 desc',
		);
		$cat_id   = $categories_importer->save_category( $cat_data );

		$this->assertEquals( $cat_id, $cat->term_id, "Existing Category Falied to update" );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Categories::get_instance()->instant_categories_import
	 */
	public function test_instant_categories_import() {

		$categories_importer = PMC\Theme_Unit_Test\Importer\Categories::get_instance();

		//  TEST CASE 1: Empty data
		$cat_data = array();
		$cat_id   = $categories_importer->instant_categories_import( $cat_data );
		$this->assertEmpty( $cat_id, "No Category Data provided" );

		//  TEST CASE 2: Valid Data
		$cat_json_data[] = array(
			'name'        => 'Category 4',
			'description' => 'This is Category 4 desc',
		);

		$cat_json_data[] = array(
			'name'        => 'Category 5',
			'description' => 'This is Category 5 desc',
		);

		$cat_ids = $categories_importer->instant_categories_import( $cat_json_data );

		$this->assertTrue( is_array( $cat_ids ), "instant_categories_import failed somewhere" );
		$this->assertNotNull( term_exists( 'Category 4', 'category' ), "Category not inserted with name Category 4" );
		$this->assertNotNull( term_exists( 'Category 5', 'category' ), "Category not inserted with name Category 5" );

		//  TEST CASE 3: Mixed / Invalid Data
		$cat_json_data[] = array(
			'name'        => '',
			'description' => 'This is Category desc',
		);

		$cat_json_data[] = array(
			'name'        => 'Category 6',
			'description' => 'This is Category 6 desc',
		);

		$cat_ids = $categories_importer->instant_categories_import( $cat_json_data );

		$this->assertTrue( is_array( $cat_ids ), "instant_categories_import failed somewhere" );

		$this->assertNotNull( term_exists( 'Category 6', 'category' ), "Category not inserted with name Category 6" );

	}

}