---
title: "feat: Teknisk SEO — Synlighet i norske Google-søk"
type: feat
date: 2026-02-20
deepened: 2026-02-20
brainstorm: docs/brainstorms/2026-02-20-teknisk-seo-synlighet-brainstorm.md
trello: https://trello.com/c/x1PLLDhB/23-teknisk-seo
---

# Teknisk SEO — Synlighet i norske Google-søk

## Enhancement Summary

**Deepened on:** 2026-02-20
**Research agents used:** Yoast Schema API, Schema.org best practices, institutional learnings, performance review

### Key Improvements From Research
1. **Yoast API avklart:** Bruk `Abstract_Schema_Piece` med `is_needed()` + `generate()`, `Schema_IDs` constants for `@id`-referanser. Verifisert mot lokal Yoast v26.8-kildekode.
2. **Forutsetning oppdaget:** Yoast Organization-piece krever at `site_represents === 'company'` i Yoast-innstillinger. Må verifiseres.
3. **Bruk `ProfessionalService`** (subtype av LocalBusiness) — mer spesifikt og anbefalt av Google.
4. **KRITISK perf-fix:** `apply_filters('the_content')` i meta-fallback erstattes med raw content stripping. Sparer 50-200ms per cache-miss.
5. **Gjenbruk `acrylicon_get_languages()`** fra language-switcher for blog-aware `inLanguage`.
6. **Product uten offers er OK** — gir warning (ikke feil) i Rich Results Test. Akseptabelt for B2B.

### New Considerations Discovered
- `wpseo_schema_organization` filter kan utvide eksisterende Organization-piece i stedet for å legge til en ny
- `wpseo_metadesc` aksepterer 2 parametere — second (`$presentation`) kan være `null` i admin-kontekst
- `$identifier` på schema-pieces må være lowercase
- Context (`$this->context`) injiseres AV Yoast etter filter — ikke sett i constructor
- Google AI Overviews favoriserer sider med "semantic completeness" (134-167 ord per seksjon) og verifiserbare fakta

---

## Overview

Implementere strukturert data (JSON-LD) og meta description-fallback for å gjøre Acrylicon synlig i norske Google-søkeresultater og AI Overviews. Bygger på Yoast SEOs eksisterende schema-graph via filtere.

**Mål:** Acrylicon dukker opp i norske Google-søk for bransjetermer som "epoxy gulv", "industrigulv", "gulvløsninger".

**Forutsetninger allerede oppfylt:**
- PageSpeed 99/100 mobil, 100/100 desktop
- Hreflang-tagger (EN/NO + x-default)
- Yoast SEO v26.8 installert (sitemap, canonical, robots)

## Problem Statement

Acrylicon er usynlig i norske Google-søk til tross for grønn PageSpeed. Årsaken: manglende strukturert data og meta descriptions.

**Nåsituasjon (produksjons-audit 2026-02-20):**

| Side | Yoast schema output | Mangler |
|------|---------------------|---------|
| Forside | WebPage, ImageObject, BreadcrumbList, WebSite | Organization |
| Produktsider | **Kun WebSite** | WebPage, Product, BreadcrumbList |
| Referanser | WebPage, ImageObject, BreadcrumbList, WebSite | Article (publisher) |
| Kontor | WebPage, ImageObject, BreadcrumbList, WebSite | LocalBusiness |
| Bruksområder | (ikke sjekket, trolig som produkter) | Service |

**Meta descriptions:** 2-3 av ~145 publiserte sider har utfylt meta description. Google lager egne utdrag.

## Proposed Solution

### Arkitekturbeslutning: Utvid Yoast's schema-graph

**Valgt tilnærming:** Bruk Yoast's `wpseo_schema_graph_pieces` filter for å legge til nye schema-pieces som `Abstract_Schema_Piece`-klasser i den eksisterende grafen. Bruk `wpseo_schema_organization` for å utvide den innebygde Organization-piecen.

**Begrunnelse:**
- Yoast outputter allerede WebPage, BreadcrumbList, WebSite — vi unngår duplikater
- Schema-stykker refererer hverandre via `@id` — må være i samme graf
- Yoast-dokumentert og anbefalt tilnærming
- `$this->context` gir tilgang til `canonical`, `site_url`, `main_schema_id`, `has_image`
- `Schema_IDs` constants sikrer korrekte `@id`-referanser

