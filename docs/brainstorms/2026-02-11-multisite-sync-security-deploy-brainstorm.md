# Brainstorm: Multisite Sync Security Fix + Deploy

> Dato: 2026-02-11

---

## Problem

Multisite-sync pluginet (`acrylicon-multisite-sync`) og mu-plugins (`acrylicon-shared-taxonomies.php`) eksisterer lokalt men er IKKE deployet til produksjon. Pluginet har en P1 sikkerhetsmangel (#006) som må fikses FØR deploy.

---

## Analyse

### Hva finnes i pluginet?

| Fil | Linjer | Rolle |
|-----|--------|-------|
| `acrylicon-multisite-sync.php` | 61 | Bootstrap, activation check |
| `class-sync-manager.php` | 213 | Orchestration, draft-first pattern |
| `class-media-handler.php` | 77 | File copy (SÅRBAR) |
| `class-acf-handler.php` | 75 | ACF field sync |
| `class-taxonomy-handler.php` | 41 | Taxonomy assignment |
| `class-admin-ui.php` | 229 | Metabox + AJAX |

**Totalt:** 696 linjer, 6 filer. Kompakt plugin.

### Sikkerhetsproblem (#006)

`class-media-handler.php:46` gjør `copy($file_path, $new_file)` uten noen validering:
- Ingen MIME-type sjekk
- Ingen filtype-whitelist
- Ingen innholdsvalidering (PHP i bilder, XSS i SVG)

**Reell risiko?** Moderat. Pluginet krever `manage_network` capability (super admin), så en angriper må allerede ha admin-tilgang. Men defense-in-depth er god praksis — en kompromittert admin-konto skal ikke kunne eskalere via sync.

### Todo #004 — IKKE relevant

Todo #004 refererer til "proposed R2 sync code (lines 486-493)" som **ikke eksisterer ennå**. Den nåværende media handleren bruker `wp_get_original_image_path()` og `wp_unique_filename()` — standard WordPress-funksjoner uten meta_value-queries. #004 parkeres.

### Hva trenger produksjon?

1. `wp-content/mu-plugins/` — mappen eksisterer ikke på prod
2. `wp-content/mu-plugins/acrylicon-shared-taxonomies.php` — deler taxonomi-tabeller mellom sites
3. `wp-content/plugins/acrylicon-multisite-sync/` — hele plugin-mappen

---

## Beslutninger

### 1. Fiksér #006 med WordPress native validation (Option 1 fra todo)

Bruker `wp_check_filetype_and_ext()`, `getimagesize()`, og SVG-sanitering. Enkleste og tryggeste tilnærming.

**Forenklinger vs. todo-forslaget:**
- **Dropper `file_get_contents()` + regex for PHP i bilder.** `getimagesize()` er tilstrekkelig — hvis det passerer som gyldig bilde, er det trygt. Regex-scanning av hele filer er brittle og slow.
- **Dropper separat SVG-validator.** SVG Support-pluginet håndterer allerede SVG-sanitering. WordPress tillater ikke SVG by default — kun via plugin. Dobbel sanitering er unødvendig.
- **Beholder enkel extension + MIME check** som primært forsvar.

### 2. Parkér #004

Ikke relevant for nåværende kodebase. Tas opp når R2-integrasjon bygges.

### 3. Deploy med scp (bevist metode)

Bruker `scp` som funket for temaet i forrige sesjon. Rsync hadde problemer.

### 4. Test lokalt først, så deploy

Verifiser at pluginet aktiverer og fungerer lokalt, deretter scp til prod og aktiver via WP-CLI.

---

## Scope

**In scope:**
- [x] Fix #006: file validation i media handler
- [ ] Deploy mu-plugins til prod
- [ ] Deploy plugin til prod
- [ ] Aktiver plugin via WP-CLI
- [ ] Verifiser at admin UI vises

**Out of scope:**
- #004 (R2-kode som ikke eksisterer)
- R2 CDN setup (#008)
- Credential management (#002)
- Nye features i pluginet

---

## Risiko

| Risiko | Sannsynlighet | Konsekvens | Mitigering |
|--------|---------------|------------|------------|
| Plugin krasjer på prod | Lav | Admin-side nede | Test lokalt først, deaktiver via WP-CLI hvis nødvendig |
| mu-plugin bryter taxonomier | Lav | Terms forsvinner | Taxonomiene brukes allerede — mu-plugin formaliserer bare delingsmekanismen |
| Shared taxonomies gir konflikter | Lav | Duplikate terms | Sjekk term_id-konflikter før aktivering |
