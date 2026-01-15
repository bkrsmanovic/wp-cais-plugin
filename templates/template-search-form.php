<?php
/**
 * Search form template.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-cais-search-wrapper">
	<form id="wp-cais-search-form" class="wp-cais-search-form">
		<div class="wp-cais-input-wrapper">
			<input
				type="text"
				id="wp-cais-query-input"
				class="wp-cais-query-input"
				placeholder="<?php echo esc_attr( WP_CAIS_Settings::get_search_placeholder() ); ?>"
				autocomplete="off"
			/>
			<button type="submit" class="wp-cais-submit-btn" id="wp-cais-submit-btn">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18 2L8 12M18 2L12 18L8 12M18 2L2 8L8 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
	</form>

	<div class="wp-cais-results-container" id="wp-cais-results-container">
		<div class="wp-cais-welcome-message" id="wp-cais-welcome-message">
			<p><?php echo esc_html( WP_CAIS_Settings::get_welcome_message() ); ?></p>
		</div>
	</div>
</div>
