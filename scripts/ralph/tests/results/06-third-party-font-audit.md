# Third-party Script and Font Loading Audit

**Date:** 2026-02-27
**Status:** PASS (with minor recommendations)
**Page(s) tested:** https://acryli-28355.jana-osl.servebolt.cloud/no/

## Summary

Third-party scripts are excellently managed — all analytics/tracking scripts are deferred behind a 3.5-second timeout or first user interaction. Total third-party transfer is ~1,078KB but only ~40ms main thread time, with zero FCP/LCP impact. Font loading follows best practices: single woff2 font preloaded with `font-display: swap`. A few minor optimizations remain around redundant resource hints and an unnecessary 301 redirect.

## Measurements

### Third-party Script Impact (Chrome Performance Trace)

| Third Party | Transfer Size | Main Thread Time | Loading Method |
|---|---|---|---|
| Facebook Pixel | 517.9 KB | 21 ms | GTM (delayed) |
| Google Tag Manager | 347.8 KB | 8 ms | setTimeout 3.5s / interaction |
| Docu.info (Matomo) | 82.0 KB | 1 ms | setTimeout 3.5s / interaction |
| JSDelivr CDN (GSAP) | 72.8 KB | 7 ms | First-party footer script |
| LinkedIn Ads | 53.8 KB | 3 ms | GTM (delayed) |
| Readpeak | 4.0 KB | 0.1 ms | GTM (delayed) |
| **TOTAL** | **~1,078 KB** | **~40 ms** | |

### First-party Resource Sizes (gzipped transfer)

| Resource | Type | Transfer Size | Location |
|---|---|---|---|
| jQuery 3.7.1 | JS | 31.8 KB | Footer (deferred) |
| GSAP 3.14.2 | JS (CDN) | 29.3 KB | Footer (no defer) |
| wp-block-library | CSS | 17.1 KB | Head (render-blocking) |
| Tailwind CSS | CSS | 5.9 KB | Head (render-blocking) |
| jQuery Migrate 3.4.1 | JS | 5.0 KB | Footer (deferred) |
| style.css | CSS | 1.5 KB | Head (render-blocking) |
| bodyScrollLock.js | JS | 1.1 KB | Footer |
| transitions.js | JS | 0.8 KB | Footer |
| scripts.js | JS | 0.6 KB | Footer |
| fonts.css | CSS | 0.3 KB | Head (render-blocking) |

### Font Loading

| Font | Format | Size | Preloaded | font-display | Status |
|---|---|---|---|---|---|
| Soehne Buch | woff2 | 33.1 KB | Yes | swap | Loaded on homepage |
| Soehne Buch Kursiv | woff2 | (declared) | No | swap | Not loaded (unused on page) |
| Soehne Mono Buch | woff2 | (declared) | No | swap | Not loaded (unused on page) |

### Page Weight Breakdown (approximate)

| Category | Transfer Size | % of Total |
|---|---|---|
| Third-party scripts | ~1,078 KB | 42% |
| Video (hero, streaming) | ~800 KB initial | 31% |
| Images | ~300 KB | 12% |
| First-party JS | ~69 KB | 3% |
| CSS | ~25 KB | 1% |
| Fonts | ~33 KB | 1% |
| HTML + other | ~50 KB | 2% |
| **TOTAL (initial load)** | **~2,355 KB** | |

Note: Video streams via HTTP 206, so the full 2.5MB is not loaded upfront.

## Issues Found

### Issue 1: Docu.info Matomo has unnecessary 301 redirect
- **Page:** All pages
- **Problem:** `stats.docu.info/analytics/js` returns 301 redirect to `stats.docu.info/analytics/js/` (trailing slash). This adds an unnecessary round-trip (~50-100ms on mobile networks). The redirect is from the Matomo JavaScript tracker library URL.
- **Impact:** Low — this loads after the 3.5s delay, so no FCP/LCP impact. But it adds latency to analytics initialization.
- **Fix:** Change the Matomo tracker URL in the docu-snippet.js or the inline loading code to use the trailing-slash version directly: `//stats.docu.info/analytics/js/`
- **Priority:** Low

### Issue 2: Redundant dns-prefetch for cdn.jsdelivr.net
- **Page:** All pages
- **Problem:** The HTML `<head>` contains both `<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>` and `<link rel="dns-prefetch" href="//cdn.jsdelivr.net" />`. The `dns-prefetch` is redundant when `preconnect` is already present — `preconnect` does DNS + TCP + TLS, which is a superset of `dns-prefetch`.
- **Impact:** Negligible — just HTML bloat (~55 bytes).
- **Fix:** Remove the `dns-prefetch` line. The `preconnect` already covers it.
- **Priority:** Low

