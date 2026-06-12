---
date: 2026-05-13
topic: dynamic-product-sheets-rollout
---

# Dynamiske Produktark — Post-Demo Brainstorm + Implementeringsbeslutninger

**Bakgrunn:** Bygger videre på `docs/brainstorms/2026-02-12-dynamic-product-sheets-brainstorm.md`. Den opprinnelige brainstormen definerte MVP ("erstatt 10 PDF-er med dynamisk template"). Prototypen ligger på branch `feature/dynamic-product-sheets` og fungerer (verifisert lokalt 2026-05-12 og 2026-05-13).

**Demo-status:** Prototypen ble vist Monika (kunde-representant fra AcryliCon) under kunde-sync 2026-05-13. Demo og feedback er **gjennomført**, ikke fremtidig. Transcript fra synken (`synk-monika.json`) inneholder retning, validering, og nye krav (QR/Goodstag-integrasjon). Denne brainstormen oppsummerer hva som er **avklart** fra demo, hva som gjenstår, og strategisk posisjonering for videre arbeid.

## Problem Frame (post-demo)

Marketing-arket er retnings-validert av kunde. Prototypens *struktur* ble vist (`?view=sheet`) og Monika engasjerte med konseptet — bekreftet "source of truth"-tankegang, omfavnet mobil-bruk + SEO, åpnet for QR-integrasjon, og avklart språk-arkitekturen. Det som gjenstår er: (a) implementere strukturparity med hånd-PDFene fra Mid/Vest Norge, (b) håndtere QR-integrasjon mot Goodstag, (c) sekvensere innholdsfyll, og (d) integrere mot internasjonal lansering som går live ~uke 21.

## Requirements

**Strukturell parity med hånd-PDFene (Side 1)**
- R1. Header viser kun "Acrylicon [System Name]" — fjern "Produktdatablad"-label
- R2. Hovedbilder hentes fra ny ACF Gallery-felt `sheet_gallery` på Produkter CPT (4 bilder, vist som 2 par à 2 bilder — ett par over fakta-pillene, ett par over bullets — matching hånd-PDFene)
- R3. Faktapiller rendres som kompakte beige piller (3-4 stk i én rad) — data hentes fra eksisterende `feature-card`-blokk; ingen nye ACF-felter
- R4. Beskrivelse vises som ren paragraf — fjern "Beskrivelse og bruk"-header
- R5. "Viktigste egenskaper og fordeler" rendres som 7 brand-nivå bullets, identiske på tvers av alle produkter innen samme blog/språk
- R6. Brand-bullets lagres på ACF Options-side "Produktarkinnstillinger", redigerbart per blog (NO + EN)

**Strukturell parity (Side 2+)**
- R7. Teknisk informasjon (full tabell fra `technical-info-table`-blokk) vises på side 2
- R8. Nedlastinger (`download-list`-blokk) vises i webversjon som side 3, men skjules i print
- R9. Hvis `sheet_gallery` er tomt, skjules bildeseksjonene helt (ærlig signal til editor om at innhold mangler)

**Behold/ikke endre**
- R10. Eksisterende `feature-card`-blokk vises som før på vanlig produktside — kun rendringen i arket endres
- R11. URL-struktur (`?view=sheet`) endres ikke
- R12. Eksisterende `/downloads/`-side endres ikke (statiske PDFer kan fortsatt brukes parallelt)

**Multispråk-bevissthet (design nå, implementer NO+EN)**
- R13. Alle UI-labels i template bruker WordPress i18n (`__()` / `_e()`) slik at oversettelse er én plugin-installasjon eller blog-tillegg unna — ikke en omskriving
- R14. Brand-bullets-ACF-felt skal være per-blog, ikke globalt — slik at hvert språk har egen versjon

