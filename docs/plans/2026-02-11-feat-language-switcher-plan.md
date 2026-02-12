---
title: "feat: Language Switcher for Multisite"
type: feat
date: 2026-02-11
brainstorm: docs/brainstorms/2026-02-11-language-switcher-brainstorm.md
audit: GREEN (2026-02-11)
---

# Language Switcher for Multisite

## Overview

Bygge en language switcher som lar brukere bytte mellom norsk (blog 3, `/norway/`) og internasjonal/engelsk (blog 1, `/`) versjon av nettstedet. Globe-ikon med dropdown i header og footer. Bygd for å skalere til flere språk. Inkluderer hreflang-tags for SEO.

## Proposed Solution

Custom PHP i egen include-fil — `inc/language-switcher.php`. Funksjoner for språkdata, slug-mapping, URL-resolving, rendering og hreflang. Rendres i header.php + footer.php. Hreflang-tags via `wp_head` action.

## Technical Approach

### Filstruktur

```
themes/acrylicon-2024/
├── inc/
│   └── language-switcher.php     ← NY: alle language switcher-funksjoner
├── assets/gfx/
│   ├── globe.svg                 ← NY: globe-ikon
│   └── flags/
│       ├── no.svg                ← NY: norsk flagg
│       └── gb.svg                ← NY: britisk flagg
├── functions.php                 ← Legger til require_once for inc/
├── header.php                    ← Legger til switcher i desktop + mobil
└── footer.php                    ← Legger til switcher inline
```

### URL-mapping mellom sider

**Bidireksjonell slug-map** med separate seksjoner for sider, CPT-arkiver og taxonomier:

```php
// inc/language-switcher.php
function acrylicon_slug_map() {
    return [
        // Sider og CPT-arkiver: Norsk slug => Engelsk slug
        'pages' => [
            'fordeler'           => 'benefits',
            'bruksomrader'       => 'applications',
            'produkter'          => 'products',
            'referanser'         => 'references',
            'om-acrylicon'       => 'about-acrylicon',
            'baerekraft'         => 'sustainability',
            'levetids-kostnader' => 'lifecycle-costs',
            'gode-grunner'       => 'good-reasons',
            'kontakt-oss'        => 'contact-us',
            'sertifiseringer'    => 'certifications',
            'industrier'         => 'industries',
        ],
        // Taxonomier
        'taxonomies' => [
            'referanser-type'       => 'reference-type',
            'referanser-kategorier' => 'reference-categories',
            'referanser-kontor'     => 'reference-offices',
            'referanser-produkter'  => 'reference-products',
        ],
    ];
}
```

**Bidireksjonell oppslag:**
```php
function acrylicon_map_slug($slug, $direction = 'no_to_en') {
    $map = acrylicon_slug_map();
    $all = array_merge($map['pages'], $map['taxonomies']);

    if ($direction === 'en_to_no') {
        $all = array_flip($all);
    }

    return $all[$slug] ?? $slug; // Fallback: bruk samme slug
}
```

### Logikk for `acrylicon_get_equivalent_url($target_blog_id)`

1. Hent nåværende URL-path via `$_SERVER['REQUEST_URI']`
2. Strip blog prefix (`/norway/`) og query parameters
3. Splitt path i segmenter
4. Map hvert segment via `acrylicon_map_slug()`
5. Bygg target URL med `home_url()` (environment-agnostisk)
6. Sanitér med `esc_url()`
7. Fallback → target blog forside

### Edge case-håndtering

| Situasjon | Handling |
|-----------|----------|
| Single post uten mapping | Fallback til forside på target |
| Taxonomy-arkiv | Map taxonomy-slug via `taxonomies`-seksjonen |
| Søkeresultatside | Gå til target forside (søkeord er språkspesifikt) |
| 404-side | Gå til target forside |
| Paginert arkiv (`/page/2/`) | Reset til side 1 på target |
| Query parameters (`?filter=`) | Strip ved bytte (filtre er språkspesifikke) |
| Sider kun på ett språk | Fallback til forside, hreflang kun self-reference |

### Tilgjengelige språk

```php
function acrylicon_get_languages() {
    return [
        1 => [
            'code'   => 'en',
            'hreflang' => 'en',      // ISO 639-1
            'label'  => 'English',
            'flag'   => 'gb',
            'prefix' => '/',
        ],
        3 => [
            'code'   => 'no',
            'hreflang' => 'nb',      // Norsk Bokmål (korrekt ISO-kode)
            'label'  => 'Norsk',
            'flag'   => 'no',
            'prefix' => '/norway/',
        ],
    ];
}
```

### Visuell plassering

