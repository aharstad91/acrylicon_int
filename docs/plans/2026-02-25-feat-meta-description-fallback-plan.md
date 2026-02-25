# Plan: SEO-007 — Meta description PHP fallback via Yoast filter

**Brainstorm:** `docs/brainstorms/2026-02-20-teknisk-seo-synlighet-brainstorm.md`
**Branch:** `ralph/technical-seo` (eksisterende)

---

## Kontekst

Acrylicon har ~145 publiserte sider, men kun 2-3 har manuelt utfylte meta descriptions i Yoast SEO. Yoast har **ingen egen fallback** — når feltet er tomt, output Yoast ingenting (ingen `<meta name="description">`-tag). Google lager da sine egne utdrag fra sideinnholdet, som ofte er dårlige.

**Mål:** Hver eneste side på acrylicon.no skal ha en meningsfull meta description — enten manuelt skrevet i Yoast, eller auto-generert fra strukturert data som allerede finnes i databasen.

---

## Implementering

### Ny fil: `themes/acrylicon-2024/inc/meta-descriptions.php`

Én filter-funksjon på `wpseo_metadesc` + hjelpefunksjoner per CPT.

#### Hovedfunksjon: `acrylicon_meta_description_fallback`

```php
add_filter( 'wpseo_metadesc', 'acrylicon_meta_description_fallback', 10, 2 );

function acrylicon_meta_description_fallback( $description, $presentation ) {
    // Behold manuelt skrevne Yoast-beskrivelser
    if ( ! empty( trim( $description ) ) ) {
        return $description;
    }

    // Kun singular posts — ignorer arkiv, 404, søk, taxonomy
    $object_type     = $presentation->model->object_type ?? '';
    $object_sub_type = $presentation->model->object_sub_type ?? '';
    $object_id       = $presentation->model->object_id ?? 0;

    if ( 'post' !== $object_type || empty( $object_id ) ) {
        return $description;
    }

    $is_norwegian = ( get_current_blog_id() === 3 );

    // Dispatch basert på post type
    switch ( $object_sub_type ) {
        case 'produkter':
        case 'products':
            return acrylicon_meta_produkter( $object_id, $is_norwegian );
        case 'referanser':
        case 'references':
            return acrylicon_meta_referanser( $object_id, $is_norwegian );
        case 'kontor':
        case 'offices':
            return acrylicon_meta_kontor( $object_id, $is_norwegian );
        case 'bruksomrader':
        case 'applications':
            return acrylicon_meta_bruksomrader( $object_id, $is_norwegian );
        case 'industrier':
        case 'industries':
            return acrylicon_meta_industrier( $object_id, $is_norwegian );
        case 'page':
            return acrylicon_meta_page( $object_id, $is_norwegian );
        default:
            return $description;
    }
}
```

**Nøkkelvalg fra research:**
- Bruker `$presentation->model` i stedet for `get_post()` — mer effektivt, ingen ekstra DB-query
- Guard mot non-singular contexts (`object_type !== 'post'`) — forhindrer feil på arkiv/404/søk
- `get_field()` er trygt her — ACF cacher post meta internt, ingen ekstra caching nødvendig

#### CPT-spesifikke hjelpefunksjoner

