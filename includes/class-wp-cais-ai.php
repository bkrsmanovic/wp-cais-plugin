<?php
/**
 * AI integration.
 *
 * @package WP_Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_CAIS_AI class.
 */
class WP_CAIS_AI {

	/**
	 * Validate API key.
	 *
	 * @param string $api_key API key to validate.
	 * @param string $provider Provider name.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_api_key( $api_key, $provider = 'openai' ) {
		if ( empty( $api_key ) ) {
			return new WP_Error( 'empty_key', __( 'API key cannot be empty.', 'wp-context-ai-search' ) );
		}

		switch ( $provider ) {
			case 'openai':
				return self::validate_openai_key( $api_key );
			case 'claude':
				return self::validate_claude_key( $api_key );
			case 'gemini':
				return self::validate_gemini_key( $api_key );
			default:
				return new WP_Error( 'unsupported_provider', __( 'Provider validation not implemented.', 'wp-context-ai-search' ) );
		}
	}

	/**
	 * Validate OpenAI API key.
	 *
	 * @param string $api_key API key.
	 * @return bool|WP_Error
	 */
	private static function validate_openai_key( $api_key ) {
		// Use /models endpoint - it's free and doesn't consume quota, just checks authentication
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', __( 'Could not connect to OpenAI API.', 'wp-context-ai-search' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check for authentication errors (401 = unauthorized, 403 = forbidden)
		if ( 401 === $code || 403 === $code ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Invalid API key. Please check your API key.', 'wp-context-ai-search' );
			return new WP_Error( 'invalid_key', $error_message );
		}

		// Check for other errors
		if ( isset( $body['error'] ) ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API error occurred.', 'wp-context-ai-search' );
			return new WP_Error( 'api_error', $error_message );
		}

		// Success - API key is valid (200 status and has data array)
		if ( 200 === $code && isset( $body['data'] ) && is_array( $body['data'] ) ) {
			return true;
		}

		// If we get here, something unexpected happened
		return new WP_Error( 'validation_failed', __( 'Could not validate API key. Please try again.', 'wp-context-ai-search' ) );
	}

	/**
	 * Validate Claude (Anthropic) API key.
	 *
	 * @param string $api_key API key.
	 * @return bool|WP_Error
	 */
	private static function validate_claude_key( $api_key ) {
		// Test with a simple message API call
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'headers' => array(
					'x-api-key' => $api_key,
					'anthropic-version' => '2023-06-01',
					'content-type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'model' => 'claude-3-haiku-20240307',
					'max_tokens' => 10,
					'messages' => array(
						array(
							'role' => 'user',
							'content' => 'test',
						),
					),
				) ),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', __( 'Could not connect to Claude API.', 'wp-context-ai-search' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check for authentication errors
		if ( 401 === $code || 403 === $code ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Invalid API key. Please check your API key.', 'wp-context-ai-search' );
			return new WP_Error( 'invalid_key', $error_message );
		}

		// Check for other errors
		if ( isset( $body['error'] ) ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API error occurred.', 'wp-context-ai-search' );
			return new WP_Error( 'api_error', $error_message );
		}

		// Success
		if ( 200 === $code && isset( $body['content'] ) ) {
			return true;
		}

