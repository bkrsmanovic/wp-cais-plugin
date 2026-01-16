<?php
/**
 * License and premium features management.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CAIS_License class.
 */
class CAIS_License {

	/**
	 * Check if premium features are available.
	 *
	 * @return bool
	 */
	public static function is_premium() {
		if ( ! function_exists( 'cais_fs' ) ) {
			return false;
		}

		return cais_fs()->can_use_premium_code__premium_only();
	}

	/**
	 * Get license key.
	 *
	 * @return string
	 */
	public static function get_license_key() {
		if ( ! function_exists( 'cais_fs' ) ) {
			return '';
		}

		$license = cais_fs()->_get_license();
		return $license ? $license->secret_key : '';
	}

	/**
	 * Check if a feature is premium-only.
	 *
	 * @param string $feature Feature name to check.
	 * @return bool
	 */
	public static function is_premium_feature( $feature ) {
		$premium_features = array(
			'custom_post_types',
			'json_files',
			'markdown_files',
			'external_files',
			'excel_files',
		);

		return in_array( $feature, $premium_features, true );
	}

	/**
	 * Check if user has access to a feature.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public static function has_access( $feature ) {
		if ( ! self::is_premium_feature( $feature ) ) {
			return true;
		}

		return self::is_premium();
	}

	/**
	 * Get premium features list.
	 *
	 * @return array
	 */
	public static function get_premium_features() {
		return array(
			'custom_post_types' => array(
				'label' => __( 'Custom Post Types', 'context-ai-search' ),
				'available' => true,
			),
			'json_files' => array(
				'label' => __( 'JSON Files', 'context-ai-search' ),
				'available' => false,
			),
			'markdown_files' => array(
				'label' => __( 'Markdown Files', 'context-ai-search' ),
				'available' => false,
			),
			'external_files' => array(
				'label' => __( 'External Files', 'context-ai-search' ),
				'available' => false,
			),
			'excel_files' => array(
				'label' => __( 'Excel/Spreadsheet Files', 'context-ai-search' ),
				'available' => false,
			),
		);
	}
}
