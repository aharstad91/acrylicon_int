# SEO: Create logo PNG for Schema.org

**Priority:** P2
**Status:** pending
**Created:** 2026-02-25
**Module:** mu-plugins/acrylicon-seo

## Problem

Google's structured data requires logo as PNG/WebP ImageObject, not SVG.
The Organization schema in `mu-plugins/acrylicon-seo/data/organization.php` references
`{theme_url}/assets/gfx/acrylicon-logo-dark.png` which does not exist yet.

## Requirements

- File: `themes/acrylicon-2024/assets/gfx/acrylicon-logo-dark.png`
- Minimum 112x112px (Google requirement)
- Recommended: 512x512px or larger
- Source: Export from existing SVG (`acrylicon-logo-dark.svg`)
- Background: Transparent

## Verification

```bash
# Check schema output includes logo
curl -s https://acryli-28355.jana-osl.servebolt.cloud/no/ | grep -o '"logo":{[^}]*}'
```
