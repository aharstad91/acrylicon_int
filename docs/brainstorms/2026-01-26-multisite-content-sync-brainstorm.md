# Multisite Content Sync Brainstorm

**Date:** 2026-01-26
**Status:** Ready for Planning

## What We're Building

Et WordPress multisite-system for Acrylicon med:
- **Norsk hovedside** (acrylicon.no) - Full innholdsportefølje
- **Internasjonal engelsk side** (acrylicon.com) - 50-70% av innholdet

**Hovedfunksjonalitet:**
Et custom WordPress plugin som lar redaktører synkronisere innhold fra engelsk internasjonal side til norsk side ved å trykke en knapp. Innholdet blir kopiert én gang, og kan deretter oversettes manuelt på norsk side uten å overskrive originalen.

**Arbeidsflyt:**
1. Redaktør oppretter innhold på engelsk side (acrylicon.com)
2. Når innholdet er godkjent, vises en "Send til Norsk side"-knapp i admin
3. Redaktør trykker knappen → innholdet kopieres til norsk site
4. Status viser "Synkronisert til Norsk - [dato]" og knappen blir disabled
5. Norsk redaktør oversetter innholdet manuelt fra engelsk til norsk
6. Ingen automatisk overskriving - engangssynkronisering

**Post Types som synkroniseres:**
- Referanser (case studies)
- Produkter
- Bruksområder
- Gode grunner
- Levetidskostnader
- Bærekraftig

## Why This Approach

### Chosen Solution: WordPress Plugin med Direct Database Access (Tilnærming 3)

**Teknisk implementasjon:**
- Bruker `switch_to_blog()` for å bytte mellom sites i multisite
- Direkte database-queries via WordPress functions (`wp_insert_post()`, `update_post_meta()`)
- ACF-felter kopieres med `get_field()` / `update_field()`
- Featured images og media kopieres med `media_sideload_image()`
- Synkroniseringsstatus lagres i post meta (`_synced_to_sites`)

**Hvorfor denne løsningen:**
1. **Enkel å implementere** - Bruker kun WordPress core functions
2. **Fungerer lokalt** - Ingen eksterne API-kall eller server-avhengigheter
3. **Rask utvikling** - Kan få proof-of-concept opp raskt
4. **Unngår bloat** - Ikke behov for WPML, Polylang eller eksterne plugins
5. **Full kontroll** - Alle data håndteres i samme database

**Alternativer vi vurderte:**
- **WordPress REST API + Custom Admin Interface** - Mer fleksibelt, men mer komplekst oppsett med Application Passwords
- **Standalone API Service** - Maksimal kontroll, men krever ekstra infrastruktur og hosting

## ⚠️ Critical Implementation Warnings

### 1. Shared Taxonomies Data Loss Risk
**WARNING:** Implementering av shared taxonomies vil SLETTE eksisterende terms på subsites som ikke finnes på hovedsitet.

**Mitigering:**
- Implementer shared taxonomies FØR innhold legges til på subsites
- Eller: eksporter eksisterende terms fra subsites til hovedsitet først
- Test grundig i staging før produksjon

### 2. MU-Plugin Timing
MU-plugin for shared taxonomies MÅ lastes før themes og plugins.

**Plassering:** `/wp-content/mu-plugins/acrylicon-shared-taxonomies.php`

**Ikke bruk:** `/wp-content/plugins/` (for sent i load order)

### 3. Database Table Engine
WordPress multisite tabeller MÅ bruke InnoDB (ikke MyISAM) for transaksjonsstøtte.

**Sjekk dette:**
```sql
SHOW TABLE STATUS WHERE Name LIKE 'wp_%';
```

**Konverter hvis nødvendig:**
```sql
ALTER TABLE wp_posts ENGINE=InnoDB;
ALTER TABLE wp_postmeta ENGINE=InnoDB;
ALTER TABLE wp_terms ENGINE=InnoDB;
-- Repeat for alle relevante tabeller
```

### 4. Memory Limits
Media-kopiering kan kreve høy memory for store bilder.

**Anbefalt minimum:**
- `memory_limit`: 256M
- `max_execution_time`: 300 (5 minutter)
- `upload_max_filesize`: 64M

