# Image Optimization Audit

**Date:** 2026-02-27
**Status:** FAIL
**Page(s) tested:**
- https://acryli-28355.jana-osl.servebolt.cloud/no/
- https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/
- https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/rehabilitering-av-norges-idrettshogskole/

## Summary

The majority of images across the site lack WebP variants and are served as uncompressed JPEG regardless of browser support. Servebolt's WebP content negotiation (via `Vary: Accept`) works correctly, but only for images that have a `.webp` file on disk alongside the original. Only 2-3 of the newest uploads (Feb 2025) have WebP variants generated; all older uploads (2016–2024) and several Feb 2025 `.jpeg` files do not. The reference single page has a 1,031KB hero image with no WebP — the single biggest optimization opportunity on the site. Additionally, 2 images on the homepage have `fetchpriority="high"` when only the LCP element should, and 3 reference card images on the homepage lack `loading="lazy"`.

## Measurements

### Homepage `/no/` — Image inventory (above-fold + first scroll)

| Image | Requested URL | Served as | Transfer size | WebP variant? | width×height | loading | fetchpriority | srcset |
|-------|--------------|-----------|---------------|---------------|--------------|---------|---------------|--------|
| Logo (header) | acrylicon-logo-dark.svg | SVG | ~2KB | N/A | 208×45 | — | — | No |
| Hero background | haslumskole-1024x526.jpg | WebP | 11KB | YES | 1024×526 | — | **high** | Yes (4 sizes) |
| Hero video | Nasjonalteateret_Timelapse_1-1.mp4 | MP4 | 2,614KB | N/A | — | preload=metadata | — | No |
| Reference card 1 | haslumslide2.jpg | WebP | 30KB | YES | 894×594 | **MISSING** | — | Yes (3 sizes) |
| Reference card 2 | image2.jpg | WebP | 63KB | YES | 893×594 | **MISSING** | — | Yes (3 sizes) |
| Reference card 3 | b375329e…-768x1152.jpeg | JPEG | 156KB | **NO** | 683×1024 | lazy | — | Yes (6 sizes) |
| Dybdecase image | imagehhh8-1024x528.jpg | WebP | 64KB | YES | 1024×528 | lazy | — | Yes (4 sizes) |
| "Bli gulvlegger" PNG | 61a343bb…-1024x579.png | WebP (via redirect) | 52KB | YES | 1024×579 | — | **high** ⚠️ | Yes (4 sizes) |
| Products card 1 | imagefda3.jpg | WebP | 45KB | YES | 678×764 | lazy | — | Yes (2 sizes) |
| Products card 2 | image-98-1.jpg | WebP | 74KB | YES | 678×764 | lazy | — | Yes (2 sizes) |
| Products card 3 | haslumskole-1024x526.jpg | WebP | 11KB (cached) | YES | 1024×526 | lazy | — | Yes (4 sizes) |

### Reference archive `/no/referanser/` — 100 thumbnails loaded at once

| Metric | Value |
|--------|-------|
| Total `<img>` tags (wp-post-image) | 100 |
| Images with `loading="lazy"` | 97 |
| Images missing `loading="lazy"` | 3 (first 3 cards) |
| All images have `width` + `height` | YES |
| All images have `srcset` | YES |
| Images with `fetchpriority="high"` | 1 (first card) |
| Image format negotiation | All served as JPEG — no WebP variants |

**Archive image sizes (1024w thumbnails, JPEG):**

| Image | JPEG size | WebP available? |
|-------|-----------|-----------------|
| haslumslide2.jpg | 426KB (WebP: 30KB) | YES |
| b375329e…-1024x1536.jpeg | 296KB | NO |
| image2.jpg | 589KB (WebP: 63KB) | YES |
| natoipn-1024x683.jpeg | 113KB | NO |
| tankcoating-1024x528.jpg | 58KB | NO |
| IMG_29571-1024x822.jpeg | 233KB | NO |
| basseng-1-1024x768.jpg | 139KB | NO |
| Gard05_9017-1024x683.jpg | 273KB | NO |
| Gard03_8908-1024x683.jpg | ~270KB | NO |
| matkanalen-1024x527.jpg | 103KB | NO |
| IMG_1768.jpg (960w max) | 254KB | NO |

### Single reference page — `/no/referanser/rehabilitering-av-norges-idrettshogskole/`

| Image | Purpose | JPEG size | WebP available? | loading | fetchpriority |
|-------|---------|-----------|-----------------|---------|---------------|
| image2.jpg | Featured image (hero) | 589KB | YES (63KB) | — | high |
| image-86.jpg | Body hero image | **1,031KB** | **NO** | — | **high** |
| image-98.jpg | Gallery | **985KB** | **NO** | lazy | — |
| image.jpg | Gallery | **448KB** | **NO** | lazy | — |
| 9720950f…-683x1024.jpeg | Gallery | **235KB** | **NO** | lazy | — |

