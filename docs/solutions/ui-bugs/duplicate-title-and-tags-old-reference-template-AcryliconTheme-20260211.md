---
module: Acrylicon Theme
date: 2026-02-11
problem_type: ui_bug
component: rails_view
symptoms:
  - "Title rendered twice on English reference pages (old template)"
  - "Category tags duplicated - one from template header, one from Gutenberg content"
root_cause: logic_error
resolution_type: code_fix
severity: medium
tags: [wordpress, template, duplicate-content, multisite, the-title, the-content]
---

# Troubleshooting: Duplicate Title and Category Tags on Reference Pages

## Problem
English reference pages using `single-referanser-old.php` displayed the post title (H1) and category tags twice — once from the PHP template and once from the Gutenberg block content stored in the database.

## Environment
- Module: Acrylicon Theme (acrylicon-2024)
- WordPress: 6.8.3 Multisite
- Affected Component: `single-referanser-old.php` template
- Affected Pages: All references using the "old" template type (not dybdecase or new-reference)
- Date: 2026-02-11

## Symptoms
- Post title appears twice at the top of reference pages (two H1 elements)
- Category tags appear twice — "Schools and public buildings" from template, "Public Buildings" from content
- The second tag ("Public Buildings") had different styling/shape than the first
- Issue only visible on English site (blog 1) — Norwegian originals use dybdecase template which only calls `the_content()`

## What Didn't Work

**Direct solution:** The problem was identified on first investigation by inspecting the DOM with Chrome DevTools MCP and tracing each H1 to its source (template vs Gutenberg content).

## Solution

Removed the duplicate `<header>` section (containing category tags) and the `the_title()` H1 from `single-referanser-old.php`, since the Gutenberg block content already includes both.

**Code changes:**

```php
// Before (broken) - single-referanser-old.php had both template output AND content:

// Template outputs title (line 33):
<h1 class="my-reset lg:text-7xl ..."><?php the_title();?></h1>

// Template outputs category tags (lines 5-20):
<header class="mb-12 ...">
    <?php foreach($category_terms as $term): ?>
        <a class="bg-red text-white rounded-full ..."><?php echo $term->name; ?></a>
    <?php endforeach; ?>
</header>

// Then content ALSO has title + tags from Gutenberg blocks:
<div class="editor"><?php the_content(); ?></div>


// After (fixed) - only the_content() which already includes title and tags:
<div class="prose max-w-none text-xl">
    <div class="lg:mb-20 editor"><?php the_content(); ?></div>
    <!-- ... rest of template (swiper, related posts) -->
</div>
```

**Verification:**
```bash
# Check all English references have exactly 1 H1 and 0 template tags:
curl -s "$URL" | grep -o '<h1' | wc -l        # Should be 1
curl -s "$URL" | grep -c 'flex-shrink no-wrap bg-red'  # Should be 0
```

## Why This Works

1. **ROOT CAUSE:** The `single-referanser-old.php` template was originally designed when post content did NOT include the title and category tags. When content was later synced from the Norwegian site (which uses `single-referanser-dybdecase.php` — a template that only calls `the_content()`), the Gutenberg blocks already contained both the title and category tags. The old template was never updated to account for this, resulting in duplication.

2. **Why the fix works:** Removing the template-level `the_title()` and category tag output eliminates the duplication. The Gutenberg content is the source of truth and already contains styled title and category elements.

3. **Why only English pages were affected:** The Norwegian original pages use `single-referanser-dybdecase.php` which only calls `the_content()` without any template-level title or tags. When content was ported to the English site via the multisite sync plugin, it kept the Gutenberg blocks including title and tags, but the English pages were routed to `single-referanser-old.php` which also output these elements.

## Prevention

- **Rule:** When a template calls `the_content()`, check if the Gutenberg content already includes a title (H1) or metadata tags. Avoid duplicating in the template what the content already provides.
- **Template audit:** When syncing content between multisite blogs, verify which template each post type uses on the target blog and ensure template output doesn't overlap with content blocks.
- **Quick check:** `curl -s "$URL" | grep -o '<h1' | wc -l` should always return 1 for any page.

## Related Issues

- See also: [tailwind-purges-layer-utilities-database-classes-AcryliconTheme-20260211.md](../build-errors/tailwind-purges-layer-utilities-database-classes-AcryliconTheme-20260211.md) — related Tailwind migration issue discovered in same session
