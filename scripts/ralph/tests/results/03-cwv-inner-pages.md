# Core Web Vitals — Product page, reference page, office page

**Date:** 2026-02-27
**Status:** PASS
**Page(s) tested:**
- https://acryli-28355.jana-osl.servebolt.cloud/no/produkter/flake-system-gulv/
- https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/rehabilitering-av-norges-idrettshogskole/
- https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/

## Summary

All 3 inner page types pass Core Web Vitals thresholds with excellent margins. LCP ranges from 251–288ms (threshold: 2500ms) and CLS is 0.00 across all pages. Servebolt's fast TTFB (20–30ms) and H2 multiplexing keep render-blocking CSS negligible. One image optimization issue found on the reference page (1MB JPEG not served as WebP).

## Measurements

### Emulation Settings
- Viewport: 390x844, deviceScaleFactor 2, isMobile, hasTouch
- CPU throttle: 4x slowdown
- Network: Fast 4G

### Core Web Vitals Summary

| Page | LCP | CLS | TTFB | LCP Element | Console Errors |
|------|-----|-----|------|-------------|----------------|
| Product (Flake System) | **251 ms** | **0.00** | 30 ms | H2 text heading | None |
| Reference Single (NIH) | **288 ms** | **0.00** | 25 ms | IMG (hero thumbnail) | None |
| Office (/no/kontor/) | **258 ms** | **0.00** | 20 ms | P text paragraph | None |

### LCP Breakdown Per Page

**Product page — 251ms total:**
- TTFB: 30ms (11.9%)
- Element render delay: 221ms (88.1%)
- LCP element: `<h2>` text — no resource to load

**Reference single — 288ms total:**
- TTFB: 25ms (8.8%)
- Resource load delay: 157ms (54.4%)
- Resource load duration: 6ms (2.1%)
- Element render delay: 100ms (34.7%)
- LCP element: `<img>` hero image — `image2.jpg` served as WebP (63KB) via content negotiation

**Office page — 258ms total:**
- TTFB: 20ms (7.9%)
- Element render delay: 238ms (92.1%)
- LCP element: `<p>` text — no resource to load

### Render-Blocking Resources

All pages load the same 4 render-blocking CSS files via H2:
1. `tailwind.css` — ~7ms total
2. `fonts.css` — ~6ms total
3. `style.css` — ~6ms total
4. `block-library/style.min.css` — ~6ms total

**Estimated savings: 0ms** — H2 multiplexing makes these negligible.

### Third-Party Scripts (Transfer Size)

| Third Party | Product | Reference | Office |
|-------------|---------|-----------|--------|
| Facebook | 517.9 kB | 517.9 kB | 517.9 kB |
| Google Tag Manager | 347.8 kB | 347.8 kB | 347.8 kB |
| JSDelivr CDN | 72.8 kB | **245.8 kB** | 72.8 kB |
| docu.info | 82.0 kB | 82.0 kB | 79.7 kB |
| LinkedIn Ads | 53.8 kB | 53.8 kB | 53.8 kB |
| readpeak.com | 4.0 kB | 4.0 kB | 4.0 kB |

**Note:** Reference page loads 245.8KB from JSDelivr vs 72.8KB on others — the extra ~173KB is Swiper JS+CSS for the slider block on that page. This is expected conditional loading.

### Main Thread Time (Third Parties)

All third-party scripts are deferred and contribute minimal main thread time:
- Facebook: 12–18ms
- JSDelivr CDN: 6–10ms
- Google Tag Manager: 8ms
- LinkedIn Ads: 4ms
- docu.info: 1–2ms

**No third-party script blocks FCP or LCP.**

### Image Resources Per Page

**Product page (15 images):**
- `image-8-1024x527.jpg` — 106KB JPEG (hero, above fold)
- `cf0295a012ac0d1a4e8b2136e05538f5-768x1152.jpeg` — 122KB JPEG
- `da4b3ea22cd15877a2ea10ed7409a313-768x1152.jpeg` — 146KB JPEG
- 12 SVG icons/logos — negligible size
- All JPEGs have `Vary: Accept` header but served as JPEG (not WebP)

