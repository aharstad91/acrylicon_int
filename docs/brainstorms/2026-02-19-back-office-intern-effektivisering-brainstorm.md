---
date: 2026-02-19
topic: back-office-intern-effektivisering
---

# Back Office — Intern effektivisering

## Bakgrunn

AcryliCon har akseptert partnerskapsavtale og øker til 40 timer/mnd. I diskusjonen identifiserte kunden et stort behov for intern effektivisering — like viktig som den eksterne investeringen i synlighet og salgsverktøy.

### Problemene i dag

1. **Dokumentkaos:** Selgere bruker gamle PDF-er av produktblader og pristilbud. Ulike versjoner sirkulerer. Ingen standardisering, ingen sentral kilde.
2. **Referansesøk:** Et kontor sender e-post til alle andre kontorer + AcryliCon Norge og spør "har noen lagt gulv for Coop?". Monika bruker tid på å samle eksempler manuelt. Dårlig tidsbruk.
3. **Bilder:** Referansebilder og produktbilder er spredt. Vanskelig å finne riktig materiale til presentasjoner og tilbud.
4. **HMS:** Gulvlegger-sertifiseringer håndteres manuelt.
5. **Kommunikasjon:** Oppdateringer spres via e-post uten struktur.
6. **Onboarding:** Ingen sentral kilde for opplæring av nye selgere.

## Hva vi bygger

Et internt back office som eget site i WordPress multisite (Blog 4: `/bo/`). Magic link-innlogging, én bruker per ansatt, rollebasert tilgang.

### Arkitektur

```
WordPress Multisite:
  Blog 1: /         (EN — internasjonal)
  Blog 3: /no/      (NO — Norge)
  Blog 4: /bo/      (Back Office — intern)

Back Office deler:
  - Brukersystem (wp_users)
  - Referanse-data (via switch_to_blog)
  - Produkt-data
  - Taksonomi-data

Lagring: Cloudflare R2 for bilder og store filer
```

### Moduler (10 stk)

**Lag 1 — Selgerverktøy (daglig bruk):**
1. Dokumentbibliotek — én kilde til sannhet for produktblader, tekniske datablad, sertifikater
2. Bildebibliotek — referansebilder, produktbilder, søkbart og nedlastbart
3. Referansesøk — søk/filter alle referanser etter bransje, produkt, region, kontor
4. Referanseark-generator — velg referanser, generer PDF-pakke (allerede i veikartet)
5. Produktblad-generator — alltid oppdatert PDF fra live data (allerede i veikartet)

**Lag 2 — Operasjonelt:**
6. HMS-portal — gulvlegger-sertifiseringer digitalt (allerede i veikartet)
7. Prosjekt-/tilbudsoversikt — CRM-light, unngå overlapp mellom kontorer
8. Kontaktliste — hvem er hvem, kompetanse, roller

**Lag 3 — Kommunikasjon og læring:**
9. Intern nyhetsfeed — oppdateringer uten e-postrunder
10. Opplæringsmateriell — onboarding, produktkunnskap, salgsargumenter

### Tverrgående

- **Innlogging:** Magic link, ingen passord. Én bruker per ansatt. Roller: selger, kontorleder, admin.
- **Lagring:** Cloudflare R2 for bilder og filer. Løser 11GB-problemet og skalerer internasjonalt.

## Prioritering

| Fase | Moduler | Estimat |
|------|---------|---------|
| **1 — Grunnmur** | Blog 4 oppsett + magic link (~15t), Dokumentbibliotek (~25t), Referansesøk (~20t) | ~60t |
| **2 — Utvidelse** | Bildebibliotek + R2 (~30t), Kontaktliste (~10t), Intern nyhetsfeed (~15t) | ~55t |
| **3 — Generatorer** | Referanseark-generator (~30t), Produktblad-generator (~60t), HMS-portal (~30t) | ~120t |
| **4 — Avansert** | Opplæringsmateriell (~20t), Prosjekt-/tilbudsoversikt (~40t) | ~60t |
| | **Totalt** | **~295t** |

## Hva som IKKE er med

- **Pristilbud-standardisering:** For tidlig å bestemme plattform. Noen kontorer bruker Odoo. Parkert til avklaring.

## Nøkkelbeslutninger

- Back office som eget site i multisite (Blog 4) — renest separasjon
- Magic link login — fjerner passord-friksjon for selgere
- Cloudflare R2 for filer — skalerbart og billig
- Bygger på eksisterende datamodell — referanser, produkter, taksonomier finnes allerede
- Tre moduler fra eksisterende veikart (referanseark-generator, produktblad-generator, HMS-portal) flyttes/kobles til back office-kontekst

## Neste steg

→ Oppdater `digital-roadmap-2026.html` med ny seksjon for intern effektivisering
→ Oppdater partnerskaps-seksjonen: avtale akseptert, 40 t/mnd
