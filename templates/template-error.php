<?php
/**
 * Error message template.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cais-error-message">
	<p><?php esc_html_e( 'WP CAIS not configured properly. Please check your API key settings.', 'context-ai-search' ); ?></p>
</div>
