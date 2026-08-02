# SEO-arbeidsplan — systematisk mot topplasseringer (aug 2026 →)

> Erstatter ad-hoc-prioritering etter juni/juli-målingen. Bygger på 22-punkts-roadmapen
> (Phase 0+1 teknisk = DONE 2026-06-23) og GSC-baseline 23. juni – 29. juli 2026.
> Alt her er målt, ikke antatt. Kilder: GSC begge domener, PageSpeed Insights 2026-07-31,
> lokal+prod DB-inspeksjon 2026-08-01.
> **Innsiktene bak planen (skriveregler, SERP-målinger, målemetode) vedlikeholdes i
> `docs/strategy/seo-innsikter.md`** — les den før hver innholdsjobb (I1–I7).

---

## Situasjonen i én setning

Teknikken er på plass og merkevaresøk eier vi (pos 1–3); på kommersielle søk ligger vi
side 2–3 fordi sidene som skal fange dem har 26–46 ord innhold — og den interne
lenkeveien til de 100 referansene er 85 % blind pga. en taksonomi-feil.

## Mål og KPI

| KPI | Baseline (23/6–29/7) | Mål neste måling |
|---|---|---|
| Klikk fra ikke-merkevare-søk, .no | ~5 av 575 | Målbar vekst (tosifret) |
| `industrigulv`-klyngen (261 visn.) | pos 6,8 (best) | topp 5 |
| `storkjøkken`/`gulv restaurant` (151 visn.) | pos 31,1 / 11,4 | side 1 |
| `næringsmiddelindustri`/`gulv hygienekrav` (231 visn.) | pos 19,6 / 8,3 | side 1 |
| `gulv til kontor` (106 visn.) | pos 17,0, ingen side | side finnes + side 1 |
| Epoksy-søk | pos 30–68 (usynlig) | målbar bevegelse |

**Måles:** første virkedag hver måned, GSC begge domener, samme metode som baseline.
Neste målepunkt: ~1. september 2026. Rapport-artifacten oppdateres da.

**Forventningsstyring (viktig mot kunde):** volumene er små — 180 visninger på 37 dager
er 5/dag. Selv topp 3 gir tosifrede klikk per måned per side. Verdien ligger i at én
ordre er stor, ikke i trafikkvolum. Dette skal sies eksplisitt til Monika.

---

## Spor 0 — Verifiseringer og beslutninger (gratis, gjøres først)

| # | Hva | Hvorfor | Status |
|---|---|---|---|
| V1 | GSC sidefilter: hvilken side eier `industrigulv`-visningene? | Avgjør om pillar-siden skal konkurrere med eller lenkes fra forsiden | ☐ |
| V2 | GSC sidefilter: hvilken side får `gulv til kontor`? | Bekreft/avkreft intent-kollisjon med kontor-CPT | ☐ |
| B1 | Beslutning: tiltak A — norsk tittel-kvalifikator («Gulv til X») i `class-meta-titles.php` | 12 sider på én gang, én kodeendring | ☐ venter på Andreas |
| B2 | Beslutning: tiltak E — `industrier`-arkiv (tom post, aktivt arkiv) | Tynn indeksert side | ☐ venter på Andreas |
| B3 | Avklaring m/kunde: skal EN-referansefilteret vise norske firmanavn? | Åpen siden 2026-06-25 | ☐ venter på kunde |

## Spor 1 — Teknisk fikspakke (én deploy-runde, lav risiko)

Samles og deployes som ett sett. Alle er billige og målt.

| # | Fiks | Effekt | Kilde |
|---|---|---|---|
| T1 | Tiltak A (hvis B1=ja) | Relevanssignal på alle 12 bransjesider | mu-plugin |
| T2 | Tiltak E (hvis B2=ja) | Fjerner tynn indeksert side | functions.php / innhold |
| T3 | «Read more»-lenker forsiden .com → beskrivende ankertekst | .com SEO-score 92→100 + internlenke-signal | todo 015 |
| T4 | info-card aria-fiks ×2 | Tilgjengelighet 95→100 begge domener | Lighthouse |
| T5 | /dev/, /feed/-URLer, uploads-listing: noindex/robots/fjern | Rydder 83 «crawled not indexed» | GSC |
| T6 | Mobil-LCP forside .no: hero-bildet (6,0 s → mål < 2,5 s) | Ytelse 72→90+ mobil | PSI 2026-07-31 |
| T7 | Fjern publisert redigeringsnotat «(link til Deep Dive 1)» på `/levetids-kostnader/levetid/` (+ evt. språkfeil-runde på byråtekstene, se innsikter §4) | Kvalitet/tillit | Målt 2026-08-02 |
| T8 | Deploy tankestrek-fiks: `—` → `–` i meta description-malene (`acrylicon-seo/modules/class-meta-descriptions.php`; fikset lokalt 2026-08-02, ikke deployet). Norsk standard er kort tankestrek; Andreas' beslutning. | Typografi i SERP-snippets | Andreas 2026-08-02 |