## Issues Found

### Issue 1: Most images lack WebP variants — systemic generation gap
- **Pages:** All pages site-wide
- **Problem:** Servebolt's WebP content negotiation works perfectly (verified via `Vary: Accept` + `Content-Type: image/webp` on supported images), but WebP `.webp` files only exist for a handful of images uploaded around Feb 2025. All images from 2016–2024 and several `.jpeg` files from 2025 have NO WebP variant on disk. Without the `.webp` file, Apache serves the original JPEG/PNG.
- **Impact:** HIGH. On the reference single page alone, `image-86.jpg` (1,031KB) + `image-98.jpg` (985KB) = 2,016KB that could be ~300-400KB as WebP — saving ~1.6MB per page load. Across the 100-thumbnail archive, estimated total savings: 5-10MB. On Fast 4G (~1.6Mbps), saving 1.6MB = ~8 seconds faster.
- **Fix:** Run a bulk WebP generation job on the server for all existing uploads. Options:
  1. **WP-CLI + cwebp:** `find uploads/ -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" | xargs -I{} cwebp -q 80 {} -o {}.webp` (then rename to match Apache's expected naming)
  2. **WordPress plugin:** Use "WebP Express" or "ShortPixel" to generate WebP for all existing media
  3. **Servebolt's built-in:** Check if Servebolt has a WebP generation feature that can be enabled for existing files
- **Priority:** HIGH — single biggest performance win available

### Issue 2: `image-86.jpg` on reference page is 1,031KB unoptimized JPEG
- **Page:** `/no/referanser/rehabilitering-av-norges-idrettshogskole/`
- **Problem:** This 679×908 image is 1,031KB as JPEG — extremely large for its dimensions. It appears to be an uncompressed/minimally-compressed JPEG. Even without WebP, re-encoding at quality 80 would likely reduce it to ~200-300KB.
- **Impact:** HIGH. This is loaded eagerly with `fetchpriority="high"`. On Fast 4G it takes ~5 seconds to transfer. As WebP at q80, estimated ~150-200KB — saving ~830KB (~4 seconds on Fast 4G).
- **Fix:** Re-encode the original at JPEG quality 80-85 AND generate WebP variant. The original may have been uploaded at quality 95-100.
- **Priority:** HIGH

### Issue 3: `image-98.jpg` is 985KB — same over-compression issue
- **Page:** `/no/referanser/rehabilitering-av-norges-idrettshogskole/`
- **Problem:** 679×908 gallery image at 985KB. Same issue as image-86.jpg.
- **Impact:** MEDIUM (loaded lazily, below fold, but still nearly 1MB per visitor who scrolls)
- **Fix:** Re-encode at quality 80 + generate WebP variant
- **Priority:** MEDIUM

### Issue 4: `b375329e…jpeg` images have no WebP variant (multiple pages)
- **Pages:** Homepage, reference archive, reference single
- **Problem:** This portrait JPEG appears on 3 different page types. The 683×1024 version is 156KB, the 1024×1536 version is 296KB. No WebP variant exists despite being uploaded Feb 2025.
- **Impact:** MEDIUM. As WebP, estimated 50-80KB at 683w — saving ~100KB per occurrence. Multiply by 3+ pages.
- **Fix:** Generate WebP variants for all `.jpeg` extension files (the WordPress WebP generator may only be processing `.jpg` files, not `.jpeg`)
- **Priority:** MEDIUM — may indicate a bug where `.jpeg` extension files are skipped by the WebP generator

### Issue 5: Two `fetchpriority="high"` on homepage — only LCP element should have it
- **Page:** `/no/`
- **Problem:** Both `haslumskole-1024x526.jpg` (above-fold hero background) AND `61a343bb…png` ("Bli gulvlegger" section, far below fold) have `fetchpriority="high"`. The LCP element is actually the `<video>` tag, which has NO fetchpriority attribute. Multiple `fetchpriority="high"` dilutes the signal — the browser can't prioritize everything.
- **Impact:** LOW-MEDIUM. The video loads fast anyway (297ms LCP), but removing fetchpriority from the below-fold image and potentially adding it to the video's poster frame would be correct.
- **Fix:**
  1. Remove `fetchpriority="high"` from the "Bli gulvlegger" image (far below fold)
  2. The hero background image's `fetchpriority="high"` is acceptable (it's the fallback if video doesn't load)
  3. Consider adding `fetchpriority="high"` to the `<video>` element itself (browsers support this)
- **Priority:** LOW

### Issue 6: Three reference card images on homepage missing `loading="lazy"`
- **Page:** `/no/`
- **Problem:** The 3 reference card thumbnails (`haslumslide2.jpg`, `image2.jpg`, `b375329e…jpeg`) in the "Utvalgte referanser" section lack `loading="lazy"`. These are just below the initial viewport on mobile (after scrolling past the hero). WordPress 5.5+ adds `loading="lazy"` automatically except for the first few images — these 3 images appear to be just at the threshold.
- **Impact:** LOW. These are relatively small (30-156KB with WebP negotiation) and close to viewport. Eager loading here may actually be beneficial for UX.
- **Fix:** Optional — add `loading="lazy"` if they're consistently below fold. But since they're borderline above-fold on some devices, the current behavior is acceptable.
- **Priority:** LOW

### Issue 7: Hero video uses `/norway/` redirect path
- **Page:** `/no/`
- **Problem:** The `<video>` src points to `/norway/wp-content/uploads/…/Nasjonalteateret_Timelapse_1-1.mp4` which 301-redirects to `/no/wp-content/uploads/…`. This adds an extra round trip (~50-100ms on Fast 4G) before the 2.6MB video starts streaming.
- **Impact:** LOW (video already preloads metadata, and LCP is fast at 297ms). But it's a free fix.
- **Fix:** Update the video URL in the ACF field/WordPress content to use the `/no/` path directly. This also applies to the "Bli gulvlegger" PNG image which uses a `/norway/` path.
- **Priority:** LOW (but trivial fix in WordPress admin)

### Issue 8: Reference archive loads 100 images on a single page
- **Page:** `/no/referanser/`
- **Problem:** All 100 reference posts are loaded on a single page with no pagination or infinite scroll. While 97 of 100 have `loading="lazy"`, the browser will still prefetch many of them on scroll. At ~150-300KB each (JPEG, no WebP), the full page weight for images alone could be 15-30MB.
- **Impact:** MEDIUM on mobile data plans. Initial page load is fine (only 3-5 images loaded eagerly), but scrolling through all references would consume significant bandwidth.
- **Fix:** Consider implementing pagination (e.g., 12-24 items per page) or AJAX infinite scroll loading. This would also improve SEO by creating paginated archive URLs.
- **Priority:** MEDIUM

### Issue 9: Multiple images missing descriptive `alt` text
- **Pages:** Homepage, single reference page
- **Problem:** Several content images have empty `alt=""` attributes:
  - `haslumskole-1024x526.jpg` (homepage hero background) — `alt=""`
  - `imagehhh8-1024x528.jpg` (dybdecase image) — `alt=""`
  - `61a343bb…png` ("Bli gulvlegger" image) — `alt=""`
  - `imagefda3.jpg`, `image-98-1.jpg` (product cards) — `alt=""`
  - `image-86.jpg`, `image-98.jpg` (single reference body) — `alt=""`
- **Impact:** LOW for performance, MEDIUM for SEO. Missing alt text means Google Image search can't index these images. Decorative images should have `alt=""`, but content images (hero backgrounds, reference photos) should have descriptive alt text.
- **Fix:** Add descriptive Norwegian alt text to content images via WordPress media library. Example: `alt="Acrylicon gulvbelegg i skolebygg"` for haslumskole.
- **Priority:** MEDIUM for SEO

## Passed Checks

1. **All images have `width` and `height` attributes** — prevents CLS (Cumulative Layout Shift). Confirmed on all 3 pages.
2. **All content images have `srcset`** — responsive image serving works correctly. WordPress generates multiple sizes (300w, 768w, 1024w, 1536w, 2048w).
3. **`sizes` attribute is present** on all images with srcset — browsers can select the right size for the viewport.
4. **WebP content negotiation works when variants exist** — Servebolt's Apache config correctly serves WebP to supporting browsers via `Vary: Accept` header. Verified: `haslumslide2.jpg` is 426KB as JPEG, 30KB as WebP (93% reduction). `image2.jpg` is 589KB as JPEG, 63KB as WebP (89% reduction).
5. **`loading="lazy"` correctly applied** to below-fold images on all pages. 97/100 archive thumbnails have it.
6. **`decoding="async"`** present on all images — allows non-blocking decode.
7. **Featured images on single reference pages** correctly have `fetchpriority="high"`.
8. **SVG icons and logos** are small, properly sized, and correctly lack lazy loading.
9. **Cache headers excellent** — `max-age=604800` (1 week) on all static image assets.
10. **No broken images** found across all 3 pages tested.

## Estimated Total Impact

| Fix | Estimated savings | Difficulty |
|-----|------------------|------------|
| Generate WebP for ALL existing uploads | 50-80% size reduction across entire media library | Medium (server-side bulk operation) |
| Re-encode over-compressed JPEGs (image-86, image-98) | ~1.6MB saved on single reference page | Easy (re-upload or server-side reprocessing) |
| Fix `/norway/` redirect paths | ~100ms per redirected resource | Easy (DB search-replace) |
| Add pagination to reference archive | Reduce initial page weight by ~90% | Medium (template change) |
| Add alt text to content images | SEO improvement for Google Images | Easy (WordPress admin) |

**Priority order:** WebP generation > JPEG re-encoding > Archive pagination > Alt text > Redirect paths > Fetchpriority cleanup
