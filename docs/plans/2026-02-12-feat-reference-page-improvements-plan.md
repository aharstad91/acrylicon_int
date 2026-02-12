# Plan: Reference Page Improvements

**Brainstorm:** `docs/brainstorms/2026-02-12-reference-page-improvements-brainstorm.md`
**Dato:** 2026-02-12

---

## Mål

Forbedre `global-reference` ACF-blokken med:
1. Client-side filtrering på industri, produkt og kontor
2. Visuelt hierarki mellom case studies og vanlige referanser
3. Erstatt randomisering med meningsfull sortering
4. Skjul tomme filter-terms

## Arkitektur-beslutning

**Endre ACF-blokken `global-reference`**, ikke taxonomy-templaten. Blokken er det som rendrer referansesiden på `/references/`. Taxonomy-templaten (`taxonomy-referanser-kategorier.php`) håndterer arkivsider per kategori — den kan beholdes som fallback men peke tilbake til hovedsiden.

## Implementasjon

### Steg 1: Oppdater `global-reference/template.php`

**Endringer:**
- Last alle referanser (ingen paginering, kun 15 poster)
- Legg `data-*` attributter på hvert kort for client-side filtering
- Sorter case studies først, deretter kronologisk
- Gi case studies et visuelt større kort (span 2 kolonner på desktop)
- Legg til filter-grupper for alle tre taksonomier (industri, produkt, kontor)
- Skjul terms uten innhold (`hide_empty: true` allerede satt)
- Legg til "Alle" som default aktiv filter-state
- Erstatt taxonomy-lenker med `data-filter` buttons (ikke `<a>` tags)

**Data-attributter per kort:**
```html
<div class="reference-card"
  data-categories="fish-processing-industry"
  data-products="flake-system-floor,wall-system"
  data-office="acrylicon-industrigulv-as-sande"
  data-type="case-study">
```

**Filter HTML-struktur:**
```html
<div class="reference-filters">
  <div class="filter-group" data-filter-taxonomy="categories">
    <h4>Filter by industry</h4>
    <div class="flex flex-wrap gap-2">
      <button class="filter-pill active" data-filter-value="all">All</button>
      <button class="filter-pill" data-filter-value="fish-processing">Fish processing</button>
      ...
    </div>
  </div>
  <!-- Repeat for products, office -->
</div>
```

**Sortering i WP_Query:**
```php
$query_args = [
  'post_type' => 'referanser',
  'posts_per_page' => -1,
  'meta_key' => '', // no meta sort needed
  'orderby' => [
    'date' => 'DESC',
  ],
];
```
Case studies sorteres først via PHP `usort` etter query (sjekk `referanser-type` taxonomy).

### Steg 2: Lag `assets/js/reference-filter.js`

**Vanilla JS, ingen jQuery.**

```
Logikk:
1. Les alle .reference-card elementer
2. Les alle .filter-pill buttons
3. På klikk:
   a. Toggle active state på pill
   b. Samle aktive filtre per gruppe
   c. For hvert kort: sjekk om data-attributter matcher
   d. AND mellom grupper, OR innenfor gruppe
   e. Toggle .hidden klasse
4. Oppdater teller: "Viser X av Y referanser"
```

**Animasjon:** Enkel CSS transition med opacity for smooth hide/show.

### Steg 3: Enqueue JS i `functions.php`

Enqueue `reference-filter.js` kun når `global-reference` blokken er på siden. Bruk `wp_enqueue_script` med `defer`.

### Steg 4: CSS for filter og kort-varianter

I Tailwind (ingen egen CSS-fil nødvendig):
- Filter pills: Gjenbruk eksisterende pill-stil
- Active pill: `bg-acryl-dark-blue text-white`
- Case study kort: `lg:col-span-2` for større fremvisning
- Hidden state: Tailwind `.hidden` + transition

### Steg 5: Oppdater taxonomy template

`taxonomy-referanser-kategorier.php`:
- Erstatt `query_posts()` med `WP_Query`
- Beholdes som fallback for direkte taxonomy-URLer
- Kan forenkles siden hovedfilteret nå er i blokken

---

## Filer

| Fil | Endring |
|-----|---------|
| `blocks/global-reference/template.php` | Refaktorér: data-attributter, filter-UI, sortering, case study layout |
| `assets/js/reference-filter.js` | NY: Client-side filter logikk |
| `functions.php` | Enqueue filter JS |
| `taxonomy-referanser-kategorier.php` | Erstatt `query_posts()` med `WP_Query` |

## Ikke i scope
- Søkefelt, AJAX, paginering, URL-state
- Endringer på single-referanse templates
- Nye ACF-felter
