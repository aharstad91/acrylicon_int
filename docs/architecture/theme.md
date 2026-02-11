# Tema-arkitektur – acrylicon-2024

> Sist oppdatert: 2026-02-11

---

## Oversikt

- **Type:** Classic WordPress theme (ikke block theme)
- **CSS:** Tailwind CSS (migrert fra legacy utility-CSS)
- **Editor:** Gutenberg med 26 custom ACF-blokker
- **JS:** jQuery, ScrollReveal, Headroom.js, bodyScrollLock

## 26 ACF-blokker

### Layout og kort
| Blokk | Beskrivelse |
|-------|-------------|
| feature-card | Feature-kort |
| beige-card-variant-two | Beige kort variant 2 |
| beige-card-variant-three | Beige kort variant 3 |
| blue-card-variant-two | Blått kort variant 2 |
| info-card | Informasjonskort |
| split-image-text-banner | Delt bilde/tekst-banner |
| split-image-text-card | Delt bilde/tekst-kort |
| image-split | Bildedeling |

### Innholdsdisplay
| Blokk | Beskrivelse |
|-------|-------------|
| product-card | Produktkort |
| office-staff-card | Ansattkort for kontor |
| office-contact-card | Kontaktkort for kontor |
| global-reference | Global referansevisning |
| specific-references-loop | Spesifikke referanser (loop) |

### Showreel/karuseller
| Blokk | Beskrivelse |
|-------|-------------|
| showreel-reference-bruksomrader | Referansekarusell per bruksområde |
| showreel-reference-produkter | Referansekarusell per produkt |
| showreel-reference-kontor | Referansekarusell per kontor |
| slider-block | Generell slider |

### Tabeller og data
| Blokk | Beskrivelse |
|-------|-------------|
| technical-info-table | Teknisk informasjonstabell |
| download-list | Nedlastingsliste |
| download-table | Nedlastingstabell |
| table-variant-one | Tabellvariant 1 |

### Navigasjon og titler
| Blokk | Beskrivelse |
|-------|-------------|
| header-with-red-back-link | Header med rød tilbakelenke |
| section-title-with-red-button-right | Seksjontittel med rød knapp |
| text-scroller | Tekst-scroller |

### Skjema og annet
| Blokk | Beskrivelse |
|-------|-------------|
| contact-form | Kontaktskjema |
| global-bruksomrader | Global bruksområdevisning |

## Tailwind-konfigurasjon

**Farger:**
- `red` (#E2241C), `dark-blue` (#253761), `light-blue` (#D5EDF7)
- `neutral-1/2/3`, `gray-1/2/3`

**Breakpoints:** `md` (640px), `lg` (960px)

**Byggsystem:**
```bash
cd wp-content/themes/acrylicon-2024
npm run build:css   # Bygg
npm run dev          # Watch mode
```

Source: `src/tailwind.css` → Output: `assets/css/tailwind.css`

## JS-biblioteker

| Bibliotek | Fil | Bruk |
|-----------|-----|------|
| jQuery | WordPress bundled | DOM-manipulering |
| ScrollReveal | scrollreveal.min.js | Scroll-animasjoner |
| Headroom.js | headroom.js | Auto-hide header |
| bodyScrollLock | bodyScrollLock.js | Lås scroll ved overlay |

## Enqueue-strategi

**Frontend:** style.css → fonts.css → gravity.css → tailwind.css → jQuery → ScrollReveal → bodyScrollLock → Headroom → scripts.js

**Editor:** gutenberg-admin.css → editor.css → gravity.css → tailwind.css (editor) → block-panels.js → disable-typography.js → custom-block-styles.js → block-panels.css

## Spesielle tilpasninger

- Font-size support fjernet fra Gutenberg (tvinger konsistente overskrifter)
- Core block patterns deaktivert
- SVG-opplasting tillatt
- `<p>`-tagger fjernet rundt bilder
- Edit-lenker åpner i ny fane