**Desktop header (lg:block):**
- Etter `wp_nav_menu('primary-menu')`, wrappet i felles flex-container
- Globe-ikon + aktivt språk-kode ("EN" / "NO")
- Klikk → dropdown med alle språk, aktiv markert
- Tailwind: `text-lg`, `text-acryl-black`, `hover:text-acryl-red`, `gap-6`

**Mobilmeny:**
- Inne i `#menuPanel`, etter `wp_nav_menu('mobile')`
- Full bredde, samme styling som menylenker (`text-2xl`, `px-6 py-2`)

**Footer:**
- Inline flagg + språknavn som lenker, hvit tekst (`text-white`)
- Ikke dropdown — direkte lenker

### Hreflang-tags

Via `wp_head` action, bruker `esc_url()` for all output:

```html
<link rel="alternate" hreflang="en" href="https://example.com/benefits/" />
<link rel="alternate" hreflang="nb" href="https://example.com/norway/fordeler/" />
<link rel="alternate" hreflang="x-default" href="https://example.com/benefits/" />
```

- `x-default` peker alltid til engelsk (internasjonal)
- Sider uten ekvivalent: kun self-reference hreflang
- URL-er bygges med `home_url()` (fungerer lokalt og i prod)

### JavaScript

Dropdown følger **eksisterende mobilmeny-pattern**:
- `opacity-0`/`opacity-100` + `invisible`/`visible` toggle
- `setTimeout()` for animasjonstiming (matcher mobilmeny)
- `aria-expanded` attributt oppdateres
- Klikk utenfor → lukk dropdown
- Escape-tast → lukk dropdown
- Inline i footer.php (matcher eksisterende mønster)

---

## Acceptance Criteria

### Funksjonelt

- [ ] Globe-ikon med dropdown vises i desktop header etter primærmeny
- [ ] Dropdown viser alle tilgjengelige språk med flagg og navn
- [ ] Aktivt språk er visuelt markert
- [ ] Klikk på språk navigerer til tilsvarende side på valgt blog
- [ ] Bidireksjonell: NO→EN og EN→NO fungerer begge
- [ ] Fallback til forside hvis tilsvarende side ikke finnes
- [ ] Dropdown lukkes ved klikk utenfor og Escape-tast
- [ ] Language switcher vises i mobilmenyen
- [ ] Language switcher vises i footer (inline-versjon)
- [ ] Fungerer korrekt på begge blogger (ID 1 og 3)

### SEO

- [ ] Hreflang-tags rendres i `<head>` på alle sider
- [ ] `x-default` peker til internasjonal side (blog 1)
- [ ] Tags bruker riktige ISO-koder (`en`, `nb`)
- [ ] Alle URL-er i hreflang bruker `esc_url()`
- [ ] Sider uten ekvivalent har kun self-reference hreflang

### Design

- [ ] Globe SVG-ikon opprettet i `assets/gfx/globe.svg`
- [ ] Flagg-SVGs i `assets/gfx/flags/` (no.svg, gb.svg)
- [ ] Bruker `svg_icon()` for rendering
- [ ] Styling bruker `acryl-*` Tailwind-farger
- [ ] Matcher `text-lg` og `gap-6` fra primærmeny
- [ ] Smooth dropdown med opacity/visibility-pattern
- [ ] Responsivt: desktop dropdown, mobil inline

### Sikkerhet

- [ ] `esc_url()` på alle genererte URL-er
- [ ] Slug-validering: kun `[a-z0-9_-]` akseptert
- [ ] URL bygges med `home_url()`, aldri string-concatenation med bruker-input
- [ ] Ingen direkte output av `$_SERVER['REQUEST_URI']`

### Kode

- [ ] `inc/language-switcher.php` opprettet med alle funksjoner
- [ ] `require_once` lagt til i functions.php
- [ ] ARIA-attributter på dropdown (`aria-expanded`, `aria-haspopup`)
- [ ] Bygg Tailwind CSS etter endringer (`npm run build:css`)

---

## Implementation Plan

### Fase 1: Include-fil med PHP-funksjoner

**Filer:** `themes/acrylicon-2024/inc/language-switcher.php`, `functions.php`

- [ ] Opprett `inc/language-switcher.php`
- [ ] Legg til `require_once` i functions.php (etter eksisterende requires ~linje 365)
- [ ] Implementer `acrylicon_get_languages()`
- [ ] Implementer `acrylicon_slug_map()` med pages + taxonomies
- [ ] Implementer `acrylicon_map_slug($slug, $direction)` med `array_flip()`
- [ ] Implementer `acrylicon_get_equivalent_url($target_blog_id)`
  - Parse `REQUEST_URI`, strip prefix, map segmenter, bygg URL med `home_url()`
  - Sanitér med `esc_url()`
  - Fallback-kjede: mapped slug → samme slug → forside
