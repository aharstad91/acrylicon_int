# PROJECT-LOG – AcryliCon

> Strategisk prosjektlogg. Oppdateres etter hver /full-sesjon.

---

## 2026-02-11 – Multisite-sync plugin deployet til produksjon

### Beslutninger
- **Security fix #006 implementert:** Lagt til `validate_file()` i media handler med 3-stegs validering (extension + MIME, WordPress whitelist, getimagesize for bilder). Filer valideres FØR kopiering mellom sites.
- **Todo #004 nedgradert fra P1 til P2:** Refererer til R2-sync-kode som ikke eksisterer ennå. Blir relevant først når R2-integrasjon (#008) bygges.
- **Plugin deployet:** `acrylicon-multisite-sync` og `acrylicon-shared-taxonomies.php` (mu-plugin) nå aktive på produksjon.
- **Gravity Forms/SuperOffice:** SuperOffice beholdes som CRM inntil videre. Gravity Forms-integrasjon parkert.

### Resultater
- Plugin: Aktivert uten feil på PHP 8.4
- MU-plugin: Automatisk lastet (shared taxonomies mellom sites)
- Sikkerhet: Media handler blokkerer nå ugyldige filtyper, polyglot-angrep, og ikke-tillatte MIME-typer

### Observasjoner
- **Pluginet er kompakt og godt strukturert:** 696 linjer fordelt på 6 klasser. Draft-first sync pattern med cleanup på feil. Solid fundament.
- **6 av 8 P1 todos er R2-relaterte:** #001, #002, #003, #005, #007, #008 handler alle om Cloudflare R2 som ikke er satt opp ennå. Disse er P1 for R2-funksjonalitet, men irrelevante inntil R2 faktisk bygges. Vurder bulk-nedgradering til P2.
- **Produksjon er nå i sync med lokal:** Tema (Tailwind), plugin (multisite-sync), og mu-plugin (shared-taxonomies) er alle deployet.

