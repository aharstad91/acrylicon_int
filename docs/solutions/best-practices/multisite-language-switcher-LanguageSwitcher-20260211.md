---
module: Language Switcher
date: 2026-02-11
problem_type: best_practice
component: frontend_stimulus
symptoms:
  - "No way to switch between Norwegian and English site versions"
  - "URL doubling when using home_url() with REQUEST_URI in multisite"
  - "9 redundant switch_to_blog() calls per page load"
  - "Multisite path /norway/ not following ISO standard country codes"
root_cause: missing_workflow_step
resolution_type: code_fix
severity: medium
tags: [wordpress, multisite, language-switcher, hreflang, seo, i18n, url-mapping, slug-mapping, iso-country-codes]
---

# WordPress Multisite Language Switcher with Bidirectional Slug Mapping

## Problem

Acrylicon's WordPress multisite (Norwegian blog 3 + English blog 1) had no way for users to switch between language versions. Additionally, the Norwegian site used `/norway/` as its path instead of the ISO standard `/no/`.

## Environment

- Module: Language Switcher (`inc/language-switcher.php`)
- WordPress: 6.8.3
- PHP: 8.4 (prod), 8.1 (local/MAMP)
- Affected Component: Theme header/footer, hreflang SEO tags
- Date: 2026-02-11

## Symptoms

- No language switching UI existed anywhere on the site
- No hreflang tags in `<head>` for SEO
- Norwegian site used `/norway/` instead of standard `/no/`
- When building URLs, `home_url($_SERVER['REQUEST_URI'])` doubled the path (e.g., `/acrylicon/acrylicon/norway/`)

## What Didn't Work

**Attempted Solution 1:** Using `home_url($_SERVER['REQUEST_URI'])` for self-reference URLs
- **Why it failed:** In MAMP multisite, `home_url()` already includes `/acrylicon/no/` base path, and `REQUEST_URI` also starts with `/acrylicon/no/`, causing path doubling: `/acrylicon/no/acrylicon/no/`

**Attempted Solution 2:** Using `esc_url()` in return values of URL functions
- **Why it failed:** Double-escaping when callers also call `esc_url()`. WordPress convention: return raw URLs from functions, escape at output point only.

## Solution

### 1. Language Switcher Architecture

Created `inc/language-switcher.php` with these key functions:

```php
// Language configuration — single source of truth
function acrylicon_get_languages() {
    return [
        1 => ['code' => 'en', 'hreflang' => 'en', 'label' => 'English', 'flag' => 'gb', 'prefix' => '/'],
        3 => ['code' => 'no', 'hreflang' => 'nb', 'label' => 'Norsk',   'flag' => 'no', 'prefix' => '/no/'],
    ];
}
```

### 2. Bidirectional Slug Mapping

```php
// Norwegian slug => English slug (single direction defined, flip for reverse)
function acrylicon_slug_map() {
    return [
        'pages' => [
            'fordeler'     => 'benefits',
            'bruksomrader' => 'applications',
            'produkter'    => 'products',
            'referanser'   => 'references',
            // ... more mappings
        ],
        'taxonomies' => [
            'referanser-type'       => 'reference-type',
            'referanser-kategorier' => 'reference-categories',
        ],
    ];
}

// Lookup with static cache — builds arrays once per page load
function acrylicon_map_slug( $slug, $direction = 'no_to_en' ) {
    static $lookup = [];
    if ( empty( $lookup ) ) {
        $map = acrylicon_slug_map();
        $all = array_merge( $map['pages'], $map['taxonomies'] );
        $lookup['no_to_en'] = $all;
        $lookup['en_to_no'] = array_flip( $all );
    }
    return $lookup[ $direction ][ $slug ] ?? $slug;
}
```

### 3. URL Construction (Avoiding the Doubling Bug)

```php
// WRONG: doubles the path in MAMP multisite
$url = home_url( $_SERVER['REQUEST_URI'] );

// CORRECT: strip site base path first, then use home_url() with relative path
$site_path = wp_parse_url( home_url(), PHP_URL_PATH );
if ( $site_path && $site_path !== '/' ) {
    $full_prefix = rtrim( $site_path, '/' ) . '/';
    if ( strpos( $path, $full_prefix ) === 0 ) {
        $path = substr( $path, strlen( $full_prefix ) );
    }
}
$path = trim( $path, '/' );
$url  = home_url( '/' . $path . '/' );
```

### 4. Static Caching for switch_to_blog() Calls

