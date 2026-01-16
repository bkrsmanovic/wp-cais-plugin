<?php
/**
 * Search functionality.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CAIS_Search class.
 */
class CAIS_Search {

	/**
	 * Process search query.
	 *
	 * @param string $query Search query.
	 * @return array|WP_Error
	 */
	public function process_query( $query ) {
		// Check cache first
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-database.php';
		$cached = CAIS_Database::get_cached( $query );
		
		if ( $cached ) {
			return array(
				'response' => $cached['response'],
				'sources' => $this->get_source_links( $cached['source_ids'] ),
				'cached' => true,
			);
		}

		// Search WordPress content
		$results = $this->search_content( $query );
		
		if ( empty( $results ) ) {
			return array(
				'response' => $this->get_no_results_response(),
				'sources' => array(),
				'cached' => false,
			);
		}

		// Generate AI response
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-ai.php';
		$ai_handler = new CAIS_AI();
		$ai_response = $ai_handler->generate_response( $query, $results );

		if ( is_wp_error( $ai_response ) ) {
			// Fallback to simple response
			$ai_response = $this->generate_simple_response( $query, $results );
		}

		// Cache the response
		$source_ids = array_column( $results, 'id' );
		CAIS_Database::cache_response( $query, $ai_response, $source_ids );

		return array(
			'response' => $ai_response,
			'sources' => $this->get_source_links( $source_ids ),
			'cached' => false,
		);
	}

	/**
	 * Search WordPress content.
	 *
	 * @param string $query Search query.
	 * @return array
	 */
	private function search_content( $query ) {
		$enabled_types = CAIS_Settings::get_enabled_post_types();
		
		// Filter out premium post types if user doesn't have premium access
		$is_premium = function_exists( 'cais_fs' ) ? cais_fs()->can_use_premium_code__premium_only() : false;
		if ( ! $is_premium ) {
			$free_types = CAIS_Settings::get_free_post_types();
			$enabled_types = array_intersect( $enabled_types, $free_types );
		}
		
		if ( empty( $enabled_types ) ) {
			return array();
		}

		// Try multiple search strategies
		$results = array();
		
		// Strategy 1: Extract key terms and search with OR logic
		$key_terms = $this->extract_key_terms( $query );
		if ( ! empty( $key_terms ) ) {
			$results = $this->search_with_terms( $enabled_types, $key_terms, $query );
		}
		
		// Strategy 2: If no results, try searching all posts and filter by content match
		if ( empty( $results ) ) {
			$results = $this->search_all_and_filter( $enabled_types, $query );
		}
		
		// Strategy 3: Fallback to WordPress default search
		if ( empty( $results ) ) {
			$results = $this->wordpress_default_search( $enabled_types, $query );
		}

		// Score and sort results by relevance
		$results = $this->score_and_sort_results( $results, $query );

		$max_results = defined( 'CAIS_MAX_RESULTS' ) ? (int) CAIS_MAX_RESULTS : 10;
		return array_slice( $results, 0, $max_results );
	}

	/**
	 * Extract key terms from query (remove stop words).
	 *
	 * @param string $query Search query.
	 * @return array
	 */
	private function extract_key_terms( $query ) {
		// Common stop words
		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'what', 'can', 'i', 'expect', 'at', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must' );
		
		// Normalize query
		$query = strtolower( trim( $query ) );
		$query = preg_replace( '/[^\w\s]/', ' ', $query );
		
		// Split into words
		$words = preg_split( '/\s+/', $query );
		
		// Filter out stop words and short words
		$key_terms = array();
		foreach ( $words as $word ) {
			$word = trim( $word );
			if ( strlen( $word ) > 2 && ! in_array( $word, $stop_words, true ) ) {
				$key_terms[] = $word;
			}
		}
		
		// Also include important phrases (3+ word sequences)
		$phrases = $this->extract_phrases( $query );
		$key_terms = array_merge( $key_terms, $phrases );
		
		return array_unique( $key_terms );
	}

	/**
	 * Extract important phrases from query.
	 *
	 * @param string $query Search query.
	 * @return array
	 */
	private function extract_phrases( $query ) {
		$phrases = array();
		$words = preg_split( '/\s+/', strtolower( $query ) );
		
		// Extract 2-4 word phrases
		for ( $i = 0; $i < count( $words ) - 1; $i++ ) {
			for ( $length = 2; $length <= 4 && ( $i + $length ) <= count( $words ); $length++ ) {
				$phrase = implode( ' ', array_slice( $words, $i, $length ) );
				if ( strlen( $phrase ) > 5 ) {
					$phrases[] = $phrase;
				}
			}
		}
		
		return $phrases;
	}

