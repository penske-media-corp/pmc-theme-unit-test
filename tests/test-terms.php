<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Terms
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Terms extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Terms::get_instance()
	 */
	public function test_get_instance() {

		$terms_importer = PMC\Theme_Unit_Test\Importer\Terms::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Terms', $terms_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Terms::get_instance()->save_taxonomy_terms
	 */
	public function test_save_taxonomy_terms() {

		global $wp_taxonomies;
		$terms_importer = PMC\Theme_Unit_Test\Importer\Terms::get_instance();

		// TEST CASE 1: Empty data
		$terms_data = array();
		$term_id    = $terms_importer->save_taxonomy_terms( $terms_data );
		$this->assertFalse( $term_id, "No Terms Data provided" );

		//  TEST CASE 2: Valid data
		$term_data = array(
			'name'        => 'Test Term 1',
			'taxonomy'    => 'post_tag',
			'description' => 'This is test term desc',
			'slug'        => 'test-term-1',
		);
		$term_id   = $terms_importer->save_taxonomy_terms( $term_data );
		$this->assertTrue( is_int( $term_id ), "Term not inserted with a valid Data" );
		$this->assertNotNull( term_exists( 'Test Term 1', 'post_tag' ), "Term not inserted with Term name Test Term 1." );

		//  TEST CASE 3: InValid OR BAD data
		$term_data = array(
			'name'        => '',
			'taxonomy'    => 'post_tag',
			'description' => 'This is test term desc',
			'slug'        => '@#$',
		);
		$term_id   = $terms_importer->save_taxonomy_terms( $term_data );
		$this->assertWPError( $term_id, "Term inserted with a bad Data" );

		$term_data = array(
			'name'        => 'test test',
			'taxonomy'    => '123',
			'description' => 'This is test term desc',
			'slug'        => '@#$',
		);
		$term_id   = $terms_importer->save_taxonomy_terms( $term_data );
		$this->assertFalse( $term_id, "Term inserted with a bad Data" );

		//  TEST CASE 4: Duplicate data

		$termID    = $this->factory->term->create_and_get( array(
			'name'        => 'Test Term 2',
			'description' => 'Terms term desc'
		) );
		$term_data = array(
			'name'        => 'Test Term 2',
			'taxonomy'    => 'post_tag',
			'description' => 'Terms term desc',
		);
		$term_id   = $terms_importer->save_taxonomy_terms( $term_data );
		$this->assertEquals( $term_id, $termID->term_id, "Existing Term Not updated" );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Terms::get_instance()->instant_terms_import
	 */
	public function test_instant_terms_import() {

		$terms_importer = PMC\Theme_Unit_Test\Importer\Terms::get_instance();

		//  TEST CASE 1: Empty data
		$term_data = array();
		$term_ids  = $terms_importer->instant_terms_import( $term_data );
		$this->assertEmpty( $term_ids, "No Term Data provided" );

		// TEST CASE 2: Valid Data
		$term_data[] = array(
			'name'        => 'Test Term 3',
			'taxonomy'    => 'post_tag',
			'description' => 'Terms term desc',
		);
		$term_data[] = array(
			'name'        => 'Test Term 4',
			'taxonomy'    => 'category',
			'description' => 'Terms term desc',
		);
		$term_ids    = $terms_importer->instant_terms_import( $term_data );
		$this->assertTrue( is_array( $term_ids ), "instant_terms_import failed somewhere" );
		$this->assertNotEmpty( term_exists( 'Test Term 3', 'post_tag' ), "Term not inserted with name Test Term 3" );
		$this->assertNotEmpty( term_exists( 'Test Term 4', 'category' ), "Term not inserted with name Test Term 4" );

		// TEST CASE 3: Mixed / InValid Data
		$term_data[] = array(
			'name'        => 'Test Term 5',
			'taxonomy'    => '123',
			'description' => 'Terms term desc',
		);
		$term_data[] = array(
			'name'        => '',
			'taxonomy'    => 'category',
			'description' => 'Terms term desc',
		);
		$term_data[] = array(
			'name'        => 'Test Term 6',
			'taxonomy'    => 'category',
			'description' => 'Terms term desc',
		);

		$term_ids    = $terms_importer->instant_terms_import( $term_data );
		$this->assertTrue( is_array( $term_ids ), "instant_terms_import failed somewhere" );
		$this->assertNull( term_exists( 'Test Term 5', '123' ), "Term inserted with name Test Term 5" );
		$this->assertNotEmpty( term_exists( 'Test Term 6', 'category' ), "Term not inserted with name Test Term 6" );

	}

}