<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Posts
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Posts extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Posts::get_instance()
	 */
	public function test_get_instance() {

		$posts_importer = PMC\Theme_Unit_Test\Importer\Posts::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Posts', $posts_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Posts::get_instance()->save_post()
	 * @group test_posts
	 */
	public function test_save_post() {
		$posts_importer = PMC\Theme_Unit_Test\Importer\Posts::get_instance();
		$post_data      = array(
			'ID'         => 1201503912,
			'status'     => 'publish',
			'type'       => 'post',
			'menu_order' => 0,
			'password'   => '',
			'excerpt'    => 'TEST excerpt',
			'content'    => 'TEST content for test post',
			'title'      => 'Test title',
			'date'       => '2015-08-23T16:30:15-07:00',
			'modified'   => '2015-08-23T17:00:56-07:00',
			'parent'     => false,
		);

		$post_id = $posts_importer->save_post( $post_data );
		$this->assertTrue( is_int( $post_id ), 'Post not inserted ' );

		$post_obj = get_post( $post_id );
		$this->assertEquals( $post_id, $post_obj->ID, 'Post not inserted with ID ' . $post_id );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Posts::get_instance()->instant_posts_import()
	 * @group test_posts
	 */
	public function test_instant_posts_import() {
		$posts_importer = PMC\Theme_Unit_Test\Importer\Posts::get_instance();

		$post_data[] = array(
			'ID'             => '1234123',
			'status'         => 'publish',
			'type'           => 'post',
			'menu_order'     => 0,
			'password'       => '',
			'excerpt'        => 'TEST excerpt 1',
			'content'        => 'TEST content for test post 1',
			'title'          => 'Test title 1',
			'date'           => '2015-08-23T16:30:15-07:00',
			'modified'       => '2015-08-23T17:00:56-07:00',
			'parent'         => false,
			'comment_count'  => '26',
			'tags'           => array(
				'AMC'         => array(
					'name'        => 'AMC',
					'slug'        => 'amc',
					'description' => '',
				),
				'Best of NOW' => array(
					'name'        => 'Best of NOW',
					'slug'        => 'best-of-NOW',
					'description' => '',
				),
			),
			'categories'     => array(
				'Breaking News' => array(
					'name'        => 'Breaking News',
					'slug'        => 'breaking-news',
					'description' => '',

				)
			),
			'attachments'    => array(
				'1201502616' => array(
					'ID'  => '1201502616',
					'URL' => 'https://liveURL.com/2015/08/fear-the-walking-dead.jpg',
				),
				'1201504029' => array(
					'ID'  => '1201504029',
					'URL' => 'https://liveURL.com/2015/08/assassin.jpg',
				),
			),
			'featured_image' => 'https://liveURL.com/2015/06/terminator-genisys.jpg',
			'metadata'       => array(
				array(
					"id"    => "6059118",
					"key"   => "geo_public",
					"value" => "0",
				),
			),

		);

		$post_data[] = array(
			'ID'             => 1201502567,
			'status'         => 'publish',
			'type'           => 'post',
			'menu_order'     => 0,
			'password'       => '',
			'excerpt'        => 'TEST excerpt 2',
			'content'        => 'TEST content for test post 2',
			'title'          => 'Test title 2',
			'date'           => '2015-08-23T16:30:15-07:00',
			'modified'       => '2015-08-23T17:00:56-07:00',
			'parent'         => false,
			'comment_count'  => '26',
			'sticky'         => false,
			'tags'           => array(
				'AMC'         => array(
					'name'        => 'AMC',
					'slug'        => 'amc',
					'description' => '',
				),
				'Best of Now' => array(
					'name'        => 'Best of Now',
					'slug'        => 'best-of-now',
					'description' => '',
				),
			),
			'categories'     => array(
				'Breaking News' => array(
					'name'        => 'Breaking News',
					'slug'        => 'breaking-news',
					'description' => '',

				)
			),
			'attachments'    => array(
				'1201502616' => array(
					'ID'  => '1201502616',
					'URL' => 'https://liveURL.com/2015/08/fear-the-walking-dead.jpg',
				),
				'1201504029' => array(
					'ID'  => '1201504029',
					'URL' => 'https://liveURL.com/2015/08/assassin.jpg',
				),
			),
			'featured_image' => 'https://liveURL.com/2015/06/terminator-genisys.jpg',
			'metadata'       => array(
				array(
					"id"    => "6059118",
					"key"   => "geo_public",
					"value" => "0",
				),
			),
		);

		$post_id = $posts_importer->instant_posts_import( $post_data );
		$this->assertTrue( is_array( $post_id ), "instant_posts_import failed somewhere" );

	}
}