**Demo-leveranse**
- R15. Demo-URL skal være tilgjengelig på minst tre produkter (variert datatilstand: ett med fullt galleri, ett uten galleri, ett med uvanlig kort/lang beskrivelse)
- R16. Demo viser side-by-side: ny prototype + en av hånd-PDFene (Multigrip, Wall eller Variant)
- R17. Demo viser samme produktark på både NO (blog 3) og EN (blog 1)

## Success Criteria

- Teamet kan med rimelig sikkerhet svare på: "Er dette riktig retning?" basert på prototypen
- Teamet kommer med konkrete tilbakemeldinger på struktur, innhold og layout — ikke generelle "ser fint ut"-kommentarer
- Forventningsavklaringen lykkes når marketing/salg ikke spør "skal dette se ut som en Illustrator-PDF?" (struktur signaliserer at det er WordPress-rendret, ikke kopi av designet PDF)
- De tre åpne temaene (eierskap, oversettelser, selvbetjening) blir reist *av teamet selv* i demoen, så vi fanger nyansene direkte fra dem

## Scope Boundaries

- **Odoo-integrasjon er IKKE i scope.** Vest/Midtnorge bruker Odoo med statisk PDF-bibliotek. Dagens flyt opprettholdes — vi gjør ikke noe automatisk push til Odoo.
- **"Begynne å bruke"-rollout er IKKE i scope.** Demo-målet er validere retning + få tilbakemelding. Eventuelle workflow-, opplærings- eller tilgangsspørsmål håndteres etter demo.
- **Multispråk utover NO+EN er IKKE i scope.** Designet skal være multispråk-bevisst (R13, R14), men kun NO+EN implementeres nå.
- **Server-generert PDF (dompdf/wkhtmltopdf) er IKKE i scope.** Print-CSS gir 90% av verdien med 10% av kompleksiteten — i tråd med vurderingen fra 2026-02-12-brainstormen.
- **Pixel-likhet med hånd-PDFene er IKKE et mål.** Strukturell parity er målet — typografi/farger/spacing følger Acrylicons nye web-design.
- **Erstatte den eksisterende `/downloads/`-siden er IKKE i scope.** Eksisterende statiske PDFer beholdes, sheet er et tillegg.
- **Nytt permissions-/godkjenningssystem er IKKE i scope.** Eksisterende WordPress-roller styrer redigeringstilgang som før.

## Key Decisions

- **Multi-page tillatt, seksjoner per side definert** — bruker bekreftet at virkelig bruk ofte spenner flere sider, men hver seksjon må ha en forutsigbar plass. Teknisk info havner på side 2 by design.
- **Hånd-PDFene definerer struktur, ikke design** — vi matcher seksjonene (tittel/galleri/piller/beskrivelse/galleri/bullets) men beholder Acrylicons web-typografi og -farger.
- **Brand-bullets er identiske på tvers av produkter (innen ett språk)** — bekreftet ved sammenligning av hånd-PDFene. De 7 punktene er brand-meldinger, ikke produkt-spesifikke.
- **Faktapiller gjenbruker eksisterende feature-card-blokk** — ingen ny ACF-felt-duplisering. Endring isolert til template-rendring.
- **Tom sheet_gallery = skjul bildeseksjoner** — ærligere signal til editor enn å gjenbruke featured image som filler. Aksepterer at noen ark vil se "naken" ut til galleriet fylles.
- **Demo-mål er validere retning + tilbakemeldinger** — ikke produksjonslansering. Tillater åpne diskusjonspunkter (eierskap/multispråk/selvbetjening) som diskuteres i demoen.

## Dependencies / Assumptions

- ACF Pro er aktivt (verifisert — eksisterer på master)
- Multisite-strukturen NO (blog 3) / EN (blog 1) opprettholdes
- 12 produkter har Gutenberg-innhold inkludert `feature-card`-blokk (verifisert lokalt for Dekor System — 4 piller ekstraheres riktig)
- Bruker er ansvarlig for å fylle `sheet_gallery` med 4 bilder per produkt etter implementering (12 produkter × 4 bilder = 48 bilder). Sheet-fallback håndterer manglende bilder ved skjuling.
- Bruker er ansvarlig for å fylle de 7 brand-bullets i ACF Options én gang for NO og én gang for EN

