<?php
/**
 * Tests for Settings class
 *
 * @package Context_AI_Search
 */

/**
 * Test settings functionality
 */
class Test_CAIS_Settings extends WP_UnitTestCase {

	/**
	 * Test default settings
	 */
	public function test_default_settings() {
		$settings = CAIS_Settings::get_settings();
		
		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'enabled_post_types', $settings );
		$this->assertContains( 'post', $settings['enabled_post_types'] );
		$this->assertContains( 'page', $settings['enabled_post_types'] );
	}

	/**
	 * Test getting enabled post types
	 */
	public function test_get_enabled_post_types() {
		$enabled = CAIS_Settings::get_enabled_post_types();
		
		$this->assertIsArray( $enabled );
		$this->assertContains( 'post', $enabled );
		$this->assertContains( 'page', $enabled );
	}

	/**
	 * Test checking if post type is enabled
	 */
	public function test_is_post_type_enabled() {
		$this->assertTrue( CAIS_Settings::is_post_type_enabled( 'post' ) );
		$this->assertTrue( CAIS_Settings::is_post_type_enabled( 'page' ) );
		$this->assertFalse( CAIS_Settings::is_post_type_enabled( 'nonexistent' ) );
	}

	/**
	 * Test free post types
	 */
	public function test_get_free_post_types() {
		$free_types = CAIS_Settings::get_free_post_types();
		
		$this->assertIsArray( $free_types );
		$this->assertContains( 'post', $free_types );
		$this->assertContains( 'page', $free_types );
	}

	/**
	 * Test contact info
	 */
	public function test_get_contact_info() {
		$contact = CAIS_Settings::get_contact_info();
		
		$this->assertIsArray( $contact );
		$this->assertArrayHasKey( 'phone', $contact );
		$this->assertArrayHasKey( 'address', $contact );
	}

}
