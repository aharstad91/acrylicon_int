#!/usr/bin/env bash
#
# 99-rollback.sh — Tilbakestill prod til for-cutover-tilstand.
# Bruker backup fra 01-prepare.sh.
#
set -euo pipefail

SSH_HOST="acryli_28355@jana-osl.servebolt.cloud"
PROD="/cust/0/acryli_15806/acryli_28355/site/public"
BACKUP="$PROD/.cutover-backup"

read -r -p "Rulle tilbake prod til for-cutover? Skriv 'ROLLBACK': " C
[ "$C" = "ROLLBACK" ] || { echo "Avbrutt."; exit 1; }

ssh "$SSH_HOST" "
  set -e
  test -f '$BACKUP/wp-config.php.bak' || { echo 'Mangler backup'; exit 1; }
  echo '==> Gjenoppretter wp-config + .htaccess'
  cp '$BACKUP/wp-config.php.bak' '$PROD/wp-config.php'
  test -f '$BACKUP/htaccess.bak' && cp '$BACKUP/htaccess.bak' '$PROD/.htaccess' || true
  echo '==> Importerer DB-backup (full)'
  wp db import '$BACKUP/db-precutover.sql'
  echo '==> Flush'
  wp cache flush || true
"
echo ""
echo "Rollback ferdig. servebolt.cloud fungerer som for."
echo "NB: sunrise.php ligger fortsatt pa plass, men er inert uten SUNRISE i wp-config (gjenopprettet)."
echo "Hvis DNS allerede er flippet: pek den tilbake / vent pa propagering."
