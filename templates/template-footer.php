<?php
/**
 * Footer template.
 *
 * @package Context_AI_Search
 *
 * @var array $data Template data.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;
$data = isset( $wp_query->query_vars['data'] ) ? $wp_query->query_vars['data'] : array();
$contact_info = isset( $data['contact_info'] ) ? $data['contact_info'] : array();
?>
<div class="cais-footer">
	<?php if ( ! empty( $contact_info['phone'] ) || ! empty( $contact_info['address'] ) ) : ?>
		<p class="cais-footer-text">
			<?php if ( ! empty( $contact_info['phone'] ) ) : ?>
				<?php esc_html_e( 'Need more help?', 'context-ai-search' ); ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_info['phone'] ) ); ?>" class="cais-contact-link">
					<?php echo esc_html( $contact_info['phone'] ); ?>
				</a>
			<?php endif; ?>
		</p>
		<?php if ( ! empty( $contact_info['address'] ) ) : ?>
			<p class="cais-footer-address">
				<?php echo esc_html( $contact_info['address'] ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
