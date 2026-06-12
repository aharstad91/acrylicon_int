#!/usr/bin/env bash
#
# 02-apply.sh — Selve cutover (ENDRER drift).
# Kjor i lavtrafikk-vindu, rett for/samtidig som DNS-flip.
# Krever at 01-prepare.sh er kjort (backup + sunrise.php pa plass).
#
#  - wp-config: define SUNRISE='on', DOMAIN_CURRENT_SITE=acrylicon.com, COOKIE_DOMAIN=''
#  - DB: wp_site / wp_blogs / sitemeta / blog-options -> ekte domener, blog 3 til rot
#  - search-replace: servebolt-URLer -> acrylicon.com / acrylicon.no (dry-run forst)
#
# Idempotent der det er mulig. Kjores LOKALT; SSH-er til prod.
set -euo pipefail

SSH_HOST="acryli_28355@jana-osl.servebolt.cloud"
PROD="/cust/0/acryli_15806/acryli_28355/site/public"
BACKUP="$PROD/.cutover-backup"
OLD="acryli-28355.jana-osl.servebolt.cloud"

echo "==> Sjekker at backup finnes (01-prepare maa vaere kjort)"
ssh "$SSH_HOST" "test -f '$BACKUP/wp-config.php.bak' && test -f '$PROD/wp-content/sunrise.php'" \
  || { echo 'FEIL: mangler backup eller sunrise.php. Kjor 01-prepare.sh forst.'; exit 1; }

read -r -p "Dette endrer LIVE prod. Skriv 'CUTOVER' for a fortsette: " CONFIRM
[ "$CONFIRM" = "CUTOVER" ] || { echo "Avbrutt."; exit 1; }

echo "==> 1/4 wp-config-endringer (idempotent)"
ssh "$SSH_HOST" "
  set -e
  WPC='$PROD/wp-config.php'
  # SUNRISE: legg til etter MULTISITE hvis ikke definert
  grep -q \"define( *'SUNRISE'\" \"\$WPC\" || \
    perl -0pi -e \"s/(define\\('MULTISITE', true\\);)/\\1\\ndefine('SUNRISE', 'on');/\" \"\$WPC\"
  # DOMAIN_CURRENT_SITE -> acrylicon.com
  perl -0pi -e \"s/define\\('DOMAIN_CURRENT_SITE', '[^']*'\\);/define('DOMAIN_CURRENT_SITE', 'acrylicon.com');/\" \"\$WPC\"
  # COOKIE_DOMAIN -> tom
  perl -0pi -e \"s/define\\( *'COOKIE_DOMAIN', *'[^']*' *\\);/define( 'COOKIE_DOMAIN', '' );/\" \"\$WPC\"
  echo '--- resultat ---'
  grep -E \"SUNRISE|DOMAIN_CURRENT_SITE|COOKIE_DOMAIN\" \"\$WPC\"
"

echo "==> 2/4 DB: nettverkstabeller + blog-options"
ssh "$SSH_HOST" "wp db query \"
  UPDATE wp_blogs   SET domain='acrylicon.com', path='/' WHERE blog_id=1;
  UPDATE wp_blogs   SET domain='acrylicon.no',  path='/' WHERE blog_id=3;
  UPDATE wp_site    SET domain='acrylicon.com', path='/' WHERE id=1;
  UPDATE wp_sitemeta SET meta_value='https://acrylicon.com/' WHERE meta_key='siteurl';
  UPDATE wp_options   SET option_value='https://acrylicon.com' WHERE option_name IN ('home','siteurl');
  UPDATE wp_3_options SET option_value='https://acrylicon.no'  WHERE option_name IN ('home','siteurl');
\""

echo "==> 3/4 search-replace (DRY-RUN forst — se antall treff)"
ssh "$SSH_HOST" "
  echo '--- DRY: blog 3 (/no) -> acrylicon.no ---'
  wp search-replace 'https://$OLD/no' 'https://acrylicon.no' --all-tables-with-prefix --precise --dry-run
  wp search-replace 'https:\\/\\/$OLD\\/no' 'https:\\/\\/acrylicon.no' --all-tables-with-prefix --precise --dry-run
  echo '--- DRY: resten -> acrylicon.com ---'
  wp search-replace 'https://$OLD' 'https://acrylicon.com' --all-tables-with-prefix --precise --dry-run
  wp search-replace 'https:\\/\\/$OLD' 'https:\\/\\/acrylicon.com' --all-tables-with-prefix --precise --dry-run
"
read -r -p "Ser dry-run riktig ut? Skriv 'REPLACE' for a kjore reell search-replace: " R
[ "$R" = "REPLACE" ] || { echo "Hoppet over search-replace. DB-domener er endret; husk a kjore replace manuelt."; exit 1; }

echo "==> 4/4 search-replace (REELL — rekkefolge: /no forst)"
ssh "$SSH_HOST" "
  set -e
  wp search-replace 'https://$OLD/no' 'https://acrylicon.no' --all-tables-with-prefix --precise
  wp search-replace 'https:\\/\\/$OLD\\/no' 'https:\\/\\/acrylicon.no' --all-tables-with-prefix --precise
  wp search-replace 'https://$OLD' 'https://acrylicon.com' --all-tables-with-prefix --precise
  wp search-replace 'https:\\/\\/$OLD' 'https:\\/\\/acrylicon.com' --all-tables-with-prefix --precise
  wp cache flush || true
  wp rewrite flush --url=https://acrylicon.com/ || true
  wp rewrite flush --url=https://acrylicon.no/ || true
"

echo ""
echo "FERDIG med apply. Kjor 03-verify.sh for a teste FOR DNS-flip."
echo "Tom deretter Servebolt-cache og flipp DNS."
