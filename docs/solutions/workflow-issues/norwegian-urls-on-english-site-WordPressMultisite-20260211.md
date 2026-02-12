---
module: WordPress Multisite
date: 2026-02-11
problem_type: workflow_issue
component: development_workflow
symptoms:
  - "All URLs on international/English site (blog 1) using Norwegian slugs (/referanser/, /produkter/)"
  - "SEO penalty from mismatched content language and URL language"
  - "Language switcher slug mapping incomplete (missing kontor, nedlastinger, informasjonskapsler)"
root_cause: incomplete_setup
resolution_type: workflow_improvement
severity: high
tags: [wordpress-multisite, i18n, url-slugs, seo, wp-cli, search-replace]
---

# Troubleshooting: Norwegian URL Slugs on English Multisite Site

## Problem
After syncing content from the Norwegian site (blog 3) to the international/English site (blog 1) in a WordPress multisite, all URLs remained Norwegian (`/referanser/`, `/produkter/dekor-system-gulv/`). This affected SEO and user experience — English content served under Norwegian URLs.

## Environment
- Module: WordPress Multisite (subdirectory mode)
- WordPress Version: 6.8.3
- PHP: 8.4 (production), 8.1 (local)
- Affected Component: CPT rewrite slugs, page slugs, taxonomy term slugs, internal links, template hardcoded links
- Date: 2026-02-11

## Symptoms
- All CPT archive URLs using Norwegian rewrite slugs (`/referanser/`, `/produkter/`, `/industrier/`)
- All page URLs using Norwegian post_name (`/fordeler/`, `/om-acrylicon/`, `/nedlastinger/`)
- All CPT single post URLs using Norwegian slugs (`/produkter/dekor-system-gulv/`)
- All taxonomy term URLs using Norwegian slugs (`/referanse-kategori/energi-og-industri/`)
- Internal links in post_content pointing to Norwegian URLs
- Language switcher missing slug mappings for 3 pages (kontor, nedlastinger, informasjonskapsler)
- Hardcoded Norwegian paths in 4 template files

## What Didn't Work

**Attempted Solution 1:** Simple search-replace of `/levetids-kostnader/` in post_content
- **Why it partially failed:** ACF fields stored links without leading slash (`levetids-kostnader/` instead of `/levetids-kostnader/`), so the search pattern missed them. Required a second pass without leading slash.

**Attempted Solution 2:** Adding `forside => certifications` to language switcher slug map
- **Why it failed:** `sertifiseringer => certifications` already existed in the map. PHP `array_flip()` used by `acrylicon_map_slug()` would lose one entry since two keys can't map to the same value. Removed the duplicate mapping.

**Attempted Solution 3:** Using `$is_english` variable directly in taxonomy registration
- **Why it failed:** `$is_english` was scoped inside `acrylicon_get_cpt_slugs()` but used in `register_custom_post_types_and_taxonomies()`. Fixed by adding a `tax_type` key to the slugs helper array.

## Solution

### 1. Conditional CPT/taxonomy rewrite slugs in functions.php

```php
// Helper function returning EN or NO slugs based on current blog
function acrylicon_get_cpt_slugs() {
    $is_english = ( get_current_blog_id() === 1 );
    return [
        'industrier'        => $is_english ? 'industries'         : 'industrier',
        'kontor'            => $is_english ? 'offices'            : 'kontor',
        'produkter'         => $is_english ? 'products'           : 'produkter',
        'bruksomrader'      => $is_english ? 'applications'       : 'bruksomrader',
        'godegrunner'       => $is_english ? 'good-reasons'       : 'gode-grunner',
        'levetidskostnader' => $is_english ? 'lifecycle-costs'    : 'levetids-kostnader',
        'baerekreaftig'     => $is_english ? 'sustainability'     : 'baerekraft',
        'referanser'        => $is_english ? 'references'         : 'referanser',
        'tax_kategorier'    => $is_english ? 'reference-category' : 'referanse-kategori',
        'tax_kontor'        => $is_english ? 'reference-office'   : 'referanse-kontor',
        'tax_produkter'     => $is_english ? 'reference-products' : 'referanse-produkter',
        'tax_type'          => $is_english ? 'reference-type'     : 'referanser-type',
    ];
}

// Used in each register_post_type() call:
$slugs = acrylicon_get_cpt_slugs();
register_post_type('referanser', [
    'rewrite' => ['slug' => $slugs['referanser'], 'with_front' => true],
    // ...
]);
```

### 2. Page slugs via WP-CLI (12 pages)

```bash
# Example commands run on production (blog 1)
wp post update 4798 --post_name=benefits --url=https://acryli-28355.jana-osl.servebolt.cloud
wp post update 84   --post_name=references --url=https://acryli-28355.jana-osl.servebolt.cloud
wp post update 80   --post_name=products --url=https://acryli-28355.jana-osl.servebolt.cloud
# ... 9 more pages
```

### 3. CPT post slugs via WP-CLI (51 posts)

