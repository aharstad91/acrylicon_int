# SEO Structured Data — Schema Completeness and Validity

**Date:** 2026-02-27
**Status:** FAIL
**Page(s) tested:**
- https://acryli-28355.jana-osl.servebolt.cloud/no/ (NO Homepage)
- https://acryli-28355.jana-osl.servebolt.cloud/ (EN Homepage)
- https://acryli-28355.jana-osl.servebolt.cloud/no/produkter/flake-system/ (Product)
- https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/rehabilitering-av-norges-idrettshogskole/ (Reference Single)
- https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/ (Reference Archive)
- https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/ (Office Archive)
- https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/acrylicon-industrigulv-as-sande/ (Office Single — Sande)
- https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/acrylicon-rogaland-as/ (Office Single — Rogaland)
- https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/acrylicon-midt-vest-norge-as/ (Office Single — Midt/Vest)
- https://acryli-28355.jana-osl.servebolt.cloud/products/flake-system/ (EN Product)
- https://acryli-28355.jana-osl.servebolt.cloud/references/ (EN Reference Archive)
- https://acryli-28355.jana-osl.servebolt.cloud/locations/ (EN Locations)

## Summary

The custom SEO module (`mu-plugins/acrylicon-seo/`) outputs JSON-LD structured data across all page types, and the basic graph structure (Organization, WebSite, WebPage, BreadcrumbList) is solid. However, there are **10 issues** spanning missing required fields, dangling @id cross-references, incorrect data types, and schema type mismatches. The most impactful issues are: Organization missing `contactPoint` (loses phone rich snippet potential), Product missing `offers` (no rich result eligibility), Article missing `author`, and ProfessionalService `address` as a plain string instead of `PostalAddress` (Google can't parse address components). All JSON-LD is syntactically valid, and all image URLs return HTTP 200.

## Schema per Page Type

### NO Homepage (`/no/`)

**Types:** Organization, WebSite, WebPage, BreadcrumbList — **4 types, all present**

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "name": "AcryliCon",
      "legalName": "AcryliCon Industrigulv AS",
      "logo": {
        "@type": "ImageObject",
        "url": "https://acryli-28355.jana-osl.servebolt.cloud/no/wp-content/themes/acrylicon-2024/assets/gfx/acrylicon-logo-dark.png",
        "width": 600,
        "height": 120
      },
      "foundingDate": "1977",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Industrivegen 24",
        "addressLocality": "Brumunddal",
        "postalCode": "2386",
        "addressCountry": "NO"
      },
      "sameAs": [],
      "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#organization",
      "url": "https://acryli-28355.jana-osl.servebolt.cloud/no/"
    },
    {
      "@type": "WebSite",
      "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#website",
      "url": "https://acryli-28355.jana-osl.servebolt.cloud/no/",
      "name": "AcryliCon",
      "inLanguage": "nb-NO",
      "publisher": { "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#organization" }
    },
    {
      "@type": "WebPage",
      "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#webpage",
      "url": "https://acryli-28355.jana-osl.servebolt.cloud/no/",
      "name": "AcryliCon | Sømløse gulv- og veggløsninger",
      "inLanguage": "nb-NO",
      "isPartOf": { "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#website" },
      "datePublished": "2025-02-05T10:52:09+01:00",
      "dateModified": "2025-10-08T08:59:40+02:00"
    },
    {
      "@type": "BreadcrumbList",
      "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#breadcrumb",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "AcryliCon", "item": "https://acryli-28355.jana-osl.servebolt.cloud/no/" },
        { "@type": "ListItem", "position": 2, "name": "Forside" }
      ]
    }
  ]
}
```

**@id references:** All resolve within page graph ✅
**Issues:** Organization missing `contactPoint`, `sameAs` is empty array. WebSite missing `potentialAction` (SearchAction).

### EN Homepage (`/`)

**Types:** Organization, WebSite, WebPage, BreadcrumbList — **Same structure as NO**

Key differences from NO:
- `inLanguage`: `"en"` (correct)
- Organization `@id`: `https://.../#organization` (uses EN base URL)
- Organization logo uses EN path (no `/no/` prefix)

**@id references:** All resolve ✅
**Issues:** Same as NO — missing `contactPoint`, empty `sameAs`, no `SearchAction`.

