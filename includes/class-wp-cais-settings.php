<?php
/**
 * Settings management class.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_CAIS_Settings class.
 */
class WP_CAIS_Settings {

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wp_cais_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private static $defaults = array(
		'enabled_post_types' => array( 'post', 'page' ),
		'version'            => WP_CAIS_VERSION,
		'contact_phone'       => '',
		'contact_address'    => '',
		'ai_api_key'         => '',
		'ai_provider'        => 'openai',
		'custom_title'       => '',
		'custom_subtitle'    => '',
		'custom_placeholder'  => '',
		'custom_welcome_msg' => '',
	);

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, self::$defaults );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed
	 */
	public static function get_setting( string $key, $default = null ) {
		$settings = self::get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $new_settings New settings to save.
	 * @return bool
	 */
	public static function update_settings( array $new_settings ): bool {
		$current_settings = self::get_settings();
		$settings         = wp_parse_args( $new_settings, $current_settings );
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Get enabled post types.
	 *
	 * @return array<string>
	 */
	public static function get_enabled_post_types(): array {
		$enabled = self::get_setting( 'enabled_post_types', array( 'post', 'page' ) );
		return is_array( $enabled ) ? $enabled : array();
	}

	/**
	 * Check if a post type is enabled.
	 *
	 * @param string $post_type Post type to check.
	 * @return bool
	 */
	public static function is_post_type_enabled( string $post_type ): bool {
		$enabled = self::get_enabled_post_types();
		return in_array( $post_type, $enabled, true );
	}

	/**
	 * Get available free post types.
	 *
	 * @return array<string>
	 */
	public static function get_free_post_types(): array {
		return array( 'post', 'page' );
	}

	/**
	 * Get available premium post types.
	 *
	 * @return array<string, string>
	 */
	public static function get_premium_post_types(): array {
		$all_post_types = get_post_types( array( 'public' => true ), 'objects' );
		$free_types     = self::get_free_post_types();
		$premium        = array();

		foreach ( $all_post_types as $post_type => $object ) {
			if ( ! in_array( $post_type, $free_types, true ) ) {
				$premium[ $post_type ] = $object->label;
			}
		}

		return $premium;
	}

	/**
	 * Get custom text with fallback to translation.
	 *
	 * @param string $key Setting key.
	 * @param string $default_translation Default translation string.
	 * @return string
	 */
	public static function get_custom_text( string $key, string $default_translation ): string {
		$custom_value = self::get_setting( $key, '' );
		if ( ! empty( trim( $custom_value ) ) ) {
			return $custom_value;
		}
		return $default_translation;
	}

	/**
	 * Get search title (custom or translated).
	 *
	 * @return string
	 */
	public static function get_search_title(): string {
		return self::get_custom_text( 'custom_title', __( 'Search Our Library', 'wp-context-ai-search' ) );
	}

	/**
	 * Get search subtitle (custom or translated).
	 *
	 * @return string
	 */
	public static function get_search_subtitle(): string {
		return self::get_custom_text( 'custom_subtitle', __( 'Ask any question and get intelligent, context-aware answers', 'wp-context-ai-search' ) );
	}

	/**
	 * Get search input placeholder (custom or translated).
	 *
	 * @return string
	 */
	public static function get_search_placeholder(): string {
		return self::get_custom_text( 'custom_placeholder', __( 'Type your question here...', 'wp-context-ai-search' ) );
	}

	/**
	 * Get welcome message (custom or translated).
	 *
	 * @return string
	 */
	public static function get_welcome_message(): string {
		return self::get_custom_text( 'custom_welcome_msg', __( 'Enter your question above to get started.', 'wp-context-ai-search' ) );
	}

	/**
	 * Get contact information.
	 *
	 * @return array{phone: string, address: string}
	 */
	public static function get_contact_info(): array {
		return array(
			'phone' => self::get_setting( 'contact_phone', '' ),
			'address' => self::get_setting( 'contact_address', '' ),
		);
	}

	/**
	 * Get AI API key.
	 *
	 * @return string
	 */
	public static function get_ai_api_key(): string {
		return (string) self::get_setting( 'ai_api_key', '' );
	}

	/**
	 * Get AI provider.
	 *
	 * @return string
	 */
	public static function get_ai_provider(): string {
		return (string) self::get_setting( 'ai_provider', 'openai' );
	}

	/**
	 * Check if API is properly configured.
	 *
	 * @return bool
	 */
	public static function is_api_configured(): bool {
		$api_key = self::get_ai_api_key();
		if ( empty( $api_key ) ) {
			return false;
		}

		// Check cached validation result (valid for 1 hour)
		$cache_key = 'wp_cais_api_valid_' . md5( $api_key );
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			return (bool) $cached;
		}

		// Validate API key
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-ai.php';
		$provider = self::get_ai_provider();
		$validation = WP_CAIS_AI::validate_api_key( $api_key, $provider );

		$is_valid = ! is_wp_error( $validation );
		
		// Cache result for 1 hour
		set_transient( $cache_key, $is_valid ? 1 : 0, HOUR_IN_SECONDS );

		return $is_valid;
	}
}
