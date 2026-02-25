# Teknisk SEO — Synlighet i norske Google-søk

**Dato:** 2026-02-20
**Trello:** https://trello.com/c/x1PLLDhB/23-teknisk-seo
**Status:** Brainstorm

---

## Hva vi bygger

En lagvis teknisk SEO-implementering som gjør Acrylicon synlig i norske Google-søkeresultater og AI Overviews. Fokus på strukturert data, meta descriptions, og verifisering.

**Ønsket utfall:** Grønn PageSpeed (✅ allerede oppnådd: 99/100 mobil), og synlighet i Google-søk for relevante norske bransjetermer som "epoxy gulv", "industrigulv", "gulvbelegg".

---

## Nåsituasjon

### Allerede gjort
- **PageSpeed:** 99/100 mobil, 100/100 desktop (fikset 2026-02-11)
- **Hreflang:** Komplett implementering i `language-switcher.php` (EN/NO + x-default)
- **CSS/JS-leveranse:** Defer på ikke-kritisk CSS og jQuery
- **Video preload:** metadata-preload på videoblokker
- **301 redirects:** Gamle norske slugs → engelske på blog 1
- **Yoast SEO:** Installert (v26.8), håndterer sitemap, robots, canonical
- **WebP:** Delvis konvertert

### Gjenstår
- **Strukturert data:** Ingen JSON-LD/Schema.org i temaet overhodet
- **Meta descriptions:** 2-3 av ~60 sider har utfylt Yoast meta description
- **SEO-score:** Står på 69 (meta/struktur-problem, ikke PageSpeed)
- **Google AI-synlighet:** Ikke verifisert/optimalisert

### Overlap med "Mobil UX og PageSpeed"-kortet
PageSpeed-delen av begge kort er allerede løst. Dette kortet utvider nå fokuset til *innhold og struktur for søkemotorer*.

---

## Hvorfor denne tilnærmingen

**Lagvis implementering** — ikke alt på en gang:

1. **Lag 1: Strukturert data (JSON-LD)** — Ren kodeimplementering, kan bygges i Claude. Gir Google maskinlesbar forståelse av virksomheten. Kritisk for AI Overviews.
2. **Lag 2: Meta descriptions** — Generere forslag basert på sideinnhold, workflow for review/godkjenning. Kontrollerer hva Google viser i søkeresultater.
3. **Lag 3: Verifisering** — Google Search Console, rich results test, AI Overview-sjekk.

**Fordel:** Lag 1 kan implementeres umiddelbart og gir effekt uavhengig av lag 2. Meta descriptions kan rulles ut gradvis.

---

## Lag 1: Strukturert data (JSON-LD)

### Schema-typer å implementere

| Schema-type | Hvor | Hvorfor |
|-------------|------|---------|
| **Organization** | Sitewide (header) | Etablerer Acrylicon som bedrift i Googles Knowledge Graph |
| **LocalBusiness** | Kontorsider (4 stk) | Lokale søk: "gulvløsninger Bergen", "industrigulv Tromsø" |
| **Product** | Produktsider (11 systemer) | Søk som "epoxy gulv", "akryl belegg", "flake system" |
| **BreadcrumbList** | Alle sider | Viser navigasjonssti i søkeresultater |
| **Article/CaseStudy** | Referanser (100+) | Troverdighet, "acrylicon referanser" |
| **FAQPage** | Evt. FAQ-innhold i blokker | Kan gi expanded snippets i søk |
| **Service** | Bruksområder-sider | "overflatebehandling industri", "gulvløsninger matproduksjon" |

### Implementeringsnoter
- Output JSON-LD via `wp_head` hook i functions.php eller dedikert inc-fil
- Bruk ACF-feltdata der tilgjengelig (kontorer har adresse, produkter har beskrivelser)
- Organization-schema: logo, kontaktinfo, sosiale profiler
- LocalBusiness: adresse, åpningstider, serviceområde per kontor
- BreadcrumbList: Bygges fra WordPress-brødsmuler (post type → arkiv → single)

---

## Lag 2: Meta descriptions

### Valgt strategi: Kode-fallback + manuell topp

**Problem:** ~145 publiserte sider/innlegg, kun 2-3 har utfylt meta description i Yoast. Google lager egne utdrag — ofte dårlige.

**Tilnærming — to deler:**

#### Del A: PHP-fallback (automatisk)
En funksjon som filtrerer Yoast's `wpseo_metadesc` og genererer meta description fra ACF-data når Yoast-feltet er tomt:

| Post type | Kilde | Mal |
|-----------|-------|-----|
| `produkter` | `product_excerpt` (stikkordliste) | "Acrylicon {tittel} — {konvertert excerpt til prosa}. Profesjonell gulvløsning fra Acrylicon." |
| `referanser` | `referance_productsystem` + tittel | "{Tittel} — referanseprosjekt med {produktsystem} fra Acrylicon." |
| `bruksomrader` | Sidetittel + kontekst | "Acrylicon {tittel} — skreddersydde gulv- og veggløsninger for {bransje}." |
| `kontor` | ACF adressedata | "Acrylicon {tittel} — {adresse}. Kontakt oss for gulvløsninger i {region}." |
| `industrier` | Sidetittel | "Gulvløsninger for {tittel} — slitesterke og hygieniske systemer fra Acrylicon." |
| `page` | Excerpt eller innhold | Første 155 tegn fra excerpt/innhold |

**Datakilder bekreftet:**
- `product_excerpt`: 10 av 12 produkter har innhold (HTML stikkordliste)
- `referance_excerpt`: Tom for alle referanser — bruk tittel + produktsystem
- 2 produkter (Multi-Grip ID, TankCoating) mangler excerpt — trenger manuell

#### Del B: Manuelt skrevne for topp-sider (~20 stk)
Skriv unike, søkeoptimaliserte meta descriptions direkte i Yoast for:
1. **Forside** (✅ har allerede)
2. **Produktsider** (12 stk) — høyest søkevolum
3. **Kontorsider** (5 stk) — lokale søk
4. **Nøkkelsider**: Om oss, Referanser-arkiv, Sertifiseringer

**Workflow:** Claude genererer forslag → Andreas reviewer/justerer → settes via WP-CLI (`wp post meta update {ID} _yoast_wpseo_metadesc "{tekst}"`)

### Referanser — moderat prioritet
De 100 referansene får automatisk meta description fra PHP-fallbacken. Referanser med dypt innhold (de 5 som genererer 7 279 sessions/år) bør få manuelle beskrivelser i tillegg.

### Best practices for meta descriptions
- 150-160 tegn (norsk)
- Inkluder primært søkeord naturlig
- Handling/CTA: "Se løsninger", "Les mer", "Kontakt oss"
- Unik per side, aldri duplisert

---

## Lag 3: Verifisering

1. **Google Rich Results Test** — test JSON-LD for feil
2. **Google Search Console** — submit sitemap, monitor indeksering
3. **PageSpeed Insights** — bekreft fortsatt grønn etter endringer
4. **Site-søk:** `site:acrylicon.no` — se hva Google indekserer
5. **AI Overview-test:** Søk på relevante termer, sjekk om Acrylicon vises

---

## Nøkkelbeslutninger

1. **Norske søk først** — fokus på google.no og norske søkeord
2. **Lagvis implementering** — strukturert data → meta descriptions → verifisering
3. **Full Schema-dekning** — Organization, LocalBusiness, Product, BreadcrumbList, Article, Service
4. **Meta: kode-fallback + manuell topp** — PHP auto-genererer fra ACF når Yoast er tom, manuelt for ~20 viktigste sider
5. **Referanser: moderat prioritet** — automatisk fallback, manuell for topp-5
6. **PageSpeed allerede løst** — ikke del av dette scopet

---

## Avklarte spørsmål

- **Google Search Console:** Ikke satt opp. Må verifiseres som del av lag 3.
- **Google Business-profiler:** Ikke opprettet for noen kontorer. Viktig tiltak utenfor dette scopet, men LocalBusiness-schema forbereder grunnlaget.

## Åpne spørsmål

1. Skal referansene ha individuell Schema-markup, eller holder det med arkivsiden?
2. Finnes det FAQ-innhold i ACF-blokkene som kan markeres opp?
3. Hvem setter opp Google Search Console og Google Business? (Manuelt arbeid i Google-portaler)

---

## Estimat

| Lag | Oppgave | Estimat |
|-----|---------|---------|
| 1 | Strukturert data (JSON-LD) | 4-6 timer |
| 2 | Meta description-generering + review | 3-4 timer |
| 3 | Verifisering og finjustering | 2-3 timer |
| | **Totalt** | **9-13 timer** |

Betydelig redusert fra originalt 25+ timer fordi PageSpeed og hreflang allerede er gjort.
