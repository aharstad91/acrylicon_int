# SEO-innsikter — målt, ikke antatt

> Levende dokument: alt her er **målt** (GSC, SERP i ren kontekst, ordtelling på faktiske
> sider). Nye funn legges til med dato. Handlingsplanen som bruker disse innsiktene:
> `docs/plans/2026-08-01-seo-arbeidsplan-systematisk.md`.
> Sist oppdatert: 2026-08-01.

---

## 1. Skriveregler for innholdssider (operativ sjekkliste)

Disse er utledet av målingene under og gjelder **alle** nye/utvidede sider (I1–I7 i planen):

1. **Bruk ordene folk søker med — bokstavelig.** Målt 2026-08-01: ordet «akryl» finnes
   **0 ganger på samtlige 12 bransjesider** (og «gulv» bare 2–3× per side). Merkevarenavnet
   «AcryliCon» staves med c og teller IKKE som det norske ordet «akryl» for Google.
   Krav: «akryl»/«akrylgulv» naturlig 3–4× per side, «gulv» gjennomgående.
2. **Siden må dekke hele målsøket.** En side kan ikke rangere på et søk som inneholder et
   ord siden mangler — da faller Google tilbake på forsiden, som mangler bransjeordet
   (se Tobb-caset i §2). Sjekk hver side mot sine målsøk ord for ord.
3. **600–900 ord er det målte nivået på tunge søk.** Topp 3 på «industrigulv» har 568–844
   ord. Det er båndet å treffe — ikke mer «for sikkerhets skyld».
4. **Ord alene vinner ikke.** Hummervoll: 1541 ord → pos 7. Sika Ucrete: 1779 ord → pos 5.
   Flowcrete vinner «gulv storkjøkken» med 390. Over ~400 relevante ord avgjør autoritet
   og relevans, ikke volum. Ikke skriv langt — skriv dekkende.
5. **Blindsone-testen:** selv 690-ordsutkastet vårt til Næringsmiddelindustri hadde
   «akryl» ×0 før retting. Den som kan produktet godt, glemmer å bruke kategorinavnet.
   Kjør ordtelling på hvert utkast før publisering (metode i §5).
6. **Sjekk søkeintensjonen før et ord settes som mål.** «storkjøkken» alene = 100 %
   utstyrsleverandører i SERP; uvinnbart og irrelevant. Målord er «gulv storkjøkken».

## 2. SERP-målinger (google.com, gl=no, hl=no, pws=0)

| Dato | Søk | Funn |
|---|---|---|
| 2026-08-01 | `gulv næringsmiddelindustri` | **AcryliCon nr. 1** — med 40-ordssiden. Verifisert i cookie-fri kontekst. Presise «gulv + bransje»-søk er vinnbare NÅ |
| 2026-08-01 | `gulv storkjøkken` | **AcryliCon nr. 3** bak Flowcrete (390 ord) og Polyflor (729). Nr. 1 innen rekkevidde med innhold |
| 2026-08-01 | `industrigulv` | Topp 3: industrigulv.com 679 ord, beleggspesialisten.no 844, industrigulvspesialisten.no 568. Vi: nederst side 1 (pos 6,8) uten dedikert side |
| 2026-08-01 | `gulv verksted akryl` | **TOBB Byggdrift nr. 1 med 174 ord** — eneste side som dekker alle tre søkeord (tittel: «Industrigulv - Akryl, Epoxy, Polyuretan», akryl ×7). AcryliCon ~pos 13–17 med FORSIDEN, fordi Verksteder-siden mangler «akryl» helt. SERP-en er svak (forum/Facebook på side 2) → lett vinnbar |
| 2026-08-01 | `storkjøkken` | Feil intent — kun utstyrsleverandører. Fjernet som mål |

## 3. Strukturelle funn (hvorfor sidene underpresterer)