- [ ] Implementer `acrylicon_render_language_switcher($context)` — `header`, `mobile`, `footer`
- [ ] Implementer `acrylicon_hreflang_tags()` — hook til `wp_head`

### Fase 2: SVG-ikoner

**Filer:** `themes/acrylicon-2024/assets/gfx/`

- [ ] Opprett `globe.svg` (linjestil, matcher eksisterende ikoner)
- [ ] Opprett `flags/no.svg` (norsk flagg, kompakt)
- [ ] Opprett `flags/gb.svg` (britisk flagg, kompakt)

Bruk `svg_icon()` for rendering.

### Fase 3: Header-integrasjon

**Filer:** `themes/acrylicon-2024/header.php`

- [ ] Desktop: Wrap primærmeny + switcher i felles `<div class="flex items-center gap-6">`
  - Bevarer `justify-between` layouten (logo venstre, meny+switcher høyre)
  - Switcher: `acrylicon_render_language_switcher('header')`
- [ ] Mobil: Legg til `acrylicon_render_language_switcher('mobile')` i `#menuPanel`
  - Etter `wp_nav_menu('mobile')`, full bredde

### Fase 4: Footer-integrasjon

**Filer:** `themes/acrylicon-2024/footer.php`

- [ ] Legg til `acrylicon_render_language_switcher('footer')`
  - Inline flagg + språknavn, hvit tekst
  - Plassering: i bunnseksjonen (etter footer-two meny)

### Fase 5: JavaScript

**Filer:** `themes/acrylicon-2024/footer.php` (inline script-blokk)

- [ ] Dropdown toggle med opacity/visibility-pattern (matcher mobilmeny)
- [ ] Klikk utenfor → lukk
- [ ] Escape-tast → lukk
- [ ] `aria-expanded` oppdateres
- [ ] `setTimeout()` for smooth animasjon

### Fase 6: Tailwind build + testing

- [ ] Kjør `npm run build:css`
- [ ] Test norsk side (blog 3): "Norsk" aktiv, "English" → `/`
- [ ] Test internasjonal side (blog 1): "English" aktiv, "Norsk" → `/norway/`
- [ ] Test slug-mapping begge veier: `/norway/produkter/` ↔ `/products/`
- [ ] Test fallback: ukjent side → forside
- [ ] Test edge cases: søk, 404, paginering → target forside
- [ ] Test mobil: switcher synlig i hamburgermenyen
- [ ] Test footer: lenker fungerer
- [ ] Verifiser hreflang i page source (begge blogger)
- [ ] Test dropdown: lukkes ved klikk utenfor + Escape

---

## Dependencies

- `svg_icon()` i functions.php (linje 383-451)
- Tailwind CSS build pipeline (`npm run build:css`)
- Multisite med blog 1 (`/`) og blog 3 (`/norway/`) aktive
- Eksisterende include-pattern: `require_once` i functions.php (linje 364-365)

## Risks

| Risiko | Mitigering |
|--------|-----------|
| Slug-map utdatert ved nye sider | Dokumenter at nye sider/slugs legges til i `acrylicon_slug_map()`. Inline kommentar med instruksjon. |
| Sider kun på én blog | Fallback til forside. Hreflang kun self-reference. |
| Fremtidige språk | `acrylicon_get_languages()` er array — legg til ny entry per språk. |
| Open redirect via URL-manipulasjon | `esc_url()` + `home_url()` + slug-validering forhindrer dette. |

---

## Tech Audit-notater (2026-02-11)

Audit-funn innarbeidet i planen:
1. ✅ Egen include-fil (`inc/language-switcher.php`) i stedet for functions.php
2. ✅ Bidireksjonell slug-map med `array_flip()`
3. ✅ `esc_url()` på all URL-output
4. ✅ Taxonomy-slugs lagt til i mappen
5. ✅ `acryl-*` Tailwind-farger spesifisert
6. ✅ JS-pattern matcher mobilmeny (opacity/visibility)
7. ✅ `nb` (ikke `no`) for hreflang
8. ✅ Edge case-tabell med eksplisitte avgjørelser

---

## References

- Header: `themes/acrylicon-2024/header.php`
- Footer: `themes/acrylicon-2024/footer.php`
- Functions: `themes/acrylicon-2024/functions.php`
- Eksisterende includes: `functions.php:364-365`
- SVG-funksjon: `functions.php:383-451`
- Tailwind config: `themes/acrylicon-2024/tailwind.config.js`
- Mobilmeny JS: `footer.php:60-111`
- Blog ID-sjekker: `footer.php:18,30` + 8 blokkfiler
- Brainstorm: `docs/brainstorms/2026-02-11-language-switcher-brainstorm.md`
