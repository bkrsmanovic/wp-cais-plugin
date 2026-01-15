# Git Push Instructions for v1.0.0

## Initial Setup (if not done)

```bash
cd /home/bkrsmanovic/work/deeq/dq-wp-search/app/public/wp-content/plugins/wp-context-ai-search

# Initialize git (if not already done)
git init

# Add remote (if not already done)
git remote add origin git@github.com:bkrsmanovic/wp-cais-plugin.git

# Verify remote
git remote -v
```

## Pre-Commit Checklist

- [x] All PHP files pass syntax check
- [x] Version is 1.0.0 everywhere
- [x] Year is 2026
- [x] LICENSE.txt is present
- [x] README.txt is complete
- [x] No TODO/FIXME comments
- [x] GitHub Issues templates created
- [x] All tests pass

## Commit and Push

```bash
# Add all files
git add .

# Commit with version tag
git commit -m "Release v1.0.0 - Initial release

- AI-powered search with OpenAI, Claude, and Gemini support
- Smart caching system
- Custom text customization
- Premium features with Freemius integration
- Multi-language support
- RTL support
- Complete admin interface
- Security hardened
- WordPress.org ready"

# Push to master branch
git push -u origin master

# Create version tag
git tag -a v1.0.0 -m "Version 1.0.0 - Initial release"
git push origin v1.0.0
```

## After Push

1. **Verify on GitHub**
   - Check repository: https://github.com/bkrsmanovic/wp-cais-plugin
   - Verify all files are present
   - Check Issues tab is enabled

2. **Enable GitHub Issues**
   - Go to repository Settings
   - Scroll to "Features" section
   - Ensure "Issues" is checked/enabled

3. **Set Repository Description**
   - Go to repository Settings
   - Update description: "AI-powered search for WordPress with OpenAI, Claude, and Gemini support"

4. **Add Topics/Tags**
   - wordpress
   - wordpress-plugin
   - ai-search
   - openai
   - claude
   - gemini
   - freemius

5. **Create Release on GitHub**
   - Go to Releases → Create a new release
   - Tag: v1.0.0
   - Title: Version 1.0.0 - Initial Release
   - Description: Copy from CHANGELOG.md
   - Mark as "Latest release"

## GitHub Issues Setup

GitHub Issues is now configured with:
- Bug Report template
- Feature Request template
- Contributing guidelines

Users can report issues at:
https://github.com/bkrsmanovic/wp-cais-plugin/issues

## Note on Freemius

Freemius does offer support/help desk features, but GitHub Issues is still useful for:
- Public bug tracking
- Community feature requests
- Open source collaboration
- Transparency

You can use both:
- GitHub Issues: Public bug reports and feature requests
- Freemius Support: Premium customer support
