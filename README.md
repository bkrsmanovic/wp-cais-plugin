# WP Context AI Search

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

1. Upload the plugin to `/wp-content/plugins/wp-context-ai-search/`
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
[wp-context-ai-search]
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
# or
./bin/update-version.sh 1.0.1
```

Check version consistency:
```bash
make version-check
```

### Customization

Override CSS variables in your theme:

```css
.wp-cais-search-container {
	--wp-cais-primary-color: #your-color;
	--wp-cais-font-size-xl: 36px;
	--wp-cais-container-max-width: 1200px;
}
```

### Premium URL

Set the premium purchase URL via environment variable:
```bash
export WP_CAIS_PREMIUM_URL="https://deeq.io/wp-cais-premium"
```

Or define it in `wp-config.php`:
```php
define( 'WP_CAIS_PREMIUM_URL', 'https://deeq.io/wp-cais-premium' );
```

## Testing

### Running Tests

```bash
# Install WordPress test environment (first time only)
./bin/install-wp-tests.sh db_name db_user db_pass

# Run tests
composer run test
```

### Code Standards

This plugin follows WordPress Coding Standards. Run checks with:

```bash
composer run phpcs
```

## File Structure

```
wp-context-ai-search/
├── admin/              # Admin assets (CSS, JS)
├── includes/           # Core classes
│   ├── internal/      # Base classes (singleton, template loader)
│   └── templates.php  # Template loader instance
├── public/            # Frontend assets
├── templates/         # Template files
├── tests/             # Test files
└── wp-context-ai-search.php  # Main plugin file
```

## License

GPL v2 or later

## Support

For support and premium features, visit: https://deeq.io

## Author

**Bojan Krsmanovic**
- Email: bojan@deeq.io
- Website: https://deeq.io
