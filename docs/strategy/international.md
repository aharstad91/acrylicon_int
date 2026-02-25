# Internasjonaliseringsplan

> Sist oppdatert: 2026-02-11

---

## Domener

| Marked | Domene | Plattform |
|--------|--------|-----------|
| Norge | acrylicon.no | WordPress (acrylicon-2024) |
| UK | acryliconuk.com | WordPress |
| USA | acryliconusa.com | WordPress |
| Australia | acrylicon.com.au | WordPress |
| Fabrikk | acryliconpolymers.com | – |

## Arkitektur (plan)

- **WordPress Multisite** – separate installasjoner per marked
- **Hosting:** Servebolt (migrert)
- **Synkronisering:** Zapier for innholdsdeling på tvers
- Nåværende multisite: blog 1 (hoved) + blog 3 (/norway/)

## Innholdssynkronisering

- Referansecaser deles på tvers av markeder
- Produktinformasjon synkroniseres
- Oversettelse og lokalisering per marked
- Custom plugin: `acrylicon-multisite-sync` (utviklet, ikke deployet)

## Tekniske prioriteringer

1. **hreflang-tagger** for flerspråklig SEO
2. **CDN og bildeoptimering** for bedre PageSpeed
3. **Sitemap og robots.txt** per marked
4. **GA4 + Hotjar** for analyse
5. **Gravity Forms** erstatter SuperOffice iFrame-kontaktskjemaer
6. **Zapier:** Gravity Forms → SuperOffice CRM

## Planlagte innovasjoner

| Innovasjon | Beskrivelse | Status |
|------------|-------------|--------|
| CRM-integrasjon | Gravity Forms + Zapier → SuperOffice | Planlagt |
| Produktdatablad-system | CPT for automatisk flerspråklige datablad | Planlagt |
| Ansatt-sertifiseringsdatabase | Spore og vise sertifiseringer | Planlagt |
| Filhåndteringsportal | Løse utdaterte dokumenter via e-post | Planlagt |
| Internasjonal karriereside | Åpne stillinger worldwide + søknadsskjema | Planlagt |

## Karrieresider (alle markeder)

**Nåsituasjon:** Karriereside finnes på acrylicon.no/karriere/ med godt innhold (rollebeskrivelse, fordeler, ansatt-sitater), men søknad skjer kun via mailto:jobb@acrylicon.no.

**Mål:** Profesjonell karriereløsning som fungerer nasjonalt og internasjonalt.

### Fase 1: Søknadsskjema (Gravity Forms)
- Installer Gravity Forms (lisens tilgjengelig)
- Søknadsskjema med: navn, e-post, telefon, foretrukket region/kontor, CV-opplasting (PDF), motivasjonstekst
- Søknader lagres i WP-admin + e-postvarsling til jobb@acrylicon.no
- Kopier til EN-siten (international careers page)

### Fase 2: Stillinger som CPT (senere)
- Custom post type for stillingsannonser med tittel, beskrivelse, kontor/region, land
- Arkivside som viser alle åpne stillinger worldwide
- Filtrering per land, kontor og type
- Synkes mellom språk/land via multisite-sync
- Hver stilling har eget søknadsskjema

### Internasjonalt perspektiv
- Hovedkarriereside på EN-siten (`/careers/`) viser alle åpne stillinger globalt
- Nasjonale karrieresider (`/no/karriere/`, `/uk/careers/`) viser kun lokale stillinger
- Søknadsskjema tilpasset per marked (språk, felt, e-postvarsling til riktig kontor)
- Gravity Forms → Zapier → SuperOffice for lead/søknadshåndtering
