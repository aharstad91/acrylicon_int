---
title: WordPress Multisite Content Sync Plugin
type: feat
date: 2026-01-26
status: ready
priority: high
---

# WordPress Multisite Content Sync Plugin

## Overview

Implementer et custom WordPress plugin som lar administratorer synkronisere innhold fra engelsk internasjonal site til norsk hovedside i Acrylicon multisite-nettverk. Pluginet skal håndtere engangssynkronisering av posts, ACF fields, media og taxonomier med graceful error handling og automatisk cleanup ved failure.

**Kontekst:** Dette er en MVP-implementering basert på grundig brainstorming og research. Målet er å unngå bloat fra WPML/Polylang ved å bygge en enkel, fokusert løsning som kun gjør det vi trenger.

**Related Documentation:**
- Brainstorm: `docs/brainstorms/2026-01-26-multisite-content-sync-brainstorm.md`

## Problem Statement

Acrylicon har et WordPress multisite med norsk og engelsk site. Redaktører må kunne:
1. Opprette innhold på engelsk internasjonal side
2. Synkronisere utvalgt innhold til norsk side med en knapp-trykk
3. Oversette innholdet manuelt på norsk side
4. Unngå utilsiktet overskriving av oversatt innhold

**Utfordringer:**
- Shared taxonomies krever custom implementasjon (WordPress fjernet native støtte)
- Media må kopieres fysisk for å unngå cross-site dependencies
- ACF fields må håndteres gracefully hvis noen mangler på target site
- Partial sync failures må ryddes opp automatisk

## Proposed Solution

### Architecture Overview

```
WordPress Multisite
├── mu-plugins/
│   └── acrylicon-shared-taxonomies.php    # Shared taxonomy tables (Phase 1)
└── plugins/
    └── acrylicon-multisite-sync/          # Sync plugin (Phase 2)
        ├── acrylicon-multisite-sync.php   # Main plugin
        ├── includes/
        │   ├── class-sync-manager.php      # Orchestrates sync
        │   ├── class-media-handler.php     # Copies media files
        │   ├── class-acf-handler.php       # Syncs ACF fields
        │   ├── class-taxonomy-handler.php  # Assigns terms
        │   └── class-admin-ui.php          # Metabox UI
        └── assets/
            ├── css/admin-style.css
            └── js/admin-script.js
```

### Key Components

**1. MU-Plugin: Shared Taxonomies** (10 lines)
- Forces all sites to use main site's taxonomy tables
- Must load before themes/plugins (hence MU-plugin)

**2. Sync Manager** (Orchestration)
- Draft-first pattern (create post as draft, then populate)
- Try/catch error handling
- Automatic cleanup on failure
- Step-by-step logging

**3. Media Handler** (Physical Copy)
- Uses `wp_get_original_image_path()` for source
- Uses `copy()` for fast file transfer
- Registers with `wp_insert_attachment()`
- Skips duplicates based on filename

**4. ACF Handler** (Graceful Sync)
- Checks field existence before syncing
- Logs warnings for missing fields
- Skips relationship/post_object fields
- Continues despite errors

**5. Admin UI** (Metabox)
- Shows sync status badge
- Site selector dropdown
- Disabled button after sync
- Success/error notices

## Technical Approach

### Phase 0: Pre-Implementation Checklist ⚠️ CRITICAL

**MUST complete these before writing ANY code:**

1. **Database Engine Verification**
   ```sql
   -- Run this in MySQL
   SHOW TABLE STATUS WHERE Name LIKE 'wp_%';
   ```
   - Verify ALL tables use InnoDB (not MyISAM)
   - MyISAM lacks transaction support needed for rollback
   - Convert if needed: `ALTER TABLE wp_posts ENGINE=InnoDB;`

2. **Create ACF JSON Folder** ✅ COMPLETED
   ```bash
   mkdir -p /Applications/MAMP/htdocs/acrylicon/wp-content/themes/acrylicon-2024/acf-json
   chmod 755 /Applications/MAMP/htdocs/acrylicon/wp-content/themes/acrylicon-2024/acf-json
   ```
   - **Status:** Folder created successfully
   - **Impact:** ACF JSON sync is now ready
   - **Priority:** HIGH - DONE

3. **PHP Limits Verification**
   ```php
   // Check in wp-admin > Site Health > Info > Server
   memory_limit >= 256M
   max_execution_time >= 300
   upload_max_filesize >= 64M
   ```
   - Contact hosting provider if limits are too low
   - Or add to wp-config.php: `define('WP_MEMORY_LIMIT', '256M');`

4. **Database Backup**
   ```bash
   # Create full backup before ANY multisite changes
   wp db export backup-$(date +%Y%m%d-%H%M%S).sql
   ```

5. **Term Export (If Subsites Have Content)**
   - If subsites already have content with terms:
   - Export those terms to main site FIRST
   - Otherwise shared taxonomies will DELETE them
   - SQL query to check: `SELECT * FROM wp_2_terms;` (subsite 2)

---

### Phase 1: Shared Infrastructure Setup ✅ COMPLETED

#### Step 1.1: Create MU-Plugin for Shared Taxonomies ✅

**File:** `/wp-content/mu-plugins/acrylicon-shared-taxonomies.php`

