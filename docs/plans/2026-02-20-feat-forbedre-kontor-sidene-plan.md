---
title: "feat: Forbedre kontor-sidene"
type: feat
date: 2026-02-20
brainstorm: docs/brainstorms/2026-02-20-forbedre-kontor-sidene-brainstorm.md
trello: https://trello.com/c/3TJP5c1e
---

# Forbedre kontor-sidene

## Oversikt

Forbedre kontor-opplevelsen med to hovedfokus: (1) en forbedret oversiktsside (`page-locations.php`) med interaktivt Google Maps-kart og rikere kontorkort, og (2) konsistente individuelle kontorsider med komplett blokkstruktur og CTA.

Analytics viser at `/kontor` har hoy engasjement. Malet er at kontorsidene fungerer som lokale landingssider som bygger tillit OG driver kontakt.

## Bakgrunn

**Dagens tilstand:**
- Oversiktssiden (`page-locations.php`) viser enkle kort med navn, adresse, telefon for norske kontorer + internasjonale kontorer fra hardkodet array
- Ingen kart, ingen bilder, ingen lenker til kontorsider for norske kontorer
- Individuelle kontorsider er blokk-baserte men mangler konsistent struktur og CTA
- Ingen Google Maps-integrasjon finnes i kodebasen

**Etter forbedring:**
- Oversiktssiden far interaktivt Google Maps (4 norske kontorer), rike kontorkort med bilder, og bedre seksjonering
- Individuelle kontorsider har konsistent oppbygning: intro, staff, kontakt, referanser, CTA
- Tydelig CTA-knapp pa alle kontorsider

## Teknisk tilnarming

### Nye ACF-felter pa Kontor CPT

Legg til felter via ACF Pro admin UI (lagres i DB, ikke JSON-eksport):

| Felt | Type | Validering | Formal |
|------|------|------------|--------|
| `office_latitude` | Number | 55-72 (Norge) | Kartpin |
| `office_longitude` | Number | 3-32 (Norge) | Kartpin |
| `office_city` | Text | - | Kort by-navn pa kontorkort |

Eksisterende felter beholdes som de er: `office_adress` (merk stavefeil — bevisst), `office_tel`.

### Google Maps-integrasjon

- **API-nokkel:** Lagres som konstant i `wp-config.php`: `define('GOOGLE_MAPS_API_KEY', '...');`
- **Script-lasting:** Betinget — kun pa `page-locations.php` via `wp_enqueue_script` med `is_page_template()`-sjekk
- **Kart-oppforsel:** Vis info-vindu ved klikk pa pin (kontornavn, adresse, «View office»-lenke). IKKE umiddelbar navigering
- **Fallback:** Skjul kart-seksjonen gracefully hvis JS er deaktivert eller API feiler. Kontorkortene under fungerer uavhengig
- **Zoom/senter:** Auto-fit bounds basert pa de 4 pinnene
- **Styling:** Enkel custom stil som demper farger og matcher brand

### Oversiktssiden (page-locations.php) — Ny seksjonering

```
1. Hero (eksisterende — "Worldwide Locations")
2. Google Maps (NY — norske kontorer)
3. Norske kontorkort (FORBEDRET — bilde, navn, by, adresse, tlf, "View office"-lenke)
4. Internasjonale kontorer (EKSISTERENDE — flyttes ned, forbedret styling)
5. Kontakt CTA (EKSISTERENDE)
```

**Rekkefolgendring:** I dag vises internasjonale forst, sa norske. Ny rekkefølge: Norge forst (med kart), deretter internasjonale.

**Norske kontorkort-layout:**
```
+---------------------------+
|  [Featured image / foto]  |
+---------------------------+
|  AcryliCon Rogaland AS    |
|  Stavanger                |
|  Adresseveien 1, 4xxx     |
|  +47 xxx xx xxx            |
|                           |
|  [View office ->]         |
+---------------------------+
```

**Kryss-sprak-hensyn:** Oversiktssiden er pa Blog 1 (EN). Norske kontor-lenker gar til Blog 3 (`/no/kontor/{slug}/`). Lenketekst pa engelsk: "View office".

### Individuelle kontorsider — Blokkmal

Registrer en standard blokkmal (template) pa Kontor CPT i `functions.php`, slik det allerede er gjort for Referanser CPT. Dette sikrer konsistent struktur nar nye kontor-poster opprettes:

```php
'template' => [
    ['core/heading', ['level' => 2, 'placeholder' => 'Kontornavn / beskrivelse']],
    ['acf/office-staff-card'],
    ['acf/office-contact-card'],
    ['acf/showreel-reference-kontor'],
    ['core/buttons', [], [
        ['core/button', ['text' => 'Kontakt oss', 'url' => '/no/kontakt-oss/']],
    ]],
],
```

Eksisterende blokker gjenbrukes — ingen nye blokker trengs.

## Faseinndeling

### Fase 1: Data og oppsett
- [ ] Opprett Google Cloud-prosjekt og aktiver Maps JavaScript API
- [ ] Generer API-nokkel med HTTP-referrer-restriksjon (prod-domene + localhost)
- [ ] Legg til `GOOGLE_MAPS_API_KEY` i `wp-config.php` (lokal + prod)
- [ ] Opprett ACF-feltgruppe med `office_latitude`, `office_longitude`, `office_city`
- [ ] Fyll inn data for alle 4 norske kontorer (koordinater, by)
- [ ] Sett featured image pa alle 4 kontor-poster (eller skaff bilder)