```php
// acrylicon_get_equivalent_url() is called 9 times per page (header + mobile + footer × 2 languages + hreflang × 2 + x-default)
// But only 2 unique blog IDs — cache eliminates 7 redundant calls
function acrylicon_get_equivalent_url( $target_blog_id ) {
    static $cache = [];
    if ( isset( $cache[ $target_blog_id ] ) ) {
        return $cache[ $target_blog_id ];
    }
    // ... expensive switch_to_blog() logic ...
    $cache[ $target_blog_id ] = $target_url;
    return $cache[ $target_blog_id ];
}
```

### 5. Changing Multisite Site Path (/norway/ → /no/)

```bash
# 1. Update wp_blogs table
wp db query "UPDATE wp_blogs SET path = '/no/' WHERE blog_id = 3;"

# 2. Update siteurl and home options
wp db query "UPDATE wp_3_options SET option_value = 'https://example.com/no' WHERE option_name IN ('siteurl', 'home');"

# 3. Add 301 redirect in .htaccess (BEFORE WordPress rewrite rules)
# RewriteRule ^norway(/.*)?$ /no$1 [R=301,L]

# 4. Update language switcher prefix config
# 'prefix' => '/no/',
```

### 6. Hreflang Tags

```php
function acrylicon_hreflang_tags() {
    $languages   = acrylicon_get_languages();
    $english_url = '';
    foreach ( $languages as $blog_id => $lang ) {
        $url = acrylicon_get_equivalent_url( $blog_id );
        if ( $blog_id === 1 ) { $english_url = $url; }
        printf( '<link rel="alternate" hreflang="%s" href="%s" />', esc_attr( $lang['hreflang'] ), esc_url( $url ) );
    }
    // x-default = English (reuse, don't call again)
    printf( '<link rel="alternate" hreflang="x-default" href="%s" />', esc_url( $english_url ) );
}
add_action( 'wp_head', 'acrylicon_hreflang_tags', 1 );
```

## Why This Works

1. **URL doubling**: `home_url()` prepends the full site URL including base path. `REQUEST_URI` also contains the base path. Stripping the site path from REQUEST_URI before passing to `home_url()` prevents doubling.

2. **Bidirectional mapping**: Defining mappings in one direction (NO→EN) and using `array_flip()` for the reverse avoids duplicate data and keeps the map DRY.

3. **Static caching**: `switch_to_blog()` triggers database queries. Caching results by blog ID eliminates 7 of 9 calls per page load since only 2 unique blog IDs exist.

4. **ISO country codes**: `/no/` follows ISO 3166-1 alpha-2 standard, making it consistent if adding `/se/`, `/dk/`, `/de/` later. The 301 redirect preserves SEO juice from existing `/norway/` URLs.

5. **hreflang**: Using `nb` (Norwegian Bokmål) instead of `no` for hreflang is the correct ISO 639-1 code for written Norwegian.

## Prevention

- **Always strip site base path** before constructing URLs in WordPress multisite — never pass `REQUEST_URI` directly to `home_url()`
- **Use ISO country codes** for multisite paths from the start (`/no/`, `/se/`, not `/norway/`, `/sweden/`)
- **Return raw URLs from functions**, escape with `esc_url()` only at output point (WordPress convention)
- **Add static caching** to any function that calls `switch_to_blog()` and gets called multiple times per page
- **Add slug mappings** to `acrylicon_slug_map()` when creating new pages/taxonomies on either blog
- **Use `nb` not `no`** for Norwegian Bokmål in hreflang tags

## Files

- `themes/acrylicon-2024/inc/language-switcher.php` — All switcher logic
- `themes/acrylicon-2024/functions.php` — Requires the switcher file
- `themes/acrylicon-2024/header.php` — Desktop + mobile switcher integration
- `themes/acrylicon-2024/footer.php` — Footer switcher + dropdown JS
- `themes/acrylicon-2024/assets/gfx/globe.svg` — Globe icon
- `themes/acrylicon-2024/assets/gfx/flags/no.svg` — Norwegian flag
- `themes/acrylicon-2024/assets/gfx/flags/gb.svg` — British flag
- `.htaccess` — 301 redirect from `/norway/*` to `/no/*`

## Related Issues

- See also: [multisite-content-sync-implementation.md](../wordpress-plugins/multisite-content-sync-implementation.md) — Content sync plugin for same multisite setup
- See also: [missing-file-validation-multisite-sync-20260211.md](../security-issues/missing-file-validation-multisite-sync-20260211.md) — Security fix for multisite media sync
