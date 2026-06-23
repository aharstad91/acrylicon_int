# SEO: Gjennomgå title-format (separator + site name)

**Priority:** P2
**Status:** resolved (2026-06-23) — reviewed, no change
**Created:** 2026-02-25
**Module:** mu-plugins/acrylicon-seo

## Review-konklusjon (2026-06-23)

Gjennomgått med Andreas. **Beslutning: behold formatet som det er.**

- `{Tittel} | AcryliCon` på undersider; forsiden bruker `AcryliCon — {tagline}`
  (em-dash, dropper redundant brand-suffiks).
- `|` er bransjestandard (Yoast/WP-default), brand-suffiks styrker SERP-gjenkjenning,
  og em-dash-på-forside vs. pipe-på-undersider er et bevisst mønster (brand-ledet
  beskrivende tittel vs. side-ledet navigasjonstittel), ikke en feil.
- Verifisert at separatoren er synkronisert i alle 3 stedene:
  `class-meta-titles.php::separator()` + `parse_yoast_title()` `%%sep%%` +
  `class-admin-metabox.php` SERP-preview (` | AcryliCon`). Ingen drift.

Vurderte alternativer (en-dash overalt; `| AcryliCon Industrigulv` keyword-suffiks)
ble forkastet — kosmetisk hhv. trunkerings-risiko.

## Problem

Nåværende format: `Sidetittel | AcryliCon`

Bør vurdere om separator (`|`) og site name (`AcryliCon`) er optimalt for alle sidetyper.

## Filer som må endres

1. `mu-plugins/acrylicon-seo/modules/class-meta-titles.php`
   - Linje 18: `separator()` — returnerer `'|'`
   - Linje 26: `$title['site'] = 'AcryliCon'`
2. `mu-plugins/acrylicon-seo/modules/class-admin-metabox.php`
   - Linje 80: SERP-preview hardkoder ` | AcryliCon`

Begge steder må holdes synkronisert.
