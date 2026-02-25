---
title: "feat: Custom SEO mu-plugin replacing Yoast SEO"
type: feat
date: 2026-02-25
---

# Custom SEO mu-plugin — Erstatt Yoast SEO

## Enhancement Summary (Deepen)

**Deepened:** 2026-02-25
**Research agents:** WordPress SEO best practices, JSON-LD schema, Open Graph, Admin metabox patterns

### Korreksjoner fra research
1. `plugins_url()` flyttes til init hook (må ikke kalles i global scope for mu-plugins)
2. Organization schema kun på forsiden, ikke alle sider
3. Logo må være PNG/WebP ImageObject, ikke SVG URL (Google-krav)
4. og:type = `website` for alle sider (B2B, ikke blogg)
5. Canonical priority endret fra 2 til 10 (core slot)
6. Search noindex: core håndterer via `wp_robots_noindex_search()` — ikke dupliser
7. `unset($robots['max-image-preview'])` når noindex settes
8. Yoast sitemap redirect regex utvidet til å dekke per-type sitemaps
9. `wp_is_post_revision()` check i save_post handler
10. BreadcrumbList schema lagt til alle sidetyper
11. SERP preview CSS: eksakte Google-farger (#1a0dab, #4d5156, #202124)
12. 50ms debounce for admin JS live preview
13. WordPress 6.8.3 inkluderer sitemap lastmod nativt
14. `og:locale:alternate` lagt til for tospråklig støtte
15. Kun `twitter:card` nødvendig — resten faller tilbake til OG tags

### Tech Audit korreksjoner (YELLOW)
16. 3 CPTs mangler: godegrunner, levetidskostnader, baerekreaftig — legg til med standard templates
17. Front page meta description: legg til eksplisitt håndtering med hardkodet fallback
18. `og:locale:alternate`: wrap i `function_exists('acrylicon_get_equivalent_url')` for sikkerhet
19. `plugins_url()` i mu-plugin: ha `content_url()` fallback klar
20. `add_image_size('og-image')`: registrer i `after_setup_theme` hook, ikke `init`
21. Sitemap redirect: legg til `strpos($uri, 'sitemap')` early-exit guard

---

## Overview

Bygg en komplett custom SEO mu-plugin ("AcryliCon SEO") som erstatter Yoast SEO. Full kontroll over all SEO-output: meta titles, meta descriptions, JSON-LD schema, Open Graph, canonical URLs, robots meta, og admin UI med Google snippet preview.

Siden er ikke live ennå — Yoast deaktiveres umiddelbart, ingen migrasjonsrisiko.

## Problem Statement

Yoast SEO er overkill for AcryliCon:
- Bruker ~30% av funksjonaliteten
- Tungt plugin (mange DB-queries, admin JS/CSS bloat)
- Black box — ingen kontroll over nøyaktig HTML-output
- Multisite-konflikter (AUTH_COOKIE bug, shared taxonomies)
- Planlagt schema/OG-arbeid (Ralph SEO-roadmap) krever egne hooks uansett

## Proposed Solution

### Arkitektur: mu-plugin med moduler

```
mu-plugins/
  acrylicon-seo.php                    ← Loader (auto-loaded by WP)
  acrylicon-seo/
    acrylicon-seo.php                  ← Main plugin, loads modules
    modules/
      class-meta-titles.php            ← Module 1: Title tags
      class-meta-descriptions.php      ← Module 2: Meta descriptions
      class-schema.php                 ← Module 3: JSON-LD schema
      class-open-graph.php             ← Module 4: OG + Twitter Card
      class-canonical.php              ← Module 5: Canonical URLs
      class-robots.php                 ← Module 6: Robots meta
      class-admin-metabox.php          ← Module 7: Admin UI
      class-sitemap-integration.php    ← Module 8: Core sitemap filters
    assets/
      css/admin-seo.css                ← Metabox styles
      js/admin-seo.js                  ← Preview + regenerate
    data/
      organization.php                 ← Hardcoded org data
```

### Loader pattern

```php
// mu-plugins/acrylicon-seo.php (auto-loaded by WordPress)
<?php
require_once __DIR__ . '/acrylicon-seo/acrylicon-seo.php';
```

### Main plugin file

```php
// mu-plugins/acrylicon-seo/acrylicon-seo.php
<?php
/**
 * Plugin Name: AcryliCon SEO
 * Description: Custom SEO module — meta titles, descriptions, schema, OG, canonical, robots
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ACRYLICON_SEO_DIR', __DIR__ );

// Load modules
foreach ( glob( ACRYLICON_SEO_DIR . '/modules/class-*.php' ) as $module ) {
    require_once $module;
}

// Define URL in init (plugins_url() must not be called at mu-plugin load time)
add_action( 'init', 'acrylicon_seo_init' );
function acrylicon_seo_init() {
    if ( ! defined( 'ACRYLICON_SEO_URL' ) ) {
        define( 'ACRYLICON_SEO_URL', plugins_url( '', __FILE__ ) );
    }
    new Acrylicon_SEO_Meta_Titles();
    new Acrylicon_SEO_Meta_Descriptions();
    new Acrylicon_SEO_Schema();
    new Acrylicon_SEO_Open_Graph();
    new Acrylicon_SEO_Canonical();
    new Acrylicon_SEO_Robots();
    new Acrylicon_SEO_Sitemap_Integration();

    if ( is_admin() ) {
        new Acrylicon_SEO_Admin_Metabox();
    }
}
```

---

## Technical Approach

### Module 1: Meta Titles (`class-meta-titles.php`)

Hook: `document_title_parts` filter (works with `add_theme_support('title-tag')` already in theme).

**Title templates:**

| Page type | NO (Blog 3) | EN (Blog 1) |
|-----------|-------------|-------------|
| Front page | `AcryliCon — Sømløse gulv- og veggløsninger` | `AcryliCon — Seamless Floor and Wall Solutions` |
| Produkter | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Referanser | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Kontor | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Bruksomrader | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Industrier | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Pages | `{title} \| AcryliCon` | `{title} \| AcryliCon` |
| Industry archive | `Industrier \| AcryliCon` | `Industries \| AcryliCon` |
| Taxonomy archive | `{term_name} — Referanser \| AcryliCon` | `{term_name} — References \| AcryliCon` |
| Search | `Søk: {query} \| AcryliCon` | `Search: {query} \| AcryliCon` |
| 404 | `Side ikke funnet \| AcryliCon` | `Page Not Found \| AcryliCon` |

**Separator:** `|` (pipe med mellomrom)

**Manual override:** Sjekk `_acrylicon_seo_title` postmeta først. Yoast-fallback: sjekk `_yoast_wpseo_title` som sekundær kilde.

**Lengdegrenser:** Maks 60 tegn for tittel-delen (ekskludert ` | AcryliCon`).

```php
class Acrylicon_SEO_Meta_Titles {
    public function __construct() {
        add_filter( 'document_title_parts', [ $this, 'filter_title' ] );
        add_filter( 'document_title_separator', [ $this, 'separator' ] );
    }

    public function separator( $sep ) {
        return '|';
    }

    public function filter_title( $title ) {
        // Check manual override
        if ( is_singular() ) {
            $custom = get_post_meta( get_the_ID(), '_acrylicon_seo_title', true );
            if ( ! empty( $custom ) ) {
                $title['title'] = $custom;
                return $title;
            }
            // Yoast migration fallback
            $yoast = get_post_meta( get_the_ID(), '_yoast_wpseo_title', true );
            if ( ! empty( $yoast ) ) {
                // Parse Yoast title vars (%%title%%, %%sitename%%)
                $title['title'] = $this->parse_yoast_title( $yoast );
                return $title;
            }
        }

        // Front page
        if ( is_front_page() ) {
            $is_no = ( get_current_blog_id() === 3 );
            $title['title'] = 'AcryliCon';
            $title['tagline'] = $is_no
                ? 'Sømløse gulv- og veggløsninger'
                : 'Seamless Floor and Wall Solutions';
            unset( $title['site'] );
            return $title;
        }

        // Search
        if ( is_search() ) {
            $is_no = ( get_current_blog_id() === 3 );
            $query = get_search_query();
            $title['title'] = $is_no ? "Søk: {$query}" : "Search: {$query}";
        }

        // 404
        if ( is_404() ) {
            $is_no = ( get_current_blog_id() === 3 );
            $title['title'] = $is_no ? 'Side ikke funnet' : 'Page Not Found';
        }

        // Taxonomy archive
        if ( is_tax() ) {
            $term = get_queried_object();
            $is_no = ( get_current_blog_id() === 3 );
            $label = $is_no ? 'Referanser' : 'References';
            $title['title'] = "{$term->name} — {$label}";
        }

        // CPT archive (industrier)
        if ( is_post_type_archive( 'industrier' ) ) {
            $is_no = ( get_current_blog_id() === 3 );
            $title['title'] = $is_no ? 'Industrier' : 'Industries';
        }

        return $title;
    }
}
```

### Module 2: Meta Descriptions (`class-meta-descriptions.php`)

Flytt logikk fra eksisterende `inc/meta-descriptions.php`. **Endringer fra Yoast-versjonen:**

1. Output direkte via `wp_head` action (ikke `wpseo_metadesc` filter som forsvinner)
2. Dispatch på `get_post_type()` (returnerer registrert CPT-navn, alltid `produkter`, ikke `products`)
3. Sjekk `_acrylicon_seo_description` postmeta først
4. Yoast-fallback: sjekk `_yoast_wpseo_metadesc` som sekundær kilde
5. Legg til archive/taxonomy/search/404-håndtering

**Hook:** `add_action( 'wp_head', 'output_meta_description', 2 )`

**Priority 2** — etter hreflang (priority 1), men tidlig i head.

**Fallback-kjede per side:**
1. `_acrylicon_seo_description` postmeta (manuell overstyring)
2. `_yoast_wpseo_metadesc` postmeta (migrasjon fra Yoast)
3. Auto-generert fra CPT-data (eksisterende logikk fra meta-descriptions.php)

**Nye contexts (ikke i original meta-descriptions.php):**

| Context | NO | EN |
|---------|----|----|
| Industry archive | `Industriløsninger fra AcryliCon — sømløse gulv for alle bransjer.` | `Industrial solutions by AcryliCon — seamless flooring for all industries.` |
| Taxonomy archive | `{term_name} — se referanseprosjekter fra AcryliCon.` | `{term_name} — see reference projects by AcryliCon.` |
| Search | Ingen (noindex) | Ingen (noindex) |
| 404 | Ingen (noindex) | Ingen (noindex) |
| Front page | Fra postmeta eller excerpt | Fra postmeta eller excerpt |

**Lengdegrenser:** 120–155 tegn. Truncate med `acrylicon_truncate_meta()` (allerede implementert, mb-safe).

### Module 3: JSON-LD Schema (`class-schema.php`)

**Arkitektur:** Selvstendige JSON-LD-blokker, koblet via `@id`-URLs.

```
@id-mønster:
  Organization: {site_url}#organization
  WebSite:      {site_url}#website
  WebPage:      {canonical}#webpage
  Product:      {canonical}#product
  LocalBusiness: {canonical}#localbusiness
```

**Hook:** `add_action( 'wp_head', 'output_schema', 5 )`

**Schema per sidetype:**

| Sidetype | Schema-typer |
|----------|-------------|
| Front page | Organization + WebSite + WebPage + BreadcrumbList |
| Produkter | Product + WebPage + BreadcrumbList |
| Kontor | LocalBusiness + WebPage + BreadcrumbList |
| Bruksomrader | Service + WebPage + BreadcrumbList |
| Industrier | Service + WebPage + BreadcrumbList |
| Referanser | Article + WebPage + BreadcrumbList |
| Pages | WebPage + BreadcrumbList |
| Archive | CollectionPage + BreadcrumbList |

> **Viktig:** Organization schema kun på forsiden (homepage). Andre sider refererer via `publisher.@id` til `{site_url}#organization`, men Organization-blokken outputtes bare én gang på forsiden.

**Organization-data (hardkodet i `data/organization.php`):**

```php
return [
    '@type'          => 'Organization',
    '@id'            => '{site_url}#organization',
    'name'           => 'AcryliCon',
    'legalName'      => 'AcryliCon Industrigulv AS',
    'url'            => '{site_url}',
    'logo'           => [
        '@type'  => 'ImageObject',
        'url'    => '{theme_url}/assets/gfx/acrylicon-logo-dark.png',
        'width'  => 600,
        'height' => 120,
    ],
    // KRAV: Google krever PNG/WebP for schema logo, IKKE SVG. Lag PNG fra eksisterende SVG.
    'foundingDate'   => '1977',
    'address'        => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Industrivegen 24',
        'addressLocality' => 'Brumunddal',
        'postalCode'      => '2386',
        'addressCountry'  => 'NO',
    ],
    'sameAs'         => [],
];
```

**Product schema (for produkter CPT):**

```php
[
    '@type'       => 'Product',
    '@id'         => '{canonical}#product',
    'name'        => '{post_title}',
    'description' => '{product_excerpt eller meta description}',
    'brand'       => [ '@type' => 'Brand', 'name' => 'AcryliCon' ],
    'manufacturer'=> [ '@id' => '{site_url}#organization' ],
    'image'       => '{featured_image_url}',
]
```

**LocalBusiness schema (for kontor CPT):**

```php
[
    '@type'   => 'ProfessionalService',
    '@id'     => '{canonical}#localbusiness',
    'name'    => '{post_title}',
    'address' => '{office_adress ACF-felt, parset}',
    'telephone' => '{office_tel ACF-felt}',
    'parentOrganization' => [ '@id' => '{site_url}#organization' ],
]
```

**Validation guards:**
- Sjekk at `name` og `description` ikke er tomme før output
- Sjekk at `image` URL er gyldig (ikke tom, ikke SVG for schema)
- Fallback: hvis nødvendige felter mangler, output kun WebPage

### Module 4: Open Graph (`class-open-graph.php`)

**Hook:** `add_action( 'wp_head', 'output_og_tags', 3 )`

**Tags per side:**

```html
<meta property="og:title" content="{title uten ' | AcryliCon'}" />
<meta property="og:description" content="{same as meta description}" />
<meta property="og:url" content="{canonical URL}" />
<meta property="og:site_name" content="AcryliCon" />
<meta property="og:locale" content="{nb_NO|en_GB}" />
<meta property="og:type" content="{website|article}" />
<meta property="og:image" content="{image URL}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:locale:alternate" content="{den andre lokalen}" />
<meta name="twitter:card" content="summary_large_image" />
```

> **Research:** Kun `twitter:card` trengs — Twitter/X faller tilbake til OG tags for title, description og image. Ingen `twitter:title` / `twitter:description` nødvendig.

**og:type mapping:**
- Alle sider: `website` (B2B-side, ikke blogg — `article` brukes kun for redaksjonelt innhold)

> **Research:** For B2B-sider uten kommentarfelt/forfatter er `website` mer semantisk korrekt enn `article`. Facebook aksepterer begge, men `website` er mer passende for produkter, referanser og kontor.

**og:locale:**
- Blog 1: `en_GB`
- Blog 3: `nb_NO`

**OG-bilde fallback-kjede:**
1. Featured image (skalert til 1200x630 via `add_image_size`)
2. Site-wide fallback: `{theme_url}/assets/gfx/acrylicon-og-default.jpg`

**Registrer bildeformat:**
```php
add_image_size( 'og-image', 1200, 630, true );
```

**Ikke output OG tags på:** 404, search (noindex-sider).

### Module 5: Canonical URLs (`class-canonical.php`)

**Hook:** `add_action( 'wp_head', 'output_canonical', 10 )`

**Priority 10** — same slot som core `rel_canonical()`. Fjern core sin og erstatt:
```php
remove_action( 'wp_head', 'rel_canonical' );
```

> **Research:** Core `rel_canonical()` bruker priority 10 og dekker kun singulære sider. Vår versjon erstatter den og legger til arkiv/taxonomy-støtte.

**Regler:**
- Singulære sider: `get_permalink()` (selvhenvisende)
- Sjekk `_acrylicon_seo_canonical` postmeta for manuell overstyring
- Arkiver: `get_post_type_archive_link()`
- Taxonomy: `get_term_link()`
- Paginerte sider: selvhenvisende (inkluderer `/page/N/`)
- 404/search: ingen canonical
- Trailing slash: følg WordPress-innstilling

**Multisite:** `get_permalink()` returnerer korrekt URL per blog automatisk — ingen `switch_to_blog()` nødvendig.

### Module 6: Robots Meta (`class-robots.php`)

**Hook:** `add_filter( 'wp_robots', 'filter_robots' )`

Bruker WordPress 5.7+ `wp_robots` filter (ikke manuell meta-tag).

**Defaults:**

| Context | robots |
|---------|--------|
| Singulær (default) | `index, follow` (WordPress default — trenger ikke filtrere) |
| Singulær med noindex | `noindex, follow` (fra `_acrylicon_seo_robots` postmeta) |
| Industry archive | `index, follow` |
| Taxonomy archive | `index, follow` |
| Search | `noindex, follow` (WordPress core håndterer dette via `wp_robots_noindex_search()` — IKKE dupliser) |
| 404 | `noindex, follow` |
| Author archive | `noindex, follow` |
| Date archive | `noindex, follow` |
| Paginated (page 2+) | `noindex, follow` |

> **Research:** WordPress core setter allerede noindex for search-sider via `wp_robots_noindex_search()`. Vi skal IKKE legge til egen logikk for dette — kun for 404, author, date og paginated.

**Postmeta format:** String `"noindex"` lagret i `_acrylicon_seo_robots`. Tom/fraværende = default (index, follow).

**Viktig:** Når `noindex` settes, fjern også `max-image-preview`:
```php
$robots['noindex'] = true;
unset( $robots['max-image-preview'] );
```

### Module 7: Admin Metabox (`class-admin-metabox.php`)

**Implementasjon:** Klassisk WordPress metabox (`add_meta_box()`). Enklere enn Gutenberg panel, ingen React/build step nødvendig.

**Plassering:** `'normal'` context, `'high'` priority — vises under editor.

**Vises på:** Alle CPTs + pages.

**UI-elementer:**

```
┌─────────────────────────────────────────────────────┐
│  AcryliCon SEO                          Status: ✅  │
│                                                     │
│  Google Preview:                                    │
│  ┌─────────────────────────────────────────────────┐│
│  │ AcryliCon Flake System – Gulv | AcryliCon       ││
│  │ acrylicon.no › produkter › flake-system-gulv     ││
│  │ Dekorativ overflate med sklisikring og rask...   ││
│  └─────────────────────────────────────────────────┘│
│                                                     │
│  SEO Title:  [                          ] 35/60     │
│  Description: [                         ] 142/155   │
│                                                     │
│  ☐ Skjul fra søkemotorer (noindex)                  │
│                                                     │
│  [🔄 Regenerer]  [Tøm manuelle overstyringer]       │
│                                                     │
│  Schema: Organization, Product                      │
└─────────────────────────────────────────────────────┘
```

**Felter:**
- SEO Title: tekst-input, live tegnteller (X/60), placeholder med auto-generert verdi
- Description: textarea, live tegnteller (X/155), placeholder med auto-generert verdi
- Noindex-checkbox
- Regenerer-knapp: tømmer `_acrylicon_seo_title` og `_acrylicon_seo_description`, refresher preview via JS
- Schema-info: viser hvilke schema-typer som vil outputtes (read-only)

**Save-logikk:**
```php
add_action( 'save_post', 'save_seo_meta', 10, 2 );

function save_seo_meta( $post_id, $post ) {
    // Nonce check
    if ( ! wp_verify_nonce( $_POST['acrylicon_seo_nonce'] ?? '', 'acrylicon_seo_save' ) ) return;
    // Capability check
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    // Autosave check
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    // Revision check
    if ( wp_is_post_revision( $post_id ) ) return;

    // Sanitize and save
    $title = sanitize_text_field( $_POST['acrylicon_seo_title'] ?? '' );
    $desc  = sanitize_text_field( $_POST['acrylicon_seo_description'] ?? '' );
    $robots = sanitize_text_field( $_POST['acrylicon_seo_robots'] ?? '' );

    // Only save non-empty values
    if ( ! empty( $title ) ) {
        update_post_meta( $post_id, '_acrylicon_seo_title', $title );
    } else {
        delete_post_meta( $post_id, '_acrylicon_seo_title' );
    }
    // Same pattern for description and robots
}
```

**JS for preview (admin-seo.js):**
- Live-oppdatering av Google preview ved typing i title/description
- Tegnteller med farge: grønn (< maks), gul (nærmer seg), rød (over)
- Regenerer-knapp: AJAX-kall som returnerer auto-generert title/description

**CSS (admin-seo.css):**
- Google SERP preview: font-family `arial, sans-serif`
  - Title: `#1a0dab`, 20px, truncate med ellipsis
  - URL breadcrumb: `#202124`, 14px
  - Description: `#4d5156`, 14px, 2-line clamp
- Kompakt layout, matcher WordPress admin-design
- Tegnteller: grønn `#0a7e07` (ok), gul `#f0b849` (nærmer seg), rød `#e2241c` (over)

**JS debounce:** 50ms debounce på input-events for live preview (forhindrer lag ved rask typing).

### Module 8: Sitemap Integration (`class-sitemap-integration.php`)

Bruker WordPress core sitemaps (`wp-sitemap.xml`).

> **Research:** WordPress 6.8.3 inkluderer allerede `lastmod` i sitemaps nativt. Ingen custom kode nødvendig for dette.

**Oppgaver:**
1. Ekskluder noindex-poster fra sitemap
2. Redirect gammel Yoast-sitemap URL (inkludert alle Yoast per-type sitemaps)

```php
class Acrylicon_SEO_Sitemap_Integration {
    public function __construct() {
        // Exclude noindexed posts
        add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'exclude_noindex' ], 10, 2 );
        // Redirect old Yoast sitemap
        add_action( 'template_redirect', [ $this, 'redirect_yoast_sitemap' ] );
    }

    public function exclude_noindex( $args, $post_type ) {
        $args['meta_query'] = $args['meta_query'] ?? [];
        $args['meta_query'][] = [
            'relation' => 'OR',
            [ 'key' => '_acrylicon_seo_robots', 'compare' => 'NOT EXISTS' ],
            [ 'key' => '_acrylicon_seo_robots', 'value' => 'noindex', 'compare' => '!=' ],
        ];
        return $args;
    }

    public function redirect_yoast_sitemap() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Match Yoast sitemap_index.xml AND per-type sitemaps (post-sitemap.xml, page-sitemap1.xml, etc.)
        if ( preg_match( '#/sitemap_index\.xml#', $uri ) ||
             preg_match( '#/[a-z-]+-sitemap\d*\.xml#', $uri ) ) {
            wp_redirect( home_url( '/wp-sitemap.xml' ), 301 );
            exit;
        }
    }
}
```

---

## Yoast Deactivation Checklist

- [ ] Deaktiver Yoast SEO plugin i WordPress admin (begge blogger)
- [ ] Verifiser at WordPress core sitemaps er aktive (`/wp-sitemap.xml`)
- [ ] Sjekk at `add_theme_support('title-tag')` er i functions.php (allerede der)
- [ ] Fjern `require_once` for `inc/meta-descriptions.php` fra functions.php linje 393
- [ ] Slett `themes/acrylicon-2024/inc/meta-descriptions.php` (logikk flyttet til mu-plugin)
- [ ] Behold Yoast postmeta i DB midlertidig (brukes som fallback)
- [ ] Oppdater Google Search Console sitemap URL til `/wp-sitemap.xml`
- [ ] Test alle CPT-sider for riktig title, description, canonical, OG
- [ ] Test 404, search, archive, taxonomy-sider
- [ ] Vurder å fjerne AUTH_COOKIE fix i wp-config.php (var for Yoast-bug)

---

## Implementation Phases

### Phase 1: Core Output (Moduler 1-6)

Implementer alle output-moduler. Deaktiver Yoast. Verifiser med curl.

**Filer som opprettes:**
- `mu-plugins/acrylicon-seo.php`
- `mu-plugins/acrylicon-seo/acrylicon-seo.php`
- `mu-plugins/acrylicon-seo/modules/class-meta-titles.php`
- `mu-plugins/acrylicon-seo/modules/class-meta-descriptions.php`
- `mu-plugins/acrylicon-seo/modules/class-schema.php`
- `mu-plugins/acrylicon-seo/modules/class-open-graph.php`
- `mu-plugins/acrylicon-seo/modules/class-canonical.php`
- `mu-plugins/acrylicon-seo/modules/class-robots.php`
- `mu-plugins/acrylicon-seo/modules/class-sitemap-integration.php`
- `mu-plugins/acrylicon-seo/data/organization.php`

**Filer som endres:**
- `themes/acrylicon-2024/functions.php` — fjern require_once for meta-descriptions.php

**Filer som slettes:**
- `themes/acrylicon-2024/inc/meta-descriptions.php` — logikk flyttet til mu-plugin

### Phase 2: Admin UI (Modul 7)

Implementer admin metabox med Google preview.

**Filer som opprettes:**
- `mu-plugins/acrylicon-seo/modules/class-admin-metabox.php`
- `mu-plugins/acrylicon-seo/assets/css/admin-seo.css`
- `mu-plugins/acrylicon-seo/assets/js/admin-seo.js`

### Phase 3: Verifisering

- PHP syntax check alle filer
- curl-test alle CPT-typer (NO + EN)
- Verifiser at Yoast er deaktivert uten feil
- Test admin metabox i editor
- Google Rich Results Test for schema

---

## Acceptance Criteria

### Functional

- [ ] Alle singulære sider har `<title>` tag med riktig format
- [ ] Alle singulære sider har `<meta name="description">` (auto-generert eller manuell)
- [ ] Alle sider har `<link rel="canonical">`
- [ ] Alle sider har `<meta name="robots">` med riktige verdier
- [ ] Alle sider har Open Graph tags (og:title, og:description, og:url, og:type, og:locale, og:site_name)
- [ ] Produkter, kontor, referanser, bruksomrader, industrier har CPT-spesifik JSON-LD schema
- [ ] Organization schema outputtes kun på forsiden (andre sider refererer via @id)
- [ ] 404 og search har noindex
- [ ] Author/date archives har noindex
- [ ] WordPress core sitemap er aktiv og ekskluderer noindex-poster
- [ ] Gammel Yoast sitemap-URL redirecter til wp-sitemap.xml
- [ ] Admin metabox vises på alle CPTs + pages
- [ ] Google preview oppdateres live ved typing
- [ ] Manuell overstyring av title/description fungerer
- [ ] Noindex-checkbox fungerer
- [ ] Regenerer-knapp tømmer manuelle overstyringer
- [ ] Yoast er deaktivert uten feil
- [ ] Eksisterende Yoast meta descriptions leses som fallback
- [ ] Alt fungerer på begge blogger (NO + EN)

### Non-Functional

- [ ] Ingen nye PHP errors/warnings
- [ ] Maks 5-10 ekstra DB-queries per sidevisning (cache miss)
- [ ] Admin assets lastes kun på post-edit screens
- [ ] Alle admin-inputs sanitiseres (sanitize_text_field, esc_url_raw)
- [ ] Nonce-verifisering på alle save-operasjoner
- [ ] Capability check (edit_post) på save

---

## Dependencies & Risks

**Dependencies:**
- `add_theme_support('title-tag')` i functions.php (allerede aktiv)
- `add_post_type_support('page', 'excerpt')` (allerede aktiv)
- ACF Pro (for `get_field()` i meta descriptions og schema)
- WordPress 5.7+ (for `wp_robots` filter)
- WordPress 5.5+ (for core sitemaps)

**Prep-oppgaver:**
- Lag PNG-versjon av logo (`acrylicon-logo-dark.png`, min 112x112px) fra eksisterende SVG
- Lag OG default-bilde (`acrylicon-og-default.jpg`, 1200x630)

**Risiko:**
- **Yoast postmeta orphaning:** Mitigert ved å lese `_yoast_wpseo_*` som fallback
- **Schema validation errors:** Mitigert ved guards (sjekk tomme felter før output)
- **Todo 009 (taxonomy term names):** Påvirker meta descriptions og schema for referanser. Løsning bør prioriteres parallelt.

---

## References

### Internal
- Brainstorm: `docs/brainstorms/2026-02-25-custom-seo-module-replace-yoast-brainstorm.md`
- Eksisterende meta descriptions: `themes/acrylicon-2024/inc/meta-descriptions.php`
- Compound doc: `docs/solutions/seo/meta-description-fallback.md`
- Language switcher: `themes/acrylicon-2024/inc/language-switcher.php`
- MU-plugin mønster: `mu-plugins/acrylicon-shared-taxonomies.php`
- Admin metabox mønster: `plugins/acrylicon-multisite-sync/includes/class-admin-ui.php`
- Todo 009: `todos/009-pending-p1-shared-taxonomy-names-multisite-i18n.md`
- CPT slugs: `themes/acrylicon-2024/functions.php:126`

### Learned Patterns
- Taxonomy ID er alltid `referanser-produkter` (ikke rewrite slug)
- `office_adress` (med typo) er korrekt ACF-feltnavn
- `get_post_type()` returnerer registrert CPT-navn (f.eks. `produkter`), ikke rewrite slug
- `wp_strip_all_tags($text, true)` > `strip_tags()` (fjerner script/style)
- `mb_substr()` + `mb_strrpos()` for æøå-safe truncering
- MU-plugins fra subdirectory krever loader-fil i `mu-plugins/`