### Issue 3: jQuery Migrate still loaded
- **Page:** All pages
- **Problem:** `jquery-migrate.min.js` (5KB gzipped) is loaded on every page. Console shows `JQMIGRATE: Migrate is installed, version 3.4.1`. This library exists solely to warn about deprecated jQuery APIs during development — it's not needed in production.
- **Impact:** Low — 5KB transfer, minimal main thread time, loaded deferred.
- **Fix:** Dequeue `jquery-migrate` in `functions.php` for the front-end:
  ```php
  add_action('wp_default_scripts', function($scripts) {
      if (!is_admin()) {
          $scripts->remove('jquery-migrate');
      }
  });
  ```
- **Priority:** Low

### Issue 4: Facebook Pixel is the heaviest third-party (518KB)
- **Page:** All pages
- **Problem:** Facebook Pixel (`fbevents.js` + `signals/config`) transfers 517.9KB and uses 21ms of main thread time. This is the single largest third-party by both size and CPU. The config response alone is massive because it includes PII extraction modules for form scraping.
- **Impact:** Low on PageSpeed (already deferred behind 3.5s/interaction). But ~518KB of data transfer on every page visit for users on metered connections.
- **Fix:** Consider whether Facebook Pixel tracking is providing sufficient ROI. If conversion tracking is the primary use, the lighter Facebook Conversions API (server-side) could replace client-side pixel. Alternatively, restrict FB Pixel to key conversion pages only (contact, reference request).
- **Priority:** Medium (business decision)

### Issue 5: Missing preconnect for stats.docu.info
- **Page:** All pages
- **Problem:** No `<link rel="preconnect">` hint exists for `stats.docu.info` (Byggfakta analytics). Three separate requests go to this domain after the 3.5s delay. Without preconnect, the first request must do DNS + TCP + TLS sequentially.
- **Impact:** Low — analytics loads after 3.5s delay, so no user-facing impact.
- **Fix:** Add `<link rel="preconnect" href="https://stats.docu.info">` if reducing time-to-analytics-ready matters.
- **Priority:** Low

## Passed Checks

### GTM Delayed Loading — EXCELLENT
GTM is loaded via a well-implemented delayed pattern:
```javascript
var t = setTimeout(loadAnalytics, 3500);
['mouseover','touchstart','scroll','keydown'].forEach(function(evt) {
    document.addEventListener(evt, function handler() {
        clearTimeout(t);
        loadAnalytics();
        document.removeEventListener(evt, handler);
    }, {once: true, passive: true});
});
```
Both GTM and Byggfakta are inside this delay function. All GTM-triggered scripts (Facebook, LinkedIn, Readpeak) are consequently also delayed. This is the #1 reason this site's CWV scores are excellent despite ~1MB of third-party scripts.

### Font Loading — EXCELLENT
- Only 1 font file loaded per page (Soehne Buch, 33.1KB woff2)
- Preloaded in `<head>` with `as="font"` — eliminates font load delay
- `font-display: swap` on all 3 `@font-face` declarations — prevents invisible text
- Only woff2 format (no woff/ttf fallbacks) — modern, smallest format
- Unused fonts (Kursiv, Mono) declared but not downloaded (browser correctly skips)
- Cache-Control: `max-age=604800` (7 days) — good caching

### `<meta name="theme-color">` — PRESENT
```html
<meta name="theme-color" content="#253761">
```
Correctly set to the brand's dark blue color.

### Preconnect Hints — GOOD
- `preconnect` for `cdn.jsdelivr.net` (GSAP CDN) — correct
- `preconnect` for `www.googletagmanager.com` (GTM) — correct
- Font preload for `soehne-buch.woff2` — correct

### No Synchronous Third-party Scripts — PASS
Every third-party script is loaded asynchronously and deferred:
- GTM: loaded via `async` attribute + delayed injection
- Byggfakta: loaded via `async` + `defer` attributes + delayed injection
- Facebook, LinkedIn, Readpeak: loaded by GTM (inherently async)

### Third-party Main Thread Impact — MINIMAL
Total third-party main thread time: ~40ms. No third-party script blocks the main thread for more than 21ms (Facebook). This is well below the 50ms long-task threshold. No TBT/INP impact from third parties.

### Cache Headers — GOOD
- First-party static assets: `max-age=31536000` (1 year) — excellent
- GTM: `max-age=900` (15 min) — standard for GTM
- LinkedIn: `max-age=86400` (1 day) — standard
- Facebook: `max-age=1200` (20 min) — standard for FB

## Verdict

**PASS.** The third-party and font loading strategy is well-optimized. The delayed GTM pattern is the single most impactful optimization on this site — it keeps ~1MB of analytics scripts completely out of the critical rendering path. Font loading follows all best practices. The 5 issues found are all low-priority optimizations that would yield marginal improvements at best.
