# Statusside — digital roadmap 2026

Token-beskyttet fremdriftsoversikt teamet kan følge. Satt opp 2026-06-12 med
statusside-skillet; scope = hele `docs/strategy/digital-roadmap-2026.html`
(godkjent av Monika 2026-06-12).

**URL:** `https://<domene>/wp-content/status/?t=<token>`
(token deles muntlig/1:1 — ligger i `status/status-token.php` på serveren og
lokalt, gitignored. Mistet token: les fila på prod via SSH, eller generer ny
med `openssl rand -hex 32` og deploy på nytt.)

## Slik leses siden

- **Progress = andel `done`** — `partial` teller som 0. Baren kan gå ned når
  nye hull oppdages; ærlig er viktigere enn pen.
- **Grønn journey** = alle kriterier done **og** eksplisitt team-godkjenning
  (`godkjentAvTeam` settes kun etter menneskelig beslutning, aldri av agent).
- Hvert kriterium viser status, bevis (fil:linje/commit/verifisering) og dato.
- **📋-knappen** kopierer en ferdig agent-prompt for akkurat det kriteriet —
  lim inn i Claude og jobb med punktet.

## Slik oppdateres den

1. Rediger `wp-content/status/status-data.php` (data = endringslogg via git).
2. Regler: aldri `done` uten bevis + verifisert-dato; tvil = `partial` med notat.
3. Deploy: `scp wp-content/status/status-data.php acryli_28355@jana-osl.servebolt.cloud:/cust/0/acryli_15806/acryli_28355/site/public/wp-content/status/`
4. Commit som vanlig.

«Revalider alt» til en agent gir full re-audit av alle kriterier mot koden —
gjøres etter større leveranser.

## Filer

| Fil | I git? | Hva |
|---|---|---|
| `status/index.php` | Ja | Token-sjekk (hash_equals, fail-closed 404), derivasjon, UI |
| `status/status-data.php` | Ja | Journeys + kriterier (innholdet) |
| `status/status-token.php` | **Nei** (gitignored) | `<?php return '<64 hex>';` per miljø |
