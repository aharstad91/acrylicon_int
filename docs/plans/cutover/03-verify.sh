#!/usr/bin/env bash
#
# 03-verify.sh — Verifiser mapping mot prod-IP UTEN DNS.
# Bruker curl --resolve sa du kan teste for DNS-flip.
# -k (insecure) brukes fordi SSL-cert for nye domener kanskje ikke er utstedt enna;
# vi tester ruting/innhold her, ikke sertifikat.
#
set -uo pipefail

IP="185.91.67.249"   # Servebolt prod-IP (oppdater hvis endret)

check() {
  local host="$1" path="$2" expect_lang="$3"
  local url="https://${host}${path}"
  echo "---- $url ----"
  curl -sk -o /dev/null -w "  HTTP %{http_code}  redirect=%{redirect_url}\n" --resolve "${host}:443:${IP}" "$url"
  curl -sk --resolve "${host}:443:${IP}" "$url" \
    | grep -ioE "<html[^>]*lang=\"[^\"]*\"|<link rel=\"canonical\" href=\"[^\"]*\"|<title>[^<]*</title>" | head -3
  echo "  (forventet lang: $expect_lang)"
  echo ""
}

echo "===== acrylicon.com (blog 1 / engelsk) ====="
check "acrylicon.com" "/" "en"
check "acrylicon.com" "/products/" "en"

echo "===== acrylicon.no (blog 3 / norsk, pa ROT) ====="
check "acrylicon.no" "/" "nb"
check "acrylicon.no" "/produkter/" "nb"

echo "===== Sanity: ingen /no/-lekkasje i acrylicon.no-lenker ====="
curl -sk --resolve "acrylicon.no:443:${IP}" "https://acrylicon.no/" \
  | grep -oE 'href="https://acrylicon\.no/no/[^"]*"' | head -3 \
  && echo "  ADVARSEL: fant /no/-lenker" || echo "  OK — ingen /no/-lekkasje"

echo ""
echo "Hvis alt ser riktig ut: tom Servebolt-cache og flipp DNS."
echo "Etter DNS+SSL er live: kjor uten -k for a bekrefte sertifikat."
