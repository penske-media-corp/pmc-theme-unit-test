<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class REST_API_oAuth
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_REST_API_oAuth extends WP_UnitTestCase {

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
	 * @covers REST_API_oAuth::get_instance()
	 */
	public function test_get_instance() {

		$rest_api_oauth = PMC\Theme_Unit_Test\REST_API_oAuth::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\REST_API_oAuth', $rest_api_oauth );
	}

	/**
	 * @covers REST_API_oAuth::get_instance()->access_endpoint()
	 */
	public function access_endpoint() {
		$rest_api_oauth = PMC\Theme_Unit_Test\REST_API_oAuth::get_instance();

		$rest_data      = $rest_api_oauth->access_endpoint( 'posts', array( 'type' => 'page' ), 'posts', false );
		$this->assertTrue( is_array( $rest_data ), 'REST API failed to fetch data for post type page' );

		$rest_data = $rest_api_oauth->access_endpoint( 'users', array( 'authors_only' => 'true' ), 'users', true );
		$this->assertTrue( is_array( $rest_data ), 'REST API failed to fetch data for users endpoint' );
	}


	/**
	 * @covers REST_API_oAuth::get_instance()->is_valid_token()
	 */
	public function is_valid_token() {
		$rest_api_oauth = PMC\Theme_Unit_Test\REST_API_oAuth::get_instance();
		$is_valid_token = $rest_api_oauth->is_valid_token();
		$this->assertTrue( $is_valid_token );

	}

	/**
	 * @covers REST_API_oAuth::get_instance()->fetch_access_token()
	 */
	public function test_fetch_access_token() {
		$rest_api_oauth = PMC\Theme_Unit_Test\REST_API_oAuth::get_instance();
		$return_value   = $rest_api_oauth->fetch_access_token( 'sdfqrfasad' );
		$this->assertFalse( $return_value );


	}
}