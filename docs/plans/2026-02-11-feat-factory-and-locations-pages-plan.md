---
title: "feat: Factory page + Locations/Contact page"
type: feat
date: 2026-02-11
---

# Factory Page + Locations/Contact Page

## Overview

Build two new pages on the international English site (blog 1) using content from the existing acryliconpolymers.com Wix site:

1. **Factory page** (`/factory/`) — AcryliCon Polymers GmbH production facility
2. **Locations page** (replaces `/contact-us/`) — All worldwide offices with contact details

Both pages use custom page templates with the existing Tailwind design system. Content is hardcoded in PHP (not ACF blocks) for version control and developer-friendly maintenance.

## Proposed Solution

### Architecture Decision: Custom Page Templates vs ACF Blocks

**Chosen: Custom page templates** (`page-factory.php`, `page-locations.php`)

Why:
- Version-controlled in Git (ACF block content lives in database)
- Faster to build and iterate
- Office data as PHP arrays = easy to maintain, grep-able, diff-able
- Same visual output using existing Tailwind classes
- Can be migrated to ACF blocks later if editor flexibility is needed

### Factory Page (`/factory/`)

**Template:** `page-factory.php`

**Sections:**
1. **Hero** — Full-width image of production facility + overlay text
2. **Introduction** — "Made in Germany" production story, resin producer since 2014
3. **Sustainability** — Photovoltaic system (765 kWp, 528t CO2/year, 300+ households equivalent)
4. **Key Figures** — 3-column grid: 1977 (established), 1000's (clients), 18 (locations)
5. **CTA** — "Contact us" button linking to /locations/

**Content source:** acryliconpolymers.com homepage + /our-expertise

**Images needed:**
- Factory exterior (from Wix slideshow)
- Production/interior shot
- Solcelle/photovoltaic image

### Locations Page (replaces `/contact-us/`)

**Template:** `page-locations.php`

**Sections:**
1. **Hero/intro** — "Worldwide Locations" heading + intro text
2. **Country listing** — Alphabetical by country, each with:
   - Country flag SVG (20x14px, matching language-switcher style)
   - Country name heading
   - Office cards (company name, address, phone, email, website)
3. **Norwegian offices** — Dynamically pulled from Kontor CPT via `WP_Query`
4. **Contact form** — SuperOffice CRM embed or simple form at bottom

**Office data structure (separate include file `inc/international-offices.php`):**
```php
// inc/international-offices.php — returns the data array
return [
    'australia' => [
        'country' => 'Australia',
        'flag' => 'au',
        'offices' => [
            [
                'name' => 'AcryliCon Australia',
                'company' => 'Andersens Floor Coverings PTY Ltd',
                'address' => ['29 Western Drive', 'Gatton QLD 4343'],
                'phone' => '+61 1800 016 016',
                'email' => 'enquires@acrylicon.com.au',
                'web' => 'www.acrylicon.com.au',
            ],
        ],
    ],
    // ... 17 more countries
];

// In page-locations.php:
$international_offices = require get_template_directory() . '/inc/international-offices.php';
```

**Norwegian offices (dynamic from Kontor CPT on blog 3):**

> **Tech audit finding:** Kontor CPT posts live on blog 3 (Norwegian site), not blog 1.
> Must use `switch_to_blog(3)` to query them from the English site.

```php
switch_to_blog( 3 );
$args = [
    'post_type'      => 'kontor',
    'posts_per_page' => 50,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
];
$offices = new WP_Query( $args );
// ... render loop ...
restore_current_blog();
```

**18 countries, ~30 offices total:**

| Country | Offices | Source |
|---------|---------|--------|
| Australia | 1 | Hardcoded |
| Canada | 2 (HQ + West) | Hardcoded |
| Central Asia | 1 (covers 6 countries) | Hardcoded |
| Denmark | 1 | Hardcoded |
| Egypt | 1 | Hardcoded |
| Faroe Islands/Greenland | 1 | Hardcoded |
| Finland | 1 | Hardcoded |
| Germany | 2 (Services + Süd) | Hardcoded |
| Ireland | 1 | Hardcoded |
| Jamaica | 2 | Hardcoded |
| Lithuania | 1 | Hardcoded |
| Middle East/UAE | 1 | Hardcoded |
| **Norway** | **5** | **Dynamic from Kontor CPT** |
| South Korea | 1 | Hardcoded |
| United Kingdom | 5 (HQ + 4 regional) | Hardcoded |
| USA | 2 (HQ + NE/MW) | Hardcoded |

