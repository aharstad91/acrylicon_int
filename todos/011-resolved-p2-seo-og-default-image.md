# SEO: Create OG default image

**Priority:** P2
**Status:** resolved (2026-06-23)
**Created:** 2026-02-25
**Module:** mu-plugins/acrylicon-seo

## Resolution (2026-06-23)

OG default image was created and shipped in Sprint 1 (commit `109ad2f`, May 2026) —
the todo was just never closed. Verified live, no code change needed:

- `themes/acrylicon-2024/assets/gfx/acrylicon-og-default.jpg` exists: **1200×630**
  (OG standard), deployed to prod.
- `class-open-graph.php` emits it as the site-wide `og:image` fallback for pages
  without a featured image. Confirmed live on the front page: `og:image` →
  `…/acrylicon-og-default.jpg` with `og:image:width 1200` / `og:image:height 630`.

## Problem

Pages without a featured image need a fallback Open Graph image.
The OG module (`mu-plugins/acrylicon-seo/modules/class-open-graph.php`) looks for
`{theme_url}/assets/gfx/acrylicon-og-default.jpg` which does not exist yet.

Without a default OG image, social shares of pages without featured images
will show no preview image on Facebook, LinkedIn, etc.

## Requirements

- File: `themes/acrylicon-2024/assets/gfx/acrylicon-og-default.jpg`
- Dimensions: 1200x630px (OG standard)
- Format: JPG (smaller file size than PNG for photos)
- Content: AcryliCon brand — logo + tagline or product imagery
- Should look good as a social media card preview

## Verification

```bash
# Check OG image output on a page without featured image
curl -s https://acryli-28355.jana-osl.servebolt.cloud/no/ | grep 'og:image'
```
