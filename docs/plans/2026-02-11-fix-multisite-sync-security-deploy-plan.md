# Plan: Fix Multisite Sync Security + Deploy to Production

> Brainstorm: `docs/brainstorms/2026-02-11-multisite-sync-security-deploy-brainstorm.md`
> Dato: 2026-02-11

---

## Oversikt

Fix P1 sikkerhetsmangel i media handler (#006), deretter deploy multisite-sync plugin + mu-plugins til produksjon.

**Estimert arbeid:** 4 steg, ~30 min

---

## Steg 1: Fix file validation i media handler

**Fil:** `plugins/acrylicon-multisite-sync/includes/class-media-handler.php`

### Endringer:

Legg til validering MELLOM file_exists-sjekk og copy-kall:

```php
// 1. Validate file type (extension + MIME)
$filetype = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );
if ( ! $filetype['ext'] || ! $filetype['type'] ) {
    restore_current_blog();
    error_log( "Media sync blocked: invalid file type for attachment $attachment_id" );
    return false;
}

// 2. Check against WordPress allowed MIME types
$allowed_mimes = get_allowed_mime_types();
if ( ! in_array( $filetype['type'], $allowed_mimes, true ) ) {
    restore_current_blog();
    error_log( "Media sync blocked: MIME type {$filetype['type']} not allowed for attachment $attachment_id" );
    return false;
}

// 3. Validate image files with getimagesize()
if ( str_starts_with( $filetype['type'], 'image/' ) && 'image/svg+xml' !== $filetype['type'] ) {
    if ( false === @getimagesize( $file_path ) ) {
        restore_current_blog();
        error_log( "Media sync blocked: file claims to be image but fails getimagesize() for attachment $attachment_id" );
        return false;
    }
}
```

### Hvorfor denne tilnærmingen:
- `wp_check_filetype_and_ext()` validerer BÅDE extension og MIME — blokkerer `shell.php.jpg`
- `get_allowed_mime_types()` bruker WordPress' egen whitelist
- `getimagesize()` verifiserer at bildefiler faktisk er bilder (blokkerer polyglot-angrep)
- SVG unntas fra getimagesize() fordi SVG er XML, ikke bitmap
- Ingen `file_get_contents()` scanning — unødvendig overhead

### Acceptance criteria:
- [ ] MIME type + extension validation før copy
- [ ] Allowed MIME whitelist enforced
- [ ] Image files verified med getimagesize()
- [ ] Error logging for rejected files
- [ ] SVG korrekt håndtert (unntas getimagesize)

---

## Steg 2: Test lokalt

1. Sjekk at pluginet fortsatt aktiverer uten feil
2. Verifiser at `manage_network` capability-check fungerer
3. Sjekk at admin metabox vises på supported post types

```bash
# Test at plugin laster uten PHP errors
wp plugin activate acrylicon-multisite-sync --skip-plugins=wp-fastest-cache
wp eval "echo 'Plugin loaded OK';" --skip-plugins=wp-fastest-cache
```

### Acceptance criteria:
- [ ] Plugin aktiverer uten feil
- [ ] Ingen PHP errors i error log

---

## Steg 3: Deploy til produksjon

### 3a: Deploy mu-plugins

```bash
# Opprett mu-plugins mappe på prod
ssh acryli_28355@jana-osl.servebolt.cloud 'mkdir -p ~/site/public/wp-content/mu-plugins'

# Kopier mu-plugin
scp wp-content/mu-plugins/acrylicon-shared-taxonomies.php \
    acryli_28355@jana-osl.servebolt.cloud:~/site/public/wp-content/mu-plugins/
```

MU-plugins aktiveres automatisk — ingen aktivering nødvendig.

### 3b: Deploy plugin

```bash
# Kopier plugin-mappe
scp -r wp-content/plugins/acrylicon-multisite-sync/ \
    acryli_28355@jana-osl.servebolt.cloud:~/site/public/wp-content/plugins/
```

### 3c: Aktiver plugin på prod

```bash
ssh acryli_28355@jana-osl.servebolt.cloud 'wp plugin activate acrylicon-multisite-sync'
```

### Acceptance criteria:
- [ ] mu-plugins mappe opprettet
- [ ] acrylicon-shared-taxonomies.php deployet
- [ ] acrylicon-multisite-sync/ deployet (alle 6 PHP + 2 asset-filer)
- [ ] Plugin aktivert på prod
- [ ] Ingen PHP errors på prod

---

## Steg 4: Verifiser på produksjon

```bash
# Sjekk at plugin er aktiv
ssh acryli_28355@jana-osl.servebolt.cloud 'wp plugin list --status=active'

# Sjekk at mu-plugin er lastet
ssh acryli_28355@jana-osl.servebolt.cloud 'wp plugin list --status=must-use'

# Test at ingen PHP errors
ssh acryli_28355@jana-osl.servebolt.cloud 'wp eval "echo \"All good\";"'
```

### Acceptance criteria:
- [ ] Plugin vises som aktiv
- [ ] MU-plugin vises i must-use listen
- [ ] Ingen PHP errors

---

## Steg 5: Oppdater todos og dokumentasjon

- [ ] Marker #006 som resolved
- [ ] Oppdater #004 med notat om at det er parkert (gjelder R2-kode som ikke finnes)
- [ ] Commit alle endringer

---

## Rollback-plan

Hvis noe feiler på prod:

```bash
# Deaktiver plugin
ssh acryli_28355@jana-osl.servebolt.cloud 'wp plugin deactivate acrylicon-multisite-sync'

# Fjern mu-plugin (stopper shared taxonomies)
ssh acryli_28355@jana-osl.servebolt.cloud 'rm ~/site/public/wp-content/mu-plugins/acrylicon-shared-taxonomies.php'
```

MU-plugins har ingen deaktiverings-hook, men fjerning av filen stopper den umiddelbart.
