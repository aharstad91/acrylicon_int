# Core Web Vitals — Norwegian Homepage /no/

**Date:** 2026-02-27
**Status:** PASS
**Page(s) tested:** https://acryli-28355.jana-osl.servebolt.cloud/no/

## Summary

The Norwegian homepage passes Core Web Vitals thresholds with flying colors under lab conditions. LCP is 297ms (threshold: 2500ms) and CLS is 0.00 (threshold: 0.1). The LCP element is a hero `<video>` (Nasjonalteateret timelapse MP4, 2.5MB). While the headline scores are excellent, several optimization opportunities exist around resource redirects, image formats, third-party script weight, and the LCP resource missing `fetchpriority=high`.

## Measurements

| Metric | Value | Threshold | Status |
|--------|-------|-----------|--------|
| **LCP** | 297ms | < 2500ms | PASS |
| **CLS** | 0.00 | < 0.1 | PASS |
| **TTFB** | 32ms | < 800ms | PASS |
| **Max Critical Path** | 245ms | — | Good |
| **Console Errors** | 0 | 0 | PASS |

### LCP Breakdown (297ms total)

| Phase | Duration | % of LCP |
|-------|----------|----------|
| TTFB | 32ms | 10.7% |
| Resource Load Delay | 172ms | 58.1% |
| Resource Load Duration | 27ms | 9.2% |
| Element Render Delay | 65ms | 22.0% |

### Render-Blocking Resources (4 CSS files)

| Resource | Uncompressed | Gzipped | Duration |
|----------|-------------|---------|----------|
| `block-library/style.min.css` | 116KB | ~17KB | 4ms |
| `style.css` | 3.8KB | ~1KB | 5ms |
| `fonts/fonts.css` | 990B | ~0.5KB | 6ms |
| `tailwind.css` | 22.6KB | ~5.9KB | 7ms |

All render-blocking CSS loaded in <7ms via H2 multiplexing — **estimated savings: 0ms** (already optimal).

### Key Resource Sizes

| Resource | Size | Format | Notes |
|----------|------|--------|-------|
| Hero video (LCP) | 2,614KB | MP4 | Streamed via HTTP 206 range request |
| image2.jpg | 589KB | JPEG | Below fold |
| 61a343bb...png | 882KB | PNG | Should be WebP |
| haslumslide2.jpg | 426KB | JPEG | Below fold |
| haslumskole.jpg | 39KB | JPEG | OK size |
| block-library CSS | 116KB (17KB gzip) | CSS | WP core, mostly unused |
| jQuery | 88KB (32KB gzip) | JS | WP core |
| GSAP | ~73KB (gzip) | JS | CDN (jsdelivr) |
| soehne-buch.woff2 | 33KB | WOFF2 | Single font file |

### Third-Party Script Weight

| Third Party | Transfer Size | Main Thread Time |
|-------------|---------------|------------------|
| Facebook Pixel | 517.9KB | 29ms |
| Google Tag Manager | 347.8KB | 15ms |
| docu.info | 82KB | 1ms |
| JSDelivr CDN (GSAP) | 72.8KB | 6ms |
| LinkedIn Ads | 53.8KB | 3ms |
| readpeak.com | 4KB | 0.1ms |

Third-party scripts total: ~1,078KB transfer, ~54ms main thread. All deferred — good.

## Issues Found

### Issue 1: LCP resource (hero video) missing `fetchpriority=high`

- **Page:** /no/
- **Problem:** The LCP element is a `<video>` tag loading `Nasjonalteateret_Timelapse_1-1.mp4`. It currently has `priority: Low` in the network waterfall. Adding `fetchpriority="high"` to the `<video>` element would signal the browser to prioritize it.
- **Impact:** Low — LCP is already 297ms, but on slower connections this could matter. The "Resource Load Delay" phase (172ms, 58% of LCP) is the biggest contributor and could be reduced with higher priority.
- **Fix:** Add `fetchpriority="high"` to the hero `<video>` element in the homepage template or ACF block.
- **Priority:** Low

### Issue 2: 301 redirects on media assets from `/norway/` to `/no/`

