---
module: Acrylicon Theme
date: 2026-02-12
problem_type: ui_bug
component: rails_view
symptoms:
  - "Double h1 title on case-study reference pages - template title + content title"
  - "Reference pages with no referanser-type term showed empty pages (only header/footer)"
root_cause: logic_error
resolution_type: code_fix
severity: high
tags: [wordpress, template-routing, taxonomy, referanser-type, case-study, fallback]
---

# Troubleshooting: Incomplete Template Routing for Reference Pages

## Problem
Reference pages had two routing issues in `single-referanser.php`: (1) `case-study` posts were routed to the wrong template causing double titles, and (2) posts without any `referanser-type` term rendered as empty pages because the router had no fallback.

## Environment
- Module: Acrylicon Theme (acrylicon-2024)
- WordPress: 6.8.3 Multisite
- Affected Component: `single-referanser.php` template router
- Affected Pages: 4 case-study posts (EN+NO) + ~80 legacy posts without type term (NO only)
- Date: 2026-02-12

## Symptoms
- **Issue 1:** Case-study reference pages (e.g., NIH, Nationaltheatret) showed the title twice — one plain h1 from the template (`the_title()`) and one h1 with red category pill from the post content (reusable block 4834)
- **Issue 2:** Old Norwegian reference pages (e.g., Frognerbadet, SSHS Kristiansand, Microbryggeri) showed only header and footer — completely empty content area
- Issue 1 affected both EN and NO sites (4 posts each)
- Issue 2 affected only NO site (~80 legacy posts that have no `referanser-type` taxonomy term)

## What Didn't Work

**Direct solution:** Both problems were identified through systematic investigation:

1. For the double title: Traced the HTML source to find two h1 elements — one from `single-referanser-old.php:33` (`the_title()`) and one from reusable block 4834 in the post content. Discovered post had `case-study` term, not `new-reference`, causing wrong template selection.

2. For the empty pages: Discovered while checking old-reference pages after fixing issue 1. `get_the_terms()` returned false for posts without a term, so the entire routing if-block was skipped.

## Solution

Updated the router in `single-referanser.php` with two changes:

**Code changes:**

```php
// Before (broken) - single-referanser.php:
$terms = get_the_terms(get_the_ID(), 'referanser-type');
if ($terms && !is_wp_error($terms)) {
    $term_slug = $terms[0]->slug;

    if ($term_slug === 'dybdecase') {
        get_template_part('single-referanser-dybdecase');
    } else if ($term_slug === 'new-reference') {
        get_template_part('single-referanser-referanse');
    } else {
        get_template_part('single-referanser-old');
    }
}
// No else! Posts without terms = empty page

// After (fixed):
$terms = get_the_terms(get_the_ID(), 'referanser-type');
if ($terms && !is_wp_error($terms)) {
    $term_slug = $terms[0]->slug;

    if ($term_slug === 'dybdecase') {
        get_template_part('single-referanser-dybdecase');
    } else if ($term_slug === 'new-reference' || $term_slug === 'case-study') {
        get_template_part('single-referanser-referanse');
    } else {
        get_template_part('single-referanser-old');
    }
} else {
    get_template_part('single-referanser-old');
}
```

**Key differences:**
1. Added `|| $term_slug === 'case-study'` — routes case-study posts to `single-referanser-referanse.php` (content-only template, no template-side title)
2. Added `else` fallback — posts without any term now render with `single-referanser-old.php`

**Verification:**
```bash
# Verify single title:
curl -s "$URL" | grep '<h1' | grep -v '<!--'  # Should show exactly 1 h1

# Verify content renders:
curl -s "$URL" | grep -c '<main'  # Should be 1
curl -s "$URL" | sed -n '/<main/,/<\/main>/p' | wc -l  # Should be > 10
```

## Why This Works

1. **ROOT CAUSE (Issue 1):** The `case-study` taxonomy term was added after the original router was built. The router only handled `dybdecase` and `new-reference` explicitly, so `case-study` fell to the `else` branch using `single-referanser-old.php`. But case-study posts have content built for `single-referanser-referanse.php` — they include their own title blocks (reusable blocks with h1 + category pills). The old template also outputs `the_title()` as h1, causing duplication.

2. **ROOT CAUSE (Issue 2):** The shared taxonomy plugin (`acrylicon-shared-taxonomies.php`) manages taxonomy terms across the multisite. Norwegian legacy posts (~80 posts) were never assigned a `referanser-type` term, so `get_the_terms()` returned false. The router's if-block was entirely skipped — no template was loaded between `get_header()` and `get_footer()`.

3. **Why the fix works:** Routing `case-study` to the content-only template eliminates the double title. The else fallback ensures all posts render, even without taxonomy terms.

## Prevention

- **Always include a default/else fallback** in taxonomy-based template routers. Never assume all posts will have a term assigned.
- **When adding new taxonomy terms**, update the router in `single-referanser.php` to handle the new slug explicitly.
- **Check which template a post's content was built for** before assigning a taxonomy type. Case-study content with reusable title blocks should use `single-referanser-referanse.php`, not the old template.
- **Quick audit:** `curl -s "$URL" | grep -o '<h1' | wc -l` should always return 1 for any reference page.

## Related Issues

- See also: [duplicate-title-and-tags-old-reference-template-AcryliconTheme-20260211.md](./duplicate-title-and-tags-old-reference-template-AcryliconTheme-20260211.md) — previous fix that removed duplicate title/tags from `single-referanser-old.php` template itself (same template, different root cause)
- See also: [missing-colors-tailwind-migration-AcryliconTheme-20260212.md](./missing-colors-tailwind-migration-AcryliconTheme-20260212.md) — Tailwind migration issue discovered in same session
