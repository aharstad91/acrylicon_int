# Internasjonal lansering — Sjekkliste (validert 2026-05-13)

**Status:** pending (validated, awaiting execution)
**Created:** 2026-05-13
**Target launch:** Uke 21 (~2026-05-19/20)
**Source:** Sync med Monika (kunde) 2026-05-13 — `synk-monika.json`

---

## P0 — Hold av inntil videre

Bruker bekreftet 2026-05-13: **ikke gjør noe på P0-punkter nå**.

- ~~DNS/IP-switch til Servebolt~~ — på hold
- ~~"Om AcryliCon"-side må matche NO~~ — på hold
- ~~"Cards"-issue~~ — på hold

---

## P1 — Aktive items

### 4. ~~Fjern Jamaica NW Acrylicon Distribution-oppføring~~ — DONE 2026-05-13

Fjernet via SSH-direct-edit (tilnærming A). Backup: `international-offices.php.bak-2026-05-13`. Turmax står igjen som eneste Jamaica-oppføring. Cache flushed. Verifisert på `/locations/` (HTTP 200, ingen "NW Acrylicon"/"Williamfield").

**Drift-konsekvens:** Lokal kode (master + feature-branch) er fortsatt ute av sync med prod på Locations-data. Bør håndteres separat (full pull fra prod) når det er tid.

**Status:** ✅ Resolved

### 5. ~~"Cold weather"-fix~~ — DROPPED

Bruker bekreftet 2026-05-13: transcript-formuleringen ga ikke mening (sannsynligvis auto-oversettelses-feil). Ingenting konkret å fikse.

**Status:** N/A — droppet

### 6. Benefits-side — kundenavn (EN)

**Status:** Blocked — venter Monika sender liste
**Eier:** Monika → Andreas implementerer

### 7. Downloads — engelske PDF-er

**Status:** Blocked — venter Monika sender Tech Sheets + avklarer METAP
**Eier:** Monika → Andreas implementerer

### 8. Referanser — ikke-norske

**Status:** Blocked — venter Monika identifiserer
**Eier:** Monika → Andreas implementerer

### 9. ~~Bildemateriale til Eva~~ — IKKE VÅR OPPGAVE

Bruker bekreftet 2026-05-13: Monika ringer Eva direkte. Vi har ingenting å fikse her.

**Status:** N/A — ikke vår oppgave

---

## Aktive arbeidsposter for Andreas nå

| # | Hva | Klar til arbeid? |
|---|---|---|
| 4 | ~~Fjern Jamaica NW-kort~~ | ✅ Done 2026-05-13 |
| 6, 7, 8 | Implementere innhold fra Monika | Nei — venter content |
| P0 | DNS / About / Cards | Nei — på hold |

---

## Det vi venter på fra Monika

- Kundenavn for Benefits-siden (6)
- Engelske Tech Sheets + METAP-avklaring (7)
- Ikke-norske referanser (8)

---

## Eksplisitt utenfor scope

- Filtrerings-UI på referanser
- Shared picture server ("Tino"/"Billet Bank")
- QR-kode/Goodstag-integrasjon (post-launch)
- Dynamiske produktark (egen brainstorm + plan)
- Bildemateriale til Eva (Monika håndterer direkte)
