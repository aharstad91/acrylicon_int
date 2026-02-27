# SEO hreflang and International Targeting

**Date:** 2026-02-27
**Status:** FAIL
**Page(s) tested:** 8 pages across 4 EN/NO pairs, plus 2 additional product and reference single pairs

## Summary

Hreflang implementation has **critical systemic flaws** that cause Google to misidentify language versions. The root cause is in `inc/language-switcher.php`: the `acrylicon_map_slug()` function maps CPT prefix slugs (e.g., `produkter` → `products`) but does NOT map individual post slugs. When EN and NO versions of a post have different slugs (which is the norm for translated content), hreflang points to non-existent URLs that either 404 or 301 redirect. Additionally, two page types have broken or missing hreflang pairs entirely.

## Methodology

- Extracted `<link rel="alternate" hreflang="...">` tags from HTML source via `curl -sL`
- Verified each hreflang target URL returns HTTP 200 (not 301 or 404)
- Verified reciprocity: if page A declares hreflang to B, page B must declare hreflang back to A
- Checked `<html lang>` attribute consistency
- Reviewed source code of hreflang generation in `inc/language-switcher.php`

## Issues Found

### Issue 1: CRITICAL — Post slug mismatch causes broken hreflang on ALL translated CPT posts
- **Pages:** Every product, reference, and other CPT single page with translated slugs
- **Problem:** `acrylicon_map_slug()` only maps URL path **segments** (directory names like `produkter` → `products`), NOT individual post slugs. When multisite-sync copies a post from NO to EN, the post slug can differ between sites.

  **Example — Product page (Flake System):**
  - EN canonical: `/products/flake-system-floor/`
  - NO canonical: `/no/produkter/flake-system-gulv/`
  - EN hreflang `nb` declares: `/no/produkter/flake-system-floor/` → **HTTP 404** (English slug on Norwegian site)
  - NO hreflang `en` declares: `/products/flake-system-gulv/` → **HTTP 301** → `/products/flake-system-floor/` (Norwegian slug on English site, redirects)

  **Example — Reference single (NIH):**
  - EN canonical: `/references/rehabilitation-of-the-norwegian-school-of-sport-sciences/`
  - NO canonical: `/no/referanser/rehabilitering-av-norges-idrettshogskole/`
  - EN hreflang `nb` declares: `/no/referanser/rehabilitation-of-the-norwegian-school-of-sport-sciences/` → **HTTP 404**
  - NO hreflang `en` declares: `/references/rehabilitering-av-norges-idrettshogskole/` → **HTTP 301** → correct EN URL

- **Impact:** HIGH. Google ignores hreflang annotations that point to 404s or redirects. This means:
  - Google may serve the wrong language version to searchers
  - Norwegian users could see English results and vice versa
  - No rich "language toggle" in search results for these pages
  - Affects potentially ALL CPT single pages where slugs differ between EN and NO
- **Root cause:** `acrylicon_get_equivalent_url()` (line 109 of `inc/language-switcher.php`) splits the URL path into segments and maps each via `acrylicon_slug_map()`. The slug map only contains prefix mappings (line 42-68), not individual post slug mappings.
- **Fix:** The function needs to look up the actual equivalent post on the target blog instead of naively mapping URL segments. Two approaches:
  1. **Post meta approach:** Store a `_translation_pair_id` meta field linking EN/NO posts during sync. Then `acrylicon_get_equivalent_url()` reads the meta and gets the actual permalink on the target blog via `get_permalink()`.
  2. **Direct DB lookup:** Since multisite-sync copies posts with the same post ID, look up `get_permalink()` on the same post ID on the target blog via `switch_to_blog()`.
- **Priority:** Critical

### Issue 2: CRITICAL — EN /offices/ returns 404, breaks kontor hreflang pair
- **Pages:** EN `/offices/` and NO `/no/kontor/`
- **Problem:** The English equivalent page at `/offices/` does not exist (HTTP 404). The NO `/no/kontor/` page correctly declares its EN hreflang as `/offices/`, but the URL is dead.

  **hreflang chain:**
  - NO `/no/kontor/` → hreflang `en` = `/offices/` → **HTTP 404**
  - EN `/offices/` → 404 page outputs hreflang to NO `/no/` (homepage!) — the 404 handler falls through to homepage hreflang

- **Impact:** HIGH. Google cannot establish the EN/NO kontor/offices pair. Norwegian office page has no valid English counterpart.
- **Fix:** Create the `/offices/` page on the EN site (Blog 1) as the English equivalent of `/no/kontor/`, or set up a proper redirect to `/locations/` and update the slug map.
- **Priority:** High

