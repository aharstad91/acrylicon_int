---
topic: Factory page + Locations/Contact page
date: 2026-02-11
status: decided
---

# Factory Page + Locations/Contact Page

## What We're Building

Two new pages on the international English site (blog 1) to replace the external acryliconpolymers.com Wix site:

### 1. Factory Page (`/factory/` or `/production/`)
A standard WordPress page with ACF blocks showcasing AcryliCon Polymers GmbH — the German production facility.

**Content scope (mellomting):**
- Hero section with factory/production imagery
- "Made in Germany" — production facility intro, resin producer since 2014
- Photovoltaic/sustainability section (765 kWp system, 528 tonnes CO2 reduction)
- Key figures (capacity, established 1977, 18 locations worldwide)
- CTA to contact/locations page

**Not included (for now):**
- Full company history (already exists on "Om Acrylicon"/"About Acrylicon")
- Detailed certifications page (already exists as separate page)
- Staff/team section

### 2. Locations/Contact Page (replaces existing `/contact-us/`)
Combined worldwide locations + contact form page.

**Content scope:**
- Intro text: "Licensed distributors and trained contractors worldwide"
- Country-by-country listing with flag icons + contact details
- Norwegian offices pulled dynamically from Kontor CPT (5 offices)
- International offices hardcoded in ACF repeater field or blocks
- Contact form (SuperOffice CRM embed or similar, check existing)
- No interactive map — clean flag + list design, better than Wix version

**18 countries to include:**
Australia, Canada, Denmark, Egypt, Finland, Faroe Islands/Greenland, Germany (2 offices), Ireland, Jamaica (2 offices), Central Asia (Kazakhstan + 5 countries), Lithuania, Middle East/UAE, Norway (5 offices from CPT), South Korea, United Kingdom (5 offices), USA (2 offices)

## Why This Approach

1. **Standard page with ACF blocks** — follows "Om Acrylicon" pattern, editable in WP admin, no custom template needed
2. **Hardcoded international offices** — only 5 Norwegian offices are CPT posts. Adding ~25 international offices as CPT would bloat the WordPress admin. ACF repeater or manual blocks is simpler to maintain.
3. **English only** — these pages serve the international audience. Norwegian visitors already have full content on /no/
4. **Replace contact-us** — combining locations + contact makes one strong page instead of two thin ones
5. **Flags + list, no map** — simpler, faster, no JS dependency, easier to maintain, better mobile experience

## Key Decisions

- [x] Factory = standard WP page with ACF blocks
- [x] Locations = hardcoded international + CPT Norwegian offices
- [x] English (blog 1) only — no Norwegian version needed
- [x] Locations replaces existing contact-us page
- [x] Factory scope: production + sustainability + key figures + CTA
- [x] Locations design: flag icons + country headings + office cards, no map
- [x] Content sourced from acryliconpolymers.com (Wix site being replaced)

## Open Questions

- Exact URL slug for factory page: `/factory/` vs `/production/` vs `/polymers/`
- What happens to the existing contact-us page content (form, etc)?
- Do we need flag SVGs for all 18 countries, or use emoji flags?
- Should Norwegian offices on locations page link to their individual kontor CPT pages?

## Existing Patterns to Follow

- **Page template**: `page.php` with `pt-20 lg:pt-44` padding
- **Block composition**: Same as "Om Acrylicon" page (hero → text → split-image-text-banner → cards → CTA)
- **Relevant blocks**: `office-contact-card`, `split-image-text-banner`, `image-split`, `beige-card-variant-three`, `info-card`
- **Colors**: acryl-red (#E2241C), acryl-dark-blue (#253761), acryl-beige-lightest (#F9F9F5)
- **Typography**: Soehne Buch (body), Soehne Mono (labels)

## Source Content

All content from https://www.acryliconpolymers.com/ — analyzed 2026-02-11:
- Homepage: slideshow, product highlights, key figures, testimonial
- /locations: 18 countries, ~30 offices with full contact details
- /our-expertise: production facility info, "Made in Germany"
- /accreditations: DIN EN ISO 9001/14001, M1, MED, Food Safety, EPD, RIBA
- /product-range: 6 product categories
- /market-sectors: 12 industry sectors
