<?php
/**
 * Frontend functionality.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_CAIS_Frontend class.
 */
class WP_CAIS_Frontend extends WP_CAIS_Singleton {

	/**
	 * Template loader instance.
	 *
	 * @var WP_CAIS_Template_Loader
	 */
	private $template_loader = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		require_once WP_CAIS_PLUGIN_DIR . 'includes/templates.php';
		$this->template_loader = new WP_CAIS_Template_Loader();

		add_shortcode( 'wp-context-ai-search', array( $this, 'render_search_interface' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wp_cais_search', array( $this, 'handle_search_ajax' ) );
		add_action( 'wp_ajax_nopriv_wp_cais_search', array( $this, 'handle_search_ajax' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_search_page() ) {
			return;
		}

		// Enqueue main stylesheet
		wp_enqueue_style(
			'wp-cais-frontend',
			WP_CAIS_PLUGIN_URL . 'public/css/frontend.css',
			array(),
			WP_CAIS_VERSION
		);

		// Enqueue RTL stylesheet if needed
		if ( is_rtl() ) {
			wp_enqueue_style(
				'wp-cais-frontend-rtl',
				WP_CAIS_PLUGIN_URL . 'public/css/frontend-rtl.css',
				array( 'wp-cais-frontend' ),
				WP_CAIS_VERSION
			);
		}

		wp_enqueue_script(
			'wp-cais-frontend',
			WP_CAIS_PLUGIN_URL . 'public/js/frontend.js',
			array( 'jquery' ),
			WP_CAIS_VERSION,
			true
		);

		wp_localize_script(
			'wp-cais-frontend',
			'wpCais',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wp_cais_search_nonce' ),
				'strings' => array(
					'searching' => __( 'Searching...', 'wp-context-ai-search' ),
					'error' => __( 'An error occurred. Please try again.', 'wp-context-ai-search' ),
				),
			)
		);
	}

	/**
	 * Check if current page has search interface.
	 *
	 * @return bool
	 */
	private function is_search_page() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'wp-context-ai-search' );
	}

	/**
	 * Render search interface shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_search_interface( $atts = array() ) {
		$contact_info = WP_CAIS_Settings::get_contact_info();
		$is_configured = WP_CAIS_Settings::is_api_configured();

		$data = array(
			'contact_info' => $contact_info,
			'is_configured' => $is_configured,
		);

		ob_start();
		$this->shortcode_render( 'search', $data );
		return ob_get_clean();
	}

	/**
	 * Render shortcode template.
	 *
	 * @param string $template Template name.
	 * @param array  $data     Template data.
	 */
	public function shortcode_render( $template, $data = array() ) {
		if ( ! $template ) {
			return;
		}

		$this->template_loader
			->set_template_data( $data )
			->get_template_part( 'template', $template );
	}

	/**
	 * Handle AJAX search request.
	 */
	public function handle_search_ajax() {
		check_ajax_referer( 'wp_cais_search_nonce', 'nonce' );

		// Validate and sanitize input
		if ( ! isset( $_POST['query'] ) || ! is_string( $_POST['query'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid query parameter.', 'wp-context-ai-search' ) ) );
		}

		$query = sanitize_text_field( wp_unslash( $_POST['query'] ) );
		$query = trim( $query );

		// Validate query length
		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a search query.', 'wp-context-ai-search' ) ) );
		}

		$min_length = defined( 'WP_CAIS_MIN_QUERY_LENGTH' ) ? (int) WP_CAIS_MIN_QUERY_LENGTH : 2;
		$max_length = defined( 'WP_CAIS_MAX_QUERY_LENGTH' ) ? (int) WP_CAIS_MAX_QUERY_LENGTH : 500;

		if ( strlen( $query ) < $min_length ) {
			wp_send_json_error( array( 
				'message' => sprintf( 
					/* translators: %d: Minimum query length */
					__( 'Query must be at least %d characters long.', 'wp-context-ai-search' ),
					$min_length
				)
			) );
		}

		if ( strlen( $query ) > $max_length ) {
			wp_send_json_error( array( 
				'message' => sprintf( 
					/* translators: %d: Maximum query length */
					__( 'Query must be no more than %d characters long.', 'wp-context-ai-search' ),
					$max_length
				)
			) );
		}

		// Check if API is configured
		if ( ! WP_CAIS_Settings::is_api_configured() ) {
			wp_send_json_error( array( 'message' => __( 'WP CAIS not configured properly. Please check your API key settings.', 'wp-context-ai-search' ) ) );
		}

		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-search.php';
		$search_handler = new WP_CAIS_Search();
		$result = $search_handler->process_query( $query );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}
}