### Issue 3: HIGH — EN /locations/ hreflang points to 404 /no/kontakt-oss/
- **Pages:** EN `/locations/` and NO `/no/kontakt-oss/`
- **Problem:** EN `/locations/` declares hreflang `nb` = `/no/kontakt-oss/`, but `/no/kontakt-oss/` returns HTTP 404. The page was removed or moved (likely to `/no/kontor/`), but the slug map still maps `kontakt-oss` ↔ `locations`.

  **hreflang chain:**
  - EN `/locations/` → hreflang `nb` = `/no/kontakt-oss/` → **HTTP 404**
  - The 404 page at `/no/kontakt-oss/` still emits hreflang tags pointing to EN homepage `/` (not back to `/locations/`)

  **Reciprocity broken in both directions:**
  - EN `/locations/` → NO `/no/kontakt-oss/` (404) — target dead
  - NO `/no/kontakt-oss/` (404) → EN `/` (homepage) — wrong page, not `/locations/`

- **Impact:** HIGH. The EN locations page has NO valid Norwegian counterpart. Google ignores the annotation entirely.
- **Fix:**
  1. Update the slug map: change `'kontakt-oss' => 'locations'` to `'kontor' => 'locations'`
  2. Set up a 301 redirect from `/no/kontakt-oss/` to `/no/kontor/`
  3. Verify `/no/kontor/` and `/locations/` then form a valid reciprocal pair
- **Priority:** High

### Issue 4: MEDIUM — Unlinked pages fall back to homepage hreflang
- **Pages:** EN `/products/epoxy-system/`, NO `/no/produkter/epoxy-system/`
- **Problem:** Some CPT pages that exist on both sites are not linked as translations. Their hreflang falls back to the homepage of the other language instead of pointing to the equivalent page.

  **Example — Epoxy System:**
  - EN `/products/epoxy-system/` → hreflang `nb` = `/no/` (homepage, NOT the product page)
  - NO `/no/produkter/epoxy-system/` → hreflang `en` = `/` (homepage, NOT the product page)

  In this case both pages have the SAME slug (`epoxy-system`), so the mapping should work... but the fallback to homepage suggests `is_404()` is returning true on one blog, or the URL path stripping is failing.

  **Wait — actually:** This page pair has identical slugs and the prefix mapping exists (`produkter` → `products`). The fallback to homepage means the function is hitting the `is_404()` or empty path check. This could be a bug where `switch_to_blog()` context causes `is_404()` to return unexpected results.

- **Impact:** MEDIUM. Pages with the same slug that should link correctly are falling back to homepage. This weakens international SEO for these specific pages.
- **Fix:** Debug the `acrylicon_get_equivalent_url()` function — add logging to identify why the epoxy-system path triggers the fallback. Likely a PATH parsing edge case.
- **Priority:** Medium

### Issue 5: LOW — 404 pages emit hreflang tags
- **Pages:** Any 404 URL, e.g., `/no/kontakt-oss/`
- **Problem:** The 404 template outputs hreflang tags (pointing to homepages). While Google likely ignores hreflang on 404 responses, it's technically incorrect and could confuse audit tools.

  **Observed:**
  - `/no/kontakt-oss/` (HTTP 404) outputs hreflang `en` → `/` and `nb` → `/no/kontakt-oss/` (self-referencing a 404!)

- **Impact:** LOW. Google ignores HTTP 404 pages, so hreflang on them is moot. But it indicates the hreflang function runs on 404s when it shouldn't.
- **Fix:** In `acrylicon_hreflang_tags()`, add an early return if `is_404()` is true:
  ```php
  function acrylicon_hreflang_tags() {
      if ( is_404() ) return;
      // ... rest of function
  }
  ```
- **Priority:** Low

## Detailed Pair Analysis

### Pair 1: Homepages — PASS
| | EN `/` | NO `/no/` |
|---|---|---|
| HTML lang | `en-US` | `nb-NO` |
| hreflang `en` | `https://.../ ` (self) | `https://.../` |
| hreflang `nb` | `https://.../no/` | `https://.../no/` (self) |
| x-default | `https://.../` (EN) | `https://.../` (EN) |
| Reciprocal? | Yes | Yes |
| All URLs 200? | Yes | Yes |

### Pair 2: Reference Archive — PASS
| | EN `/references/` | NO `/no/referanser/` |
|---|---|---|
| HTML lang | `en-US` | `nb-NO` |
| hreflang `en` | `https://.../references/` (self) | `https://.../references/` |
| hreflang `nb` | `https://.../no/referanser/` | `https://.../no/referanser/` (self) |
| x-default | `https://.../references/` (EN) | `https://.../references/` (EN) |
| Reciprocal? | Yes | Yes |
| All URLs 200? | Yes | Yes |

### Pair 3: Product Single (Flake System) — FAIL
| | EN `/products/flake-system-floor/` | NO `/no/produkter/flake-system-gulv/` |
|---|---|---|
| HTML lang | `en-US` | `nb-NO` |
| hreflang `en` | `https://.../products/flake-system-floor/` (self) | `https://.../products/flake-system-gulv/` → 301 → correct URL |
| hreflang `nb` | `https://.../no/produkter/flake-system-floor/` → **404** | `https://.../no/produkter/flake-system-gulv/` (self) |
| x-default | EN self | `https://.../products/flake-system-gulv/` → 301 |
| Reciprocal? | **NO** — EN→NO is 404, NO→EN is 301 | |
| All URLs 200? | **NO** — `/no/produkter/flake-system-floor/` is 404 | |

