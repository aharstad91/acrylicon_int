---
status: pending
priority: p3
issue_id: "015"
tags: [seo, a11y, content, agentic, raw-html, deferred]
created: 2026-06-23
---

# a11y/SEO: rå-HTML-lenker uten lesbart navn i sideinnhold

## Bakgrunn

PageSpeed (acrylicon.com, 2026-06-23) flagget «links without discernible /
descriptive text» — traff Tilgjengelighet (95), SEO (92) og Agentisk surfing (1/2)
samtidig. Rot-årsaken for **live blokker** ble fikset i commit `026b549`
(`split-image-text-banner` + `info-card` fikk `aria-label`). Forsiden gikk 6 → 1.

Den ene gjenværende lenken — og resten av denne gjelden — er **rå HTML limt inn i
sideinnhold** via Custom HTML-blokker (`<!-- wp:html -->`). Noen har limt inn
ferdig-rendret blokk-markup (`<div class="info-card-container">…<a class="block
h-full"><img alt=""></a>`) som «frosset» innhold. Template-fiksene treffer ikke
disse — de er data, ikke kode.

## Omfang (per 2026-06-23, lokal prod-synk)

- **blog 1 (EN):** 9 sider med `class="block h-full"`-mønster i `post_content`
- **blog 3 (NO):** 11 sider (inkl. Forside ID 4540, Karriere ID 5717)

Konkret kjent: Home (4540) har `<a href="/locations/" class="block h-full">` med
bilde som har `alt=""` → navnløs lenke.

## Hvorfor utsatt

Bruker valgte 2026-06-23 å deploye temafiksene og **vente med innhold**. En blanket
`search-replace` kan kun gi ETT felles `aria-label` per lenke (ikke per-lenke
beskrivende), og er en bred DB-mutasjon på ~20 sider.

## Anbefalt løsning (redaksjonell, ikke blanket)

1. **Best:** legg alt-tekst på bildene i mediebiblioteket — fikser både lenkenavn
   og bildenes egen a11y/SEO. Eller:
2. Bygg om `<!-- wp:html -->`-instansene til ekte `split-image-text-banner`-blokker
   (da gjelder template-fiksen automatisk), eller
3. Hvis search-replace likevel: scope per side, utled label fra lenke-slug
   (`/locations/` → «Locations»), og kjør først på blog 3 (kilde), så sync.

## Verifisering

```bash
# Tell gjenværende navnløse / generiske lenker på en side
curl -s "https://acrylicon.com/?cb=$(date +%s)" \
  | python3 -c "import sys,re;h=sys.stdin.read();print('block h-full uten aria-label:',len(re.findall(r'class=\"block h-full\">',h)))"
```
