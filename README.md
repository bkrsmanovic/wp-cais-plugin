# Context AI Search

AI-powered search for WordPress content with free and premium features.

## Features

### Free Version
- Search Posts and Pages
- AI-powered context-aware responses
- Smart caching system
- Customizable UI with CSS variables

### Premium Version
- Custom Post Types support (Available in v1.0)
- JSON file indexing (Coming soon)
- Markdown file support (Coming soon)
- External file integration (Coming soon)
- Excel/Spreadsheet file support (Coming soon)

## Installation

1. Upload the plugin to `/wp-content/plugins/context-ai-search/`
2. Activate the plugin through the 'Plugins' screen
3. Configure API key in Settings → WP CAIS

## Configuration

### API Key Setup
1. Get your OpenAI API key from https://platform.openai.com/api-keys
2. Go to Settings → WP CAIS
3. Enter your API key and click "Test API Key"
4. Add contact information (optional)
5. Add contact information (optional)

### Usage

Add the shortcode to any page:
```
[context-ai-search]
```

## Development

### Requirements
- PHP 7.4+
- WordPress 5.8+
- Composer (for development)

### Setup

```bash
# Install dependencies
composer install

# Run code style checks
composer run phpcs

# Auto-fix code style issues
composer run phpcbf

# Run tests
composer run test
```

### Version Management

Update version across all files:
```bash
make version VERSION=1.0.1
```

Check version consistency:
```bash
make version-check
```

### Customization

Override CSS variables in your theme:

```css
.cais-search-container {
	--cais-primary-color: #your-color;
	--cais-font-size-xl: 36px;
	--cais-container-max-width: 1200px;
}
```

### Premium URL

Set the premium purchase URL via environment variable:
```bash
export CAIS_PREMIUM_URL="https://deeq.io/cais-premium"
```

Or define it in `wp-config.php`:
```php
define( 'CAIS_PREMIUM_URL', 'https://deeq.io/cais-premium' );
```

## Testing

### Running Tests

```bash
# Run tests
composer run test
```

Note: WordPress test environment setup is handled by the test suite automatically.

### Code Standards

This plugin follows WordPress Coding Standards. Run checks with:

```bash
composer run phpcs
```

## File Structure

```
context-ai-search/
├── admin/              # Admin assets (CSS, JS)
├── includes/           # Core classes
│   ├── internal/      # Base classes (singleton, template loader)
│   └── templates.php  # Template loader instance
├── public/            # Frontend assets
├── templates/         # Template files
├── tests/             # Test files
└── context-ai-search.php  # Main plugin file
```

## License

GPL v2 or later

## Support

For support and premium features, visit: https://deeq.io

## Author

**Bojan Krsmanovic**
- Email: bojan@deeq.io
- Website: https://deeq.io
