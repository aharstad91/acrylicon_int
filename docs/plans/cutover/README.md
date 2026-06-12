# Cutover: acrylicon.com + acrylicon.no domene-mapping

Kjørbare scripts for å mappe domenene mot Servebolt-prod. Se full plan:
`docs/plans/2026-05-27-001-feat-multisite-domain-mapping-plan.md`.

Alle scripts kjøres **lokalt** og SSH-er til prod. Validert lokalt 2026-05-27.

## Hva scriptene gjør (WP/prod-siden)

| Script | Når | Driftspåvirkning |
|--------|-----|------------------|
| `01-prepare.sh` | Når som helst i forkant | Ingen — backup + deploy av inert sunrise.php |
| `02-apply.sh` | I cutover-vinduet | Endrer drift — wp-config + DB + search-replace |
| `03-verify.sh` | Rett etter apply, FØR DNS | Ingen — leser via curl --resolve |
| `99-rollback.sh` | Ved problemer | Gjenoppretter fra backup |

## Hva scriptene IKKE gjør (manuelt — utenfor serveren)

1. **Servebolt-panel:** legg til `acrylicon.com` + `acrylicon.no` (+ www) som domener/aliaser, sørg for SSL-sertifikat.
2. **DNS:** pek A/CNAME for begge domener til Servebolt (IP `185.91.67.249`). Senk TTL ~300s i forkant.

## Rekkefølge

```
[I forkant]
  - Senk DNS-TTL til ~300s
  - Servebolt: legg til domener + SSL
  - ./01-prepare.sh

[Cutover-vindu]
  - ./02-apply.sh          # endrer wp-config + DB (dry-run search-replace + bekreftelse)
  - ./03-verify.sh         # test begge domener mot prod-IP UTEN DNS
  - (ser bra ut?) tøm Servebolt-cache
  - Flipp DNS

[Etter]
  - Bekreft begge domener i nettleser (HTTPS, riktig språk, språkbytter)
  - Legg inn 301 servebolt.cloud -> kanonisk domene i .htaccess (ikke /wp-admin)
  - Hev DNS-TTL tilbake
  - Google Search Console: nye properties + resubmit sitemaps
  - Oppdater CLAUDE.md med nye domener
```

## Viktig: SSL-rekkefølge
Hvis Servebolt krever at domenet peker dit (HTTP-01) før Let's Encrypt-cert utstedes,
må DNS-flippen skje før SSL er live → noen minutters cert-feil til cert er klart.
Avklar dette med Servebolt før dato settes. `03-verify.sh` bruker `-k` nettopp fordi
cert kanskje ikke er utstedt ennå ved pre-DNS-test.

## Nedetidsnote
Den gamle `servebolt.cloud/no/`-URL-en slutter å virke i det `02-apply` kjører
(blog 3 flyttes til acrylicon.no-rot). Dette er kun staging-URL-en — de ekte
domenene og den engelske servebolt-fallbacken er upåvirket.

## Rollback
`./99-rollback.sh` gjenoppretter wp-config + full DB fra backupen `01-prepare` tok.
servebolt.cloud fungerer da som før. Lav TTL gjør at DNS kan trekkes raskt tilbake.
