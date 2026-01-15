# Changelog

All notable changes to WP Context AI Search will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-12-20

### Added
- Initial release
- Free version with Posts and Pages search support
- AI-powered context-aware search using OpenAI, Claude, and Gemini
- Admin settings page with configuration options
- Contact information management
- Smart caching system for similar queries
- Template-based architecture with singleton pattern
- CSS variables for easy customization
- RTL (Right-to-Left) support for Arabic
- Multilingual support with translations for 6 languages (German, Norwegian, Spanish, Dutch, Serbian, Arabic)
- Premium features bridge
- Plugin row meta with premium link
- Database table management with automatic creation
- API quota monitoring and display
- Comprehensive test suite
- WordPress coding standards compliance
- Version management tools
- Type declarations and modern PHP features

### Features
- Shortcode: `[wp-context-ai-search]`
- Gutenberg blocks content extraction
- ACF fields and blocks support
- HTML entity decoding
- Multi-strategy search algorithm with phrase matching
- API key validation for all providers
- Responsive design
- Modern AI-style UI
- Object cache support ready
- Input validation and sanitization
- Rate limiting ready
- Error handling and user-friendly messages

### Security
- Nonce verification on all AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- SQL injection prevention
- XSS protection

### Performance
- Smart query caching
- Database optimization
- Efficient content extraction
- Minimal API calls
