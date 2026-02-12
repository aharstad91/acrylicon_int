---
module: WordPress Multisite
date: 2026-02-12
problem_type: runtime_error
component: rails_view
symptoms:
  - "Warning: Undefined array key 0 in wp-includes/class-wp-query.php on line 3742"
  - "English pages at /references/, /products/, /offices/ etc. render completely empty"
  - "Norwegian pages at /no/referanser/ etc. work fine"
root_cause: config_error
resolution_type: config_change
severity: high
tags: [wordpress-multisite, page-slugs, db-sync, the-post, have-posts, i18n]
---

# Troubleshooting: English Pages Empty After Database Sync

## Problem
After syncing the database from production or between multisite blogs, all English pages on blog 1 rendered empty with a PHP warning. Norwegian pages on blog 3 worked fine. The root cause was twofold: page slugs on blog 1 were Norwegian (not matching English URLs), and `index.php` lacked a `have_posts()` guard.

## Environment
- Module: WordPress Multisite (subdirectory mode)
- WordPress Version: 6.8.3
- PHP: 8.1 (local MAMP)
- Affected Component: Theme index.php, page post_name slugs on blog 1
- Date: 2026-02-12

## Symptoms
- PHP Warning: `Undefined array key 0 in wp-includes/class-wp-query.php on line 3742` on English URLs
- `/references/` completely empty (no content rendered)
- `/products/`, `/offices/`, `/about-acrylicon/` etc. all empty
- `/no/referanser/`, `/no/produkter/` etc. all working fine
- Norwegian-to-English 301 redirects (`/referanser/` -> `/references/`) working, but landing on empty page

## What Didn't Work

**Direct solution:** The problem was identified through two-step investigation:
1. First the PHP warning pointed to `the_post()` being called on empty query
2. Then investigating why the query was empty revealed the slug mismatch

## Solution

### 1. Add `have_posts()` guard to index.php

```php
// Before (broken):
<?php
    get_header();
    the_post();
?>
<main>...</main>

// After (fixed):
<?php
    get_header();
    if ( have_posts() ) {
        the_post();
?>
<main>...</main>
<?php } ?>
```

### 2. Update page slugs on blog 1 from Norwegian to English

```bash
# 11 pages needed slug updates on blog 1
wp post update 80 --post_name=products --url=http://localhost:8888/acrylicon/
wp post update 82 --post_name=offices --url=http://localhost:8888/acrylicon/
wp post update 84 --post_name=references --url=http://localhost:8888/acrylicon/
wp post update 86 --post_name=about-acrylicon --url=http://localhost:8888/acrylicon/
wp post update 1858 --post_name=applications --url=http://localhost:8888/acrylicon/
wp post update 2268 --post_name=cookie-policy --url=http://localhost:8888/acrylicon/
wp post update 2336 --post_name=downloads --url=http://localhost:8888/acrylicon/
wp post update 4790 --post_name=good-reasons --url=http://localhost:8888/acrylicon/
wp post update 4793 --post_name=lifecycle-costs --url=http://localhost:8888/acrylicon/
wp post update 4795 --post_name=sustainability --url=http://localhost:8888/acrylicon/
wp post update 4798 --post_name=benefits --url=http://localhost:8888/acrylicon/

# Flush rewrite rules after
wp rewrite flush --url=http://localhost:8888/acrylicon/
```

### Full slug mapping reference

| Page ID | Norwegian slug | English slug |
|---------|---------------|-------------|
| 80 | `produkter` | `products` |
| 82 | `kontor` | `offices` |
| 84 | `referanser` | `references` |
| 86 | `om-acrylicon` | `about-acrylicon` |
| 1858 | `bruksomrader` | `applications` |
| 2268 | `informasjonskapsler` | `cookie-policy` |
| 2336 | `nedlastinger` | `downloads` |
| 4790 | `gode-grunner` | `good-reasons` |
| 4793 | `levetids-kostnader` | `lifecycle-costs` |
| 4795 | `baerekraft` | `sustainability` |
| 4798 | `fordeler` | `benefits` |

## Why This Works

1. **Root cause (PHP warning):** `index.php` called `the_post()` unconditionally. When WordPress couldn't resolve a URL to any content (because `/references/` didn't match any page slug), the fallback template `index.php` tried to access `$this->posts[0]` on an empty WP_Query, triggering the "Undefined array key 0" warning.

2. **Root cause (empty pages):** The multisite sync plugin copies content between blogs including `post_name` (slug). Since blog 3 (Norwegian) is the source, all page slugs on blog 1 were Norwegian. The English URL `/references/` didn't match any page with slug `referanser`, so WordPress returned an empty query.

3. **Why Norwegian site worked:** On blog 3, the page slug `referanser` matched the URL `/no/referanser/` perfectly. No mismatch.

4. **The `have_posts()` guard** prevents the PHP warning by checking if there are posts before calling `the_post()`. This is a WordPress best practice that should always be in fallback templates.

## Prevention

- **After any database sync from blog 3 to blog 1:** Always re-run the page slug updates (see mapping table above). The sync will overwrite English slugs with Norwegian ones.
- **Always use `have_posts()` before `the_post()`** in WordPress templates — never call `the_post()` unconditionally.
- **Verify after sync:** `curl -s -o /dev/null -w "%{http_code}" "http://localhost:8888/acrylicon/references/"` should return 200, not empty/404.
- **Production was not affected** — prod already had correct English slugs. This is a local development issue after DB sync.

## Related Issues

- See also: [norwegian-urls-on-english-site-WordPressMultisite-20260211.md](../workflow-issues/norwegian-urls-on-english-site-WordPressMultisite-20260211.md) — the original comprehensive URL translation migration
