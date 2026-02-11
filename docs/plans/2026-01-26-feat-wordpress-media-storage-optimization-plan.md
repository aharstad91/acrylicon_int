---
title: WordPress Multisite Media Storage Optimization
type: feat
date: 2026-01-26
status: ready
priority: critical
---

# WordPress Multisite Media Storage Optimization

## Overview

Production WordPress multisite is at critical storage capacity (10.47 GB / 10 GB = 104.7% full) on Servebolt hosting. The recently implemented multisite sync plugin physically duplicates all media files across sites, accelerating storage consumption. This plan implements aggressive media optimization to reduce storage by 60-70% (~6-9 GB) through compression, deduplication, and cleanup strategies.

## Problem Statement

**Critical Storage Situation:**
- **Current usage:** 10.47 GB / 10 GB (over capacity)
- **Servebolt limit:** 10 GB on Partner Free plan
- **Site 3 (English):** 5.1 GB
- **Main site (Norwegian):** ~4.9 GB
- **Total image files:** 15,037 files
- **Largest directory:** /2022/ with 3.7 GB (37% of total)

**Root Causes:**
1. **Media Duplication:** Custom sync plugin (`class-media-handler.php:18-75`) performs physical file copying without optimization, creating complete duplicates on target site
2. **No Compression:** Original JPEG files maintain full quality (no quality filtering applied)
3. **Excessive Thumbnails:** WordPress generates 6-8 size variants per image, including unused sizes
4. **No Modern Formats:** No WebP or AVIF conversion (25-50% smaller than JPEG)
5. **Accumulated Bloat:** 10 years of uploads (2016-2026) with no cleanup

**Immediate Impact:**
- Cannot upload new media
- Risk of site functionality issues
- Blocking content creation workflow
- Must address before adding second multisite

## Proposed Solution

### Three-Phase Approach

**Phase 1: Emergency Relief (Target: 30-40% reduction)**
- Implement JPEG/PNG compression
- Convert to WebP format
- Remove duplicate images
- **Expected savings: ~3-4 GB**
- **Timeline: Immediate (1-2 days)**

**Phase 2: Structure Optimization (Target: 10-15% reduction)**
- Disable unused image sizes
- Reduce big_image_size_threshold
- Clean up orphaned files
- **Expected savings: ~1-2 GB**
- **Timeline: Short-term (3-5 days)**

**Phase 3: Long-term Solution (Target: 70-90% reduction)**
- Implement Cloudflare R2 external storage
- Modify sync plugin for shared media references
- Automatic cleanup of local files after upload
- **Expected savings: ~7-9 GB (keep only 1-2 GB local cache)**
- **Timeline: Medium-term (1-2 weeks)**

## Technical Approach

### Phase 1: Emergency Image Compression

#### Step 1.1: Install and Configure ShortPixel

**Plugin Selection:** ShortPixel Image Optimizer
- **Why:** Best compression ratio, WebP/AVIF support, bulk optimization, preserves EXIF
- **Alternative:** Imagify (similar features, slightly different pricing)

**Installation:**
```bash
# Via WP-CLI (if available)
wp plugin install shortpixel-image-optimiser --activate --network

# Or manual install via admin:
# Plugins → Add New → Search "ShortPixel" → Install → Network Activate
```

**Configuration Settings:**

**File:** WordPress Admin → Settings → ShortPixel

```php
// Recommended settings:
Compression type: Lossy (recommended)
JPEG quality: 82 (optimal balance)
Convert PNG to JPEG: Yes (if no transparency)
Create WebP versions: Yes
Keep EXIF data: Yes (for photos)
Resize large images: Enable (max 1920px)
Backup originals: No (save space)
Optimize PDFs: Yes
```

**API Key:** ShortPixel requires free API key (100 images/month free, then paid)

#### Step 1.2: Bulk Optimize Existing Images

**Process:**

1. **Start Bulk Optimization:**
   - ShortPixel → Bulk ShortPixel
   - Select all image types
   - Enable "Include thumbnails"
   - Click "Start Optimizing"

2. **Monitor Progress:**
   - Process runs in background
   - Expected time: 15,037 images ÷ 100 images/hour ≈ 150 hours (6-7 days)
   - Can pause and resume