## Outstanding Questions

### Resolve Before Planning

*(Ingen — alle produktbeslutninger er tatt. Følgende åpne tema er eksplisitt diskusjonspunkter for team-demoen, ikke planning-blokkere.)*

### Deferred to Planning

- [Affects R7][Technical] Hva gjør vi når et produkt mangler `technical-info-table`-blokk? Skjul side 2, eller vis tom side med kun overskrift?
- [Affects R3][Technical] Hva hvis et produkt har mer enn 4 feature-card-blokker? Begrens til 4, eller flyt til ny rad?
- [Affects R4][Technical] Hva med svært lange beskrivelser? Trunker, eller la flyte over til side 2 før tech-info?
- [Affects R1, R2][Technical] Hva gjør vi når featured image og sheet_gallery begge mangler? Skjul hele hero-området?
- [Affects R15][Needs research] Hvilke tre produkter er best egnet som demo-eksempler? (Dekor System har fullt innhold, andre kan ha hull)
- [Affects R8][Technical] Print-CSS sidebrudd — current `print-page-2`-tilnærming må verifiseres med ny seksjonsstruktur

### Strategisk posisjonering: WordPress som source-of-truth (etter Goodstag-analyse 2026-05-13)

Etter inspeksjon av Goodstag-plattformen (`acrylicon.goodstag.com`) ser den større posisjonen slik ut:

**Dagens dataflyt:**
```
Fabrikk DE  →  Word/PDF  →  AcryliCon laster manuelt opp  →  Goodstag DPP
                                          ↑
                              WordPress (NO) — i dag isolert
```

**Foreslått dataflyt:**
```
Fabrikk DE  →  Compliance-PDFer (SDS, EPD, sertifikat)  →  Goodstag DPP
                            ↑
WordPress (NO) ─────────────┘  genererte marketing-/tech-sheet-PDFer
            = single source for alt som IKKE er fabrikk-regulert
```

**Hvorfor dette gir styrket forhandlingsposisjon for AcryliCon Norge:**
- Goodstag er kun like god som filene de mates med
- I dag eier AcryliCon Norge dataen i hodene, men ikke produksjonen av PDFene
- Hvis WordPress genererer marketing-, tech-, referanse-ark som mates inn i Goodstag, eier vi *kilden* og kontrollerer *flyten*
- Compliance-data (SDS/EPD/CE) skal *fortsatt* eies av fabrikken — det er ikke vårt territorium

### Dokumenttype-matrise (hva som bør genereres fra WP)

| Dokumenttype | I Goodstag? | Eier | WP genererer? | Prioritet |
|---|---|---|---|---|
| **Marketing-ark (dagens leveranse)** | Nei | Norge | ✅ Bygges nå | P0 — kjernen |
| **Tech Sheet PDF (matches dagens DS01-EN_Tech Sheet)** | Ja, kun EN-fil | Norge har samme data | ✅ Lavt-hengende neste skritt | P1 — utvider per språk i Goodstag |
| **Reference pack (samling per kunde)** | Nei | Norge | Ja — i digital-roadmap Lag 3 | P2 |
| **Care/Maintenance manual** | Ja, lokal-språk-PDF | Norge har innholdet | Mulig | P3 |
| **SDS (Safety Data Sheet)** | Ja, per komponent | **Fabrikk DE — regulert under REACH 1907/2006** | ❌ Skal *ikke* eies av Norge | Ikke-mål |
| **EPD/Sertifikat** | Ja | Akkrediterte org / fabrikk | ❌ | Ikke-mål |
| **DPP-side selv** | Goodstag-plattform | Goodstag | ❌ Goodstag løser det allerede | Ikke-mål |

### Bekreftet fra sync med Monika (kunde) 2026-05-13

