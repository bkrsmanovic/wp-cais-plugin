# Testing Checklist for v1.0.0

## Pre-Release Testing

### 1. Installation & Activation ✅
- [ ] Plugin installs without errors
- [ ] Plugin activates without errors
- [ ] No PHP errors in debug log
- [ ] Database table is created on activation
- [ ] No JavaScript console errors

### 2. Admin Settings Page ✅
- [ ] Settings page loads correctly
- [ ] All sections display properly
- [ ] Post type checkboxes work
- [ ] API provider selection works
- [ ] API key input field works
- [ ] "Test API Key" button works
- [ ] Quota information displays (if available)
- [ ] Custom text fields work (title, subtitle, placeholder, welcome message)
- [ ] Contact information fields work
- [ ] Settings save correctly
- [ ] Form validation works
- [ ] Premium upgrade banner displays (if not premium)
- [ ] Free plan notice displays (if not premium)

### 3. API Configuration ✅
- [ ] OpenAI API key validation works
- [ ] Claude API key validation works
- [ ] Gemini API key validation works
- [ ] Invalid API keys show error
- [ ] Empty API keys show error
- [ ] Quota information displays for OpenAI
- [ ] Provider switching works

### 4. Search Functionality ✅
- [ ] Shortcode `[wp-context-ai-search]` works
- [ ] Search form displays correctly
- [ ] Custom title displays (if set)
- [ ] Custom subtitle displays (if set)
- [ ] Custom placeholder displays (if set)
- [ ] Custom welcome message displays (if set)
- [ ] Search input accepts text
- [ ] Submit button works
- [ ] Loading state displays
- [ ] Search results display
- [ ] AI responses are generated
- [ ] Sources are listed
- [ ] Error messages display correctly
- [ ] No results message displays when appropriate
- [ ] Contact information displays in footer (if set)

### 5. Caching ✅
- [ ] Cache table is created
- [ ] Similar queries use cache
- [ ] Cache hit count increments
- [ ] Different queries don't collide
- [ ] Cache persists across page loads

### 6. Content Extraction ✅
- [ ] Post content is extracted
- [ ] Post titles are extracted
- [ ] Post excerpts are extracted
- [ ] Gutenberg blocks are extracted
- [ ] ACF fields are extracted
- [ ] ACF blocks are extracted
- [ ] HTML entities are decoded correctly

### 7. Premium Features ✅
- [ ] Custom post types are gated (premium only)
- [ ] Premium notice displays for free users
- [ ] Upgrade links work
- [ ] Premium features don't work for free users

### 8. Security ✅
- [ ] Nonce verification works on AJAX
- [ ] Input sanitization works
- [ ] Output escaping works
- [ ] Capability checks work
- [ ] SQL injection prevention works

### 9. Internationalization ✅
- [ ] Translations load correctly
- [ ] RTL support works (Arabic)
- [ ] Text domain is correct
- [ ] Custom text overrides translations

### 10. Browser Compatibility ✅
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### 11. WordPress Compatibility ✅
- [ ] WordPress 5.8 (minimum)
- [ ] WordPress 6.4 (tested up to)
- [ ] Latest WordPress version

### 12. PHP Compatibility ✅
- [ ] PHP 7.4 (minimum)
- [ ] PHP 8.0
- [ ] PHP 8.1
- [ ] PHP 8.2

### 13. Error Handling ✅
- [ ] API errors are handled gracefully
- [ ] Network errors are handled
- [ ] Invalid responses are handled
- [ ] User-friendly error messages display

### 14. Performance ✅
- [ ] Page load time is acceptable
- [ ] AJAX requests are fast
- [ ] Caching improves performance
- [ ] No memory leaks

### 15. UI/UX ✅
- [ ] Search interface is responsive
- [ ] Animations work smoothly
- [ ] Loading states are clear
- [ ] Error states are clear
- [ ] Success states are clear
- [ ] Mobile-friendly

## Quick Test Script

Run these tests manually:

1. **Activate Plugin**
   ```
   - Go to Plugins → Installed Plugins
   - Activate "WP Context AI Search"
   - Check for errors
   ```

2. **Configure API**
   ```
   - Go to Context AI Search → Settings
   - Select AI provider (OpenAI/Claude/Gemini)
   - Enter API key
   - Click "Test API Key"
   - Verify success message
   ```

3. **Test Search**
   ```
   - Create a test page with shortcode: [wp-context-ai-search]
   - View the page
   - Enter a search query
   - Verify results appear
   ```

4. **Test Customization**
   ```
   - Go to Settings
   - Enter custom title, subtitle, placeholder, welcome message
   - Save settings
   - View search interface
   - Verify custom text displays
   ```

5. **Test Caching**
   ```
   - Perform a search
   - Perform the same search again
   - Verify it's faster (cached)
   - Check database for cache entry
   ```

## Automated Tests

Run PHPUnit tests (if configured):
```bash
composer run test
```

Run PHPCS (code standards):
```bash
composer run phpcs
```

## Known Issues

List any known issues here before release.

## Test Results

Date: ___________
Tester: ___________
WordPress Version: ___________
PHP Version: ___________

Results: [ ] Pass [ ] Fail [ ] Needs Review

Notes:
_________________________________________________
_________________________________________________
_________________________________________________