**Reference single (9 first-party images):**
- `image2.jpg` — **63KB WebP** (LCP image, correctly served as WebP via content negotiation)
- `image-86.jpg` — **1,031KB JPEG** (below fold, NOT served as WebP despite Vary: Accept)
- 7 SVG icons — negligible size

**Office page (2 first-party images):**
- `acrylicon-logo-dark.svg` — logo only
- `acrylicon-logo-light.svg` — footer logo
- No raster images at all

## Issues Found

### Issue 1: image-86.jpg on reference page is 1MB JPEG, not served as WebP
- **Page:** /no/referanser/rehabilitering-av-norges-idrettshogskole/
- **Problem:** `image-86.jpg` (1,031KB) is served as `image/jpeg` despite the server having `Vary: Accept` header and WebP content negotiation working for other images on the same page (e.g., `image2.jpg` serves as WebP). This image likely has no WebP variant generated, or the WebP file doesn't exist on disk.
- **Impact:** Medium — image is below the fold so doesn't affect LCP, but adds ~900KB unnecessary weight on mobile. On Fast 4G (~1.6 Mbps), that's ~4.5 seconds of download time.
- **Fix:** Regenerate WebP thumbnails for this image. Run `wp media regenerate` or use a WebP generation plugin to ensure all uploads have WebP variants. Verify the WebP file exists at the expected path.
- **Priority:** Medium

### Issue 2: Product page JPEGs not served as WebP
- **Page:** /no/produkter/flake-system-gulv/
- **Problem:** Three JPEG images (106KB, 122KB, 146KB) are served as `image/jpeg` despite `Vary: Accept` header being present. The content negotiation that works on the reference page's `image2.jpg` is not working for these older images (uploaded 2016/2021).
- **Impact:** Low — images are already reasonably sized (all under 150KB), but WebP would save ~60-75% (~225KB total savings).
- **Fix:** Same as Issue 1 — regenerate WebP variants for older uploads. The 2016-era images likely predate WebP generation.
- **Priority:** Low

### Issue 3: Swiper loaded on reference page adds 173KB
- **Page:** /no/referanser/rehabilitering-av-norges-idrettshogskole/
- **Problem:** JSDelivr loads 245.8KB on the reference page vs 72.8KB on pages without sliders — the extra ~173KB is Swiper JS+CSS for the `acf/slider-block`.
- **Impact:** Low — Swiper is loaded conditionally (only on pages with slider blocks) and is deferred, so it doesn't block rendering. However, 173KB is significant for a single carousel component.
- **Fix:** Consider lazy-loading Swiper on interaction (IntersectionObserver) rather than on page load. Or evaluate lighter carousel alternatives. This is a low-priority optimization since Swiper is already deferred.
- **Priority:** Low

## Passed Checks

- **LCP < 2500ms:** All 3 pages pass with massive margin (251–288ms, ~10x under threshold)
- **CLS < 0.1:** All 3 pages score 0.00 — perfect layout stability
- **TTFB:** Excellent across all pages (20–30ms) — Servebolt edge caching working well
- **No console errors:** All 3 pages clean (unlike EN homepage which has "Swiper is not defined")
- **Render-blocking CSS:** 4 files per page, all load in <7ms via H2 — estimated savings: 0ms
- **Third-party scripts:** All deferred, no impact on FCP/LCP
- **H2 protocol:** All first-party resources served via HTTP/2
- **Cache headers:** All static assets have `max-age=31536000` (1 year) for browser cache, `s-maxage=3600` for CDN
- **Conditional Swiper loading:** Confirmed — Swiper NOT loaded on /no/kontor/ or /no/produkter/, only on reference page with slider block
- **No 301 redirects on inner pages:** Unlike the homepage `/norway/` → `/no/` redirect issue, these inner pages serve resources from correct paths
