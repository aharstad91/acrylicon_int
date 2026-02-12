---
module: Acrylicon Theme
date: 2026-02-12
problem_type: ui_bug
component: frontend_stimulus
symptoms:
  - "Footer missing red background on production — white text on beige background"
  - "Global Presence section missing dark blue background — white text invisible on light background"
  - "CSS classes bg-red and bg-dark-blue present in DOM but backgroundColor computed as rgba(0,0,0,0)"
root_cause: missing_workflow_step
resolution_type: code_fix
severity: high
tags: [tailwind, css-migration, utility-css, wp-fastest-cache, database-content, wp-html-block, search-replace]
---

# Troubleshooting: Missing Background Colors After Tailwind CSS Migration

## Problem
After migrating from legacy utility CSS to Tailwind CSS, the footer (red) and "Global Presence" section (dark blue) lost their background colors on production. The old `utility.css` was removed from enqueue, but HTML still referenced old class names that no longer exist in any loaded stylesheet.

## Environment
- Module: Acrylicon Theme (acrylicon-2024)
- WordPress: 6.8.3 (Multisite)
- Tailwind CSS: v3
- Affected Component: Footer template + wp:html blocks in database
- Date: 2026-02-12

## Symptoms
- Footer rendered with white text on beige background (no red `#E2241C` background)
- "Global Presence" section rendered with white text on light background (no dark blue `#253761` background)
- Browser DevTools showed classes like `bg-red`, `bg-dark-blue` present in DOM
- `getComputedStyle().backgroundColor` returned `rgba(0, 0, 0, 0)` for both
- `text-white` (built-in Tailwind class) worked fine — only custom color classes were broken

## What Didn't Work

**Attempted Solution 1:** Checked if CSS files differed between local and production
- **Why it failed:** `tailwind.css` had identical MD5 checksums. The CSS wasn't the problem — the HTML used class names that didn't exist in the CSS.

**Attempted Solution 2:** Checked if template PHP files differed between local and production
- **Why it failed:** All template files were identical (rsync deploy was successful). The issue wasn't in template files for the "Global Presence" section.

**Attempted Solution 3:** Cleared WP Fastest Cache file cache
- **Why it failed:** Partially helped (footer.php changes took effect), but the "Global Presence" section still showed old classes because the HTML was stored in the database, not generated from templates.

## Solution

**Two separate root causes required two fixes:**

### Fix 1: Template files with old class names

`footer.php` and 6 other template files still used old utility class names that were never migrated:

```php
# Before (broken):
<footer class="bg-red text-white py-10 font-normal">

# After (fixed):
<footer class="bg-acryl-red text-white py-10 font-normal">
```

Other files fixed: `single-referanser-dybdecase.php`, `single-referanser-old.php`, `single-referanser-referanse.php`, `taxonomy-referanser-kategorier.php`, `blocks/header-with-red-back-link/template.php`, `blocks/table-variant-one/template.php`.

### Fix 2: Database-stored HTML with old class names

The "Global Presence" section was stored as a `<!-- wp:html -->` block in WordPress `post_content` — hardcoded HTML that bypasses the PHP template entirely. These stored old utility classes:

```html
<!-- What was in the database (broken): -->
<div class="bg-dark-blue text-white rounded-lg overflow-hidden icon-large text-large lg-flex h-full">
  <div class="px-6 lg-px-16 py-10 lg-py-24 flex flex-col justify-center">

<!-- What it should be (Tailwind): -->
<div class="bg-acryl-dark-blue text-white rounded-lg overflow-hidden icon-large text-large lg:flex h-full">
  <div class="px-6 lg:px-16 py-10 lg:py-24 flex flex-col justify-center">
```

**Database search-replace commands:**

```bash
# Blog 1 (English — wp_posts)
wp search-replace 'bg-dark-blue' 'bg-acryl-dark-blue' wp_posts --precise
wp search-replace 'bg-red' 'bg-acryl-red' wp_posts --precise
wp search-replace 'lg-flex' 'lg:flex' wp_posts --precise
wp search-replace 'lg-px-16' 'lg:px-16' wp_posts --precise
wp search-replace 'lg-py-24' 'lg:py-24' wp_posts --precise
wp search-replace 'lg-text-5xl' 'lg:text-5xl' wp_posts --precise
wp search-replace 'lg-w-1-3' 'lg:w-1/3' wp_posts --precise

# Blog 3 (Norwegian — wp_3_posts, via SQL since wp search-replace doesn't find the table)
wp db query "UPDATE wp_3_posts SET post_content = REPLACE(post_content, 'bg-dark-blue', 'bg-acryl-dark-blue') WHERE post_content LIKE '%bg-dark-blue%'"
# ... same pattern for all other replacements
```

### Fix 3: Cache clear

```bash
# Remove WP Fastest Cache files
rm -rf wp-content/cache/acryli-28355.jana-osl.servebolt.cloud/

# Flush WP object cache and transients
wp cache flush
wp transient delete --all
```

## Why This Works

1. **Root cause — Tailwind color naming conflict:** During Tailwind migration, custom brand colors were prefixed with `acryl-` (e.g., `bg-acryl-red` instead of `bg-red`) to avoid conflicts with Tailwind's built-in color palette. The old `utility.css` that defined `.bg-red { background-color: #E2241C; }` was removed from `wp_enqueue_style`, but not all references were updated.

2. **Two storage locations for HTML:** WordPress stores block content in two ways:
   - **ACF blocks with `render_template`**: PHP renders dynamically on each page load → template file changes take effect immediately
   - **`wp:html` (Custom HTML) blocks**: Raw HTML stored in `post_content` database field → template changes have NO effect

3. **Cache compounded the issue:** WP Fastest Cache stored the old HTML output, masking whether changes were taking effect during debugging.

## Prevention

- **When migrating CSS frameworks, always check BOTH template files AND database content.** Run:
  ```bash
  wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%old-class-name%'"
  ```
- **After any CSS class rename, search the entire database** — not just PHP files. WordPress stores rendered HTML in `post_content` for `wp:html` blocks, reusable blocks, and synced content.
- **Always clear ALL cache layers after deploy:** file cache, object cache, transients, and server-level cache (Servebolt/Nginx).
- **Use Tailwind's `safelist` or keep a mapping document** when renaming utility classes to catch stragglers.
- **Check `wp:html` blocks specifically** — they are the most likely to contain stale hardcoded CSS classes since they bypass template rendering.

## Related Issues

- See also: [duplicate-title-and-tags-old-reference-template-AcryliconTheme-20260211.md](./duplicate-title-and-tags-old-reference-template-AcryliconTheme-20260211.md) — another template/content mismatch issue
