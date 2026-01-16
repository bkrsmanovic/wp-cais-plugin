<?php
/**
 * Search interface template.
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
$is_configured = isset( $data['is_configured'] ) ? $data['is_configured'] : false;

// Set RTL direction for RTL languages
$is_rtl = is_rtl();
$dir_attr = $is_rtl ? 'dir="rtl"' : '';
?>
<div class="cais-search-container" id="cais-search-container" <?php echo $dir_attr; ?>>
	<div class="cais-header-section">
		<h1 class="cais-title"><?php echo esc_html( CAIS_Settings::get_search_title() ); ?></h1>
		<p class="cais-subtitle"><?php echo esc_html( CAIS_Settings::get_search_subtitle() ); ?></p>
	</div>

	<?php if ( ! $is_configured ) : ?>
		<?php
		// Load error template.
		$template_loader = new CAIS_Template_Loader();
		$template_loader->get_template_part( 'template', 'error' );
		?>
	<?php else : ?>
		<?php
		// Load search form template.
		$template_loader = new CAIS_Template_Loader();
		$template_loader->get_template_part( 'template', 'search-form' );
		?>
	<?php endif; ?>

	<?php if ( ! empty( $contact_info['phone'] ) || ! empty( $contact_info['address'] ) ) : ?>
		<?php
		// Load footer template.
		$template_loader = new CAIS_Template_Loader();
		$template_loader->set_template_data( array( 'contact_info' => $contact_info ) )->get_template_part( 'template', 'footer' );
		?>
	<?php endif; ?>
</div>