T6 er litt større enn de andre (bildeformat/preload/srcset på forsiden) men samme
deploy-runde. NB [[wp-image-scaled-threshold]] og WebP-serving er allerede wired i
.htaccess — trenger bare .webp-generering.

## Spor 2 — Referanse-retagging (⚠️ HØYRISIKO — `/effort xhigh` før kjøring)

**Funn 2026-08-01:** `acrylicon-shared-taxonomies.php` tvinger alle blogger til å lese
blog 1 sine taksonomitabeller. Referansene ble tagget i `wp_3_term_relationships` —
tabellen siten ikke leser. Effektivt: **15 av 100 referanser** har bransje-tag;
Næringsmiddel, Offshore og Verksteder har 0. Konsekvens: «No posts found in this term»
på bransjesider (verifisert i prod begge domener), og den viktigste interne lenkeveien
til referansene er død — forklarer hvorfor 100 referanser henter maks 8 klikk.

**Jobb:** migrér taggene fra `wp_3_term_relationships` → delt `wp_term_relationships`
for blog 3-referansene. ~85 poster, datamutasjon i prod.

**Krav før kjøring:**
1. Full mapping-tabell (post → term) fremlagt og godkjent først
2. DB-backup av `wp_term_relationships`
3. Tørrkjøring lokalt, verifiser showreel på alle 12 bruksomrader-sider
4. `/effort xhigh`

**Kobling:** todo 009 (engelske termnavn vises i norsk kontekst) er samme systemområde.
Retagging gjør 009 mer synlig (flere refs viser term-pills) — vurder minimum
navne-beslutning samtidig.

### SERP-måling 2026-08-01 (google.com, gl=no, pws=0, verifisert i ren kontekst)

- **`gulv næringsmiddelindustri`: AcryliCon er nr. 1** — allerede, med 40-ords-siden.
  Kvalifiserte «gulv + bransje»-søk er vinnbare NÅ; det er de brede enkeltordene vi taper.
- **`gulv storkjøkken`: AcryliCon er nr. 3** (bak Flowcrete 390 ord, Polyflor 729 ord).
  Nr. 1 er innen rekkevidde med innhold.
- **`storkjøkken` alene er FEIL INTENT** — SERP-en er 100 % utstyrsleverandører, null gulv.
  Pos 31,1 på dette ordet er ikke et tap; slutt å regne det som mål. Målordet er
  `gulv storkjøkken`.
- **`industrigulv` topp 3 har 568–844 ord** (industrigulv.com 679, beleggspesialisten 844,
  industrigulvspesialisten 568). 600–900-anbefalingen er dermed MÅLT, ikke skjønn.
- **Ord alene vinner ikke:** Hummervoll har 1541 ord på pos 7, Sika Ucrete 1779 på pos 5,
  mens Flowcrete vinner `gulv storkjøkken` med 390. Autoritet/merkevare teller — bekrefter
  at linkbuilding kan bli nødvendig for selve toppen (lag 4, ikke målt).
- sto.no lot seg ikke måle med curl (JS-rendret).
- **`gulv verksted akryl`: TOBB Byggdrift nr. 1, AcryliCon ~pos 13–17 med FORSIDEN** (ikke
  Verksteder-siden). Årsak målt: Verksteder-siden inneholder ordet «akryl» **0 ganger**
  («verksted» ×15, «gulv» ×8) — merkevarenavnet «AcryliCon» staves med c og teller ikke som
  det norske ordet. Tobb vinner med 174 ord fordi tittelen+teksten dekker alle tre søkeord
  («Industrigulv - Akryl, Epoxy, Polyuretan», akryl ×7). SERP-en er ellers svak (forum- og
  Facebook-poster på side 2) → lett vinnbar. **Sjekket alle 12 bransjesider 2026-08-01:
  «akryl» = 0 på SAMTLIGE** (og «gulv» bare 2–3× per side). Krav til I1–I5: hver
  bransjeside skal inneholde «akryl»/«akrylgulv» naturlig 3–4×. Næringsmiddel-utkastet
  (post 1891, lokal) er allerede rettet: 4 forekomster vevd inn.

