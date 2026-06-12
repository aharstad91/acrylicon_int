---
title: "feat: Map acrylicon.com → blog 1 og acrylicon.no → blog 3 (sunrise.php domene-mapping)"
type: feat
status: active
date: 2026-05-27
---

# feat: Multisite domene-mapping — acrylicon.com + acrylicon.no

## Overview

WordPress-installasjonen kjører som subdirectory-multisite på Servebolt, i dag eksponert på `acryli-28355.jana-osl.servebolt.cloud` (blog 1 = engelsk på `/`, blog 3 = norsk på `/no/`). Vi skal mappe to ekte domener:

- **acrylicon.com → blog 1 (engelsk)**, servert på rot
- **acrylicon.no → blog 3 (norsk)**, servert på rot (ikke `/no/`)

Hosting forblir på Servebolt. Selve domene-bindingen kan *ikke* løses med DNS alene: fordi det er et **subdirectory**-nettverk, klarer ikke WP-kjernen å servere en underkatalog-site (`/no/`) på sitt eget rot-domene. Det krever en `sunrise.php` som pinner nettverket og ruter innkommende domener til riktig blog, kombinert med DB- og wp-config-endringer. servebolt.cloud-domenet beholdes som teknisk backend/fallback for admin og rollback.

## Problem Frame

Per i dag (verifisert mot prod 2026-05-27):

```
wp_site (network):  acryli-28355.jana-osl.servebolt.cloud  /
wp_blogs[1] (EN):   acryli-28355.jana-osl.servebolt.cloud  /
wp_blogs[3] (NO):   acryli-28355.jana-osl.servebolt.cloud  /no/
blog1 home/siteurl: https://acryli-28355.jana-osl.servebolt.cloud
blog3 home/siteurl: https://acryli-28355.jana-osl.servebolt.cloud/no
wp-config:          SUBDOMAIN_INSTALL=false, DOMAIN_CURRENT_SITE=<servebolt>,
                    COOKIE_DOMAIN=<servebolt>, ingen SUNRISE, ingen sunrise.php
```

Mål-tilstand:

```
wp_site (network):  acrylicon.com  /
wp_blogs[1] (EN):   acrylicon.com  /
wp_blogs[3] (NO):   acrylicon.no   /        ← flyttet fra /no/ til rot
blog1 home/siteurl: https://acrylicon.com
blog3 home/siteurl: https://acrylicon.no
wp-config:          DOMAIN_CURRENT_SITE=acrylicon.com, COOKIE_DOMAIN='',
                    SUNRISE='on', sunrise.php aktiv
```

`sunrise.php` ruter alle tre vertsnavn (acrylicon.com, acrylicon.no, og servebolt.cloud som fallback) til riktig blog under det ene nettverket.

## Requirements Trace

- **R1.** acrylicon.com serverer blog 1 (engelsk) på rot, med HTTPS.
- **R2.** acrylicon.no serverer blog 3 (norsk) på rot (ikke `/no/`), med HTTPS.
- **R3.** Alle interne lenker, canonical, OG, schema, sitemaps og språkbytter peker til riktig nytt domene per blog.
- **R4.** servebolt.cloud forblir tilgjengelig for admin/backend og fungerer som rollback-vei.
- **R5.** Innlogging/cookies fungerer på begge nye domener (ikke låst til ett domene).
- **R6.** Gamle servebolt.cloud-URLer 301-redirectes til kanonisk domene (unngå duplikat-innhold/SEO-tap).
- **R7.** Endringen er reverserbar uten datatap.

## Scope Boundaries

- **Ikke** migrering av hosting bort fra Servebolt — alt forblir på samme server.
- **Ikke** endring av multisite fra subdirectory til subdomain.
- **Ikke** endring av innholdsstruktur, CPT-slugs eller taksonomier.
- **Ikke** håndtering av e-post-DNS (MX/SPF/DKIM) — kun web (A/CNAME) for de to domenene. Bekreft separat at e-post ikke påvirkes.

### Deferred to Separate Tasks

- 301-redirect-regler fra eventuelle *eldre* domener (f.eks. tidligere acrylicon.no-URL-struktur) utover servebolt.cloud: kartlegges separat hvis aktuelt.
- Oppdatering av eksterne integrasjoner (Google Search Console properties, Analytics, backlinks) — egen oppfølging etter cutover.

## Context & Research

### Relevant Code and Patterns

