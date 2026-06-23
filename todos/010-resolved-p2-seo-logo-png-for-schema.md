# SEO: Create logo PNG for Schema.org

**Priority:** P2
**Status:** resolved (2026-06-23)
**Created:** 2026-02-25
**Module:** mu-plugins/acrylicon-seo

## Resolution (2026-06-23)

PNG was created and shipped in Sprint 1 (commit `109ad2f`, May 2026) — the todo
was just never closed. Verified live:

- `themes/acrylicon-2024/assets/gfx/acrylicon-logo-dark.png` exists: **1024×222**,
  transparent (alpha), HTTP 200 (`image/png`) on prod.
- Referenced in the live Organization JSON-LD on both acrylicon.no and acrylicon.com.
- **Fixed today:** `data/organization.php` declared `width:600 / height:120`, which did
  not match the actual file. Corrected to `1024×222` so the schema's stated dimensions
  match the real image. Deployed + cache-flushed; live schema now reports 1024×222.

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
