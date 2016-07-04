<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Users
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Users extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\Importer\Users::get_instance()
	 */
	public function test_get_instance() {

		$users_importer = PMC\Theme_Unit_Test\Importer\Users::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Users', $users_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Users::get_instance()->save_user
	 */
	public function test_save_user() {

		$users_importer = PMC\Theme_Unit_Test\Importer\Users::get_instance();

		// TEST CASE 1: Empty data
		$user_data = array();
		$user_id   = $users_importer->save_user( $user_data );
		$this->assertFalse( $user_id, "No User Data provided" );

		//  TEST CASE 2: Valid data
		$user_data = array(
			'login'     => 'abcd',
			'name'      => 'test_user',
			'nice_name' => 'Test User',
			'URL'       => 'http://google.com/',
			'email'     => 'abcd@abcd.com',
		);
		$user_id   = $users_importer->save_user( $user_data );
		$this->assertTrue( is_int( $user_id ), "User not inserted with a valid User ID " );
		$this->assertNotEmpty( username_exists( 'abcd' ), "User not inserted with user login abcd." );

		// TEST CASE 3: invalid OR bad data
		$user_data = array(
			'login'     => '',
			'name'      => 'admin',
			'nice_name' => 'Test User',
			'URL'       => '',
			'email'     => 'asdfad3adf#sf',
		);
		$user_id   = $users_importer->save_user( $user_data );
		$this->assertWPError( $user_id, "User not inserted with a valid User ID " );

		//  TEST CASE 4: duplicate data - should get updated
		$user = new WP_User( $this->factory->user->create( array(
			'user_login' => 'user1',
			'user_pass'  => 'password',
			'user_email' => 'user_1@example.org'
		) ) );

		$user_data = array(
			'login'     => $user->user_login,
			'name'      => $user->display_name,
			'nice_name' => $user->user_nicename,
			'URL'       => $user->user_url,
			'email'     => $user->user_email,
		);
		$user_id   = $users_importer->save_user( $user_data );

		$this->assertEquals( $user_id, $user->ID, "Existing User Falied to update" );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Users::get_instance()->instant_users_import
	 */
	public function test_instant_users_import() {

		$users_importer = PMC\Theme_Unit_Test\Importer\Users::get_instance();

		//  TEST CASE 1: Empty data
		$user_data = array();
		$user_id   = $users_importer->instant_users_import( $user_data );
		$this->assertEmpty( $user_id, "No User Data provided" );

		//  TEST CASE 2: Valid Data
		$user_json_data[] = array(
			'login'     => 'efgh',
			'name'      => 'test_user1',
			'nice_name' => 'Test User1',
			'URL'       => 'http://google.com/',
			'email'     => 'efgh@abcd.com',
		);

		$user_json_data[] = array(
			'login'     => 'defg',
			'name'      => 'test_user2',
			'nice_name' => 'Test User2',
			'URL'       => 'http://google.com/',
			'email'     => 'defg@abcd.com',
		);

		$user_ids          = $users_importer->instant_users_import( $user_json_data );

		$this->assertTrue( is_array( $user_ids ), "instant_users_import failed somewhere" );
		$this->assertNotEmpty( username_exists( 'efgh' ), "User not inserted with User login efgh." );
		$this->assertNotEmpty( username_exists( 'defg' ), "User not inserted with  User login defg." );

		//  TEST CASE 3: Mixed / Invalid Data
		$user_json_data[] = array(
			'login'     => '',
			'name'      => 'test_user3',
			'nice_name' => 'Test User3',
			'URL'       => 'http://google.com/',
			'email'     => 'pqrs@abcd.com',
		);

		$user_json_data[] = array(
			'login'     => 'jkhi',
			'name'      => 'test_user4',
			'nice_name' => 'Test User4',
			'URL'       => 'http://google.com/',
			'email'     => 'jkhi@abcd.com',
		);

		$user_ids          = $users_importer->instant_users_import( $user_json_data );

		$this->assertTrue( is_array( $user_ids ), "instant_users_import failed somewhere" );

		$this->assertNotEmpty( username_exists( 'jkhi' ), "User not inserted with  User login jkhi." );

	}

}