.PHONY: test phpcs phpcbf install-deps version-check version release package

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
	CONST_VERSION=$$(grep "define( 'CAIS_VERSION'" wp-context-ai-search.php | sed "s/.*'CAIS_VERSION', '//" | sed "s/'.*//"); \
	if [ "$$VERSION" = "$$CONST_VERSION" ]; then \
		echo "✓ Versions match: $$VERSION"; \
	else \
		echo "✗ Version mismatch! Header: $$VERSION, Constant: $$CONST_VERSION"; \
		exit 1; \
	fi

# Get current version
get-version:
	@VERSION=$$(grep "Version:" wp-context-ai-search.php | sed 's/.*Version: *//' | sed 's/ *\*\/.*//'); \
	echo "$$VERSION"

# Update version in all files (usage: make version VERSION=1.0.1)
version:
	@if [ -z "$(VERSION)" ]; then \
		echo "Usage: make version VERSION=1.0.1"; \
		exit 1; \
	fi
	@echo "Updating version to $(VERSION) in all files..."
	@# Main plugin file - header and constant
	@sed -i "s/Version: [0-9.]*/Version: $(VERSION)/" wp-context-ai-search.php
	@sed -i "s/define( 'CAIS_VERSION', '[^']*'/define( 'CAIS_VERSION', '$(VERSION)'/" wp-context-ai-search.php
	@# package.json
	@sed -i 's/"version": "[^"]*"/"version": "$(VERSION)"/' package.json
	@# README.txt - stable tag and changelog sections
	@sed -i "s/Stable tag: [0-9.]*/Stable tag: $(VERSION)/" README.txt
	@sed -i "s/= [0-9.]* =/= $(VERSION) =/g" README.txt
	@# Language files (.po and .pot)
	@find languages -name "*.po" -o -name "*.pot" | xargs sed -i "s/Project-Id-Version: Context AI Search [0-9.]*/Project-Id-Version: Context AI Search $(VERSION)/"
	@# Documentation files
	@sed -i "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$(VERSION)/g" GIT_PUSH_INSTRUCTIONS.md 2>/dev/null || true
	@sed -i "s/Version is [0-9.]*/Version is $(VERSION)/" GIT_PUSH_INSTRUCTIONS.md 2>/dev/null || true
	@sed -i "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$(VERSION)/g" RELEASE_SUMMARY.md 2>/dev/null || true
	@sed -i "s/\*\*Version\*\*: [0-9.]*/\*\*Version\*\*: $(VERSION)/" RELEASE_SUMMARY.md 2>/dev/null || true
	@sed -i "s/v[0-9]\+\.[0-9]\+\.[0-9]\+/v$(VERSION)/g" TESTING_CHECKLIST.md 2>/dev/null || true
	@sed -i "s/Version: [0-9.]*/Version: $(VERSION)/" WP_ORG_CHECKLIST.md 2>/dev/null || true
	@sed -i "s/Stable tag: [0-9.]*/Stable tag: $(VERSION)/" WP_ORG_CHECKLIST.md 2>/dev/null || true
	@echo "✓ Version updated to $(VERSION) in all files"

# Prepare release: update version and add CHANGELOG entry template
# Usage: make release VERSION=1.0.1
# Then edit CHANGELOG.md to fill in the details
release: version
	@if [ -z "$(VERSION)" ]; then \
		echo "Usage: make release VERSION=1.0.1"; \
		exit 1; \
	fi
	@echo "Preparing release $(VERSION)..."
	@# Add new entry to CHANGELOG.md if it doesn't exist
	@if ! grep -q "## \[$(VERSION)\]" CHANGELOG.md; then \
		DATE=$$(date +%Y-%m-%d); \
		{ \
			echo ""; \
			echo "## [$(VERSION)] - $$DATE"; \
			echo ""; \
			echo "### Added"; \
			echo "- "; \
			echo ""; \
			echo "### Changed"; \
			echo "- "; \
			echo ""; \
			echo "### Fixed"; \
			echo "- "; \
			echo ""; \
		} > /tmp/changelog_entry.txt; \
		sed -i "/^## \[/r /tmp/changelog_entry.txt" CHANGELOG.md; \
		rm /tmp/changelog_entry.txt; \
		echo "✓ Added CHANGELOG.md entry template for $(VERSION)"; \
		echo "  Please edit CHANGELOG.md to fill in the details"; \
	else \
		echo "⚠ CHANGELOG.md already has entry for $(VERSION)"; \
	fi
	@echo ""
	@echo "✓ Release preparation complete!"
	@echo ""
	@echo "Next steps:"
	@echo "1. Edit CHANGELOG.md to fill in the changes for $(VERSION)"
	@echo "2. Review all changes: git diff"
	@echo "3. Commit: git add . && git commit -m 'Release v$(VERSION) - [your description]'"
	@echo "4. Tag: git tag -a v$(VERSION) -m 'Version $(VERSION) - [your description]'"
	@echo "5. Push: git push origin master && git push origin v$(VERSION)"

# Full check before release
pre-release: version-check phpcs
	@echo "✓ Pre-release checks passed!"

# Package plugin for WordPress.org submission
# Usage: make package
package:
	@echo "Packaging plugin for WordPress.org submission..."
	@VERSION=$$(grep "Version:" wp-context-ai-search.php | sed 's/.*Version: *//' | sed 's/ *\*\/.*//'); \
	PLUGIN_NAME="context-ai-search"; \
	ZIP_NAME="$${PLUGIN_NAME}-$${VERSION}.zip"; \
	TEMP_DIR="/tmp/$${PLUGIN_NAME}-package"; \
	rm -rf "$$TEMP_DIR" "$$ZIP_NAME"; \
	mkdir -p "$$TEMP_DIR/$${PLUGIN_NAME}"; \
	echo "Copying files..."; \
	cp -r admin "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp -r freemius "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp -r includes "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp -r languages "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp -r public "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp -r templates "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp wp-context-ai-search.php "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp README.txt "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp LICENSE.txt "$$TEMP_DIR/$${PLUGIN_NAME}/"; \
	cp CHANGELOG.md "$$TEMP_DIR/$${PLUGIN_NAME}/" 2>/dev/null || true; \
	echo "Cleaning up hidden files and development files..."; \
	find "$$TEMP_DIR/$${PLUGIN_NAME}" -name ".DS_Store" -delete 2>/dev/null || true; \
	find "$$TEMP_DIR/$${PLUGIN_NAME}" -name ".editorconfig" -delete 2>/dev/null || true; \
	find "$$TEMP_DIR/$${PLUGIN_NAME}" -name ".git*" -delete 2>/dev/null || true; \
	echo "Creating zip file..."; \
	CURRENT_DIR=$$(pwd); \
	cd "$$TEMP_DIR" && zip -r "$$ZIP_NAME" "$${PLUGIN_NAME}" -q && mv "$$ZIP_NAME" "$$CURRENT_DIR/" && echo "Zip created successfully"; \
	cd - > /dev/null; \
	rm -rf "$$TEMP_DIR"; \
	if [ ! -f "$$CURRENT_DIR/$$ZIP_NAME" ]; then \
		echo "ERROR: Zip file was not created!"; \
		exit 1; \
	fi; \
	echo ""; \
	echo "✓ Package created: $$ZIP_NAME"; \
	echo ""; \
	echo "⚠️  IMPORTANT: Before submitting to WordPress.org:"; \
	echo "   1. Test the zip on a fresh WordPress installation"; \
	echo "   2. Verify all features work correctly"; \
	echo ""; \
	echo "Package location: $$(pwd)/$$ZIP_NAME"
