---
title: WordPress Multisite Content Sync Plugin Implementation
category: wordpress-plugins
module: multisite-sync
tags:
  - wordpress
  - multisite
  - content-sync
  - acf
  - i18n
  - plugin-development
date: 2026-01-26
status: solved
severity: medium
symptoms:
  - Need to sync content between multisite installations
  - WPML/Polylang bloat for simple use case
  - Manual content duplication across sites
  - Risk of content drift between language sites
related_issues: []
---

# WordPress Multisite Content Sync Plugin Implementation

## Problem

Acrylicon har et WordPress multisite med norsk hovedside og engelsk internasjonal side. Teamet trengte en måte å:

1. Synkronisere utvalgt innhold mellom sites (50-70% av content)
2. Unngå bloat fra WPML/Polylang for en enkel use case
3. Støtte bi-directional synkronisering (norsk→engelsk eller engelsk→norsk)
4. Håndtere ACF fields, media, og taxonomier korrekt
5. Tillate manuell oversettelse etter synkronisering

**Utfordringer:**
- WordPress fjernet native shared taxonomies i versjon 3.0
- Media må kopieres fysisk for site-uavhengighet
- ACF fields kan mangle på target site
- Partial sync failures må ryddes opp automatisk
- One-way sync per site for å unngå overskriving av oversettelser

## Investigation Steps

### Hva vi prøvde først

1. **WPML/Polylang evaluering**
   - For tungt for use case
   - Overkill når kun 6 post types skal synkroniseres
   - Lisenskostnader

2. **Native WordPress Multisite Features**
   - Ingen innebygd content sync
   - Shared taxonomies fjernet i WP 3.0
   - Måtte bygge custom løsning

3. **Media Sync Strategier**
   - **Linking vs Copying**: Valgte physical copy for uavhengighet
   - **media_sideload_image()**: For treg, valgte direkte `copy()`
   - **wp_get_original_image_path()**: Beste måte å få source fil

### Root Cause Analysis

WordPress Multisite ble ikke designet med content sync i tankene. Hver subsite har:
- Egne post/postmeta tabeller (`wp_2_posts`, `wp_3_posts`, etc.)
- Egne taxonomy tabeller (før WP 3.0)
- Egne upload directories
- Ingen native sync-mekanisme

For å løse dette trengte vi:
1. **Shared taxonomies**: Custom MU-plugin som overskriver `$wpdb->terms`
2. **Media handler**: Physical file copy mellom upload directories
3. **ACF sync**: Graceful handling hvis fields mangler på target
4. **Error recovery**: Draft-first pattern med cleanup

## Solution

### Architecture Overview

```
WordPress Multisite
├── mu-plugins/
│   └── acrylicon-shared-taxonomies.php    # Forces shared taxonomy tables
└── plugins/
    └── acrylicon-multisite-sync/
        ├── acrylicon-multisite-sync.php   # Main plugin file
        ├── includes/
        │   ├── class-sync-manager.php      # Orchestrates sync process
        │   ├── class-media-handler.php     # Physical media copying
        │   ├── class-acf-handler.php       # ACF field syncing
        │   ├── class-taxonomy-handler.php  # Term assignment
        │   └── class-admin-ui.php          # Metabox interface
        └── assets/
            ├── css/admin-style.css
            └── js/admin-script.js
```

### Implementation Details

#### 1. MU-Plugin: Shared Taxonomies

**File:** `/wp-content/mu-plugins/acrylicon-shared-taxonomies.php`

```php
<?php
/**
 * Plugin Name: Acrylicon Shared Taxonomies
 * Description: Forces all multisite blogs to share taxonomy tables from main site
 * Version: 1.0.0
 */

add_action( 'init', 'acrylicon_share_taxonomy_tables', 0 );
add_action( 'switch_blog', 'acrylicon_share_taxonomy_tables', 0 );

function acrylicon_share_taxonomy_tables() {
    global $wpdb;

    // Force use of main site tables instead of blog-specific tables
    $wpdb->terms = $wpdb->base_prefix . 'terms';
    $wpdb->term_taxonomy = $wpdb->base_prefix . 'term_taxonomy';
    $wpdb->term_relationships = $wpdb->base_prefix . 'term_relationships';
}
```

