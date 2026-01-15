<?php
/**
 * Plugin Name: WP Context AI Search
 * Plugin URI: https://github.com/bkrsmanovic/wp-cais-plugin
 * Description: AI-powered search for WordPress content with free and premium features. Search your site's posts, pages, and more with intelligent context-aware results powered by OpenAI, Claude, or Gemini.
 * Version: 1.0.0
 * Update URI: https://api.freemius.com
 * Author: Bojan Krsmanovic
 * Author URI: https://deeq.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-context-ai-search
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Network: false
 *
 * @fs_premium_only /includes/class-wp-cais-license.php
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Freemius SDK integration - check if already loaded
if ( function_exists( 'wp_cais_fs' ) ) {
	wp_cais_fs()->set_basename( true, __FILE__ );
} else {
	/**
	 * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
	 * `function_exists` CALL ABOVE TO PROPERLY WORK.
	 */
	if ( ! function_exists( 'wp_cais_fs' ) ) {
		/**
		 * Create a helper function for easy SDK access.
		 *
		 * @return Freemius
		 */
		function wp_cais_fs() {
			global $wp_cais_fs;

			if ( ! isset( $wp_cais_fs ) ) {
				// Include Freemius SDK.
				require_once dirname( __FILE__ ) . '/freemius/start.php';

				$wp_cais_fs = fs_dynamic_init( array(
					'id'                  => '23024',
					'slug'                => 'wp-context-ai-search',
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
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'menu'                => array(
						'slug'           => 'wp-context-ai-search',
						'support'        => false,
					),
				) );
			}

			return $wp_cais_fs;
		}

		// Init Freemius.
		wp_cais_fs();
		// Signal that SDK was initiated.
		do_action( 'wp_cais_fs_loaded' );
	}
}

// Define plugin constants.
define( 'WP_CAIS_VERSION', '1.0.0' );
define( 'WP_CAIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_CAIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_CAIS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WP_CAIS_PLUGIN_FILE', __FILE__ );

// Premium purchase link - can be overridden via environment variable.
if ( ! defined( 'WP_CAIS_PREMIUM_URL' ) ) {
	define( 'WP_CAIS_PREMIUM_URL', getenv( 'WP_CAIS_PREMIUM_URL' ) ?: 'https://deeq.io/wp-cais-premium' );
}

/**
 * Main plugin class.
 */
final class WP_Context_AI_Search {

	/**
	 * Plugin instance.
	 *
	 * @var WP_Context_AI_Search
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return WP_Context_AI_Search
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
		// Load plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Load includes.
		$this->load_includes();

		// Initialize admin.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize frontend.
		$this->init_frontend();

		// Activation hook.
		register_activation_hook( WP_CAIS_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		
		// Deactivation hook.
		register_deactivation_hook( WP_CAIS_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-context-ai-search',
			false,
			dirname( WP_CAIS_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Load plugin includes.
	 */
	private function load_includes() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/internal/singleton.php';
		require_once WP_CAIS_PLUGIN_DIR . 'includes/constants.php';
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-settings.php';
		
		// Load license class only if Freemius is available
		if ( function_exists( 'wp_cais_fs' ) ) {
			require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-license.php';
		}
		
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-database.php';
		
		// Ensure database table exists on every load
		WP_CAIS_Database::ensure_table_exists();
	}

	/**
	 * Initialize frontend.
	 */
	private function init_frontend() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-frontend.php';
		WP_CAIS_Frontend::instance();
	}

	/**
	 * Initialize admin functionality.
	 */
	private function init_admin() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-admin.php';
		WP_CAIS_Admin::instance();
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-settings.php';
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-database.php';
		
		WP_CAIS_Database::create_table();
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-database.php';
		// Optionally clean old cache
		WP_CAIS_Database::clean_old_cache( 30 );
	}
}

/**
 * Initialize plugin.
 */
function wp_cais_init() {
	return WP_Context_AI_Search::get_instance();
}

// Start the plugin.
wp_cais_init();