Mye av det vi hadde som åpne antakelser ble avklart i kundesynk samme dag som denne brainstormen:

- **Språk-arkitektur:** sheet-språk = blog-språk. NO+EN launch denne uka. Italia får egen blog når den kommer. UK har allerede egen side (lenkes, ikke konsolideres).
- **Språkprioritet etter NO+EN:** Italia, Frankrike, Spania, Portugal innen 1-2 år (investering nær fabrikken). Finland kan bli tidlig pilot.
- **DE-workaround:** Tyskland har ikke egen blog ennå, men produkter finnes teknisk på tysk via fabrikken. AI-oversettelse fra EN er akseptabel midlertidig løsning.
- **Strategisk dual-use (bekreftet av Monika):** internt sparer fabrikken i Tyskland "hundrevis av timer" (erstatter dagens Illustrator-flyt der de laster ned fra acrylicon.no). Eksternt: mobil + SEO/AI-søkbart innhold er ny verdi.
- **Source-of-Truth omfavnet:** Monika sa "what we have here is the source of truth". Mønsteret kan utvides til andre datatyper utover produkter.
- **Co-design-grunnlag:** Eva + Anders fra Mid/Vest Norge har designet den strukturelle referansen i Figma sammen med deg. De vil kjenne igjen sitt arbeid i team-demoen.
- **Nytt strategisk element: QR-kode-integrasjon:** Monika ruller ut QR-koder per produktsystem fra 2027 (lovkrav). QR-en linker til tredjeparts-plattform "Gods Tag" med all teknisk data. Produktarket må vise QR-en og kan inngå i samme dataøkosystem.

### Skarpere spørsmål for internasjonal bruk (etter Monika-sync)

Disse erstatter de generiske usage-spørsmålene fra forrige brainstorm-runde — nå med konkret kontekst:

1. **Per-land brand-identitet:** Italia får egen blog. Skal arket der ha italiensk forhandlers logo/kontakt/sertifiseringer, eller AcryliCon-internasjonalt? Følger sheet-stylingen den lokale bloggens design, eller en delt mal? *(Påvirker R14 ACF Options per-blog-modellen)*
2. **DE-arket uten DE-blog:** Mens Tyskland ikke har egen blog — hvor "lever" det tyske arket? Generert ad-hoc via AI-oversettelse av EN-arket? Egen midlertidig URL? Eller venter vi til en DE-blog opprettes? *(Påvirker scope for NO+EN-launch)*
3. **QR-kode-plassering i arket:** Hvor på siden vises QR-en? På side 1 (synlig i fysisk møte) eller side 2 (under teknisk info)? Skal URL-en til arket selv inkluderes i QR-en, eller kun "Gods Tag"-data? *(Nytt krav som ikke var i R1-R17)*
4. **Cross-language sheet-bruk:** Kan en norsk selger gi en italiensk-versjon av arket til kundens italienske datterselskap? Skal det være lett å bytte språk i arket (knapp), eller må kunden selv navigere til italiensk-bloggen?
5. **Innholdseierskap per blog:** Når Italia/Finland kommer online — eier de innholdet på sin blog selv (lokal forhandler kan endre), eller pulles fra norsk kilde via multisite-sync slik dagens NO→EN-flyt fungerer?
6. **Sertifiseringer per land:** Tyske ISO 9001/14001, BREEAM, CE-merker — er disse identiske på tvers av land, eller varierer regulatoriske markeringer? Skal disse vises i arket?
7. **A4-utskrift vs mobil vs SEO — én eller flere mal-varianter?:** Selger som printer i kundemøte trenger A4-A. Kunde som skanner QR på mobil trenger responsive web. Google AI som indekserer trenger semantisk HTML. Kan samme template tjene alle tre, eller trenger vi 2-3 visningsmoduser?
8. **"Adam UK"-spørsmålet:** Monika nevnte at England-representanten ville ha egen side. Hvis UK ikke konsolideres i internasjonal side — får de tilgang til produktark-generatoren for sin side? Hvordan deler vi infrastrukturen?

