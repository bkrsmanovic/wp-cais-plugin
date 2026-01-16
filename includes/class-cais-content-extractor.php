<?php
/**
 * Content extraction for posts.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CAIS_Content_Extractor class.
 */
class CAIS_Content_Extractor {

	/**
	 * Extract all content from a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function extract_content( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$content_parts = array();

		// Title
		$content_parts[] = get_the_title( $post_id );

		// Main content
		$content_parts[] = self::extract_post_content( $post );

		// Gutenberg blocks
		$content_parts[] = self::extract_gutenberg_blocks( $post->post_content );

		// ACF fields
		if ( function_exists( 'get_fields' ) ) {
			$content_parts[] = self::extract_acf_fields( $post_id );
		}

		// ACF blocks
		if ( function_exists( 'get_fields' ) ) {
			$content_parts[] = self::extract_acf_blocks( $post->post_content );
		}

		// Excerpt - decode immediately
		$excerpt = get_the_excerpt( $post_id );
		if ( ! empty( $excerpt ) ) {
			$excerpt = html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$content_parts[] = $excerpt;
		}

		$combined = implode( "\n\n", array_filter( $content_parts ) );
		
		// Decode HTML entities one more time to catch any that might have been re-encoded
		$combined = html_entity_decode( $combined, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		return $combined;
	}

	/**
	 * Extract post content.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private static function extract_post_content( $post ) {
		$content = $post->post_content;
		
		// Remove HTML tags but keep text
		$content = wp_strip_all_tags( $content );
		
		// Decode HTML entities
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		// Clean up extra whitespace
		$content = preg_replace( '/\s+/', ' ', $content );
		
		return trim( $content );
	}

	/**
	 * Extract Gutenberg blocks content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	private static function extract_gutenberg_blocks( $content ) {
		if ( ! has_blocks( $content ) ) {
			return '';
		}

		$blocks = parse_blocks( $content );
		$text_content = array();

		foreach ( $blocks as $block ) {
			$block_text = self::extract_block_text( $block );
			if ( ! empty( $block_text ) ) {
				$text_content[] = $block_text;
			}
		}

		return implode( "\n", array_filter( $text_content ) );
	}

	/**
	 * Extract text from a single block.
	 *
	 * @param array $block Block data.
	 * @return string
	 */
	private static function extract_block_text( $block ) {
		$text = '';

		// Extract from innerHTML
		if ( isset( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
			// Remove block comments
			$html = preg_replace( '/<!--.*?-->/s', '', $html );
			$text .= wp_strip_all_tags( $html );
			// Decode HTML entities
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		// Extract from innerContent
		if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
			foreach ( $block['innerContent'] as $content ) {
				if ( is_string( $content ) ) {
					$text .= ' ' . wp_strip_all_tags( $content );
				}
			}
		}

		// Extract from attributes (for blocks that store content in attrs)
		if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
			foreach ( $block['attrs'] as $key => $value ) {
				if ( is_string( $value ) && ! empty( $value ) ) {
					// Common attribute keys that contain text
					if ( in_array( $key, array( 'content', 'text', 'caption', 'description', 'title', 'heading' ), true ) ) {
						$text .= ' ' . $value;
					}
				} elseif ( is_array( $value ) ) {
					// Handle nested arrays (like columns, lists, etc.)
					$text .= ' ' . self::extract_array_text( $value );
				}
			}
		}

		// Process inner blocks recursively
		if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$text .= ' ' . self::extract_block_text( $inner_block );
			}
		}

		return trim( $text );
	}

	/**
	 * Extract text from array recursively.
	 *
	 * @param array $array Array to extract from.
	 * @return string
	 */
	private static function extract_array_text( $array ) {
		$text = '';
		foreach ( $array as $value ) {
			if ( is_string( $value ) ) {
				$text .= ' ' . $value;
			} elseif ( is_array( $value ) ) {
				$text .= ' ' . self::extract_array_text( $value );
			}
		}
		return trim( $text );
	}

	/**
	 * Extract ACF fields.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function extract_acf_fields( $post_id ) {
		$fields = get_fields( $post_id );
		if ( ! $fields || ! is_array( $fields ) ) {
			return '';
		}

		$text_content = array();
		foreach ( $fields as $field_name => $field_value ) {
			$field_obj = get_field_object( $field_name, $post_id );
			
			if ( ! $field_obj ) {
				continue;
			}

			$label = isset( $field_obj['label'] ) ? $field_obj['label'] : $field_name;
			$value = self::extract_acf_field_value( $field_value, $field_obj );

			if ( ! empty( $value ) ) {
				$text_content[] = $label . ': ' . $value;
			}
		}

		return implode( "\n", $text_content );
	}

	/**
	 * Extract ACF field value.
	 *
	 * @param mixed $value Field value.
	 * @param array $field_obj Field object.
	 * @return string
	 */
	private static function extract_acf_field_value( $value, $field_obj ) {
		if ( empty( $value ) ) {
			return '';
		}

		$field_type = isset( $field_obj['type'] ) ? $field_obj['type'] : '';

		switch ( $field_type ) {
			case 'text':
			case 'textarea':
			case 'wysiwyg':
			case 'email':
			case 'url':
			case 'number':
				return is_string( $value ) ? $value : '';
			
			case 'select':
			case 'radio':
			case 'checkbox':
				if ( is_array( $value ) ) {
					return implode( ', ', $value );
				}
				return is_string( $value ) ? $value : '';
			
			case 'repeater':
				if ( is_array( $value ) ) {
					$repeater_text = array();
					foreach ( $value as $row ) {
						if ( is_array( $row ) ) {
							$row_text = array();
							foreach ( $row as $key => $row_value ) {
								if ( ! empty( $row_value ) && is_string( $row_value ) ) {
									$row_text[] = $row_value;
								}
							}
							if ( ! empty( $row_text ) ) {
								$repeater_text[] = implode( ' ', $row_text );
							}
						}
					}
					return implode( "\n", $repeater_text );
				}
				return '';
			
			case 'group':
				if ( is_array( $value ) ) {
					$group_text = array();
					foreach ( $value as $key => $group_value ) {
						if ( ! empty( $group_value ) && is_string( $group_value ) ) {
							$group_text[] = $group_value;
						}
					}
					return implode( ' ', $group_text );
				}
				return '';
			
			default:
				if ( is_string( $value ) ) {
					return $value;
				} elseif ( is_array( $value ) ) {
					return implode( ' ', array_filter( $value, 'is_string' ) );
				}
				return '';
		}
	}

	/**
	 * Extract ACF blocks content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	private static function extract_acf_blocks( $content ) {
		if ( ! has_blocks( $content ) ) {
			return '';
		}

		$blocks = parse_blocks( $content );
		$acf_content = array();

		foreach ( $blocks as $block ) {
			// Check if it's an ACF block (starts with acf/)
			if ( isset( $block['blockName'] ) && strpos( $block['blockName'], 'acf/' ) === 0 ) {
				$acf_text = self::extract_acf_block_data( $block );
				if ( ! empty( $acf_text ) ) {
					$acf_content[] = $acf_text;
				}
			}

			// Check inner blocks
			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $inner_block ) {
					if ( isset( $inner_block['blockName'] ) && strpos( $inner_block['blockName'], 'acf/' ) === 0 ) {
						$acf_text = self::extract_acf_block_data( $inner_block );
						if ( ! empty( $acf_text ) ) {
							$acf_content[] = $acf_text;
						}
					}
				}
			}
		}

		return implode( "\n\n", array_filter( $acf_content ) );
	}

	/**
	 * Extract ACF block data.
	 *
	 * @param array $block Block data.
	 * @return string
	 */
	private static function extract_acf_block_data( $block ) {
		$text = '';

		// ACF blocks store data in attrs['data']
		if ( isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ) {
			foreach ( $block['attrs']['data'] as $key => $value ) {
				if ( is_string( $value ) && ! empty( $value ) ) {
					$text .= ' ' . $value;
				} elseif ( is_array( $value ) ) {
					$text .= ' ' . self::extract_array_text( $value );
				}
			}
		}

		// Also check innerHTML for rendered content
		if ( isset( $block['innerHTML'] ) ) {
			$html = $block['innerHTML'];
			$html = preg_replace( '/<!--.*?-->/s', '', $html );
			$text .= ' ' . wp_strip_all_tags( $html );
			// Decode HTML entities
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return trim( $text );
	}
}
