# Core Web Vitals — English Homepage /

**Date:** 2026-02-27
**Status:** PASS
**Page(s) tested:** https://acryli-28355.jana-osl.servebolt.cloud/

## Summary

The English homepage passes Core Web Vitals thresholds with strong margins. LCP is 354ms (threshold: 2500ms) and CLS is 0.00 (threshold: 0.1). The LCP element is the same hero `<video>` as the Norwegian homepage. One notable difference: the EN homepage has a **JS error** (`Swiper is not defined`) that is absent on the NO homepage. Image serving benefits from Servebolt's WebP content negotiation — most images are automatically converted, reducing page weight significantly compared to raw file sizes. No 301 redirects on media assets (unlike the NO homepage's `/norway/` → `/no/` redirect issue).

## Measurements

| Metric | Value | Threshold | Status |
|--------|-------|-----------|--------|
| **LCP** | 354ms | < 2500ms | PASS |
| **CLS** | 0.00 | < 0.1 | PASS |
| **TTFB** | 35ms | < 800ms | PASS |
| **Max Critical Path** | 225ms | — | Good |
| **Console Errors** | 1 | 0 | FAIL |

### LCP Breakdown (354ms total)

| Phase | Duration | % of LCP |
|-------|----------|----------|
| TTFB | 35ms | 9.9% |
| Resource Load Delay | 157ms | 44.2% |
| Resource Load Duration | 115ms | 32.3% |
| Element Render Delay | 48ms | 13.6% |

### Render-Blocking Resources (5 resources)

| Resource | Type | Duration | Notes |
|----------|------|----------|-------|
| `block-library/style.min.css` | CSS | 4ms | WP core |
| `style.css` | CSS | 4ms | Theme |
| `fonts/fonts.css` | CSS | 4ms | Font declarations |
| `tailwind.css` | CSS | 4ms | Main styles |
| `gsap.min.js` (jsdelivr CDN) | JS | 25ms | Render-blocking script in `<head>` |

GSAP is render-blocking on EN (loaded from CDN with 25ms total duration). The 4 CSS files load in ≤4ms each via H2 — negligible. Estimated savings from render-blocking: **0ms** (already fast enough).

### Key Resource Sizes (actual transferred)

| Resource | Transferred | Original | Format Served | Notes |
|----------|-------------|----------|---------------|-------|
| Hero video (LCP) | 2,614KB | 2,614KB | MP4 | Streamed via HTTP 206, no redirect |
| image2.jpg | **63KB** | 589KB | **WebP** | Auto-converted by Servebolt |
| 61a343bb...png | **52KB** | 882KB | **WebP** | Auto-converted by Servebolt |
| haslumslide2.jpg | **30KB** | 426KB | **WebP** | Auto-converted by Servebolt |
| haslumskole-1024x526.jpg | 39KB | 39KB | JPEG | No WebP conversion (older image) |
| soehne-buch.woff2 | 33KB | 33KB | WOFF2 | Single font file |

### Third-Party Script Weight

| Third Party | Transfer Size | Main Thread Time |
|-------------|---------------|------------------|
| Facebook Pixel | 517.9KB | 13ms |
| Google Tag Manager | 347.8KB | 8ms |
| docu.info | 82KB | 1ms |
| JSDelivr CDN (GSAP) | 72.8KB | 5ms |
| LinkedIn Ads | 53.8KB | 3ms |
| readpeak.com | 4KB | 0.1ms |

Third-party total: ~1,078KB transfer, ~30ms main thread. All deferred (loaded after initial render) — good.

## Comparison: EN (/) vs NO (/no/)

| Metric | EN (/) | NO (/no/) | Difference |
|--------|--------|-----------|------------|
| **LCP** | 354ms | 297ms | +57ms (EN slightly slower) |
| **CLS** | 0.00 | 0.00 | Same |
| **TTFB** | 35ms | 32ms | +3ms (negligible) |
| **Console Errors** | 1 (Swiper) | 0 | EN has JS error |
| **image2.jpg transferred** | 63KB (WebP) | 589KB (JPEG) | EN benefits from WebP content negotiation |
| **Large PNG transferred** | 52KB (WebP) | 882KB (PNG) | EN benefits from WebP content negotiation |
| **haslumslide2 transferred** | 30KB (WebP) | 426KB (JPEG) | EN benefits from WebP content negotiation |
| **Media 301 redirects** | 0 | 3 (`/norway/` → `/no/`) | EN has clean paths |
| **Render-blocking GSAP** | 25ms | ~7ms | EN GSAP slightly slower (CDN vs cached) |
| **Total image weight** | ~184KB | ~1,936KB | EN saves ~1,752KB via WebP |

### Key Differences Explained

