<?php
/**
 * Plugin Name: Context AI Search
 * Plugin URI: https://github.com/bkrsmanovic/cais-plugin
 * Description: AI-powered search for WordPress content with free and premium features. Search your site's posts, pages, and more with intelligent context-aware results powered by OpenAI, Claude, or Gemini.
 * Version: 1.0.1
 * Update URI: https://api.freemius.com
 * Author: Bojan Krsmanovic
 * Author URI: https://deeq.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: context-ai-search
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @fs_premium_only /includes/class-cais-license.php
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Freemius SDK integration - check if already loaded
if ( function_exists( 'cais_fs' ) ) {
	cais_fs()->set_basename( true, __FILE__ );
} else {
	/**
	 * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
	 * `function_exists` CALL ABOVE TO PROPERLY WORK.
	 */
	if ( ! function_exists( 'cais_fs' ) ) {
		/**
		 * Create a helper function for easy SDK access.
		 *
		 * @return Freemius
		 */
		function cais_fs() {
			global $cais_fs;

			if ( ! isset( $cais_fs ) ) {
				// Include Freemius SDK.
				require_once dirname( __FILE__ ) . '/freemius/start.php';

				$cais_fs = fs_dynamic_init( array(
					'id'                  => '23024',
					'slug'                => 'context-ai-search',
					'type'                => 'plugin',
					'public_key'          => 'pk_bb0c2d4d32815a76add42a2b03bd9',
					'is_premium'          => true,
					'premium_suffix'      => 'Premium',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					// Automatically removed in the free version. If you're not using the
					// auto-generated free version, delete this line before uploading to wp.org.
					'menu'                => array(
						'slug'           => 'context-ai-search',
						'support'        => false,
					),
				) );
			}

			return $cais_fs;
		}

		// Init Freemius.
		cais_fs();
		
		// For WordPress.org hosted free version, prevent Freemius updater from hooking into update system.
		// WordPress.org plugins must not use custom updaters - updates come from WordPress.org.
		if ( function_exists( 'cais_fs' ) ) {
			$fs = cais_fs();
			// Only for free version (WordPress.org hosted).
			if ( ! $fs->is_premium() ) {
				// Remove updater filters after Freemius fully initializes.
				// Use 'wp_loaded' to ensure Freemius has completed initialization.
				add_action( 'wp_loaded', function() use ( $fs ) {
					if ( class_exists( 'FS_Plugin_Updater' ) ) {
						$updater = FS_Plugin_Updater::instance( $fs );
						if ( $updater && is_object( $updater ) ) {
							// Remove the update transient filters to comply with WordPress.org guidelines.
							remove_filter( 'pre_set_site_transient_update_plugins', array( 
								$updater, 
								'pre_set_site_transient_update_plugins_filter' 
							) );
							remove_filter( 'pre_set_site_transient_update_themes', array( 
								$updater, 
								'pre_set_site_transient_update_plugins_filter' 
							) );
						}
					}
				}, 999 );
			}
		}
		
		// Signal that SDK was initiated.
		do_action( 'cais_fs_loaded' );
	}
}

// Define plugin constants.
define( 'CAIS_VERSION', '1.0.1' );
define( 'CAIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CAIS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CAIS_PLUGIN_FILE', __FILE__ );

// Premium purchase link - can be overridden via environment variable.
if ( ! defined( 'CAIS_PREMIUM_URL' ) ) {
	define( 'CAIS_PREMIUM_URL', getenv( 'CAIS_PREMIUM_URL' ) ?: 'https://deeq.io/cais-premium' );
}

/**
 * Main plugin class.
 */
final class Context_AI_Search {

	/**
	 * Plugin instance.
	 *
	 * @var Context_AI_Search
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Context_AI_Search
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 */
	private function init() {
		// Note: load_plugin_textdomain() is not needed for WordPress.org hosted plugins.
		// WordPress automatically loads translations for plugins hosted on WordPress.org.

		// Load includes.
		$this->load_includes();

		// Initialize admin.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize frontend.
		$this->init_frontend();

		// Activation hook.
		register_activation_hook( CAIS_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		
		// Deactivation hook.
		register_deactivation_hook( CAIS_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Load plugin includes.
	 */
	private function load_includes() {
		require_once CAIS_PLUGIN_DIR . 'includes/internal/singleton.php';
		require_once CAIS_PLUGIN_DIR . 'includes/constants.php';
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-settings.php';
		
		// Load license class only if Freemius is available
		if ( function_exists( 'cais_fs' ) ) {
			require_once CAIS_PLUGIN_DIR . 'includes/class-cais-license.php';
		}
		
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-database.php';
		
		// Ensure database table exists on every load
		CAIS_Database::ensure_table_exists();
	}

	/**
	 * Initialize frontend.
	 */
	private function init_frontend() {
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-frontend.php';
		CAIS_Frontend::instance();
	}

	/**
	 * Initialize admin functionality.
	 */
	private function init_admin() {
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-admin.php';
		CAIS_Admin::instance();
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-settings.php';
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-database.php';
		
		CAIS_Database::create_table();
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-database.php';
		// Optionally clean old cache
		CAIS_Database::clean_old_cache( 30 );
	}
}

/**
 * Initialize plugin.
 */
function cais_init() {
	return Context_AI_Search::get_instance();
}

// Start the plugin.
cais_init();
