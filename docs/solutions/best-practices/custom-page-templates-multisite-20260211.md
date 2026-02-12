---
module: Theme Templates
date: 2026-02-11
problem_type: best_practice
component: wordpress_theme
symptoms:
  - "Need hardcoded content pages with version control"
  - "Cross-blog WP_Query returns 0 results for CPT"
  - "Kontor CPT posts live on blog 3 but need display on blog 1"
root_cause: missing_workflow_step
resolution_type: code_fix
severity: medium
tags: [wordpress, multisite, page-templates, switch-to-blog, custom-page-templates, kontor-cpt, svg-flags]
---

# Custom Page Templates with Cross-Blog Queries in WordPress Multisite

## Problem

Building new content pages (Factory, Locations) on the international English site (blog 1) that need to:
1. Display hardcoded content in version-controlled PHP templates
2. Query Norwegian office data (Kontor CPT) that lives on blog 3
3. Render country flag SVGs safely via `svg_icon()` helper

## Environment

- Module: Theme Templates (`page-factory.php`, `page-locations.php`)
- WordPress: 6.8.3 Multisite
- PHP: 8.4 (prod), 8.1 (local/MAMP)
- Theme: `acrylicon-2024` (classic theme, Tailwind CSS)

## Solution

### 1. Custom Page Templates (WordPress Template Hierarchy)

WordPress automatically resolves `page-{slug}.php` for pages with matching slugs. No template assignment needed in the admin — just name the file correctly.

```php
// page-factory.php — auto-used for /factory/ page
// page-locations.php — auto-used for /locations/ page
```

### 2. Cross-Blog WP_Query Pattern

Kontor CPT posts live on blog 3 (Norwegian site). Querying them from blog 1 requires `switch_to_blog()`.

```php
// WRONG: Returns 0 results on blog 1
$offices = new WP_Query(['post_type' => 'kontor']);

// CORRECT: Switch to blog 3 first, validate blog exists
$norway_blog_id = 3;
if ( get_blog_details( $norway_blog_id ) ) :
    switch_to_blog( $norway_blog_id );

    $kontor_query = new WP_Query( [
        'post_type'      => 'kontor',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    // ... render loop using get_field('office_adress'), get_field('office_tel') ...

    wp_reset_postdata();
    restore_current_blog();
endif;
```

**Key gotcha:** The Kontor CPT field name is `office_adress` (typo in original ACF setup, not `office_address`).

### 3. Office Data as Separate PHP Include

International office data stored in `inc/international-offices.php` as a return array:

```php
// inc/international-offices.php
return [
    'australia' => [
        'country' => 'Australia',
        'flag'    => 'au',
        'offices' => [
            [
                'name'    => 'AcryliCon Australia',
                'company' => 'Andersens Floor Coverings PTY Ltd',
                'address' => [ '29 Western Drive', 'Gatton QLD 4343' ],
                'phone'   => '+61 1800 016 016',
                'email'   => 'enquires@acrylicon.com.au',
                'web'     => 'www.acrylicon.com.au',
            ],
        ],
    ],
    // ... 14 more countries
];

// In page-locations.php:
$international_offices = require get_template_directory() . '/inc/international-offices.php';
```

### 4. Language Switcher Slug Mapping

Convention is NO→EN direction. Updated existing `kontakt-oss` mapping (don't add alongside):

```php
// inc/language-switcher.php — acrylicon_slug_map()
'kontakt-oss' => 'locations',  // UPDATED from 'contact-us' to 'locations'
// No entry needed for 'factory' — unmapped slugs return as-is
```

### 5. 301 Redirect from Old URL

Added to existing `$redirects` array in `acrylicon_redirect_old_norwegian_slugs()`:

```php
'contact-us'  => 'locations',
'kontakt-oss' => 'locations',
```

### 6. SVG Flag Security

Strengthened `svg_icon()` sanitization to strip dangerous elements:

```php
$svg = preg_replace('#<foreignObject(.*?)>(.*?)</foreignObject>#is', '', $svg);
$svg = preg_replace('#<use[^>]*/?>#is', '', $svg);
$svg = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $svg);
```

Flags follow existing format: `viewBox="0 0 20 14"`, `rx="1"`, SVGO-optimized.

### 7. Email Protection

All email output uses `antispambot()`:

```php
<a href="mailto:<?php echo esc_attr( antispambot( $office['email'] ) ); ?>">
    <?php echo esc_html( antispambot( $office['email'] ) ); ?>
</a>
```

## Why This Works

1. **Template hierarchy** resolves `page-{slug}.php` automatically — no database template meta needed
2. **`get_blog_details()` check** prevents errors if blog 3 doesn't exist (different environments)
3. **Separate data file** keeps office data grep-able, diff-able, and version-controlled
4. **`svg_icon()` with sanitization** strips XSS vectors from SVG content before inline rendering
5. **antispambot()** entity-encodes emails to prevent harvesting by simple scrapers

## Prevention

- **Always use `switch_to_blog()` / `restore_current_blog()`** when querying cross-blog CPTs in multisite
- **Always validate blog existence** with `get_blog_details()` before switching
- **Use `antispambot()`** on all email addresses in templates
- **Run SVGO** on all SVG assets: `npx svgo --multipass assets/gfx/flags/*.svg`
- **Audit SVGs** for `<script>`, `<foreignObject>`, `on*` attributes before commit
- **Language switcher mapping** is NO→EN direction — update existing entries, don't add new ones

## Files

- `themes/acrylicon-2024/page-factory.php` — Factory page template
- `themes/acrylicon-2024/page-locations.php` — Locations page template
- `themes/acrylicon-2024/inc/international-offices.php` — Office data array
- `themes/acrylicon-2024/inc/language-switcher.php` — Updated slug mapping
- `themes/acrylicon-2024/functions.php` — 301 redirects + SVG sanitization
- `themes/acrylicon-2024/assets/gfx/flags/*.svg` — 16 country flag SVGs

## Related Issues

- See also: [multisite-language-switcher](./multisite-language-switcher-LanguageSwitcher-20260211.md) — Language switcher with bidirectional slug mapping