### Product Page (`/no/produkter/flake-system/` → 301 → `/no/produkter/flake-system-gulv/`)

**Types:** WebPage, BreadcrumbList, Product

```json
{
  "@type": "Product",
  "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/produkter/flake-system-gulv/#product",
  "name": "Flake System – Gulv",
  "brand": { "@type": "Brand", "name": "AcryliCon" },
  "manufacturer": { "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#organization" },
  "description": "Dekorativ overflate Sklisikring i ønsket gradering og metning For tung fottrafikk og moderat industri 2-3 mm tykkelse",
  "image": "https://acryli-28355.jana-osl.servebolt.cloud/no/wp-content/uploads/sites/3/2021/09/image-8-1024x527.jpg"
}
```

**@id references:** 2 DANGLING refs ⚠️
- `https://acryli-28355.jana-osl.servebolt.cloud/no/#website` — referenced by WebPage `isPartOf` but not defined on this page
- `https://acryli-28355.jana-osl.servebolt.cloud/no/#organization` — referenced by Product `manufacturer` but not defined on this page

**Issues:** Product missing `offers` (required for rich results), `sku`, `category`. Description appears to be ACF excerpt (line-break separated bullet points, not a proper sentence).

### EN Product Page (`/products/flake-system/` → 301 → `/products/flake-system-floor/`)

Same structure as NO product, but **description is in Norwegian** on the English page:
```
"description": "Dekorativ overflate Sklisikring i ønsket gradering og metning..."
```
This is a multisite-sync issue — the Product description is copied without translation.

### Reference Single (`/no/referanser/rehabilitering-av-norges-idrettshogskole/`)

**Types:** WebPage, BreadcrumbList, Article

```json
{
  "@type": "Article",
  "@id": ".../#article",
  "headline": "Rehabilitering av Norges Idrettshøgskole",
  "datePublished": "2025-02-16T21:04:53+01:00",
  "dateModified": "2025-03-10T21:06:51+01:00",
  "publisher": { "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#organization" },
  "isPartOf": { "@id": ".../#webpage" },
  "image": "https://acryli-28355.jana-osl.servebolt.cloud/no/wp-content/uploads/sites/3/2025/02/image2.jpg"
}
```

**@id references:** 2 DANGLING refs ⚠️ (same as product — `#website`, `#organization` not on page)
**Issues:** Article missing `author`. Dates are valid ISO 8601 ✅.

### Reference Archive (`/no/referanser/`)

**Types:** WebPage, BreadcrumbList

**@id references:** 1 DANGLING ref — `#website`
**Issues:** Uses generic `WebPage` type, should be `CollectionPage` for archive pages. No `CollectionPage` properties like `hasPart` or `mainEntity`.

### Office Archive (`/no/kontor/`)

**Types:** WebPage, BreadcrumbList

**@id references:** 1 DANGLING ref — `#website`
**Issues:** Same as reference archive — should be `CollectionPage`. No listing schema for child office pages.

### Office Single Pages (3 tested: Sande, Rogaland, Midt/Vest)

**Types:** WebPage, BreadcrumbList, ProfessionalService

```json
{
  "@type": "ProfessionalService",
  "name": "Acrylicon Industrigulv AS, Sande",
  "parentOrganization": { "@id": ".../#organization" },
  "address": "Prestegårdsjordet 1",
  "telephone": "+47 33 78 50 00",
  "geo": { "@type": "GeoCoordinates", "latitude": 59.5918742, "longitude": 10.211192100000062 },
  "image": "...haslumskole-1024x526.jpg"
}
```

**@id references:** 2 DANGLING refs ⚠️ (`#website`, `#organization`)
**Telephone:** All use `+47` prefix ✅
**Geo:** All have lat/lng ✅
**Issues:** `address` is a **plain string** instead of `PostalAddress` object — Google can't extract city, postal code, country. Missing `openingHours` and `areaServed`.

## Image URL Verification

| Image URL | HTTP Status |
|-----------|-------------|
| NO Logo (`acrylicon-logo-dark.png`) | 200 ✅ |
| EN Logo (`acrylicon-logo-dark.png`) | 200 ✅ |
| OG Default (`acrylicon-og-default.jpg`) | 200 ✅ |
| Product Image (`image-8-1024x527.jpg`) | 200 ✅ |
| Article Image (`image2.jpg`) | 200 ✅ |
| Office Image Sande (`haslumskole-1024x526.jpg`) | 200 ✅ |
| Office Image Rogaland (`ovrige.jpg`) | 200 ✅ |