### Flag SVG Assets

Need flag SVGs for all countries. Already have `no.svg` and `gb.svg` from language switcher.

**New flags needed (14):**
`au.svg`, `ca.svg`, `dk.svg`, `eg.svg`, `fi.svg`, `fo.svg` (Faroe), `de.svg`, `ie.svg`, `jm.svg`, `kz.svg` (Central Asia), `lt.svg`, `ae.svg` (UAE), `kr.svg`, `us.svg`

**Location:** `assets/gfx/flags/` (same as existing flags)

**Source:** Use simple, clean SVG flags from open-source flag collections (lipis/flag-icons or similar).

**Requirements (from tech audit):**
- Match existing format: `viewBox="0 0 20 14"`, `fill="none"`, `rx="1"` base rectangle
- Run SVGO optimization before committing: `npx svgo --multipass assets/gfx/flags/*.svg`
- Manually audit each SVG for `<script>`, `<foreignObject>`, `on*` attributes before commit

### Navigation Updates

1. Add "Factory" to primary menu (or under About Us)
2. Replace "Contact Us" menu link with `/locations/`
3. Update footer menus if needed

### WordPress Page Setup

```bash
# Create Factory page (blog 1 — use --url for multisite)
PATH="/Applications/MAMP/Library/bin/mysql80/bin:/opt/homebrew/bin:/usr/bin:/usr/local/bin:$PATH" \
  php /usr/local/bin/wp --skip-plugins=wp-fastest-cache \
  post create --post_type=page --post_title='Factory' --post_name='factory' \
  --post_status=publish --url=http://localhost:8888/acrylicon/

# Create Locations page (blog 1, replaces contact-us)
PATH="/Applications/MAMP/Library/bin/mysql80/bin:/opt/homebrew/bin:/usr/bin:/usr/local/bin:$PATH" \
  php /usr/local/bin/wp --skip-plugins=wp-fastest-cache \
  post create --post_type=page --post_title='Locations' --post_name='locations' \
  --post_status=publish --url=http://localhost:8888/acrylicon/

# Trash old contact-us page to avoid duplicate content in sitemaps
PATH="/Applications/MAMP/Library/bin/mysql80/bin:/opt/homebrew/bin:/usr/bin:/usr/local/bin:$PATH" \
  php /usr/local/bin/wp --skip-plugins=wp-fastest-cache \
  post list --post_type=page --name=contact-us --field=ID --url=http://localhost:8888/acrylicon/
# Then: wp post update <ID> --post_status=trash

# 301 redirect: add to existing $redirects array in functions.php
# (uses existing acrylicon_redirect_old_norwegian_slugs() pattern)
```

### Language Switcher Updates

> **Tech audit fix:** Slug map convention is NO→EN. Update the *existing* mapping, don't add a new one.

Update existing entry in `inc/language-switcher.php` `acrylicon_slug_map()`:

```php
'pages' => [
    // ... existing mappings
    'kontakt-oss' => 'locations',  // UPDATED from 'contact-us' to 'locations'
    // No entry needed for 'factory' — slug mapper returns original slug when no mapping exists
],
```

**Factory page behavior:** English-only. When Norwegian user clicks language switcher on `/factory/`, they'll land on Norwegian front page (existing fallback behavior in `acrylicon_get_equivalent_url()`).

## Technical Considerations

- **No new ACF fields needed** — all content is in PHP templates
- **No new block registration** — using page templates, not blocks
- **Kontor CPT query** — uses `switch_to_blog(3)` since posts live on Norwegian site
- **Flag SVGs** — small file size (~1-3KB each), inline via `svg_icon()` helper, SVGO-optimized
- **Mobile responsive** — follows existing Tailwind patterns (1-col mobile, 2-3 col desktop)
- **301 redirect** — uses existing `$redirects` array in `acrylicon_redirect_old_norwegian_slugs()`
- **Email protection** — use `antispambot()` on all email output to prevent harvesting
- **Output escaping** — `esc_html()`, `esc_attr()`, `esc_url()` on all template output
- **Hero images** — use `wp_get_attachment_image()` with `loading="eager"` + `fetchpriority="high"` for LCP
- **SVG sanitization** — strengthen `svg_icon()` to strip `on*` attrs, `<foreignObject>`, `<use>` elements

