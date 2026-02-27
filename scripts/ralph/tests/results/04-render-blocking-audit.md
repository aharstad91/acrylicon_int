# Render-Blocking Resource Audit

**Date:** 2026-02-27
**Status:** FAIL
**Page(s) tested:** /no/, /no/kontor/, /no/produkter/flake-system/, /no/referanser/rehabilitering-av-norges-idrettshogskole/

## Summary

The render-blocking situation is **better than most WordPress sites** — only 4 CSS files block rendering, all served via H2 from the same origin with excellent TTFB (1ms), completing in ~12ms total. Chrome DevTools estimates **0ms savings** from fixing these. However, there are still optimization opportunities: `wp-block-library` CSS (113.6KB raw) loads on every page despite most styles being unused on the front-end, the 9KB WordPress Global Styles inline block declares unused CSS custom properties, the wp-emoji inline script (5.8KB) is unnecessary, and 4 JS scripts in the footer lack `defer`/`async` attributes (including GSAP at 71KB from a third-party CDN). Swiper is correctly conditional — it only loads on pages with slider blocks.

## Measurements

### Chrome Performance Trace — /no/ (mobile, Fast 4G, 4x CPU)
- **LCP:** 121ms (well under 2500ms)
- **CLS:** 0.00
- **Max critical path latency:** 74ms
- **Render-blocking estimated savings:** FCP 0ms, LCP 0ms

### Resource Loading Timeline (from trace)
All render-blocking CSS files complete in 7–12ms via H2 multiplexing:
| Resource | Queue → Complete | Download | Render-blocking |
|----------|-----------------|----------|-----------------|
| block-library/style.min.css | 7ms → 11ms | 0.7ms | Yes |
| style.css | 7ms → 11ms | 0.6ms | Yes |
| fonts.css | 7ms → 11ms | 0.6ms | Yes |
| tailwind.css | 7ms → 12ms | 0.4ms | Yes |

## Render-Blocking CSS Inventory

### 1. `wp-block-library/style.min.css` — WASTEFUL
- **URL:** `/wp-includes/css/dist/block-library/style.min.css?ver=6.8.3`
- **Raw size:** 113.6 KB | **Compressed:** ~16.7 KB (gzip)
- **Loads on:** ALL pages
- **Render-blocking:** Yes (media="all" in `<head>`)
- **Problem:** This is WordPress's Gutenberg block stylesheet containing styles for ~50 block types (gallery, quote, pullquote, verse, table, code, columns, etc.). A custom theme using ACF blocks and Tailwind uses very few of these. Estimated usage: 10-20% of styles are actually needed.
- **Impact:** Adds ~16.7KB to the critical rendering path on every page load. The browser must download and parse all 113.6KB before rendering.
- **Fix:** Either (a) dequeue `wp-block-library` and include only needed block styles inline, or (b) use `wp_enqueue_block_style()` to load per-block styles only when those blocks are used, or (c) move to `media="print"` with onload swap like gravity.css. Option (b) is built into WP 5.8+ with `should_load_separate_core_block_assets`.
- **Priority:** Medium (16.7KB compressed, but loads fast over H2)

### 2. `style.css` (theme) — APPROPRIATE
- **URL:** `/wp-content/themes/acrylicon-2024/style.css?ver=6.8.3`
- **Raw size:** 3.7 KB | **Compressed:** ~1.5 KB
- **Loads on:** ALL pages
- **Render-blocking:** Yes
- **Assessment:** Small file, contains theme identification header and base styles. Appropriate as render-blocking.

### 3. `fonts.css` — APPROPRIATE
- **URL:** `/wp-content/themes/acrylicon-2024/assets/fonts/fonts.css?ver=1.0.0`
- **Raw size:** 0.9 KB | **Compressed:** ~0.3 KB
- **Loads on:** ALL pages
- **Render-blocking:** Yes
- **Assessment:** Tiny file with @font-face declarations. Must be render-blocking to avoid FOUT. Font file itself (`soehne-buch.woff2`) is correctly preloaded.

### 4. `tailwind.css` — APPROPRIATE
- **URL:** `/wp-content/themes/acrylicon-2024/assets/css/tailwind.css?ver=1772130825`
- **Raw size:** 22.0 KB | **Compressed:** ~5.7 KB
- **Loads on:** ALL pages
- **Render-blocking:** Yes
- **Assessment:** Main layout/component CSS. Must be render-blocking for above-the-fold content. Size is reasonable for a Tailwind build with purging.