## Spor 3 — Innhold (hovedjobben)

Mal bevist på Næringsmiddelindustri (post 1891, lokal, 690 ord): 7 blokker —
krav → hvorfor vanlige gulv svikter → herdetid/driftsstans → levetidskostnad →
dokumentasjon/sertifiseringer → navngitte prosjekter m/lenker → riktig system.
Kilder: `docs/context/products.md`, `certifications.md`, dybdecase-referansene, **og
byråtekstene under /fordeler/, /gode-grunner/, /levetids-kostnader/, /baerekraft/** —
faktagrunnlaget der (kjemi, N/mm², HMS, sertifiseringer) er sterkt men usøkbart skrevet;
gjenbruk faktaene, ikke vokabularet (målt 2026-08-02, innsikter §4). NB: byråtekstene
distanserer AcryliCon fra ordet «akryl» — posisjoneringsvalget må avklares med kunden
før I1–I5 sluttføres.

**Rekkefølge (etter posisjon × kjøpsintensjon, ikke råvolum):**

| # | Side | Klynge (visn.) | Nå | Merknad |
|---|---|---|---|---|
| I1 | **Deploy Næringsmiddel til prod** | 231 | **hos kunde til godkjenning** (prod-kladd 5907, preview-lenke sendt 2026-08-02, token utløper 16/8) | Ved godkjenning: spleis kladd-innhold inn i post 1891 på prod, purge cache, slett kladd 5907 |
| I2 | **Industrigulv pillar-side** (ny) | 261, pos 6,8 | finnes ikke | Kortest vei til klikk. Avhenger av V1. Samme spor som epoksy-pillar i roadmapen |
| I3 | **Kontorbygg** (ny bruksomrade el. landingsside) | 106, pos 17 | finnes ikke | Avhenger av V2 (intent-kollisjon m/kontor-CPT) |
| I4 | **Hotell/storkjøkken** (utvid eksisterende) | 151 | **hos kunde til godkjenning** (skrevet 2026-08-02 m/tekstforfatter-skillen, 623 ord kropp; prod-kladd 5908, token utløper 16/8) | Ved godkjenning: spleis inn i post 1883 på prod, purge cache, slett kladd 5908 |
| I5 | Resterende 9 bruksomrader-sider | lavere | 26–46 ord | Batch, 2–3 per økt |
| I6 | Epoksy-pillar + «akryl vs. epoksy» | pos 30–68 | mangler | Roadmap-punktet; strategisk kjernehistorie |
| I7 | Article-schema (roadmap rank 4) | — | — | Etter at innholdet finnes |

**Per side trengs fra Acrylicon (faktaliste sendes samlet, ikke stykkevis):**
- Én konkret forskrifts-/standardhenvisning per bransje (fagansvarlig verifiserer)
- Ett driftsstans-tall fra et navngitt prosjekt («lagt på en helg, åpnet mandag»)
- Ev. kr/m²-intervaller (valgfritt)
- Bilder der bransjen mangler gode

**Avhengighet:** Spor 2 (retagging) bør lande før/parallelt med I4–I5, ellers har
sidene tomme referanse-showreels ved lansering.

## Spor 4 — Måling og kadens

1. Månedlig GSC-snapshot (1. virkedag), samme kolonner som baseline
2. Oppdater rapport-artifact + kundeversjon ved vesentlig endring
3. Etter 2 målinger: revurder rekkefølgen i Spor 3 mot faktisk bevegelse

## Spor 5 — Bilde-SEO (målt 2026-08-02, innsikter §6)

Google Bilder «akrylgulv» = null AcryliCon; konkurrentene eier SERP-en. Rotårsak målt:
**0 av 1007 bilder (.no) / 0 av 1030 (.com) har alt-tekst** — alt rendres `alt=""`.

| # | Oppgave | Merknad |
|---|---|---|
| B-1 | Batch-generér alt-tekster fra kontekst (referanse/produkt/side + filnavn) → gjennomgang → `_wp_attachment_image_alt` | Dry-run lokalt + postmeta-backup; ⚠️ `/effort xhigh` før prod (~2000 rader) |
| B-2 | Render-filter (`wp_content_img_tag`) i mu-plugin: fyll tom alt i innholds-bilder fra mediebiblioteket | Innbakt `alt=""` i post_content er frosset ved innsetting — fikses ikke av mediebiblioteket alene |
| B-3 | Filnavn-policy for nye opplastinger (beskrivende + søkeord) | IKKE omdøp eksisterende (URL-brudd) |

Avhengighet: full effekt krever at sidene inneholder «akrylgulv» (Spor 3) — bilder
rangerer via siden de står på. Alt-tekst alene løfter ikke bilde-SERP-en.

## Spor 6 — Lovkrav: personvern + WCAG (kartlagt 2026-08-02)

**Personvern/cookies — banneret er IKKE lovpålagt, samtykket er.** Ny ekomlov § 3-15
(jan 2025) krever GDPR-nivå samtykke FØR ikke-nødvendige cookies/lagring; nødvendige
cookies er unntatt. To lovlige tilstander: (a) samtykkepliktig sporing + ekte CMP-banner,
(b) ingen samtykkepliktig sporing + **ingen banner** (Andreas' preferanse — fullt lovlig).

Målt status 2026-08-02: **ingen banner/CMP finnes**, men to samtykkepliktige sporinger
fyrer likevel: (1) GA4 ×2 via egen GTM-5496VPK (hostname-delt G-D2YGZGKMXP/.no +
G-8R9B6YK8F7/.com — setter `_ga`-cookies), (2) Byggfakta B2B-sporing (`stats.docu.info`,
hardkodet header.php:31). Ingen server-cookies, ingen ad-pixels (Brists ble fjernet ved
container-byttet 2026-06-24). Dagens oppsett er dermed ikke i samsvar med ekomloven.
Hele stacken er vår egen — ingen Brist-avhengighet for å endre.

**Avklart med Andreas 2026-08-02:** Byggfakta-innsikten brukes aktivt av kunden, og
Google-trackinga likeså → **begge beholdes**. Målt: Byggfakta-snippeten kjører standard
Matomo UTEN `disableCookies` + med `enableCrossDomainLinking()` → setter `_pk_id`-cookies
(13 mnd) = samtykkepliktig, akkurat som GA4. Banner-fritt er dermed utelukket — veien er
**ekte CMP, gjort så lavfriksjons som lovlig mulig**. Ærlig konsekvens: sporerne ser kun
samtykkende besøkende etter dette (typisk 60–80 %) — Byggfakta-leads-dekningen synker
tilsvarende; det er prisen for samsvar, og den kan ikke omgås teknisk.

| # | Oppgave | Merknad |
|---|---|---|
| P-1 | Velg CMP + spør Byggfakta om cookieless-modus | CMP-anbefaling: Complianz (selvhostet samtykke-logg, gratis kjerne, Consent Mode v2- og GTM-integrasjon). Byggfakta-spørsmål: finnes cookieless/`disableCookies`-variant av docu-snippet? I så fall kan Byggfakta kjøre banner-uavhengig (redusert presisjon), og banneret gater bare GA4 |
| P-2 | Implementér: CMP-banner + Consent Mode v2 i GTM-5496VPK (vår container) + gate Byggfakta-snippeten på statistikk-samtykke | Banner designes i sitens profil (dark-blue/rød), like lett «avslå» som «godta» (lovkrav). GA4 får modellerte tall for ikke-samtykkende via Consent Mode |
| P-3 | WCAG-mini-audit begge domener (Lighthouse a11y + manuell: skjema, kontrast, aria, tastatur) | Kjente funn: info-card aria ×2; alt-tekst dekkes av Spor 5 |

**WCAG er lovpålagt** for private virksomheter rettet mot allmennheten: likestillings- og
diskrimineringsloven § 18 + forskrift om universell utforming av IKT → **WCAG 2.0 nivå AA**;
uu-tilsynet fører tilsyn og kan sanksjonere. Alt-tekst (suksesskriterium 1.1.1) = Spor 5
B-1/B-2 er dermed lovkrav og SEO i samme jobb.

## Bevisst UTENFOR planen (ikke glemt, nedprioritert)

- Linkbuilding / digital PR — først når innholdet finnes å lenke til
- .com-innhold utover sync — .com er 100 % merkevaresøk; Norge først
- R2/CDN-media-prosjektet (todos 001–008) — eget prosjekt, unntatt T6-bildet
- GA4-besøkstall (property-ID-jakt) — GSC dekker behovet nå
- Todo 013 (international launch checklist) — eget løp

## Arbeidsform

Én økt = ett spor-element, ferdig verifisert (lokal → prod → cache-purge → visuell
sjekk med BAR URL). Innholdssider skrives alltid lokalt først, godkjennes, deployes.
Backup av original post-content til scratchpad/backup/ før hver mutasjon.

---

*Opprettet 2026-08-01. Vedlikeholdes ved at status-kolonnene oppdateres og nye funn
legges til — ikke ved å skrive ny plan.*
