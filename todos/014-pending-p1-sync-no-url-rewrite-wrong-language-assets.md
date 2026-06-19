---
status: pending
priority: p1
issue_id: "014"
tags: [multisite, sync, i18n, media, pre-launch, blocking-new-language]
dependencies: ["001", "013"]
---

# Multisite-sync kopierer post_content ordrett → synket språk arver kildespråkets media/datablad

## Problem Statement

`acrylicon-multisite-sync` kopierer `post_content` **verbatim** fra kildesite til
target-site uten å skrive om media-URLer eller bytte til språkriktige filer. Lenker
og inline-media som ligger i Gutenberg/ACF-**blokk-JSON** (f.eks. produkt-databladenes
`download_link`) peker derfor fortsatt til kildesitens uploads etter sync.

**Hvorfor det betyr noe:** Ved neste språk-sync (NO → nytt språk) vil den nye
språk-posten arve NO sine datablad-URLer (`/wp-content/uploads/sites/3/...`). På
target-siten resolver den absolutte stien fortsatt til **blog 3 (Norge)** sine
uploads → den nye språkversjonen viser **norsk PDF**. Samme problem ble allerede
observert på den engelske siten (alle 12 produktsider lenket til norske datablad).

Blokkerer ren launch av nytt språk.

## Findings

**Trigger-modell (ikke automatisk — godt nytt):**
- Sync kjøres kun manuelt via AJAX-knapp i post-editoren
  (`includes/class-admin-ui.php:31,158` – `wp_ajax_acrylicon_sync_post`).
- Ingen `save_post`-hook, ingen cron.
- Én-gang-per-site-sperre via `is_synced()` (`class-admin-ui.php:116-124`): target
  deaktiveres i dropdown når allerede synket. Re-sync krever manuell sletting av
  `_synced_to_post_{blog}`-meta.
- => Manuelle DB-fikser på allerede-synkede target-poster blir **ikke** klobret av
  vanlig redigering.

**Rotårsak (verbatim copy, ingen rewrite):**
```php
// includes/class-sync-manager.php:127-138  copy_post_content()
wp_update_post( [
    'ID'           => $target_post_id,
    'post_title'   => $source_post->post_title,
    'post_content' => $source_post->post_content,   // ← ordrett, ingen URL-rewrite
    'post_excerpt' => $source_post->post_excerpt,
] );
```

**Hva som faktisk re-pekes i dag:**
- Featured image: kopieres fysisk + re-pekes (`copy_featured_image` steg 3 +
  `class-media-handler.php`). OK.
- ACF non-block-felt: synkes via `acf_handler->sync_fields()` (steg 4).
- Inline blokk-media + `download_link` i `post_content`: **ikke** re-pekt. ← gapet.

**Observert konsekvens (2026-06-16):** Alle 12 EN-produktsider hadde
`download_link` mot norske datablad i `2025/02`–`2025/06` (de fleste med ødelagt
`%EF%BF%BD` / U+FFFD-filnavn). Manuelt re-pekt til engelske
`2026/05/AcryliCon-Tech-Sheet-*-System-EN.pdf`. Backup på prod:
`/tmp/acrylicon-en-product-datasheets-backup-20260616.sql`.

## Anbefalt fix (avgjøres når nytt språk + datablad-navngiving er kjent)

Vurder, i prioritert rekkefølge:

1. **Språk-bevisst datablad-håndtering (kjernen):** Ved sync til nytt språk, ikke
   arv kildens `download_link`. Enten (a) tøm `download_link` på target slik at
   redaktør må feste språkriktig fil, eller (b) map kilde→target via et
   filnavn-skjema (`*-{LANG}.pdf`). Krever at per-språk datablad lastes opp til
   target-blog uploads.
2. **Generell URL-rewrite av post_content ved sync:** Skriv om
   `/wp-content/uploads/sites/{source}/...` (og ev. absolutt domene) til target-blogs
   upload-base, og kopier embeddede filer fysisk (slik media-handler gjør for
   featured image). Løser *sti*, men ikke *språk* — må kombineres med pkt. 1 for
   datablad. Se også todo 001.
3. **Post-sync sjekkliste i 013** (international launch): manuell verifisering av at
   alle `download_link`/inline-media peker på target-språkets filer før publisering.

## Acceptance Criteria

- [x] Ved sync NO → nytt språk peker ingen `download_link` på `sites/3/`-stier eller
      norske filer. → **Løst:** blankes ved sync (se Update under).
- [ ] Inline blokk-media (bilder) på synket post serveres fra target-blogs egne uploads
      (eller bevisst delt CDN, jf. 001/008), ikke kildeblogg. → **Gjenstår** (lav risiko:
      bilder rendrer cross-blog; språk-nøytrale).
- [x] Avgjørelse dokumentert: tømmes datablad-lenker ved sync, eller auto-mappes per språk?
      → **Valgt:** tømmes ved sync (språk-agnostisk), redaktør fester riktig fil.

## Update (2026-06-16): download_link-guard implementert + deployet

Lagt til språk-agnostisk guard i `copy_post_content`: alle `download_link`-verdier i
blokk-JSON blankes på vei til target (`includes/class-sync-manager.php` →
`strip_inherited_download_links()`). ACF-feltreferanser (`"_..._download_link":"field_xxx"`)
bevares. Ved regex-feil returneres innholdet uendret (blankes aldri utilsiktet).

- Templates (`blocks/download-list`, `blocks/download-table`) skjuler allerede `<a>` når
  `$link` er tom → ingen knust «Download»-knapp, kun navn + ikon til redaktør fester fil.
- Verifisert via reflection mot ekte NO-innhold: produkt 5651 (1 lenke) + Downloads-side
  2336 (12 lenker) blanket korrekt, alle `field_`-refs bevart. Ingen faktisk sync kjørt.
- Deployet til prod (lint OK, md5 match). Kildeinnhold på NO uberørt.

**Gjenstående (nedgradert):** generell URL-rewrite av inline blokk-bilder ved sync —
overlapper todo 001. Ikke blokkerende for språk-launch siden bilder er språk-nøytrale og
serveres cross-blog. Vurder sammen med 001/008 (R2/CDN).

## Notes

Ikke haster: ingen ny sync planlagt før nytt språk er klart (bekreftet av eier
2026-06-16). Logget for å fanges opp før neste språk-onboarding. Relatert: 001
(URL-rewrite-lag), 009 (delte taksonomi-navn i18n), 013 (launch-checklist).
