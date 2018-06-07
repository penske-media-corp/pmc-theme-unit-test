<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Tags
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Tags extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Tags::get_instance()
	 */
	public function test_get_instance() {

		$tags_importer = PMC\Theme_Unit_Test\Importer\Tags::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Tags', $tags_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Tags::get_instance()->save_tag
	 */
	public function test_save_tag() {

		$tags_importer = PMC\Theme_Unit_Test\Importer\Tags::get_instance();

		// TEST CASE 1: Empty data
		$tag_data = array();
		$tag_id   = $tags_importer->save_tag( $tag_data );
		$this->assertFalse( $tag_id, "No Tag Data provided" );

		//  TEST CASE 2: Valid data
		$tag_data = array(
			'name'        => 'tag_1',
			'description' => 'test description for tag 1',
		);
		$tag_id   = $tags_importer->save_tag( $tag_data );
		$this->assertTrue( is_int( $tag_id ), "Tag not inserted with a valid term ID " );
		$this->assertNotNull( term_exists( $tag_data['name'], 'post_tag' ), "Tag not inserted with name tag_1." );

		// TEST CASE 3: invalid OR bad data
		$tag_data = array(
			'name'        => '',
			'description' => 'test description for tag 1',
		);
		$tag_id   = $tags_importer->save_tag( $tag_data );
		$this->assertFalse( $tag_id, "Tag inserted with a bad Data" );

		//  TEST CASE 4: duplicate data - should return the ID as is
		$tag = $this->factory->tag->create_and_get( array(
			'name'        => 'tag_2',
			'description' => 'test description for tag 1'
		) );

		$tag_data = array(
			'name'        => 'tag_2',
			'description' => 'test description for tag 1',
		);
		$tag_id   = $tags_importer->save_tag( $tag_data );

		$this->assertEquals( $tag_id, $tag->term_id, "Existing Tag Falied to update" );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Tags::get_instance()->instant_tags_import
	 */
	public function test_instant_tags_import() {

		$tags_importer = PMC\Theme_Unit_Test\Importer\Tags::get_instance();

		//  TEST CASE 1: Empty data
		$tag_data = array();
		$tag_id   = $tags_importer->instant_tags_import( $tag_data );
		$this->assertEmpty( $tag_id, "No Tag Data provided" );

		//  TEST CASE 2: Valid Data
		$tag_json_data[] = array(
			'name'        => 'tag_3',
			'description' => 'test description for tag 3',
		);

		$tag_json_data[] = array(
			'name'        => 'tag_4',
			'description' => 'test description for tag 4',
		);

		$tag_ids = $tags_importer->instant_tags_import( $tag_json_data );

		$this->assertTrue( is_array( $tag_ids ), "instant_tags_import failed somewhere" );
		$this->assertNotNull( term_exists( 'tag_3', 'post_tag' ), "Tag not inserted with name tag_3" );
		$this->assertNotNull( term_exists( 'tag_4', 'post_tag' ), "Tag not inserted with name tag_4" );

		//  TEST CASE 3: Mixed / Invalid Data
		$tag_json_data[] = array(
			'name'        => '',
			'description' => 'test description for tag 5',
		);

		$tag_json_data[] = array(
			'name'        => 'tag_5',
			'description' => 'test description for tag 5',
		);

		$tag_ids = $tags_importer->instant_tags_import( $tag_json_data );

		$this->assertTrue( is_array( $tag_ids ), "instant_tags_import failed somewhere" );

		$this->assertNotNull( term_exists( 'tag_5', 'post_tag' ), "Tag not inserted with name tag_5" );

	}

}