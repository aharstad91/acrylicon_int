# Brainstorm: Forbedre kontor-sidene

**Dato:** 2026-02-20
**Trello:** [Forbedre kontor-sidene](https://trello.com/c/3TJP5c1e)
**Status:** Brainstorm ferdig — klar for plan

---

## Hva vi bygger

Forbedre kontor-opplevelsen på Acrylicon-nettstedet med to hovedfokus:

1. **Ny og bedre oversiktsside (page-locations.php)** — Interaktivt kart med alle kontorer (norske + internasjonale), rikere kontorkort med bilder og info, bedre navigasjon.
2. **Konsistente individuelle kontorsider** — Sørge for at alle kontorsider har komplett innhold via Gutenberg-blokker: intro, staff cards, kontaktinfo, lokale referanser, og CTA-knapp til kontaktsiden.

### Mål
- **Bygge lokal tillit** — Besøkende skal se at Acrylicon har kompetent, lokalt nærvær
- **Drive kontakt** — Gjøre det enkelt å ta neste steg med rett kontor
- Kontorsidene som **lokale landingssider** som både informerer og konverterer

---

## Hvorfor denne tilnærmingen

**Oversiktssiden (page-locations.php):** Allerede template-drevet — forbedres direkte i PHP. Interaktivt kart gir visuell oversikt over dekning og styrker inntrykket av nasjonal tilstedeværelse.

**Individuelle kontorsider:** Forblir blokk-baserte (Gutenberg + ACF). Konsistent med resten av nettstedet. Eksisterende blokker (`office-contact-card`, `office-staff-card`, `showreel-reference-kontor`) fungerer bra — det handler om å sørge for at de brukes konsistent og med godt innhold.

**Datamodell beholdes:** Norske kontorer = CPT, internasjonale = hardkodet array. Ingen migrering nødvendig.

---

## Nøkkelbeslutninger

### 1. Interaktivt kart på oversiktssiden
- **Google Maps JavaScript API** — kjent UX, god geocoding
- Kartet viser kun **norske kontorer** (4 pins) — holder det fokusert
- Klikk på pin → gå til kontorsiden for det kontoret
- Internasjonale kontorer vises som liste under kartet (som i dag, men forbedret)
- Ingen kart på individuelle kontorsider — kun oversiktssiden

### 2. Kartdata via ACF-felter
- Legg til `office_latitude` og `office_longitude` ACF-felter på kontor-CPT
- Fylles inn manuelt per kontor (4 kontorer = overkommelig)
- Oversikts-templaten henter koordinater fra ACF og sender til Google Maps JS

### 3. Oversiktssiden forbedres i template
- `page-locations.php` bygges ut med:
  - Interaktivt Google Maps-kart (norske kontorer)
  - Rikere kontorkort med bilde, kontornavn, sted, adresse, telefon, og «Se kontor →»-lenke
  - Tydeligere seksjonering Norge vs internasjonalt
- Beholder eksisterende datakilder (CPT + hardkodet array)
- Kontorkort-layout: bilde øverst, info under (card-style)

### 4. Individuelle kontorsider bygges med blokker
- Ingen endring i template (`single-kontor.php` forblir minimal/blokk-basert)
- Konsistent blokkoppsett: hero/intro → staff cards → kontaktinfo → lokale referanser → CTA
- `showreel-reference-kontor`-blokken brukes som den er for lokale referanser
- CTA-knapp til kontaktsiden i bunnen

### 5. CTA-strategi
- Tydelig CTA-knapp som linker til hovedkontaktsiden
- Ikke kontaktskjema direkte på kontorsiden — holder det enkelt

---

## Scope / Hva er IKKE inkludert

- Migrering av internasjonale kontorer til CPT (eventuelt senere)
- Kontaktskjema direkte på kontorsidene
- Kart på individuelle kontorsider
- Nye ACF-blokker (eksisterende blokker dekker behovene)

---

## Åpne spørsmål for planfasen

1. **Google Maps API-nøkkel:** Trenger en API-nøkkel med Maps JavaScript API aktivert. Har vi dette allerede, eller må det opprettes?
2. **Oversiktssiden — bilder:** Har vi bilder av kontorene/byene for kontorkortene, eller må vi skaffe?
3. **Mobil-opplevelse:** Hvordan fungerer kartet på mobil? Fullbredde, eller skjult med «Vis kart»-knapp?
4. **Thumbnail på kontor-CPT:** Trenger vi et eget ACF-felt for kontorbilde, eller bruke featured image?

---

## Eksisterende infrastruktur som gjenbrukes

| Komponent | Brukes til |
|-----------|-----------|
| `page-locations.php` | Oversiktsside-template (forbedres) |
| `single-kontor.php` | Individuell kontorside (beholdes) |
| `office-contact-card` blokk | Kontaktinfo-kort på kontorsider |
| `office-staff-card` blokk | Ansatte-profiler på kontorsider |
| `showreel-reference-kontor` blokk | Lokale referanser filtrert per kontor |
| `referanser-kontor` taksonomi | Kobling mellom referanser og kontorer |
| `inc/international-offices.php` | Hardkodet data for internasjonale kontorer |
| ACF-felter: `office_adress`, `office_tel` | Adresse og telefon per kontor |
| **Nye ACF-felter (planlagt):** `office_latitude`, `office_longitude` | Koordinater for kartpins |