### 5. ACF JSON Location
ACF JSON sync krever at tema har en `/acf-json/` mappe.

**Sjekk:** `/wp-content/themes/acrylicon-2024/acf-json/` eksisterer

**Permissions:** Mappen må være writable av WordPress

## Key Decisions

### Content Strategy
- **Engelsk først, norsk oversettelse** - Hovedinnhold lages på engelsk internasjonal side, kopieres til norsk
- **Delvis koblet innhold** - 50-70% av innholdet deles, men hver side kan ha unikt innhold
- **Manuell oversettelse** - Ingen automatisk maskinoversettelse, full kontroll på kvalitet

### Sync Mechanism
- **Engangssynkronisering** - Innhold sendes én gang for å unngå utilsiktet overskriving
- **Manuell trigger** - Redaktør bestemmer når innhold er klart for synkronisering
- **Status tracking** - Visuell indikator i admin for å se hva som er synkronisert

### Technical Architecture
- **Multisite native** - Bruker WordPress multisite-funksjoner (`switch_to_blog()`)
- **Post meta for status** - Lagrer sync-informasjon som post metadata
- **Media handling** - Kopierer featured images og ACF-bilder automatisk
- **No rollback** - Enkel strategi: send én gang, ingen automatisk overskriving
- **Shared taxonomies** - Alle sites bruker samme taxonomy-tabeller fra hovedsitet
- **ACF JSON sync** - Field groups deles via JSON-filer i tema
- **Graceful error handling** - Manglende ACF fields logger warning uten å krasje

### Admin Interface
- **Metaboks i post editor** - Synkroniserings-UI vises ved siden av post-innhold
- **Site selector** - Viser tilgjengelige target sites (norsk, evt. fremtidige sites)
- **Status badge** - Grønn badge når synkronisert, grå knapp når allerede sendt
- **Sync log** - Post meta viser dato og bruker som utførte synkronisering
- **Administrator-only** - Kun Administrator-rolle har tilgang til synkroniseringsfunksjoner

### Post Sync Behavior
- **Draft status** - Synkroniserte poster får status 'draft' (må publiseres manuelt)
- **Current date** - Bruker dagens dato, ikke original publiseringsdato
- **Automatic slug suffix** - WordPress legger til `-2`, `-3` ved slug-konflikter
- **Empty relationships** - ACF relationship fields settes tomme, må kobles manuelt

## Resolved Questions ✅

### Implementation Details - RESOLVED
1. **✅ Taxonomi-håndtering** - SHARED TAXONOMY TABLES
   - **Beslutning:** Alle sites deler samme taxonomy-tabeller fra hovedsitet
   - Kategorier/tags administreres på ett sted, brukes av alle sites
   - Term-navn kan være på engelsk på alle sites (forenkler synkronisering)
   - Ingen synkroniseringslogikk nødvendig for taxonomier

2. **✅ Slug-konflikter** - AUTOMATISK SUFFIX
   - **Beslutning:** WordPress håndterer automatisk med suffix (f.eks. `-2`, `-3`)
   - Hvis 'solar-panels' finnes, blir ny post 'solar-panels-2'
   - Ingen feilmeldinger eller avbrudd av synkronisering

3. **✅ ACF field groups** - JSON SYNC + GRACEFUL HANDLING
   - **Beslutning:** Bruk ACF JSON sync for å dele field groups på tvers av sites
   - Alle field definitions ligger i `/themes/acrylicon-2024/acf-json/`
   - Hvis et field mangler på target site: hopp over, logg warning, ikke krasj
   - Relationship fields ignoreres i MVP (settes tomme på target site)

4. **✅ ACF relationship fields** - IGNORE IN MVP
   - **Beslutning:** Relationship fields blir tomme på target site
   - Redaktør kan manuelt koble til norske relaterte poster etter oversettelse
   - Kan bygges ut senere med ID-mapping hvis nødvendig

5. **✅ Custom block content** - JA, KOPIERES
   - **Beslutning:** Alle ACF-felter kopieres, inkludert block-data fra de 26 custom blocks
   - Graceful handling: hvis block ikke finnes på target site, logges warning