3. **Expected Results:**
   - Original JPEGs: 30-40% size reduction
   - WebP versions: Additional 25-30% smaller than optimized JPEG
   - Total space saved: ~3-4 GB

**Important:** This is one-time bulk optimization. Future uploads automatically optimize.

#### Step 1.3: Configure Automatic Optimization

**Ensure "Auto-optimize on upload" is enabled** in ShortPixel settings.

**File:** `wp-content/themes/acrylicon-2024/functions.php`

Add WordPress quality filters as backup:

```php
/**
 * Media Optimization Filters
 * Ensures quality control even if ShortPixel fails
 */

// Set JPEG quality to 82% (WordPress 6.8+ recommended)
add_filter( 'jpeg_quality', function() {
    return 82;
}, 10 );

// Control quality for all formats including WebP/AVIF (WordPress 6.8+)
add_filter( 'wp_editor_set_quality', function( $quality, $mime_type, $size ) {
    // Lower quality for small thumbnails (under 200x200)
    if ( isset( $size['width'], $size['height'] ) ) {
        if ( $size['width'] < 200 && $size['height'] < 200 ) {
            return 70; // More aggressive compression for thumbnails
        }
    }

    // Standard quality for different formats
    switch ( $mime_type ) {
        case 'image/jpeg':
            return 82;
        case 'image/webp':
            return 85;
        case 'image/avif':
            return 90; // AVIF handles quality better
        default:
            return $quality;
    }
}, 10, 3 );
```

### Phase 2: Structure Optimization

#### Step 2.1: Identify and Remove Duplicate Images

**Plugin:** Media Deduper

**Installation:**
```bash
wp plugin install media-deduper --activate --network
```

**Process:**

1. **Scan for Duplicates:**
   - Media → Deduper
   - Click "Scan Library"
   - Review detected duplicates

2. **Merge Strategy:**
   - Keep oldest file (likely has most references)
   - Update all references to point to kept file
   - Delete duplicate copies

3. **Expected Results:**
   - ~10-15% of files are duplicates (1,500-2,250 files)
   - Space saved: ~1-1.5 GB

**Manual Check for Sync Duplicates:**

Since sync plugin copies files, check for identical files across sites:

```bash
# Find potential duplicates by filename across multisite
find /Applications/MAMP/htdocs/acrylicon/wp-content/uploads -name "*.jpg" | \
  xargs -I {} basename {} | sort | uniq -d | head -20
```

#### Step 2.2: Disable Unused Image Sizes

**Current Image Sizes Generated:**

From research, WordPress is generating these sizes:
- 150x150 (thumbnail)
- 300x300 (medium)
- 768x768 (medium_large)
- 1024x1024 (large)
- 1536x1536 (2x medium_large)
- 2048x2048 (2x large)
- Custom: 1000x500, 25x25 (theme-specific?)

**Audit Which Sizes Are Actually Used:**

**File:** Create `wp-content/mu-plugins/audit-image-sizes.php`

```php
<?php
/**
 * Plugin Name: Audit Image Sizes
 * Description: Logs which image sizes are actually rendered in templates
 * Version: 1.0.0
 */

add_filter( 'wp_get_attachment_image_src', function( $image, $attachment_id, $size ) {
    error_log( sprintf(
        'Image size used: %s (ID: %d, File: %s)',
        is_array( $size ) ? implode( 'x', $size ) : $size,
        $attachment_id,
        basename( $image[0] ?? '' )
    ) );
    return $image;
}, 10, 3 );
```

**Run for 1 week, then analyze logs:**

```bash
# View most common sizes
grep "Image size used" wp-content/debug.log | \
  awk -F'Image size used: ' '{print $2}' | \
  awk -F' (' '{print $1}' | \
  sort | uniq -c | sort -rn
```

**Disable Unused Sizes:**

**File:** `wp-content/themes/acrylicon-2024/functions.php`