	/**
	 * Search with extracted terms using OR logic.
	 *
	 * @param array  $enabled_types Post types.
	 * @param array  $key_terms Key terms to search.
	 * @param string $original_query Original query.
	 * @return array
	 */
	private function search_with_terms( $enabled_types, $key_terms, $original_query ) {
		global $wpdb;
		
		if ( empty( $key_terms ) ) {
			return array();
		}
		
		require_once CAIS_PLUGIN_DIR . 'includes/class-cais-content-extractor.php';
		
		// Build search conditions
		// Prepare post types for IN clause
		$post_types_placeholders = implode( ',', array_fill( 0, count( $enabled_types ), '%s' ) );
		
		// Build LIKE conditions with placeholders
		$like_conditions = array();
		$prepare_values = array();
		
		foreach ( $key_terms as $term ) {
			$escaped_term = '%' . $wpdb->esc_like( $term ) . '%';
			$like_conditions[] = "(p.post_title LIKE %s OR p.post_content LIKE %s)";
			$prepare_values[] = $escaped_term;
			$prepare_values[] = $escaped_term;
		}
		
		if ( empty( $like_conditions ) ) {
			return array();
		}
		
		// Build the full query with all placeholders
		$like_conditions_sql = implode( ' OR ', $like_conditions );
		
		// Combine all values for prepare: post types first, then LIKE values
		$all_values = array_merge( $enabled_types, $prepare_values );
		
		// Build query template with table name (table names can't use placeholders)
		// $wpdb->posts is a WordPress constant, safe to use directly
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is WordPress constant, placeholders are used for all user input
		$query_template = "SELECT DISTINCT p.ID, p.post_title, p.post_type
			FROM {$wpdb->posts} p
			WHERE p.post_type IN ($post_types_placeholders)
			AND p.post_status = 'publish'
			AND ($like_conditions_sql)
			LIMIT 50";
		
		// Prepare the full query
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared on next line
		$sql = $wpdb->prepare( $query_template, ...$all_values );
		
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above
		$post_ids = $wpdb->get_col( $sql );
		
		if ( empty( $post_ids ) ) {
			return array();
		}
		
		$results = array();
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			
			$full_content = CAIS_Content_Extractor::extract_content( $post_id );
			
			// Check if content actually contains the terms
			$content_lower = strtolower( $full_content );
			$has_match = false;
			foreach ( $key_terms as $term ) {
				if ( strpos( $content_lower, strtolower( $term ) ) !== false ) {
					$has_match = true;
					break;
				}
			}
			
			if ( $has_match ) {
				$title = get_the_title( $post_id );
				$excerpt = get_the_excerpt( $post_id );
				
				$results[] = array(
					'id' => $post->ID,
					'title' => html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'content' => $full_content,
					'excerpt' => html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'url' => get_permalink( $post_id ),
					'type' => $post->post_type,
					'score' => 0, // Will be calculated later
				);
			}
		}
		
