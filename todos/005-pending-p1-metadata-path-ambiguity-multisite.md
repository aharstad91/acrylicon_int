---
status: pending
priority: p1
issue_id: "005"
tags: [code-review, architecture, phase3, r2, multisite]
dependencies: ["001"]
---

# Metadata Path Ambiguity in Multisite R2 Sync

## Problem Statement

WordPress `_wp_attached_file` metadata is **site-relative**, creating fundamental ambiguity when Site 3 references Site 2's R2 media:

- Site 2: `_wp_attached_file = "2022/01/image.jpg"` means `/uploads/sites/2/2022/01/image.jpg`
- Site 3: `_wp_attached_file = "2022/01/image.jpg"` means `/uploads/sites/3/2022/01/image.jpg`

When Site 3 copies this metadata from Site 2, WordPress will construct the **wrong path** because it assumes the file is in Site 3's upload directory.

**Why it matters:** This breaks thumbnail generation, responsive images, image editing, and ACF image fields on synced sites.

## Findings

**Source:** Architecture-strategist agent

**Root cause:** The plan attempts to retrofit a **reference-based architecture** onto WordPress core designed for **location-based architecture**.

**Code from plan (lines 519-523):**
```php
// Problem: Copies metadata as-is
update_post_meta( $target_id, '_wp_attached_file', $attachment_meta['file'] );
update_post_meta( $target_id, '_wp_attachment_metadata', $attachment_meta );
```

