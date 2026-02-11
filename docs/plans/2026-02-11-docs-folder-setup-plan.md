# Plan: Docs Folder Setup

**Date:** 2026-02-11
**Type:** docs
**Brainstorm:** `docs/brainstorms/2026-02-11-docs-folder-setup-brainstorm.md`
**Source:** `/Users/andreasharstad/Downloads/ACRYLICON_CONTEXT.md`
**Risk:** LOW (kun markdown, ingen kodeendringer)

---

## Oppgaver

### 1. Opprett mappestruktur
- [ ] `docs/context/`
- [ ] `docs/strategy/`
- [ ] `docs/architecture/`

### 2. Context-dokumenter

#### 2.1 `docs/context/company.md` - Selskapet
- [ ] Historikk og grunnlegger (1977, Bjørn Hegstad)
- [ ] Forretningsmodell (vertikal integrasjon, regionale kontraktører)
- [ ] AcryliCon Norge: vår kunde (Monika, daglig leder)
- [ ] Eierstruktur (Polymers GmbH → Services GmbH)
- [ ] Internasjonal tilstedeværelse (18+ kontorer, 21 land)
- [ ] Bærekraft og miljø (Red List Free, M1, solceller)
- [ ] Nøkkelbudskap og verdiforslag (7 kjernebudskap)
- [ ] Differensieringspunkter
- [ ] Ordliste/nøkkelbegreper

#### 2.2 `docs/context/offices.md` - Kontorstruktur
- [ ] De 4 norske kontorene med dekningsområder
- [ ] Nøkkelpersoner (Brønnøysund-data)
- [ ] Sertifiseringer per kontor (Miljøfyrtårn etc.)
- [ ] Kobling til CPT `kontor` (IDs: 107, 137, 141, 163, 171)

#### 2.3 `docs/context/products.md` - Produktkatalog
- [ ] Alle 12 systemer med: tykkelse, bruksområde, nøkkelegenskaper
- [ ] Tekniske nøkkelegenskaper (herdetid, levetid, trykkstyrke)
- [ ] Produktposisjonering vs. konkurrenter
- [ ] Kobling til CPT `produkter` (IDs fra database)
- [ ] Bruksområder (12 sektorer, koblet til CPT `bruksomrader`)

#### 2.4 `docs/context/certifications.md` - Sertifiseringer
- [ ] Full sertifiseringstabell (M1, BREEAM, TÜV, ISEGA, DNV, CE, ETA, etc.)
- [ ] Hva hver sertifisering betyr for kunden
- [ ] Kobling til page ID 324 (Sertifiseringer)

#### 2.5 `docs/context/competitors.md` - Konkurrenter
- [ ] Internasjonale konkurrenter (Resdev, Quest, Flowcrete, John Lord)
- [ ] Norske søkeord-konkurrenter (Gjøco etc.)
- [ ] Historisk: Silikal GmbH (rettssak, forlik 2010)
- [ ] Produktsammenligning (hva AcryliCon erstatter)

### 3. Strategy-dokumenter

#### 3.1 `docs/strategy/seo.md` - SEO-strategi
- [ ] Prioriterte søkeord (Tier 1/2/3 med volum)
- [ ] Google Ads-annonsestruktur
- [ ] PageSpeed-status (57-68, mål 90+)
- [ ] AI Search-synlighet (konkurrenter med PSI 90+ vises)

#### 3.2 `docs/strategy/content.md` - Innholdsstrategi
- [ ] 6 innholdspilarer (fra Brist markedsbyrå)
- [ ] Standardiserte CTA-er
- [ ] LinkedIn/SoMe-strategi (5 pilarer)
- [ ] Menystruktur acrylicon.no
- [ ] Referansestrategi (sektorspesifikke caser med data)

#### 3.3 `docs/strategy/international.md` - Internasjonalisering
- [ ] Multisite-arkitektur per marked
- [ ] Domener (NO, UK, USA, AU, Polymers)
- [ ] Innholdssynkronisering (referanser, produkter)
- [ ] hreflang og flerspråklig SEO
- [ ] Planlagte innovasjoner (CRM, datablad-system, sertifiseringsDB, filportal)

### 4. Architecture-dokumenter

#### 4.1 `docs/architecture/wordpress.md` - WordPress-arkitektur
- [ ] Multisite-konfigurasjon (subdirectory, blog 1 + 3)
- [ ] Custom Post Types (utdypet: alle felt, relasjoner)
- [ ] Taxonomier (4 stk på referanser, hva de brukes til)
- [ ] Viktige page-IDs (fra database)
- [ ] wp-config.php spesialiteter (AUTH_COOKIE fix, socket)

#### 4.2 `docs/architecture/theme.md` - Tema-arkitektur
- [ ] 26 ACF-blokker (full liste med beskrivelse)
- [ ] Template-hierarki
- [ ] Tailwind-konfigurasjon (farger, breakpoints, byggsystem)
- [ ] JS-biblioteker og hva de brukes til
- [ ] Enqueue-strategi

#### 4.3 `docs/architecture/integrations.md` - Integrasjoner
- [ ] SuperOffice CRM (nåværende iFrame → planlagt Gravity Forms + Zapier)
- [ ] GA4 og Hotjar
- [ ] Servebolt hosting (SSH, WP-CLI, environment)
- [ ] CDN/bildeoptimering (planlagt)
- [ ] WordPress Importer (for content sync)

### 5. Prosjektlogg
- [ ] `docs/PROJECT-LOG.md` - Første innlegg

### 6. Oppdater CLAUDE.md
- [ ] Legg til referanse til docs/-strukturen
- [ ] Oppdater "Produkter mangler lokalt" (nå synkronisert)

---

## Kvalitetskrav

- Alle filer under 150 linjer
- Norsk språk
- "Sist oppdatert: 2026-02-11" i toppen av hver fil
- Tabeller for strukturert data
- Koblinger til WordPress IDs der relevant
- Ingen duplisering av CLAUDE.md-innhold
