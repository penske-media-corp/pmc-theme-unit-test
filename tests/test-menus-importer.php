<?php

/**
 * @group test_menu
 *
 * Unit test for class Menus_Importer
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Menus_Importer extends WP_UnitTestCase {

	function setUp() {
		// to speeed up unit test, we bypass files scanning on upload folder
		self::$ignore_files = true;
		parent::setUp();
		register_taxonomy( 'taxonomy1', 'post' );
	}

	function remove_added_uploads() {
		// To prevent all upload files from deletion, since set $ignore_files = true
		// we override the function and do nothing here
	}

	/**
	 * @covers Menus_Importer::get_instance()
	 */
	public function test_get_instance() {

		$menus_importer = PMC\Theme_Unit_Test\Menus_Importer::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Menus_Importer', $menus_importer );
	}

	/**
	 * @covers Menus_Importer::get_instance()->save_menu()
	 * @group test_menu
	 */
	public function test_save_menu() {
		$menus_importer = PMC\Theme_Unit_Test\Menus_Importer::get_instance();

		$menu_id = $menus_importer->save_menu( array() );
		$this->assertFalse( $menu_id, 'Menu insert should fail for empty data' );

		$menu_data = array(
			"id"          => 12345,
			"name"        => "abc-primary",
			"description" => "",
			"items"       => array(
				array(
					"id"          => 123,
					"content_id"  => 1234,
					"type"        => "taxonomy1",
					"type_family" => "taxonomy",
					"type_label"  => "Taxonomy1",
					"url"         => "http://live.com/v/awards/awards-news/",
					"name"        => "Awards News",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 124,
					"content_id"  => 1235,
					"type"        => "taxonomy1",
					"type_family" => "taxonomy",
					"type_label"  => "Taxonomy1",
					"url"         => "http://live.com/v/awards/dialogue/",
					"name"        => "Interviews",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 12341234,
					"content_id"  => 123,
					"type"        => "page",
					"type_family" => "post_type",
					"type_label"  => "Page",
					"url"         => "http://live.com/about-dhd/",
					"name"        => "About Us",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
				array(
					"id"          => 273124,
					"content_id"  => 273124,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"name"        => "Google+",
					"url"         => "https://plus.google.com/100415061732001833760/posts",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
				array(
					"id"          => 861243,
					"content_id"  => 132,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"name"        => "More",
					"url"         => "#",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
					"items"       => array(
						"id"          => 8651234403,
						"content_id"  => 318134860,
						"type"        => "taxonomy1",
						"type_family" => "taxonomy",
						"type_label"  => "Taxonomy1",
						"url"         => "http://live.com/awards/cover-stories/",
						"name"        => "Cover Stories",
						"link_target" => "",
						"link_title"  => "",
						"description" => "",
						"classes"     => array(),
						"xfn"         => "",
					),
					array(

						"id"          => 865402,
						"content_id"  => 177752,
						"type"        => "taxonomy1",
						"type_family" => "taxonomy",
						"type_label"  => "Taxonomy1",
						"url"         => "http://live.com/awards/flash-mob/",
						"name"        => "Photos",
						"link_target" => "",
						"link_title"  => "",
						"description" => "",
						"classes"     => array(),
						"xfn"         => "",
					),
				),
			),
			"locations"   => array(
				"abc-primary"
			),
		);

		$menu_id = $menus_importer->save_menu( $menu_data );
		$this->assertTrue( is_int( intval( $menu_id ) ), 'Menu not saved with name abc-primary' );
		$this->assertNotFalse( wp_get_nav_menu_object( 'abc-primary' ), 'Menu insert fail for abc-primary' );


	}

	/**
	 * @covers Menus_Importer::get_instance()->instant_menus_import()
	 * @group test_menu
	 */
	public function test_instant_menus_import() {
		$menus_importer = PMC\Theme_Unit_Test\Menus_Importer::get_instance();

		$menu_data[] = array(
			"id"          => 12345,
			"name"        => "abc-primary",
			"description" => "",
			"items"       => array(
				array(
					"id"          => 123,
					"content_id"  => 1234,
					"type"        => "taxonomy1",
					"type_family" => "taxonomy",
					"type_label"  => "Taxonomy1",
					"url"         => "http://live.com/v/awards/awards-news/",
					"name"        => "Awards News",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 124,
					"content_id"  => 1235,
					"type"        => "taxonomy1",
					"type_family" => "taxonomy",
					"type_label"  => "Taxonomy1",
					"url"         => "http://live.com/v/awards/dialogue/",
					"name"        => "Interviews",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 12341234,
					"content_id"  => 123,
					"type"        => "page",
					"type_family" => "post_type",
					"type_label"  => "Page",
					"url"         => "http://live.com/about-dhd/",
					"name"        => "About Us",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
				array(
					"id"          => 273124,
					"content_id"  => 273124,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"name"        => "Google+",
					"url"         => "https://plus.google.com/100415061732001833760/posts",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
				array(
					"id"          => 861243,
					"content_id"  => 132,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"name"        => "More",
					"url"         => "#",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
					"items"       => array(
						"id"          => 8651234403,
						"content_id"  => 318134860,
						"type"        => "taxonomy1",
						"type_family" => "taxonomy",
						"type_label"  => "Taxonomy1",
						"url"         => "http://live.com/awards/cover-stories/",
						"name"        => "Cover Stories",
						"link_target" => "",
						"link_title"  => "",
						"description" => "",
						"classes"     => array(),
						"xfn"         => "",
					),
					array(

						"id"          => 865402,
						"content_id"  => 177752,
						"type"        => "taxonomy1",
						"type_family" => "taxonomy",
						"type_label"  => "Taxonomy1",
						"url"         => "http://live.com/awards/flash-mob/",
						"name"        => "Photos",
						"link_target" => "",
						"link_title"  => "",
						"description" => "",
						"classes"     => array(),
						"xfn"         => "",
					),
				),
			),
			"locations"   => array(
				"abc-primary"
			),
		);


		$menu_data[] = array(
			"id"          => 34123,
			"name"        => "Primary Menu",
			"description" => "",
			"items"       => array(
				array(
					"id"          => 1341234,
					"content_id"  => 7421234360,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"url"         => "/",
					"name"        => "Home",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 742366,
					"content_id"  => 524,
					"type"        => "taxonomy1",
					"type_family" => "taxonomy",
					"type_label"  => "Taxonomy1",
					"url"         => "http://live.com/v/film",
					"name"        => "Film",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => ""
				),
				array(
					"id"          => 728913,
					"content_id"  => 462,
					"type"        => "page",
					"type_family" => "post_type",
					"type_label"  => "Page",
					"url"         => "http://live.com/v/tv",
					"name"        => "TV",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
				array(
					"id"          => 1201321026,
					"content_id"  => 1201321026,
					"type"        => "custom",
					"type_family" => "custom",
					"type_label"  => "Custom Link",
					"name"        => "Jobs",
					"url"         => "http://jobsearch.com/jobs/search",
					"link_target" => "",
					"link_title"  => "",
					"description" => "",
					"classes"     => array(),
					"xfn"         => "",
				),
			),
			"locations"   => array(
				"primary"
			),
		);

		$menu_data[] = array(
			"id"          => 115960436,
			"name"        => "secondary",
			"description" => "",
			"items"       => array(),
			"locations"   => array()
		);
		$menus_id    = $menus_importer->instant_menus_import( $menu_data );
		$this->assertTrue( is_array( $menus_id ), "Menu Importer Failed to insert menus json data" );
	}

}
