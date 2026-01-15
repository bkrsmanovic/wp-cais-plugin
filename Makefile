.PHONY: test phpcs phpcbf install-deps version-check

# Install dependencies
install-deps:
	composer install

# Run PHPCS
phpcs:
	composer run phpcs

# Fix PHPCS issues
phpcbf:
	composer run phpcbf

# Run tests
test:
	composer run test

# Check version consistency
version-check:
	@echo "Checking version consistency..."
	@VERSION=$$(grep "Version:" wp-context-ai-search.php | sed 's/.*Version: *//' | sed 's/ *\*\/.*//'); \
	CONST_VERSION=$$(grep "define( 'WP_CAIS_VERSION'" wp-context-ai-search.php | sed "s/.*'WP_CAIS_VERSION', '//" | sed "s/'.*//"); \
	if [ "$$VERSION" = "$$CONST_VERSION" ]; then \
		echo "✓ Versions match: $$VERSION"; \
	else \
		echo "✗ Version mismatch! Header: $$VERSION, Constant: $$CONST_VERSION"; \
		exit 1; \
	fi

# Update version (usage: make version VERSION=1.0.1)
version:
	@if [ -z "$(VERSION)" ]; then \
		echo "Usage: make version VERSION=1.0.1"; \
		exit 1; \
	fi
	@echo "Updating version to $(VERSION)..."
	@sed -i "s/Version: [0-9.]*/Version: $(VERSION)/" wp-context-ai-search.php
	@sed -i "s/define( 'WP_CAIS_VERSION', '[^']*'/define( 'WP_CAIS_VERSION', '$(VERSION)'/" wp-context-ai-search.php
	@sed -i "s/Stable tag: [0-9.]*/Stable tag: $(VERSION)/" README.txt
	@echo "✓ Version updated to $(VERSION)"

# Full check before release
pre-release: version-check phpcs
	@echo "✓ Pre-release checks passed!"
