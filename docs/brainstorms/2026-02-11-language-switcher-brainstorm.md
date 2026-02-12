# Language Switcher — Brainstorm

> 2026-02-11

---

## Hva vi bygger

En language switcher som lar brukere bytte mellom norsk og engelsk versjon av nettstedet. Plasseres i **header og footer**. Visuelt design: **globe-ikon med dropdown** som skalerer til flere språk.

## Hvorfor denne tilnærmingen

Custom PHP i temaet — ingen plugin. Bygger videre på eksisterende mønster der `get_current_blog_id()` allerede brukes i footer.php for språkspesifikke labels. Multisite-arkitekturen gir naturlig språkseparasjon via blog ID.

WPML/Polylang ble vurdert og forkastet: overkill for multisite-oppsettet, potensielle konflikter med sync-plugin, unødvendig lisenskostnad.

## Nøkkelbeslutninger

| Beslutning | Valg |
|---|---|
| Plassering | Header + footer |
| Visuelt design | Globe-ikon med dropdown |
| Lenkelogikk | Tilsvarende side på det andre språket |
| Fremtidssikring | Bygd for flere språk (ikke bare NO/EN) |
| Teknisk tilnærming | Custom PHP i temaet (functions.php + header/footer) |

## Funksjonelle krav

1. **Globe-ikon** i header (desktop + mobil) og footer
2. **Dropdown** viser tilgjengelige språk med flagg og navn
3. **Aktiv språk markert** visuelt i dropdown
4. **Lenke til tilsvarende side** på valgt språk
5. **Fallback til forside** hvis tilsvarende side ikke finnes
6. **Skalerbart** til flere språk/blogger i fremtiden

## Slug-mapping

Utfordring: Norsk og internasjonal side kan ha ulike slugs (f.eks. `/norway/produkter/` → `/products/`).

Mulige strategier:
- **ACF-felt** på hver side som peker til tilsvarende side på andre blogger
- **Konvensjonsbasert** med mapping-array i functions.php
- **Post meta** som kobler sider via ID

Anbefalt: Mapping-array i functions.php som primær metode (enklest å vedlikeholde for et begrenset antall sider), med fallback til forside.

## Teknisk skisse

```
functions.php:
  - acrylicon_get_language_switcher_data() — returnerer array med tilgjengelige språk, URL-er, aktiv status
  - acrylicon_get_equivalent_url($target_blog_id) — finner tilsvarende side på en annen blog
  - acrylicon_language_map() — slug-mapping mellom blogger

header.php / footer.php:
  - Render globe-ikon + dropdown fra data-funksjonen

assets/css (Tailwind):
  - Dropdown-styling, hover/focus states, flagg-ikoner
```

## Multisite-kontekst

| Blog ID | URL-prefix | Språk | Flagg |
|---------|-----------|-------|-------|
| 1 | `/` | English | 🇬🇧 |
| 3 | `/norway/` | Norsk | 🇳🇴 |
| (fremtidig) | `/se/` | Svenska | 🇸🇪 |
| (fremtidig) | `/de/` | Deutsch | 🇩🇪 |

## Åpne spørsmål

1. Skal vi bruke emoji-flagg eller SVG-flagg? (SVG gir mer kontroll og konsistens)
2. Skal dropdown lukke seg automatisk ved klikk utenfor?
3. Bør vi legge til hreflang-tags i `<head>` samtidig? (viktig for SEO)
4. Mobilmenyen — skal language switcher være i hamburgermenyen eller synlig utenfor?

---

*Neste steg: `/workflows:plan` for implementasjonsplan*
