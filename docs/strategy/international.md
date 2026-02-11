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
