<?php
/**
 * Database management for caching.
 *
 * @package Context_AI_Search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CAIS_Database class.
 */
class CAIS_Database {

	/**
	 * Table name for cache.
	 *
	 * @var string
	 */
	private static $table_name = 'cais_cache';

	/**
	 * Get table name with prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::$table_name;
	}

	/**
	 * Check if table exists.
	 *
	 * @return bool
	 */
	public static function table_exists(): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		$result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		return $result === $table_name;
	}

	/**
	 * Create database table.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			query_hash varchar(64) NOT NULL,
			query_text text NOT NULL,
			response text NOT NULL,
			source_ids text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			hit_count int(11) DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY query_hash (query_hash),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$result = dbDelta( $sql );
		
		// Check if table was created
		if ( self::table_exists() ) {
			return true;
		}
		
		// If dbDelta didn't work, try direct query
		// Note: DDL statements (CREATE TABLE) cannot use placeholders, so we escape the table name.
		$escaped_table_name = esc_sql( $table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- DDL statement, table name is escaped
		$sql_direct = "CREATE TABLE $escaped_table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			query_hash varchar(64) NOT NULL,
			query_text text NOT NULL,
			response text NOT NULL,
			source_ids text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			hit_count int(11) DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY query_hash (query_hash),
			KEY created_at (created_at)
		) $charset_collate;";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- DDL statement, table name is escaped
		$wpdb->query( $sql_direct );
		
		if ( self::table_exists() ) {
			return true;
		}
		
		$error = $wpdb->last_error;
		if ( empty( $error ) ) {
			$error = __( 'Unknown error creating table.', 'context-ai-search' );
		}
		
		return new WP_Error( 'table_creation_failed', $error );
	}

	/**
	 * Ensure table exists, create if it doesn't.
	 *
	 * @return bool|WP_Error
	 */
	public static function ensure_table_exists() {
		if ( self::table_exists() ) {
			return true;
		}
		
		return self::create_table();
	}

	/**
	 * Drop database table.
	 */
	public static function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		// Note: DDL statements (DROP TABLE) cannot use placeholders, so we escape the table name.
		$escaped_table_name = esc_sql( $table_name );
		$wpdb->query( "DROP TABLE IF EXISTS $escaped_table_name" );
	}

	/**
	 * Generate query hash for caching.
	 * Uses exact matching (case-insensitive, normalized whitespace) to avoid collisions.
	 *
	 * @param string $query Query text.
	 * @return string
	 */
	public static function generate_query_hash( string $query ): string {
		// Normalize query: lowercase, normalize whitespace only (preserve all characters)
		$normalized = strtolower( trim( $query ) );
		// Only normalize multiple spaces to single space, preserve all other characters
		$normalized = preg_replace( '/\s+/', ' ', $normalized );
		
		// Use exact normalized query for hash to avoid collisions
		return md5( $normalized );
	}

	/**
	 * Get cached response.
	 *
	 * @param string $query Query text.
	 * @return array{response: string, source_ids: array<int>}|null
	 */
	public static function get_cached( string $query ): ?array {
		global $wpdb;
		$table_name = self::get_table_name();
		$query_hash = self::generate_query_hash( $query );

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE query_hash = %s",
				$query_hash
			),
			ARRAY_A
		);

		if ( $result ) {
			// Update hit count
			$wpdb->update(
				$table_name,
				array( 'hit_count' => $result['hit_count'] + 1 ),
				array( 'id' => $result['id'] ),
				array( '%d' ),
				array( '%d' )
			);

			return array(
				'response' => $result['response'],
				'source_ids' => maybe_unserialize( $result['source_ids'] ),
			);
		}

		return null;
	}

	/**
	 * Cache response.
	 *
	 * @param string   $query Query text.
	 * @param string   $response AI response.
	 * @param array<int> $source_ids Source post/page IDs.
	 * @return bool|int False on failure, number of rows affected on success.
	 */
	public static function cache_response( string $query, string $response, array $source_ids = array() ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$query_hash = self::generate_query_hash( $query );

		return $wpdb->replace(
			$table_name,
			array(
				'query_hash' => $query_hash,
				'query_text' => $query,
				'response' => $response,
				'source_ids' => maybe_serialize( $source_ids ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Clean old cache entries.
	 *
	 * @param int $days Days to keep cache.
	 * @return int|false Number of deleted rows, or false on error.
	 */
	public static function clean_old_cache( int $days = 30 ) {
		global $wpdb;
		$table_name = self::get_table_name();

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}
}
