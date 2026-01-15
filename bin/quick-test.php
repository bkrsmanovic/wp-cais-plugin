#!/usr/bin/env php
<?php
/**
 * Quick test script for WP Context AI Search
 * 
 * This script performs basic validation checks without requiring WordPress to be fully loaded.
 * 
 * Usage: php bin/quick-test.php
 */

echo "🔍 WP Context AI Search - Quick Test\n";
echo str_repeat("=", 50) . "\n\n";

$plugin_dir = dirname( __DIR__ );
$errors = [];
$warnings = [];

// 1. Check if main plugin file exists
echo "1. Checking main plugin file...\n";
if ( file_exists( $plugin_dir . '/wp-context-ai-search.php' ) ) {
    echo "   ✓ Main plugin file exists\n";
} else {
    $errors[] = "Main plugin file missing";
    echo "   ✗ Main plugin file missing\n";
}

// 2. Check required includes
echo "\n2. Checking required includes...\n";
$required_includes = [
    'includes/class-wp-cais-settings.php',
    'includes/class-wp-cais-admin.php',
    'includes/class-wp-cais-frontend.php',
    'includes/class-wp-cais-ai.php',
    'includes/class-wp-cais-search.php',
    'includes/class-wp-cais-content-extractor.php',
    'includes/class-wp-cais-database.php',
    'includes/class-wp-cais-license.php',
    'includes/templates.php',
    'includes/internal/singleton.php',
    'includes/internal/template-loader.php',
];

foreach ( $required_includes as $include ) {
    $path = $plugin_dir . '/' . $include;
    if ( file_exists( $path ) ) {
        echo "   ✓ $include\n";
    } else {
        $errors[] = "Missing include: $include";
        echo "   ✗ Missing: $include\n";
    }
}

// 3. Check template files
echo "\n3. Checking template files...\n";
$templates = [
    'templates/template-search.php',
    'templates/template-search-form.php',
    'templates/template-error.php',
    'templates/template-footer.php',
];

foreach ( $templates as $template ) {
    $path = $plugin_dir . '/' . $template;
    if ( file_exists( $path ) ) {
        echo "   ✓ $template\n";
    } else {
        $warnings[] = "Missing template: $template";
        echo "   ⚠ Missing: $template\n";
    }
}

// 4. Check asset files
echo "\n4. Checking asset files...\n";
$assets = [
    'admin/css/admin.css',
    'admin/js/admin.js',
    'public/css/frontend.css',
    'public/js/frontend.js',
];

foreach ( $assets as $asset ) {
    $path = $plugin_dir . '/' . $asset;
    if ( file_exists( $path ) ) {
        $size = filesize( $path );
        echo "   ✓ $asset (" . round( $size / 1024, 2 ) . " KB)\n";
    } else {
        $warnings[] = "Missing asset: $asset";
        echo "   ⚠ Missing: $asset\n";
    }
}

// 5. Check for syntax errors in PHP files
echo "\n5. Checking PHP syntax...\n";
$php_files = [
    'wp-context-ai-search.php',
    'includes/class-wp-cais-settings.php',
    'includes/class-wp-cais-admin.php',
    'includes/class-wp-cais-frontend.php',
    'includes/class-wp-cais-ai.php',
];

foreach ( $php_files as $file ) {
    $path = $plugin_dir . '/' . $file;
    if ( file_exists( $path ) ) {
        $output = [];
        $return_var = 0;
        exec( "php -l $path 2>&1", $output, $return_var );
        if ( $return_var === 0 ) {
            echo "   ✓ $file (syntax OK)\n";
        } else {
            $errors[] = "Syntax error in $file";
            echo "   ✗ Syntax error in $file\n";
            echo "     " . implode( "\n     ", $output ) . "\n";
        }
    }
}

// 6. Check security index files
echo "\n6. Checking security files...\n";
$security_dirs = [ 'includes', 'admin', 'public', 'templates' ];
foreach ( $security_dirs as $dir ) {
    $index_file = $plugin_dir . '/' . $dir . '/index.php';
    if ( file_exists( $index_file ) ) {
        echo "   ✓ $dir/index.php\n";
    } else {
        $warnings[] = "Missing security file: $dir/index.php";
        echo "   ⚠ Missing: $dir/index.php (security best practice)\n";
    }
}

// 7. Check version consistency
echo "\n7. Checking version consistency...\n";
$plugin_file = $plugin_dir . '/wp-context-ai-search.php';
if ( file_exists( $plugin_file ) ) {
    $content = file_get_contents( $plugin_file );
    
    // Extract version from header
    preg_match( "/Version:\s*([0-9.]+)/", $content, $header_version );
    preg_match( "/define\s*\(\s*['\"]WP_CAIS_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]/", $content, $const_version );
    
    if ( isset( $header_version[1] ) && isset( $const_version[1] ) ) {
        if ( $header_version[1] === $const_version[1] ) {
            echo "   ✓ Versions match: {$header_version[1]}\n";
        } else {
            $errors[] = "Version mismatch: Header={$header_version[1]}, Constant={$const_version[1]}";
            echo "   ✗ Version mismatch!\n";
            echo "     Header: {$header_version[1]}\n";
            echo "     Constant: {$const_version[1]}\n";
        }
    } else {
        $warnings[] = "Could not extract version information";
        echo "   ⚠ Could not extract version information\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Summary:\n";

if ( empty( $errors ) && empty( $warnings ) ) {
    echo "✅ All checks passed! Plugin structure looks good.\n";
    exit( 0 );
} else {
    if ( ! empty( $errors ) ) {
        echo "❌ Errors found (" . count( $errors ) . "):\n";
        foreach ( $errors as $error ) {
            echo "   - $error\n";
        }
    }
    
    if ( ! empty( $warnings ) ) {
        echo "⚠️  Warnings (" . count( $warnings ) . "):\n";
        foreach ( $warnings as $warning ) {
            echo "   - $warning\n";
        }
    }
    
    exit( ! empty( $errors ) ? 1 : 0 );
}