**What happens:**
1. Source (Site 2) has: `_wp_attached_file = "2022/01/image.jpg"`
2. Sync copies this to target (Site 3)
3. Site 3 now has: `_wp_attached_file = "2022/01/image.jpg"`
4. WordPress on Site 3 tries to find: `/uploads/sites/3/2022/01/image.jpg` ❌ (doesn't exist!)
5. Actual R2 file is at: `https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg`

**Impact:**
- Thumbnail generation fails (WordPress can't find source file)
- Responsive srcset broken (wrong URLs generated)
- Image metadata extraction fails
- WordPress image editor can't open file
- ACF image fields show broken images

## Proposed Solutions

### Option 1: Add Source Blog Metadata (Recommended)

Store the source blog ID so URL rewriting layer (todo #001) knows which site's R2 path to use:

```php
// In sync_media_reference() when creating attachment
$attach_id = wp_insert_post( [...] );

// Standard sync metadata
update_post_meta( $attach_id, '_synced_from_attachment', $attachment_id );
update_post_meta( $attach_id, '_synced_from_blog', $source_blog_id );

// NEW: Store source blog for R2 path resolution
update_post_meta( $attach_id, '_r2_source_blog', $source_blog_id );
update_post_meta( $attach_id, '_r2_reference', true );  // Flag as reference (not stored locally)

// Copy metadata (will be interpreted relative to source blog)
update_post_meta( $attach_id, '_wp_attached_file', $attachment_meta['file'] );
update_post_meta( $attach_id, '_wp_attachment_metadata', $attachment_meta );
```

Then in URL rewrite filter (from todo #001):

```php
add_filter('wp_get_attachment_url', function($url, $attachment_id) {
    $r2_source_blog = get_post_meta($attachment_id, '_r2_source_blog', true);

    if ($r2_source_blog) {
        // Get file path
        $file = get_post_meta($attachment_id, '_wp_attached_file', true);

        // Construct R2 URL using SOURCE blog's path structure
        switch_to_blog($r2_source_blog);
        $uploads = wp_upload_dir();
        restore_current_blog();

        // Build URL: https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg
        $r2_url = 'https://media.acrylicon.no' . str_replace(
            WP_CONTENT_DIR,
            '',
            $uploads['basedir']
        ) . '/' . $file;

        return $r2_url;
    }

    return $url;
}, 10, 2);
```

- **Pros:** Maintains WordPress metadata structure, clear source tracking, works with existing plugins
- **Cons:** Requires URL rewrite layer (todo #001)
- **Effort:** Small (add 2 meta keys)
- **Risk:** Low (metadata-only change)

### Option 2: Store Full R2 Paths

Store complete R2 URLs in metadata instead of relative paths:

```php
// In sync_media_reference()
$r2_url = 'https://media.acrylicon.no/uploads/sites/2/2022/01/image.jpg';

update_post_meta( $attach_id, '_wp_attached_file', $r2_url );  // Full URL instead of relative
wp_update_post([
    'ID' => $attach_id,
    'guid' => $r2_url
]);
```

- **Pros:** No filter overhead, explicit URL
- **Cons:** Breaks WordPress conventions (attached_file should be relative), harder to migrate storage providers
- **Effort:** Small
- **Risk:** Medium (violates WordPress standards)

### Option 3: Site-Specific R2 Path Prefixes

Modify metadata to include site-specific prefix:

```php
// Instead of: "2022/01/image.jpg"
// Store: "sites/2/2022/01/image.jpg"
$prefixed_path = 'sites/' . $source_blog_id . '/' . $attachment_meta['file'];
update_post_meta( $attach_id, '_wp_attached_file', $prefixed_path );
```

- **Pros:** Maintains relative paths, clear source indication
- **Cons:** Non-standard path format, may confuse plugins
- **Effort:** Small
- **Risk:** Medium (compatibility with WordPress plugins unknown)

## Recommended Action

**Implement Option 1** (source blog metadata) - works seamlessly with URL rewrite layer from todo #001.

**Dependencies:**
- Must implement todo #001 (URL Rewrite Layer) first or simultaneously
- This todo provides the metadata, todo #001 uses it

**Implementation order:**
1. Add `_r2_source_blog` and `_r2_reference` metadata to sync
2. Implement URL rewrite filter using this metadata
3. Test with 5-10 sample images
4. Validate thumbnails, srcset, and ACF fields work

## Technical Details

**Affected Files:**
- `/plugins/acrylicon-multisite-sync/includes/class-media-handler.php` (add metadata in sync_media_reference)
- `/mu-plugins/acrylicon-r2-url-rewriter.php` (use metadata in filters - from todo #001)

**Components:**
- Multisite sync plugin
- WordPress attachment metadata
- URL generation filters
- R2 path construction

**New Metadata Keys:**
- `_r2_source_blog` (int): Blog ID where file is actually stored on R2
- `_r2_reference` (bool): Flag indicating this is a reference, not local storage

**Database Impact:**
- 2 meta keys per synced attachment
- ~15,000 attachments × 2 meta × ~50 bytes = ~1.5 MB

## Acceptance Criteria

- [ ] `_r2_source_blog` metadata stored on synced attachments
- [ ] `_r2_reference` flag stored on synced attachments
- [ ] URL rewrite filter correctly interprets source blog ID
- [ ] Thumbnail URLs use correct R2 path (sites/2/ not sites/3/)
- [ ] Responsive srcset generates correct R2 URLs
- [ ] Featured images display correctly
- [ ] ACF image fields work with synced media
- [ ] WordPress image editor disabled for R2 references (can't edit)
- [ ] No PHP warnings about missing files
- [ ] Site 3 displays images from Site 2's R2 path

## Work Log

### 2026-01-26
- Identified metadata path ambiguity during architecture review
- Validated that _wp_attached_file is site-relative
- Recommended storing source blog ID for path resolution

## Resources

- Plan section: Lines 519-523 (metadata copy without path correction)
- Depends on: Todo #001 (URL Rewrite Layer)
- [WordPress get_attached_file() source](https://developer.wordpress.org/reference/functions/get_attached_file/)
- [WordPress upload directory structure](https://developer.wordpress.org/reference/functions/wp_upload_dir/)
- [Multisite upload paths](https://wordpress.stackexchange.com/questions/147800/multisite-upload-path)
