# DNS-endring for cutover til Servebolt — Arne

Servebolt-mål: **A → `185.91.67.249`** og **AAAA → `2a05:e880:10:1::484c`**

## Records som skal settes (alle fire hostene → Servebolt, dual-stack)

| Host | Type | Fra (i dag) | Til |
|---|---|---|---|
| `acrylicon.com` | A | `23.236.62.147` (Wix) | `185.91.67.249` |
| `acrylicon.com` | AAAA | (ingen) | `2a05:e880:10:1::484c` |
| `www.acrylicon.com` | A (erstatter CNAME) | CNAME `www179.wixdns.net` | `185.91.67.249` |
| `www.acrylicon.com` | AAAA | (via Wix) | `2a05:e880:10:1::484c` |
| `acrylicon.no` | A | `93.188.2.51` (Loopia) | `185.91.67.249` |
| `acrylicon.no` | AAAA | `2a02:250:0:8::51` (Loopia) | `2a05:e880:10:1::484c` |
| `www.acrylicon.no` | A | `93.188.2.51` (Loopia) | `185.91.67.249` |
| `www.acrylicon.no` | AAAA | `2a02:250:0:8::51` (Loopia) | `2a05:e880:10:1::484c` |

> Viktig: domenene har AAAA (IPv6) i dag som peker på Loopia/Wix. Hvis bare A flippes, fortsetter
> IPv6-klienter å treffe gammel host. Derfor må AAAA enten peke til Servebolt (som over) eller fjernes.
> `www.*` kan ikke ha både CNAME og A — CNAME erstattes av A+AAAA.

## IKKE rør (e-post / verifisering)
- **MX**: `acrylicon-com.mail.protection.outlook.com`, `acrylicon-no.mail.protection.outlook.com`
- **TXT/SPF**: alle `v=spf1 …`-records + Microsoft/SuperOffice/emarketeer-verifiseringsstrenger

## Timing
- Flipp de fire recordene til `185.91.67.249` når Andreas gir grønt lys. Trenger ikke skje på et bestemt minutt — kort nedetid under selve migreringen er OK.
- (Valgfritt) senk TTL på recordene til 300s litt før, så nedetiden blir kortest mulig.
- SSL ordnes automatisk av Servebolt etter at DNS peker dit — ingen ekstra TXT-record fra deg.
