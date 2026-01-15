# WordPress.org Repository Submission Checklist

## Pre-Submission Requirements

### 1. Plugin Header ✅
- [x] Plugin Name: WP Context AI Search
- [x] Version: 1.0.0
- [x] Author: Bojan Krsmanovic
- [x] Author URI: https://deeq.io
- [x] License: GPL v2 or later
- [x] License URI: https://www.gnu.org/licenses/gpl-2.0.html
- [x] Text Domain: wp-context-ai-search
- [x] Domain Path: /languages

### 2. README.txt ✅
- [x] Stable tag: 1.0.0
- [x] Requires at least: 5.8
- [x] Tested up to: 6.4
- [x] Requires PHP: 7.4
- [x] License: GPLv2 or later
- [x] Complete description
- [x] Installation instructions
- [x] FAQ section
- [x] Changelog section

### 3. Code Quality ✅
- [x] All PHP files pass syntax check
- [x] No TODO/FIXME comments in production code
- [x] Proper sanitization and validation
- [x] Nonce verification on forms
- [x] Capability checks for admin functions
- [x] Proper escaping for output

### 4. Security ✅
- [x] Input sanitization (sanitize_text_field, sanitize_textarea_field)
- [x] Output escaping (esc_html, esc_attr, esc_url)
- [x] Nonce verification on AJAX requests
- [x] Capability checks (current_user_can)
- [x] SQL injection prevention (prepared statements)
- [x] XSS protection

### 5. Internationalization ✅
- [x] All user-facing strings are translatable
- [x] Text domain: wp-context-ai-search
- [x] .pot file generated
- [x] Translation files included (.po files)

### 6. File Structure ✅
- [x] Main plugin file: wp-context-ai-search.php
- [x] README.txt for WordPress.org
- [x] LICENSE.txt (GPL v2)
- [x] Languages folder with .pot file
- [x] Proper file organization

### 7. Freemius Integration ⚠️
- [x] Plugin ID configured: 23024
- [x] Auto-deactivation code present
- [ ] **IMPORTANT**: Remove `wp_org_gatekeeper` line before submitting to WordPress.org
- [ ] **IMPORTANT**: Ensure premium code is properly gated with `@fs_premium_only`

### 8. Required Files ✅
- [x] wp-context-ai-search.php (main file)
- [x] README.txt (WordPress.org readme)
- [x] LICENSE.txt (GPL v2)
- [x] languages/wp-context-ai-search.pot
- [x] index.php files in directories (security)

### 9. Optional but Recommended ✅
- [x] CHANGELOG.md
- [x] README.md (GitHub)
- [x] .gitignore
- [x] composer.json (for development)

### 10. WordPress.org Specific Requirements

#### Before Submission:
- [ ] Remove `wp_org_gatekeeper` from Freemius config
- [ ] Test on fresh WordPress installation
- [ ] Test with default theme (Twenty Twenty-Four)
- [ ] Test with minimal plugins active
- [ ] Verify all links work
- [ ] Check for console errors
- [ ] Test all features work correctly

#### Submission Process:
1. Create account on WordPress.org
2. Submit plugin for review
3. Wait for review (can take 1-2 weeks)
4. Address any feedback from reviewers
5. Plugin goes live after approval

### 11. Common Rejection Reasons to Avoid

- ❌ Premium features not properly gated
- ❌ External links to premium sales (must be in readme only)
- ❌ Phone home/telemetry without disclosure
- ❌ Security vulnerabilities
- ❌ Code quality issues
- ❌ Missing translations
- ❌ Broken functionality
- ❌ Violation of WordPress guidelines

### 12. Testing Checklist

#### Functional Testing:
- [ ] Plugin activates without errors
- [ ] Settings page loads correctly
- [ ] API key validation works
- [ ] Search interface displays correctly
- [ ] Search functionality works
- [ ] Caching works
- [ ] Database table creation works
- [ ] All AJAX requests work
- [ ] Error handling works
- [ ] Translations load correctly

#### Browser Testing:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

#### WordPress Version Testing:
- [ ] WordPress 5.8 (minimum)
- [ ] WordPress 6.4 (tested up to)
- [ ] Latest WordPress version

#### PHP Version Testing:
- [ ] PHP 7.4 (minimum)
- [ ] PHP 8.0
- [ ] PHP 8.1
- [ ] PHP 8.2

### 13. Files to Remove Before Submission

**DO NOT include these in WordPress.org submission:**
- [ ] `.git/` directory
- [ ] `node_modules/` directory
- [ ] `vendor/` directory (unless required)
- [ ] `.github/` directory (optional, but not needed)
- [ ] `ITEMS_TO_FILL.md`
- [ ] `WP_ORG_CHECKLIST.md` (this file)
- [ ] Development files (Makefile, composer.json, etc.)
- [ ] Test files (`tests/` directory)
- [ ] Bin scripts (`bin/` directory)

**Keep these:**
- ✅ All PHP files
- ✅ README.txt
- ✅ LICENSE.txt
- ✅ languages/ directory
- ✅ admin/ directory
- ✅ includes/ directory
- ✅ public/ directory
- ✅ templates/ directory
- ✅ freemius/ directory (SDK)

### 14. Final Steps

1. **Create clean zip file** with only necessary files
2. **Test the zip** by installing it on fresh WordPress
3. **Review all code** one more time
4. **Submit to WordPress.org**
5. **Monitor for review feedback**

## Notes

- Freemius integration is allowed on WordPress.org
- Premium features must be properly gated
- External links to premium sales are allowed in readme
- Plugin must work without premium features

## Support

For questions about WordPress.org submission:
- WordPress.org Plugin Directory: https://wordpress.org/plugins/developers/
- Plugin Review Guidelines: https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-handbook/plugin-review/
