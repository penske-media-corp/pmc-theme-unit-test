<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Options
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Options extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Options::get_instance()
	 */
	public function test_get_instance() {

		$options_importer = PMC\Theme_Unit_Test\Importer\Options::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Options', $options_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Options::get_instance()->instant_options_import()
	 * @group test_options
	 */
	public function test_instant_options_import() {

		$options_importer = PMC\Theme_Unit_Test\Importer\Options::get_instance();

		$options_data     = array();
		$options_json     = json_encode( $options_data );
		$retrun_value     = $options_importer->instant_options_import( $options_json );
		$this->assertFalse( $retrun_value, 'Options Importer failed' );


		$options_data = array(
			'options'     => array(
				"thread_comments"       => "1",
				"thread_comments_depth" => "5",
				"thumbnail_crop"        => 0,
				"thumbnail_size_h"      => 150,
				"thumbnail_size_w"      => 150,
				"tiled_galleries"       => "",
				"start_of_week"         => "1",
				"stb_enabled"           => "",
				"stc_disabled"          => "",
				"sticky_posts"          => "a:0:{}",
				"blogdescription"       => "LIVE Entertainment Breaking News",
				"blogname"              => "LIVE",
				"blog_charset"          => "UTF-8",
				"blog_public"           => "1",
				"moderation_keys"       => "",
				"recently_edited"       => "",
				"recently_edited"       => "",
			),
			'no_autoload' => array(
				"moderation_keys",
				"recently_edited",
			),
		);
		$options_json = json_encode( $options_data );
		$return_value = $options_importer->instant_options_import( $options_json );
		$this->assertTrue( $return_value, 'Options Importer failed' );

	}

}