- `wp-config.php` (prosjektrot på prod: `.../site/public/wp-config.php`) — multisite-konstanter, linje 92–102.
- `wp-content/themes/acrylicon-2024/inc/language-switcher.php` — bygger cross-language-URLer via `switch_to_blog()` + `home_url()` (dynamisk, følger domenet). Linje 29 har vestigial `'prefix' => '/no/'` som blir uvirksom når blog 3 flyttes til rot (path-stripping i else-grenen finner ingen `/no/` å fjerne — harmløst, men bør ryddes).
- `wp-content/mu-plugins/acrylicon-seo/` — canonical, OG, schema, sitemap. Bruker `home_url()` overalt → følger nytt domene automatisk. `modules/class-sitemap-integration.php` håndterer sitemaps.
- `wp-content/mu-plugins/acrylicon-seo/data/organization.php` — hardkodet `info@acrylicon.no` (e-post, ikke URL — uberørt).
- `.htaccess` på prod — har allerede multisite-rewrites + 301 `/norway/` → `/no/`. Ny redirect-regel legges her.
- Ingen eksisterende `sunrise.php` eller domene-mapping-plugin (verifisert lokalt + prod).

### Institutional Learnings

- CLAUDE.md «Pull DB fra prod»-seksjonen dokumenterer at multisite-tabellene (`wp_blogs`/`wp_site`) lagrer domain/path separat og må fikses eksplisitt ved URL-bytte — samme mekanikk gjelder her, motsatt vei.
- SEO-modulen erstattet Yoast (se `2026-02-25-feat-custom-seo-module-replace-yoast-plan.md`) — `acrylicon-seo` mu-plugin er autoritativ for canonical/sitemap, ikke Yoast.

### External References

- WordPress Advanced Administration: Multisite Domain Mapping (developer.wordpress.org) — `sunrise.php` kjører før DB-init i `ms-settings.php`; `WP_Network`/`WP_Site`-klassene er tilgjengelige der.
- Beste praksis bekreftet: definer `COOKIE_DOMAIN` som tom streng for fler-domene-støtte; map til blog-**rot** fremfor underkatalog-sti (sti-mapping krever REQUEST_URI/PATH_INFO-rewriting og er skjør). Derfor flytter vi blog 3 til path `/`.
- Test mapping via `curl --resolve` eller midlertidig hosts-fil **før** DNS-cutover.

## Key Technical Decisions

- **Behold servebolt.cloud som nettverkets fallback-vert.** `sunrise.php` mapper servebolt.cloud → blog 1 i tillegg til de ekte domenene, slik at admin/backend alltid er tilgjengelig og rollback er triviell (deaktiver SUNRISE).
- **Flytt blog 3 fra `/no/` til path `/`.** Nødvendig for å servere norsk på acrylicon.no-rot uten skjør sti-rewriting. `home`/`siteurl` for blog 3 oppdateres til `https://acrylicon.no`.
- **`COOKIE_DOMAIN = ''` (tom).** Lar WP sette cookies per forespurt domene → innlogging fungerer på både .com og .no. Den nåværende hardkodede servebolt-verdien ville ellers brutt cookies på de nye domenene.
- **`sunrise.php` bruker en eksplisitt host→blog-mapping** (få domener, ingen behov for dynamisk plugin). Pinner `$current_site` til nettverk 1 og setter `$current_blog`/`$blog_id`/`$site_id`.
- **Kanonisk nettverksdomene = acrylicon.com.** `wp_site.domain` + `DOMAIN_CURRENT_SITE` settes til acrylicon.com; servebolt.cloud lever videre kun via sunrise-fallback.
- **DNS gjøres med lav TTL først.** Sett TTL lavt (f.eks. 300s) i forkant for rask rollback, hev igjen etter stabilisering.

## Open Questions

### Resolved During Planning

- *Holder DNS alene?* Nei — subdirectory-multisite krever `sunrise.php` + DB/config-endringer for å servere `/no/`-siten på eget rot-domene.
- *Trenger blog 1 sunrise?* Strengt tatt nei (nettverksrot), men sunrise håndterer den også for enhetlig ruting og servebolt-fallback.
- *Hva med cookies på to domener?* Løst ved `COOKIE_DOMAIN=''`.

### Deferred to Implementation

