# PROJECT-LOG – AcryliCon

> Strategisk prosjektlogg. Oppdateres etter hver /full-sesjon.

---

## 2026-02-25 – Custom SEO mu-plugin erstatter Yoast SEO

### Beslutninger
- **Full Yoast-erstatning:** Bygget komplett custom mu-plugin "AcryliCon SEO" med 8 moduler (titles, descriptions, schema, OG, canonical, robots, admin metabox, sitemap). Yoast network-deaktivert.
- **mu-plugin arkitektur:** Subdirectory-mønster med loader-fil. Alltid aktiv, temauavhengig. `plugins_url()` defineres i `init` hook — kan ikke kalles i global scope for mu-plugins.
- **Organization schema kun på forsiden:** Research viste at Google anbefaler dette. Andre sider refererer via `@id`.
- **`og:type = website` for alle sider:** B2B-side uten kommentarfelt/forfatter — `article` er ikke semantisk korrekt.
- **Logo som PNG, ikke SVG:** Google avviser SVG for schema. Må lage PNG-versjon av eksisterende logo.
- **`is_front_page()` sjekkes FØR `is_singular()`:** Front page er også singular i WordPress. Denne rekkefølgen er kritisk i alle moduler.
- **Yoast postmeta beholdt som fallback:** `_yoast_wpseo_title` og `_yoast_wpseo_metadesc` leses som sekundær kilde i fallback-kjeden.

### Parkert / Åpne spørsmål
- ~~Ralph testkjøring~~ — Løst: Bygget alt selv via /full workflow. Ralph-oppsettet kan fjernes.
- **Logo PNG mangler:** Må konvertere `acrylicon-logo-dark.svg` til PNG (min 112x112px). Blokkerer schema-validering.
- **OG default-bilde mangler:** Trenger `acrylicon-og-default.jpg` (1200x630) for sider uten featured image.
- **Taxonomy term-navn er engelske:** (Todo 009) Påvirker meta descriptions og schema. Uløst.
- **Deploy:** SEO-modulen er på `feat/custom-seo-module` branch. Yoast må deaktiveres på prod samtidig med deploy.
- **wp-sitemap.xml:** Fungerer ikke lokalt (301 redirect-loop). Sannsynligvis .htaccess/rewrite issue. Bør testes på prod.

### Retning
SEO-infrastrukturen er nå fullstendig under vår kontroll. Alle ~145+ sider har meta title, description, canonical, OG tags, robots, og JSON-LD schema — uten noen tredjeparts-plugin. Admin UI gir redaktører Google preview og mulighet for manuell overstyring.

Neste steg bør være:
1. Lage logo PNG + OG default-bilde (blokkerende for production)
2. Merge til main og deploy (inkl. Yoast-deaktivering på prod)
3. Verifisere med Google Rich Results Test og Search Console
4. Todo 009 (taxonomy term-navn) — påvirker meta descriptions kvalitet

### Observasjoner
- **Research-fasen avdekket 15+ korreksjoner** til opprinnelig plan: `plugins_url()` timing, Organization kun på forsiden, SVG→PNG krav, search noindex allerede i core, canonical priority 10 (ikke 2), etc. Deepen-fasen er definitivt verdt tiden.
- **WordPress har overraskende god SEO-infrastruktur:** `document_title_parts`, `wp_robots`, `rel_canonical`, core sitemaps med lastmod — bare trenger å fylle hullene.
- **`is_front_page()` vs `is_singular()` ordreproblemet** er en klassiker som dukket opp i 4 separate moduler. Viktig å huske.
- **Yoast-deaktivering var rent:** Ingen andre avhengigheter i temaet utover `wpseo_metadesc` filter som allerede var i den separate filen.

---

## 2026-02-25 – Meta description fallback + Ralph SEO-oppsett

### Beslutninger
- **Yoast `wpseo_metadesc` filter:** Valgte å bruke Yoasts dokumenterte public API fremfor custom wp_head-output. Tryggere ved Yoast-oppdateringer, og unngår dupliserte meta-tagger.
- **`$presentation->model` over `get_post()`:** Yoasts Indexable-objekt har allerede post_type og ID — ingen ekstra DB-query.
- **Taxonomy-ID er fast, ikke dynamisk:** `referanser-produkter` er taxonomy-ID på begge blogger. `acrylicon_get_cpt_slugs()` returnerer URL-rewrite-slug, ikke taxonomy-ID. Viktig distinksjon som forårsaket en bug.
- **`mb_substr()` for trunkering:** Norsk tekst med æøå krever multibyte-safe funksjoner.
- **Ralph autonomous agent satt opp:** `wp-content/ralph/` med 8 SEO user stories i `prd.json`. Ikke testkjørt ennå — Ralph er ikke tilgjengelig som marketplace-plugin, satt opp manuelt.