- **Taksonomi-bruddet (2026-08-01):** `acrylicon-shared-taxonomies.php` gjør at alle
  blogger leser blogg 1 sine term-tabeller. Referansene ble tagget i
  `wp_3_term_relationships` — som siten aldri leser. Effektivt: **15 av 100 referanser**
  har bransjetag; Næringsmiddel, Offshore og Verksteder har 0 → «No posts found» på
  bransjesider og død intern lenkevei. Forklarer at 100 referanser henter maks 8 klikk.
  Fiks: Spor 2 i planen (retagging, høyrisiko).
- **GSC-baseline finnes ikke før 2026-06-23** (begge domener, verifisert m/16-mnd-visning).
  Alle rapporter i denne perioden er nullpunktsmålinger.
- **.com er 100 % merkevaresøk**; .no eier merkevaresøk (pos 1–3) men lå pos 17–31 på
  kommersielle ord ved baseline. Innsatsen skal stå på .no.
- **Volumene er små i absolutte tall** (største klynge: 261 visninger/37 dager).
  Verdien ligger i ordrestørrelse, ikke trafikk — styr forventninger deretter.
- **Autoritet (lenker) er sannsynlig neste flaskehals på de tyngste ordene** etter at
  innholdet er på plass — indikert av at etablerte aktører vinner med færre ord (§1.4),
  ikke målt direkte ennå.

## 4. Byråinnholdet «Fordeler» (målt 2026-08-02)

De fire områdene fra Brist/tekstforfatter-samarbeidet — `/fordeler/`, `/gode-grunner/` (7),
`/levetids-kostnader/` (4), `/baerekraft/` (3) = **18 sider** — målt med samme metode som
bransjesidene + GSC sidefilter (23/6–31/7):

- **Avkastning: 8 klikk og 505 visninger på 5,5 uker** — 1,3 % av nettstedets 595 klikk.
  17 av 18 sider har ≤ 46 visninger. Unntaket er Herdetid (157 visn., pos 7,5).
- **«akrylgulv» finnes ikke på noen av de 18 sidene.** «akryl» brukes nesten utelukkende om
  konkurrentkategorien Akryl/MMA (som rangeres UNDER AcryliCon i styrketabellen). Samtidig:
  «epoks/epoxy» ×43 totalt. Resultat målt i GSC: Herdetid-siden vises kun på
  **epoxy**-informasjonssøk («herdetid epoxy», «epoxy herdetid», «tørketid epoxy») —
  tekstene fanger research-trafikk for konkurrentproduktet, ikke kjøpssøk for vårt.
- **Posisjoneringsparadokset:** tekstene distanserer aktivt AcryliCon fra ordet «akryl»
  («Acrylicons løsninger skiller seg ut i termoplastfamilien…»). Det er et merkevarevalg
  med målbar SEO-kostnad: man kan ikke rangere på «akrylgulv» og samtidig unngå ordet.
  Må avklares med kunden før I1–I5 sluttføres (post 1891 sier nå «AcryliCon er et akrylgulv»).
- **Meta-titler uten søkeord:** samtlige er «Sidenavn | AcryliCon» («Slitesterkt | AcryliCon»,
  «Levetid | AcryliCon»). Ingen inneholder gulv/akryl/industrigulv.
- **Arkitektur etter internt argument, ikke søk:** «Kjemisk binding», «Porefritt og
  hygienisk», «Oppsummering» — ingen søker slik. Hub-sidene har 82–89 ord.
- **Ordvolum:** 220–390 ord på de fleste; unntak HMS-siden (807 — lengst på hele nettstedet,
  20 visninger). Under det målte 600–900-båndet, og uten målsøk å dekke.
- **Kvalitet/korrektur:** publisert redigeringsnotat **«(link til Deep Dive 1)»** står i
  brødteksten på `/levetids-kostnader/levetid/` (prod). Ellers: «herdeplattgulv»,
  «Volitile Organic Compounds», «helsebestandige gasser», sirkulær definisjon
  («Herdeplastbegrepet kan deles inn i … Herdeplast og termoplast»).
