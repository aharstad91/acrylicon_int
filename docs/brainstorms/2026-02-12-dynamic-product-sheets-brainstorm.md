# Dynamiske Produktark — Brainstorm

**Dato:** 2026-02-12
**Status:** Klar for planlegging

---

## Hva vi bygger

Erstatte de 10 statiske PDF-produktarkene med dynamiske websider som rendres fra eksisterende ACF-data i WordPress. Hver produktside får en "Se produktark"-seksjon, og /nedlastinger/-siden oppdateres til å vise webversjoner i stedet for (eller i tillegg til) PDF-lenker.

Websidene følger Acrylicons nye nettside-design og har en print-CSS som produserer en pen, PDF-lignende A4-utskrift.

## Hvorfor denne tilnærmingen

### Problemet
- 10 statiske PDF-er (laget i Illustrator) må manuelt oppdateres hver gang noe endres
- Innholdet i PDF-ene er det *samme* som allerede ligger i WordPress ACF-felter
- PDF-ene er ikke søkbare, ikke responsive, og tungvinte å vedlikeholde

### Løsningen
- Én WordPress-template som rendrer produktark-data dynamisk
- All data kommer fra ACF-felter som allerede eksisterer på Produkter-CPT
- Print-CSS gir en pen A4-utskrift direkte fra nettleseren
- Oppdater data i WordPress → produktarket oppdateres automatisk overalt

### Hvorfor IKKE server-generert PDF (som dompdf/wkhtmltopdf)
- Øker serverkompleksitet og avhengigheter
- Print-CSS gir 90% av verdien med 10% av kompleksiteten
- Kan alltid legge til PDF-generering senere om det viser seg nødvendig

## Hva som finnes i dag

### Eksisterende produktark (PDF-er)
- 10 produktsystemer med 2-siders A4-ark (Illustrator-laget)
- Side 1: Produktbilde, systemnavn, beskrivelse, egenskaper/fordeler (med ikoner), spesifikasjonstabell
- Side 2: Teknisk informasjon, rengjøring, herdetid, egenskaper/påføring, underlag, forventet levetid
- Pluss: Fargekart og Kjemisk resistensliste (generelle dokumenter)

### Eksisterende WordPress-data
- **Produkter CPT** med ACF-felter (blog 3 = kilde, blog 1 = synket)
- **ACF-blokker:** `product-card`, `technical-info-table`, `download-list`
- **Temaet:** Tailwind CSS, nytt design allerede på prod
- **Produktkatalog:** 12 systemer dokumentert i `docs/context/products.md`

### Eksisterende ACF-felter på produkter
- `product-card`: image, title, product_type, product_card_meta (repeater)
- `technical-info-table`: technical_info_repeater (name + desc)
- `download-list`: download_list_repeater (name + link)

## Nøkkelbeslutninger

| Beslutning | Valg | Begrunnelse |
|---|---|---|
| MVP-scope | Dynamiske ark, ingen konfigurasjon | Erstatt PDF-er først, konfigurator senere |
| Output | Webside + print-CSS | Enklere enn server-PDF, dekker behovet |
| Design | Følg nytt nettside-design | Allerede på plass, konsistent branding |
| Plassering | Både på produktsider og /nedlastinger/ | Maks tilgjengelighet |
| Datakilde | Eksisterende ACF-felter | Alt ligger der allerede |
| Multisite | Rendres per-blog med riktig språk | Følger eksisterende sync-mønster |

## Scope — MVP

### Inkludert
- [ ] Ny template for produktark-visning (single-produkter-sheet.php eller lignende)
- [ ] Innholdsseksjoner som matcher dagens PDF-struktur (side 1 + side 2)
- [ ] Tailwind-styling som følger nytt nettside-design
- [ ] Print-CSS som gir pen A4-utskrift
- [ ] "Se produktark" / "Last ned produktark" knapp på produktsider
- [ ] Oppdatert /nedlastinger/-side med web-produktark
- [ ] Fungerer på både blog 1 (EN) og blog 3 (NO)

### Ekskludert (senere)
- Selger-konfigurator (velg farge, bruksområde, etc.)
- Server-generert PDF (dompdf/wkhtmltopdf)
- Fargekart og kjemisk resistensliste (beholder som statiske PDF-er)
- E-post-sending av produktark

## Åpne spørsmål

1. **ACF-felter komplett?** — Må verifiseres at alle datapunkter fra PDF-ene faktisk har ACF-felter. Hvis ikke, må nye felter opprettes.
2. **URL-struktur** — `/produkter/dekor-system/produktark/` eller query param `?view=sheet`?
3. **Print-knapp** — Skal det være en eksplisitt "Skriv ut / Last ned PDF"-knapp, eller bare stole på Cmd+P?

## Neste steg

Kjør `/full-auto` med denne brainstormen for å planlegge og bygge autonomt.