## Non-Blocking CSS (Already Optimized)

These use the `media="print" onload="this.media='all'"` pattern:

| File | Raw Size | Loads On | Notes |
|------|----------|----------|-------|
| `gravity.css` | 3.3 KB | All pages | Gravity Forms — deferred correctly |
| `block-panels.css` | 6.7 KB | All pages | Custom block panels — deferred correctly |
| `swiper-bundle.min.css` | 18.0 KB | Slider pages only | Conditionally loaded AND deferred |

## JavaScript Inventory

### End-of-Body Scripts (no defer/async)

These 4 scripts are at the very end of `<body>` (line 841-844) without `defer` or `async`. They are technically parser-blocking at that point, but since they're after all content, the impact on FCP/LCP is minimal. However, they DO block the `load` event and affect TTI.

| Script | Source | Raw Size | Compressed | Purpose |
|--------|--------|----------|------------|---------|
| `gsap.min.js` | cdn.jsdelivr.net | 71.0 KB | ~29.3 KB (br) | Page transitions (opacity animation) |
| `bodyScrollLock.js` | First-party | 2.7 KB | ~1.1 KB | Mobile menu scroll locking |
| `transitions.js` | First-party | 1.6 KB | ~0.8 KB | GSAP transition init |
| `scripts.js` | First-party | 0.9 KB | ~0.6 KB | ScrollReveal, Swiper init, misc |

**Issue:** GSAP (71KB) is a third-party CDN dependency loaded without `defer`. While its position at end-of-body means it doesn't block FCP/LCP, adding `defer` would allow the browser to start downloading it earlier (during HTML parsing) while still executing after DOM is ready.

### Deferred Scripts (Non-Blocking)

| Script | Raw Size | Compressed | Notes |
|--------|----------|------------|-------|
| `jquery.min.js` | 85.5 KB | ~31.0 KB | `defer` attribute ✓ |
| `jquery-migrate.min.js` | 13.2 KB | ~4.8 KB | `defer` attribute ✓ |
| `swiper-bundle.min.js` | 150.9 KB | N/A | Only on slider pages, end-of-body |

## Inline Resources

### WordPress Global Styles — 9.0 KB inline `<style>`
- **Location:** `<head>` (render-blocking by nature)
- **Content:** CSS custom properties for all WordPress preset colors, fonts, spacing, aspect ratios — ~200 CSS variables
- **Problem:** Most of these presets are unused by a Tailwind-based custom theme. The 9KB of `:root` declarations adds to the render-blocking CSS budget.
- **Fix:** Add `add_filter('wp_theme_json_data_default', ...)` to strip unused presets, or `remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles')` if not using theme.json features.
- **Priority:** Low (9KB inline, no network request needed)

### wp-emoji Script — 5.8 KB inline `<script>`
- **Location:** `<head>` (render-blocking)
- **Content:** Emoji detection and SVG/PNG fallback loader
- **Problem:** Unnecessary for a corporate site. Adds 5.8KB to the HTML document and runs JS during page parse.
- **Fix:** `remove_action('wp_head', 'print_emoji_detection_script', 7)` and `remove_action('wp_print_styles', 'print_emoji_styles')`
- **Priority:** Low-Medium (removes 5.8KB from critical path + eliminates unnecessary JS execution)

### Mobile Menu Script — 2.6 KB inline `<script>`
- **Location:** `<head>` (render-blocking)
- **Content:** Mobile hamburger menu toggle and body scroll lock logic
- **Assessment:** Needs to be in `<head>` for immediate interactivity on mobile. Size is acceptable.

### Delayed Analytics Loader — 1.1 KB inline `<script>`
- **Location:** `<head>` (but self-delaying via setTimeout/interaction)
- **Assessment:** Excellent pattern — delays GTM and Byggfakta by 3.5s or first interaction. No FCP/LCP impact.

## Swiper Conditional Loading — PASS

Verified across 4 pages:

| Page | Swiper CSS | Swiper JS | Has Slider Block |
|------|-----------|-----------|-----------------|
| `/no/` (homepage) | Not loaded | Not loaded | No |
| `/no/kontor/` (office) | Not loaded | Not loaded | No |
| `/no/produkter/flake-system/` | Not loaded | Not loaded | No |
| `/no/referanser/rehabilitering-av-norges-idrettshogskole/` | Loaded (deferred) | Loaded (end-of-body) | Yes |

