# SEO: Gjennomgå title-format (separator + site name)

**Priority:** P2
**Status:** pending
**Created:** 2026-02-25
**Module:** mu-plugins/acrylicon-seo

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
