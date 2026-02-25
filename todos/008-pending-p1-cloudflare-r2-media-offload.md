---
status: pending
priority: p1
issue_id: "008"
tags: [infrastructure, storage, cdn, multisite, scalability]
created: 2026-02-11
---

# Cloudflare R2 + WP Offload Media

## Problem
Servebolt har 10 GB lagringsgrense. Uploads-mappen var 11 GB (nå 3.8 GB etter komprimering). Multisite dupliserer media per subsite — legger vi til flere land spiser lagringen seg opp igjen raskt.

## Løsning
Flytt alle media til Cloudflare R2 med WP Offload Media plugin.

### Steg
1. Opprett Cloudflare-konto (gratis plan) og R2 bucket
2. Generer R2 API-nøkler (S3-kompatibelt)
3. Installer WP Offload Media på prod
4. Konfigurer plugin med R2 bucket-credentials
5. Bulk-migrer eksisterende uploads til R2
6. Verifiser at alle bilder/video serveres fra R2/CDN
7. Slett lokale uploads fra Servebolt for å frigjøre plass
8. Konfigurer slik at alle subsites deler samme bucket (ingen duplisering)

### Kostnad
- R2 free tier: 10 GB lagring + 10M lesinger/mnd gratis
- WP Offload Media: $0 (lite-versjon) eller $99/år (pro med bulk offload)
- Estimert månedskostnad med 10 GB media: ~0 kr (innenfor free tier)

### Gevinst
- Ubegrenset mediaskalering uavhengig av Servebolt-kvote
- CDN — bilder fra nærmeste edge, bedre PageSpeed
- Ingen mediaduplisering mellom subsites
- Frigjør ~3-5 GB på Servebolt