```php
/**
 * Disable Unused Image Sizes
 * Based on audit results, keep only sizes actually used in templates
 */

add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
    // Disable WordPress default sizes that aren't used
    unset( $sizes['medium_large'] );  // 768px - rarely used
    unset( $sizes['1536x1536'] );     // 2x medium_large
    unset( $sizes['2048x2048'] );     // 2x large

    // Keep only essential sizes:
    // - thumbnail (150x150) - Used in admin
    // - medium (300x300) - Used in grids
    // - large (1024x1024) - Used in content

    return $sizes;
}, 10 );

// Remove custom sizes if not used (check audit first)
// remove_image_size( '1000x500' );
// remove_image_size( '25x25' );
```

**Regenerate Thumbnails After Changes:**

**Plugin:** Force Regenerate Thumbnails

```bash
wp plugin install force-regenerate-thumbnails --activate --network
```

**Process:**
1. Tools → Force Regenerate Thumbnails
2. Click "Regenerate All Thumbnails"
3. This will:
   - Delete old unused size variants
   - Generate only enabled sizes
   - Save ~5-10% storage (~0.5-1 GB)

#### Step 2.3: Reduce big_image_size_threshold

**Current Behavior:** WordPress scales down images larger than 2560px.

**Recommendation:** Lower to 1920px (standard Full HD resolution).

**File:** `wp-content/themes/acrylicon-2024/functions.php`

```php
/**
 * Reduce Big Image Threshold
 * Scales down large uploads to 1920px instead of 2560px
 */

add_filter( 'big_image_size_threshold', function() {
    return 1920; // Full HD resolution
}, 10 );
```

**Impact:**
- Reduces storage for new uploads
- For existing images with `-scaled` versions, no immediate impact
- Consider regenerating thumbnails to apply to existing images

#### Step 2.4: Clean Up Unused and Orphaned Files

**Plugin:** Media Cleaner

```bash
wp plugin install media-cleaner --activate --network
```

**Process:**

1. **Scan for Unused Files:**
   - Media → Cleaner
   - Click "Start Scan"
   - Reviews database references vs actual files

2. **Categories of Unused Files:**
   - Orphaned attachments (no post reference)
   - Files not referenced in content
   - Old revisions
   - Auto-draft attachments

3. **Safe Deletion Process:**
   - Review scan results carefully
   - Export CSV for backup
   - Delete in batches (100-500 at a time)
   - Test site functionality after each batch

**Expected Results:**
- ~5-10% of files are unused
- Space saved: ~0.5-1 GB

### Phase 3: External Storage Implementation

#### Step 3.1: Cloudflare R2 Setup

**Why Cloudflare R2:**
- No egress fees (unlike AWS S3)
- S3-compatible API
- Built-in CDN
- Cost: ~$0.015/GB/month (5GB = ~1 NOK/måned)

**Setup Steps:**

1. **Create Cloudflare Account** (if not exists)
   - Go to https://dash.cloudflare.com/
   - Sign up for free account

2. **Enable R2 Storage:**
   - Dashboard → R2
   - Click "Create bucket"
   - Bucket name: `acrylicon-media` (or similar)
   - Location: Automatic (nearest to Norway)

3. **Generate API Token:**
   - R2 → API Tokens
   - Click "Create API Token"
   - Permissions: Read & Write
   - Copy: Access Key ID and Secret Access Key

4. **Configure Custom Domain (Optional but Recommended):**
   - R2 → Custom Domains
   - Add: `media.acrylicon.no` (requires DNS control)
   - Cloudflare handles SSL certificate automatically

#### Step 3.2: Install WordPress Offload Plugin

**Plugin Selection:** Media Cloud Sync

**Why:** Multisite compatible, R2 support, automatic local deletion, well-maintained.

**Installation:**

```bash
wp plugin install media-cloud-sync --activate --network
```

**Configuration:**

**File:** WordPress Admin → Settings → Media Cloud Sync

```php
// Configuration via wp-config.php (more secure than UI)
define( 'MCS_PROVIDER', 'r2' );
define( 'MCS_BUCKET', 'acrylicon-media' );
define( 'MCS_REGION', 'auto' );
define( 'MCS_ACCESS_KEY_ID', 'your-access-key-id' );
define( 'MCS_SECRET_ACCESS_KEY', 'your-secret-access-key' );
define( 'MCS_CUSTOM_DOMAIN', 'https://media.acrylicon.no' );
define( 'MCS_DELETE_LOCAL', true ); // Delete local files after upload
```

