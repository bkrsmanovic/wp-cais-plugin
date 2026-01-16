=== WP Context AI Search ===
Contributors: bkrsmanovic
Tags: search, ai, artificial intelligence, openai, claude, gemini, context search, smart search
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered search for WordPress content with intelligent, context-aware results powered by OpenAI, Claude, or Gemini.

== Description ==

WP Context AI Search transforms your WordPress site's search functionality with AI-powered, context-aware results. Instead of simple keyword matching, users get intelligent answers that understand the meaning and context of their queries.

= Key Features =

* **AI-Powered Search**: Get intelligent, context-aware search results powered by OpenAI GPT-3.5, Claude (Anthropic), or Gemini (Google)
* **Smart Caching**: Frequently asked questions are cached for faster responses
* **Multi-Strategy Search**: Advanced search algorithms that go beyond basic WordPress search
* **Content Extraction**: Extracts content from Gutenberg blocks, ACF fields, and ACF blocks
* **Customizable UI**: Fully customizable via CSS variables
* **RTL Support**: Full right-to-left support for Arabic and other RTL languages
* **Multilingual**: Includes translations for German, Norwegian, Spanish, Dutch, Serbian, and Arabic
* **Free & Premium**: Free version includes Posts and Pages search; Premium unlocks Custom Post Types (v1.0). JSON, MD files, and external data coming soon.

= Free Version Includes =

* Search Posts and Pages
* AI-powered context-aware responses
* Smart caching system
* Customizable UI with CSS variables
* Multiple AI provider support (OpenAI, Claude, Gemini)
* Contact information footer

= Premium Features =

* Custom Post Types support (Available in v1.0)
* JSON file indexing (Coming soon)
* Markdown file support (Coming soon)
* External file integration (Coming soon)
* Excel/Spreadsheet file support (Coming soon)

= How It Works =

1. User enters a natural language query
2. Plugin searches your WordPress content using advanced algorithms
3. Relevant content is sent to your chosen AI provider
4. AI generates a context-aware response based on your content
5. Results are cached for faster future responses

= Supported AI Providers =

* **OpenAI** (GPT-3.5 Turbo) - Most popular, reliable
* **Claude** (Anthropic) - Excellent for long-form content
* **Gemini** (Google) - Fast and cost-effective

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* API key from one of the supported AI providers

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New
2. Search for "WP Context AI Search"
3. Click "Install Now"
4. Activate the plugin

= Manual Installation =

1. Upload the `wp-context-ai-search` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' screen
3. Go to Settings → WP CAIS to configure

= Configuration =

1. Get your API key from:
   * OpenAI: https://platform.openai.com/api-keys
   * Claude: https://console.anthropic.com/settings/keys
   * Gemini: https://makersuite.google.com/app/apikey

2. Go to Settings → WP CAIS
3. Select your AI provider
4. Enter your API key and click "Test API Key"
6. Add contact information (optional)
7. Save settings

= Usage =

Add the shortcode to any page or post:

`[wp-context-ai-search]`

The search interface will appear with a modern, AI-like interface where users can ask questions and get intelligent answers based on your content.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes, you need an API key from one of the supported AI providers (OpenAI, Claude, or Gemini). The plugin will not work without a valid API key.

= Which AI provider should I use? =

* **OpenAI**: Most popular, reliable, good for general use
* **Claude**: Excellent for long-form content and detailed responses
* **Gemini**: Fast and cost-effective, good for high-volume sites

= Is there a free tier? =

All AI providers offer free tiers with limited usage. Check each provider's pricing page for current limits.

= Can I customize the appearance? =

Yes! The plugin uses CSS variables that can be overridden in your theme. See the documentation for details.

= Does it work with multilingual sites? =

Yes, the plugin includes translations for German, Norwegian, Spanish, Dutch, Serbian, and Arabic. It also supports RTL languages.

= What content types are searchable in the free version? =

The free version supports Posts and Pages. Premium v1.0 includes Custom Post Types support. JSON files, Markdown files, and external data sources are coming soon.

= How does caching work? =

Similar questions are automatically cached to improve performance and reduce API costs. The cache is stored in a custom database table.

= Can I use multiple AI providers? =

You can switch between providers in the settings, but only one provider can be active at a time.

== Screenshots ==

1. Search interface with modern AI-like design
2. Admin settings page with AI provider selection
3. Database status and cache management
4. Database status and cache management
5. Contact information settings

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered search with OpenAI, Claude, and Gemini support
* Smart caching system
* Multi-strategy search algorithms
* Content extraction from Gutenberg and ACF
* Customizable UI with CSS variables
* RTL support for Arabic
* Translations for 6 languages (German, Norwegian, Spanish, Dutch, Serbian, Arabic)
* Admin interface with API key validation
* Quota monitoring and management
* Contact information footer

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and configure your API key to get started.

== Support ==

For support, feature requests, and bug reports:
* Website: https://deeq.io
* Email: bojan@deeq.io
* GitHub: https://github.com/bkrsmanovic/wp-cais-plugin

== Credits ==

Developed by Bojan Krsmanovic

== License ==

This plugin is licensed under the GPL v2 or later.

== Privacy ==

This plugin sends search queries and content excerpts to third-party AI services (OpenAI, Claude, or Gemini) to generate responses. No personal user data is collected or stored by the plugin itself. Please review each AI provider's privacy policy:
* OpenAI: https://openai.com/policies/privacy-policy
* Anthropic: https://www.anthropic.com/privacy
* Google: https://policies.google.com/privacy