### Fase 2: Oversiktssiden
- [ ] Restructurer `page-locations.php`: Norge forst, internasjonalt etter
- [ ] Bygg Google Maps-seksjon med betinget script-lasting
- [ ] Implementer info-vindu pa pin-klikk med kontor-info og lenke
- [ ] Bygg rike norske kontorkort (bilde, navn, by, adresse, tlf, lenke)
- [ ] Forbedre visuell styling pa internasjonale kontorkort
- [ ] Legg til noscript-fallback / graceful degradation for kart
- [ ] Kart: auto-fit bounds, custom pin-farge (#E2241C), subtil kartStyling
- [ ] Test responsivt: mobil (1-kolonne), tablet (2-kolonner), desktop (3-kolonner)

### Fase 3: Individuelle kontorsider
- [ ] Registrer blokkmal pa Kontor CPT i `functions.php`
- [ ] Oppdater innholdet pa alle 4 kontorsider i Gutenberg:
  - Intro/hero
  - Staff cards med riktig data
  - Office contact card
  - showreel-reference-kontor med riktig taksonomi-term valgt
  - CTA-knapp til kontaktsiden
- [ ] Verifiser at `showreel-reference-kontor` ACF-felt er satt pa alle 4 kontorer

### Fase 4: Test og deploy
- [ ] Test oversiktssiden pa lokal (localhost:8888)
- [ ] Test kontorsider pa lokal
- [ ] Test pa mobil (touch-interaksjon med kart, responsive layout)
- [ ] Bygg Tailwind CSS: `npm run build:css`
- [ ] Deploy tema til prod via rsync
- [ ] Legg til `GOOGLE_MAPS_API_KEY` i prod `wp-config.php`
- [ ] Opprett ACF-felter og fyll inn data pa prod (Blog 3)
- [ ] Tom cache etter deploy
- [ ] Verifiser pa prod

## Akseptansekriterier

- [ ] Oversiktssiden viser et interaktivt Google Maps med pins for alle 4 norske kontorer
- [ ] Klikk pa pin viser info-vindu med kontornavn og «View office»-lenke
- [ ] Norske kontorkort viser bilde, navn, by, adresse, telefon og lenke
- [ ] Internasjonale kontorer vises i forbedret layout under norske
- [ ] Alle 4 individuelle kontorsider har konsistent blokkstruktur
- [ ] CTA-knapp til kontaktsiden finnes pa alle kontorsider
- [ ] Kartet fungerer pa mobil (touch-interaksjon)
- [ ] Siden fungerer uten kart hvis JS er deaktivert (graceful fallback)
- [ ] Google Maps-script lastes KUN pa oversiktssiden (ikke globalt)

## Avhengigheter og risiko

| Risiko | Konsekvens | Mitigering |
|--------|-----------|------------|
| Google Maps API-nokkel-oppsett | Krever Google Cloud-konto med billing | Gratis tier dekker ~28 000 lastinger/mnd |
| Manglende kontorbilder | Tomme/okkede kort | Bruk placeholder eller by-bilder midlertidig |
| Kryss-sprak navigering (EN -> NO) | Engelsktalende besokende havner pa norsk side | Akseptert kompromiss — kontorinnhold finnes kun pa Blog 3 |
| `office_adress` stavefeil | Forvirring for utviklere | Dokumenter i kode-kommentar, fiks ikke na |

## Viktige gotchas (fra docs/solutions/)

1. **Feltnavnet er `office_adress`** (ikke `office_address`) — bruk riktig stavelse!
2. **Tailwind-farger bruker `acryl-`-prefiks:** `bg-acryl-red`, `bg-acryl-dark-blue` osv.
3. **`switch_to_blog()` — valider forst:** `if (get_blog_details(3)) : switch_to_blog(3);`
4. **E-post-beskyttelse:** Bruk `antispambot()` for alle e-postadresser
5. **Kart-script betinget:** Last kun pa locations-side for a unnga ytelsespavirkning globalt
6. **Etter deploy:** Tom WP Fastest Cache + flush rewrite rules pa begge blogger

## Referanser

### Filer som endres
- `themes/acrylicon-2024/page-locations.php` — hovedarbeidet
- `themes/acrylicon-2024/functions.php` — blokkmal-registrering, betinget script-enqueue
- `wp-config.php` (lokal + prod) — Google Maps API-nokkel

### Filer som gjenbrukes (uendret)
- `themes/acrylicon-2024/single-kontor.php` — forblir minimal
- `themes/acrylicon-2024/blocks/office-contact-card/` — eksisterende blokk
- `themes/acrylicon-2024/blocks/office-staff-card/` — eksisterende blokk
- `themes/acrylicon-2024/blocks/showreel-reference-kontor/` — eksisterende blokk
- `themes/acrylicon-2024/inc/international-offices.php` — hardkodet data

### Ekstern dokumentasjon
- [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript)
- [ACF Pro felter](https://www.advancedcustomfields.com/resources/)

### Brainstorm
- `docs/brainstorms/2026-02-20-forbedre-kontor-sidene-brainstorm.md`