**Alternativ vurdert og forkastet:** Separate JSON-LD-blokker — risiko for duplikat Organization/BreadcrumbList.

### Ny fil: `inc/schema-markup.php`

Følger eksisterende mønster med `inc/language-switcher.php`.

```
themes/acrylicon-2024/
├── inc/
│   ├── language-switcher.php   (eksisterende)
│   ├── international-offices.php (eksisterende)
│   └── schema-markup.php       (NY)
└── functions.php               (legg til require_once)
```

---

## Implementation Phases

### Phase 1: Grunnmur — Yoast-config + Organization (2-3 timer)

**1.1 Verifiser Yoast site representation**

Organization-schema krever at Yoast er satt til "Company" modus:

```
Yoast > Settings > Site Representation > "Organization or Person" = Organization
```

Verifiser via WP-CLI:
```bash
ssh acryli_28355@jana-osl.servebolt.cloud 'wp option get wpseo --format=json' | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('company_or_person',''))"
# Forventet: "company"
```

Hvis dette er satt korrekt, outputter Yoast allerede en Organization-piece som vi kan utvide med `wpseo_schema_organization`-filteret.

**1.2 Fiks Yoast-konfigurasjon for CPT-er**

Produktsider outputter kun WebSite — dette tyder på at Yoast ikke gjenkjenner CPT-ene som "innhold":

```bash
ssh acryli_28355@jana-osl.servebolt.cloud 'wp option get wpseo_titles --format=json' | python3 -m json.tool | grep -i "noindex\|display.*produkter\|display.*referanser\|display.*kontor\|display.*bruksomrader"
```

Sett alle CPT-er til "Show in search results":
```
Yoast > Search Appearance > Content Types > [CPT] > "Show in search results?" = Yes
```

**1.3 Opprett `inc/schema-markup.php`**

```php
<?php
/**
 * Acrylicon Schema Markup
 *
 * Utvider Yoast SEOs schema-graph med Organization, LocalBusiness, Product, Service.
 * Legger til meta description-fallback for CPT-er uten Yoast-beskrivelse.
 *
 * @package Acrylicon
 */

use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

// Schema graph pieces
add_filter( 'wpseo_schema_graph_pieces', 'acrylicon_add_schema_pieces', 11, 2 );

// Utvid Yoast's innebygde Organization-piece
add_filter( 'wpseo_schema_organization', 'acrylicon_enhance_organization', 11 );

// Meta description fallback
add_filter( 'wpseo_metadesc', 'acrylicon_meta_description_fallback', 10, 2 );

function acrylicon_add_schema_pieces( $pieces, $context ) {
    $pieces[] = new Acrylicon_LocalBusiness_Schema();
    $pieces[] = new Acrylicon_Product_Schema();
    $pieces[] = new Acrylicon_Service_Schema();
    $pieces[] = new Acrylicon_Article_Schema();
    return $pieces;
}
```

> **Research insight:** Yoast injiserer `$context` og `$helpers` som public properties ETTER at `wpseo_schema_graph_pieces` returnerer. Ikke send `$context` til constructor — bruk kun i `is_needed()` og `generate()`.