- **Eksakt Servebolt-prosedyre for å legge til domener/aliaser + SSL** — avhenger av Servebolt-kontrollpanelets aktuelle UI; utføres av den som har panel-tilgang. Verifiser at Let's Encrypt-sertifikat utstedes for begge domener (inkl. www hvis ønsket).
- **Hvorvidt `www`-varianter skal støttes/redirectes** — bekreft preferanse (canonical uten www antas). Mapping i sunrise + redirect-regel justeres deretter.
- **Om noen ACF-felt/innhold inneholder absolutte servebolt-URLer utover standard** — verifiseres med en tørr-kjørt `search-replace` (`--dry-run`) før reell kjøring.

## High-Level Technical Design

> *Dette illustrerer tiltenkt tilnærming og er retningsgivende for review, ikke en implementasjonsspesifikasjon. Implementerende agent skal behandle det som kontekst, ikke kode å reprodusere.*

**Forespørselsflyt etter mapping:**

```
Browser → DNS (acrylicon.no A/CNAME → Servebolt IP)
        → Servebolt (domene-alias + SSL terminering)
        → PHP / WordPress bootstrap
            ms-settings.php → inkluderer sunrise.php (SUNRISE='on')
                sunrise.php:
                  host = "acrylicon.no"
                  map[host] = blog 3
                  $current_site  = WP_Network::get_instance(1)   // nettverket
                  $current_blog  = get_site(3)                    // path '/'
                  $blog_id = 3; $site_id = 1
            → WP fortsetter, hopper over egen domene-deteksjon
            → home_url() = https://acrylicon.no  (fra blog 3 options)
```

**Domene→blog-mapping (sunrise):**

| Vertsnavn | Blog | Site (nettverk) | Servert path |
|-----------|------|-----------------|--------------|
| acrylicon.com / www | 1 (EN) | 1 | / |
| acrylicon.no / www | 3 (NO) | 1 | / |
| acryli-28355…servebolt.cloud | 1 (EN, fallback) | 1 | / |

**Utkast `sunrise.php`** (retningsgivende — må testes på lokal/staging med hosts-fil før DNS-cutover):

```php
<?php
/**
 * Acrylicon multisite domain mapping.
 * Kjorer i ms-settings.php for WP bestemmer nettverk/blog.
 * Ruter kjente vertsnavn til riktig blog under nettverk 1.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Eksplisitt host -> blog_id. www-varianter normaliseres bort under.
$acrylicon_domain_map = [
	'acrylicon.com'                          => 1,
	'acrylicon.no'                           => 3,
	'acryli-28355.jana-osl.servebolt.cloud'  => 1, // fallback/admin
];

$acrylicon_host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( $_SERVER['HTTP_HOST'] ) : '';
$acrylicon_host = preg_replace( '/:\d+$/', '', $acrylicon_host );   // strip port
$acrylicon_host = preg_replace( '/^www\./', '', $acrylicon_host );  // strip www

if ( isset( $acrylicon_domain_map[ $acrylicon_host ] ) ) {
	$acrylicon_blog_id = (int) $acrylicon_domain_map[ $acrylicon_host ];

	// WP_Site / WP_Network er lastet for sunrise inkluderes.
	$blog = get_site( $acrylicon_blog_id );
	if ( $blog ) {
		$current_blog = $blog;
		$current_site = WP_Network::get_instance( (int) $blog->site_id );
		$blog_id      = (int) $blog->blog_id;
		$site_id      = (int) $blog->site_id;
		// Tilgjengelig for resten av bootstrap.
		$current_site->blog_id = $blog_id;
	}
}
// Ukjent host: ikke sett noe -> WP faller tilbake til standard deteksjon.
```

## Implementation Units

- [ ] **Unit 1: Forarbeid — backup, lav TTL, lokal speiling**

**Goal:** Etabler trygg utgangspunkt og testmiljø før noen endring.

**Requirements:** R7

**Dependencies:** Ingen

**Files:** Ingen kodeendringer (drift/forberedelse)

**Approach:**
- Full DB-backup på prod (`wp db export`) + kopi av `wp-config.php` og `.htaccess`.
- Senk DNS-TTL for acrylicon.com og acrylicon.no til ~300s i god tid før cutover (rask rollback).
- Speil prod-DB lokalt (MAMP) per CLAUDE.md-prosedyren for å teste sunrise + DB-endringer uten å røre prod.

**Test scenarios:**
- Happy path: DB-eksport gjenopprettes i et engangs-test → tabeller intakte.
- Verifisering: `dig`/`nslookup` viser TTL ≤ 300 for begge domener før cutover.

**Verification:** Backup-fil eksisterer og er importerbar; TTL bekreftet lav; lokal speiling kjører.

- [ ] **Unit 2: Servebolt — legg til domener + SSL**

