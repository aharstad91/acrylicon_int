# Mobile UX and PageSpeed Visual Verification

**Date:** 2026-02-27
**Status:** FAIL
**Page(s) tested:**
- `https://acryli-28355.jana-osl.servebolt.cloud/no/` (NO homepage)
- `https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/` (reference archive)
- `https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/` (office listing)
- `https://acryli-28355.jana-osl.servebolt.cloud/no/referanser/rehabilitering-av-norges-idrettshogskole/` (single reference)
- `https://acryli-28355.jana-osl.servebolt.cloud/locations/` (EN locations/worldwide)
- `https://acryli-28355.jana-osl.servebolt.cloud/no/kontakt-oss/` (NO locations — 404)
- `https://acryli-28355.jana-osl.servebolt.cloud/no/kontor/acrylicon-industrigulv-as-sande/` (single office)

**Emulation:** Mobile 390x844, deviceScaleFactor 2, isMobile true, hasTouch true, CPU 4x, Fast 4G

## Summary

Visual layout is generally solid across all pages — text is readable, images scale correctly, and the hamburger navigation works well. However, the audit uncovered 9 distinct issues: a critical horizontal overflow on the reference archive from an overflowing filter dropdown, missing h1 elements on 2 archive pages, broken email addresses (`@localhost`) on all single office pages, an unlabelled hamburger button, English-language category labels on the Norwegian site, stale internal links, and a missing Norwegian equivalent of the EN `/locations/` page.

## Measurements

| Page | Horizontal Overflow | h1 Present | Console Errors | Phone tel: Links |
|------|-------------------|------------|----------------|------------------|
| /no/ | No (390/390) | Yes | 0 | 0 |
| /no/referanser/ | **Yes (433/390 = 43px)** | **No** | 0 | 0 |
| /no/kontor/ | No (390/390) | **No** | 0 | 0 |
| /no/referanser/rehabilitering-av-norges-idrettshogskole/ | No (390/390) | Yes | 0 | 0 |
| /locations/ | No (390/390) | Yes | 1 (Swiper) | 28 |
| /no/kontor/acrylicon-industrigulv-as-sande/ | No (390/390) | Yes | 0 | 10 |

## Issues Found

### Issue 1: Horizontal overflow on /no/referanser/ — filter dropdown
- **Page:** `/no/referanser/`
- **Problem:** The third filter dropdown (`.filter-group` containing a `<select>` element) renders at 413px width, causing 43px horizontal overflow on a 390px mobile viewport. The overflowing element is a `<select>` with class `filter-select border border-solid border-acryl-neutral-1 rounded-lg px-4 py-2...`. This means users can accidentally scroll horizontally, which breaks the mobile experience and directly impacts PageSpeed's "Content is wider than screen" diagnostic.
- **Impact:** High — horizontal scroll on mobile directly hurts PageSpeed mobile score and UX. Google flags this as a mobile usability error in Search Console.
- **Fix:** Add `max-width: 100%` or `w-full` to the `.filter-group` and `<select>` elements. Also ensure the parent filter container uses `overflow-x: hidden` or proper flex/grid wrapping. The filter layout likely needs `flex-wrap: wrap` or stacking on mobile.
- **Priority:** High
- **Screenshot:** `scripts/ralph/tests/screenshots/no-referanser-mobile.png`

### Issue 2: Missing h1 on /no/referanser/ archive page
- **Page:** `/no/referanser/`
- **Problem:** Page has zero `<h1>` elements. The page title "Referanser" is rendered as `<h2>`. The heading hierarchy is h2 → h3 (reference titles). This violates HTML semantics and SEO best practice — every page must have exactly one h1.
- **Impact:** Medium — missing h1 weakens page's primary keyword signal for Google. Accessibility tools will flag this as an error.
- **Fix:** Change the "Referanser" heading from `<h2>` to `<h1>` in the archive template (`archive-referanser.php` or equivalent).
- **Priority:** Medium

### Issue 3: Missing h1 on /no/kontor/ listing page
- **Page:** `/no/kontor/`
- **Problem:** Same as Issue 2 — zero `<h1>` elements. "Kontorer" is rendered as `<h2>`. Heading hierarchy is h2 → h3 (office names).
- **Impact:** Medium — same SEO/accessibility impact as Issue 2.
- **Fix:** Change "Kontorer" heading from `<h2>` to `<h1>` in the office archive template.
- **Priority:** Medium

### Issue 4: CRITICAL — Broken email addresses on single office pages (`@localhost`)
- **Page:** `/no/kontor/acrylicon-industrigulv-as-sande/` (and likely all single office pages)
- **Problem:** All 9 employee email links point to `@localhost` domains (e.g., `mailto:ho@localhost`, `mailto:hmf@localhost`, `mailto:std@localhost`). These are clearly development/placeholder values that were never updated for production. Clicking any email link on mobile will fail or open a mail client with an invalid address.
- **Impact:** High — customers cannot email staff from office pages. This is a conversion-killing bug on what should be the primary contact pathway.
- **Fix:** Update all employee email ACF fields in WordPress admin with correct `@acrylicon.no` addresses for each office. Verify across all 4 office pages.
- **Priority:** High

### Issue 5: Hamburger menu button has no accessible label
- **Page:** All pages (global header)
- **Problem:** The mobile hamburger menu `<button>` element has no accessible name — the a11y tree shows it only as `button expandable` with no text, aria-label, or sr-only text. Screen reader users cannot identify the purpose of this button.
- **Impact:** Medium — accessibility violation (WCAG 2.1 SC 4.1.2 Name, Role, Value). Will be flagged by Lighthouse accessibility audit.
- **Fix:** Add `aria-label="Meny"` (or `"Menu"` on EN) to the hamburger button element in `header.php`.
- **Priority:** Medium