All schema-referenced image URLs return HTTP 200. ✅

## @id Cross-Reference Summary

The custom SEO module uses a **split graph strategy**: Organization + WebSite nodes are only output on homepage, while inner pages reference them via `@id`. This is technically valid per JSON-LD spec (Google resolves `@id` across the domain), but creates dangling references on every inner page:

- **Homepage:** 0 dangling refs ✅
- **Product page:** 2 dangling refs (`#organization`, `#website`)
- **Reference single:** 2 dangling refs (`#organization`, `#website`)
- **Reference archive:** 1 dangling ref (`#website`)
- **Office archive:** 1 dangling ref (`#website`)
- **Office single:** 2 dangling refs (`#organization`, `#website`)

**Verdict:** Google generally handles this pattern, but including the Organization and WebSite nodes on every page (like Yoast does) would be more robust and make each page self-contained.

## Issues Found

### Issue 1: Organization missing `contactPoint` — both homepages
- **Page:** `/no/` and `/`
- **Problem:** Organization schema has no `contactPoint` property. This is the primary way Google's Knowledge Panel displays phone/email for businesses.
- **Impact:** High — Cannot trigger the "Call" button or display contact info in Google Knowledge Panel. Competitors with `contactPoint` will be preferred.
- **Fix:** Add `contactPoint` to Organization node in the SEO module:
  ```json
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+47 62 34 21 00",
    "contactType": "customer service",
    "availableLanguage": ["Norwegian", "English"]
  }
  ```
- **Priority:** High

### Issue 2: Organization `sameAs` is empty array
- **Page:** `/no/` and `/`
- **Problem:** `"sameAs": []` — no social profiles linked. This is how Google populates social links in the Knowledge Panel.
- **Impact:** Medium — Missing social links in Knowledge Panel. Less entity disambiguation for Google.
- **Fix:** Populate with company social profiles:
  ```json
  "sameAs": [
    "https://www.linkedin.com/company/acrylicon/",
    "https://www.facebook.com/acrylicon/"
  ]
  ```
- **Priority:** Medium

### Issue 3: WebSite missing `potentialAction` (SearchAction)
- **Page:** `/no/` and `/`
- **Problem:** No `SearchAction` defined on WebSite node. This enables the sitelinks search box in Google SERPs.
- **Impact:** Low-Medium — Won't get sitelinks search box. Less useful for branded searches.
- **Fix:** Add `potentialAction` if site has internal search:
  ```json
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://acryli-28355.jana-osl.servebolt.cloud/no/?s={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
  ```
- **Priority:** Low (site may not have meaningful search functionality)

### Issue 4: Product schema missing `offers` — no rich result eligibility
- **Page:** `/no/produkter/flake-system/` (and all product pages)
- **Problem:** Product schema has `name`, `brand`, `description`, `image` but no `offers`. Google requires `offers` (with `price` or `priceCurrency`) for Product rich results.
- **Impact:** High — Product pages will NEVER appear as rich results in Google without `offers`. Even if these are B2B products without public pricing, an alternative approach is needed (see fix).
- **Fix:** Since these are B2B products without fixed prices, either:
  1. Remove Product schema entirely and use only WebPage (avoids invalid Product markup)
  2. Or add minimal offers with a request-a-quote pattern (not recommended by Google)
  Best approach: Keep Product schema for entity recognition but don't expect rich results.
- **Priority:** Medium (Product schema still helps entity understanding even without rich results)

### Issue 5: EN Product description in Norwegian
- **Page:** `/products/flake-system/`
- **Problem:** Product `description` is `"Dekorativ overflate Sklisikring i ønsket gradering..."` — Norwegian text on English page. This is a multisite-sync issue where ACF content is copied without translation.
- **Impact:** Medium — Language mismatch confuses Google's language detection. May hurt EN product rankings.
- **Fix:** Translation workflow for synced product descriptions, or suppress Product schema on EN until translations exist.
- **Priority:** Medium