Swiper (18KB CSS + 151KB JS = 169KB) correctly loads ONLY on pages with the `acf/slider-block`. This is a significant optimization.

## Preconnect/Preload Hints — GOOD

Present on all pages:
- `<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>` — for GSAP
- `<link rel="preconnect" href="https://www.googletagmanager.com">` — for delayed GTM
- `<link rel="preload" href="...soehne-buch.woff2" as="font" type="font/woff2" crossorigin>` — LCP font
- `<link rel="dns-prefetch" href="//cdn.jsdelivr.net">` — redundant with preconnect, but harmless

## Cache Headers — EXCELLENT

All CSS/JS files return:
```
cache-control: max-age=31536000, s-maxage=3600, public
vary: Accept-Encoding
```
1-year browser cache with ETags. CDN (Servebolt) caches for 1 hour. This is optimal.

## Issues Found

### Issue 1: wp-block-library CSS loads on all pages (113.6 KB raw, ~16.7 KB compressed)
- **Page:** All pages
- **Problem:** Full Gutenberg block library styles load on every page. A custom ACF block + Tailwind theme uses <20% of these styles. The file is the single largest render-blocking CSS resource.
- **Impact:** Adds ~16.7KB to critical path. With H2 multiplexing and fast TTFB, actual delay is minimal (~2ms download), but the CSS still needs parsing (affects FCP on slow devices). PageSpeed Lighthouse flags this as "Reduce unused CSS."
- **Fix:** Add to `functions.php`: `add_filter('should_load_separate_core_block_assets', '__return_true')` — this loads block styles per-block instead of a single 113KB file. Available since WP 5.8.
- **Priority:** Medium

### Issue 2: GSAP loaded without defer from third-party CDN (71 KB)
- **Page:** All pages
- **Problem:** `gsap.min.js` from cdn.jsdelivr.net loads as a synchronous `<script>` at end of body. While its position prevents FCP/LCP impact, adding `defer` would allow earlier discovery and parallel downloading with HTML parsing.
- **Impact:** Low on current performance (loads in 24ms per trace), but `defer` is a free optimization.
- **Fix:** Add `defer` attribute to GSAP script enqueue in `functions.php`. Ensure transitions.js also has `defer` and depends on GSAP in the WordPress enqueue dependency chain.
- **Priority:** Low

### Issue 3: wp-emoji inline script (5.8 KB) is unnecessary
- **Page:** All pages
- **Problem:** WordPress emoji detection script runs on every page. Acrylicon is a corporate site that doesn't use emoji in content.
- **Impact:** 5.8KB added to HTML document size + JavaScript execution during page parse.
- **Fix:** Add to `functions.php`:
  ```php
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('wp_print_styles', 'print_emoji_styles');
  ```
- **Priority:** Low

### Issue 4: WordPress Global Styles inline block (9.0 KB) mostly unused
- **Page:** All pages
- **Problem:** WordPress outputs ~9KB of CSS custom properties for preset colors, fonts, spacing that the Tailwind-based theme doesn't use.
- **Impact:** 9KB of inline CSS in `<head>` that's parsed but unused. Minor render-blocking contribution.
- **Fix:** Remove with `remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles')` if no theme.json features are used, or filter to keep only needed presets.
- **Priority:** Low

## Passed Checks

- **CSS deferral pattern:** gravity.css, block-panels.css, and swiper CSS all use the `media="print" onload` pattern — excellent non-blocking loading
- **Swiper conditional loading:** 169KB of CSS+JS only loads on pages with slider blocks — verified on 4 page types
- **jQuery deferred:** Both jQuery files use `defer` attribute — no render-blocking impact
- **Analytics delayed:** GTM and Byggfakta load after 3.5s/user interaction — zero FCP/LCP impact
- **H2 multiplexing:** All first-party resources served over HTTP/2 — parallel downloads, no head-of-line blocking
- **Cache headers:** 1-year browser cache on all static assets with ETags
- **Preconnect hints:** cdn.jsdelivr.net and googletagmanager.com pre-connected
- **Font preloaded:** `soehne-buch.woff2` correctly preloaded as highest priority
- **No unnecessary resources on /no/kontor/:** Verified clean — no Swiper, no extra JS bundles
- **All CSS/JS identical between /no/ and /no/kontor/:** Same resource set (excluding Swiper), confirming no page-type bloat