- **Gjenbruksverdi (positivt):** faktagrunnlaget er sterkt — kjemi (termoplast vs. herdeplast,
  N/mm²-tabeller, herdetider), HMS (BPA/isocyanater/substitusjonsplikt), sertifiseringer
  (M1, TÜV FCC, DNV, BREEAM). Dette er råstoffet bransjesidene (I1–I5) trenger; jobben er
  å koble det til søkeordene, ikke å skrive faktaene på nytt.

## 5. Målemetode (repliserbar)

- **SERP:** Chrome MCP → `google.com/search?q=…&gl=no&hl=no&pws=0`; resultater via
  selector `div#search a h3`. Avgjørende funn verifiseres i `isolatedContext`
  (cookie-fri). WebSearch-verktøyet er US-only og ubrukelig for norske SERP-er.
- **Ordtelling:** hent HTML, strip `script/style/noscript/svg/nav/header/footer` +
  kommentarer + tagger, tell tokens med bokstaver. JS-rendrede sider (f.eks. sto.no) kan
  ikke måles slik — rapporter «ikke målbar», aldri et falskt tall.
- **Søkeordsdekning:** `grep -ci 'ordet'` på strippet HTML per målsøk-ord.
- **GSC sidefilter via URL:** `…/performance/search-analytics?resource_id=https%3A%2F%2Facrylicon.no%2F&breakdown=query&num_of_months=3&page=!<URL-enkodet eksakt side-URL>` — `!` = eksakt match (fungerer; `*contains*`-syntaks i URL gjør IKKE). Med `breakdown=page` ligger alle radene i DOM-en (`table tr`) selv om UI-et paginerer.
- Sist oppdatert-dato i toppen gjelder §1–3; §4 målt 2026-08-02.

## 6. Bilde-SEO (målt 2026-08-02)

Bakgrunn: Google Bilder for «akrylgulv» viser null AcryliCon-treff — SERP-en eies av
Silikal, GulvDesign, Ja Gulvsystemer, GH Gulv og Salt Entreprenør.

- **Alt-tekst: 0 av 1007 bilder på blog 3 (.no) og 0 av 1030 på blog 1 (.com)**
  (`_wp_attachment_image_alt` tom/mangler på samtlige attachments; SQL-verifisert på prod).
  Alt som rendres i HTML er `alt=""` — de eneste alt-tekstene på hele siten er hardkodet
  «Acrylicon logo» i header/footer.
- Filnavn: blandet kvalitet. Noen bærer merkevare/prosjekt (`AcryliCon_Hima_Seafood-1.jpg`),
  ingen bærer søkeord (akrylgulv/industrigulv), en del er ubrukelige (`hl.jpg`, hash-navn).
- Viktig sammenheng: bilder rangerer via siden de står på. Så lenge sidene ikke inneholder
  «akrylgulv» (§3-funnet), hjelper ikke alt-tekst alene — innholdssporet (I1–I5) og
  alt-tekst-prosjektet er to halvdeler av samme bilde-SERP-fiks.
- Remediering (planlagt, se arbeidsplan): (a) generér alt-tekster batch-vis fra kontekst
  (tilknyttet referanse/produkt/side + filnavn), menneskelig gjennomgang, skriv til
  `_wp_attachment_image_alt`; (b) render-filter (`wp_content_img_tag`) i mu-plugin som
  fyller tom alt i post_content-bilder fra mediebibliotekets alt — innholds-innbakte
  `<img alt="">` er frosset ved innsettingstidspunktet og fikses IKKE av mediebiblioteket
  alene; (c) filnavn-policy kun for NYE opplastinger (ikke omdøp eksisterende — URL-brudd).

---

*Nye innsikter føyes til i riktig seksjon med dato — dokumentet erstattes ikke.*
