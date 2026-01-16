<?php
/**
 * CAIS Templates
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CAIS_Template_Loader_Base' ) ) {
	require_once CAIS_PLUGIN_DIR . 'includes/internal/template-loader.php';
}

/**
 * Template loader for Context AI Search.
 *
 * @package Context_AI_Search
 */
class CAIS_Template_Loader extends CAIS_Template_Loader_Base {
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'cais';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'cais/templates';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_directory = CAIS_PLUGIN_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates';
}