**Per-Site Configuration:**

Enable individually on each multisite:
1. Switch to site (Network Admin → Sites → Edit)
2. Settings → Media Cloud Sync
3. Enable "Offload media to cloud"
4. Enable "Delete local files after upload"

#### Step 3.3: Migrate Existing Media to R2

**Strategy: Gradual Migration**

**Don't migrate everything at once!** Start with largest/oldest directories:

**Phase 1: Test Migration (2022/ directory - 3.7 GB)**

```bash
# Via plugin UI:
# Media Cloud Sync → Bulk Tools → Migrate Existing Media
# Select year: 2022
# Click "Start Migration"
```

**Phase 2: Monitor and Validate**

- Check 10-20 random posts from 2022
- Verify images load correctly
- Check featured images
- Test ACF image fields
- Confirm local files deleted

**Phase 3: Continue Migration**

Once validated, migrate remaining years:
- 2021 (589 MB)
- 2020 (186 MB)
- 2023, 2024, 2025, 2026 (remaining ~1-2 GB)

**Expected Results:**
- Local storage: 1-2 GB (only new uploads before sync to R2)
- R2 storage: 9-10 GB (all media)
- **Local savings: 70-90% (7-9 GB freed)**

#### Step 3.4: Modify Sync Plugin for Shared Media

**Current Problem:** `class-media-handler.php` physically copies files.

**New Approach:** Instead of copying files, sync attachment post with R2 URL reference.

**File:** `/wp-content/plugins/acrylicon-multisite-sync/includes/class-media-handler.php`

**Add new method:**

```php
/**
 * Sync media reference instead of copying file (when using R2)
 *
 * @param int $attachment_id Source attachment ID
 * @param int $target_blog_id Target blog ID
 * @param int $source_blog_id Source blog ID
 * @return int|false New attachment ID or false on failure
 */
public function sync_media_reference( $attachment_id, $target_blog_id, $source_blog_id ) {
    // Get source attachment URL (R2 URL)
    switch_to_blog( $source_blog_id );
    $attachment_url = wp_get_attachment_url( $attachment_id );
    $attachment_meta = wp_get_attachment_metadata( $attachment_id );
    $post_data = get_post( $attachment_id );
    restore_current_blog();

    if ( ! $attachment_url ) {
        error_log( "Attachment URL not found: $attachment_id" );
        return false;
    }

    // Switch to target site
    switch_to_blog( $target_blog_id );

    // Check if this R2 URL already exists on target
    global $wpdb;
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta
         WHERE meta_key = '_wp_attached_file'
         AND meta_value = %s",
        $attachment_meta['file']
    ) );

    if ( $existing ) {
        restore_current_blog();
        return $existing; // Reuse existing reference
    }

    // Create new attachment post referencing R2 URL
    $attach_id = wp_insert_post( [
        'post_title' => $post_data->post_title,
        'post_content' => $post_data->post_content,
        'post_excerpt' => $post_data->post_excerpt,
        'post_status' => 'inherit',
        'post_mime_type' => $post_data->post_mime_type,
        'guid' => $attachment_url // R2 URL
    ] );

    if ( is_wp_error( $attach_id ) ) {
        restore_current_blog();
        return false;
    }

    // Copy metadata (points to R2, not local file)
    update_post_meta( $attach_id, '_wp_attached_file', $attachment_meta['file'] );
    update_post_meta( $attach_id, '_wp_attachment_metadata', $attachment_meta );

    // Mark as R2-stored
    update_post_meta( $attach_id, '_r2_stored', true );
    update_post_meta( $attach_id, '_synced_from_attachment', $attachment_id );

    restore_current_blog();
    return $attach_id;
}
```

**Update `copy_media()` to detect R2:**

```php
public function copy_media( $attachment_id, $target_blog_id, $source_blog_id ) {
    // Check if using R2 storage
    if ( defined( 'MCS_PROVIDER' ) && MCS_PROVIDER === 'r2' ) {
        // Use reference sync instead of physical copy
        return $this->sync_media_reference( $attachment_id, $target_blog_id, $source_blog_id );
    }

    // Fallback to original physical copy (for local development)
    return $this->copy_media_physical( $attachment_id, $target_blog_id, $source_blog_id );
}

// Rename original copy_media() method
private function copy_media_physical( $attachment_id, $target_blog_id, $source_blog_id ) {
    // Original physical copy logic here...
}
```

