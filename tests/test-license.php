<?php
/**
 * Tests for License class
 *
 * @package WP_Context_AI_Search
 */

/**
 * Test license functionality
 */
class Test_WP_CAIS_License extends WP_UnitTestCase {

	/**
	 * Test premium check defaults to false
	 */
	public function test_is_premium_default() {
		$this->assertFalse( WP_CAIS_License::is_premium() );
	}

	/**
	 * Test premium features list
	 */
	public function test_get_premium_features() {
		$features = WP_CAIS_License::get_premium_features();
		
		$this->assertIsArray( $features );
		$this->assertArrayHasKey( 'custom_post_types', $features );
		$this->assertArrayHasKey( 'json_files', $features );
		$this->assertArrayHasKey( 'markdown_files', $features );
	}

	/**
	 * Test premium feature check
	 */
	public function test_is_premium_feature() {
		$this->assertTrue( WP_CAIS_License::is_premium_feature( 'custom_post_types' ) );
		$this->assertTrue( WP_CAIS_License::is_premium_feature( 'json_files' ) );
		$this->assertFalse( WP_CAIS_License::is_premium_feature( 'posts' ) );
	}

	/**
	 * Test has access for free features
	 */
	public function test_has_access_free_features() {
		$this->assertTrue( WP_CAIS_License::has_access( 'posts' ) );
		$this->assertTrue( WP_CAIS_License::has_access( 'pages' ) );
	}

	/**
	 * Test has access for premium features
	 */
	public function test_has_access_premium_features() {
		// Should be false when not premium
		$this->assertFalse( WP_CAIS_License::has_access( 'custom_post_types' ) );
	}
}