```bash
# Generated English slugs from post_title using sanitize_title()
ssh prod 'wp eval '\''
$types = ["industrier","kontor","produkter","bruksomrader","godegrunner","levetidskostnader","baerekreaftig","referanser"];
foreach ($types as $type) {
    $posts = get_posts(["post_type" => $type, "numberposts" => -1, "post_status" => "publish"]);
    foreach ($posts as $p) {
        $new_slug = sanitize_title($p->post_title);
        if ($new_slug !== $p->post_name) {
            wp_update_post(["ID" => $p->ID, "post_name" => $new_slug]);
        }
    }
}
'\'' --url=https://acryli-28355.jana-osl.servebolt.cloud'
```

### 4. Internal links via search-replace (514+ replacements)

```bash
# 10 search-replace operations on blog 1
wp search-replace '/kontor/' '/offices/' wp_posts --include-columns=post_content --url=...
wp search-replace '/produkter/' '/products/' wp_posts --include-columns=post_content --url=...
wp search-replace '/referanser/' '/references/' wp_posts --include-columns=post_content --url=...
# ... 7 more patterns

# Extra pass for ACF fields without leading slash
wp search-replace 'levetids-kostnader/' 'lifecycle-costs/' wp_posts --include-columns=post_content --url=...
```

### 5. Template hardcoded links (4 files)

```php
// Before:
home_url() . '/referanser'

// After:
home_url() . '/' . ( get_current_blog_id() === 1 ? 'references' : 'referanser' )
```

### 6. 301 Redirects for old Norwegian URLs

```php
function acrylicon_redirect_old_norwegian_slugs() {
    if ( get_current_blog_id() !== 1 || is_admin() ) { return; }
    $redirects = [
        'referanser' => 'references', 'produkter' => 'products',
        'industrier' => 'industries', 'bruksomrader' => 'applications',
        'kontor' => 'offices', 'gode-grunner' => 'good-reasons',
        'levetids-kostnader' => 'lifecycle-costs', 'baerekraft' => 'sustainability',
        'fordeler' => 'benefits', 'om-acrylicon' => 'about-acrylicon',
        'nedlastinger' => 'downloads', 'informasjonskapsler' => 'cookie-policy',
    ];
    // Parse REQUEST_URI, check first segment, 301 redirect if match
}
add_action( 'template_redirect', 'acrylicon_redirect_old_norwegian_slugs' );
```

### 7. Taxonomy term slugs (24 terms across 4 taxonomies)

```bash
# Generated English slugs from term name using sanitize_title()
wp eval '
$taxonomies = ["referanser-kategorier", "referanser-kontor", "referanser-produkter"];
foreach ($taxonomies as $tax) {
    $terms = get_terms(["taxonomy" => $tax, "hide_empty" => false]);
    foreach ($terms as $t) {
        $new_slug = sanitize_title($t->name);
        wp_update_term($t->term_id, $tax, ["slug" => $new_slug]);
    }
}
' --url=https://acryli-28355.jana-osl.servebolt.cloud
```

### 8. Language switcher slug map updates

Added missing entries to `acrylicon_slug_map()` in `inc/language-switcher.php`:
- `kontor => offices`
- `nedlastinger => downloads`
- `informasjonskapsler => cookie-policy`

### 9. Flush rewrite rules

```bash
wp rewrite flush --url=https://acryli-28355.jana-osl.servebolt.cloud
wp rewrite flush --url=https://acryli-28355.jana-osl.servebolt.cloud/norway/
```

## Why This Works

1. **Root cause:** The international site was created by syncing all content from the Norwegian site, including URL slugs. WordPress stores slugs in multiple places: CPT rewrite rules (code), page/post `post_name` (database), taxonomy term slugs (database), internal links in `post_content` (database), and hardcoded paths in templates (code).

2. **Conditional slugs solve the code layer:** `acrylicon_get_cpt_slugs()` uses `get_current_blog_id()` to return the correct language slugs per site. This means the same theme codebase works for both sites without duplication.

3. **WP-CLI + sanitize_title() solves the database layer:** `sanitize_title()` generates proper URL-safe slugs from English post titles, handling special characters, spaces, and casing automatically.

4. **301 redirects preserve SEO:** Any existing links or bookmarks to old Norwegian URLs are permanently redirected to the new English URLs, preserving link equity.

5. **The `blog/` front base quirk:** CPTs registered with `with_front => true` include the `blog/` permalink front base in their URLs (e.g., `/blog/references/`). This is pre-existing WordPress configuration, not a bug from the migration.

## Prevention

- **When syncing multisite content between languages:** Always plan for URL slug translation as a separate step. Content sync tools copy `post_name` directly.
- **Use `sanitize_title($english_title)` for generating slugs** from translated titles — don't manually translate slugs.
- **Check ACF fields for links without leading slashes** — `wp search-replace` with `/old-slug/` won't catch `old-slug/` (no leading slash). Always run a second pass without the leading slash.
- **Watch for `array_flip()` in bidirectional slug maps** — two different source slugs can't map to the same target slug.
- **Include taxonomy term slugs in translation plans** — easy to forget since they live in a separate database table (`wp_terms`/`wp_termmeta`).
- **Test all URL types after migration:** pages, CPT archives, CPT singles, taxonomy archives, taxonomy term archives, internal links in content.

## Related Issues

No related issues documented yet.