### Parkert / Åpne spørsmål
- **Ralph testkjøring:** Skal vi faktisk kjøre `ralph.sh` for de gjenværende SEO-storiene (JSON-LD schemas), eller bygge dem manuelt med /full?
- **Referanse-taxonomy termer:** Mange referanser (~42 av ~100) har ingen `referanser-produkter`-termer. Bør disse tagges manuelt?
- **Taxonomy term-navn er engelske:** "Flake System – Floor" vises i norske meta descriptions. Bør vi ha norske term-navn på blog 3?
- **Deploy:** Meta description-koden er på `ralph/technical-seo`-branch. Merge til main + deploy til prod når verifisert.

### Retning
SEO-arbeidet er i gang. Meta descriptions var det lavest hengende fruktet — én fil, umiddelbar effekt på alle ~145 sider. Neste logiske steg er JSON-LD structured data (Organization → LocalBusiness → Product schemas) som er planlagt i de 7 gjenværende Ralph-storiene.

Salgsdokumentet ("Digital veikart 2026-2027") og Ralph-oppsettet gir en god ramme for å systematisere dette arbeidet. Men spørsmålet er om Ralph-loopen faktisk er verdt overhead for WordPress/PHP-prosjekter der testinfrastrukturen er svakere enn i TypeScript-prosjekter.

### Observasjoner
- **Yoast har ingen fallback** — dette var overraskende. Uten vår filter ville ~142 sider hatt null meta description.
- **ACF-data dekker mye:** product_excerpt (83%), office_adress (100%), taxonomy-termer (58%) gir godt grunnlag for auto-generering.
- **"Deepening"-fasen ga reell verdi:** Yoast filter-signaturen, edge cases for non-singular contexts, og `wp_strip_all_tags` vs `strip_tags` var alle funn som forbedret implementeringen. Verdt å kjøre deepen på alle tekniske planer.

---

## 2026-02-12 – Referanseside: client-side filtrering

### Beslutninger
- **Client-side filter over AJAX**: 15 referanser er lite nok til å laste alle og filtrere med JS. Ingen REST API-overhead.
- **Tre filter-grupper**: Industri, produktsystem, kontor — alle eksisterte som taksonomier men kun industri var filtrerbar.
- **AND mellom grupper, OR innenfor**: Velg "Fiskeindustri" + "Flake System" = kun referanser som matcher begge.
- **Case studies sortert først**: `usort()` etter query, case studies får `lg:col-span-2` for visuell vekt.
- **Erstattet `query_posts()`**: Taxonomy-template brukte deprecated funksjon, nå `WP_Query`.
- **Kun terms med innhold**: Filter-pills bygges fra faktisk data, ikke alle registrerte terms.

### Parkert / Åpne spørsmål
- URL-state for filtre (query params) — ikke i MVP, kan legges til med `history.pushState`
- Animasjoner ved filter — enkel hide/show nå, kan legge til fade transition

### Retning
Referansesiden er nå mye mer brukbar for kunder som vil finne relevante case studies. Neste steg kan være å legge til filtrering i andre listevisninger (produkter, bruksområder).

### Observasjoner
- Vanilla JS IIFE-mønsteret fungerer bra for denne type isolert funksjonalitet
- ACF-blokkens `show_taxonomy` toggle bestemmer om filtre vises — eksisterende oppførsel bevart
- Duplisert kortmal-kode i blokken er eliminert — fra 220 linjer med 3 nesten-identiske loops til én loop

---

## 2026-02-12 – DB-sync fra prod + index.php-fix + git cleanup

### Beslutninger
- **Prod DB pull til localhost:** Lokal DB var utdatert — manglet alle engelske oversettelser fra 2026-02-11. Pullet full prod DB (37MB), kjørte search-replace for URLs, fikset wp_blogs/wp_site domain+path.
- **`index.php` have_posts() guard:** Tema-filen kalte `the_post()` uten å sjekke `have_posts()` først. Ga PHP warning på URLer som ikke matchet noen side. Fikset og deployet til prod.
- **CLAUDE.md korrigert:** Blog 3 bruker `/no/` på *begge* miljøer, ikke `/norway/` som dokumentert. Rettet.
- **Git cleanup:** 5 commits med alt uncommittet arbeid fra forrige sesjoner (language switcher, i18n templates, factory/locations pages, docs).

### Parkert / Åpne spørsmål
- **Lokal DB-sync bør automatiseres:** Manuell prosess med SSH export → import → search-replace → wp_blogs fix. Bør lage et script.
- **SEO docs oppdatert:** `docs/strategy/seo.md` har nå detaljert konkurrentanalyse og faseplan, men ingenting er implementert ennå.
- **7 åpne P1 todos** i `wp-content/todos/` — fortsatt ubehandlet (R2 CDN, credential exposure, multisite sync issues).

