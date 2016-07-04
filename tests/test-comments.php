<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Comments_Importer
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Comments extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Comments::get_instance()
	 */
	public function test_get_instance() {

		$comments_importer = PMC\Theme_Unit_Test\Importer\Comments::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Comments', $comments_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Comments::get_instance()->save_comment()
	 */
	public function test_save_comment() {
		$comments_importer = PMC\Theme_Unit_Test\Importer\Comments::get_instance();
		$post_id           = $this->factory->post->create();

		// TEST CASE 1: Empty data
		$comments_data = array();
		$comment_id    = $comments_importer->save_comment( $comments_data, $post_id );
		$this->assertFalse( $comment_id, 'Comment insert should fail for empty data' );

		// TEST CASE 1: Valid Data
		$comments_data = array(
			'author'  => array(
				'name'  => '',
				'email' => false,
				'URL'   => '',
			),
			'content' => '<p>Rick IS TWD. Daryl may have the most girl fans.</p>\n',
			'type'    => 'comment',
			'status'  => 'approved',
			'date'    => '2015-08-23T19:06:20-07:00',
		);
		$comment_id    = $comments_importer->save_comment( $comments_data, $post_id );
		$this->assertTrue( is_int( $comment_id ), 'Comment not inserted for post' );

		$comment = get_comment( $comment_id );

		$this->assertEquals( wp_unslash( $comments_data['content'] ), $comment->comment_content );
		$this->assertEquals( 1, $comment->comment_approved );
		$this->assertEquals( $post_id, $comment->comment_post_ID );


	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Comments::get_instance()->instant_comments_import()
	 */
	public function test_instant_comments_import() {
		$comments_importer = PMC\Theme_Unit_Test\Importer\Comments::get_instance();
		$post_id           = $this->factory->post->create();

		//  TEST CASE 1: Empty data
		$comment_id = $comments_importer->instant_comments_import( array(), $post_id );
		$this->assertEmpty( $comment_id, "No Comment Data provided" );

		//  TEST CASE 2: Valid data
		$comments_data[] = array(
			'author'  => array(
				'name'  => '',
				'email' => false,
				'URL'   => '',
			),
			'content' => '<p>Test comment check. Rick IS TWD. Daryl may have the most girl fans.</p>\n',
			'type'    => 'comment',
			'status'  => 'approved',
			'date'    => '2015-08-23T19:06:20-07:00',
		);
		$comments_data[] = array(
			'author'  => array(
				'name'  => '',
				'email' => false,
				'URL'   => '',
			),
			'content' => '<p>This is a new comment Rick IS TWD. Daryl may have the most girl fans.</p>\n',
			'type'    => 'comment',
			'status'  => 'approved',
			'date'    => '2015-08-23T19:06:20-07:00',
		);
		$comments_id     = $comments_importer->instant_comments_import( $comments_data, $post_id );
		$this->assertTrue( is_array( $comments_id ), "No Attachment Data provided" );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Comments::get_instance()->call_rest_api_route()
	 */
	public function test_call_rest_api_route() {
		$comments_importer = PMC\Theme_Unit_Test\Importer\Comments::get_instance();
		$post_id           = $this->factory->post->create();
		//@todo - to test the REST API calls

	}
}