### Avklart i demoen — ingen åpne spørsmål her

Følgende ble besvart eller validert direkte i sync med Monika 2026-05-13. Listen erstatter tidligere "For Team Demo Discussion"-tema.

- **Retning:** Validert. Monika sa eksplisitt "what we have here is the source of truth". Strukturen ble forstått som riktig vei.
- **Hånd-PDF-provenans:** Designet i Figma sammen med **Eva + Anders i Mid/Vest Norge**. Representerer en konkret salgsenhets behov, ikke én persons preferanse — derfor reell co-design-validering.
- **Oversettelser:** sheet-språk = blog-språk. NO+EN nå (launch ~uke 21). Italia/Frankrike/Spania/Portugal får egen blog innen 1-2 år. UK har egen side allerede. Tyskland uten egen blog: AI-oversettelse fra EN aksepteres som midlertidig.
- **Brand-bullets per produkt-familie:** Monika åpnet for at noen produkter (Lacquer, Composite Terrazzo) kan ha ulike bullets. Strategi: bygg som default-fra-Options + per-produkt-override fra start, ikke singleton.
- **Bruks-press / gating:** Ikke et reelt problem i nær fremtid — Monika selv ønsker å komme i bruk, og lansering er internt styrt. URL-en kan stå åpen.
- **Mobile-first:** Bekreftet kritisk. Selger som sender QR-kode → kunde scanner på mobil → må fungere uten print-tweaks.
- **SEO-verdi:** Bekreftet strategisk verdi. "Public HTML — perfect for Google AI" var Andreas' formulering, ikke avvist.
- **Discoverability:** "Se produktark"-trigger ligger naturlig på produktside (knapp). Replikere hvordan Crona/eksisterende workflow ser ut. Ikke noe nytt navigasjons-konsept.
- **Goodstag-relasjon (nytt etter demo):** Kunde-arket er marketing-/sales-verktøy. Goodstag er compliance-platform. De er **komplementære, ikke konkurrerende**. WordPress kan over tid bli source-of-truth som mater Goodstag.

### Open Questions / Planning (tekniske, fra doc-review)

- **[Affects R6][Technical/Code-verified]** Eksisterende `acrylicon_parse_product_sheet_data` henter benefits fra `core/list`-blokk i `post_content`. R6 erstatter med ACF Options. Skal vi: (a) fjerne `core/list`-pathen, (b) bruke den som fallback når Options er tomt, eller (c) la editor velge per produkt?
- **[Affects R6][Technical/Code-verified]** Temaet har ingen ACF Options-side i dag (grep for `acf_add_options_page` returnerer 0). Velg registrering-pattern: vanilla `acf_add_options_sub_page` i functions.php, eller egen `inc/options.php`. Per-blog-strategi for multisite må bekreftes.
- **[Affects R13][Technical/Code-verified]** Tema har ingen `load_theme_textdomain()`, ingen .pot-fil, eksisterende `__()`-kall mangler textdomain. R13 forutsetter at i18n-infrastruktur eksisterer — det gjør den ikke. Velg: drop R13 og hardcode NO/EN, eller commit til full i18n-setup som egen oppgave.
- **[Affects R15][Owner/Sequence]** Demo-prerequisites: hvem laster opp 4 bilder for minst ett demo-produkt før demoen? Hvem skriver 7 EN brand-bullets? Hvis ikke gjort før demo, blir alle 12 produkter "naked" (R9 skjuler bildeseksjoner) — bias-risiko mot hånd-PDF-referansen.
- **[Affects R7][Technical]** Hva gjør vi når et produkt mangler `technical-info-table`-blokk? Skjul side 2, eller vis tom side med kun overskrift?
- **[Affects R7][Technical]** Hva hvis tech-info-tabellen har 20-40 rader (industri-produkter)? Print-CSS sidebrudd inni en `<table>` er notorisk skjørt. Behov for testing.
- **[Affects R3][Technical]** Hva hvis et produkt har mer enn 4 feature-card-blokker? Begrens til 4, eller flyt til ny rad?
- **[Affects R3][Technical]** Hva med 0 feature-cards (tom row)? Skjul piller-rad eller vis placeholder?
- **[Affects R4][Technical]** Hva med svært lange beskrivelser? Trunker, eller la flyte over til side 2 før tech-info?
- **[Affects R1, R2][Technical]** Hva gjør vi når featured image og sheet_gallery begge mangler? Skjul hele hero-området?
- **[Affects R2][Technical]** Hva hvis sheet_gallery har 1, 2 eller 3 bilder (ikke 4)? Vis bare første par? Full-width singel? Skjul andre par?
- **[Affects R15][Needs research]** Hvilke tre produkter er best egnet som demo-eksempler? (Dekor System har fullt innhold, andre kan ha hull)
- **[Affects R8][Technical]** "Vises som side 3 i webversjon" — betyr det visuell sideskille/page-break på skjerm, eller bare lang scroll? Print-CSS sidebrudd må verifiseres med ny seksjonsstruktur.
- **[Affects R17][Scope]** Krever bilingual demo content-fyll på blog 1 (EN) som ligger utenfor "strukturpolish"-scope? Kan demoen kjøres kun på NO, eller har vi EN-stakeholdere i rommet?
- **[Affects R3][Verification]** Feature-card-parser er kun verifisert på Dekor System (1 av 12). Kjør `wp eval` over alle 12 produkter for å bekrefte parser fungerer før vi går videre.

