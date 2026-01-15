<?php
/**
 * WP CAIS Templates
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_CAIS_Template_Loader_Base' ) ) {
	require_once WP_CAIS_PLUGIN_DIR . 'includes/internal/template-loader.php';
}

/**
 * Template loader for WP Context AI Search.
 *
 * @package WP_Context_AI_Search
 */
class WP_CAIS_Template_Loader extends WP_CAIS_Template_Loader_Base {
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'wp_cais';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'wp-cais/templates';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_directory = WP_CAIS_PLUGIN_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates';
}
