# Plan: Dynamiske Produktark

**Dato:** 2026-02-12
**Brainstorm:** `docs/brainstorms/2026-02-12-dynamic-product-sheets-brainstorm.md`
**Branch:** `feature/dynamic-product-sheets`

---

## Oversikt

Erstatt 10 statiske PDF-produktark med dynamiske websider generert fra WordPress-data. Hver produktside får en "Produktark"-visning med print-CSS for A4-utskrift.

## Arkitekturbeslutninger

### Datakilde: Parse block content
Produktdata lagres i ACF-blokker i `post_content` (ikke som post meta). Vi bruker `parse_blocks()` for å ekstrahere data fra blokkene og re-rendrer i et sheet-layout.

### URL-struktur: Query parameter
`/produkter/dekor-system/?view=sheet` — enklest å implementere, krever ingen rewrite rules. Fungerer på både blog 1 og blog 3.

### Template-tilnærming: Custom template loading
I `functions.php`, hook på `template_include` for å laste `single-produkter-sheet.php` når `?view=sheet` er satt.

### Manglende data
PDF-ene har seksjoner som IKKE finnes i WordPress-blokkene:
- Spesifikasjonstabell (Overflate, Tykkelse, Farge, Leverandør, BREEAM)
- Rengjøring og vedlikehold
- Herdetid
- Egenskaper og påføring
- Underlag
- Forventet levetid

**MVP-beslutning:** Vi rendrer det som finnes. Manglende seksjoner vises ikke. Data kan legges til senere via nye ACF-felter.

---

## Implementeringsplan

### Steg 1: Block parser helper
**Fil:** `themes/acrylicon-2024/inc/product-sheet-helpers.php`

Lag en funksjon `acrylicon_parse_product_blocks($post_id)` som:
1. Henter `post_content` for produktet
2. Bruker `parse_blocks()` for å ekstrahere blokk-data
3. Returnerer strukturert array med:
   - `title` (fra h1 heading-blokk)
   - `featured_image` (fra post-featured-image eller post thumbnail)
   - `description` (fra paragraph-blokker i columns)
   - `features` (fra acf/feature-card blokk — repeater med image, title, excerpt)
   - `technical_info` (fra acf/technical-info-table — repeater med name, desc)
   - `benefits` (fra wp:list blokk under "Viktigste egenskaper")
   - `downloads` (fra acf/download-list — repeater med name, link)
   - `certifications` (fra post meta `certifications`)
   - `product_excerpt` (fra post meta `product_excerpt`)

### Steg 2: Sheet template
**Fil:** `themes/acrylicon-2024/single-produkter-sheet.php`

HTML-struktur som matcher PDF-layout men med Acrylicons web-design:

```
┌─────────────────────────────────────────┐
│ Header: Logo + systemnavn               │
├─────────────────────────────────────────┤
│ Featured image (full width)             │
├──────────────────┬──────────────────────┤
│ Beskrivelse      │ Egenskaper/fordeler  │
│ og bruk          │ (bullet list)        │
├──────────────────┴──────────────────────┤
│ Teknisk informasjon (tabell)            │
├─────────────────────────────────────────┤
│ Feature cards (2x2 grid)               │
├─────────────────────────────────────────┤
│ Nedlastinger                            │
├─────────────────────────────────────────┤
│ Footer: Logo + kontaktinfo              │
└─────────────────────────────────────────┘
```

- Bruker Acrylicons Tailwind-klasser (acryl-red, acryl-dark-blue, font-sohne-buch/mono)
- Responsive: fungerer på mobil og desktop
- "Skriv ut produktark" knapp (synlig på web, skjult i print)

### Steg 3: Template routing
**Fil:** `themes/acrylicon-2024/functions.php`

Legg til i functions.php:
- `add_query_vars_filter` for å registrere `view` query var
- `template_include` filter for å laste sheet-template når `?view=sheet`

### Steg 4: Print CSS
**Fil:** `themes/acrylicon-2024/assets/css/product-sheet-print.css`

Print-spesifikk CSS:
- `@media print` regler
- Skjul header, footer, navigasjon, print-knapp
- A4-sideformat med margins
- Sidebrytinger mellom seksjoner
- Farger tvunget på (print-color-adjust: exact)
- Bilder skalert til print-vennlig størrelse

### Steg 5: "Se produktark"-knapp
**Fil:** `themes/acrylicon-2024/functions.php` eller som ACF-blokk-utvidelse

Legg til en lenke/knapp på produktsider som peker til `?view=sheet`:
- Kan legges til via `the_content` filter for produkter-CPT
- Eller som en ny enkel ACF-blokk
- **Beslutning:** Bruk `the_content` filter — enklest, ingen blokk-registrering nødvendig

### Steg 6: Oppdater /nedlastinger/-siden
**Manuell oppgave i WP admin**

Legg til lenker til web-produktarkene på nedlastinger-siden. Beholder eksisterende PDF-lenker som fallback.

### Steg 7: Tailwind CSS build
Kjør `npm run build:css` for å inkludere nye klasser i produksjons-CSS.

---

## Filer som endres/opprettes

| Fil | Handling | Beskrivelse |
|-----|----------|-------------|
| `inc/product-sheet-helpers.php` | **NY** | Block parser + data extractor |
| `single-produkter-sheet.php` | **NY** | Produktark web-template |
| `assets/css/product-sheet-print.css` | **NY** | Print-spesifikk CSS |
| `functions.php` | ENDRE | Query var + template routing + content filter |
| `src/tailwind.css` | ENDRE | Evt. nye utility-klasser for sheet |
| `tailwind.config.js` | ENDRE | Evt. nye print-utilities |

---

## Multisite-hensyn

- Template og kode er felles for begge blogger (tema deles)
- Data er per-blog (block content er ulikt per språk)
- URL fungerer på begge: `/produkter/dekor-system/?view=sheet` (NO) og `/products/decor-system/?view=sheet` (EN)
- Ingen spesiell multisite-logikk nødvendig

---

## Avhengigheter

- Tailwind CSS byggesystem (allerede satt opp)
- ACF Pro (allerede installert)
- Eksisterende produkter med blokk-innhold (allerede på plass)

## Testing

- [ ] Sjekk at sheet-template lastes for alle 10+ produkter
- [ ] Verifiser at block parser henter korrekt data
- [ ] Test print-utskrift i Chrome (Cmd+P)
- [ ] Test på blog 1 (EN) og blog 3 (NO)
- [ ] Test responsivt (mobil + desktop)
- [ ] Verifiser at vanlig produktside fortsatt fungerer uten `?view=sheet`
