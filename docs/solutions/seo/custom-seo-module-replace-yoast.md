---
title: Custom SEO mu-plugin replacing Yoast SEO
category: seo
tags: [mu-plugin, meta-titles, meta-descriptions, json-ld, open-graph, canonical, robots, schema, multisite]
module: mu-plugins/acrylicon-seo/
date: 2026-02-25
---

# Custom SEO mu-plugin — Erstatter Yoast SEO

## Problem

Yoast SEO var overkill — tungt plugin med mange DB-queries, admin bloat, og begrenset kontroll over HTML-output. Multisite-konflikter (AUTH_COOKIE bug). Brukte ~30% av funksjonaliteten.

## Loesning

Bygget komplett custom mu-plugin "AcryliCon SEO" med 8 moduler.

### Arkitektur

```
mu-plugins/acrylicon-seo.php          ← Loader
mu-plugins/acrylicon-seo/
  acrylicon-seo.php                   ← Main (defines constants, loads modules)
  modules/
    class-meta-titles.php             ← document_title_parts filter
    class-meta-descriptions.php       ← wp_head priority 2
    class-schema.php                  ← wp_head priority 5 (JSON-LD @graph)
    class-open-graph.php              ← wp_head priority 3
    class-canonical.php               ← wp_head priority 10 (replaces core)
    class-robots.php                  ← wp_robots filter
    class-admin-metabox.php           ← SERP preview, override fields
    class-sitemap-integration.php     ← noindex exclusion + Yoast redirect
  assets/css/admin-seo.css
  assets/js/admin-seo.js
  data/organization.php               ← Hardcoded Organization schema data
```

### Noekkelbeslutninger

1. **mu-plugin subdirectory** — alltid aktiv, krever loader-fil i root
2. **`plugins_url()` i init** — ma ikke kalles i global scope for mu-plugins
3. **Organization schema kun pa forsiden** — andre sider refererer via `@id`
4. **Logo ma vaere PNG/WebP** — Google avviser SVG for schema
5. **`og:type = website`** for alle sider (B2B, ikke blogg)
6. **Kun `twitter:card` tag** — resten faller tilbake til OG tags
7. **Core haandterer search noindex** — `wp_robots_noindex_search()` finnes allerede
8. **Front page sjekkes for `is_singular()`** — ellers faller front page gjennom til singular-logikk

## Gotchas

- **`is_front_page()` vs `is_singular()`**: Front page er OGSA singular. Sjekk `is_front_page()` FOERST i alle moduler.
- **`get_post_type()` returnerer registrert navn**: Alltid `produkter`, aldri `products` — forenkler dispatch.
- **`unset($robots['max-image-preview'])` ved noindex**: Ellers vises `noindex, max-image-preview:large` som er motsigende.
- **Taxonomy ID er fast**: `referanser-produkter` pa begge blogger, uavhengig av rewrite slug.
- **Yoast postmeta beholdt som fallback**: `_yoast_wpseo_title` og `_yoast_wpseo_metadesc` leses som sekundaer kilde.

## Postmeta-noekler

- `_acrylicon_seo_title` — manuell tittel-overstyring
- `_acrylicon_seo_description` — manuell description
- `_acrylicon_seo_robots` — "noindex" string
- `_acrylicon_seo_canonical` — custom canonical URL

## Filer

- `mu-plugins/acrylicon-seo/` — all logikk
- `mu-plugins/acrylicon-seo.php` — loader
- `themes/acrylicon-2024/functions.php:393` — fjernet require_once for gammel meta-descriptions.php

## Verifisering

```bash
# Sjekk all SEO-output pa en produktside
curl -s "http://localhost:8888/acrylicon/no/produkter/flake-system-gulv/" | grep -E '(<title>|<meta |<link rel="canonical"|application/ld)'
```