### Issue 6: Article schema missing `author`
- **Page:** `/no/referanser/rehabilitering-av-norges-idrettshogskole/` (all reference single pages)
- **Problem:** Article has `publisher` but no `author`. Google recommends `author` for Article rich results.
- **Impact:** Medium — Missing author weakens Article schema validity. Google uses author for E-E-A-T signals.
- **Fix:** Add `author` referencing the Organization (since these are company case studies, not personal articles):
  ```json
  "author": { "@id": "https://acryli-28355.jana-osl.servebolt.cloud/no/#organization" }
  ```
- **Priority:** Medium

### Issue 7: ProfessionalService `address` is plain string, not PostalAddress
- **Page:** All office single pages (Sande, Rogaland, Midt/Vest, etc.)
- **Problem:** `"address": "Prestegårdsjordet 1"` is a string. Google needs a `PostalAddress` object with `streetAddress`, `addressLocality`, `postalCode`, `addressCountry` to extract structured location data.
- **Impact:** High — Google can't parse address components, reducing eligibility for local pack / Google Maps rich results.
- **Fix:** Convert address string to PostalAddress object:
  ```json
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Prestegårdsjordet 1",
    "addressLocality": "Sande",
    "postalCode": "3070",
    "addressCountry": "NO"
  }
  ```
  The ACF fields likely have separate city/postal code values — wire them into the schema.
- **Priority:** High

### Issue 8: ProfessionalService missing `openingHours` and `areaServed`
- **Page:** All office single pages
- **Problem:** No opening hours or service area defined. These are common fields Google uses for local business cards.
- **Impact:** Low — Nice to have for local SEO but not required for basic functionality.
- **Fix:** Add `openingHours` (e.g., `"Mo-Fr 08:00-16:00"`) and `areaServed` if data is available in ACF.
- **Priority:** Low

### Issue 9: Archive pages use `WebPage` instead of `CollectionPage`
- **Page:** `/no/referanser/`, `/no/kontor/`, `/references/`, `/locations/`
- **Problem:** Archive/listing pages use generic `WebPage` type instead of the more specific `CollectionPage` (a subtype of WebPage). No `mainEntity` or `hasPart` linking to child items.
- **Impact:** Low — Google understands archive pages from HTML structure, but `CollectionPage` improves semantic clarity.
- **Fix:** Change `@type` to `CollectionPage` for archive page templates. Optionally add `mainEntity` with `ItemList` of child pages.
- **Priority:** Low

### Issue 10: Dangling @id references on all inner pages
- **Page:** All pages except homepages
- **Problem:** Inner pages reference `#organization` and `#website` via `@id` but don't include those nodes. These only exist on the homepage graph. While Google generally handles cross-page @id resolution, it's less reliable than self-contained graphs.
- **Impact:** Low — Google typically resolves this, but self-contained graphs are more robust.
- **Fix:** Include Organization and WebSite nodes on every page (like Yoast does) so each page's graph is fully self-contained.
- **Priority:** Low (Google handles this, but worth improving)

## Passed Checks

- **JSON-LD syntax:** All pages output syntactically valid JSON-LD ✅
- **@context:** All use `https://schema.org` ✅
- **@graph pattern:** Consistent use of single @graph array across all pages ✅
- **Organization `name`, `legalName`, `foundingDate`:** Present and correct ✅
- **Organization `logo`:** Proper ImageObject with valid URL (HTTP 200), width, height ✅
- **Organization `address` on homepage:** Proper PostalAddress object ✅
- **WebSite `inLanguage`:** `"nb-NO"` on NO, `"en"` on EN — correct ✅
- **WebSite `publisher`:** References Organization ✅
- **WebPage dates:** All have `datePublished` and `dateModified` in ISO 8601 ✅
- **BreadcrumbList:** Present on all pages, correct ListItem structure, last item correctly omits `item` URL ✅
- **Product `name`, `brand`, `manufacturer`, `image`:** All present ✅
- **Article `headline`, `datePublished`, `dateModified`, `publisher`, `image`:** All present ✅
- **ProfessionalService `name`, `parentOrganization`, `telephone`, `geo`:** All present ✅
- **Telephone format:** All use `+47` prefix ✅
- **GeoCoordinates:** All have lat/lng ✅
- **All schema image URLs:** Return HTTP 200 ✅
- **No duplicate JSON-LD blocks:** Only 1 block per page (custom module, no Yoast conflict) ✅
