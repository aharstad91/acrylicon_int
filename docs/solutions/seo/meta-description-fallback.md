---
title: Auto-generated meta descriptions via Yoast filter
category: seo
tags: [yoast, meta-description, wpseo_metadesc, acf, multisite]
module: themes/acrylicon-2024/inc/meta-descriptions.php
date: 2026-02-25
---

# Auto-generated Meta Descriptions via Yoast Filter

## Problem

~145 publiserte sider på acrylicon.no hadde ingen meta description. Yoast SEO har **ingen innebygd fallback** — når feltet er tomt, output Yoast ingenting (ingen `<meta name="description">`-tag). Google lager da egne utdrag som ofte er dårlige.

## Løsning

En PHP-funksjon som filtrerer `wpseo_metadesc` og auto-genererer meta descriptions fra ACF-data og postinnhold.

### Nøkkelvalg

1. **Bruker `$presentation->model`** i stedet for `get_post()` — ingen ekstra DB-query
2. **Guard mot non-singular contexts** — `object_type !== 'post'` returnerer tidlig for arkiv/404/søk
3. **`wp_strip_all_tags($content, true)`** brukes over `strip_tags()` — fjerner script/style-innhold
4. **`mb_substr()` + `mb_strrpos()`** for trunkering — æøå-safe
5. **Overstyrer aldri manuelt skrevne Yoast-descriptions**

### Maler per CPT

| CPT | Datakilde | Eksempel output |
|-----|-----------|-----------------|
| produkter | `product_excerpt` ACF-felt | "AcryliCon Flake System – Gulv — Dekorativ overflate Sklisikring..." |
| referanser | Tittel + `referanser-produkter` taxonomy | "Bøler bad — referanseprosjekt med Flake System fra AcryliCon." |
| kontor | `office_adress` ACF-felt | "Acrylicon Nord Norge AS — Jernbaneveien 30, 8012 Bodø..." |
| bruksomrader | Post-tittel | "Gulvløsninger for Sykehus — skreddersydde gulv-..." |
| industrier | Post-tittel | "Gulvløsninger for Matproduksjon — slitesterke..." |
| pages | post_excerpt → post_content | Dynamisk fra innhold |

## Gotchas

- **Taxonomy ID ≠ rewrite slug:** Taxonomy-ID er alltid `referanser-produkter` på begge blogger. `acrylicon_get_cpt_slugs()['tax_produkter']` gir URL-rewrite-sluggen, IKKE taxonomy-ID-en.
- **Yoast filter signatur:** `add_filter('wpseo_metadesc', $fn, 10, 2)` — `2` er obligatorisk for å få `$presentation`-objektet.
- **`product_excerpt` format:** Newline-separerte bullet-fragmenter (ikke HTML-liste). `wp_strip_all_tags($excerpt, true)` gjør dem lesbare.
- **2 produkter mangler excerpt:** Multi-Grip ID (#5651) og TankCoating (#5639) — har generisk fallback.

## Filer

- `themes/acrylicon-2024/inc/meta-descriptions.php` — all logikk
- `themes/acrylicon-2024/functions.php` — require_once (linje 393)

## Test

```bash
# Sjekk meta description for en produktside
curl -s http://localhost:8888/acrylicon/no/produkter/flake-system-gulv/ | grep 'meta name="description"'
```
