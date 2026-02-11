# Docs Folder Setup Brainstorm

**Date:** 2026-02-11
**Status:** Ready for Planning

## What We're Building

En komplett, strukturert dokumentasjonsbase i `wp-content/docs/` som gir Claude Code og utviklere full oversikt over Acrylicon-prosjektet. Basert på kontekstfilen ACRYLICON_CONTEXT.md (401 linjer med selskapsinfo, produkter, kontorer, referanser, SEO-strategi, teknisk stack og fremtidsplaner).

## Problem

Dagens docs/-mappe har bare workflow-relaterte filer (brainstorms, plans, security, solutions). Det mangler:
- Selskapskontekst som AI-assistenter og nye utviklere trenger
- Produktkatalog med tekniske detaljer
- Kontorstruktur og regionfordeling
- SEO-strategi og søkeordsdata
- Innholdsstrategi og budskap
- Teknisk arkitekturoversikt utover det CLAUDE.md dekker
- Referanse til sertifiseringer og godkjenninger

## Mål

1. **Claude Code-effektivitet:** Hver ny sesjon skal ha tilgang til all prosjektkontekst uten å måtte lese 401-linjers kontekstfilen
2. **Utvikler-onboarding:** En ny utvikler skal forstå prosjektet på 10 minutter
3. **Levende dokumenter:** Strukturen skal være enkel å oppdatere etter hvert som prosjektet utvikler seg
4. **Ingen duplisering:** CLAUDE.md dekker teknisk quick-reference. Docs skal gå dypere uten å gjenta

## Designbeslutninger

### Mappestruktur

**Valgt tilnærming: Flat tematisk struktur**

```
docs/
├── brainstorms/          # (eksisterende)
├── plans/                # (eksisterende)
├── security/             # (eksisterende)
├── solutions/            # (eksisterende)
├── context/              # NY: Selskapskontekst
│   ├── company.md        # Selskapet, historie, forretningsmodell
│   ├── offices.md        # Kontorstruktur, regioner, kontaktinfo
│   ├── products.md       # Produktkatalog med tekniske data
│   ├── certifications.md # Sertifiseringer og godkjenninger
│   └── competitors.md    # Konkurrenter og posisjonering
├── strategy/             # NY: Strategi og retning
│   ├── seo.md            # SEO-strategi, søkeord, Google Ads
│   ├── content.md        # Innholdsstrategi, pilarer, budskap
│   └── international.md  # Internasjonaliseringsplan
├── architecture/         # NY: Teknisk arkitektur (utdyper CLAUDE.md)
│   ├── wordpress.md      # WP-konfig, multisite, CPTs, taxonomier
│   ├── theme.md          # Tema-arkitektur, blokker, Tailwind
│   └── integrations.md   # CRM, analytics, Zapier, CDN
└── PROJECT-LOG.md        # Prosjektlogg (/full Phase 7)
```

### Vurderte alternativer

1. **Alt i én fil:** For enkel, vanskelig å navigere og oppdatere
2. **Mange dype mapper:** For komplekst for et prosjekt med én utvikler
3. **Flat tematisk (valgt):** Balanse mellom oversikt og dybde. 3 nye mapper med 3-5 filer hver

### Innholdsprinsipper

- **Skriv for maskiner OG mennesker** - Strukturert med tabeller og lister, men leselig prosa
- **Pek, ikke kopier** - Referér til ACRYLICON_CONTEXT.md og CLAUDE.md der det passer
- **Oppdaterbar** - Hver fil har en "Sist oppdatert"-dato
- **Kort** - Hvert dokument under 150 linjer. Dybde via lenker
- **Norsk** - Konsistent med prosjektets språk

### Hva CLAUDE.md dekker (unngå duplisering)

CLAUDE.md har allerede:
- SSH/WP-CLI kommandoer
- Mappestruktur
- Tech stack oppsummering
- CPT-oversikt (kort)
- Tailwind-oppsett
- Deploy-info
- Lokal vs. prod status

Docs skal **utdype**, ikke gjenta dette.

## Scope

### Inkludert
- 11 nye markdown-filer i 3 nye mapper
- PROJECT-LOG.md i docs/
- Oppdatering av CLAUDE.md for å referere til docs/

### Ikke inkludert
- Endringer i kode eller tema
- Ny WordPress-funksjonalitet
- Oppdatering av eksisterende brainstorms/plans

## Risiko

- **Lav:** Kun markdown-filer, ingen kodeendringer, enkelt reverserbart
- **Vedlikehold:** Dokumenter kan bli utdaterte. Mitigering: Dato i hver fil + PROJECT-LOG for å flagge endringer

## Neste steg

Gå til Phase 2: Plan → detaljert innhold for hvert dokument.