1. **WebP content negotiation works on EN, appears not on NO.** Servebolt serves WebP via `Vary: Accept` header on the EN site. The NO homepage images go through `/norway/` → `/no/` 301 redirects, which may bypass WebP negotiation. This is a **significant finding** — the NO homepage transfers ~1.7MB more in images than EN.

2. **Swiper JS error on EN only.** `scripts.js` references `Swiper` but the Swiper library is not loaded (conditional loading only on pages with `acf/slider-block`). The EN homepage must have a Swiper initialization call that the NO homepage does not. This is a functional bug, not a performance issue — but it's a console error that affects PageSpeed audits.

3. **No media 301 redirects on EN.** The EN site serves images from direct paths. The NO site has stale `/norway/` references in the database.

## Issues Found

### Issue 1: JS Error — "Swiper is not defined"

- **Page:** / (EN homepage)
- **Problem:** `scripts.js:1:14` throws `Uncaught ReferenceError: Swiper is not defined`. Swiper JS/CSS is conditionally loaded only on pages with `acf/slider-block`, but `scripts.js` contains Swiper initialization code that runs on all pages without checking if Swiper is available.
- **Impact:** Medium — Does not affect LCP/CLS, but:
  - Shows as a console error in PageSpeed/Lighthouse audits
  - Indicates broken JavaScript execution path
  - Could mask other JS errors
  - Any slider functionality on this page is broken
- **Fix:** Wrap the Swiper initialization in `scripts.js` with a guard: `if (typeof Swiper !== 'undefined') { ... }`. Or, better: move the Swiper init code into a separate file that only loads alongside the Swiper library.
- **Priority:** High (functional bug + PageSpeed signal)

### Issue 2: LCP video missing `fetchpriority="high"`

- **Page:** / (EN homepage)
- **Problem:** The LCP element (`<video>` tag for `Nasjonalteateret_Timelapse_1-1.mp4`) has `priority: Low` in the network waterfall. The "Resource Load Delay" phase is 157ms (44% of total LCP), partly because the browser deprioritizes the video.
- **Impact:** Low — LCP is 354ms (well under threshold), but `fetchpriority="high"` would signal the browser to start loading it sooner.
- **Fix:** Add `fetchpriority="high"` to the hero `<video>` element.
- **Priority:** Low

### Issue 3: haslumskole-1024x526.jpg not converted to WebP

- **Page:** / (EN homepage)
- **Problem:** This older image (from 2016) is still served as JPEG (39KB). All other images benefit from Servebolt's WebP content negotiation, but this one does not (no `Vary: Accept` response, content-type remains `image/jpeg`).
- **Impact:** Low — The image is only 39KB so savings would be minimal (~15-20KB as WebP).
- **Fix:** Re-upload the image or trigger Servebolt's WebP conversion for this file. Low priority given the small size.
- **Priority:** Low

### Issue 4: NO homepage missing WebP content negotiation (cross-reference)

- **Page:** /no/ (Norwegian homepage — found via comparison)
- **Problem:** The NO homepage transfers ~1,752KB more in images than EN because WebP content negotiation is not working. This is likely caused by the `/norway/` → `/no/` 301 redirects — when images redirect, the `Accept` header with WebP support may not be forwarded, causing Servebolt to serve the original JPEG/PNG.
- **Impact:** High — 1.7MB of unnecessary image data on the main landing page. While it doesn't affect LCP (which is the video), it massively increases total page weight, slows below-fold content, and hurts the overall PageSpeed score.
- **Fix:** Fix the stale `/norway/` references in the WordPress database so images load from direct `/no/` paths (or direct `/wp-content/uploads/` paths). This would enable WebP content negotiation on NO, saving ~1.7MB.
- **Priority:** High

## Passed Checks

- **LCP: 354ms** — Excellent. Well under 2500ms threshold.
- **CLS: 0.00** — Perfect. No layout shift detected.
- **TTFB: 35ms** — Excellent server response time (Servebolt).
- **HTTP/2** — All first-party resources served over H2 with multiplexing.
- **GTM deferred** — Loads after page render (~3.5s timeout), not blocking FCP/LCP.
- **All third-party scripts deferred** — Facebook, LinkedIn, docu.info, readpeak load well after FCP.
- **WebP content negotiation active** — Servebolt auto-converts JPEG/PNG to WebP for supporting browsers. Saves ~1.7MB on this page vs serving originals.
- **Preconnect hints present** — `cdn.jsdelivr.net` and `googletagmanager.com` have preconnect.
- **No media redirect overhead** — Unlike NO homepage, EN serves all images from direct paths.
- **Font loading** — Single font file (soehne-buch.woff2, 33KB), loaded early.
- **CSS gzip compression** — All CSS gzip-compressed and served efficiently.
