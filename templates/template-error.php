<?php
/**
 * Error message template.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wp-cais-error-message">
	<p><?php esc_html_e( 'WP CAIS not configured properly. Please check your API key settings.', 'wp-context-ai-search' ); ?></p>
</div>