- **Page:** /no/
- **Problem:** Two resources are initially requested from `/norway/` paths and 301 redirect to `/no/`:
  - `Nasjonalteateret_Timelapse_1-1.mp4` — 19ms redirect overhead (this is the LCP resource!)
  - `61a343bb...png` — redirect overhead
  - `arrow-right.svg` — redirect overhead
- **Impact:** Medium — The LCP video incurs an extra 19ms round-trip due to the redirect. On slower connections this would be worse. This indicates stale URLs in the page HTML or WordPress database referencing the old `/norway/` slug.
- **Fix:** Run a search-replace in the WordPress database to update all `/norway/` references to `/no/`. Check ACF fields and post content for hardcoded `/norway/` paths.
- **Priority:** Medium

### Issue 3: Large PNG image served instead of WebP

- **Page:** /no/
- **Problem:** `61a343bbc34e8d2c9b28588fba1c25726462466c-1024x579.png` is 882KB. As WebP this would be ~150-200KB (75-80% savings). The image also goes through a 301 redirect from `/norway/`.
- **Impact:** Medium — Saves ~680KB on mobile. Not on the critical path for LCP but adds to total page weight.
- **Fix:** Re-upload the image as WebP, or use a WebP conversion plugin. Update the reference to use the `/no/` path.
- **Priority:** Medium

### Issue 4: Large JPEG images without WebP alternative

- **Page:** /no/
- **Problem:** `image2.jpg` (589KB) and `haslumslide2.jpg` (426KB) are served as JPEG. As WebP these would be ~120KB and ~85KB respectively.
- **Impact:** Medium — Combined savings of ~810KB. These appear to be below-fold so they don't affect LCP but hurt total page weight and PageSpeed score.
- **Fix:** Convert to WebP. Ensure `loading="lazy"` is present on below-fold images.
- **Priority:** Medium

### Issue 5: WordPress block-library CSS loaded but mostly unused

- **Page:** /no/
- **Problem:** `block-library/style.min.css` is 116KB (17KB gzipped) and is render-blocking. This is the WordPress Gutenberg block CSS that includes styles for ALL block types, but the homepage likely only uses a few.
- **Impact:** Low — With gzip it's only 17KB and loaded in 4ms over H2. But it's technically wasted CSS.
- **Fix:** Consider using `wp_dequeue_style('wp-block-library')` and inlining only the critical block styles needed, or accept the minimal cost. Not worth the complexity unless pursuing a perfect PageSpeed score.
- **Priority:** Low

### Issue 6: Cache TTL could be longer for first-party static assets

- **Page:** /no/
- **Problem:** First-party images and the hero video have `max-age=604800` (7 days). Font files and CSS have the same. For versioned assets (CSS with `?ver=`), the cache could be much longer (1 year) since the version string changes on updates.
- **Impact:** Low — Only affects repeat visits, not first-load PageSpeed scores.
- **Fix:** Set `max-age=31536000` (1 year) for versioned CSS/JS assets. Already applied to CSS files per the headers — this is actually already correct for CSS (headers show `max-age=31536000`). The 7-day cache is for images and video, which is reasonable.
- **Priority:** Low

## Passed Checks

- **LCP: 297ms** — Excellent. Well under 2500ms threshold.
- **CLS: 0.00** — Perfect. No layout shift detected.
- **TTFB: 32ms** — Excellent server response time.
- **No console errors** — Clean JS execution.
- **HTTP/2** — All first-party resources served over H2 with multiplexing.
- **GTM deferred** — Google Tag Manager loads after page render (at ~3.5s), not blocking the main thread during initial load.
- **Third-party scripts deferred** — Facebook, LinkedIn, docu.info, readpeak all load well after FCP. Total main thread impact: ~54ms.
- **Preconnect hints present** — `cdn.jsdelivr.net` and `googletagmanager.com` have preconnect.
- **Font loading** — Single font file (soehne-buch.woff2, 33KB), loaded early.
- **CSS gzip compression** — All CSS is gzip-compressed and served efficiently.
- **Critical path short** — Max critical path latency of 245ms is excellent.
