<?php
/**
 * Search form template.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cais-search-wrapper">
	<form id="cais-search-form" class="cais-search-form">
		<div class="cais-input-wrapper">
			<input
				type="text"
				id="cais-query-input"
				class="cais-query-input"
				placeholder="<?php echo esc_attr( CAIS_Settings::get_search_placeholder() ); ?>"
				autocomplete="off"
			/>
			<button type="submit" class="cais-submit-btn" id="cais-submit-btn">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18 2L8 12M18 2L12 18L8 12M18 2L2 8L8 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
	</form>

	<div class="cais-results-container" id="cais-results-container">
		<div class="cais-welcome-message" id="cais-welcome-message">
			<p><?php echo esc_html( CAIS_Settings::get_welcome_message() ); ?></p>
		</div>
	</div>
</div>
