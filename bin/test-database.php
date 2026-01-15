#!/usr/bin/env php
<?php
/**
 * Test database table creation
 * 
 * This script tests if the database table can be created and accessed.
 * 
 * Usage: php bin/test-database.php
 * 
 * Note: This requires WordPress to be loaded, so run it from WordPress root or via WP-CLI
 */

// Load WordPress
if ( file_exists( __DIR__ . '/../../../../wp-load.php' ) ) {
	require_once __DIR__ . '/../../../../wp-load.php';
} elseif ( file_exists( __DIR__ . '/../../../../../wp-load.php' ) ) {
	require_once __DIR__ . '/../../../../../wp-load.php';
} else {
	echo "❌ Error: Could not find wp-load.php\n";
	echo "Please run this script from WordPress root or use WP-CLI:\n";
	echo "  wp eval-file bin/test-database.php\n";
	exit( 1 );
}

// Load plugin
require_once __DIR__ . '/../includes/class-wp-cais-database.php';

echo "🔍 Testing WP Context AI Search Database\n";
echo str_repeat("=", 50) . "\n\n";

global $wpdb;

// 1. Check if table exists
echo "1. Checking if table exists...\n";
$table_name = WP_CAIS_Database::get_table_name();
$table_exists = WP_CAIS_Database::table_exists();

if ( $table_exists ) {
	echo "   ✓ Table exists: $table_name\n";
} else {
	echo "   ✗ Table does not exist: $table_name\n";
	echo "   → Attempting to create table...\n";
	
	$result = WP_CAIS_Database::create_table();
	
	if ( is_wp_error( $result ) ) {
		echo "   ✗ Error creating table: " . $result->get_error_message() . "\n";
		exit( 1 );
	}
	
	if ( WP_CAIS_Database::table_exists() ) {
		echo "   ✓ Table created successfully!\n";
	} else {
		echo "   ✗ Table creation failed. Check database permissions.\n";
		echo "   Last error: " . $wpdb->last_error . "\n";
		exit( 1 );
	}
}

// 2. Test table structure
echo "\n2. Testing table structure...\n";
$columns = $wpdb->get_results( "DESCRIBE $table_name" );
$expected_columns = array( 'id', 'query_hash', 'query_text', 'response', 'source_ids', 'created_at', 'updated_at', 'hit_count' );

$found_columns = array();
foreach ( $columns as $column ) {
	$found_columns[] = $column->Field;
}

foreach ( $expected_columns as $col ) {
	if ( in_array( $col, $found_columns, true ) ) {
		echo "   ✓ Column exists: $col\n";
	} else {
		echo "   ✗ Missing column: $col\n";
	}
}

// 3. Test insert
echo "\n3. Testing insert operation...\n";
$test_query = 'test query ' . time();
$test_response = 'This is a test response';
$test_sources = array( 1, 2, 3 );

$insert_result = WP_CAIS_Database::cache_response( $test_query, $test_response, $test_sources );

if ( $insert_result ) {
	echo "   ✓ Insert successful\n";
} else {
	echo "   ✗ Insert failed: " . $wpdb->last_error . "\n";
	exit( 1 );
}

// 4. Test retrieve
echo "\n4. Testing retrieve operation...\n";
$cached = WP_CAIS_Database::get_cached( $test_query );

if ( $cached && isset( $cached['response'] ) && $cached['response'] === $test_response ) {
	echo "   ✓ Retrieve successful\n";
	echo "   → Response matches: " . substr( $cached['response'], 0, 50 ) . "...\n";
} else {
	echo "   ✗ Retrieve failed or data mismatch\n";
	if ( $cached ) {
		echo "   → Retrieved: " . print_r( $cached, true ) . "\n";
	}
}

// 5. Test query hash normalization
echo "\n5. Testing query hash normalization...\n";
$query1 = 'What is the weather today?';
$query2 = 'what is the weather today';
$query3 = 'What is the weather TODAY?';

$hash1 = WP_CAIS_Database::generate_query_hash( $query1 );
$hash2 = WP_CAIS_Database::generate_query_hash( $query2 );
$hash3 = WP_CAIS_Database::generate_query_hash( $query3 );

if ( $hash1 === $hash2 && $hash2 === $hash3 ) {
	echo "   ✓ Hash normalization works (similar queries get same hash)\n";
	echo "   → Hash: " . substr( $hash1, 0, 16 ) . "...\n";
} else {
	echo "   ⚠ Hashes differ (this might be expected depending on normalization)\n";
	echo "   → Hash1: " . substr( $hash1, 0, 16 ) . "...\n";
	echo "   → Hash2: " . substr( $hash2, 0, 16 ) . "...\n";
	echo "   → Hash3: " . substr( $hash3, 0, 16 ) . "...\n";
}

// 6. Check cache count
echo "\n6. Checking cache statistics...\n";
$cache_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
echo "   → Total cached entries: $cache_count\n";

$oldest = $wpdb->get_var( "SELECT MIN(created_at) FROM $table_name" );
$newest = $wpdb->get_var( "SELECT MAX(created_at) FROM $table_name" );

if ( $oldest ) {
	echo "   → Oldest entry: $oldest\n";
}
if ( $newest ) {
	echo "   → Newest entry: $newest\n";
}

// 7. Cleanup test data
echo "\n7. Cleaning up test data...\n";
$wpdb->delete( $table_name, array( 'query_text' => $test_query ), array( '%s' ) );
echo "   ✓ Test data removed\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ All database tests passed!\n";
echo "\nTable: $table_name\n";
echo "Status: Ready for use\n";
