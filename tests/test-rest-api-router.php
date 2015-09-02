<?php

/**
 * @group test_rest_api_router
 *
 * Unit test for class REST_API_Router
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_REST_API_Router extends WP_UnitTestCase {

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
	 * @covers REST_API_Router::get_instance()
	 */
	public function test_get_instance() {

		$rest_api_router = PMC\Theme_Unit_Test\REST_API_Router::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\REST_API_Router', $rest_api_router );
	}

	/**
	 * @covers REST_API_Router::get_instance()->call_rest_api_posts_route()
	 * @group test_rest_api_router
	 */
	public function test_call_rest_api_posts_route() {
		$rest_api_router = PMC\Theme_Unit_Test\REST_API_Router::get_instance();
		$pages           = $rest_api_router->call_rest_api_posts_route( 'page' );
		$this->assertTrue( is_array( $pages ), 'REST_API_Router failed to import page' );
	}

	/**
	 * @covers REST_API_Router::get_instance()->call_rest_api_all_route()
	 * @group test_rest_api_router
	 */
	public function test_call_rest_api_all_route() {
		$rest_api_router = PMC\Theme_Unit_Test\REST_API_Router::get_instance();
		$users           = $rest_api_router->call_rest_api_all_route( 'users' );
		$this->assertTrue( is_array( $users ), 'REST_API_Router failed to import users' );
	}
}