**Benefits:**
- No physical duplication on R2 (single file serves both sites)
- Instant "sync" (just database reference)
- Storage savings on R2 as well
- Maintains site independence (each site has own attachment post)

## Acceptance Criteria

### Phase 1: Emergency Compression
- [ ] ShortPixel plugin installed and configured
- [ ] Bulk optimization completed for all existing images
- [ ] WebP versions generated for all images
- [ ] JPEG quality filters implemented in theme
- [ ] Storage reduced by at least 3 GB
- [ ] All images still display correctly on both sites
- [ ] No broken featured images or ACF image fields

### Phase 2: Structure Optimization
- [ ] Media Deduper installed and duplicates removed
- [ ] Unused image sizes identified via audit
- [ ] Image size filters implemented in theme
- [ ] Thumbnails regenerated with new settings
- [ ] big_image_size_threshold reduced to 1920px
- [ ] Media Cleaner scan completed and unused files removed
- [ ] Storage reduced by additional 1-2 GB (total 4-6 GB saved)
- [ ] No broken images or missing thumbnails

### Phase 3: External Storage
- [ ] Cloudflare R2 bucket created and configured
- [ ] Media Cloud Sync plugin installed on all sites
- [ ] Test migration successful (2022/ directory)
- [ ] All migrated images accessible via CDN
- [ ] Local files deleted after successful upload
- [ ] Sync plugin modified to use reference-based copying
- [ ] No duplicate files on R2 when syncing between sites
- [ ] Storage reduced to 1-2 GB local (90% reduction achieved)
- [ ] Monitoring and alerts configured for R2 usage

### Quality Checks
- [ ] Random sample of 50 images checked for quality
- [ ] Page load speed unchanged or improved
- [ ] No console errors for missing images
- [ ] ACF image fields working correctly
- [ ] Featured images displaying correctly
- [ ] Image galleries functioning properly
- [ ] Responsive images (srcset) working correctly
- [ ] WebP served to modern browsers, JPEG fallback for old browsers

## Success Metrics

**Primary Goal:** Reduce storage from 10.47 GB to under 4 GB (60% reduction)

**Phase 1 Target:** 7-8 GB remaining
**Phase 2 Target:** 5-6 GB remaining
**Phase 3 Target:** 1-2 GB remaining

**Secondary Metrics:**
- Page load time: Maintain or improve (target: <2s)
- Image quality: SSIM score >0.95 (visually lossless)
- Cost savings: R2 cost ~1 NOK/month vs Servebolt upgrade ~200+ NOK/month
- Deployment time: Phase 1 (2 days), Phase 2 (5 days), Phase 3 (2 weeks)

## Dependencies & Risks

### Dependencies

**External Services:**
- ShortPixel API key (100 free images/month, then $4.99/month for 7,000 images)
- Cloudflare account with R2 enabled
- Servebolt hosting access for file system operations

**WordPress Requirements:**
- WordPress 6.8+
- PHP 8.1+
- Multisite network admin access
- WP-CLI access (recommended but not required)

### Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| **Broken images after optimization** | Low | High | Test on staging, backup before bulk operations |
| **R2 migration corruption** | Low | High | Migrate oldest/largest dirs first, validate before proceeding |
| **ShortPixel API limit exceeded** | Medium | Low | Monitor API usage, upgrade plan if needed |
| **Local deletion before R2 upload complete** | Low | High | Ensure MCS_DELETE_LOCAL waits for upload confirmation |
| **Sync plugin conflicts with R2** | Medium | Medium | Test reference-based sync thoroughly on dev |
| **Cost overrun on R2** | Low | Low | R2 is cheap (~1 NOK/month), set billing alerts |

### Rollback Plan

**Phase 1 Rollback:** Restore from backup, uninstall ShortPixel
**Phase 2 Rollback:** Restore wp-config.php, regenerate thumbnails with original settings
**Phase 3 Rollback:** Disable MCS_DELETE_LOCAL, migrate files back from R2 using `rclone`