**`acrylicon_meta_produkter($post_id, $is_norwegian)`**
- **Datakilde:** `get_field('product_excerpt', $post_id)` — newline-separerte bullet-fragmenter (ikke HTML-liste)
- **Faktisk dataformat:** `"Elastisk, værbestandig og sklisikkert / Både utendørs og innendørs / For parkering, gangveier, ramper m.m. / 2-4 mm tykkelse"`
- **Konvertering:** `wp_strip_all_tags($excerpt, true)` → allerede brukbart format, trunkér til 155 tegn
- **Mal NO:** `"AcryliCon {tittel} — {excerpt}. Profesjonell gulvløsning."`
- **Mal EN:** `"AcryliCon {title} — {excerpt}. Professional flooring solution."`
- **Fallback:** 2 produkter (Multi-Grip ID #5651, TankCoating #5639) har tom excerpt → bruk post-tittel + generisk tekst

**`acrylicon_meta_referanser($post_id, $is_norwegian)`**
- **Datakilde:** Tittel + `wp_get_post_terms($post_id, 'referanse-produkter')` (NO) / `reference-products` (EN) — taxonomy slug hentes fra `acrylicon_get_cpt_slugs()`
- **Data bekreftet:** 58 av ~100 referanser har minst én produktterm (Dekor System: 18, Flake System: 18, Wall System: 7, osv.)
- **Mal NO:** `"{Tittel} — referanseprosjekt med {produktsystem} fra AcryliCon."`
- **Mal EN:** `"{Title} — reference project with {product system} by AcryliCon."`
- **Fallback:** Uten taxonomy-termer → `"{Tittel} — referanseprosjekt fra AcryliCon."`

**`acrylicon_meta_kontor($post_id, $is_norwegian)`**
- **Datakilde:** `get_field('office_adress', $post_id)` + `get_field('office_tel', $post_id)` — 5/5 kontorer har data
- **Eksempel data:** "Jernbaneveien 30, 8012 Bodø" / "75 58 80 80"
- **Mal NO:** `"AcryliCon {tittel} — {adresse}. Kontakt oss for profesjonelle gulvløsninger."`
- **Mal EN:** `"AcryliCon {title} — {address}. Contact us for professional flooring solutions."`

**`acrylicon_meta_bruksomrader($post_id, $is_norwegian)`**
- **Datakilde:** Post-tittel (ingen ACF-felter)
- **Mal NO:** `"Gulvløsninger for {tittel} — skreddersydde gulv- og veggløsninger fra AcryliCon."`
- **Mal EN:** `"Flooring solutions for {title} — tailored floor and wall solutions by AcryliCon."`

**`acrylicon_meta_industrier($post_id, $is_norwegian)`**
- **Datakilde:** Post-tittel (ingen ACF-felter)
- **Mal NO:** `"Gulvløsninger for {tittel} — slitesterke og hygieniske systemer fra AcryliCon."`
- **Mal EN:** `"Flooring for {title} — durable and hygienic systems by AcryliCon."`

**`acrylicon_meta_page($post_id, $is_norwegian)`**
- **Datakilde:** `get_post($post_id)->post_excerpt` (excerpt aktivert for pages via `functions.php:14`)
- **Fallback:** `wp_trim_words( wp_strip_all_tags( get_post($post_id)->post_content, true ), 25 )`
- **Ingen fast mal** — bruker innholdet direkte

#### Hjelpefunksjon

**`acrylicon_truncate_meta($text, $max = 155)`**
- Bruker `mb_substr()` + `mb_strrpos()` for æøå-safe trunkering på ordgrense
- Legger til "…" om teksten kuttes
- Returnerer ren tekst

```php
function acrylicon_truncate_meta( $text, $max = 155 ) {
    $text = wp_strip_all_tags( $text, true );
    if ( mb_strlen( $text ) <= $max ) {
        return $text;
    }
    $text = mb_substr( $text, 0, $max );
    $last_space = mb_strrpos( $text, ' ' );
    if ( $last_space !== false ) {
        $text = mb_substr( $text, 0, $last_space );
    }
    return $text . '…';
}
```

### Endring: `themes/acrylicon-2024/functions.php`

Legg til etter linje 392:

```php
require_once get_template_directory() . '/inc/meta-descriptions.php';
```

---

## Filer som endres

| Fil | Type | Endring |
|-----|------|---------|
| `themes/acrylicon-2024/inc/meta-descriptions.php` | NY | ~120 linjer — filter + hjelpere |
| `themes/acrylicon-2024/functions.php` | ENDRING | +1 linje: require_once (etter linje 392) |

---

## Datahull og fallbacks

| CPT | Dekning | Mangler | Fallback |
|-----|---------|---------|----------|
| produkter | 10/12 har product_excerpt | Multi-Grip ID (#5651), TankCoating (#5639) | Tittel + generisk tekst |
| referanser | ~58/100 har produkttermer | ~42 referanser uten taxonomy | Tittel + "referanseprosjekt fra AcryliCon" |
| kontor | 5/5 komplett | — | — |
| bruksomrader | Kun tittel | Ingen ACF-felter | Mal-basert fra tittel |
| industrier | Kun tittel | Ingen ACF-felter | Mal-basert fra tittel |
| pages | Excerpt + innhold | Mest tomme excerpts | Første 25 ord fra innhold |

---

## Verifisering

### 1. PHP syntax
```bash
php -l wp-content/themes/acrylicon-2024/inc/meta-descriptions.php
php -l wp-content/themes/acrylicon-2024/functions.php
```

### 2. Funksjonell test — meta description vises
```bash
# Produkt med excerpt (Dekor System)
curl -s http://localhost:8888/acrylicon/no/produkter/dekor-system-gulv/ | grep 'meta name="description"'

# Kontor (Nord Norge)
curl -s http://localhost:8888/acrylicon/no/kontor/acrylicon-nord-norge-as/ | grep 'meta name="description"'

# Referanse med produktterm
curl -s http://localhost:8888/acrylicon/no/referanser/nationalteatret-stasjon/ | grep 'meta name="description"'

# Forside — MÅ beholde manuell Yoast-description
curl -s http://localhost:8888/acrylicon/no/ | grep 'meta name="description"'

# Engelsk side
curl -s http://localhost:8888/acrylicon/products/dekor-system-gulv/ | grep 'meta name="description"'
```

### 3. Lengdesjekk (130-160 tegn)
```bash
curl -s http://localhost:8888/acrylicon/no/produkter/dekor-system-gulv/ | grep -oP 'name="description" content="\K[^"]+' | wc -c
```

### 4. Negativ test — manuell Yoast bevares
Forsiden (som har manuell meta) skal beholde sin eksisterende description uendret.