**Why this works:**
- Runs before `init` hook (priority 0)
- Runs on every `switch_blog()` call
- Must be MU-plugin (loads before themes/plugins)

**⚠️ WARNING:** This will DELETE existing subsite terms. Export first if needed.

#### 2. Sync Manager: Draft-First Pattern

**Key Pattern:** Create as draft, populate, then cleanup on failure

```php
public function sync_post( $source_post_id, $target_blog_id ) {
    switch_to_blog( $target_blog_id );

    try {
        // STEP 1: Create draft post (safe, can be deleted)
        $new_post_id = wp_insert_post([
            'post_title' => 'Synkroniserer...',
            'post_status' => 'draft',
            'post_type' => $post_type,
            'post_author' => get_current_user_id()
        ]);

        // STEP 2-5: Copy content, media, ACF fields, taxonomies
        $this->copy_post_content( $source_post_id, $new_post_id, $source_blog_id );
        $this->copy_featured_image( $source_post_id, $new_post_id, $source_blog_id );
        $this->acf_handler->sync_fields( $source_post_id, $new_post_id, $source_blog_id );
        $this->taxonomy_handler->sync_taxonomies( $source_post_id, $new_post_id, $source_blog_id );

        // STEP 6: Save metadata
        $this->save_sync_metadata( $source_post_id, $new_post_id, $source_blog_id, $target_blog_id, $errors );

        restore_current_blog();
        return ['success' => true, 'post_id' => $new_post_id];

    } catch ( \Exception $e ) {
        // CLEANUP on failure
        if ( isset( $new_post_id ) && $new_post_id ) {
            $this->cleanup_failed_sync( $new_post_id );
        }
        restore_current_blog();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

#### 3. Media Handler: Physical File Copy

```php
public function copy_media( $attachment_id, $target_blog_id, $source_blog_id ) {
    // Get source file
    switch_to_blog( $source_blog_id );
    $file_path = wp_get_original_image_path( $attachment_id );
    restore_current_blog();

    // Switch to target and copy
    switch_to_blog( $target_blog_id );
    $upload_dir = wp_upload_dir();
    $filename = wp_unique_filename( $upload_dir['path'], basename( $file_path ) );
    $new_file = $upload_dir['path'] . '/' . $filename;

    // Physical copy
    if ( ! @copy( $file_path, $new_file ) ) {
        restore_current_blog();
        return false;
    }

    // Register in database
    $attach_id = wp_insert_attachment( $attachment_data, $new_file );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    wp_generate_attachment_metadata( $attach_id, $new_file );

    restore_current_blog();
    return $attach_id;
}
```

**Why physical copy vs linking:**
- Site independence (deleting english site won't break norwegian)
- Different upload directories per site
- Allows independent image editing

#### 4. ACF Handler: Graceful Sync

```php
public function sync_fields( $source_post_id, $target_post_id, $source_blog_id ) {
    $errors = [];

    foreach ( $field_groups as $group ) {
        $fields = acf_get_fields( $group['key'] );

        foreach ( $fields as $field ) {
            // Skip relationship fields
            if ( in_array( $field['type'], ['relationship', 'post_object'] ) ) {
                continue;
            }

            // Get value from source
            $value = get_field( $field['name'], $source_post_id );

            // Sync safely to target
            $synced = $this->sync_field_safely( $field['name'], $value, $target_post_id );
            if ( ! $synced ) {
                $errors[] = "ACF field '{$field['name']}' could not be synced";
            }
        }
    }

    return $errors;
}

