#!/usr/bin/env bash
#
# 01-prepare.sh — Trygt forarbeid (INGEN endring i drift).
# Kjor nar som helst for cutover.
#
#  - Tar full backup pa prod (DB + wp-config + .htaccess)
#  - Deployer sunrise.php (inert til SUNRISE defineres i 02-apply)
#
# Kjores LOKALT; SSH-er til prod.
set -euo pipefail

SSH_HOST="acryli_28355@jana-osl.servebolt.cloud"
PROD="/cust/0/acryli_15806/acryli_28355/site/public"
BACKUP="$PROD/.cutover-backup"
SUNRISE_SRC="$(cd "$(dirname "$0")" && pwd)/sunrise.php"

echo "==> 1/3 Lager backup-mappe pa prod: $BACKUP"
ssh "$SSH_HOST" "mkdir -p '$BACKUP'"

echo "==> 2/3 Backup av DB + wp-config + .htaccess"
ssh "$SSH_HOST" "
  set -e
  cp '$PROD/wp-config.php' '$BACKUP/wp-config.php.bak'
  cp '$PROD/.htaccess'     '$BACKUP/htaccess.bak' 2>/dev/null || true
  wp db export '$BACKUP/db-precutover.sql' --tables=\$(wp db tables --all-tables-with-prefix --format=csv)
  echo 'Backup OK:'; ls -la '$BACKUP'
"

echo "==> 3/3 Deployer sunrise.php (inert til SUNRISE='on')"
scp "$SUNRISE_SRC" "$SSH_HOST:$PROD/wp-content/sunrise.php"
ssh "$SSH_HOST" "ls -la '$PROD/wp-content/sunrise.php'"

echo ""
echo "FERDIG. Forarbeid utfort uten driftspavirkning."
echo "Neste: legg til acrylicon.com + acrylicon.no (+ SSL) i Servebolt-panelet,"
echo "deretter kjor 02-apply.sh i cutover-vinduet."
