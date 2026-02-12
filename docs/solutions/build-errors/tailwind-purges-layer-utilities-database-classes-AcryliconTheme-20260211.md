---
module: Acrylicon Theme
date: 2026-02-11
problem_type: build_error
component: frontend_stimulus
symptoms:
  - "Database CSS classes like bg-dark-blue, bg-red, text-dark produce no CSS output"
  - "White text on light background - missing dark blue background on career section"
  - "grep -c 'bg-dark-blue' assets/css/tailwind.css returns 0"
root_cause: config_error
resolution_type: code_fix
severity: high
tags: [tailwind, purge, layer-utilities, database-classes, css-compatibility]
---

# Troubleshooting: Tailwind Purges Database-Only Classes Inside @layer utilities

## Problem
Custom CSS classes stored in WordPress Gutenberg database content (not in PHP template files) are silently removed from Tailwind CSS build output when placed inside `@layer utilities`, resulting in missing backgrounds, colors, and layout on the live site.

## Environment
- Module: Acrylicon Theme (acrylicon-2024)
- WordPress: 6.8.3
- Tailwind CSS: v3
- Affected Component: `src/tailwind.css` build output
- Date: 2026-02-11

## Symptoms
- `bg-dark-blue` career section shows white text on light background (invisible text)
- `bg-red`, `text-dark`, `bg-neutral-*` and other color classes have no effect
- `mx-reset`, `mb-reset`, custom height classes (`h-600`, `h-300`) missing
- `grep -c 'bg-dark-blue' assets/css/tailwind.css` returns `0` after build
- Chrome DevTools shows no matching CSS rule for these classes

## What Didn't Work

**Attempted Solution 1:** Placed compatibility classes inside `@layer utilities {}` in `src/tailwind.css`
- **Why it failed:** Tailwind treats `@layer utilities` as part of its own utility system and applies tree-shaking/purging. Since these classes only exist in the WordPress database (Gutenberg block content), they are NOT found by Tailwind's content scanner (which only scans PHP templates and JS files). Tailwind removes them from the build output.

**Note:** Chrome DevTools MCP showed the correct computed style during testing because the browser was loading a fresh page where the development server may have served un-minified CSS. The actual minified production build had the classes purged.

## Solution

Move all database-only compatibility classes **outside** `@layer utilities` into plain CSS. Plain CSS rules are never purged by Tailwind, regardless of whether they appear in scanned content files.

**Code changes:**

```css
/* Before (broken) - classes inside @layer utilities get purged: */
@layer utilities {
  .bg-dark-blue { background-color: #253761; }
  .bg-red { background-color: #E2241C; }
  .text-dark { color: #2B3338; }
  /* ... these are ALL removed from build output */
}

/* After (fixed) - classes outside @layer are always included: */

/* Close the @layer utilities block first */
}

/* Database compatibility classes - OUTSIDE @layer to prevent Tailwind purging.
 * These classes are used in ~2000 Gutenberg blocks stored in the database,
 * NOT in template files, so Tailwind's content scanner cannot find them. */
.bg-red { background-color: #E2241C; }
.bg-dark-blue { background-color: #253761; }
.bg-light-blue { background-color: #D5EDF7; }
.text-dark { color: #2B3338; }
/* ... etc */
```

**Verification:**
```bash
# After rebuild, confirm classes are present:
npm run build:css
grep -c "bg-dark-blue" assets/css/tailwind.css  # Should return 1 (not 0)
```

## Why This Works

1. **ROOT CAUSE:** Tailwind CSS v3 uses a JIT (Just-In-Time) compiler that scans files listed in `content` configuration for class names. Classes inside `@layer utilities` are treated identically to Tailwind's own utilities — they are only included if found in scanned content. WordPress stores Gutenberg block HTML in the `wp_posts` database table, which Tailwind cannot scan.

2. **Why the fix works:** CSS rules written outside any `@layer` directive are "un-layered" styles. Tailwind's purging/tree-shaking only applies to layered utilities. Un-layered CSS is always included in the output, making it the correct approach for classes that exist only in database content.

3. **Why `@layer utilities` specifically is dangerous:** Unlike `@layer base` or `@layer components`, the `utilities` layer is the one Tailwind most aggressively purges because it's designed for on-demand generation. Any custom class in this layer must appear in a scanned file to survive the build.

## Prevention

- **Rule:** Never put CSS classes that are only used in WordPress database content (Gutenberg blocks, post content) inside `@layer utilities`. Place them as plain CSS outside any `@layer` directive.
- **Comment convention:** Always add a comment explaining WHY classes are outside `@layer`:
  ```css
  /* Database compatibility - OUTSIDE @layer to prevent purging */
  ```
- **Verification step:** After any Tailwind CSS change, run `grep -c "class-name" assets/css/tailwind.css` to confirm critical classes survive the build.
- **Alternative approach:** You could also add a `safelist` array to `tailwind.config.js`, but plain CSS outside `@layer` is simpler and more explicit for a WordPress context with database-stored content.

## Related Issues

No related issues documented yet.
