<?php
/**
 * Plugin constants.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CAIS_Constants class.
 */
class CAIS_Constants {

	/**
	 * Cache group name for object cache.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'cais';

	/**
	 * Cache expiry time in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_EXPIRY = HOUR_IN_SECONDS;

	/**
	 * Rate limit: maximum requests per window.
	 *
	 * @var int
	 */
	const RATE_LIMIT_REQUESTS = 10;

	/**
	 * Rate limit: time window in seconds (1 minute).
	 *
	 * @var int
	 */
	const RATE_LIMIT_WINDOW = MINUTE_IN_SECONDS;

	/**
	 * Maximum number of search results to return.
	 *
	 * @var int
	 */
	const MAX_SEARCH_RESULTS = 10;

	/**
	 * Maximum number of cache entries to keep.
	 *
	 * @var int
	 */
	const MAX_CACHE_ENTRIES = 1000;

	/**
	 * API request timeout in seconds.
	 *
	 * @var int
	 */
	const API_TIMEOUT = 30;

	/**
	 * Minimum query length for search.
	 *
	 * @var int
	 */
	const MIN_QUERY_LENGTH = 2;

	/**
	 * Maximum query length for search.
	 *
	 * @var int
	 */
	const MAX_QUERY_LENGTH = 500;
}