		return new WP_Error( 'validation_failed', __( 'Could not validate API key. Please try again.', 'wp-context-ai-search' ) );
	}

	/**
	 * Validate Gemini (Google) API key.
	 *
	 * @param string $api_key API key.
	 * @return bool|WP_Error
	 */
	private static function validate_gemini_key( $api_key ) {
		// Test with a simple generateContent call using POST
		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . urlencode( $api_key ),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'contents' => array(
						array(
							'parts' => array(
								array( 'text' => 'test' ),
							),
						),
					),
				) ),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', __( 'Could not connect to Gemini API.', 'wp-context-ai-search' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check for authentication errors
		if ( 400 === $code || 401 === $code || 403 === $code ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Invalid API key. Please check your API key.', 'wp-context-ai-search' );
			return new WP_Error( 'invalid_key', $error_message );
		}

		// Success
		if ( 200 === $code && isset( $body['candidates'] ) ) {
			return true;
		}

		return new WP_Error( 'validation_failed', __( 'Could not validate API key. Please try again.', 'wp-context-ai-search' ) );
	}

	/**
	 * Get API quota/usage information.
	 *
	 * @param string $api_key API key.
	 * @param string $provider Provider name.
	 * @return array|WP_Error Quota information or error.
	 */
	public static function get_api_quota( $api_key, $provider = 'openai' ) {
		if ( empty( $api_key ) ) {
			return new WP_Error( 'empty_key', __( 'API key cannot be empty.', 'wp-context-ai-search' ) );
		}

		switch ( $provider ) {
			case 'openai':
				return self::get_openai_quota( $api_key );
			case 'claude':
				return self::get_claude_quota( $api_key );
			case 'gemini':
				return self::get_gemini_quota( $api_key );
			default:
				return new WP_Error( 'unsupported_provider', __( 'Quota checking not implemented for this provider.', 'wp-context-ai-search' ) );
		}
	}

	/**
	 * Get OpenAI quota information.
	 *
	 * @param string $api_key API key.
	 * @return array|WP_Error
	 */
	private static function get_openai_quota( $api_key ) {
		// Try to get usage information from billing API
		$response = wp_remote_get(
			'https://api.openai.com/v1/usage',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			// If we can't get usage, return basic info
			return array(
				'has_quota_info' => false,
				'message' => __( 'Quota information not available. Check your OpenAI dashboard.', 'wp-context-ai-search' ),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && isset( $body ) ) {
			// Parse usage data if available
			$usage_data = array(
				'has_quota_info' => true,
				'raw_data' => $body,
			);

			// Try to extract useful info
			if ( isset( $body['hard_limit_usd'] ) ) {
				$usage_data['limit'] = $body['hard_limit_usd'];
			}
			if ( isset( $body['total_usage'] ) ) {
				$usage_data['used'] = $body['total_usage'];
			}

			return $usage_data;
		}

		// Fallback - check subscription for hard limits
		$sub_response = wp_remote_get(
			'https://api.openai.com/v1/dashboard/billing/subscription',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 10,
			)
		);

		if ( ! is_wp_error( $sub_response ) && 200 === wp_remote_retrieve_response_code( $sub_response ) ) {
			$sub_body = json_decode( wp_remote_retrieve_body( $sub_response ), true );
			
			if ( isset( $sub_body['hard_limit_usd'] ) ) {
				return array(
					'has_quota_info' => true,
					'limit' => $sub_body['hard_limit_usd'],
					'used' => isset( $sub_body['soft_limit_usd'] ) ? $sub_body['soft_limit_usd'] : 0,
					'percentage' => isset( $sub_body['hard_limit_usd'] ) && $sub_body['hard_limit_usd'] > 0 
						? ( ( isset( $sub_body['soft_limit_usd'] ) ? $sub_body['soft_limit_usd'] : 0 ) / $sub_body['hard_limit_usd'] ) * 100 
						: 0,
				);
			}
		}

		return array(
			'has_quota_info' => false,
			'message' => __( 'Quota information not available. Check your OpenAI dashboard.', 'wp-context-ai-search' ),
		);
	}

	/**
	 * Get Claude quota information.
	 *
	 * @param string $api_key API key.
	 * @return array|WP_Error
	 */
	private static function get_claude_quota( $api_key ) {
		// Anthropic doesn't provide a public quota API endpoint
		// Return message to check dashboard
		return array(
			'has_quota_info' => false,
			'message' => __( 'Check your Anthropic dashboard for quota information.', 'wp-context-ai-search' ),
		);
	}

	/**
	 * Get Gemini quota information.
	 *
	 * @param string $api_key API key.
	 * @return array|WP_Error
	 */
	private static function get_gemini_quota( $api_key ) {
		// Google Gemini quota info is available via Cloud Console API
		// For now, return message to check dashboard
		return array(
			'has_quota_info' => false,
			'message' => __( 'Check your Google Cloud Console for quota information.', 'wp-context-ai-search' ),
		);
	}

	/**
	 * Generate AI response.
	 *
	 * @param string $query User query.
	 * @param array  $results Search results.
	 * @return string|WP_Error
	 */
	public function generate_response( $query, $results ) {
		$api_key = WP_CAIS_Settings::get_ai_api_key();
		$provider = WP_CAIS_Settings::get_ai_provider();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'AI API key is not configured.', 'wp-context-ai-search' ) );
		}

		switch ( $provider ) {
			case 'openai':
				return $this->generate_openai_response( $query, $results, $api_key );
			case 'claude':
				return $this->generate_claude_response( $query, $results, $api_key );
			case 'gemini':
				return $this->generate_gemini_response( $query, $results, $api_key );
			case 'huggingface':
				return $this->generate_huggingface_response( $query, $results, $api_key );
			default:
				return new WP_Error( 'invalid_provider', __( 'Invalid AI provider.', 'wp-context-ai-search' ) );
		}
	}

	/**
	 * Generate OpenAI response.
	 *
	 * @param string $query User query.
	 * @param array  $results Search results.
	 * @param string $api_key API key.
	 * @return string|WP_Error
	 */
	private function generate_openai_response( $query, $results, $api_key ) {
		$system_prompt = $this->build_system_prompt();
		$context = $this->build_context( $results );

		$messages = array(
			array(
				'role' => 'system',
				'content' => $system_prompt,
			),
			array(
				'role' => 'user',
				'content' => sprintf(
					"%s\n\nContext from our website:\n%s",
					$query,
					$context
				),
			),
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'model' => 'gpt-3.5-turbo',
					'messages' => $messages,
					'max_tokens' => 500,
					'temperature' => 0.7,
				) ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API error occurred.', 'wp-context-ai-search' );
			return new WP_Error( 'api_error', $error_msg );
		}

		if ( 200 !== $code ) {
			return new WP_Error( 'api_error', __( 'Invalid API key or API error. Please check your API key settings.', 'wp-context-ai-search' ) );
		}

		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			$response_text = trim( $body['choices'][0]['message']['content'] );
			// Decode HTML entities in AI response
			$response_text = html_entity_decode( $response_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return $response_text;
		}

		return new WP_Error( 'invalid_response', __( 'Invalid response from AI service.', 'wp-context-ai-search' ) );
	}

	/**
	 * Generate Claude (Anthropic) response.
	 *
	 * @param string $query User query.
	 * @param array  $results Search results.
	 * @param string $api_key API key.
	 * @return string|WP_Error
	 */
	private function generate_claude_response( $query, $results, $api_key ) {
		$system_prompt = $this->build_system_prompt();
		$context = $this->build_context( $results );

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'headers' => array(
					'x-api-key' => $api_key,
					'anthropic-version' => '2023-06-01',
					'content-type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'model' => 'claude-3-haiku-20240307',
					'max_tokens' => 500,
					'system' => $system_prompt,
					'messages' => array(
						array(
							'role' => 'user',
							'content' => sprintf(
								"%s\n\nContext from our website:\n%s",
								$query,
								$context
							),
						),
					),
				) ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API error occurred.', 'wp-context-ai-search' );
			return new WP_Error( 'api_error', $error_msg );
		}

		if ( 200 !== $code ) {
			return new WP_Error( 'api_error', __( 'Invalid API key or API error. Please check your API key settings.', 'wp-context-ai-search' ) );
		}

		if ( isset( $body['content'][0]['text'] ) ) {
			$response_text = trim( $body['content'][0]['text'] );
			$response_text = html_entity_decode( $response_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return $response_text;
		}

		return new WP_Error( 'invalid_response', __( 'Invalid response from AI service.', 'wp-context-ai-search' ) );
	}

	/**
	 * Generate Gemini (Google) response.
	 *
	 * @param string $query User query.
	 * @param array  $results Search results.
	 * @param string $api_key API key.
	 * @return string|WP_Error
	 */
	private function generate_gemini_response( $query, $results, $api_key ) {
		$system_prompt = $this->build_system_prompt();
		$context = $this->build_context( $results );

		$full_prompt = sprintf(
			"%s\n\n%s\n\nContext from our website:\n%s",
			$system_prompt,
			$query,
			$context
		);

		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . urlencode( $api_key ),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( array(
					'contents' => array(
						array(
							'parts' => array(
								array( 'text' => $full_prompt ),
							),
						),
					),
					'generationConfig' => array(
						'maxOutputTokens' => 500,
						'temperature' => 0.7,
					),
				) ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'API error occurred.', 'wp-context-ai-search' );
			return new WP_Error( 'api_error', $error_msg );
		}

		if ( 200 !== $code ) {
			return new WP_Error( 'api_error', __( 'Invalid API key or API error. Please check your API key settings.', 'wp-context-ai-search' ) );
		}

		if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$response_text = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
			$response_text = html_entity_decode( $response_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return $response_text;
		}

		return new WP_Error( 'invalid_response', __( 'Invalid response from AI service.', 'wp-context-ai-search' ) );
	}

	/**
	 * Generate HuggingFace response (fallback/alternative).
	 *
	 * @param string $query User query.
	 * @param array  $results Search results.
	 * @param string $api_key API key.
	 * @return string|WP_Error
	 */
	private function generate_huggingface_response( $query, $results, $api_key ) {
		// HuggingFace implementation can be added later
		// For now, return error to use OpenAI
		return new WP_Error( 'not_implemented', __( 'HuggingFace integration coming soon.', 'wp-context-ai-search' ) );
	}

	/**
	 * Build system prompt.
	 *
	 * @return string
	 */
	private function build_system_prompt() {
		$prompt = __( 'You are a helpful assistant that answers questions based on content from a WordPress website. ', 'wp-context-ai-search' );
		$prompt .= __( 'Use a friendly and professional tone. ', 'wp-context-ai-search' );
		$prompt .= __( 'Keep your answers clear and concise. ', 'wp-context-ai-search' );
		$prompt .= __( 'Only use information from the provided context. If the context does not contain the answer, say so clearly.', 'wp-context-ai-search' );

		return trim( $prompt );
	}

	/**
	 * Build context from search results.
	 *
	 * @param array $results Search results.
	 * @return string
	 */
	private function build_context( $results ) {
		$context = '';
		$max_length = 3000; // Limit context length
		$current_length = 0;

		foreach ( $results as $result ) {
			// Decode HTML entities in title and content before sending to AI
			$title = html_entity_decode( $result['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$content = html_entity_decode( $result['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			
			// Trim words after decoding
			$trimmed_content = wp_trim_words( $content, 100 );
			// Decode again in case wp_trim_words preserved any entities
			$trimmed_content = html_entity_decode( $trimmed_content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			
			$item_text = sprintf(
				"Title: %s\nContent: %s\n\n",
				$title,
				$trimmed_content
			);

			if ( $current_length + strlen( $item_text ) > $max_length ) {
				break;
			}

			$context .= $item_text;
			$current_length += strlen( $item_text );
		}

		return $context;
	}
}