## Acceptance Criteria

### Factory Page
- [ ] Page accessible at `/factory/`
- [ ] Hero section with production facility image
- [ ] "Made in Germany" production intro section
- [ ] Sustainability section with photovoltaic stats
- [ ] Key figures section (1977, 1000's clients, 18 locations)
- [ ] CTA button to /locations/
- [ ] Mobile responsive layout
- [ ] Matches existing site design (Tailwind classes, colors, typography)

### Locations Page
- [ ] Page accessible at `/locations/`
- [ ] "Worldwide Locations" heading + intro text
- [ ] All 18 countries listed alphabetically with flag icons
- [ ] ~30 offices with name, address, phone, email, website
- [ ] Norwegian offices pulled dynamically from Kontor CPT
- [ ] Contact section at bottom
- [ ] Mobile responsive (cards stack on mobile)
- [ ] 301 redirect from `/contact-us/` to `/locations/`

### Navigation & Integration
- [ ] Primary menu updated with Factory link
- [ ] Contact Us menu item updated to point to /locations/
- [ ] Language switcher slug mapping added
- [ ] Flag SVGs for all 18 countries in assets/gfx/flags/

## Implementation Steps

### Step 1: Flag SVG Assets
- Download/create 14 new flag SVGs (already have `no.svg`, `gb.svg`)
- Place in `assets/gfx/flags/`
- Verify `svg_icon()` helper works with new flags

### Step 2: Factory Page Template
- Create `page-factory.php` with hardcoded content sections
- Use existing Tailwind classes matching site design
- Placeholder images initially (swap with real factory photos later)

### Step 3: Locations Page Template
- Create `page-locations.php` with office data array
- Render country sections with flags + office cards
- Add Kontor CPT query for Norwegian offices
- Add contact section

### Step 4: WordPress Setup
- Create both pages via WP-CLI
- Set page templates
- Set up 301 redirect from /contact-us/ to /locations/

### Step 5: Navigation & Language Switcher
- Update primary menu
- Add slug mappings to language-switcher.php
- Update footer if needed

### Step 6: Tailwind CSS Build
- Run `npm run build:css` to compile any new classes
- Verify responsive layout at all breakpoints

## Files to Create/Modify

### New Files
- `page-factory.php` — Factory page template
- `page-locations.php` — Locations page template
- `assets/gfx/flags/au.svg` — Australian flag
- `assets/gfx/flags/ca.svg` — Canadian flag
- `assets/gfx/flags/dk.svg` — Danish flag
- `assets/gfx/flags/eg.svg` — Egyptian flag
- `assets/gfx/flags/fi.svg` — Finnish flag
- `assets/gfx/flags/fo.svg` — Faroese flag
- `assets/gfx/flags/de.svg` — German flag
- `assets/gfx/flags/ie.svg` — Irish flag
- `assets/gfx/flags/jm.svg` — Jamaican flag
- `assets/gfx/flags/kz.svg` — Kazakh flag
- `assets/gfx/flags/lt.svg` — Lithuanian flag
- `assets/gfx/flags/ae.svg` — UAE flag
- `assets/gfx/flags/kr.svg` — South Korean flag
- `assets/gfx/flags/us.svg` — US flag

### Modified Files
- `inc/language-switcher.php` — Add slug mappings
- `functions.php` — Add 301 redirect for /contact-us/

## References

- Brainstorm: `docs/brainstorms/2026-02-11-factory-and-locations-pages-brainstorm.md`
- Existing pattern: "Om Acrylicon" page (page ID 86)
- Existing Kontor CPT: `functions.php` lines 168-172
- Existing blocks: `blocks/office-contact-card/`, `blocks/split-image-text-banner/`
- Content source: https://www.acryliconpolymers.com/
- Language switcher: `inc/language-switcher.php`
- Existing flags: `assets/gfx/flags/no.svg`, `assets/gfx/flags/gb.svg`