**Goal:** Servebolt-siten svarer på acrylicon.com og acrylicon.no med gyldig HTTPS.

**Requirements:** R1, R2

**Dependencies:** Unit 1

**Files:** Ingen (Servebolt-kontrollpanel)

**Approach:**
- Legg til acrylicon.com og acrylicon.no (+ ev. www) som domener/aliaser på Servebolt-siten.
- Utløs/verifiser Let's Encrypt-sertifikat for begge.
- Utføres av den med panel-tilgang; eksakt UI-flyt avklares ved utførelse (deferred).

**Test scenarios:**
- Verifisering: `curl --resolve acrylicon.no:443:<servebolt-ip> https://acrylicon.no` gir TLS-handshake uten sertifikatfeil (før DNS er live).

**Verification:** Begge domener har gyldig sertifikat på Servebolt; ingen TLS-advarsel ved `--resolve`-test.

- [ ] **Unit 3: sunrise.php — domene-ruting**

**Goal:** WordPress ruter kjente vertsnavn til riktig blog under nettverk 1.

**Requirements:** R1, R2, R4

**Dependencies:** Unit 1 (lokal test først)

**Files:**
- Create: `wp-content/sunrise.php`

**Approach:**
- Implementer host→blog-mapping per utkastet i High-Level Technical Design.
- Normaliser bort port og `www`.
- Sett `$current_blog`, `$current_site`, `$blog_id`, `$site_id`. Ukjente verter: ingen overstyring (graceful fallback).
- Test lokalt via hosts-fil/`curl --resolve` *før* deploy.

**Patterns to follow:** Mercator/WP-CLI domain-mapping sunrise-mønster (sett bootstrap-globaler, ikke filtre — plugins er ikke lastet ennå).

**Test scenarios:**
- Happy path: host=acrylicon.no → `get_current_blog_id()` = 3, `home_url()` = https://acrylicon.no.
- Happy path: host=acrylicon.com → blog 1, `home_url()` = https://acrylicon.com.
- Edge case: host med port (`acrylicon.no:443`) → normaliseres → blog 3.
- Edge case: host=www.acrylicon.no → blog 3 (www strippet).
- Edge case: ukjent host → ingen fatal; WP standard-deteksjon overtar.
- Integration: forespørsel til acrylicon.no/produkter/ → serverer blog 3-innhold på rot (ikke 404, ikke /no/-redirect-loop).

**Verification:** Lokalt med hosts-fil: begge domener serverer riktig blog på rot; ukjent host kræsjer ikke.

- [ ] **Unit 4: wp-config — SUNRISE, COOKIE_DOMAIN, DOMAIN_CURRENT_SITE**

**Goal:** Aktiver sunrise og gjør cookie/domene-konstanter fler-domene-vennlige.

**Requirements:** R1, R2, R5

**Dependencies:** Unit 3

**Files:**
- Modify: `wp-config.php` (prod: `.../site/public/wp-config.php`)

**Approach:**
- `define('SUNRISE', 'on');` (før `wp-settings.php`-require).
- `DOMAIN_CURRENT_SITE` → `'acrylicon.com'`.
- `COOKIE_DOMAIN` → `''` (tom) eller fjern definisjonen helt.
- Behold `SUBDOMAIN_INSTALL=false`, `PATH_CURRENT_SITE='/'`.

**Test scenarios:**
- Integration: etter endring + sunrise aktiv, innlogging på acrylicon.com setter cookie for acrylicon.com (ikke servebolt) og admin er tilgjengelig.
- Edge case: innlogging på acrylicon.no fungerer uavhengig av .com-sesjon.

**Verification:** `define('SUNRISE','on')` lest av WP (sunrise kjører); wp-admin tilgjengelig på begge domener; ingen cookie-domene-feil i nettleser.

- [ ] **Unit 5: DB — wp_site, wp_blogs, sitemeta, blog-options**

**Goal:** Multisite-tabeller og per-blog URLer reflekterer de nye domenene; blog 3 flyttet til rot.

**Requirements:** R1, R2, R3

**Dependencies:** Unit 4

**Files:** Ingen kodefiler (DB-endringer via WP-CLI/`db query`)

**Approach (rekkefølge viktig — kjør lokalt først, så prod):**
- `wp_site` (id=1): domain=`acrylicon.com`, path=`/`.
- `wp_sitemeta` `siteurl` → `https://acrylicon.com/`.
- `wp_blogs[1]`: domain=`acrylicon.com`, path=`/`.
- `wp_blogs[3]`: domain=`acrylicon.no`, path=`/` (endret fra `/no/`).
- blog 1 options: `home`/`siteurl` → `https://acrylicon.com`.
- blog 3 options: `home`/`siteurl` → `https://acrylicon.no`.
- `rewrite flush` for begge blogs.