private function sync_field_safely( $field_name, $value, $target_post_id ) {
    $field_object = get_field_object( $field_name, $target_post_id );

    if ( $field_object ) {
        update_field( $field_name, $value, $target_post_id );
        return true;
    } else {
        error_log( "ACF field '{$field_name}' not found on target site - skipping" );
        return false;
    }
}
```

**Why this approach:**
- `get_field_object()` checks if field exists before syncing
- Logs warnings instead of crashing
- Skips relationship fields (cross-site IDs won't match)

#### 5. Admin UI: Metabox with Status

```php
public function render_sync_metabox( $post ) {
    $sites = get_sites();
    $current_blog_id = get_current_blog_id();

    // Check if already synced
    foreach ( $sites as $site ) {
        if ( $this->sync_manager->is_synced( $post->ID, $site->blog_id ) ) {
            echo "✓ Synkronisert til: " . get_blog_details( $site->blog_id )->blogname;
        }
    }

    // Site selector dropdown
    echo '<select id="target_blog_id">';
    foreach ( $sites as $site ) {
        $is_synced = $this->sync_manager->is_synced( $post->ID, $site->blog_id );
        echo '<option value="' . $site->blog_id . '" ' . disabled( $is_synced ) . '>';
        echo get_blog_details( $site->blog_id )->blogname;
        echo $is_synced ? '(Allerede synkronisert)' : '';
        echo '</option>';
    }
    echo '</select>';

    // Sync button
    echo '<button id="acrylicon-sync-button">Synkroniser nå</button>';
}
```

### Supported Post Types

Plugin fungerer kun med disse 6 custom post types:
- `referanser`
- `produkter`
- `bruksomrader`
- `godegrunner`
- `levetidskostnader`
- `baerekreaftig`

### File Structure

```
/wp-content/
├── mu-plugins/
│   └── acrylicon-shared-taxonomies.php (27 lines)
├── plugins/
│   └── acrylicon-multisite-sync/
│       ├── acrylicon-multisite-sync.php (60 lines)
│       ├── includes/
│       │   ├── class-sync-manager.php (212 lines)
│       │   ├── class-media-handler.php (76 lines)
│       │   ├── class-acf-handler.php (75 lines)
│       │   ├── class-taxonomy-handler.php (41 lines)
│       │   └── class-admin-ui.php (229 lines)
│       └── assets/
│           ├── css/admin-style.css (80 lines)
│           └── js/admin-script.js (67 lines)
└── themes/
    └── acrylicon-2024/
        └── acf-json/ (created for ACF field group sync)
```

**Totalt: 867 linjer kode**

## Deployment Steps

### Phase 0: Pre-Implementation Checklist

1. **Database Backup**
   ```bash
   wp db export backup-$(date +%Y%m%d-%H%M%S).sql
   ```

2. **Verify Database Engine**
   ```sql
   SHOW TABLE STATUS WHERE Name LIKE 'wp_%';
   -- All tables must use InnoDB (not MyISAM)
   ```

3. **Create ACF JSON Folder**
   ```bash
   mkdir -p wp-content/themes/acrylicon-2024/acf-json
   chmod 755 wp-content/themes/acrylicon-2024/acf-json
   ```

4. **Verify PHP Limits**
   - memory_limit >= 256M
   - max_execution_time >= 300
   - upload_max_filesize >= 64M

### Phase 1: Activate Plugins

1. MU-plugin loads automatically (verify in Network Admin → Must-Use)
2. Activate "Acrylicon Multisite Sync" in Plugins

### Phase 2: Test Sync

1. Åpne en post av supported post type
2. Se metabox "Multisite Synkronisering" i sidebar
3. Velg target site
4. Klikk "Synkroniser nå"
5. Verifiser draft opprettes på target site

## Prevention Strategies

### 1. Pre-Sync Validation

```php
// Check post is published before syncing
if ( get_post_status( $source_post_id ) !== 'publish' ) {
    return ['success' => false, 'error' => 'Only published posts can be synced'];
}

// Check if already synced
if ( $this->is_synced( $source_post_id, $target_blog_id ) ) {
    return ['success' => false, 'error' => 'Already synced to this site'];
}
```

### 2. Transaction-Like Pattern

```php
// Draft-first ensures atomic-like behavior
try {
    $new_post_id = wp_insert_post(['post_status' => 'draft', ...]);
    // ... all sync operations ...
    // If we get here, sync succeeded
} catch ( \Exception $e ) {
    // Cleanup everything created
    $this->cleanup_failed_sync( $new_post_id );
}
```

### 3. Metadata Tracking

```php
// On source post
update_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id, $target_post_id );
update_post_meta( $source_post_id, '_synced_date_' . $target_blog_id, current_time('mysql') );

