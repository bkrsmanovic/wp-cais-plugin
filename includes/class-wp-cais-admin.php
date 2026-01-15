<?php
/**
 * Admin functionality.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_CAIS_Admin class.
 */
class WP_CAIS_Admin extends WP_CAIS_Singleton {

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_wp_cais_test_api_key', array( $this, 'test_api_key_ajax' ) );
		add_action( 'wp_ajax_wp_cais_get_quota', array( $this, 'get_quota_ajax' ) );
		add_action( 'wp_ajax_wp_cais_create_table', array( $this, 'create_table_ajax' ) );
		
		// Add plugin action links
		if ( function_exists( 'wp_cais_fs' ) ) {
			// Freemius handles upgrade links automatically
		} else {
			add_filter( 'plugin_action_links_' . WP_CAIS_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=wp-context-ai-search' ) . '">' . __( 'Settings', 'wp-context-ai-search' ) . '</a>';
		$premium_link  = '<a href="' . esc_url( WP_CAIS_PREMIUM_URL ) . '" target="_blank" rel="noopener noreferrer" style="color: #2271b1; font-weight: 600;">' . __( 'Get Premium', 'wp-context-ai-search' ) . '</a>';
		
		array_unshift( $links, $settings_link );
		$links[] = $premium_link;
		
		return $links;
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		// Create the main admin menu page
		// Freemius will hook into this existing menu
		add_menu_page(
			__( 'WP Context AI Search', 'wp-context-ai-search' ),
			__( 'Context AI Search', 'wp-context-ai-search' ),
			'manage_options',
			'wp-context-ai-search',
			array( $this, 'render_settings_page' ),
			'dashicons-search',
			30
		);

		// Add Settings as the first submenu (default page)
		add_submenu_page(
			'wp-context-ai-search',
			__( 'Settings', 'wp-context-ai-search' ),
			__( 'Settings', 'wp-context-ai-search' ),
			'manage_options',
			'wp-context-ai-search',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'wp_cais_settings_group',
			WP_CAIS_Settings::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
		} else {
			$sanitized['enabled_post_types'] = array();
		}

		if ( isset( $input['contact_phone'] ) ) {
			$sanitized['contact_phone'] = sanitize_text_field( $input['contact_phone'] );
		}

		if ( isset( $input['contact_address'] ) ) {
			$sanitized['contact_address'] = sanitize_textarea_field( $input['contact_address'] );
		}

		if ( isset( $input['custom_title'] ) ) {
			$sanitized['custom_title'] = sanitize_text_field( $input['custom_title'] );
		}

		if ( isset( $input['custom_subtitle'] ) ) {
			$sanitized['custom_subtitle'] = sanitize_text_field( $input['custom_subtitle'] );
		}

		if ( isset( $input['custom_placeholder'] ) ) {
			$sanitized['custom_placeholder'] = sanitize_text_field( $input['custom_placeholder'] );
		}

		if ( isset( $input['custom_welcome_msg'] ) ) {
			$sanitized['custom_welcome_msg'] = sanitize_text_field( $input['custom_welcome_msg'] );
		}

		if ( isset( $input['ai_api_key'] ) ) {
			$sanitized['ai_api_key'] = sanitize_text_field( $input['ai_api_key'] );
		}

		if ( isset( $input['ai_provider'] ) ) {
			$sanitized['ai_provider'] = sanitize_text_field( $input['ai_provider'] );
		}


		return $sanitized;
	}

	/**
	 * Handle form submission.
	 */
	private function handle_form_submission() {
		if ( ! isset( $_POST['wp_cais_save_settings'] ) ) {
			return;
		}

		if ( ! check_admin_referer( 'wp_cais_save_settings', 'wp_cais_settings_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$enabled_post_types = isset( $_POST['enabled_post_types'] ) ? (array) $_POST['enabled_post_types'] : array();

		// Validate that only free post types are enabled if not premium.
		$is_premium = function_exists( 'wp_cais_fs' ) ? wp_cais_fs()->can_use_premium_code__premium_only() : false;
		if ( ! $is_premium ) {
			// Filter out premium post types for free users
			$free_types = WP_CAIS_Settings::get_free_post_types();
			$enabled_post_types = array_intersect( $enabled_post_types, $free_types );
		}

		$settings_to_save = array(
			'enabled_post_types' => $enabled_post_types,
		);

		// Contact info
		if ( isset( $_POST['contact_phone'] ) ) {
			$settings_to_save['contact_phone'] = sanitize_text_field( $_POST['contact_phone'] );
		}
		if ( isset( $_POST['contact_address'] ) ) {
			$settings_to_save['contact_address'] = sanitize_textarea_field( $_POST['contact_address'] );
		}

		if ( isset( $_POST['custom_title'] ) ) {
			$settings_to_save['custom_title'] = sanitize_text_field( $_POST['custom_title'] );
		}

		if ( isset( $_POST['custom_subtitle'] ) ) {
			$settings_to_save['custom_subtitle'] = sanitize_text_field( $_POST['custom_subtitle'] );
		}

		if ( isset( $_POST['custom_placeholder'] ) ) {
			$settings_to_save['custom_placeholder'] = sanitize_text_field( $_POST['custom_placeholder'] );
		}

		if ( isset( $_POST['custom_welcome_msg'] ) ) {
			$settings_to_save['custom_welcome_msg'] = sanitize_text_field( $_POST['custom_welcome_msg'] );
		}

		// AI Configuration
		if ( isset( $_POST['ai_api_key'] ) ) {
			$new_api_key = sanitize_text_field( $_POST['ai_api_key'] );
			$current_api_key = WP_CAIS_Settings::get_ai_api_key();
			$provider = isset( $_POST['ai_provider'] ) ? sanitize_text_field( $_POST['ai_provider'] ) : 'openai';
			
			// Validate API key if it's new or changed
			if ( ! empty( $new_api_key ) && $new_api_key !== $current_api_key ) {
				require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-ai.php';
				$validation = WP_CAIS_AI::validate_api_key( $new_api_key, $provider );
				
				if ( is_wp_error( $validation ) ) {
					add_settings_error(
						'wp_cais_settings',
						'invalid_api_key',
						sprintf(
							/* translators: %s: Error message */
							__( 'API Key Validation Failed: %s', 'wp-context-ai-search' ),
							$validation->get_error_message()
						),
						'error'
					);
					// Don't save invalid key - keep the old one
					// Clear the validation cache so it re-checks
					delete_transient( 'wp_cais_api_valid_' . md5( $new_api_key ) );
				} else {
					$settings_to_save['ai_api_key'] = $new_api_key;
					// Clear old cache and set new validation cache
					delete_transient( 'wp_cais_api_valid_' . md5( $current_api_key ) );
					set_transient( 'wp_cais_api_valid_' . md5( $new_api_key ), 1, HOUR_IN_SECONDS );
				}
			} elseif ( ! empty( $new_api_key ) && $new_api_key === $current_api_key ) {
				// Key unchanged, save as is
				$settings_to_save['ai_api_key'] = $new_api_key;
			} else {
				// Empty key - clear cache
				$settings_to_save['ai_api_key'] = '';
				delete_transient( 'wp_cais_api_valid_' . md5( $current_api_key ) );
			}
		}
		if ( isset( $_POST['ai_provider'] ) ) {
			$settings_to_save['ai_provider'] = sanitize_text_field( $_POST['ai_provider'] );
		}


		WP_CAIS_Settings::update_settings( $settings_to_save );

		add_settings_error(
			'wp_cais_settings',
			'wp_cais_settings_saved',
			__( 'Settings saved successfully.', 'wp-context-ai-search' ),
			'success'
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Check if we're on our settings page (works with both Freemius and standard menu)
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
		
		$is_our_page = (
			'settings_page_wp-context-ai-search' === $hook ||
			'toplevel_page_wp-context-ai-search' === $hook ||
			'wp-context-ai-search' === $page ||
			strpos( $hook, 'wp-context-ai-search' ) !== false ||
			strpos( $request_uri, 'page=wp-context-ai-search' ) !== false
		);
		
		if ( ! $is_our_page ) {
			return;
		}

		wp_enqueue_style(
			'wp-cais-admin',
			WP_CAIS_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			WP_CAIS_VERSION
		);

		wp_enqueue_script(
			'wp-cais-admin',
			WP_CAIS_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			WP_CAIS_VERSION,
			true
		);

		wp_localize_script(
			'wp-cais-admin',
			'wpCaisAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wp_cais_test_api_key' ),
				'strings' => array(
					'removeRule' => __( 'Remove', 'wp-context-ai-search' ),
					'addRule' => __( 'Add Custom Rule', 'wp-context-ai-search' ),
					'testing' => __( 'Testing...', 'wp-context-ai-search' ),
					'valid' => __( 'API key is valid!', 'wp-context-ai-search' ),
					'invalid' => __( 'API key is invalid', 'wp-context-ai-search' ),
				),
			)
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		$this->handle_form_submission();

		$settings           = WP_CAIS_Settings::get_settings();
		$enabled_post_types = $settings['enabled_post_types'];
		$is_premium         = function_exists( 'wp_cais_fs' ) ? wp_cais_fs()->can_use_premium_code__premium_only() : false;
		$free_post_types    = WP_CAIS_Settings::get_free_post_types();
		$premium_post_types = WP_CAIS_Settings::get_premium_post_types();
		$premium_features   = WP_CAIS_License::get_premium_features();
		$contact_info       = WP_CAIS_Settings::get_contact_info();
		$custom_title        = WP_CAIS_Settings::get_setting( 'custom_title', '' );
		$custom_subtitle     = WP_CAIS_Settings::get_setting( 'custom_subtitle', '' );
		$custom_placeholder  = WP_CAIS_Settings::get_setting( 'custom_placeholder', '' );
		$custom_welcome_msg  = WP_CAIS_Settings::get_setting( 'custom_welcome_msg', '' );
		$ai_api_key         = WP_CAIS_Settings::get_ai_api_key();
		$ai_provider        = WP_CAIS_Settings::get_ai_provider();
		
		// Check database table status
		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-database.php';
		$table_exists = WP_CAIS_Database::table_exists();
		$table_name = WP_CAIS_Database::get_table_name();

		?>
		<div class="wrap wp-cais-settings" id="wp-cais-settings-wrapper">
			<h1>
				<?php echo esc_html( get_admin_page_title() ); ?>
				<?php if ( ! $is_premium && function_exists( 'wp_cais_fs' ) ) : ?>
					<span class="wp-cais-plan-badge wp-cais-free-badge">
						<?php esc_html_e( 'Free Plan', 'wp-context-ai-search' ); ?>
					</span>
					<?php if ( wp_cais_fs()->is_registered() ) : ?>
						<a href="<?php echo esc_url( wp_cais_fs()->get_upgrade_url() ); ?>" class="button button-primary" style="margin-left: 10px;">
							<?php esc_html_e( 'Upgrade to Premium', 'wp-context-ai-search' ); ?>
						</a>
					<?php endif; ?>
				<?php elseif ( $is_premium ) : ?>
					<span class="wp-cais-plan-badge wp-cais-premium-badge">
						<?php esc_html_e( 'Premium', 'wp-context-ai-search' ); ?>
					</span>
				<?php endif; ?>
			</h1>

			<div class="wp-cais-header">
				<h2><?php esc_html_e( 'WP Context AI Search', 'wp-context-ai-search' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Enable AI-powered search for your WordPress content. Select which content types should be searchable.', 'wp-context-ai-search' ); ?>
				</p>
				<?php if ( ! $is_premium && function_exists( 'wp_cais_fs' ) ) : ?>
					<div class="wp-cais-free-plan-notice notice notice-info" style="margin-top: 15px; padding: 12px;">
						<p style="margin: 0;">
							<strong><?php esc_html_e( 'You are using the Free plan.', 'wp-context-ai-search' ); ?></strong>
							<?php esc_html_e( 'Upgrade to Premium to unlock Custom Post Types. JSON/Markdown files and external data sources coming soon.', 'wp-context-ai-search' ); ?>
							<?php if ( wp_cais_fs()->is_registered() ) : ?>
								<a href="<?php echo esc_url( wp_cais_fs()->get_upgrade_url() ); ?>" class="button button-primary" style="margin-left: 10px;">
									<?php esc_html_e( 'Upgrade Now', 'wp-context-ai-search' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( wp_cais_fs()->get_upgrade_url() ); ?>" class="button button-primary" style="margin-left: 10px;">
									<?php esc_html_e( 'View Pricing', 'wp-context-ai-search' ); ?>
								</a>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>
			</div>

			<?php settings_errors( 'wp_cais_settings' ); ?>

			<div class="wp-cais-section" style="margin-bottom: 20px;">
				<div class="wp-cais-info-box" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'How to Use', 'wp-context-ai-search' ); ?></h3>
					<p style="margin-bottom: 10px;">
						<?php esc_html_e( 'Add the search interface to any page or post using one of the following methods:', 'wp-context-ai-search' ); ?>
					</p>
					<p style="margin-bottom: 5px;">
						<strong><?php esc_html_e( 'Shortcode:', 'wp-context-ai-search' ); ?></strong>
					</p>
					<code style="display: block; background: #fff; padding: 10px; border: 1px solid #c3c4c7; margin-bottom: 15px; font-size: 13px;">
						[wp-context-ai-search]
					</code>
					<p style="margin-bottom: 5px;">
						<strong><?php esc_html_e( 'PHP Function (in theme templates):', 'wp-context-ai-search' ); ?></strong>
					</p>
					<code style="display: block; background: #fff; padding: 10px; border: 1px solid #c3c4c7; font-size: 13px;">
						&lt;?php echo do_shortcode( '[wp-context-ai-search]' ); ?&gt;
					</code>
					<p style="margin-top: 10px; margin-bottom: 0; font-size: 13px; color: #646970;">
						<?php esc_html_e( 'Note: The search interface will only display if your API key is properly configured.', 'wp-context-ai-search' ); ?>
					</p>
				</div>
			</div>

			<div class="wp-cais-content-wrapper">
				<div class="wp-cais-main-content">
					<form method="post" action="">
						<?php wp_nonce_field( 'wp_cais_save_settings', 'wp_cais_settings_nonce' ); ?>

						<div class="wp-cais-section">
							<h2><?php esc_html_e( 'Free Features', 'wp-context-ai-search' ); ?></h2>
							<p class="description">
								<?php esc_html_e( 'These features are available in the free version:', 'wp-context-ai-search' ); ?>
							</p>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Searchable Content', 'wp-context-ai-search' ); ?></th>
										<td>
											<fieldset>
												<?php foreach ( $free_post_types as $post_type ) : ?>
													<?php
													$post_type_obj = get_post_type_object( $post_type );
													$checked       = in_array( $post_type, $enabled_post_types, true ) ? 'checked' : '';
													?>
													<label>
														<input
															type="checkbox"
															name="enabled_post_types[]"
															value="<?php echo esc_attr( $post_type ); ?>"
															<?php echo esc_attr( $checked ); ?>
														>
														<?php echo esc_html( $post_type_obj ? $post_type_obj->label : $post_type ); ?>
													</label><br>
												<?php endforeach; ?>
											</fieldset>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<?php if ( ! empty( $premium_post_types ) || ! $is_premium ) : ?>
							<div class="wp-cais-section wp-cais-premium-section">
								<h2>
									<?php esc_html_e( 'Premium Features', 'wp-context-ai-search' ); ?>
									<?php if ( ! $is_premium ) : ?>
										<span class="wp-cais-badge"><?php esc_html_e( 'Premium', 'wp-context-ai-search' ); ?></span>
									<?php endif; ?>
								</h2>

								<?php if ( ! $is_premium ) : ?>
									<div class="wp-cais-premium-notice">
										<p>
											<strong><?php esc_html_e( 'Upgrade to Premium', 'wp-context-ai-search' ); ?></strong>
										</p>
										<p>
											<?php esc_html_e( 'Unlock advanced search capabilities with premium features:', 'wp-context-ai-search' ); ?>
										</p>
										<ul>
											<?php foreach ( $premium_features as $feature_key => $feature_data ) : ?>
												<?php
												$label = is_array( $feature_data ) ? $feature_data['label'] : $feature_data;
												$available = is_array( $feature_data ) ? $feature_data['available'] : true;
												?>
												<li>
													<?php if ( 'custom_post_types' === $feature_key ) : ?>
														<strong><?php echo esc_html( $label ); ?></strong>
													<?php else : ?>
														<?php echo $available ? esc_html( $label ) : esc_html( $label ) . ' *'; ?>
													<?php endif; ?>
												</li>
											<?php endforeach; ?>
										</ul>
										<p style="margin-top: 10px; font-size: 12px;">
											* <?php esc_html_e( 'Coming soon', 'wp-context-ai-search' ); ?>
										</p>
										<p>
											<?php if ( function_exists( 'wp_cais_fs' ) ) : ?>
												<a href="<?php echo esc_url( wp_cais_fs()->get_upgrade_url() ); ?>" class="button button-primary">
													<?php esc_html_e( 'Get Premium', 'wp-context-ai-search' ); ?>
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( WP_CAIS_PREMIUM_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
													<?php esc_html_e( 'Get Premium', 'wp-context-ai-search' ); ?>
												</a>
											<?php endif; ?>
										</p>
									</div>
								<?php else : ?>
									<?php if ( ! empty( $premium_post_types ) ) : ?>
										<table class="form-table">
											<tbody>
												<tr>
													<th scope="row"><?php esc_html_e( 'Custom Post Types', 'wp-context-ai-search' ); ?></th>
													<td>
														<fieldset>
															<?php foreach ( $premium_post_types as $post_type => $label ) : ?>
																<?php
																$checked = in_array( $post_type, $enabled_post_types, true ) ? 'checked' : '';
																?>
																<label>
																	<input
																		type="checkbox"
																		name="enabled_post_types[]"
																		value="<?php echo esc_attr( $post_type ); ?>"
																		<?php echo esc_attr( $checked ); ?>
																	>
																	<?php echo esc_html( $label ); ?>
																</label><br>
															<?php endforeach; ?>
														</fieldset>
													</td>
												</tr>
											</tbody>
										</table>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="wp-cais-section">
							<h2><?php esc_html_e( 'Search Interface Text', 'wp-context-ai-search' ); ?></h2>
							<p class="description">
								<?php esc_html_e( 'Customize the text displayed in the search interface. Leave empty to use default translations.', 'wp-context-ai-search' ); ?>
							</p>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="custom_title"><?php esc_html_e( 'Search Title', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="text"
												id="custom_title"
												name="custom_title"
												value="<?php echo esc_attr( $custom_title ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g., Search Our Library', 'wp-context-ai-search' ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'Main title displayed at the top of the search interface.', 'wp-context-ai-search' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_subtitle"><?php esc_html_e( 'Subtitle', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="text"
												id="custom_subtitle"
												name="custom_subtitle"
												value="<?php echo esc_attr( $custom_subtitle ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g., Ask any question and get intelligent answers', 'wp-context-ai-search' ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'Subtitle displayed below the main title.', 'wp-context-ai-search' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_placeholder"><?php esc_html_e( 'Input Placeholder', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="text"
												id="custom_placeholder"
												name="custom_placeholder"
												value="<?php echo esc_attr( $custom_placeholder ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g., Type your question here...', 'wp-context-ai-search' ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'Placeholder text shown in the search input field.', 'wp-context-ai-search' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_welcome_msg"><?php esc_html_e( 'Welcome Message', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="text"
												id="custom_welcome_msg"
												name="custom_welcome_msg"
												value="<?php echo esc_attr( $custom_welcome_msg ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g., Enter your question above to get started.', 'wp-context-ai-search' ); ?>"
											/>
											<p class="description">
												<?php esc_html_e( 'Welcome message displayed before the user enters a query.', 'wp-context-ai-search' ); ?>
											</p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="wp-cais-section">
							<h2><?php esc_html_e( 'Contact Information', 'wp-context-ai-search' ); ?></h2>
							<p class="description">
								<?php esc_html_e( 'Contact information displayed in the search interface footer.', 'wp-context-ai-search' ); ?>
							</p>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="contact_phone"><?php esc_html_e( 'Phone Number', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="text"
												id="contact_phone"
												name="contact_phone"
												value="<?php echo esc_attr( $contact_info['phone'] ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g., +1 (555) 123-4567', 'wp-context-ai-search' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="contact_address"><?php esc_html_e( 'Address', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<textarea
												id="contact_address"
												name="contact_address"
												rows="3"
												class="large-text"
												placeholder="<?php esc_attr_e( 'e.g., 123 Main St, City, State 12345', 'wp-context-ai-search' ); ?>"
											><?php echo esc_textarea( $contact_info['address'] ); ?></textarea>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="wp-cais-section">
							<h2><?php esc_html_e( 'AI Configuration', 'wp-context-ai-search' ); ?></h2>
							<p class="description">
								<?php esc_html_e( 'Configure your AI service provider and API key.', 'wp-context-ai-search' ); ?>
							</p>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="ai_provider"><?php esc_html_e( 'AI Provider', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<select id="ai_provider" name="ai_provider">
												<option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>
													<?php esc_html_e( 'OpenAI (GPT-3.5 Turbo)', 'wp-context-ai-search' ); ?>
												</option>
												<option value="claude" <?php selected( $ai_provider, 'claude' ); ?>>
													<?php esc_html_e( 'Claude (Anthropic)', 'wp-context-ai-search' ); ?>
												</option>
												<option value="gemini" <?php selected( $ai_provider, 'gemini' ); ?>>
													<?php esc_html_e( 'Gemini (Google)', 'wp-context-ai-search' ); ?>
												</option>
												<option value="huggingface" <?php selected( $ai_provider, 'huggingface' ); ?>>
													<?php esc_html_e( 'HuggingFace (Coming Soon)', 'wp-context-ai-search' ); ?>
												</option>
											</select>
											<p class="description">
												<?php esc_html_e( 'Select your AI service provider. All providers require an API key.', 'wp-context-ai-search' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="ai_api_key"><?php esc_html_e( 'API Key', 'wp-context-ai-search' ); ?></label>
										</th>
										<td>
											<input
												type="password"
												id="ai_api_key"
												name="ai_api_key"
												value="<?php echo esc_attr( $ai_api_key ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'Enter your API key', 'wp-context-ai-search' ); ?>"
											/>
											<p class="description">
												<span id="wp-cais-api-key-link">
													<?php
													$provider_links = array(
														'openai' => '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>',
														'claude' => '<a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic</a>',
														'gemini' => '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>',
													);
													$current_link = isset( $provider_links[ $ai_provider ] ) ? $provider_links[ $ai_provider ] : $provider_links['openai'];
													printf(
														/* translators: %s: Link to provider */
														esc_html__( 'Get your API key from %s', 'wp-context-ai-search' ),
														$current_link
													);
													?>
												</span>
											</p>
											<p>
												<button type="button" class="button wp-cais-test-api-key" id="wp-cais-test-api-key">
													<?php esc_html_e( 'Test API Key', 'wp-context-ai-search' ); ?>
												</button>
												<span id="wp-cais-api-key-status" class="wp-cais-api-key-status"></span>
											</p>
											<div id="wp-cais-quota-info" class="wp-cais-quota-info" style="margin-top: 15px; padding: 12px; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;">
												<p style="margin: 0 0 8px 0; font-weight: 600;">
													<?php esc_html_e( 'API Quota Information', 'wp-context-ai-search' ); ?>
												</p>
												<div id="wp-cais-quota-content">
													<p style="margin: 0; color: #646970;">
														<?php esc_html_e( 'Loading quota information...', 'wp-context-ai-search' ); ?>
													</p>
												</div>
											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="wp-cais-section">
							<h2><?php esc_html_e( 'Database Status', 'wp-context-ai-search' ); ?></h2>
							<p class="description">
								<?php esc_html_e( 'Cache table status and management.', 'wp-context-ai-search' ); ?>
							</p>

							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Cache Table', 'wp-context-ai-search' ); ?></th>
										<td>
											<?php if ( $table_exists ) : ?>
												<p style="color: green; margin: 0;">
													✓ <?php esc_html_e( 'Table exists', 'wp-context-ai-search' ); ?>: <code><?php echo esc_html( $table_name ); ?></code>
												</p>
												<?php
												global $wpdb;
												$cache_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
												?>
												<p class="description" style="margin-top: 8px;">
													<?php
													printf(
														/* translators: %d: Number of cached entries */
														esc_html__( 'Currently %d cached entries.', 'wp-context-ai-search' ),
														(int) $cache_count
													);
													?>
												</p>
											<?php else : ?>
												<p style="color: #d63638; margin: 0;">
													✗ <?php esc_html_e( 'Table does not exist', 'wp-context-ai-search' ); ?>: <code><?php echo esc_html( $table_name ); ?></code>
												</p>
												<p class="description" style="margin-top: 8px;">
													<?php esc_html_e( 'The cache table is required for storing search results. Click the button below to create it.', 'wp-context-ai-search' ); ?>
												</p>
												<p style="margin-top: 12px;">
													<button type="button" class="button button-secondary" id="wp-cais-create-table">
														<?php esc_html_e( 'Create Cache Table', 'wp-context-ai-search' ); ?>
													</button>
													<span id="wp-cais-table-status" class="wp-cais-api-key-status" style="margin-left: 10px;"></span>
												</p>
											<?php endif; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<?php submit_button( __( 'Save Settings', 'wp-context-ai-search' ), 'primary', 'wp_cais_save_settings' ); ?>
					</form>
				</div>

				<div class="wp-cais-sidebar">
					<?php if ( ! $is_premium ) : ?>
						<div class="wp-cais-info-box wp-cais-premium-box">
							<h3><?php esc_html_e( 'Upgrade to Premium', 'wp-context-ai-search' ); ?></h3>
							<p style="margin-bottom: 15px;">
								<?php esc_html_e( 'Unlock advanced search capabilities with premium features:', 'wp-context-ai-search' ); ?>
							</p>
							<ul style="margin-bottom: 20px;">
								<?php foreach ( $premium_features as $feature_key => $feature_data ) : ?>
									<?php
									$label = is_array( $feature_data ) ? $feature_data['label'] : $feature_data;
									$available = is_array( $feature_data ) ? $feature_data['available'] : true;
									?>
									<li style="margin-bottom: 8px;">
										<?php if ( 'custom_post_types' === $feature_key ) : ?>
											<strong><?php echo esc_html( $label ); ?></strong>
										<?php else : ?>
											<?php echo $available ? esc_html( $label ) : esc_html( $label ) . ' *'; ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<p style="margin-top: 10px; font-size: 12px; color: rgba(255, 255, 255, 0.8);">
								* <?php esc_html_e( 'Coming soon', 'wp-context-ai-search' ); ?>
							</p>
							<p style="margin: 0; text-align: center;">
								<?php if ( function_exists( 'wp_cais_fs' ) ) : ?>
									<a href="<?php echo esc_url( wp_cais_fs()->get_upgrade_url() ); ?>" class="button button-primary" style="width: 100%; text-align: center; font-weight: 600;">
										<?php esc_html_e( 'Upgrade Now', 'wp-context-ai-search' ); ?>
									</a>
								<?php else : ?>
									<a href="<?php echo esc_url( WP_CAIS_PREMIUM_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 100%; text-align: center; font-weight: 600;">
										<?php esc_html_e( 'Upgrade Now', 'wp-context-ai-search' ); ?>
									</a>
								<?php endif; ?>
							</p>
						</div>
					<?php endif; ?>

					<div class="wp-cais-info-box">
						<h3><?php esc_html_e( 'About WP Context AI Search', 'wp-context-ai-search' ); ?></h3>
						<p>
							<?php esc_html_e( 'WP Context AI Search provides intelligent, context-aware search results powered by AI. It analyzes your WordPress content to deliver relevant search results.', 'wp-context-ai-search' ); ?>
						</p>
					</div>

					<div class="wp-cais-info-box">
						<h3><?php esc_html_e( 'Free Version', 'wp-context-ai-search' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Search Posts', 'wp-context-ai-search' ); ?></li>
							<li><?php esc_html_e( 'Search Pages', 'wp-context-ai-search' ); ?></li>
							<li><?php esc_html_e( 'AI-powered context matching', 'wp-context-ai-search' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Test API key via AJAX.
	 */
	public function test_api_key_ajax() {
		check_ajax_referer( 'wp_cais_test_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-context-ai-search' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : 'openai';

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is empty.', 'wp-context-ai-search' ) ) );
		}

		// Clear any cached validation for this key
		delete_transient( 'wp_cais_api_valid_' . md5( $api_key ) );

		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-ai.php';
		$validation = WP_CAIS_AI::validate_api_key( $api_key, $provider );

		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
		}

		// Cache the valid result
		set_transient( 'wp_cais_api_valid_' . md5( $api_key ), 1, HOUR_IN_SECONDS );

		// Also get quota info
		$quota = WP_CAIS_AI::get_api_quota( $api_key, $provider );
		$quota_data = is_wp_error( $quota ) ? null : $quota;

		wp_send_json_success( array(
			'message' => __( 'API key is valid!', 'wp-context-ai-search' ),
			'quota' => $quota_data,
		) );
	}

	/**
	 * Get quota information via AJAX.
	 */
	public function get_quota_ajax() {
		check_ajax_referer( 'wp_cais_test_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-context-ai-search' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : 'openai';

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is empty.', 'wp-context-ai-search' ) ) );
		}

		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-ai.php';
		$quota = WP_CAIS_AI::get_api_quota( $api_key, $provider );

		if ( is_wp_error( $quota ) ) {
			wp_send_json_error( array( 'message' => $quota->get_error_message() ) );
		}

		wp_send_json_success( array( 'quota' => $quota ) );
	}

	/**
	 * Create database table via AJAX.
	 */
	public function create_table_ajax() {
		check_ajax_referer( 'wp_cais_test_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-context-ai-search' ) ) );
		}

		require_once WP_CAIS_PLUGIN_DIR . 'includes/class-wp-cais-database.php';
		
		$result = WP_CAIS_Database::create_table();
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		if ( WP_CAIS_Database::table_exists() ) {
			$table_name = WP_CAIS_Database::get_table_name();
			wp_send_json_success( array(
				'message' => __( 'Table created successfully!', 'wp-context-ai-search' ),
				'table_name' => $table_name,
			) );
		}
		
		wp_send_json_error( array( 'message' => __( 'Failed to create table. Please check database permissions.', 'wp-context-ai-search' ) ) );
	}

	/**
	 * Add premium link to plugin row meta.
	 *
	 * @param array  $links Existing links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public function add_plugin_row_meta( $links, $file ) {
		if ( WP_CAIS_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$premium_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" style="color: #ff9800; font-weight: 600;">%s</a>',
			function_exists( 'wp_cais_fs' ) ? esc_url( wp_cais_fs()->get_upgrade_url() ) : esc_url( WP_CAIS_PREMIUM_URL ),
			esc_html__( 'Get Premium', 'wp-context-ai-search' )
		);

		array_unshift( $links, $premium_link );

		return $links;
	}
}