**Patterns to follow:** CLAUDE.md «Pull DB»-seksjonens `wp_blogs`/`wp_site`-UPDATE-mønster (domain/path separat).

**Test scenarios:**
- Happy path: etter UPDATE serverer acrylicon.no front-page for blog 3 på rot.
- Edge case: gamle `/no/`-stier på acrylicon.no gir ikke dobbel-prefiks (`/no/no/`).
- Integration: permalinks for CPT (produkter, referanser) på begge blogs genererer korrekt nytt domene.

**Verification:** `wp db query` viser nye domain/path; front + en CPT-side laster på begge domener uten 404/redirect-loop.

- [ ] **Unit 6: Innhold — search-replace av absolutte servebolt-URLer**

**Goal:** Innhold/ACF/options inneholder ingen gjenværende absolutte servebolt.cloud-URLer (utenom bevisst fallback).

**Requirements:** R3

**Dependencies:** Unit 5

**Files:** Ingen kodefiler (DB `search-replace`)

**Approach:**
- Tørr-kjør (`--dry-run`) `search-replace` per blog:
  - blog 1: `https://acryli-28355.jana-osl.servebolt.cloud` → `https://acrylicon.com` (ekskluder `/no/`-stier).
  - blog 3: `https://acryli-28355.jana-osl.servebolt.cloud/no` → `https://acrylicon.no`.
- Kjør reell replace med `--precise --all-tables-with-prefix` etter verifisert dry-run. Pass på rekkefølge (lengste streng `/no` først for blog 3) for å unngå dobbel-erstatning.

**Test scenarios:**
- Happy path: lenker i innhold/menyer peker til nytt domene.
- Edge case: blog 3-URLer med `/no` blir `acrylicon.no` (ikke `acrylicon.no/no`).
- Edge case: serialisert ACF-data forblir gyldig (`--precise` håndterer lengde).

**Verification:** Grep i rendret HTML viser ingen servebolt.cloud-URLer (unntatt ev. bevisst); ACF-felt laster normalt.

- [ ] **Unit 7: Redirects + språkbytter-opprydding**

**Goal:** Gamle URLer 301-redirectes til kanonisk domene; vestigial `/no/`-prefiks i språkbytter ryddet.

**Requirements:** R6

**Dependencies:** Unit 5

**Files:**
- Modify: `.htaccess` (prod-rot)
- Modify: `wp-content/themes/acrylicon-2024/inc/language-switcher.php`

**Approach:**
- `.htaccess`: 301 fra `acryli-28355.jana-osl.servebolt.cloud` → riktig kanonisk domene (.com for rot, .no for tidligere `/no/`-stier). Legg over eksisterende multisite-rewrites.
- Behold servebolt.cloud tilgjengelig for admin (ikke redirect `/wp-admin`/`/wp-login.php`).
- Fjern/oppdater `'prefix' => '/no/'`-antagelsen i language-switcher (linje 29 + else-grenen ~138–141), siden blog 3 nå er på rot.

**Test scenarios:**
- Happy path: `GET https://…servebolt.cloud/produkter/` → 301 → `https://acrylicon.no/produkter/`.
- Happy path: språkbytter NO→EN på acrylicon.no/produkter/ → acrylicon.com/products/.
- Edge case: `/wp-admin` på servebolt.cloud redirectes IKKE (backend-tilgang bevart).
- Edge case: språkbytter på forside og på 404 gir korrekt fallback-URL på riktig domene.

**Verification:** `curl -I` bekrefter 301 på offentlige stier, 200 på `/wp-admin`; språkbytter lenker korrekt på begge domener.

- [ ] **Unit 8: Verifisering, SEO og cutover-avslutning**

**Goal:** Helhetlig verifisering etter DNS-cutover; SEO-output korrekt; TTL hevet igjen.

**Requirements:** R1–R6

**Dependencies:** Unit 2–7 ferdig + DNS pekt om

**Files:** Ingen kodefiler

**Approach:**
- Pek DNS (A/CNAME) for begge domener til Servebolt; vent på propagering.
- Verifiser canonical/OG/schema/hreflang i rendret `<head>` på begge domener (acrylicon-seo).
- Verifiser `wp-sitemap.xml`/SEO-sitemaps bruker nye domener; gjør klar resubmit i Google Search Console (egen oppfølging).
- Hev DNS-TTL tilbake til normal etter stabil drift.
- Tøm Servebolt-cache (per CLAUDE.md).

