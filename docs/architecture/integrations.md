# Integrasjoner

> Sist oppdatert: 2026-02-11

---

## Nåværende integrasjoner

### Servebolt (hosting)
- **SSH:** `acryli_28355@jana-osl.servebolt.cloud` (nøkkelautentisering)
- **WP path:** `/cust/0/acryli_15806/acryli_28355/site/public`
- **WP-CLI:** Konfigurert via `~/.wp-cli/config.yml` (skip-plugins: wp-fastest-cache)
- **Environment:** bolt_id 15806

### SuperOffice CRM (nåværende)
- Kontaktskjema via iFrame fra SuperOffice
- Ingen API-integrasjon
- Leads fordeles manuelt til riktig kontor/region

### GA4 (Google Analytics 4)
- Implementert for trafikkanalyse
- Mobiltrafikk: ~66%

### Yoast SEO
- SEO-plugin for meta, sitemap, breadcrumbs
- Kjent bug: AUTH_COOKIE-feil i WP-CLI multisite (fikset med eksplisitte cookie-definisjoner)

### WP Fastest Cache
- Caching-plugin
- Skippes i WP-CLI (dumper HTML ellers)

### ACF Pro
- Advanced Custom Fields – brukes for alle 26 custom blokker
- Feltgrupper knyttet til CPTs og blokker

## Planlagte integrasjoner

### Gravity Forms + Zapier → SuperOffice
- **Erstatter:** SuperOffice iFrame-kontaktskjemaer
- **Flyt:** Bruker fyller ut skjema → Gravity Forms → Zapier → SuperOffice CRM
- **Fordel:** Bedre UX, tracking, datakvalitet

### Hotjar
- Heatmaps og brukeratferdsanalyse
- Planlagt for å identifisere UX-forbedringer

### CDN/bildeoptimering
- **Problem:** 11GB uploads-mappe
- **Plan:** R2 CDN / bildekomprimering
- Se `docs/plans/2026-01-26-feat-wordpress-media-storage-optimization-plan.md`

### WordPress Importer
- Installert lokalt for content sync mellom prod og lokal
- Brukes for produkter, referanser og andre CPTs

## Plugins-oversikt

| Plugin | Type | Status |
|--------|------|--------|
| ACF Pro | Custom fields/blocks | Aktiv |
| Yoast SEO | SEO | Aktiv |
| WP Fastest Cache | Caching | Aktiv (skippes i CLI) |
| SVG Support | Mediestøtte | Aktiv |
| WordPress Importer | Content sync | Aktiv (lokal) |
| acrylicon-multisite-sync | Custom plugin | Utviklet, ikke deployet |

## MU-plugins

| Plugin | Beskrivelse |
|--------|-------------|
| acrylicon-shared-taxonomies.php | Deler taxonomier på tvers av multisite |