### Retning
Localhost er nå i sync med prod. Lokal utvikling kan stoles på igjen. Neste naturlige steg er enten:
1. Takle P1 todosene (R2 CDN, credential-sikkerhet)
2. SEO fase A (hreflang, meta descriptions)
3. Innholdsarbeid (referansesider, bruksområde-utvidelser)

### Observasjoner
- **DB-sync er den vanligste "gotcha":** Tredje gang vi har sett issues relatert til lokal/prod DB-forskjeller. Et `db-pull.sh`-script ville spart mye debugging-tid.
- **Uncommittet arbeid over flere sesjoner er risikabelt.** Denne sesjonen committet 5 stykker arbeid som har ligget i working tree. Bør committe oftere.
- **Branch-hygienen trenger rydding:** Vi er på `feature/reference-page-filters` men committene inkluderer language switcher, factory pages, og docs som ikke er relatert til referansesider.

---

## 2026-02-11 – Internasjonal side oversatt til engelsk

### Beslutninger
- **Full innholdsoversettelse av internasjonal side (blog 1):** All brukersynlig tekst oversatt fra norsk til engelsk — sidetitler, CPT-titler (produkter, referanser, bruksområder, gode grunner, levetidskostnader, bærekraft), forsideinnhold (17KB ACF-blokker), navigasjonsmenyer, footer-menyer, taxonomi-termer (referanser-type, referanser-kategorier, referanser-produkter).
- **Norge-spesifikt innhold slettet fra internasjonal:** 3 sider (Karriere, Nyhetsbrev, Komponenter) og 5 norske kontorposter slettet. Karriere-blokk erstattet med "Global presence"-blokk.
- **Dynamisk flerspråklig footer:** `footer.php` bruker `get_current_blog_id()` for å vise "Applications"/"Bruksområder" og "Offices"/"Kontorer" per site.
- **Hardkodet "Dybdecase" fikset:** `specific-references-loop/template.php` rendrer nå taxonomi-termnavn dynamisk i stedet for hardkodet norsk tekst. "Filtrer på industri" gjort dynamisk.
- **Yoast SEO oppdatert:** Tittel og meta description for forsiden oversatt til engelsk.

### Resultater
- Navigasjon: Benefits, Applications, References, Products, About AcryliCon, Contact
- Forside: Alle 17KB ACF-blokkinnhold på engelsk
- Footer: Engelske overskrifter og menyelementer
- Taxonomier: "Case study" (fra "Dybdecase"), "Schools and public buildings" (fra "Skoler og offentlige bygg"), produktsystemer "Floor"/"Wall" (fra "Gulv"/"Vegg")
- SEO-tittel: "AcryliCon – When sustainability and durability meet"

### Observasjoner
- **WP Fastest Cache var vanskelig:** Cache måtte tømmes på domene-nivå (`cache/acryli-28355.jana-osl.servebolt.cloud/`), ikke bare `cache/all/`. Browser-cache krevde også cache-busting query parameters.
- **Mange hardkodede norske strenger i templates:** "Dybdecase" i reference-block, "Filtrer på industri", footer-overskrifter. Multisite-oppsett krever systematisk gjennomgang av alle templates for hardkodet tekst.
- **URL-slugs er fortsatt norske:** `/fordeler/`, `/bruksomrader/`, `/referanser/`, `/baerekraft/` etc. Fungerer, men ikke ideelt for SEO.
- **Footer "Offices"-kolonnen er tom:** Norske kontorer slettet, ingen internasjonale kontor-CPTs opprettet ennå.

### Parkert / Åpne spørsmål
- **URL-slugs:** Bør oversettes til engelsk for SEO (`/benefits/`, `/applications/`, `/references/`). Krever slug-oppdatering + redirects.
- **Fabrikk-side:** Bruker nevnte eksplisitt behov for en dedikert fabrikkside (AcryliCon Polymers GmbH).
- **CPT body content:** Kun titler er oversatt. Brødtekst i produkter, referanser, bruksområder etc. er fortsatt norsk.
- **Internasjonale kontorer:** Trenger nye kontor-CPTs for internasjonale lokasjoner.
- **Andre templates med hardkodet norsk:** Bør gjøre en systematisk gjennomgang av alle block-templates.

### Retning
**Den internasjonale forsiden ser nå profesjonell og engelskspråklig ut.** Monika og stakeholders vil se tydelig fremgang. Navigasjon, forside, footer — alt er oversatt og konsistent.

Neste naturlige steg for internasjonal:
1. **Oversett CPT-innhold** — Produktbeskrivelser, referansetekster, bruksområder-tekster. Forsiden linker til disse.
2. **Fabrikk-side** — Ny eksklusiv side for AcryliCon Polymers GmbH.
3. **URL-slugs** — Oversett til engelske URLer med redirects.
4. **Kontor-CPTs** — Opprett internasjonale kontorer.

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