// On target post
update_post_meta( $target_post_id, '_synced_from_post', $source_post_id );
update_post_meta( $target_post_id, '_synced_from_blog', $source_blog_id );
```

### 4. Error Logging

```php
error_log( sprintf(
    '[Acrylicon Sync] FAILED - Source: %d, Target Blog: %d, Error: %s',
    $source_post_id,
    $target_blog_id,
    $e->getMessage()
) );
```

## Testing Checklist

- [ ] Sync creates draft post on target site
- [ ] Featured image copies correctly
- [ ] ACF fields sync (except relationships)
- [ ] Taxonomies assign correctly (shared terms)
- [ ] Post meta stores sync history
- [ ] Button disables after sync (prevents re-sync)
- [ ] Failed sync cleans up (deletes draft and media)
- [ ] Works both directions (norsk→engelsk and engelsk→norsk)
- [ ] Memory limits sufficient for large media
- [ ] ACF JSON folder enables field group sync

## Common Issues

### Issue 1: "Taxonomy table not shared"

**Symptom:** Terms don't appear on target site

**Solution:** Verify MU-plugin is loaded BEFORE themes/plugins
```php
// Must be in /mu-plugins/, not /plugins/
add_action( 'init', 'acrylicon_share_taxonomy_tables', 0 ); // Priority 0
```

### Issue 2: "Media copy failed"

**Symptom:** Featured image missing on target

**Solution:** Check file permissions and path
```bash
# Verify upload directory is writable
chmod 755 wp-content/uploads/
chmod 755 wp-content/uploads/sites/*/
```

### Issue 3: "ACF field not syncing"

**Symptom:** Some ACF fields empty on target

**Solution:** Check ACF JSON sync
```bash
# Verify ACF JSON folder exists and has permissions
ls -la wp-content/themes/acrylicon-2024/acf-json/
# Should contain .json files for each field group
```

### Issue 4: "Already synced" despite no sync

**Symptom:** Button disabled incorrectly

**Solution:** Clear sync metadata
```php
delete_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id );
delete_post_meta( $source_post_id, '_synced_to_blog_' . $target_blog_id );
```

## Related Documentation

- [WordPress Multisite Documentation](https://wordpress.org/support/article/multisite-network-administration/)
- [ACF JSON Sync](https://www.advancedcustomfields.com/resources/local-json/)
- [WordPress switch_to_blog()](https://developer.wordpress.org/reference/functions/switch_to_blog/)
- Brainstorm: `docs/brainstorms/2026-01-26-multisite-content-sync-brainstorm.md`
- Implementation Plan: `docs/plans/2026-01-26-feat-multisite-content-sync-plugin-plan.md`

## Key Learnings

1. **MU-plugins are critical for multisite**: Must load before themes/plugins for shared taxonomies
2. **Draft-first pattern = safe transactions**: Always create as draft, cleanup on failure
3. **Physical media copy > linking**: Prevents cross-site dependencies
4. **ACF graceful handling**: Check field existence before syncing
5. **Metadata tracking prevents re-sync**: Store sync history on both posts
6. **Bi-directional by design**: Direction determined by where button is clicked
7. **InnoDB required**: MyISAM lacks transaction support needed for cleanup

## Future Enhancements

- [ ] Bulk sync (select multiple posts)
- [ ] Sync scheduling (cron job)
- [ ] Revision history sync
- [ ] Comment sync (if needed)
- [ ] Better error reporting UI
- [ ] Sync status dashboard
- [ ] Rollback functionality
- [ ] Add more post types dynamically

## Tags

`wordpress` `multisite` `content-sync` `acf` `i18n` `plugin-development` `php` `custom-plugin` `shared-taxonomies` `media-handler`