		return $results;
	}

	/**
	 * Search all posts and filter by content match.
	 *
	 * @param array  $enabled_types Post types.
	 * @param string $query Search query.
	 * @return array
	 */
	private function search_all_and_filter( $enabled_types, $query ) {
		$args = array(
			'post_type' => $enabled_types,
			'post_status' => 'publish',
			'posts_per_page' => 50,
			'orderby' => 'date',
			'order' => 'DESC',
		);
		
		$search_query = new WP_Query( $args );
		$results = array();
		
		if ( $search_query->have_posts() ) {
			require_once CAIS_PLUGIN_DIR . 'includes/class-cais-content-extractor.php';
			
			$query_lower = strtolower( $query );
			$key_terms = $this->extract_key_terms( $query );
			
			while ( $search_query->have_posts() ) {
				$search_query->the_post();
				$post = get_post();
				
				$full_content = CAIS_Content_Extractor::extract_content( $post->ID );
				$content_lower = strtolower( $full_content );
				
				// Check for matches
				$match_count = 0;
				foreach ( $key_terms as $term ) {
					if ( strpos( $content_lower, strtolower( $term ) ) !== false ) {
						$match_count++;
					}
				}
				
				// Also check for phrase matches
				$phrase_matches = 0;
				foreach ( $this->extract_phrases( $query ) as $phrase ) {
					if ( strpos( $content_lower, $phrase ) !== false ) {
						$phrase_matches++;
					}
				}
				
				// If we have matches, include this post
				if ( $match_count > 0 || $phrase_matches > 0 ) {
					$title = get_the_title();
					$excerpt = get_the_excerpt();
					
					$results[] = array(
						'id' => $post->ID,
						'title' => html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
						'content' => $full_content,
						'excerpt' => html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
						'url' => get_permalink(),
						'type' => $post->post_type,
						'score' => ( $match_count * 2 ) + ( $phrase_matches * 5 ),
					);
				}
			}
			wp_reset_postdata();
		}
		
		return $results;
	}

	/**
	 * WordPress default search (fallback).
	 *
	 * @param array  $enabled_types Post types.
	 * @param string $query Search query.
	 * @return array
	 */
	private function wordpress_default_search( $enabled_types, $query ) {
		$args = array(
			'post_type' => $enabled_types,
			'post_status' => 'publish',
			's' => $query,
			'posts_per_page' => 10,
			'orderby' => 'relevance',
		);

		$search_query = new WP_Query( $args );
		$results = array();

		if ( $search_query->have_posts() ) {
			require_once CAIS_PLUGIN_DIR . 'includes/class-cais-content-extractor.php';
			
			while ( $search_query->have_posts() ) {
				$search_query->the_post();
				$post = get_post();
				
				$full_content = CAIS_Content_Extractor::extract_content( $post->ID );
				$title = get_the_title();
				$excerpt = get_the_excerpt();
				
				$results[] = array(
					'id' => $post->ID,
					'title' => html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'content' => $full_content,
					'excerpt' => html_entity_decode( $excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'url' => get_permalink(),
					'type' => $post->post_type,
					'score' => 1,
				);
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Score and sort results by relevance.
	 *
	 * @param array  $results Search results.
	 * @param string $query Original query.
	 * @return array
	 */
	private function score_and_sort_results( $results, $query ) {
		$query_lower = strtolower( $query );
		$key_terms = $this->extract_key_terms( $query );
		
		foreach ( $results as &$result ) {
			if ( ! isset( $result['score'] ) ) {
				$result['score'] = 0;
			}
			
			$content_lower = strtolower( $result['content'] );
			$title_lower = strtolower( $result['title'] );
			
			// Title matches are worth more
			foreach ( $key_terms as $term ) {
				if ( strpos( $title_lower, strtolower( $term ) ) !== false ) {
					$result['score'] += 10;
				}
				if ( strpos( $content_lower, strtolower( $term ) ) !== false ) {
					$result['score'] += 2;
				}
			}
			
			// Phrase matches are worth even more
			foreach ( $this->extract_phrases( $query ) as $phrase ) {
				if ( strpos( $content_lower, $phrase ) !== false ) {
					$result['score'] += 15;
				}
			}
			
			// Exact query match in title
			if ( strpos( $title_lower, $query_lower ) !== false ) {
				$result['score'] += 50;
			}
		}
		
		// Sort by score descending
		usort( $results, function( $a, $b ) {
			return ( $b['score'] ?? 0 ) - ( $a['score'] ?? 0 );
		} );
		
		return $results;
	}

	/**
	 * Get source links.
	 *
	 * @param array $source_ids Source post IDs.
	 * @return array
	 */
	private function get_source_links( $source_ids ) {
		if ( empty( $source_ids ) || ! is_array( $source_ids ) ) {
			return array();
		}

		$sources = array();
		foreach ( $source_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$title = get_the_title( $post_id );
				// Decode HTML entities in title
				$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				
				$sources[] = array(
					'title' => $title,
					'url' => get_permalink( $post_id ),
				);
			}
		}

		return $sources;
	}

	/**
	 * Generate simple response when AI fails.
	 *
	 * @param string $query Query.
	 * @param array  $results Search results.
	 * @return string
	 */
	private function generate_simple_response( $query, $results ) {
		$response = sprintf(
			/* translators: %1$d: number of results, %2$s: search query text */
			__( 'I found %1$d relevant result(s) for your query: "%2$s".', 'context-ai-search' ),
			count( $results ),
			esc_html( $query )
		);

		$response .= "\n\n";
		$response .= __( 'Here are the most relevant sources:', 'context-ai-search' );

		foreach ( array_slice( $results, 0, 3 ) as $result ) {
			$title = html_entity_decode( $result['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$response .= "\n\n• " . $title;
			if ( ! empty( $result['excerpt'] ) ) {
				$excerpt = html_entity_decode( $result['excerpt'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$trimmed = wp_trim_words( $excerpt, 20 );
				// Decode again in case wp_trim_words preserved entities
				$trimmed = html_entity_decode( $trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$response .= "\n  " . $trimmed;
			}
		}

		return $response;
	}

	/**
	 * Get no results response.
	 *
	 * @return string
	 */
	private function get_no_results_response() {
		$response = __( "I couldn't find specific information about that in our content.", 'context-ai-search' );
		$response .= "\n\n" . __( 'I apologize for the inconvenience. Please feel free to contact us for more information.', 'context-ai-search' );
		
		return $response;
	}
}
