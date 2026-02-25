---
title: "Sitemap 301 redirect loop — Yoast regex matcher WordPress core URLs"
category: seo
tags: [sitemap, redirect-loop, regex, yoast-migration, wp-sitemap, 301]
module: mu-plugins/acrylicon-seo
symptom: "wp-sitemap.xml returns 301 redirect loop on both blogs"
root_cause: "Yoast redirect regex [a-z-]+-sitemap matched wp-sitemap"
date: 2026-02-25
---

# Sitemap 301 redirect loop — Yoast regex matches WordPress core URLs

## Symptom

`wp-sitemap.xml` returns a 301 redirect loop on both `/no/` and `/` blogs. Response header shows `x-redirect-by: WordPress` — confirming the redirect comes from PHP, not `.htaccess`.

```
HTTP/2 301
x-redirect-by: WordPress
location: https://acryli-28355.jana-osl.servebolt.cloud/no/wp-sitemap.xml
```

## Root Cause

The Yoast sitemap redirect handler in `class-sitemap-integration.php` used this regex to catch old Yoast per-type sitemap URLs:

```php
preg_match( '#/[a-z-]+-sitemap\d*\.xml#', $uri )
```

This was intended to match Yoast URLs like `/post-sitemap.xml`, `/page-sitemap1.xml`. But `[a-z-]+` also matches `wp-`, so `wp-sitemap.xml` matched the pattern. The handler then redirected to `home_url('/wp-sitemap.xml')` — creating an infinite 301 loop.

## Solution

Add an early exit for WordPress core sitemap URLs before the Yoast regex check:

```php
// Don't redirect WordPress core sitemap URLs (wp-sitemap*.xml)
if ( strpos( $uri, 'wp-sitemap' ) !== false ) {
    return;
}
```

File: `mu-plugins/acrylicon-seo/modules/class-sitemap-integration.php`

## Verification

```bash
# Core sitemaps return 200
curl -sI https://example.com/no/wp-sitemap.xml | head -3
curl -sI https://example.com/wp-sitemap.xml | head -3

# Old Yoast URLs still redirect to core sitemap
curl -sI https://example.com/no/sitemap_index.xml | head -3
curl -sI https://example.com/no/post-sitemap.xml | head -3
```

## Prevention

When writing redirect handlers that match URL patterns:

1. **Test regex against your own URLs** — not just the legacy URLs you're trying to catch
2. **Whitelist before blacklist** — exit early for known-good URLs before applying catch-all patterns
3. **Be specific in regex** — `/[a-z]+-sitemap` is too broad. Could use a negative lookahead or explicit prefix list instead

## Related

- `docs/solutions/seo/custom-seo-module-replace-yoast.md` — parent solution doc