## Next Steps

Demo er fullført med Monika 2026-05-13. Retning validert. Neste skritt:

1. **`/ce-plan` for implementeringsplanlegging** — alle produktbeslutninger er på plass, åpne spørsmål er enten besvart (i demoen) eller flyttet til teknisk planning (under).
2. **Sekvensere før internasjonal lansering ~uke 21:**
   - Fyll `sheet_gallery` for minst ett demo-produkt (helst Dekor System eller Multigrip)
   - Fyll 7 brand-bullets i ACF Options for NO og EN
   - Implementer R1-R17 i kode
3. **Etter lansering:** evaluere om Tech Sheet PDF-generator skal være neste skritt (P1 i dokumenttype-matrisen) — Goodstag har kun EN-versjon av Tech Sheet i dag, WP kan fylle alle språk.
4. **Langsiktig:** Goodstag-integrasjon (push av WP-genererte filer til Goodstag som single-source).

## Appendix: Sidelayout-skisse

```
SIDE 1 — Highlights
┌─────────────────────────────────────┐
│ Acrylicon [System Name]             │  R1 — ren tittel
├──────────┬──────────────────────────┤
│ Bilde 1  │ Bilde 2                  │  R2 — sheet_gallery[0..1]
├──────────┴──────────────────────────┤
│ [pill] [pill] [pill] [pill]         │  R3 — feature-card → piller
├─────────────────────────────────────┤
│ Beskrivelse-paragraf (60-90 ord)    │  R4 — ingen header
├──────────┬──────────────────────────┤
│ Bilde 3  │ Bilde 4                  │  R2 — sheet_gallery[2..3]
├─────────────────────────────────────┤
│ Viktigste egenskaper og fordeler    │  R5
│  • [7 brand-bullets]                │  R6 — fra ACF Options
└─────────────────────────────────────┘

SIDE 2 — Teknisk
┌─────────────────────────────────────┐
│ Teknisk informasjon                 │  R7
│  | Egenskap | Verdi |               │
│  | …        | …     |               │
└─────────────────────────────────────┘

SIDE 3 — Nedlastinger (kun web, print-hide)
┌─────────────────────────────────────┐
│ Nedlastinger                        │  R8
│  ▼ Tech Sheet PDF                   │
└─────────────────────────────────────┘
```
