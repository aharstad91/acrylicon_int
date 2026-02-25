---
status: pending
priority: p1
issue_id: "001"
tags: [code-review, architecture, phase3, r2, multisite]
dependencies: []
---

# Missing URL Rewrite Layer for R2 Reference-Based Sync

## Problem Statement

The plan proposes reference-based media sync for Phase 3, where Site 3 references Site 2's R2 media without physical file duplication. However, **the architectural layer needed to make WordPress generate correct URLs is completely missing from the plan**.

**Why it matters:** Without this layer, Phase 3 will fail - synced images won't display on target sites because WordPress will construct incorrect file paths.

## Findings

**Source:** Architecture-strategist agent

**Evidence from plan analysis:**

WordPress core functions assume `_wp_attached_file` is relative to the current blog's upload directory:

```php
// wp-includes/post.php - get_attached_file()
function get_attached_file( $attachment_id, $unfiltered = false ) {
    $file = get_post_meta( $attachment_id, '_wp_attached_file', true );

    // Prepends upload base URL - assumes file is in current blog's uploads!
    $uploads = wp_upload_dir();
    if ( 0 !== strpos( $file, $uploads['basedir'] ) ) {
        $file = $uploads['basedir'] . '/' . $file;  // ← BUG: Wrong path
    }

    return $file;
}
```

**Problem scenario:**
- Source (Site 2): `_wp_attached_file = "2022/01/image.jpg"` → `/uploads/sites/2/2022/01/image.jpg`
- Target (Site 3): `_wp_attached_file = "2022/01/image.jpg"` → `/uploads/sites/3/2022/01/image.jpg` ❌ (file doesn't exist!)
- Actual R2 file: `https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg`

**Impact:**
- Image thumbnails won't generate correctly
- `wp_get_attachment_image_src()` returns broken URLs
- ACF image fields fail to display
- WordPress image editor fails
- Responsive srcset generation breaks

**Location:** Plan lines 456-545 propose sync modification but don't include URL rewriting

## Proposed Solutions

### Option 1: Add WordPress Filter Layer (Recommended)

Implement missing filters in multisite sync plugin or mu-plugin:

```php
/**
 * Override URL generation for synced attachments
 */
add_filter('wp_get_attachment_url', function($url, $attachment_id) {
    // Check if this is a synced attachment
    $synced_from = get_post_meta($attachment_id, '_synced_from_attachment', true);

    if ($synced_from) {
        // Get source blog and attachment
        $source_blog_id = get_post_meta($attachment_id, '_synced_from_blog', true);
        switch_to_blog($source_blog_id);
        $source_url = wp_get_attachment_url($synced_from);
        restore_current_blog();

        return $source_url;  // Return R2 URL from source site
    }

    return $url;
}, 10, 2);

/**
 * Override metadata to prevent thumbnail regeneration
 */
add_filter('wp_get_attachment_metadata', function($metadata, $attachment_id) {
    $synced_from = get_post_meta($attachment_id, '_synced_from_attachment', true);

    if ($synced_from) {
        // Return source site's metadata
        $source_blog_id = get_post_meta($attachment_id, '_synced_from_blog', true);
        switch_to_blog($source_blog_id);
        $source_metadata = wp_get_attachment_metadata($synced_from);
        restore_current_blog();

        return $source_metadata;
    }

    return $metadata;
}, 10, 2);
```

- **Pros:** Works with existing WordPress architecture, transparent to themes/plugins
- **Cons:** Adds filter overhead on every image request
- **Effort:** Medium (2-3 days development + testing)
- **Risk:** Low (can test extensively, rollback is clean)

### Option 2: Store Full R2 URLs in Database

Modify sync to store complete R2 URLs in `guid` and attachment metadata:

```php
// In sync_media_reference()
wp_update_post([
    'ID' => $attach_id,
    'guid' => 'https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg'  // Full URL
]);

update_post_meta($attach_id, '_wp_attached_file', 'https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg');
```

- **Pros:** No filter overhead, simple
- **Cons:** Breaks WordPress conventions (attached_file should be relative), harder to migrate storage later
- **Effort:** Small (modify sync code only)
- **Risk:** Medium (violates WordPress standards, may break plugins)

### Option 3: Use Network Media Library Plugin

Replace custom sync entirely with battle-tested plugin:

- Install Network Media Library plugin
- Configure Site 2 as central media library
- All sites reference media transparently
- R2 offload handles URLs natively

- **Pros:** Zero custom code, proven solution, maintains standards
- **Cons:** Requires architectural change, may not fit all workflows
- **Effort:** Medium (migration from custom sync)
- **Risk:** Low (well-tested plugin)

## Recommended Action

**Primary:** Implement Option 1 (filter layer) - maintains flexibility and WordPress standards

**Alternative:** Evaluate Option 3 (Network Media Library) as simpler long-term architecture

## Technical Details

**Affected Files:**
- `/plugins/acrylicon-multisite-sync/includes/class-media-handler.php` (add filters)
- OR create `/mu-plugins/acrylicon-r2-url-rewriter.php` (cleaner separation)

**Components:**
- WordPress attachment URL generation
- Image metadata retrieval
- Multisite sync plugin
- Theme image rendering
- ACF image fields

**Database Changes:**
- No schema changes required
- Existing `_synced_from_attachment` and `_synced_from_blog` meta sufficient

## Acceptance Criteria

- [ ] Synced images display correctly on target site
- [ ] Featured images work in posts
- [ ] ACF image fields display synced media
- [ ] Responsive srcset generates correct R2 URLs
- [ ] WordPress media library shows thumbnails
- [ ] Image editor doesn't attempt to edit synced images
- [ ] No duplicate file uploads to R2
- [ ] Page load time not significantly impacted by filters
- [ ] Works across all thumbnail sizes

## Work Log

### 2026-01-26
- Identified critical gap during architecture review
- Validated that plan's sync_media_reference() creates broken references without URL rewriting

## Resources

- Plan section: Lines 456-545 (proposed sync modification)
- WordPress Codex: [get_attached_file()](https://developer.wordpress.org/reference/functions/get_attached_file/)
- WordPress Codex: [wp_get_attachment_url](https://developer.wordpress.org/reference/functions/wp_get_attachment_url/)
- Similar pattern: [Network Media Library plugin source](https://github.com/humanmade/network-media-library)
