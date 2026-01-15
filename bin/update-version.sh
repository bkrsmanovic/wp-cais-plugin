#!/bin/bash
# Update version across all files
# Usage: ./bin/update-version.sh 1.0.1

if [ -z "$1" ]; then
    echo "Usage: ./bin/update-version.sh <version>"
    echo "Example: ./bin/update-version.sh 1.0.1"
    exit 1
fi

VERSION=$1

echo "Updating version to $VERSION..."

# Update main plugin file
sed -i "s/Version: [0-9.]*/Version: $VERSION/" wp-context-ai-search.php
sed -i "s/define( 'WP_CAIS_VERSION', '[^']*'/define( 'WP_CAIS_VERSION', '$VERSION'/" wp-context-ai-search.php

# Update README.txt
sed -i "s/Stable tag: [0-9.]*/Stable tag: $VERSION/" README.txt

# Update changelog in README.txt
CURRENT_DATE=$(date +"%Y-%m-%d")
sed -i "/^== Changelog ==/a\\
\\
= $VERSION =\\
* Version $VERSION update" README.txt

echo "✓ Version updated to $VERSION"
echo "✓ Updated files:"
echo "  - wp-context-ai-search.php"
echo "  - README.txt"