**1.4 Organization-schema (utvid Yoast's innebygde piece)**

I stedet for å legge til en ny Organization-piece, utvider vi den eksisterende via `wpseo_schema_organization`:

```php
function acrylicon_enhance_organization( $data ) {
    $is_norwegian = ( get_current_blog_id() === 3 );

    $data['@type']        = [ 'Organization', 'Corporation' ];
    $data['legalName']    = $is_norwegian
        ? 'AcryliCon Industrigulv AS'
        : 'AcryliCon Polymers GmbH';
    $data['foundingDate'] = '1977';
    $data['description']  = $is_norwegian
        ? 'Norsk leverandør av sømløse gulv- og veggløsninger for industri og næringsbygg.'
        : 'Norwegian provider of seamless floor and wall solutions for industrial and commercial buildings.';
    $data['address']      = [
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Industrivegen 24',
        'addressLocality' => 'Sande i Vestfold',
        'postalCode'      => '3070',
        'addressCountry'  => 'NO',
    ];
    $data['areaServed']   = [
        [ '@type' => 'Country', 'name' => $is_norwegian ? 'Norge' : 'Norway' ],
    ];

    return $data;
}
```

> **Research insight:** `wpseo_schema_organization` filteret gir oss Organization-dataen Yoast allerede har generert. Vi legger til/overskriver felt uten å duplisere hele piecen. Krever at `site_represents === 'company'` i Yoast.

**1.5 Legg til `require_once` i functions.php**

```php
// functions.php, etter linje 392 (etter language-switcher.php require)
require_once get_template_directory() . '/inc/schema-markup.php';
```

**Verifisering fase 1:**
- [ ] `wp option get wpseo` viser `company_or_person: company`
- [ ] Alle CPT-er er satt til "Show in search results" i Yoast
- [ ] Organization i schema-grafen har `legalName`, `foundingDate`, `address`
- [ ] Produktsider har WebPage + BreadcrumbList (etter Yoast-config)
- [ ] Ingen duplikater — sjekk med Google Rich Results Test
- [ ] PageSpeed fortsatt 99/100

---

### Phase 2: CPT-spesifikk schema (2-3 timer)

**2.1 LocalBusiness — Kontorsider (4 norske kontorer)**

Bruk `ProfessionalService` (spesifikt subtype av LocalBusiness, anbefalt av Google):

```php
class Acrylicon_LocalBusiness_Schema extends Abstract_Schema_Piece {

    public $identifier = 'acrylicon-localbusiness';

    public function is_needed() {
        return is_singular( [ 'kontor', 'offices' ] );
    }

    public function generate() {
        $post_id = $this->context->id;
        $offices = acrylicon_get_office_schema_data();
        $slug    = get_post_field( 'post_name', $post_id );

        if ( ! isset( $offices[ $slug ] ) ) {
            return false;
        }

        $office = $offices[ $slug ];

        $data = [
            '@type'              => 'ProfessionalService',
            '@id'                => $this->context->canonical . '#localbusiness',
            'name'               => get_the_title( $post_id ),
            'url'                => $this->context->canonical,
            'mainEntityOfPage'   => [ '@id' => $this->context->main_schema_id ],
            'parentOrganization' => [
                '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH,
            ],
            'address'   => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $office['street'],
                'addressLocality' => $office['city'],
                'postalCode'      => $office['postal_code'],
                'addressRegion'   => $office['region'],
                'addressCountry'  => 'NO',
            ],
            'telephone' => get_field( 'office_tel', $post_id ) ?: $office['phone'],
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name'  => $office['service_area'],
            ],
            'inLanguage' => acrylicon_get_current_language_code(),
        ];

        if ( $this->context->has_image ) {
            $data['image'] = [ '@id' => $this->context->canonical . Schema_IDs::PRIMARY_IMAGE_HASH ];
        }

        return $data;
    }
}
```

**Adressedata — hardkodet array** (følger mønster fra `inc/international-offices.php`):

```php
function acrylicon_get_office_schema_data() {
    return [
        'acrylicon-industrigulv-as-sande' => [
            'street'       => 'Industrivegen 24',
            'city'         => 'Sande i Vestfold',
            'postal_code'  => '3070',
            'region'       => 'Vestfold og Telemark',
            'phone'        => '', // fra ACF
            'service_area' => 'Oslo, Viken, Innlandet, Vestfold og Telemark',
        ],
        'acrylicon-rogaland-as' => [
            'street'       => '', // hentes fra docs/context/offices.md
            'city'         => 'Stavanger',
            'postal_code'  => '',
            'region'       => 'Rogaland',
            'phone'        => '',
            'service_area' => 'Rogaland, Vestland, Agder, Offshore',
        ],
        'acrylicon-midt-vest-norge-as' => [
            'street'       => '',
            'city'         => '', // hentes fra docs
            'postal_code'  => '',
            'region'       => 'Møre og Romsdal',
            'phone'        => '',
            'service_area' => 'Møre og Romsdal, Trøndelag',
        ],
        'acrylicon-nord-norge-as' => [
            'street'       => '',
            'city'         => 'Tromsø',
            'postal_code'  => '',
            'region'       => 'Troms',
            'phone'        => '',
            'service_area' => 'Nordland, Troms, Finnmark, Svalbard',
        ],
    ];
}
```

> **Research insight:** Bruk `ProfessionalService` i stedet for generisk `LocalBusiness`. Google anbefaler "mest spesifikke subtype." `parentOrganization` refererer tilbake til Organization via `Schema_IDs::ORGANIZATION_HASH`. Adressedata MÅ ha separate felter (streetAddress, addressLocality, postalCode) — Google krever strukturert PostalAddress.

**2.2 Product — Produktsider (12 systemer)**

```php
class Acrylicon_Product_Schema extends Abstract_Schema_Piece {

    public $identifier = 'acrylicon-product';

    public function is_needed() {
        return is_singular( [ 'produkter', 'products' ] );
    }

    public function generate() {
        $post_id = $this->context->id;

        // Hent description fra product_excerpt (HTML stikkordliste)
        $excerpt = get_field( 'product_excerpt', $post_id );
        $description = '';
        if ( $excerpt ) {
            // Konverter <ul><li> til komma-separert prosa
            $clean = wp_strip_all_tags( $excerpt );
            $description = preg_replace( '/\s+/', ' ', trim( $clean ) );
        } else {
            // Fallback for Multi-Grip ID og TankCoating (mangler excerpt)
            $raw = get_post_field( 'post_content', $post_id );
            $raw = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', '', $raw );
            $description = wp_trim_words( wp_strip_all_tags( $raw ), 30 );
        }

        $data = [
            '@type'            => 'Product',
            '@id'              => $this->context->canonical . '#product',
            'name'             => get_the_title( $post_id ),
            'url'              => $this->context->canonical,
            'description'      => $description,
            'mainEntityOfPage' => [ '@id' => $this->context->main_schema_id ],
            'manufacturer'     => [ '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH ],
            'brand'            => [
                '@type' => 'Brand',
                'name'  => 'AcryliCon',
            ],
            'inLanguage' => acrylicon_get_current_language_code(),
            // INGEN offers — B2B uten offentlig pris. Gir warning (ikke feil) i Rich Results Test.
        ];

        if ( $this->context->has_image ) {
            $data['image'] = [ '@id' => $this->context->canonical . Schema_IDs::PRIMARY_IMAGE_HASH ];
        }

        // Tekniske spesifikasjoner fra product_card_meta repeater
        if ( have_rows( 'product_card_meta', $post_id ) ) {
            $data['additionalProperty'] = [];
            while ( have_rows( 'product_card_meta', $post_id ) ) {
                the_row();
                $text = get_sub_field( 'text' );
                if ( $text ) {
                    $data['additionalProperty'][] = [
                        '@type' => 'PropertyValue',
                        'name'  => wp_strip_all_tags( $text ),
                        'value' => wp_strip_all_tags( $text ),
                    ];
                }
            }
        }

        return $data;
    }
}
```

> **Research insight:** Product uten `offers` er gyldig for B2B — Google viser warning, ikke feil. For å kvalifisere for Product Snippet rich results trenger man `review`, `aggregateRating`, eller `offers`. Uten noen av disse får man entity-gjenkjennelse men ikke rich results. `additionalProperty` med `PropertyValue` er anbefalt for tekniske spesifikasjoner.

**2.3 Service — Bruksområder (12 sider)**

```php
class Acrylicon_Service_Schema extends Abstract_Schema_Piece {

    public $identifier = 'acrylicon-service';

    public function is_needed() {
        return is_singular( [ 'bruksomrader', 'applications' ] );
    }

    public function generate() {
        $post_id      = $this->context->id;
        $is_norwegian = ( get_current_blog_id() === 3 );

        // Hent description fra raw post_content (IKKE apply_filters('the_content'))
        $raw = get_post_field( 'post_content', $post_id );
        $raw = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', '', $raw );
        $description = wp_trim_words( wp_strip_all_tags( $raw ), 30 );

        return [
            '@type'            => 'Service',
            '@id'              => $this->context->canonical . '#service',
            'name'             => get_the_title( $post_id ),
            'url'              => $this->context->canonical,
            'description'      => $description,
            'mainEntityOfPage' => [ '@id' => $this->context->main_schema_id ],
            'provider'         => [ '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH ],
            'serviceType'      => $is_norwegian ? 'Gulvløsninger' : 'Floor Solutions',
            'areaServed'       => [
                '@type' => 'Country',
                'name'  => $is_norwegian ? 'Norge' : 'Norway',
            ],
            'inLanguage' => acrylicon_get_current_language_code(),
        ];
    }
}
```

> **Performance insight:** ALDRI bruk `apply_filters('the_content', ...)` i schema-hooks. Det trigger hele blokk-renderingskjeden (26 ACF-blokker) og koster 50-200ms. Bruk raw `post_content` med regex for å strippe blokk-kommentarer.

**2.4 Article — Referanser (100+ sider)**

```php
class Acrylicon_Article_Schema extends Abstract_Schema_Piece {

    public $identifier = 'acrylicon-article';

    public function is_needed() {
        return is_singular( [ 'referanser', 'references' ] );
    }

    public function generate() {
        $post_id = $this->context->id;

        $data = [
            '@type'            => 'Article',
            '@id'              => $this->context->canonical . '#article',
            'headline'         => get_the_title( $post_id ),
            'url'              => $this->context->canonical,
            'mainEntityOfPage' => [ '@id' => $this->context->main_schema_id ],
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_the_modified_date( 'c', $post_id ),
            'publisher'        => [ '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH ],
            'author'           => [ '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH ],
            'inLanguage'       => acrylicon_get_current_language_code(),
        ];

        // Bilde: short-circuit — featured image først, repeater som fallback
        $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( ! $image_url ) {
            $rows = get_field( 'referance_images_repeater', $post_id );
            if ( ! empty( $rows ) && ! empty( $rows[0]['referance_images_repeater_image'] ) ) {
                $img       = $rows[0]['referance_images_repeater_image'];
                $image_url = is_array( $img ) ? $img['url'] : wp_get_attachment_url( $img );
            }
        }
        if ( $image_url ) {
            $data['image'] = $image_url;
        } elseif ( $this->context->has_image ) {
            $data['image'] = [ '@id' => $this->context->canonical . Schema_IDs::PRIMARY_IMAGE_HASH ];
        }

        // Artikkelkategori fra taksonomi
        $terms = get_the_terms( $post_id, 'referanser-kategorier' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            $data['articleSection'] = wp_list_pluck( $terms, 'name' );
        }

        return $data;
    }
}
```

> **Performance insight:** Short-circuit bilde-lookup — prøv `get_the_post_thumbnail_url()` først (1 query). Kun access repeater som fallback, og bare les index 0 (ikke loop med `have_rows()`). Unngår unødvendige DB-queries.

**2.5 Hjelpefunksjon for språk**

Gjenbruk `acrylicon_get_languages()` fra language-switcher:

```php
function acrylicon_get_current_language_code() {
    $languages = acrylicon_get_languages();
    $blog_id   = get_current_blog_id();
    return $languages[ $blog_id ]['hreflang'] ?? 'en';
    // Blog 1: 'en', Blog 3: 'nb'
}
```

> **Institutional learning:** Fra `multisite-language-switcher-LanguageSwitcher-20260211.md` — gjenbruk `acrylicon_get_languages()` for blog-aware kode. Unngå å hardkode blog IDs der denne funksjonen allerede finnes.

**Verifisering fase 2:**
- [ ] LocalBusiness (ProfessionalService) validerer i Rich Results Test for alle 4 kontorsider
- [ ] Product validerer (aksepter "offers missing" warning for B2B)
- [ ] Service validerer på bruksområdesider
- [ ] Article validerer på referansesider med bilde og dato
- [ ] Alle `@id`-referanser peker korrekt til Organization via `Schema_IDs::ORGANIZATION_HASH`
- [ ] `inLanguage` matcher hreflang-tagger (`en` på blog 1, `nb` på blog 3)
- [ ] Ingen `apply_filters('the_content')` i noen schema-piece

---

### Phase 3: Meta description-fallback (1-2 timer)

**3.1 Yoast-filter for meta description**

```php
/**
 * Fallback meta descriptions for CPTs when Yoast field is empty.
 *
 * @param string $description  Current meta description (empty if Yoast field is blank).
 * @param mixed  $presentation Indexable presentation (may be null in admin).
 */
function acrylicon_meta_description_fallback( $description, $presentation = null ) {
    if ( ! empty( $description ) ) {
        return $description;
    }

    if ( ! is_singular() ) {
        return $description;
    }

    $post = get_post();
    if ( ! $post ) {
        return $description;
    }

    $is_norwegian = ( get_current_blog_id() === 3 );
    $generated    = '';

    switch ( $post->post_type ) {
        case 'produkter':
        case 'products':
            $excerpt = get_field( 'product_excerpt', $post->ID );
            if ( $excerpt ) {
                $clean     = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( $excerpt ) ) );
                $generated = 'AcryliCon ' . $post->post_title . ' — ' . wp_trim_words( $clean, 20, '.' );
            } else {
                $generated = 'AcryliCon ' . $post->post_title . ( $is_norwegian
                    ? ' — profesjonell gulvløsning fra AcryliCon.'
                    : ' — professional flooring solution by AcryliCon.' );
            }
            break;

        case 'referanser':
        case 'references':
            $system    = get_field( 'referance_productsystem', $post->ID );
            $generated = $is_norwegian
                ? $post->post_title . ' — referanseprosjekt med ' . ( $system ?: 'AcryliCon' ) . '.'
                : $post->post_title . ' — reference project with ' . ( $system ?: 'AcryliCon' ) . '.';
            break;

        case 'bruksomrader':
        case 'applications':
            $generated = $is_norwegian
                ? 'AcryliCon ' . $post->post_title . ' — skreddersydde gulv- og veggløsninger.'
                : 'AcryliCon ' . $post->post_title . ' — tailored floor and wall solutions.';
            break;

        case 'kontor':
        case 'offices':
            $tel       = get_field( 'office_tel', $post->ID );
            $generated = $is_norwegian
                ? $post->post_title . ' — kontakt oss' . ( $tel ? ' på ' . $tel : '' ) . ' for gulvløsninger.'
                : $post->post_title . ' — contact us' . ( $tel ? ' at ' . $tel : '' ) . ' for flooring solutions.';
            break;

        case 'industrier':
        case 'industries':
            $generated = $is_norwegian
                ? 'Gulvløsninger for ' . mb_strtolower( $post->post_title ) . ' — slitesterke systemer fra AcryliCon.'
                : 'Flooring solutions for ' . mb_strtolower( $post->post_title ) . ' — durable systems by AcryliCon.';
            break;

        default: // pages
            // VIKTIG: ALDRI bruk apply_filters('the_content') her — det trigger
            // hele blokk-renderingskjeden og koster 50-200ms.
            if ( ! empty( $post->post_excerpt ) ) {
                $generated = wp_trim_words( wp_strip_all_tags( $post->post_excerpt ), 25, '...' );
            } else {
                $raw = preg_replace( '/<!--\s*\/?wp:[^>]*-->/', '', $post->post_content );
                $raw = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( $raw ) ) );
                $generated = wp_trim_words( $raw, 25, '...' );
            }
            break;
    }

    return mb_substr( $generated, 0, 160 );
}
```

> **KRITISK performance-fix:** Originalt plan brukte `apply_filters('the_content', $post->post_content)` i default-casen. Dette trigger rendering av alle 26 ACF-blokker og koster 50-200ms per cache-miss. Erstattet med raw `post_content` stripping via regex — koster mikrosekunder.

> **Research insight:** `wpseo_metadesc` aksepterer 2 parametere. Den andre (`$presentation`) kan være `null` i admin-kontekst. Deklarer alltid med default `null`.

**3.2 Generer manuell meta for topp-20 sider**

Generer forslag via Claude, sett via WP-CLI:
```bash
ssh acryli_28355@jana-osl.servebolt.cloud \
  'wp post meta update {ID} _yoast_wpseo_metadesc "{tekst}" --url=https://acryli-28355.jana-osl.servebolt.cloud/no/'
```

**Prioriterte sider (NO-site, blog 3):**
1. Forside (ID 4540, ✅ har allerede)
2. 12 produktsider (ID 70, 72, 76, 148, 149, 152, 154, 156, 159, 160, 5639, 5651)
3. 4 kontorsider + "Norge"-side (ID 137, 141, 163, 171, 107)
4. Nøkkelsider: Referanser-arkiv (84), Om oss (86), Sertifiseringer (324)

**Meta description best practices:**
- 150-160 tegn (norsk)
- Inkluder primært søkeord naturlig
- CTA: "Se løsninger", "Les mer", "Kontakt oss"
- Unik per side — aldri duplisert

**Verifisering fase 3:**
- [ ] Alle sider har meta description (`view-source:` sjekk)
- [ ] Fallback aktiverer kun når Yoast-felt er tomt
- [ ] Manuelt satte meta descriptions overstyrer fallback
- [ ] Korrekt språk per blog (NO/EN)
- [ ] Ingen `apply_filters('the_content')` i fallback-funksjonen

---

### Phase 4: Verifisering og deploy (1-2 timer)

**4.1 Lokal testing**
- [ ] Rich Results Test på representativ side per type (forside, produkt, referanse, kontor, bruksområde)
- [ ] Sjekk at PageSpeed fortsatt er 99/100 (test kald og varm cache)
- [ ] Verifiser ingen JavaScript-feil i konsollen
- [ ] Test på begge blogs (EN og NO)
- [ ] Sjekk `inLanguage` matcher hreflang-tag

**4.2 Deploy til prod**
```bash
rsync -avz --delete \
  --exclude='node_modules/' --exclude='.DS_Store' --exclude='package-lock.json' \
  wp-content/themes/acrylicon-2024/ \
  acryli_28355@jana-osl.servebolt.cloud:/cust/0/acryli_15806/acryli_28355/site/public/wp-content/themes/acrylicon-2024/

# Tøm cache
ssh acryli_28355@jana-osl.servebolt.cloud \
  'rm -rf /cust/0/acryli_15806/acryli_28355/site/public/wp-content/cache/acryli-28355.jana-osl.servebolt.cloud/'
```

**4.3 Prod-verifisering**
- [ ] Google Rich Results Test på 5 representative sider
- [ ] `site:acryli-28355.jana-osl.servebolt.cloud` i Google
- [ ] View-source sjekk av JSON-LD og meta description
- [ ] Kjør PageSpeed Insights 2x: kald cache + varm cache

**4.4 Google Search Console (manuelt)**
- [ ] Verifiser domenet i GSC (DNS TXT-record eller HTML-fil)
- [ ] Submit sitemap: `/no/sitemap_index.xml` og `/sitemap_index.xml`
- [ ] Be om indeksering av forsiden og viktige sider

---

## Acceptance Criteria

### Funksjonelle krav
- [ ] Organization-schema på alle sider med `legalName`, `foundingDate`, `address`, `areaServed`
- [ ] ProfessionalService-schema (LocalBusiness subtype) på 4 norske kontorsider med strukturert PostalAddress
- [ ] Product-schema på 12 produktsider med navn, beskrivelse, bilde, brand, manufacturer
- [ ] Service-schema på 12 bruksområdesider med serviceType og provider
- [ ] Article-schema på referansesider med datePublished, dateModified, publisher, image
- [ ] BreadcrumbList på alle sider (via Yoast)
- [ ] Meta description-fallback genererer norsk/engelsk tekst for alle CPT-er når Yoast er tom
- [ ] Manuell meta description satt for 20+ viktige sider
- [ ] `inLanguage` matcher hreflang (`en` på blog 1, `nb` på blog 3)

### Tekniske krav
- [ ] Alle schema validerer uten feil i Google Rich Results Test
- [ ] Ingen dupliserte schema-blokker (verifisert i view-source)
- [ ] PageSpeed fortsatt 99/100 mobil (kald og varm cache)
- [ ] Bruker `Abstract_Schema_Piece` + `wpseo_schema_graph_pieces` + `wpseo_schema_organization`
- [ ] `@id`-referanser bruker `Schema_IDs::ORGANIZATION_HASH`, `Schema_IDs::PRIMARY_IMAGE_HASH`
- [ ] Ingen `apply_filters('the_content')` i noen schema- eller meta-funksjon
- [ ] `$identifier` er lowercase på alle schema-pieces

### Kvalitetsporter
- [ ] Testet på begge blogs (EN/NO) lokalt
- [ ] Testet på prod etter deploy
- [ ] Cache tømt etter deploy
- [ ] TTFB under 800ms på kald cache for referanser-dybdecase

---

## Performance Budget

| Metrikk | Grense | Kommentar |
|---------|--------|-----------|
| Ekstra HTML-payload per side | < 1 KB | JSON-LD er typisk 600-1000 bytes ekstra |
| Ekstra DB-queries per side (cache-miss) | 3-6 | ACF get_field + get_the_terms |
| TTFB-økning per side (cache-miss) | < 15 ms | Maskert av WP Fastest Cache etter første request |
| PageSpeed mobil | >= 99/100 | Ingen regresjon |

> **Research insight:** WP Fastest Cache serverer statisk HTML for alle gjentatte besøk. PHP-koden kjører kun på cache-miss (etter deploy, etter post-edit, admin-besøk). Med 145 sider og moderat trafikk er 5-15ms ekstra PHP-tid per side usynlig. Transient-caching er IKKE anbefalt — det legger til kompleksitet uten målbar gevinst.

---

## Dependencies & Risks

### Dependencies
- Yoast SEO v26.8 med `site_represents === 'company'` (verifiser!)
- ACF Pro aktivert (feltdata brukes i schema og meta)
- Google Search Console-verifisering krever DNS-tilgang eller filopplasting (manuelt)
- `acrylicon_get_languages()` fra `inc/language-switcher.php` (eksisterende funksjon)

### Risks
| Risiko | Sannsynlighet | Konsekvens | Mitigering |
|--------|---------------|------------|------------|
| Yoast `site_represents` ikke satt til `company` | Medium | Organization-schema mangler | Sjekk og sett i fase 1.1 |
| Yoast oppdatering bryter Schema API | Lav | Schema forsvinner | API har vært stabil v14-v26. Test etter oppdatering. |
| WP Fastest Cache serverer gammel schema | Medium | Google ser utdatert data | Tøm cache etter deploy + etter ACF-endringer |
| Produktsider mangler excerpt | Lav (2 av 12) | Tom/dårlig description | Fallback til raw post_content stripping |
| Google ignorerer schema (tynt innhold) | Medium | Ingen effekt i søk | Fokus på dybdecaser med verifiable facts |
| `apply_filters('the_content')` sniker seg inn | Lav | 50-200ms perf-regresjon | Code review: INGEN the_content filter i schema/meta |

---

## Filer som endres

| Fil | Endring |
|-----|---------|
| `inc/schema-markup.php` | **NY** — Schema pieces (4 klasser) + Organization-filter + meta description fallback |
| `functions.php:~393` | Legg til `require_once` (1 linje) |
| Yoast-innstillinger (DB) | `site_represents: company` + CPT visibility via admin/WP-CLI |

---

## References

### Interne
- Brainstorm: `docs/brainstorms/2026-02-20-teknisk-seo-synlighet-brainstorm.md`
- SEO-strategi: `docs/strategy/seo.md`
- Hreflang-implementering: `inc/language-switcher.php:323-349` (mønster for `wp_head` hook + blog-aware kode)
- Office-data: `inc/international-offices.php` (mønster for hardkodet adressearray), `docs/context/offices.md`
- Selskapsinfo: `docs/context/company.md`
- PageSpeed-løsning: `docs/solutions/performance-issues/pagespeed-69-to-99-render-blocking-webp-20260211.md`
- Multisite-learning: `docs/solutions/best-practices/multisite-language-switcher-LanguageSwitcher-20260211.md`
- Custom templates: `docs/solutions/best-practices/custom-page-templates-multisite-20260211.md`

### Yoast Schema API (verifisert mot lokal v26.8 kildekode)
- Schema Generator: `plugins/wordpress-seo/src/generators/schema-generator.php`
- Abstract Schema Piece: `plugins/wordpress-seo/src/generators/schema/abstract-schema-piece.php`
- Schema IDs: `plugins/wordpress-seo/src/config/schema-ids.php`
- Organization Piece: `plugins/wordpress-seo/src/generators/schema/organization.php`
- Meta Description Presenter: `plugins/wordpress-seo/src/presenters/meta-description-presenter.php`

### Eksterne
- Yoast Schema API: https://developer.yoast.com/features/schema/api/
- Yoast Schema Integration Guidelines: https://developer.yoast.com/features/schema/integration-guidelines/
- Yoast Meta Description API: https://developer.yoast.com/features/seo-tags/descriptions/api/
- Google Rich Results Test: https://search.google.com/test/rich-results
- Google Organization Schema: https://developers.google.com/search/docs/appearance/structured-data/organization
- Google LocalBusiness Schema: https://developers.google.com/search/docs/appearance/structured-data/local-business
- Google Product Schema: https://developers.google.com/search/docs/appearance/structured-data/product
- Schema.org ProfessionalService: https://schema.org/ProfessionalService
