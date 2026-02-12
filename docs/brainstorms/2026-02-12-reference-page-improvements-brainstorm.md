# Brainstorm: Reference Page Improvements

**Dato:** 2026-02-12
**Kontekst:** https://acryli-28355.jana-osl.servebolt.cloud/references/

---

## Hva vi bygger

Forbedret referanseside med client-side filtrering, bedre visuell hierarki mellom dybdecaser og vanlige referanser, og utnyttelse av alle fire taksonomier som allerede finnes.

## Nåsituasjon

### Data
- **15 referanser** totalt (4 case studies, 11 vanlige)
- **4 taksonomier** registrert men kun 1 brukt til filtrering:
  - `referanser-kategorier` (industri): 16 terms, 9 med innhold — **har filter**
  - `referanser-produkter` (produkt/system): 11 terms, 6 med innhold — **ingen filter**
  - `referanser-kontor` (kontor/region): 4 terms — **ingen filter**
  - `referanser-type` (case study/vanlig): 3 terms — **ingen filter, bare template routing**

### UX-problemer
1. **Kun industri-filter** — produkter og kontor ignorert helt
2. **Full page reload** per filterklikk — tregt og mister kontekst
3. **Randomisert rekkefølge** — ingen konsistens mellom besøk
4. **Ingen visuell forskjell** mellom dybdecaser og vanlige referanser (bare liten rød badge)
5. **Tomme kategorier synlige** i filter (f.eks. Offshore, Recreation — 0 referanser)
6. **Ingen "Vis alle"-knapp** som aktiv default-state
7. **`query_posts()` brukes** — deprecated, bør erstattes med `WP_Query`

---

## Tilnærming: AJAX-fri client-side filtrering

### Hvorfor dette?
- 15 referanser er lite nok til å laste alle på én gang
- Client-side filter = instant, ingen server roundtrip
- Ingen behov for AJAX, REST API, eller JavaScript-rammeverk
- Enkel CSS hide/show med data-attributter

### Implementasjon

#### 1. Data-attributter på hvert referansekort
```html
<div class="reference-card"
  data-categories="fish-processing-industry"
  data-products="flake-system-floor,wall-system"
  data-office="acrylicon-industrigulv-as-sande"
  data-type="case-study">
```

#### 2. Filtergrupper med pills
```
[Industri ▾]     Fiskeindustri | Skoler | Hotell | ...
[Produkt ▾]      Flake System | Decor System | Wall System | ...
[Kontor ▾]       Sande | Midt-Norge | Nord-Norge | Rogaland
```

Klikk på pill → toggle `.hidden` på kort som ikke matcher. Kombinerte filtre (AND mellom grupper, OR innenfor gruppe).

#### 3. Visuelt hierarki for type
- **Case studies**: Større kort (span 2 kolonner), tydelig "Case Study"-label, mer prominent
- **Vanlige referanser**: Standard enkeltkort

#### 4. Sortering
- Default: Case studies først, deretter kronologisk (nyeste først)
- Fjern `orderby: rand` — forutsigbar rekkefølge

---

## Key Decisions

1. **Client-side filter (ikke AJAX)** — 15 poster er lite nok, instant UX, null backend-kompleksitet
2. **Vanilla JS** — ingen jQuery dependency for filter-logikken
3. **Alle poster på én side** — ingen paginering nødvendig med 15 poster
4. **Skjul tomme kategorier** — filter-pills vises kun for terms med innhold
5. **Kombinerte filtre** — AND mellom grupper (industri + produkt), OR innenfor gruppe
6. **Case studies prominent** — større kort, sortert først
7. **Erstatt `query_posts()`** — bruk `WP_Query` i taxonomy template
8. **Multisite-aware** — EN/NO labels på filtre

## Scope

### In scope
- [ ] Client-side filter med data-attributter
- [ ] Filter-pills for industri, produkt, kontor
- [ ] Visuelt hierarki: case studies vs vanlige referanser
- [ ] Fjern randomisering, sortér kronologisk med case studies først
- [ ] Skjul tomme filter-terms
- [ ] "Vis alle"-state som default
- [ ] Erstatt `query_posts()` med `WP_Query`
- [ ] Bilingual labels (EN/NO)

### Out of scope
- Søkefelt (overkill for 15 poster)
- AJAX/REST API
- Paginering
- Kart-visning av referanser

## Filer som berøres
- `taxonomy-referanser-kategorier.php` → refaktoreres til ny referanseside-template
- `blocks/global-reference/template.php` → mulig oppdatering
- Ny JS-fil for filter-logikk
- Mulig ny CSS for filter-pills og kort-varianter

## Open Questions
- Skal filteret fungere på taxonomy-arkivsiden OGSÅ, eller bare på hovedreferansesiden?
  - **Auto-beslutning:** Kun hovedreferansesiden. Taxonomy-arkiv kan lenke dit.
- Trenger vi URL-state (query params) for filtre?
  - **Auto-beslutning:** Nei for MVP. Kan legges til senere med `history.pushState`.