### Pair 4: Reference Single (NIH) — FAIL
| | EN `.../rehabilitation-of-the-...` | NO `.../rehabilitering-av-norges-...` |
|---|---|---|
| hreflang `en` | self | `.../references/rehabilitering-...` → 301 → correct EN URL |
| hreflang `nb` | `.../no/referanser/rehabilitation-...` → **404** | self |
| Reciprocal? | **NO** — EN→NO is 404 | |

### Pair 5: Locations ↔ Kontakt-oss — FAIL
| | EN `/locations/` | NO `/no/kontakt-oss/` |
|---|---|---|
| HTTP status | 200 | **404** |
| hreflang `nb` | `/no/kontakt-oss/` → **404** | `/no/kontakt-oss/` (self, but 404) |
| hreflang `en` | self | `/` (homepage! wrong page) |
| Reciprocal? | **NO** — target is 404, 404 points to homepage | |

### Pair 6: Kontor ↔ Offices — FAIL
| | NO `/no/kontor/` | EN `/offices/` |
|---|---|---|
| HTTP status | 200 | **404** |
| hreflang `en` | `/offices/` → **404** | self (on 404 page) |
| hreflang `nb` | self | `/no/` (homepage! wrong) |
| Reciprocal? | **NO** — target is 404 | |

### Pair 7: Epoxy System — FAIL (fallback to homepage)
| | EN `/products/epoxy-system/` | NO `/no/produkter/epoxy-system/` |
|---|---|---|
| hreflang `en` | self | `/` (homepage — wrong!) |
| hreflang `nb` | `/no/` (homepage — wrong!) | self |
| Reciprocal? | **NO** — both fall back to homepages | |

## Passed Checks

- **x-default**: Consistently points to English version on all pages — correct
- **Language codes**: Consistently `en` and `nb` across all pages — no mixing of `en`/`en-US` or `nb`/`nb-NO` in hreflang values
- **HTML lang attributes**: `en-US` on EN pages, `nb-NO` on NO pages — correct and consistent
- **HTTPS**: All hreflang URLs use HTTPS — no mixed HTTP/HTTPS
- **Absolute URLs**: All hreflang URLs are absolute with full domain — correct
- **Homepage pair**: Perfect reciprocity, all URLs resolve to HTTP 200
- **Archive pair**: Reference archive has perfect reciprocity and all URLs resolve

## Root Cause Analysis

The hreflang generation in `inc/language-switcher.php` uses a **URL segment mapping** approach (line 162-179):

```php
$segments = explode( '/', $path );
foreach ( $segments as $segment ) {
    $mapped_segments[] = acrylicon_map_slug( $segment, $direction );
}
```

This maps each URL segment individually. The `acrylicon_slug_map()` function only contains prefix mappings like `produkter` → `products`, NOT individual post slugs like `flake-system-gulv` → `flake-system-floor`.

**Result:** For a URL like `/no/produkter/flake-system-gulv/`:
- `produkter` maps to `products` ✓
- `flake-system-gulv` has no mapping, passes through unchanged ✗
- Generated URL: `/products/flake-system-gulv/` — wrong slug on EN site

This is a **fundamental design flaw**: you cannot map between translated content using URL segment replacement alone, because post slugs are independently managed per site.

## Recommendations Summary

| # | Issue | Fix | Priority | Effort |
|---|-------|-----|----------|--------|
| 1 | Post slug mismatch in hreflang | Replace segment mapping with post ID lookup via `switch_to_blog()` + `get_permalink()` | Critical | Medium |
| 2 | EN `/offices/` is 404 | Create the offices page on Blog 1, or redirect to `/locations/` | High | Low |
| 3 | EN `/locations/` → NO `/no/kontakt-oss/` is 404 | Update slug map: `kontakt-oss` → `kontor`, add 301 redirect | High | Low |
| 4 | Epoxy-system homepage fallback | Debug path parsing in `acrylicon_get_equivalent_url()` | Medium | Low |
| 5 | 404 pages emit hreflang | Add `if (is_404()) return;` guard in `acrylicon_hreflang_tags()` | Low | Trivial |

### Recommended Fix for Issue 1 (Post Slug Mismatch)

Since multisite-sync copies posts with the **same post ID** across blogs, the most reliable fix:

```php
// In acrylicon_get_equivalent_url(), after determining it's a single post:
if ( is_singular() ) {
    $current_post_id = get_queried_object_id();
    switch_to_blog( $target_blog_id );
    $target_url = get_permalink( $current_post_id );
    restore_current_blog();

    if ( $target_url ) {
        $cache[ $target_blog_id ] = $target_url;
        return $cache[ $target_blog_id ];
    }
    // Fall through to segment mapping for non-synced content
}
```

This approach:
- Uses WordPress's own permalink system (always correct slugs)
- Works regardless of slug language differences
- Falls back to existing segment mapping for non-synced content
- No new database tables or meta fields needed