### Issue 6: English category labels on Norwegian pages
- **Page:** `/no/` (homepage), `/no/referanser/` (archive)
- **Problem:** Reference category labels display in English on Norwegian pages: "Schools and public buildings", "Fish processing industry", "Hospitals and veterinary clinics", "Hotels, restaurants and commercial kitchens", etc. The filter dropdown labels ("Industri", "Produktsystem", "Kontor") are correctly Norwegian, but the option values within the Industri dropdown are English.
- **Impact:** Medium — mixed-language content confuses Norwegian users and sends mixed language signals to Google, potentially harming SEO for Norwegian queries.
- **Fix:** The taxonomy terms (`referanser-type` / industry categories) need Norwegian translations on Blog 3. Either: (a) translate the shared taxonomy terms per-blog, or (b) use the multisite-sync system to maintain separate Norwegian term names.
- **Priority:** Medium

### Issue 7: Stale `/norway/karriere/` link on homepage
- **Page:** `/no/` (homepage)
- **Problem:** The "Se ledige muligheter" (career) CTA link points to `https://acryli-28355.jana-osl.servebolt.cloud/norway/karriere/` — using the old `/norway/` path prefix instead of `/no/`. This likely 301-redirects but adds latency and looks unprofessional if inspected.
- **Impact:** Low — functional due to redirect, but adds unnecessary redirect hop.
- **Fix:** Update the ACF field or hardcoded link in the karriere/recruitment block to use `/no/karriere/`.
- **Priority:** Low

### Issue 8: /no/kontakt-oss/ is 404 — no Norwegian equivalent of /locations/
- **Page:** `/no/kontakt-oss/`
- **Problem:** The Norwegian equivalent of the EN `/locations/` page does not exist. `/no/kontakt-oss/` returns a 404. The EN locations page is a comprehensive worldwide offices directory with 28 phone numbers as clickable `tel:` links, addresses, and emails. The Norwegian site only has `/no/kontor/` which lists 4 Norwegian offices with descriptions but NO phone numbers or contact details — just "Kontakt oss" buttons that link to individual office pages.
- **Impact:** Medium — Norwegian users cannot see international offices or quickly find a phone number from the office listing. The EN page is vastly more useful than the NO equivalent.
- **Fix:** Either (a) add a 301 redirect from `/no/kontakt-oss/` to `/no/kontor/` and enhance `/no/kontor/` with phone numbers and addresses inline, or (b) create a NO equivalent of the full `/locations/` page.
- **Priority:** Medium

### Issue 9: Jumbled heading hierarchy on single office pages
- **Page:** `/no/kontor/acrylicon-industrigulv-as-sande/`
- **Problem:** Heading levels are out of order: h1 → h2 → **h5** ("Kontakt oss") → **h4** ("Våre ansatte") → h3 (employee names) → h4 ("Miljøfyrtårn") → h3 (certification text). The h5 appears before h4, and h3 appears after h4 — violating the hierarchical nesting requirement.
- **Impact:** Low — accessibility tools will flag this. Screen reader users navigating by headings will experience a confusing structure.
- **Fix:** Restructure headings: "Kontakt oss" should be h2, "Våre ansatte" should be h2, employee names h3, "Miljøfyrtårn" should be h2 or h3.
- **Priority:** Low

## Passed Checks

### Mobile navigation
- Hamburger menu opens/closes correctly on tap
- Navigation reveals 8 main links + language switcher
- Links are properly spaced for touch targets (large text, generous padding)
- Close button (X) is visible and functional
- **Screenshot:** `scripts/ralph/tests/screenshots/no-homepage-mobile-nav-open.png`

### Layout and readability
- All 5 main pages render correctly at 390px width (except referanser overflow)
- Text is readable without zooming on all pages
- Images scale proportionally, no broken aspect ratios
- CTA buttons are full-width and easily tappable on mobile
- Card layouts stack properly in single column on mobile

### Phone number links (where present)
- EN `/locations/` has 28 properly formatted `<a href="tel:+XX...">` links — all clickable
- Single office pages have proper `tel:` links for all staff (10 on Sande office)
- Phone numbers include country code (+47, +61, +1, etc.)

### 404 page
- `/no/kontakt-oss/` properly returns a styled 404 page with Norwegian message ("Siden ble ikke funnet") and CTA to homepage
- **Screenshot:** `scripts/ralph/tests/screenshots/no-kontakt-oss-404-mobile.png`

### Console errors
- No JavaScript errors on any Norwegian page tested
- EN `/locations/` has one non-critical error: "Swiper is not defined" (known issue from Story 2 — Swiper init runs on pages without the library loaded)

### No horizontal overflow (4 of 5 pages)
- Homepage: 390/390 — clean
- Kontor: 390/390 — clean
- Single reference: 390/390 — clean
- EN Locations: 390/390 — clean

## Screenshots

| Page | Screenshot |
|------|-----------|
| NO Homepage (full page) | `scripts/ralph/tests/screenshots/no-homepage-mobile.png` |
| NO Homepage (nav open) | `scripts/ralph/tests/screenshots/no-homepage-mobile-nav-open.png` |
| Reference archive | `scripts/ralph/tests/screenshots/no-referanser-mobile.png` |
| Office listing | `scripts/ralph/tests/screenshots/no-kontor-mobile.png` |
| Single reference | `scripts/ralph/tests/screenshots/no-referanse-single-mobile.png` |
| Single office | `scripts/ralph/tests/screenshots/no-kontor-single-mobile.png` |
| EN Locations | `scripts/ralph/tests/screenshots/en-locations-mobile.png` |
| NO Kontakt-oss 404 | `scripts/ralph/tests/screenshots/no-kontakt-oss-404-mobile.png` |
