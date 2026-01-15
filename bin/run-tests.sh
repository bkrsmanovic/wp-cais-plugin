#!/bin/bash
# Quick test runner
# Checks code standards and runs basic validation

set -e

echo "🔍 Running WP Context AI Search Tests..."
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "📦 Installing dependencies..."
    composer install --no-interaction
fi

# Version check
echo "✅ Checking version consistency..."
VERSION=$(grep "Version:" wp-context-ai-search.php | sed 's/.*Version: *//' | sed 's/ *\*\/.*//')
CONST_VERSION=$(grep "define( 'WP_CAIS_VERSION'" wp-context-ai-search.php | sed "s/.*'WP_CAIS_VERSION', '//" | sed "s/'.*//")
if [ "$VERSION" = "$CONST_VERSION" ]; then
    echo "   ✓ Versions match: $VERSION"
else
    echo "   ✗ Version mismatch! Header: $VERSION, Constant: $CONST_VERSION"
    exit 1
fi

# PHPCS check
echo ""
echo "✅ Running PHP Code Sniffer..."
if composer run phpcs > /dev/null 2>&1; then
    echo "   ✓ Code style check passed"
else
    echo "   ⚠ Code style issues found. Run 'composer run phpcbf' to auto-fix."
    composer run phpcs
    exit 1
fi

# Check for required files
echo ""
echo "✅ Checking required files..."
REQUIRED_FILES=(
    "wp-context-ai-search.php"
    "README.txt"
    "includes/class-wp-cais-settings.php"
    "includes/class-wp-cais-frontend.php"
    "includes/class-wp-cais-admin.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ✗ Missing: $file"
        exit 1
    fi
done

# Check for security index files
echo ""
echo "✅ Checking security files..."
SECURITY_DIRS=("includes" "admin" "public" "templates")
for dir in "${SECURITY_DIRS[@]}"; do
    if [ -f "$dir/index.php" ]; then
        echo "   ✓ $dir/index.php"
    else
        echo "   ⚠ Missing: $dir/index.php (security best practice)"
    fi
done

echo ""
echo "✅ All checks passed!"
echo ""
echo "Ready for release! 🚀"