**Test scenarios:**
- Integration: begge domener laster front + undersider med HTTPS, korrekt språk, ingen mixed-content.
- Happy path: canonical på acrylicon.no-side = acrylicon.no-URL (ikke servebolt/.com).
- Edge case: sitemap-URLer og interne lenker i sitemap bruker riktig domene per blog.

**Verification:** Begge domener fullt funksjonelle (front, CPT, søk, skjema, språkbytter); SEO-head korrekt; ingen servebolt-lekkasje i canonical/sitemap.

## System-Wide Impact

- **Interaction graph:** `sunrise.php` kjører før *alt* annet i multisite-bootstrap — en feil her tar ned begge sitene samtidig. Påvirker enhver forespørsel.
- **Error propagation:** Ukjent host i sunrise må falle tilbake gracefully (ingen overstyring), ikke fatal.
- **State lifecycle risks:** DB-UPDATE av `wp_blogs`/`wp_site` + options må skje i riktig rekkefølge og helst i én økt; halvveis tilstand gir blandede URLer. Object cache / Servebolt-cache må tømmes etter.
- **API surface parity:** `home_url()`/`switch_to_blog()`-baserte komponenter (SEO, språkbytter, locations-side) følger nytt domene automatisk — ingen kodeendring nødvendig der.
- **Unchanged invariants:** Subdirectory-multisite-modellen, CPT-slugs, taksonomier, sync-plugin og post-IDer på tvers av blogs er uendret. Språkbytteren er avhengig av delte post-IDer — dette berøres ikke.

## Risks & Dependencies

| Risk | Mitigation |
|------|------------|
| Feil i `sunrise.php` tar ned begge sitene | Test grundig lokalt/staging med hosts-fil/`curl --resolve` før deploy; behold servebolt-fallback + enkel rollback (fjern SUNRISE-konstant) |
| DNS-propagering treg → nedetid | Senk TTL i forkant (Unit 1); cutover i lavtrafikk-vindu |
| SSL ikke utstedt i tide for nye domener | Verifiser sertifikat via `curl --resolve` før DNS pekes om (Unit 2) |
| Cookie/innlogging brytes på nye domener | `COOKIE_DOMAIN=''` (Unit 4); test admin-login på begge domener |
| search-replace dobbel-erstatter `/no` | Tørr-kjør først; lengste streng først; `--precise` for serialisert data |
| SEO-tap fra duplikat servebolt-innhold | 301-redirect servebolt → kanonisk (Unit 7); resubmit sitemaps i GSC |
| Halvveis DB-tilstand | Backup (Unit 1); kjør DB-endringer samlet; tøm cache etter |

## Rollback

1. Fjern/kommenter ut `define('SUNRISE','on')` i wp-config (og gjenopprett `COOKIE_DOMAIN`/`DOMAIN_CURRENT_SITE`).
2. Gjenopprett `wp_site`/`wp_blogs`/sitemeta/options til servebolt-verdier (fra backup eller revers-UPDATE).
3. `rewrite flush` + tøm cache.
4. servebolt.cloud fungerer igjen som primær. (DNS for de nye domenene kan stå — de bare slutter å rute korrekt til de er reaktivert.)

## Documentation / Operational Notes

- Oppdater CLAUDE.md «Quick Reference» og «Multisite-struktur» med de nye domenene etter vellykket cutover.
- Logg cutover i WORKLOG.md.
- Etter cutover: oppdater Google Search Console (nye properties + sitemaps), Analytics, og eventuelle eksterne lenker/integrasjoner.
- Bekreft at e-post-DNS (MX/SPF/DKIM) for acrylicon.no/.com IKKE forstyrres av web-DNS-endringene.

## Sources & References

- Verifisert prod-tilstand: `wp_blogs`, `wp_site`, `wp_options`/`wp_3_options` (SSH + WP-CLI, 2026-05-27).
- `wp-config.php` (lokal + prod), `.htaccess` (prod).
- `wp-content/themes/acrylicon-2024/inc/language-switcher.php`, `wp-content/mu-plugins/acrylicon-seo/`.
- WordPress Advanced Administration — Multisite Domain Mapping (developer.wordpress.org).
- Relatert plan: `docs/plans/2026-02-25-feat-custom-seo-module-replace-yoast-plan.md`.