## Implementation Phases

### Phase 1: Emergency Relief (Days 1-2)

**Day 1:**
- Install ShortPixel
- Configure settings (lossy, WebP, 82% quality)
- Start bulk optimization (runs overnight)
- Implement JPEG quality filters

**Day 2:**
- Monitor bulk optimization progress
- Verify image quality on sample pages
- Check storage reduction metrics
- Deploy to production if validation passes

### Phase 2: Structure Optimization (Days 3-7)

**Day 3:**
- Install Media Deduper
- Scan for duplicates
- Review and merge duplicates (in batches)

**Day 4:**
- Deploy image size audit plugin
- Monitor size usage for 24 hours

**Day 5:**
- Analyze audit results
- Implement image size filters
- Regenerate thumbnails

**Day 6:**
- Install Media Cleaner
- Scan for unused files
- Review results carefully

**Day 7:**
- Delete unused files in batches
- Verify storage reduction
- Final testing of all image functionality

### Phase 3: External Storage (Weeks 2-3)

**Week 2:**
- Set up Cloudflare R2 bucket
- Configure custom domain (media.acrylicon.no)
- Install Media Cloud Sync plugin
- Test migration with small subset (10 images)

**Week 3:**
- Migrate 2022/ directory (3.7 GB)
- Validate images loading correctly
- Modify sync plugin for reference-based copying
- Test sync between sites with R2
- Continue migration of remaining years
- Monitor R2 usage and costs

## References & Research

### Internal References
- Multisite sync implementation: `docs/solutions/wordpress-plugins/multisite-content-sync-implementation.md`
- Media handler: `plugins/acrylicon-multisite-sync/includes/class-media-handler.php:18-75`
- Theme functions: `themes/acrylicon-2024/functions.php:11`

### External Documentation
- [ShortPixel Documentation](https://shortpixel.com/knowledge-base/)
- [WordPress wp_editor_set_quality Filter (6.8+)](https://developer.wordpress.org/reference/hooks/wp_editor_set_quality/)
- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)
- [Media Cloud Sync Plugin](https://wordpress.org/plugins/media-cloud-sync/)
- [WordPress Image Optimization Best Practices 2026](https://kinsta.com/blog/optimize-images-for-web/)

### Related Issues
- Storage capacity warning received from Servebolt
- Multisite sync plugin completing successfully but doubling storage
- Future multisite expansion blocked by storage limits

## Future Considerations

### Post-Implementation Monitoring

**Weekly Checks (First Month):**
- Storage usage trending
- R2 costs vs projections
- Image load time metrics
- Error logs for missing images

**Monthly Reviews:**
- ShortPixel optimization stats
- R2 bandwidth usage
- Unused file accumulation
- Duplicate file growth

### Scaling Considerations

**When Adding Additional Languages:**
- R2 storage scales infinitely (no concern)
- Reference-based sync prevents further duplication
- Optimize new uploads automatically

**If Storage Grows Again:**
- Review and reduce image size limits further
- Implement automatic cleanup schedules
- Consider AVIF format (50% smaller than WebP)

### Documentation Updates

After implementation, document:
- Final storage savings achieved
- R2 configuration steps
- Sync plugin modifications
- Maintenance procedures for editors

## Cost Analysis

### Current Situation
- Servebolt Partner Free: 10 GB (at capacity)
- Need upgrade to next tier: ~200-400 NOK/month

### Proposed Solution Costs

**ShortPixel:**
- Free tier: 100 images/month
- Paid plan: $4.99/month (7,000 images) or one-time $9.99 (10,000 credits)
- **Recommendation:** One-time purchase for bulk optimization

**Cloudflare R2:**
- Storage: $0.015/GB/month
- Operations: $0.36 per million Class A operations
- No egress fees
- **Expected cost:** ~1 NOK/month for 5-10 GB

**Total Monthly Cost:** ~1-10 NOK/month vs 200-400 NOK/month for upgraded hosting

**ROI:** Saves 190-390 NOK/month = 2,280-4,680 NOK/year

## Tags

`wordpress` `multisite` `media-optimization` `storage` `compression` `webp` `cloudflare-r2` `image-optimization` `performance` `cost-reduction`
