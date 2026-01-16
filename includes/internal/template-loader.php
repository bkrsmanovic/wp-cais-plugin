<?php
/**
 * Template Loader for Plugins.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CAIS_Template_Loader_Base' ) ) :

	/**
	 * Template loader base class.
	 *
	 * @package CAIS_Template_Loader_Base
	 */
	class CAIS_Template_Loader_Base {
		/**
		 * Prefix for filter names.
		 *
		 * @var string
		 */
		protected $filter_prefix = 'cais';

		/**
		 * Directory name where custom templates for this plugin should be found in the theme.
		 *
		 * @var string
		 */
		protected $theme_template_directory = 'cais/templates';

		/**
		 * Reference to the root directory path of this plugin.
		 *
		 * @var string
		 */
		protected $plugin_directory = CAIS_PLUGIN_DIR;

		/**
		 * Directory name where templates are found in this plugin.
		 *
		 * @var string
		 */
		protected $plugin_template_directory = 'templates';

		/**
		 * Internal use only: Store located template paths.
		 *
		 * @var array
		 */
		private $template_path_cache = array();

		/**
		 * Internal use only: Store variable names used for template data.
		 *
		 * @var array
		 */
		private $template_data_var_names = array( 'data' );

		/**
		 * Clean up template data.
		 */
		public function __destruct() {
			$this->unset_template_data();
		}

		/**
		 * Retrieve a template part.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Optional. Template variation name. Default null.
		 * @param bool   $load Optional. Whether to load template. Default true.
		 * @return string
		 */
		public function get_template_part( $slug, $name = null, $load = true ) {
			// Execute code for this part.
			do_action( 'get_template_part_' . $slug, $slug, $name );
			do_action( $this->filter_prefix . '_get_template_part_' . $slug, $slug, $name );

			// Get files names of templates, for given slug and name.
			$templates = $this->get_template_file_names( $slug, $name );

			// Return the part that is found.
			return $this->locate_template( $templates, $load, false );
		}

		/**
		 * Make custom data available to template.
		 *
		 * @param mixed  $data     Custom data for the template.
		 * @param string $var_name Optional. Variable under which the custom data is available in the template.
		 * @return CAIS_Template_Loader_Base
		 */
		public function set_template_data( $data, $var_name = 'data' ) {
			global $wp_query;

			$wp_query->query_vars[ $var_name ] = (array) $data;

			// Add $var_name to custom variable store if not default value.
			if ( 'data' !== $var_name ) {
				$this->template_data_var_names[] = $var_name;
			}

			return $this;
		}

		/**
		 * Remove access to custom data in template.
		 *
		 * @return CAIS_Template_Loader_Base
		 */
		public function unset_template_data() {
			global $wp_query;

			// Remove any duplicates from the custom variable store.
			$custom_var_names = array_unique( $this->template_data_var_names );

			// Remove each custom data reference from $wp_query.
			foreach ( $custom_var_names as $var ) {
				if ( isset( $wp_query->query_vars[ $var ] ) ) {
					unset( $wp_query->query_vars[ $var ] );
				}
			}

			return $this;
		}

		/**
		 * Given a slug and optional name, create the file names of templates.
		 *
		 * @param string $slug Template slug.
		 * @param string $name Template variation name.
		 * @return array
		 */
		protected function get_template_file_names( $slug, $name ) {
			$templates = array();
			if ( isset( $name ) ) {
				$templates[] = $slug . '-' . $name . '.php';
			}
			$templates[] = $slug . '.php';

			/**
			 * Allow template choices to be filtered.
			 *
			 * @param array  $templates Names of template files that should be looked for, for given slug and name.
			 * @param string $slug      Template slug.
			 * @param string $name      Template variation name.
			 */
			return apply_filters( $this->filter_prefix . '_get_template_part', $templates, $slug, $name );
		}

		/**
		 * Retrieve the name of the highest priority template file that exists.
		 *
		 * @param string|array $template_names Template file(s) to search for, in order.
		 * @param bool         $load           If true the template file will be loaded if it is found.
		 * @param bool         $require_once   Whether to require_once or require. Default true.
		 * @return string The template filename if one is located.
		 */
		public function locate_template( $template_names, $load = false, $require_once = true ) {
			// Use $template_names as a cache key.
			$cache_key = is_array( $template_names ) ? $template_names[0] : $template_names;

			// If the key is in the cache array, we've already located this file.
			if ( isset( $this->template_path_cache[ $cache_key ] ) ) {
				$located = $this->template_path_cache[ $cache_key ];
			} else {
				// No file found yet.
				$located = false;

				// Remove empty entries.
				$template_names = array_filter( (array) $template_names );
				$template_paths = $this->get_template_paths();

				// Try to find a template file.
				foreach ( $template_names as $template_name ) {
					// Trim off any slashes from the template name.
					$template_name = ltrim( $template_name, '/' );

					// Try locating this template file by looping through the template paths.
					foreach ( $template_paths as $template_path ) {
						if ( file_exists( $template_path . $template_name ) ) {
							$located = $template_path . $template_name;
							// Store the template path in the cache.
							$this->template_path_cache[ $cache_key ] = $located;
							break 2;
						}
					}
				}
			}

			if ( $load && $located ) {
				load_template( $located, $require_once );
			}

			return $located;
		}

		/**
		 * Return a list of paths to check for template locations.
		 *
		 * @return array
		 */
		protected function get_template_paths() {
			$theme_directory = trailingslashit( $this->theme_template_directory );

			$file_paths = array(
				10  => trailingslashit( get_template_directory() ) . $theme_directory,
				100 => $this->get_templates_dir(),
			);

			// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
			if ( get_stylesheet_directory() !== get_template_directory() ) {
				$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
			}

			/**
			 * Allow ordered list of template paths to be amended.
			 *
			 * @param array $file_paths Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
			 */
			$file_paths = apply_filters( $this->filter_prefix . '_template_paths', $file_paths );

			// Sort the file paths based on priority.
			ksort( $file_paths, SORT_NUMERIC );

			return array_map( 'trailingslashit', $file_paths );
		}

		/**
		 * Return the path to the templates directory in this plugin.
		 *
		 * @return string
		 */
		protected function get_templates_dir() {
			return trailingslashit( $this->plugin_directory ) . $this->plugin_template_directory;
		}
	}

endif;
