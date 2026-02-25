# Mobil UX og PageSpeed-optimalisering

**Dato:** 2026-02-20
**Trello:** https://trello.com/c/ClySejp0
**Status:** Brainstorm ferdig, klar for plan

---

## Hva vi bygger

Systematisk ytelsesoptimalisering av acrylicon.no med mål om PageSpeed 90+ (mobil) og grønne Core Web Vitals. 66% av trafikken er mobil — dette er kritisk for brukeropplevelse og AI-søk-synlighet.

**Nåværende tilstand (Lighthouse mobil, 2026-02-20):**

| Side | Score | FCP | LCP | TBT | CLS |
|------|-------|-----|-----|-----|-----|
| `/no/` (NO forside) | **43** | 1.9s | **11.7s** | **1,690ms** | 0.035 |
| `/` (EN forside) | **75** | 1.0s | **8.6s** | 20ms | 0 |
| `/no/referanser/` | **84** | 1.1s | 4.6s | 0ms | 0 |

**Mål:** PageSpeed 90+, LCP < 2.5s, CLS < 0.1, INP < 200ms

---

## Hvorfor denne tilnærmingen

**Systematisk lag-for-lag** — jobber i prioriterte steg som bygger på hverandre. Hver fase gir målbar forbedring og det er trygt å stoppe mellom lagene.

### Lag 1: Fjern død kode
- **Headroom.js** (4.6 KB) — enqueued men aldri initialisert. Fjernes helt.
- **scrollreveal.min.js** (44.6 KB) — ligger i assets/scripts/ men ikke enqueued. Slettes.
- Eventuell annen ubrukt CSS/JS identifisert under audit.

### Lag 2: Fiks kritiske rendering-problemer
- **GSAP bfcache-bug** — `opacity: 0` på main/footer gir blank side ved tilbake-navigasjon. Fiks: lytt på `pageshow` event og sett opacity til 1 når `event.persisted === true`. Behold GSAP fade-in for normal navigasjon.
- **Preconnect hints** — Legg til `<link rel="preconnect">` for cdn.jsdelivr.net (Swiper, GSAP).
- **fetchpriority="high"** på LCP-bilde (hero-blokken).
- **theme-color meta tag** for mobil browser chrome.

### Lag 3: Optimaliser asset-levering
- **Conditional Swiper-lasting** — Last Swiper JS/CSS kun når en slider-blokk finnes på siden (sjekk `has_block()` eller ACF-felter).
- **Defer/async strategi** — Gjennomgå alle scripts, sikre at ingenting er render-blocking unødvendig.
- **Font-optimalisering** — Vurder om soehne-buch-kursiv (34 KB) trengs på alle sider. Legg til `font-display: swap` om det mangler.

### Lag 4: Bilder
- **Fiks slider-blokker** — Bytt fra rå `<img src="url">` til `wp_get_attachment_image()` for automatisk srcset, sizes og lazy loading.
- **Bildeoptimaliserings-plugin** — Installer ShortPixel eller Imagify for automatisk WebP-konvertering og komprimering.
- **fetchpriority på above-the-fold bilder** — Sikre at LCP-bilder ikke lazy-loades.

### Lag 5: Mål og iterer
- Kjør PageSpeed Insights baseline FØR endringer.
- Re-test etter hvert lag.
- Finjuster basert på CWV-data.

---

## Nøkkelbeslutninger

| Beslutning | Valg | Begrunnelse |
|---|---|---|
| GSAP fade-in | Behold, fiks bfcache | Godt designelement, bare bfcache-buggen må fikses |
| Headroom.js | Fjern | Aldri initialisert, ren død vekt |
| Bildeoptimalisering | Plugin nå, R2/CDN senere | ShortPixel/Imagify for umiddelbar gevinst, R2 som eget prosjekt |
| CTA-standardisering | Utenfor scope | Holde dette prosjektet teknisk, CTA-design separat |
| Swiper-lasting | Conditional | Kun noen sider bruker slider, unødvendig å laste overalt |
| Suksesskriterier | PageSpeed 90+ OG grønne CWV | LCP < 2.5s, CLS < 0.1, INP < 200ms |

---

## Funn fra research

### Assets som lastes på hver side
- 6 CSS-filer (3 allerede deferred via media="print" trick)
- 8+ JS-filer (jQuery, GSAP, Swiper, bodyScrollLock, Headroom, transitions, scripts)
- 3 font-filer (92 KB totalt, woff2)
- GTM + gtag + Byggfakta analytics inline i head

### Kritiske funn fra Lighthouse (2026-02-20)

**Største synder: Et bilde på 2.6 MB** — `Nasjonalteateret`-bildet på /no/-forsiden er 2.6 MB ukomprimert JPEG. Det er LCP-elementet og alene ansvarlig for 11.7s LCP.

**Tredjepartsskript (327 KB unused JS, må beholdes):**
- GTM: 120 KB (51% ubrukt) — må beholdes
- gtag: 146 KB (39% ubrukt) — må beholdes
- Facebook pixel: 95 KB (35% ubrukt) — status usikker, beholdes inntil videre
- Swiper: 44 KB (86% ubrukt på forsiden) — conditional lasting mulig
- Docu/Byggfakta: 22 KB (93% ubrukt) — må beholdes, mye i bruk

**TBT 1,690ms på /no/** — hovedtråden blokkeres i nesten 2 sekunder, primært pga analytics-skript.

### Identifiserte bottlenecks (prioritert etter impact)
1. **Ukomprimerte hero-bilder (2.6 MB!)** — trenger bildeoptimalisering/WebP
2. Slider-bilder mangler srcset og lazy loading (rå img-tag fra ACF URL)
3. Ingen fetchpriority="high" på LCP-bilde
4. Swiper lastes globalt, brukes bare på noen sider (44 KB)
5. GSAP-avhengig opacity:0 gir blank side ved bfcache
6. Headroom.js lastes men brukes ikke (4.6 KB)
7. GSAP + Swiper fra CDN uten preconnect hints
8. Tredjepartsskript kan ikke fjernes, men kan optimaliseres (delayed loading)

---

## Åpne spørsmål

- Hvilken bildeoptimaliserings-plugin passer best? (ShortPixel vs Imagify vs annet)
- Skal font-subsetting/splitting vurderes, eller er 92 KB akseptabelt?
- Er det andre blokker enn slider som bruker rå img-tags fra ACF?
- Bør GSAP self-hostes istedenfor CDN for bedre caching-kontroll?
- Facebook pixel — sjekk om den er aktiv/nødvendig, potensielt 95 KB å spare
- Kan tredjepartsskript (GTM, Docu, FB) delayed-loades etter brukerinteraksjon?

---

## Neste steg

Kjør `/workflows:plan` for å lage detaljert implementeringsplan med konkrete filer, kodeendringer og tidsestimat per lag.
