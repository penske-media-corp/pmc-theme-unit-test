<?php

/**
 * @group pmc-theme-unit-test
 *
 * Unit test for class Attachments
 *
 * Author Archana Mandhare <amandhare@pmc.com>
 *
 */
class Test_Attachments extends WP_UnitTestCase {

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
	 * @covers Attachments::get_instance()
	 */
	public function test_get_instance() {

		$attachments_importer = PMC\Theme_Unit_Test\Importer\Attachments::get_instance();
		$this->assertInstanceOf( 'PMC\Theme_Unit_Test\Importer\Attachments', $attachments_importer );
	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Attachments::get_instance()->save_featured_image()
	 */
	public function test_save_featured_image() {
		$attachments_importer = PMC\Theme_Unit_Test\Importer\Attachments::get_instance();
		$post_id              = $this->factory->post->create();

		//  TEST CASE 1: Empty data
		$image_url     = '';
		$attachment_id = $attachments_importer->save_featured_image( $image_url, 0 );
		$this->assertFalse( $attachment_id, "No Attachment Data provided" );

		//  TEST CASE 2: Valid data
		$image_url     = 'https://www.google.co.in/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0CAcQjRxqFQoTCPq5kO-X2McCFVFJjgodklgANw&url=https%3A%2F%2Ftwitter.com%2Fgosmallbusiness&bvm=bv.101800829,d.c2E&psig=AFQjCNHLq-eeb5czaZyGlefMXfCCRZ1ooA&ust=1441277662392086';
		$attachment_id = $attachments_importer->save_featured_image( $image_url, $post_id );
		$this->assertTrue( is_int( $attachment_id ), "Attachment not inserted with for image URL " . $image_url );

		// TEST CASE 3: invalid OR bad data
		$image_url     = 'http://google.com/1.jpg';
		$attachment_id = $attachments_importer->save_featured_image( $image_url, $post_id );
		$this->assertFalse( $attachment_id, "Attachment not inserted with for image URL " . $image_url );

	}

	/**
	 * @covers PMC\Theme_Unit_Test\Importer\Attachments::get_instance()->instant_attachments_import()
	 */
	public function test_instant_attachments_import() {

		$attachments_importer = PMC\Theme_Unit_Test\Importer\Attachments::get_instance();
		$post_id              = $this->factory->post->create();

		//  TEST CASE 1: Empty data
		$attachments_data = false;
		$attachment_id    = $attachments_importer->instant_attachments_import( array(), $post_id );
		$this->assertEmpty( $attachment_id, "No Attachment Data provided" );

		//  TEST CASE 2: Valid data
		$attachments_data[] = array( 'URL' => 'https://www.google.co.in/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0CAcQjRxqFQoTCPq5kO-X2McCFVFJjgodklgANw&url=https%3A%2F%2Ftwitter.com%2Fgosmallbusiness&bvm=bv.101800829,d.c2E&psig=AFQjCNHLq-eeb5czaZyGlefMXfCCRZ1ooA&ust=1441277662392086' );
		$attachments_data[] = array( 'URL' => 'https://www.google.co.in/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0CAcQjRxqFQoTCPq5kO-X2McCFVFJjgodklgANw&url=https%3A%2F%2Ftwitter.com%2Fgosmallbusiness&bvm=bv.101800829,d.c2E&psig=AFQjCNHLq-eeb5czaZyGlefMXfCCRZ1ooA&ust=1441277662392086' );
		$attachments_id     = $attachments_importer->instant_attachments_import( $attachments_data, $post_id );
		$this->assertTrue( is_array( $attachments_id ), "No Attachment Data provided" );

		// TEST CASE 3: invalid OR bad data
		$attachments_data[] = array( 'URL' => 'http://google.com/1.jpg' );
		$attachments_data[] = array( 'URL' => 'https://www.google.co.in/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0CAcQjRxqFQoTCPq5kO-X2McCFVFJjgodklgANw&url=https%3A%2F%2Ftwitter.com%2Fgosmallbusiness&bvm=bv.101800829,d.c2E&psig=AFQjCNHLq-eeb5czaZyGlefMXfCCRZ1ooA&ust=1441277662392086' );
		$attachments_data[] = array( 'URL' => 'https://www.google.co.in/url?sa=i&rct=j&q=&esrc=s&source=images&cd=&cad=rja&uact=8&ved=0CAcQjRxqFQoTCPq5kO-X2McCFVFJjgodklgANw&url=https%3A%2F%2Ftwitter.com%2Fgosmallbusiness&bvm=bv.101800829,d.c2E&psig=AFQjCNHLq-eeb5czaZyGlefMXfCCRZ1ooA&ust=1441277662392086' );
		$attachments_id     = $attachments_importer->instant_attachments_import( $attachments_data, $post_id );
		$this->assertTrue( is_array( $attachments_id ), "No Attachment Data provided" );

	}
}