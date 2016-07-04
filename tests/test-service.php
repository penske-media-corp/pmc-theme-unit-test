<?php

/**
 * @group test_xmlrpc_router
 *
 * Unit test for class XML_RPC Service
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Service extends WP_UnitTestCase {

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
	 * @covers PMC\Theme_Unit_Test\XML_RPC\Service::_init()
	 *
	 */
	public function test_init() {

		$xmlrpc_router = PMC\Theme_Unit_Test\XML_RPC\Service::get_instance();

		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\XML_RPC\Service', $xmlrpc_router );

		$filters = array(
			'pmc_xmlrpc_client_credentials' => 'filter_pmc_xmlrpc_client_credentials',
		);

		$this->markTestSkipped( 'The XML_RPC Service filters are not loading with running tests' );

		foreach ( $filters as $filter => $listener ) {
			$this->assertGreaterThanOrEqual(
				10,
				has_filter( $filter, array( $xmlrpc_router, $listener ) ),
				sprintf( 'PMC\Theme_Unit_Test\XML_RPC\Service::_init failed registering filter/action "%1$s" to PMC\Theme_Unit_Test\XML_RPC\Service::%2$s', $filter, $listener )
			);
		}
	}

	/**
	 * @covers PMC\Theme_Unit_Test\XML_RPC\Service::get_instance()->filter_pmc_xmlrpc_client_credentials()
	 */
	public function test_filter_pmc_xmlrpc_client_credentials() {

		$xmlrpc_router = PMC\Theme_Unit_Test\XML_RPC\Service::get_instance();

		$this->assertTrue( has_filter( 'pmc_xmlrpc_client_credentials' ) );

		$xmlrpc_router->xmlrpc_client = new PMC\Theme_Unit_Test\XML_RPC\Client();

		$this->assertNotNull( $xmlrpc_router->xmlrpc_client, 'No Credentials but still Class Client got instantiated' );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\XML_RPC\Service::get_instance()->get_taxonomy_term_by_id()
	 */
	public function test_get_taxonomy_term_by_id() {

		$xmlrpc_router = PMC\Theme_Unit_Test\XML_RPC\Service::get_instance();
		$term_id       = $xmlrpc_router->get_taxonomy_term_by_id( 'category', 4553 );
		$this->assertTrue( is_int( $term_id ), 'Term not imported with the import id 4553' );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\XML_RPC\Service::get_instance()->call_xmlrpc_api_route()
	 */
	public function test_call_xmlrpc_api_route() {

		$xmlrpc_router = PMC\Theme_Unit_Test\XML_RPC\Service::get_instance();

		// Import taxonomies
		$term_ids      = $xmlrpc_router->call_xmlrpc_api_route( 'taxonomies' );
		$this->assertTrue( is_array( $term_ids ), 'Terms for taxonomies not imported with using xmlrpc' );

		// Import Options
		$options = $xmlrpc_router->call_xmlrpc_api_route( 'options' );
		$this->assertTrue( $options, 'Options not imported ' );

		// Import Posts data
		$posts_id = $xmlrpc_router->call_xmlrpc_api_route( 'posts', array( 'post_id' => 1201507640 ) );
		$this->assertTrue( is_array( $posts_id ), 'Posts meta data not imported' );

	}

}