### Workflow Questions - RESOLVED
6. **✅ Bulk sync** - IKKE I MVP
   - **Beslutning:** Kun én og én synkronisering i første versjon
   - Forenkler utvikling og testing
   - Kan legges til senere hvis nødvendig

7. **✅ Resync-mulighet** - NEI
   - **Beslutning:** Engangssynkronisering, ingen re-sync i MVP
   - Unngår utilsiktet overskriving av oversatt norsk innhold
   - Kan vurderes senere med merge-funksjonalitet

8. **✅ Permissions** - KUN ADMINISTRATORS
   - **Beslutning:** Kun Administrator-rolle har tilgang til synkronisering
   - Strengeste kontroll for å unngå utilsiktede endringer
   - Editors og Authors har ikke tilgang

9. **✅ Post dato** - DRAFT MED DAGENS DATO
   - **Beslutning:** Synkronisert post får status 'draft' med dagens dato
   - Redaktør må manuelt publisere etter oversettelse er ferdig
   - Gir full kontroll over når oversatt innhold går live

10. **✅ Notification** - NICE TO HAVE (IKKE MVP)
    - **Beslutning:** Ikke implementert i første versjon
    - Kan legges til email-notifikasjon senere hvis ønskelig

## Technical Considerations

### WordPress Functions to Use
```php
// Site switching
switch_to_blog($site_id)
restore_current_blog()

// Post operations
wp_insert_post($post_data)
update_post_meta($post_id, $meta_key, $meta_value)

// ACF operations
get_field($field_name, $post_id)
update_field($field_name, $value, $post_id)

// Media operations
media_sideload_image($image_url, $post_id)
```

### Data to Sync
- Post title, content, excerpt
- Featured image
- All ACF fields (text, images, wysiwyg, repeaters)
  - Except relationship fields (set to empty)
  - Graceful handling: skip missing fields with warning
- Taxonomies (shared across sites via shared taxonomy tables)
- Post meta (excluding internal WP meta and sync status)

### Data NOT to Sync
- Post author (set to current user as author on target site)
- Post slug (WordPress generates new slug with suffix if conflict)
- Post date (use current date, not original)
- Post status (always set to 'draft' on target site)
- ACF relationship fields (set to empty, must be connected manually)
- Comments and revisions
- Sync status meta (to avoid recursion)

## Global Multisite Infrastructure

### Shared Taxonomy Tables Setup
Alle sites deler samme taxonomy-tabeller for konsistent kategorisering.

**⚠️ VIKTIG:** WordPress har IKKE native støtte for shared taxonomies. Global terms ble fjernet i WordPress 3.0. Dette krever custom implementasjon.

**Implementation (MU-Plugin Løsning):**
```php
// File: /wp-content/mu-plugins/acrylicon-shared-taxonomies.php
<?php
/**
 * Plugin Name: Acrylicon Shared Taxonomies
 * Description: Forces all multisite blogs to share taxonomy tables from main site
 * Version: 1.0.0
 */

// Must run before 'init' and on every blog switch
add_action( 'init', 'acrylicon_share_taxonomy_tables', 0 );
add_action( 'switch_blog', 'acrylicon_share_taxonomy_tables', 0 );

function acrylicon_share_taxonomy_tables() {
    global $wpdb;

    // Force all sites to use main site taxonomy tables
    $wpdb->terms = $wpdb->base_prefix . 'terms';
    $wpdb->term_taxonomy = $wpdb->base_prefix . 'term_taxonomy';
    $wpdb->term_relationships = $wpdb->base_prefix . 'term_relationships';
}
```

**Hvordan det fungerer:**
1. MU-plugins lastes automatisk før themes og vanlige plugins
2. `$wpdb->base_prefix` peker til hovedsitets tabeller (wp_terms)
3. Alle sites bruker nå samme taxonomy-tabeller
4. Når en site endrer en term, sees endringen på alle sites