### Parkert / Åpne spørsmål
- **R2-relaterte todos (#001-003, #005, #007-008):** Bør nedgraderes til P2. De er reelle issues, men for kode/infrastruktur som ikke finnes ennå.
- **Automatisk deploy-pipeline:** Manuell scp fungerer, men er feilbar. Bør vi sette opp en enkel deploy-script?
- **Plugin-testing i prod:** Ingen referanser er synkronisert ennå. Bør teste sync-funksjonaliteten med ett testinnlegg.

### Retning
**Infrastrukturen er nå komplett:** HTTPS, Tailwind, PageSpeed 99/100, multisite-sync plugin — alt er på plass. Fundamentet er solid.

Neste naturlige steg er **forretningsverdi**, ikke mer infrastruktur:
1. **SEO 69 → 90+** — Meta descriptions, Open Graph, JSON-LD. Direkte synlighet.
2. **Test sync i prod** — Synkroniser en referanse fra hovedsite til Norway. Bevis at pluginet fungerer.
3. **Gravity Forms** — Erstatte SuperOffice iFrames når tiden er inne.

Det harde spørsmålet gjenstår: *Bruker vi mer tid på .no, eller starter vi .com (Fase 2)?* Med alt på plass nå er .no i svært god stand. SEO er den siste lavthengende frukten som gir direkte forretningsverdi.

---

## 2026-02-11 – PageSpeed 69 → 99 (mobil) / 100 (desktop)

### Beslutninger
- **Deferred non-critical CSS:** gravity.css, swiper.css og block-panels.css lastes nå med `media="print" onload="this.media='all'"`. Fjerner dem fra render-blocking chain uten å miste funksjonalitet.
- **Deferred jQuery:** La til `defer`-attributt på jquery.min.js og jquery-migrate.min.js via `script_loader_tag`-filter. Trygt fordi ingen inline jQuery brukes på forsiden — alt jQuery-avhengig er i scripts.js (footer).
- **WebP-konvertering og auto-serving:** Konverterte nøkkelbilder på forsiden med `cwebp` (quality 80). La til `.htaccess`-rewrite som automatisk serverer .webp-filer når browser sender `Accept: image/webp`. Hero-bilde: 862 KiB PNG → 51 KiB WebP (94% reduksjon).
- **Tidligere i sesjonen:** Fjernet duplisert ScrollReveal (unpkg), IE-fix script, hardcoded Swiper CSS/JS. Flyttet Swiper til wp_enqueue. La til `preload="metadata"` på video-blokker.

### Resultater

| Metrikk | Før | Etter (mobil) | Desktop |
|---|---|---|---|
| Performance | 69 | **99** | **100** |
| FCP | 2.0s | **0.9s** | 0.2s |
| LCP | 11.3s | **2.2s** | 0.7s |
| TBT | 130ms | **60ms** | — |
| Speed Index | 5.1s | **0.9s** | — |
| Accessibility | 79 | **84** | 90 |

### Observasjoner
- **LCP-flaskehalsen var todelt:** Render-blocking CSS/JS-kjede (4,410ms → 650ms etter defer) + enorm hero-PNG (862K). Begge måtte fikses for å få effekt — bare én av dem ville ikke gitt 99.
- **WebP .htaccess-rewrite er elegant:** Browsere som støtter WebP får automatisk WebP. Gamle browsere (finnes de?) får original. Ingen WordPress-kode endret for bildene.
- **Kun forsidebilder konvertert til WebP.** Resten av bildene på siten er fortsatt JPEG/PNG. Bør kjøre bulk-konvertering for hele uploads-mappen.
- **SEO-score er fortsatt 69.** Dette er meta/struktur-relatert (meta descriptions, structured data), ikke PageSpeed.

### Parkert / Åpne spørsmål
- ~~**WebP-konvertering:** Bør vurderes som neste optimalisering.~~ — Løst for forsiden, men trenger bulk-konvertering for resten.
- **Bulk WebP-konvertering:** Bør kjøre `cwebp` på alle JPEG/PNG i uploads/ for å dekke hele siten.
- **Automatisk WebP ved upload:** Bør settes opp slik at nye bilder automatisk konverteres til WebP.
- **SEO 69 → 90+:** Trenger meta descriptions, Open Graph, structured data (JSON-LD).

### Retning
Fundamentet er nå solid: **Performance 99/100, HTTPS, Tailwind deployet, lagring under kontroll (5.56 GB).** Det er den viktigste forutsetningen for alt som kommer — AI Search og Google belønner rask side.

Neste prioriteter bør være:
1. **SEO 69 → 90+** — Meta descriptions, Open Graph, JSON-LD structured data. Dette er lavthengende frukt som direkte påvirker synlighet.
2. **Bulk WebP-konvertering** — Forsiden er fikset, men undersider serverer fortsatt tunge JPEG/PNG. Kan automatiseres med et script.
3. **Cloudflare R2** (todo #008) — Ikke akutt nå (5.56/10 GB), men blir det når flere land legges til.
4. **Gravity Forms + Zapier → SuperOffice** — Erstatte iFrames. Viktig for konvertering.

Det harde spørsmålet: *Er det verdt å bruke mer tid på .no nå, eller bør fokus flyttes til .com (Fase 2)?* Med 99/100 PageSpeed og HTTPS er .no i god nok stand til å stå. SEO-forbedring og Gravity Forms gir direkte forretningsverdi. R2 og bulk WebP er "nice to have" inntil multisite-skalering faktisk blir et problem.

---

## 2026-02-11 – Bildekomprimering (5 GB frigjort)

### Beslutninger
- **522 JPEGs over 5MB komprimert** med ImageMagick `mogrify` på prod: resize til maks 2400px, kvalitet 82, progressive, strip metadata.
- **Resultat:** 7.0 GB → 443 MB for de 522 filene. Uploads-mappen gikk fra 11 GB til 3.8 GB. Servebolt-lagring ned fra 10.48 GB til 5.56 GB.
- Ingen originaler beholdt — filene er overskrevet in-place. For web-bruk er 2400px og kvalitet 82 mer enn tilstrekkelig.
- Største enkeltfiler var 30-34 MB rå kamerabilder (7952x5304) fra 2022-fotoshoot, duplisert mellom hovedsite og sites/3/ (Norway).

### Observasjoner
- **Duplisering mellom subsites:** sites/3/ (Norway) var 5.2 GB — nesten identisk med hovedsitens uploads. Multisite kopierer media per subsite i stedet for å dele. Dette skalerer dårlig med flere land.
- **Ingen WebP:** Kun 10 WebP-filer totalt. Konvertering til WebP ville gitt ytterligere ~30-50% besparelse.
- **PDFs:** 60 MB i store PDFs — ubetydelig foreløpig.

### Parkert / Åpne spørsmål
- **Media-skalering for multisite:** Trenger en løsning som deler media mellom subsites i stedet for å duplisere. Alternativer: Cloudflare R2, shared uploads-mappe, eller WP Offload Media.
- **WebP-konvertering:** Bør vurderes som neste optimalisering.
- **Automatisk komprimering:** Bør settes opp slik at nye uploads komprimeres automatisk (ShortPixel, Imagify, eller server-side hook).

---

## 2026-02-11 – Tailwind deploy + HTTPS

### Beslutninger
- **Tailwind deployet til prod:** Rsync av hele temaet fra branch `refactor/migrate-legacy-css-to-tailwind` (6 commits). 6 legacy block style.css-filer slettet, stiler migrert til tailwind.css. functions.php oppdatert med ny enqueue-logikk.
- **Database URL-fiks:** 10 327 `localhost:8888`-referanser erstattet med prod-URL via `wp search-replace`. Forsiden hadde bl.a. en video med localhost-src som ikke lastet.
- **HTTPS aktivert:** 10 649 `http://` → `https://` erstatninger. Servebolt hadde allerede SSL — bare WordPress-konfig manglet. HTTP redirecter nå til HTTPS (301).
- **DB-passord resatt:** Nytt passord for phpMyAdmin-tilgang. wp-config.php oppdatert. Credentials lagret i `docs/security/credentials.md` (gitignored).
- **Deploy-prosess dokumentert:** Rsync-kommando og cache-flush lagt til i CLAUDE.md.

### Løste issues fra forrige sesjon
- ~~SSL/HTTPS på produksjon~~ — Fikset
- ~~Tailwind-migrering ikke deployet~~ — Fikset
- ~~Deploy-strategi udokumentert~~ — Rsync-prosess dokumentert

### Parkert / Åpne spørsmål
- **11GB uploads-mappe:** Fortsatt uløst. Trenger CDN/R2-løsning.
- **Multisite content sync plugin:** Fortsatt ikke deployet. 7 P1 todos bør fikses først.
- **PageSpeed:** Nå som Tailwind er deployet, bør vi måle og optimalisere.
- **Lokal DB ut av sync:** Lokal DB har fortsatt `localhost`-URLer. Funker for lokal utvikling, men vær obs ved fremtidig DB-sync.

### Retning
Første runde deploy er gjennomført — temaet og HTTPS er på plass. Neste naturlige steg:
1. **PageSpeed-måling og optimalisering** (kritisk for AI Search-synlighet)
2. **Fiks P1 todos** i sync-pluginen (spesielt sikkerhet: credential exposure, file upload validation)
3. **Gravity Forms + Zapier → SuperOffice** (erstatte iFrames)

---

## 2026-02-11 – Internasjonal brief mottatt

### Beslutninger
- Lagret briefen i `docs/strategy/international-brief.md` for fremtidig referanse
- Briefen definerer Fase 2 av prosjektet: acrylicon.com (internasjonal side)

### Parkert / Åpne spørsmål
- **Fase 2 vs. nåværende acrylicon.no:** Skal arbeid på .no fullføres først (Tailwind-deploy, PageSpeed, SSL)?
- **Multisite vs. separate installasjoner:** Briefen sier "separate eller multisite" – vi har allerede multisite-infrastruktur. Beslutning trengs.
- **150k-estimat:** Hva er scoped inn, hva er ute? Trenger nedbrytning i konkrete leveranser.
- **Gravity Forms:** Lisens kjøpt? Zapier-konto satt opp?
- **Hotjar:** Konto opprettet?
- **CDN-valg:** Servebolt har innebygd CDN, men R2 kan være bedre for 11GB uploads

### Retning
To parallelle spor:
1. **Kort sikt:** Ferdigstille acrylicon.no (deploy Tailwind, PageSpeed, SSL)
2. **Medium sikt:** Bygge acrylicon.com (Fase 2 fra briefen)

Anbefaler å få .no i god stand først – det er lettere å bygge .com på et solid fundament enn å starte noe nytt mens det gamle halter.

### Observasjoner
- Briefen er godt strukturert men mangler prioritering – alt er like viktig, og det er det aldri
- 150k er et realistisk budsjett for dette scopet, men trenger tydelige faser/milepæler
- Mobile-first med 65,9% mobiltrafikk er riktig prioritering
- Referansesystemet (filtre, søk, lazy load) kan bli det mest komplekse enkeltelementet

---

## 2026-02-11 – Miljø-setup og dokumentasjonsbase

### Beslutninger
- **Servebolt SSH/WP-CLI:** Satt opp nøkkelautentisering og WP-CLI-tilgang til produksjon, lik bimverdi-v2-oppsettet. Gir direkte databasetilgang fra begge miljøer.
- **Produktsync:** 12 produkter synkronisert fra prod til lokal via WP export/import. Databasene er nå identiske.
- **Docs-struktur:** Valgte flat tematisk struktur med 3 mapper (context/, strategy/, architecture/) fremfor alt-i-én eller dyp hierarkisk struktur. 11 nye markdown-filer basert på ACRYLICON_CONTEXT.md.
- **AUTH_COOKIE fix:** Løste Yoast SEO multisite-bug med eksplisitte cookie-definisjoner i wp-config.php på begge miljøer.
- **WP Fastest Cache:** Skippes i WP-CLI for å unngå HTML-dump.

### Parkert / Åpne spørsmål
- **SSL/HTTPS på produksjon:** Siteurl er fortsatt http://. Bør settes opp.
- **11GB uploads-mappe:** Trenger CDN/R2-løsning (plan finnes).
- **Deploy-strategi:** Manuell deploy. Trenger en prosess for å flytte tema/plugins fra lokal til prod.
- **Multisite content sync plugin:** Utviklet men ikke deployet. Når skal den deployes?
- **7 åpne P1 todos:** Ikke tatt tak i denne sesjonen.
- **Tailwind-migrering:** 6 commits foran master, ikke deployet.

### Retning
Prosjektet er i en **stabiliseringsfase** – miljøet er nå satt opp med full tilgang og dokumentasjon. Neste naturlige steg:
1. Deploy Tailwind-migreringen (allerede ferdig, bare trenger push)
2. PageSpeed-forbedring (kritisk for AI Search-synlighet)
3. Graviy Forms + Zapier → SuperOffice (erstatte iFrames)

Dokumentasjonsbasen gir et solid fundament for å jobbe effektivt fremover. Hver Claude Code-sesjon har nå full kontekst uten å måtte lese 401-linjers kontekstfilen.

### Observasjoner
- **Effektivitetsgevinst:** WP-CLI på begge miljøer + synkroniserte databaser gjør innholdsarbeid mye raskere
- **Docs vs. CLAUDE.md:** God arbeidsdeling – CLAUDE.md har quick reference, docs/ har dybde
- **Teknisk gjeld:** Mye er planlagt men lite deployet (multisite-sync, Tailwind, mu-plugins). Bør prioritere å få ting ut i prod fremfor flere nye features
- **PageSpeed er kritisk:** 57–68 vs. konkurrenter på 90+ betyr at AcryliCon er usynlig i AI-søk. Dette bør være topp-prioritet etter deploy