```php
<?php
/**
 * Plugin Name: Acrylicon Shared Taxonomies
 * Description: Forces all multisite blogs to share taxonomy tables from main site
 * Version: 1.0.0
 * Author: Acrylicon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Force all sites to use main site taxonomy tables.
 * Must run before 'init' and on every blog switch.
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

**Testing:**
```php
// Add temporary test to functions.php
add_action('init', function() {
    global $wpdb;
    error_log('Site ' . get_current_blog_id() . ' using terms table: ' . $wpdb->terms);
}, 999);
```

**Expected Output in debug.log:**
```
Site 1 using terms table: wp_terms
Site 2 using terms table: wp_terms  # Not wp_2_terms!
Site 3 using terms table: wp_terms  # Not wp_3_terms!
```

#### Step 1.2: Verify ACF JSON Sync

**Action Items:**
1. Create `/themes/acrylicon-2024/acf-json/` folder
2. Edit any ACF field group in admin
3. Check folder for generated JSON file
4. Switch to another site in multisite
5. Verify field group loads correctly

**Success Criteria:**
- JSON files appear in `/acf-json/` after editing
- All sites see same field groups
- No "Sync available" warnings in ACF admin

---

### Phase 2: Sync Plugin Development ✅ COMPLETED

#### Step 2.1: Plugin Scaffolding ✅

**File:** `/wp-content/plugins/acrylicon-multisite-sync/acrylicon-multisite-sync.php`

```php
<?php
/**
 * Plugin Name: Acrylicon Multisite Sync
 * Plugin URI: https://acrylicon.com
 * Description: Synkroniser innhold fra engelsk til norsk site i multisite-nettverk
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.0
 * Author: Acrylicon
 * Text Domain: acrylicon-multisite-sync
 * Domain Path: /languages
 * Network: false
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'ACRYLICON_SYNC_VERSION', '1.0.0' );
define( 'ACRYLICON_SYNC_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ACRYLICON_SYNC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'ACRYLICON_SYNC_BASENAME', plugin_basename( __FILE__ ) );

// Load plugin classes
require_once ACRYLICON_SYNC_PATH . '/includes/class-sync-manager.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-media-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-acf-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-taxonomy-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-admin-ui.php';

// Initialize plugin
function acrylicon_sync_init() {
    // Only load for administrators
    if ( ! current_user_can( 'manage_network' ) ) {
        return;
    }

    // Initialize admin UI
    new Acrylicon_Multisite_Sync\Admin_UI();
}
add_action( 'plugins_loaded', 'acrylicon_sync_init' );

// Activation hook
register_activation_hook( __FILE__, 'acrylicon_sync_activate' );
function acrylicon_sync_activate() {
    // Check requirements
    if ( ! is_multisite() ) {
        deactivate_plugins( ACRYLICON_SYNC_BASENAME );
        wp_die( 'Dette pluginet krever WordPress Multisite.' );
    }

    if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
        deactivate_plugins( ACRYLICON_SYNC_BASENAME );
        wp_die( 'Dette pluginet krever PHP 8.0 eller høyere.' );
    }

    // Log activation
    error_log( 'Acrylicon Multisite Sync aktivert på site ' . get_current_blog_id() );
}
```

#### Step 2.2: Sync Manager Class

**File:** `/includes/class-sync-manager.php`

```php
<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sync_Manager {

    private $media_handler;
    private $acf_handler;
    private $taxonomy_handler;

    public function __construct() {
        $this->media_handler = new Media_Handler();
        $this->acf_handler = new ACF_Handler();
        $this->taxonomy_handler = new Taxonomy_Handler();
    }

    /**
     * Main sync orchestration with draft-first pattern
     *
     * @param int $source_post_id Source post ID
     * @param int $target_blog_id Target blog/site ID
     * @return array ['success' => bool, 'post_id' => int, 'errors' => array]
     */
    public function sync_post( $source_post_id, $target_blog_id ) {
        // Increase limits for large media
        $this->prepare_environment();

        $source_blog_id = get_current_blog_id();
        $errors = [];

        // Switch to target site
        switch_to_blog( $target_blog_id );

        try {
            // STEP 1: Create draft post (safe, can be deleted)
            $new_post_id = $this->create_draft_post( $source_post_id, $source_blog_id );

            if ( is_wp_error( $new_post_id ) ) {
                throw new \Exception( $new_post_id->get_error_message() );
            }

            // STEP 2: Copy content
            $this->copy_post_content( $source_post_id, $new_post_id, $source_blog_id );

            // STEP 3: Copy featured image
            $thumb_errors = $this->copy_featured_image( $source_post_id, $new_post_id, $source_blog_id );
            if ( ! empty( $thumb_errors ) ) {
                $errors = array_merge( $errors, $thumb_errors );
            }

            // STEP 4: Copy ACF fields
            $acf_errors = $this->acf_handler->sync_fields( $source_post_id, $new_post_id, $source_blog_id );
            if ( ! empty( $acf_errors ) ) {
                $errors = array_merge( $errors, $acf_errors );
            }

            // STEP 5: Assign taxonomies
            $this->taxonomy_handler->sync_taxonomies( $source_post_id, $new_post_id, $source_blog_id );

            // STEP 6: Save sync metadata
            $this->save_sync_metadata( $source_post_id, $new_post_id, $source_blog_id, $target_blog_id, $errors );

            restore_current_blog();

            return [
                'success' => true,
                'post_id' => $new_post_id,
                'errors' => $errors
            ];

        } catch ( \Exception $e ) {
            // CLEANUP on failure
            if ( isset( $new_post_id ) && $new_post_id ) {
                $this->cleanup_failed_sync( $new_post_id );
            }

            restore_current_blog();

            // Log error
            error_log( sprintf(
                '[Acrylicon Sync] FAILED - Source: %d, Target Blog: %d, Error: %s',
                $source_post_id,
                $target_blog_id,
                $e->getMessage()
            ) );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare environment for large media operations
     */
    private function prepare_environment() {
        @ini_set( 'memory_limit', '256M' );
        @ini_set( 'max_execution_time', '300' );
        @set_time_limit( 300 );
    }

    /**
     * Create draft post on target site
     */
    private function create_draft_post( $source_post_id, $source_blog_id ) {
        switch_to_blog( $source_blog_id );
        $post_type = get_post_type( $source_post_id );
        restore_current_blog();

        $new_post_id = wp_insert_post( [
            'post_title' => 'Synkroniserer...',
            'post_status' => 'draft',
            'post_type' => $post_type,
            'post_author' => get_current_user_id()
        ] );

        return $new_post_id;
    }

    /**
     * Copy post content fields
     */
    private function copy_post_content( $source_post_id, $target_post_id, $source_blog_id ) {
        switch_to_blog( $source_blog_id );
        $source_post = get_post( $source_post_id );
        restore_current_blog();

        wp_update_post( [
            'ID' => $target_post_id,
            'post_title' => $source_post->post_title,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
        ] );
    }

    /**
     * Copy featured image
     */
    private function copy_featured_image( $source_post_id, $target_post_id, $source_blog_id ) {
        $errors = [];

        switch_to_blog( $source_blog_id );
        $thumb_id = get_post_thumbnail_id( $source_post_id );
        restore_current_blog();

        if ( $thumb_id ) {
            $new_thumb_id = $this->media_handler->copy_media( $thumb_id, get_current_blog_id(), $source_blog_id );

            if ( $new_thumb_id ) {
                set_post_thumbnail( $target_post_id, $new_thumb_id );
            } else {
                $errors[] = 'Featured image copy failed';
            }
        }

        return $errors;
    }

    /**
     * Save sync metadata on both posts
     */
    private function save_sync_metadata( $source_post_id, $target_post_id, $source_blog_id, $target_blog_id, $errors ) {
        // On target post
        update_post_meta( $target_post_id, '_synced_from_post', $source_post_id );
        update_post_meta( $target_post_id, '_synced_from_blog', $source_blog_id );
        update_post_meta( $target_post_id, '_synced_date', current_time( 'mysql' ) );
        update_post_meta( $target_post_id, '_synced_by_user', get_current_user_id() );

        if ( ! empty( $errors ) ) {
            update_post_meta( $target_post_id, '_sync_errors', $errors );
        }

        // On source post
        switch_to_blog( $source_blog_id );
        update_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id, $target_post_id );
        update_post_meta( $source_post_id, '_synced_to_blog_' . $target_blog_id, $target_blog_id );
        update_post_meta( $source_post_id, '_synced_date_' . $target_blog_id, current_time( 'mysql' ) );
        restore_current_blog();
    }

    /**
     * Cleanup failed sync - delete post and orphan attachments
     */
    private function cleanup_failed_sync( $post_id ) {
        // Get all attachments for this post
        $attachments = get_posts( [
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'numberposts' => -1
        ] );

        // Delete attachments (files + database)
        foreach ( $attachments as $attachment ) {
            wp_delete_attachment( $attachment->ID, true );
        }

        // Delete post (force delete, not trash)
        wp_delete_post( $post_id, true );
    }

    /**
     * Check if post is already synced to target blog
     */
    public function is_synced( $source_post_id, $target_blog_id ) {
        $synced_post_id = get_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id, true );
        return ! empty( $synced_post_id );
    }
}
```

#### Step 2.3: Media Handler Class

**File:** `/includes/class-media-handler.php`

```php
<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Media_Handler {

    /**
     * Copy media file from source blog to target blog
     *
     * @param int $attachment_id Source attachment ID
     * @param int $target_blog_id Target blog/site ID
     * @param int $source_blog_id Source blog/site ID
     * @return int|false New attachment ID on success, false on failure
     */
    public function copy_media( $attachment_id, $target_blog_id, $source_blog_id ) {
        // Get source file path
        switch_to_blog( $source_blog_id );
        $file_path = wp_get_original_image_path( $attachment_id );
        $attachment_meta = wp_get_attachment_metadata( $attachment_id );
        restore_current_blog();

        if ( ! file_exists( $file_path ) ) {
            error_log( "Media file not found: $attachment_id at $file_path" );
            return false;
        }

        // Switch to target site
        switch_to_blog( $target_blog_id );

        // Generate unique filename
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename( $upload_dir['path'], basename( $file_path ) );
        $new_file = $upload_dir['path'] . '/' . $filename;

        // Check if file already exists (skip duplicate)
        if ( file_exists( $new_file ) ) {
            restore_current_blog();
            error_log( "Media already exists, skipping: $filename" );
            return false;
        }

        // Copy file
        if ( ! @copy( $file_path, $new_file ) ) {
            restore_current_blog();
            error_log( "Failed to copy media: $file_path to $new_file" );
            return false;
        }

        // Register attachment in database
        $attachment_data = [
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => mime_content_type( $new_file ),
            'post_title' => pathinfo( $filename, PATHINFO_FILENAME ),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment( $attachment_data, $new_file );

        if ( is_wp_error( $attach_id ) ) {
            @unlink( $new_file ); // Cleanup file on failure
            restore_current_blog();
            return false;
        }

        // Generate metadata (thumbnails, etc.)
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        wp_generate_attachment_metadata( $attach_id, $new_file );

        restore_current_blog();
        return $attach_id;
    }
}
```

#### Step 2.4: ACF Handler Class

**File:** `/includes/class-acf-handler.php`

```php
<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ACF_Handler {

    /**
     * Sync all ACF fields from source to target post
     *
     * @param int $source_post_id Source post ID
     * @param int $target_post_id Target post ID
     * @param int $source_blog_id Source blog ID
     * @return array Array of error messages
     */
    public function sync_fields( $source_post_id, $target_post_id, $source_blog_id ) {
        $errors = [];

        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return [ 'ACF Pro is not active' ];
        }

        // Get field groups for this post type
        switch_to_blog( $source_blog_id );
        $post_type = get_post_type( $source_post_id );
        $field_groups = acf_get_field_groups( [ 'post_type' => $post_type ] );

        foreach ( $field_groups as $group ) {
            $fields = acf_get_fields( $group['key'] );

            foreach ( $fields as $field ) {
                $field_name = $field['name'];

                // Skip relationship fields (set empty on target)
                if ( in_array( $field['type'], [ 'relationship', 'post_object' ] ) ) {
                    continue;
                }

                // Get value from source
                $value = get_field( $field_name, $source_post_id );

                // Switch to target and sync
                switch_to_blog( get_current_blog_id() );
                $synced = $this->sync_field_safely( $field_name, $value, $target_post_id );

                if ( ! $synced ) {
                    $errors[] = "ACF field '{$field_name}' could not be synced";
                }

                switch_to_blog( $source_blog_id );
            }
        }

        restore_current_blog();
        return $errors;
    }

    /**
     * Safely sync a single ACF field with graceful error handling
     */
    private function sync_field_safely( $field_name, $value, $target_post_id ) {
        $field_object = get_field_object( $field_name, $target_post_id );

        if ( $field_object ) {
            update_field( $field_name, $value, $target_post_id );
            return true;
        } else {
            // Field doesn't exist on target site - log warning, don't crash
            error_log( "ACF field '{$field_name}' not found on target site - skipping" );
            return false;
        }
    }
}
```

#### Step 2.5: Taxonomy Handler Class

**File:** `/includes/class-taxonomy-handler.php`

```php
<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Taxonomy_Handler {

    /**
     * Sync taxonomies from source to target post
     * Taxonomies are already shared, so we just assign the same term IDs
     *
     * @param int $source_post_id Source post ID
     * @param int $target_post_id Target post ID
     * @param int $source_blog_id Source blog ID
     */
    public function sync_taxonomies( $source_post_id, $target_post_id, $source_blog_id ) {
        $current_blog = get_current_blog_id();

        // Get all taxonomies for source post type
        switch_to_blog( $source_blog_id );
        $taxonomies = get_object_taxonomies( get_post_type( $source_post_id ) );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $source_post_id, $taxonomy, [ 'fields' => 'ids' ] );

            // Switch back to target
            switch_to_blog( $current_blog );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                // Assign same term IDs (terms are shared via MU-plugin)
                wp_set_post_terms( $target_post_id, $terms, $taxonomy );
            }

            switch_to_blog( $source_blog_id );
        }

        switch_to_blog( $current_blog );
    }
}
```

#### Step 2.6: Admin UI Class

**File:** `/includes/class-admin-ui.php`

```php
<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_UI {

    private $sync_manager;

    /**
     * Supported post types for syncing
     */
    private $supported_post_types = [
        'referanser',
        'produkter',
        'bruksomrader',
        'godegrunner',
        'levetidskostnader',
        'baerekreaftig'
    ];

    public function __construct() {
        $this->sync_manager = new Sync_Manager();

        // Add metabox to supported post types
        add_action( 'add_meta_boxes', [ $this, 'add_sync_metabox' ] );

        // Handle sync AJAX request
        add_action( 'wp_ajax_acrylicon_sync_post', [ $this, 'handle_sync_ajax' ] );

        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Add admin notice after sync
        add_action( 'admin_notices', [ $this, 'show_sync_notices' ] );
    }

    /**
     * Add sync metabox to post editor
     */
    public function add_sync_metabox() {
        foreach ( $this->supported_post_types as $post_type ) {
            add_meta_box(
                'acrylicon_sync_metabox',
                'Multisite Synkronisering',
                [ $this, 'render_sync_metabox' ],
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Render metabox content
     */
    public function render_sync_metabox( $post ) {
        // Get available target sites
        $sites = get_sites( [ 'number' => 100 ] );
        $current_blog_id = get_current_blog_id();

        // Check if already synced
        $synced_sites = [];
        foreach ( $sites as $site ) {
            if ( $site->blog_id == $current_blog_id ) {
                continue;
            }

            if ( $this->sync_manager->is_synced( $post->ID, $site->blog_id ) ) {
                $synced_sites[] = $site;
            }
        }

        // Nonce for security
        wp_nonce_field( 'acrylicon_sync_post', 'acrylicon_sync_nonce' );

        ?>
        <div class="acrylicon-sync-metabox">
            <?php if ( ! empty( $synced_sites ) ) : ?>
                <div class="sync-status synced">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong>Synkronisert til:</strong>
                    <ul>
                        <?php foreach ( $synced_sites as $site ) : ?>
                            <?php
                            $synced_post_id = get_post_meta( $post->ID, '_synced_to_post_' . $site->blog_id, true );
                            $synced_date = get_post_meta( $post->ID, '_synced_date_' . $site->blog_id, true );
                            ?>
                            <li>
                                <?php echo esc_html( get_blog_details( $site->blog_id )->blogname ); ?>
                                <br>
                                <small>
                                    Post ID: <?php echo esc_html( $synced_post_id ); ?> |
                                    Dato: <?php echo esc_html( date( 'Y-m-d H:i', strtotime( $synced_date ) ) ); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else : ?>
                <div class="sync-status not-synced">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <p>Ikke synkronisert til andre sites ennå.</p>
                </div>
            <?php endif; ?>

            <div class="sync-actions">
                <label for="target_blog_id"><strong>Synkroniser til:</strong></label>
                <select id="target_blog_id" name="target_blog_id">
                    <option value="">-- Velg site --</option>
                    <?php foreach ( $sites as $site ) : ?>
                        <?php if ( $site->blog_id == $current_blog_id ) continue; ?>
                        <?php
                        $is_synced = $this->sync_manager->is_synced( $post->ID, $site->blog_id );
                        ?>
                        <option
                            value="<?php echo esc_attr( $site->blog_id ); ?>"
                            <?php disabled( $is_synced ); ?>
                        >
                            <?php echo esc_html( get_blog_details( $site->blog_id )->blogname ); ?>
                            <?php echo $is_synced ? '(Allerede synkronisert)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button
                    type="button"
                    id="acrylicon-sync-button"
                    class="button button-primary"
                    data-post-id="<?php echo esc_attr( $post->ID ); ?>"
                >
                    <span class="dashicons dashicons-update"></span>
                    Synkroniser nå
                </button>

                <div id="sync-spinner" class="spinner" style="display:none;"></div>
                <div id="sync-result" style="margin-top: 10px;"></div>
            </div>

            <div class="sync-info">
                <p><strong>⚠️ Viktig:</strong></p>
                <ul style="margin-left: 20px; font-size: 12px;">
                    <li>Post opprettes som <strong>utkast</strong> på target site</li>
                    <li>Kan kun synkroniseres <strong>én gang</strong> per site</li>
                    <li>ACF relationship fields blir <strong>tomme</strong></li>
                    <li>Media kopieres fysisk til target site</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX sync request
     */
    public function handle_sync_ajax() {
        // Security checks
        check_ajax_referer( 'acrylicon_sync_post', 'nonce' );

        if ( ! current_user_can( 'manage_network' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
        }

        $post_id = intval( $_POST['post_id'] );
        $target_blog_id = intval( $_POST['target_blog_id'] );

        if ( ! $post_id || ! $target_blog_id ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters' ] );
        }

        // Perform sync
        $result = $this->sync_manager->sync_post( $post_id, $target_blog_id );

        if ( $result['success'] ) {
            wp_send_json_success( [
                'message' => 'Synkronisering fullført!',
                'post_id' => $result['post_id'],
                'errors' => $result['errors']
            ] );
        } else {
            wp_send_json_error( [
                'message' => 'Synkronisering feilet: ' . $result['error']
            ] );
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! in_array( $screen->post_type, $this->supported_post_types ) ) {
            return;
        }

        wp_enqueue_style(
            'acrylicon-sync-admin',
            ACRYLICON_SYNC_URL . '/assets/css/admin-style.css',
            [],
            ACRYLICON_SYNC_VERSION
        );

        wp_enqueue_script(
            'acrylicon-sync-admin',
            ACRYLICON_SYNC_URL . '/assets/js/admin-script.js',
            [ 'jquery' ],
            ACRYLICON_SYNC_VERSION,
            true
        );

        wp_localize_script( 'acrylicon-sync-admin', 'acrylicon_sync', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'acrylicon_sync_post' )
        ] );
    }

    /**
     * Show admin notices after sync
     */
    public function show_sync_notices() {
        // Placeholder for future implementation
    }
}
```

#### Step 2.7: Admin Assets

**File:** `/assets/css/admin-style.css`

```css
.acrylicon-sync-metabox {
    padding: 10px 0;
}

.sync-status {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sync-status.synced {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.sync-status.not-synced {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.sync-status .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.sync-actions {
    margin-bottom: 15px;
}

.sync-actions label {
    display: block;
    margin-bottom: 5px;
}

.sync-actions select {
    width: 100%;
    margin-bottom: 10px;
}

#acrylicon-sync-button {
    width: 100%;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

#sync-result {
    padding: 10px;
    border-radius: 4px;
}

#sync-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#sync-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.sync-info {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.sync-info ul {
    margin: 5px 0;
}
```

**File:** `/assets/js/admin-script.js`

```javascript
jQuery(document).ready(function($) {
    $('#acrylicon-sync-button').on('click', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var targetBlogId = $('#target_blog_id').val();
        var spinner = $('#sync-spinner');
        var resultDiv = $('#sync-result');

        // Validation
        if (!targetBlogId) {
            alert('Vennligst velg en target site');
            return;
        }

        // Disable button and show spinner
        button.prop('disabled', true);
        spinner.show();
        resultDiv.html('').removeClass('success error');

        // AJAX request
        $.ajax({
            url: acrylicon_sync.ajax_url,
            type: 'POST',
            data: {
                action: 'acrylicon_sync_post',
                nonce: acrylicon_sync.nonce,
                post_id: postId,
                target_blog_id: targetBlogId
            },
            success: function(response) {
                spinner.hide();
                button.prop('disabled', false);

                if (response.success) {
                    resultDiv.addClass('success').html(
                        '<strong>✓ ' + response.data.message + '</strong><br>' +
                        'Ny post ID: ' + response.data.post_id
                    );

                    if (response.data.errors && response.data.errors.length > 0) {
                        resultDiv.append('<br><br><strong>Advarsler:</strong><ul>');
                        response.data.errors.forEach(function(error) {
                            resultDiv.append('<li>' + error + '</li>');
                        });
                        resultDiv.append('</ul>');
                    }

                    // Reload page after 2 seconds to show updated status
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.addClass('error').html(
                        '<strong>✗ ' + response.data.message + '</strong>'
                    );
                }
            },
            error: function(xhr, status, error) {
                spinner.hide();
                button.prop('disabled', false);
                resultDiv.addClass('error').html(
                    '<strong>✗ AJAX feil:</strong> ' + error
                );
            }
        });
    });
});
```

---

### Phase 3: Testing Strategy

#### Step 3.1: Unit Testing

**Create test file:** `/tests/test-sync-manager.php`

```php
<?php
/**
 * Manual unit tests for Sync Manager
 * Run from WordPress admin or via WP-CLI
 */

// Test 1: Draft post creation
function test_draft_post_creation() {
    $sync_manager = new \Acrylicon_Multisite_Sync\Sync_Manager();

    // Create test post on site 1
    $test_post = wp_insert_post([
        'post_title' => 'Test Sync Post ' . time(),
        'post_content' => 'This is a test.',
        'post_type' => 'referanser',
        'post_status' => 'publish'
    ]);

    // Try to sync to site 2
    $result = $sync_manager->sync_post($test_post, 2);

    if ($result['success']) {
        echo "✓ Draft post creation: PASS\n";
        echo "  New post ID: " . $result['post_id'] . "\n";
    } else {
        echo "✗ Draft post creation: FAIL\n";
        echo "  Error: " . $result['error'] . "\n";
    }

    // Cleanup
    wp_delete_post($test_post, true);

    if ($result['success']) {
        switch_to_blog(2);
        wp_delete_post($result['post_id'], true);
        restore_current_blog();
    }
}

// Test 2: Media copying
function test_media_copying() {
    $media_handler = new \Acrylicon_Multisite_Sync\Media_Handler();

    // Upload test image
    $test_image = '/path/to/test/image.jpg';
    $attachment_id = media_sideload_image($test_image, 0, 'Test image', 'id');

    if (is_wp_error($attachment_id)) {
        echo "✗ Media test setup failed\n";
        return;
    }

    // Copy to site 2
    $new_attachment_id = $media_handler->copy_media($attachment_id, 2, 1);

    if ($new_attachment_id) {
        echo "✓ Media copying: PASS\n";
        echo "  New attachment ID: " . $new_attachment_id . "\n";
    } else {
        echo "✗ Media copying: FAIL\n";
    }

    // Cleanup
    wp_delete_attachment($attachment_id, true);
    switch_to_blog(2);
    wp_delete_attachment($new_attachment_id, true);
    restore_current_blog();
}

// Run tests
add_action('admin_init', function() {
    if (isset($_GET['run_sync_tests'])) {
        echo "<pre>";
        test_draft_post_creation();
        echo "\n";
        test_media_copying();
        echo "</pre>";
        exit;
    }
});
```

**Run tests:**
```
Visit: /wp-admin/?run_sync_tests=1
```

#### Step 3.2: Integration Testing

**Test Scenarios:**

1. **Happy Path Test**
   - Create referanse on English site with:
     - Title, content, excerpt
     - Featured image (2MB)
     - 5 ACF fields
     - 2 taxonomy terms
   - Click sync button
   - Verify draft post created on Norwegian site
   - Verify all fields copied correctly
   - Verify taxonomies assigned
   - Verify featured image copied

2. **Large Media Test**
   - Upload 10MB image
   - Sync post
   - Monitor memory usage
   - Verify no timeout

3. **Missing ACF Field Test**
   - Remove one ACF field from target site
   - Sync post
   - Verify sync continues
   - Check debug.log for warning

4. **Slug Conflict Test**
   - Create post with slug "test-product"
   - Manually create post on target site with same slug
   - Sync original post
   - Verify WordPress adds suffix (-2)

5. **Failure Recovery Test**
   - Start sync
   - Kill process mid-sync (simulate crash)
   - Verify no orphan posts/media on target
   - Check cleanup worked

#### Step 3.3: Edge Case Testing

**Test Matrix:**

| Test Case | Input | Expected Output | Status |
|-----------|-------|-----------------|--------|
| Empty content | Post with no content | Syncs with empty content | [ ] |
| No featured image | Post without thumbnail | Syncs without error | [ ] |
| No ACF fields | Post type with no fields | Syncs core content only | [ ] |
| Relationship field | ACF relationship set | Field empty on target | [ ] |
| Repeater field (10 rows) | Large repeater | All rows copied | [ ] |
| Concurrent sync | 2 users sync simultaneously | Both succeed | [ ] |
| Network failure | Disconnect mid-sync | Rollback triggered | [ ] |
| Permission check | Editor tries to sync | Access denied | [ ] |

---

### Phase 4: Deployment

#### Step 4.1: Pre-Deployment Checklist

**Staging Environment:**
- [ ] Full database backup created
- [ ] Shared taxonomy MU-plugin deployed and tested
- [ ] ACF JSON folder exists and is writable
- [ ] All PHP limits verified (256M memory, 300s timeout)
- [ ] Database tables use InnoDB engine
- [ ] Test sync completed successfully

#### Step 4.2: Deployment Steps

**Step 1: Deploy MU-Plugin (Do First!)**
```bash
# Copy MU-plugin file
cp /path/to/acrylicon-shared-taxonomies.php /wp-content/mu-plugins/

# Verify it loads
tail -f /wp-content/debug.log
# Should see: "Site X using terms table: wp_terms"
```

**Step 2: Test Taxonomy Sharing**
```
1. Login to WordPress admin on Site 1
2. Create new category: "Test Category"
3. Switch to Site 2 admin
4. Verify "Test Category" appears in category list
5. Delete "Test Category" from Site 1
6. Verify it's gone from Site 2
```

**Step 3: Deploy Sync Plugin**
```bash
# Upload plugin
cp -r acrylicon-multisite-sync/ /wp-content/plugins/

# Activate on Site 1 (English)
wp plugin activate acrylicon-multisite-sync --url=example.com/english
```

**Step 4: Smoke Test**
```
1. Create test post on English site
2. Add featured image, ACF fields, taxonomies
3. Click "Synkroniser nå"
4. Select Norwegian site
5. Verify draft post created
6. Check all fields copied
7. Delete test posts
```

#### Step 4.3: Monitoring

**Watch for errors:**
```bash
# Monitor debug.log during first syncs
tail -f /wp-content/debug.log | grep -i "acrylicon"
```

**Key metrics to track:**
- Sync success rate (should be 100%)
- Average sync time per post type
- Memory usage peaks
- Failed syncs (should be zero)

#### Step 4.4: Rollback Plan

**If something goes wrong:**

```bash
# 1. Deactivate sync plugin immediately
wp plugin deactivate acrylicon-multisite-sync

# 2. Remove MU-plugin (restores separate taxonomy tables)
rm /wp-content/mu-plugins/acrylicon-shared-taxonomies.php

# 3. Restore database from backup
wp db import backup-YYYYMMDD-HHMMSS.sql

# 4. Clear all caches
wp cache flush
```

---

## Acceptance Criteria

### Must Have (MVP)

- [x] **MU-Plugin deployed:** Shared taxonomies work across all sites
- [x] **Sync button visible:** Administrators see metabox on supported post types
- [x] **Draft creation:** Synced posts created as draft (not published)
- [x] **Content copied:** Title, content, excerpt copied correctly
- [x] **Media copied:** Featured images copied physically to target site
- [x] **ACF fields synced:** All ACF fields copied with graceful error handling
- [x] **Taxonomies assigned:** Terms assigned to synced post (shared via MU-plugin)
- [x] **Status tracking:** Post meta shows sync history (date, user, target site)
- [x] **One-time sync:** Button disabled after sync (no re-sync)
- [x] **Error recovery:** Failed syncs cleanup orphaned data automatically
- [x] **Admin notice:** Success/error messages shown after sync
- [x] **Permission check:** Only administrators can sync
- [x] **Logging:** All operations logged to debug.log

### Should Have

- [ ] **Performance:** Sync completes in <30 seconds for typical post
- [ ] **Memory safe:** Large images (10MB+) sync without memory errors
- [ ] **Missing fields:** Gracefully skip ACF fields that don't exist on target
- [ ] **Slug handling:** WordPress automatic suffix works correctly
- [ ] **Multiple post types:** All 6 post types sync correctly
- [ ] **Current date:** Synced posts use current date (not original)

### Nice to Have

- [ ] **Bulk sync:** Select multiple posts for batch syncing
- [ ] **Email notification:** Notify Norwegian editor when content synced
- [ ] **Sync preview:** Show what will be synced before clicking button
- [ ] **Retry failed:** Manual retry button for failed syncs
- [ ] **Dashboard widget:** Sync statistics and recent activity
- [ ] **Multi-language support:** Plugin strings translated to Norwegian

---

## Technical Details

### Database Schema

**Post Meta Keys (Source Post):**
```
_synced_to_post_{blog_id}   # Target post ID
_synced_to_blog_{blog_id}   # Target blog ID
_synced_date_{blog_id}      # Sync timestamp
```

**Post Meta Keys (Target Post):**
```
_synced_from_post           # Source post ID
_synced_from_blog           # Source blog ID
_synced_date                # Sync timestamp
_synced_by_user             # User ID who triggered sync
_sync_errors                # Array of error messages (if any)
```

### File Structure

```
/wp-content/
├── mu-plugins/
│   └── acrylicon-shared-taxonomies.php     # 23 lines
└── plugins/
    └── acrylicon-multisite-sync/
        ├── acrylicon-multisite-sync.php    # 50 lines
        ├── includes/
        │   ├── class-sync-manager.php       # ~200 lines
        │   ├── class-media-handler.php      # ~80 lines
        │   ├── class-acf-handler.php        # ~70 lines
        │   ├── class-taxonomy-handler.php   # ~40 lines
        │   └── class-admin-ui.php           # ~250 lines
        ├── assets/
        │   ├── css/
        │   │   └── admin-style.css          # ~80 lines
        │   └── js/
        │       └── admin-script.js          # ~60 lines
        └── tests/
            └── test-sync-manager.php        # ~100 lines
```

**Total estimated lines of code:** ~953 lines

### Dependencies

**Required:**
- WordPress 6.8+
- PHP 8.0+
- ACF Pro (installed)
- WordPress Multisite (active)

**Optional:**
- WP-CLI (for testing/deployment)

### Performance Considerations

**Sync Time Estimates:**
- Simple post (no media): 1-2 seconds
- Post with featured image (2MB): 3-5 seconds
- Post with 10 ACF fields: 2-3 seconds
- Post with 5 images in ACF: 10-15 seconds

**Memory Usage:**
- Baseline: 50MB
- With 2MB image: 80MB
- With 10MB image: 150MB
- Peak with multiple large images: 200MB+

**Bottlenecks:**
- `wp_generate_attachment_metadata()` (thumbnail generation)
- Large image file copying
- Multiple ACF relationship queries

**Optimizations:**
- Pre-set high memory limits before sync
- Skip metadata generation for speed (generate on-demand later)
- Use direct file copy instead of HTTP download

---

## Risk Analysis & Mitigation

### High Risk: Data Loss from Shared Taxonomies

**Risk:** Implementing shared taxonomies will DELETE existing terms on subsites.

**Mitigation:**
1. Create full database backup before deploying MU-plugin
2. Export subsite terms to main site first (if content exists)
3. Test thoroughly in staging environment
4. Deploy MU-plugin during off-hours with team on standby

### Medium Risk: Memory Exhaustion with Large Media

**Risk:** Large images (10MB+) could exhaust PHP memory during sync.

**Mitigation:**
1. Pre-set memory limits to 256M in sync code
2. Monitor memory usage during first production syncs
3. Add file size warnings in admin UI
4. Implement chunked file copying for very large files (future enhancement)

### Medium Risk: Partial Sync Failures

**Risk:** Network/server issues could cause mid-sync failures.

**Mitigation:**
1. Draft-first pattern ensures no published content is affected
2. Try/catch blocks wrap all operations
3. Automatic cleanup deletes orphaned posts/media
4. Detailed error logging for debugging

### Low Risk: ACF Field Mismatches

**Risk:** Target site might not have all ACF fields from source.

**Mitigation:**
1. Graceful error handling skips missing fields
2. Warnings logged to debug.log
3. Sync continues despite errors
4. ACF JSON sync ensures field groups are identical (if implemented)

### Low Risk: Concurrent Sync Attempts

**Risk:** Two users might try to sync same post simultaneously.

**Mitigation:**
1. Check if already synced before starting
2. Disable button after sync
3. Post meta lock prevents double-sync
4. AJAX operations are atomic

---

## Future Considerations

### Phase 2 Features (Post-MVP)

1. **Bulk Sync**
   - Select multiple posts from list view
   - Batch sync with progress bar
   - Queue system for large batches

2. **Bi-directional Sync**
   - Update Norwegian post updates English version
   - Merge conflict resolution
   - Change tracking

3. **Translation Workflow**
   - Mark fields as "needs translation"
   - Translation progress tracking
   - Integration with translation services

4. **Multi-target Support**
   - Sync to Swedish, Danish sites
   - Language-specific content variations
   - Region-specific customizations

5. **Advanced Media Handling**
   - Image optimization during sync
   - Lazy loading for large media
   - CDN integration

### Extensibility

**Hooks for developers:**
```php
// Before sync starts
do_action('acrylicon_before_sync', $source_post_id, $target_blog_id);

// After sync completes
do_action('acrylicon_after_sync', $source_post_id, $target_post_id, $target_blog_id);

// Filter target post data before insert
apply_filters('acrylicon_target_post_data', $post_data, $source_post_id);

// Filter ACF fields to sync
apply_filters('acrylicon_acf_fields_to_sync', $fields, $post_type);
```

---

## Documentation Requirements

### User Documentation

1. **Admin Guide** (`docs/admin-guide.md`)
   - How to sync a post
   - Understanding sync status
   - Troubleshooting common issues
   - Best practices

2. **Editor Guide** (`docs/editor-guide.md`)
   - When to sync content
   - What gets synced vs. what doesn't
   - Manual translation workflow
   - FAQ

### Developer Documentation

1. **Technical Documentation** (`docs/technical-docs.md`)
   - Architecture overview
   - Class descriptions
   - Database schema
   - Hooks reference

2. **Contribution Guide** (`docs/contributing.md`)
   - Code standards
   - Testing procedures
   - Pull request process
   - Debugging tips

---

## References & Resources

### Internal References

- **Brainstorm Document:** `docs/brainstorms/2026-01-26-multisite-content-sync-brainstorm.md`
- **Theme Functions:** `/wp-content/themes/acrylicon-2024/functions.php:121-256` (CPT registration)
- **ACF Block Example:** `/wp-content/themes/acrylicon-2024/blocks/info-card/template.php`
- **Plugin Example:** `/wp-content/plugins/flexible-spacer-block/` (structure reference)
- **NS Cloner Plugin:** `/wp-content/plugins/ns-cloner-site-copier/` (multisite patterns)

### External References

- [BuddyDev: Global Taxonomies Guide](https://buddydev.com/want-global-categories-tags-taxonomies-across-wordpress-multisite-network/)
- [Rudrastyh: Copy Media Between Sites](https://rudrastyh.com/wordpress-multisite/copy-media-files-from-one-site-to-another.html)
- [WordPress Multisite Handbook](https://developer.wordpress.org/advanced-administration/multisite/)
- [ACF Documentation](https://www.advancedcustomfields.com/resources/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

### Related Work

- **Existing Plugins:**
  - NS Cloner Site Copier (full site cloning)
  - ACF Pro (field syncing via JSON)
- **Similar Solutions:**
  - Multisite Global Terms plugin (deprecated)
  - Distributor plugin by 10up (content syndication)

---

## Success Metrics

### Immediate (Week 1)

- Plugin activated without errors
- First successful sync completed
- No data loss incidents
- Zero failed syncs

### Short-term (Month 1)

- 50+ posts synced successfully
- <5% sync warnings (missing fields)
- Average sync time <10 seconds
- Zero manual cleanup required

### Long-term (Quarter 1)

- 200+ posts synced
- Editorial workflow established
- Norwegian translation backlog cleared
- Feature requests prioritized for Phase 2

---

## Project Timeline Estimate

**Phase 0 (Pre-Implementation):** 1 day
- Database verification
- ACF JSON folder creation
- PHP limits check
- Backup procedures

**Phase 1 (Shared Infrastructure):** 1 day
- MU-plugin creation
- Taxonomy sharing testing
- ACF JSON verification

**Phase 2 (Plugin Development):** 5 days
- Scaffolding (0.5 day)
- Sync Manager (1 day)
- Media Handler (1 day)
- ACF Handler (1 day)
- Taxonomy Handler (0.5 day)
- Admin UI (1 day)

**Phase 3 (Testing):** 2 days
- Unit tests (0.5 day)
- Integration tests (1 day)
- Edge case tests (0.5 day)

**Phase 4 (Deployment):** 1 day
- Staging deployment
- Smoke testing
- Production deployment
- Monitoring setup

**Total Estimate:** 10 days (2 work weeks)

---

**Status:** Ready for Implementation
**Next Step:** Begin Phase 0 pre-implementation checklist