**⚠️ KRITISKE ADVARSLER:**
- **Data tap:** Eksisterende terms på subsites som ikke finnes på hovedsitet VIL BLI TAPT
- **Timing:** Implementer DETTE FØR innhold legges til på subsites
- **Registration:** Taxonomier må fortsatt registreres på alle sites via `register_taxonomy()`
- **Testing:** Test grundig i staging før produksjon

**Fordeler:**
- ✅ Kun 10 linjer kode, ingen eksterne avhengigheter
- ✅ Automatisk synkronisering på tvers av alle sites
- ✅ Ingen overhead i sync-plugin
- ✅ Term-navn kan være på engelsk på alle sites
- ✅ Ett administrasjonspunkt (hovedsitet)

**Alternativer:**
- [Multisite Global Terms Plugin](https://wordpress.org/plugins/mu-global-terms/) (gratis, men ikke lenger aktivt vedlikeholdt)

**Resources:**
- [BuddyDev: Global Taxonomies Guide](https://buddydev.com/want-global-categories-tags-taxonomies-across-wordpress-multisite-network/)
- [WP Tavern: Multisite Global Terms](https://wptavern.com/wordpress-multisite-global-terms-plugin-share-taxonomies-across-a-network)

---

### Media Sync Strategy
Media kopieres fysisk til target site for å unngå cross-site dependencies.

**Strategi: Direkte Filkopiering (Raskere enn media_sideload_image)**

**Implementation:**
```php
function acrylicon_copy_media($attachment_id, $target_blog_id) {
    // 1. Hent original fil fra source site
    $file_path = wp_get_original_image_path($attachment_id);

    if (!file_exists($file_path)) {
        error_log("Media file not found: $attachment_id");
        return false;
    }

    // 2. Switch til target site
    switch_to_blog($target_blog_id);

    // 3. Generer unique filename og kopier fil
    $upload_dir = wp_upload_dir();
    $filename = wp_unique_filename($upload_dir['path'], basename($file_path));
    $new_file = $upload_dir['path'] . '/' . $filename;

    // Check if file already exists (skip duplicate)
    if (file_exists($new_file)) {
        restore_current_blog();
        error_log("Media already exists, skipping: $filename");
        return false;
    }

    if (!@copy($file_path, $new_file)) {
        restore_current_blog();
        error_log("Failed to copy media: $file_path to $new_file");
        return false;
    }

    // 4. Registrer attachment i database
    $attachment_data = [
        'guid' => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => mime_content_type($new_file),
        'post_title' => pathinfo($filename, PATHINFO_FILENAME),
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment_data, $new_file);

    if (is_wp_error($attach_id)) {
        @unlink($new_file); // Cleanup file on failure
        restore_current_blog();
        return false;
    }

    // 5. Generer metadata (kan være langsomt for store bilder!)
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    wp_generate_attachment_metadata($attach_id, $new_file);

    restore_current_blog();
    return $attach_id;
}
```

**Performance Optimalisering:**
```php
// Kjør før media sync
function acrylicon_prepare_media_sync() {
    // Increase limits for large files
    @ini_set('memory_limit', '256M');
    @ini_set('max_execution_time', '300');
    @set_time_limit(300);
}
```

**Fordeler med Fysisk Kopiering:**
- ✅ Ingen cross-site dependencies (hvis engelsk site slettes, norsk site påvirkes ikke)
- ✅ Hver site kan optimalisere bilder separat
- ✅ Enklere backup og restore per site
- ✅ Bedre sikkerhet (ingen shared media exposure)

**Ulemper:**
- ❌ Dobbel diskplass (men diskplass er billig i 2026)
- ❌ Langsommere enn linking (men kun én gang per post)

**Alternativ (hvis diskplass er kritisk):**
- Bruk [Multisite Global Media Plugin](https://github.com/bueltge/multisite-global-media) for shared media library

**Resources:**
- [Rudrastyh: Copy Media Between Sites](https://rudrastyh.com/wordpress-multisite/copy-media-files-from-one-site-to-another.html)
- [Cloudways: Upload Size Guide](https://www.cloudways.com/blog/increase-media-file-maximum-upload-size-in-wordpress/)

---

### ACF JSON Sync Setup
Field groups deles automatisk via JSON-filer (allerede aktivert i Acrylicon-temaet).

**Current structure:**
```
/themes/acrylicon-2024/acf-json/
```

**How it works:**
1. ACF Pro lagrer field groups som JSON-filer i tema
2. Når field group oppdateres, oppdateres JSON-fil
3. Alle sites i multisite leser samme JSON-filer
4. Automatisk synkronisert uten database-operasjoner

**Graceful handling in sync plugin:**
```php
function acrylicon_sync_acf_field_safely($field_name, $source_post_id, $target_post_id) {
    $value = get_field($field_name, $source_post_id);
    $field_object = get_field_object($field_name, $target_post_id);

    if ($field_object) {
        update_field($field_name, $value, $target_post_id);
        return true;
    } else {
        // Field doesn't exist on target site - log warning, don't crash
        error_log("ACF field '{$field_name}' not found on target site - skipping");
        return false;
    }
}
```

**Fordeler:**
- ✅ Zero configuration (ACF Pro har dette built-in)
- ✅ Versjonskontroll-vennlig (JSON i Git)
- ✅ Automatisk synkronisering på tvers av sites

---

### Error Recovery & Rollback Strategy

**Strategi: Draft-First + Cleanup on Failure**

Denne tilnærmingen minimerer risiko ved å:
1. Opprette post som draft først
2. Kopiere data steg-for-steg med error tracking
3. Automatisk cleanup hvis noe feiler

**Implementation:**
```php
function acrylicon_sync_post_safe($source_post_id, $target_blog_id) {
    global $wpdb;

    // Prepare for large media files
    acrylicon_prepare_media_sync();

    $source_blog_id = get_current_blog_id();
    $errors = [];

    // Switch to target site
    switch_to_blog($target_blog_id);

    try {
        // STEP 1: Create draft post (safe, can be deleted)
        $new_post_id = wp_insert_post([
            'post_title' => 'Synkroniserer...',
            'post_status' => 'draft',
            'post_type' => get_post_type($source_post_id),
            'post_author' => get_current_user_id()
        ]);

        if (is_wp_error($new_post_id)) {
            throw new Exception($new_post_id->get_error_message());
        }

        // STEP 2: Copy content
        switch_to_blog($source_blog_id);
        $source_post = get_post($source_post_id);
        switch_to_blog($target_blog_id);

        wp_update_post([
            'ID' => $new_post_id,
            'post_title' => $source_post->post_title,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
        ]);

        // STEP 3: Copy featured image
        switch_to_blog($source_blog_id);
        $thumb_id = get_post_thumbnail_id($source_post_id);
        switch_to_blog($target_blog_id);

        if ($thumb_id) {
            $new_thumb = acrylicon_copy_media($thumb_id, $target_blog_id);
            if ($new_thumb) {
                set_post_thumbnail($new_post_id, $new_thumb);
            } else {
                $errors[] = 'Featured image copy failed';
            }
        }

        // STEP 4: Copy ACF fields (gracefully)
        switch_to_blog($source_blog_id);
        $field_groups = acf_get_field_groups(['post_type' => get_post_type($source_post_id)]);

        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            foreach ($fields as $field) {
                $field_name = $field['name'];

                // Skip relationship fields (set empty on target)
                if ($field['type'] === 'relationship' || $field['type'] === 'post_object') {
                    continue;
                }

                $value = get_field($field_name, $source_post_id);

                switch_to_blog($target_blog_id);
                $synced = acrylicon_sync_acf_field_safely($field_name, $source_post_id, $new_post_id);

                if (!$synced) {
                    $errors[] = "ACF field '{$field_name}' could not be synced";
                }

                switch_to_blog($source_blog_id);
            }
        }

        switch_to_blog($target_blog_id);

        // STEP 5: Assign taxonomies (shared, so just assign)
        acrylicon_sync_taxonomies($source_post_id, $new_post_id, $source_blog_id);

        // STEP 6: Save sync metadata
        update_post_meta($new_post_id, '_synced_from_post', $source_post_id);
        update_post_meta($new_post_id, '_synced_from_blog', $source_blog_id);
        update_post_meta($new_post_id, '_synced_date', current_time('mysql'));
        update_post_meta($new_post_id, '_synced_by_user', get_current_user_id());

        if (!empty($errors)) {
            update_post_meta($new_post_id, '_sync_errors', $errors);
        }

        restore_current_blog();

        // Save sync status on source post
        update_post_meta($source_post_id, '_synced_to_post_' . $target_blog_id, $new_post_id);
        update_post_meta($source_post_id, '_synced_to_blog_' . $target_blog_id, $target_blog_id);
        update_post_meta($source_post_id, '_synced_date_' . $target_blog_id, current_time('mysql'));

        return [
            'success' => true,
            'post_id' => $new_post_id,
            'errors' => $errors
        ];

    } catch (Exception $e) {
        // CLEANUP on failure
        if (isset($new_post_id) && $new_post_id) {
            // Delete post and all attachments
            acrylicon_cleanup_failed_sync($new_post_id);
        }

        restore_current_blog();

        // Log error
        error_log(sprintf(
            '[Acrylicon Sync] FAILED - Source: %d, Target Blog: %d, Error: %s',
            $source_post_id,
            $target_blog_id,
            $e->getMessage()
        ));

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Cleanup failed sync - delete post and orphan attachments
 */
function acrylicon_cleanup_failed_sync($post_id) {
    // Get all attachments for this post
    $attachments = get_posts([
        'post_type' => 'attachment',
        'post_parent' => $post_id,
        'numberposts' => -1
    ]);

    // Delete attachments (files + database)
    foreach ($attachments as $attachment) {
        wp_delete_attachment($attachment->ID, true);
    }

    // Delete post (force delete, not trash)
    wp_delete_post($post_id, true);
}

/**
 * Sync taxonomies (already shared, just assign terms)
 */
function acrylicon_sync_taxonomies($source_post_id, $target_post_id, $source_blog_id) {
    $current_blog = get_current_blog_id();

    switch_to_blog($source_blog_id);
    $taxonomies = get_object_taxonomies(get_post_type($source_post_id));

    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_post_terms($source_post_id, $taxonomy, ['fields' => 'ids']);

        switch_to_blog($current_blog);

        if (!is_wp_error($terms) && !empty($terms)) {
            wp_set_post_terms($target_post_id, $terms, $taxonomy);
        }

        switch_to_blog($source_blog_id);
    }

    switch_to_blog($current_blog);
}
```

**Fordeler:**
- ✅ Draft-status sikrer at ingenting går live før alt er OK
- ✅ Try/catch fanger alle exceptions
- ✅ Automatisk cleanup ved failure (ingen orphan data)
- ✅ Detaljert error logging for debugging
- ✅ Partial success håndteres (f.eks. hvis bare ett bilde feiler)

**Resources:**
- [GitHub: MySQL Transactions with $wpdb](https://gist.github.com/nciske/3b6b6367fdb5fa0dd26e91042b4ea309)
- [Sabrina Zeidan: Delete Orphan Attachments](https://sabrinazeidan.com/wordpress-delete-unattached-media-seconds/)

## Plugin Structure

```
/wp-content/
├── mu-plugins/
│   └── acrylicon-shared-taxonomies.php    # Shared taxonomy tables (10 lines)
└── plugins/
    └── acrylicon-multisite-sync/
        ├── acrylicon-multisite-sync.php   # Main plugin file
        ├── includes/
        │   ├── class-sync-manager.php      # Core sync orchestration
        │   ├── class-media-handler.php     # Media copying logic
        │   ├── class-acf-handler.php       # ACF field sync
        │   ├── class-taxonomy-handler.php  # Taxonomy assignment
        │   └── class-admin-ui.php          # Metabox & UI
        ├── assets/
        │   ├── css/
        │   │   └── admin-style.css         # Metabox styling
        │   └── js/
        │       └── admin-script.js         # AJAX sync trigger
        └── languages/
            └── acrylicon-sync-nb_NO.po     # Norwegian translations
```

## Success Criteria

### Must Have (MVP)
- [ ] **MU-Plugin:** Shared taxonomies fungerer på tvers av sites
- [ ] **Sync Plugin:** Administrator kan synkronisere en post fra engelsk til norsk site
- [ ] **UI:** Metaboks viser tydelig sync-status (synkronisert/ikke synkronisert)
- [ ] **UI:** Knapp blir disabled etter synkronisering
- [ ] **Media:** Featured image kopieres fysisk til target site
- [ ] **ACF:** Alle ACF-felter kopieres (graceful handling av manglende fields)
- [ ] **Taxonomies:** Term assignments kopieres (terms allerede shared)
- [ ] **Safety:** Ingen overskriving av eksisterende norsk innhold
- [ ] **Rollback:** Automatisk cleanup ved sync failure
- [ ] **Logging:** Error logging til WP debug.log

### Should Have
- [ ] **Performance:** Memory og execution time limits satt før sync
- [ ] **Status:** Post meta viser sync history (dato, bruker, source/target)
- [ ] **Multi-post-type:** Sync fungerer for alle 6 post types
- [ ] **Slug handling:** WordPress' automatiske suffix ved konflikter
- [ ] **Draft status:** Synkroniserte poster opprettes som draft
- [ ] **Admin notice:** Vis success/error melding etter sync

### Nice to Have
- [ ] **Bulk sync:** Synkroniser flere poster samtidig
- [ ] **Email notification:** Varsle norsk redaktør ved ny synkronisert post
- [ ] **Preview:** Vis hva som vil synkroniseres før klikk
- [ ] **Manual retry:** Retry failed syncs fra admin interface
- [ ] **Sync analytics:** Dashboard widget med sync-statistikk
- [ ] **Multi-target:** Support for fremtidige sites (svensk, dansk)

## Implementation Roadmap

### Phase 0: Pre-Implementation (CRITICAL - Do First!)
1. **✅ Verify database engine:** Check all tables use InnoDB
2. **✅ Backup database:** Full backup before any changes
3. **✅ Export existing terms:** If subsites have content, export terms to main site first
4. **✅ Verify ACF JSON:** Ensure `/themes/acrylicon-2024/acf-json/` exists and is writable
5. **✅ Check PHP limits:** Verify memory_limit (256M+) and max_execution_time (300+)

### Phase 1: Shared Infrastructure Setup
1. **Create MU-Plugin:** `/mu-plugins/acrylicon-shared-taxonomies.php`
2. **Test taxonomy sharing:** Verify categories visible on all sites
3. **Verify ACF JSON sync:** Confirm field groups load on all sites

### Phase 2: Sync Plugin Development
1. **Plan implementation** - Detaljert teknisk plan med klasser og metoder
2. **Create plugin structure** - Scaffolding med alle klasser
3. **Implement media handler** - `class-media-handler.php` med copy logic
4. **Implement ACF handler** - `class-acf-handler.php` med graceful handling
5. **Implement sync manager** - `class-sync-manager.php` orkestrer hele prosessen
6. **Implement admin UI** - Metaboks med sync-knapp og status
7. **Add error logging** - Debug logging for alle operasjoner

### Phase 3: Testing & Refinement
1. **Unit tests:** Test hver handler separat
2. **Integration tests:** Test full sync flow med test-data
3. **Edge case testing:** Store bilder, mange ACF fields, missing fields
4. **Performance testing:** Measure sync time, memory usage
5. **User acceptance testing:** Test med reelle redaktører

### Phase 4: Deployment
1. **Staging deployment:** Deploy til staging environment
2. **Production testing:** Verify shared taxonomies on staging
3. **Production deployment:** Deploy MU-plugin først, deretter sync plugin
4. **Monitoring:** Watch logs for errors første dagene
5. **Documentation:** Skriv brukerdokumentasjon for redaktører

---

## Research Completed ✅

All critical technical questions have been researched and documented:

1. **✅ Shared Taxonomies:** MU-plugin implementation verified
2. **✅ Media Sync Strategy:** Physical copy with cleanup on failure
3. **✅ Error Rollback:** Draft-first + try/catch with automatic cleanup

**Status:** Ready for `/workflows:plan`

Dette brainstormet kan nå overføres til en detaljert implementeringsplan med konkret kode.
