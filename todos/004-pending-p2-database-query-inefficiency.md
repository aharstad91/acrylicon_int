---
status: pending
priority: p2
issue_id: "004"
tags: [code-review, performance, database, phase3, multisite]
dependencies: [008]
note: "Refererer til R2 sync-kode som ikke eksisterer ennå. Relevant først når R2-integrasjon (#008) bygges. Nedgradert fra P1 til P2."
---

# Database Query Inefficiency - O(n) Full Table Scans

## Problem Statement

The proposed R2 sync code (lines 486-493) performs unindexed lookups on `wp_postmeta.meta_value`, causing **O(n) full table scans** that will make sync operations extremely slow:

```php
$existing = $wpdb->get_var( $wpdb->prepare(
    "SELECT post_id FROM $wpdb->postmeta
     WHERE meta_key = '_wp_attached_file'
     AND meta_value = %s",
    $attachment_meta['file']
) );
```

**Why it matters:** For 15,000 images, this creates 12-50 minutes of sync time (instead of 2-5 minutes), plus high database CPU usage that could impact site performance.

## Findings

**Source:** Performance-oracle agent

**Problem:** `meta_value` is a LONGTEXT column and typically has **no index** in WordPress:

```sql
CREATE TABLE wp_postmeta (
  meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned NOT NULL DEFAULT '0',
  meta_key varchar(255) DEFAULT NULL,
  meta_value longtext,  -- NO INDEX!
  PRIMARY KEY (meta_id),
  KEY post_id (post_id),
  KEY meta_key (meta_key(191))
  -- NO INDEX ON meta_value
);
```

**Performance impact:**

For a typical multisite with 15,000 attachments:
- Postmeta rows: ~150,000 (10 meta per attachment)
- Query time per lookup: 50-200ms (full table scan)
- **For 15,000 sync operations: 750-3,000 seconds (12-50 minutes)** ❌

This is a **CRITICAL BOTTLENECK** for the sync operation.

**Database CPU during sync:** 80-100% (full table scans are expensive)

**Risk of timeout:** High on shared hosting environments

## Proposed Solutions

### Option 1: Hash-Based Lookup with Indexed Meta Key (Recommended)

Store MD5 hash of file path in a separate indexed meta key:

```php
public function sync_media_reference( $attachment_id, $target_blog_id, $source_blog_id ) {
    switch_to_blog( $source_blog_id );
    $attachment_meta = wp_get_attachment_metadata( $attachment_id );
    $file_path = $attachment_meta['file'];
    $file_hash = md5( $file_path );  // Generate hash
    restore_current_blog();

    switch_to_blog( $target_blog_id );

    // Check cache first - O(1)
    $cache_key = 'media_ref_' . $file_hash;
    $existing = wp_cache_get( $cache_key, 'media_sync' );

    if ( false === $existing ) {
        // Query with indexed meta_key - O(log n)
        $existing = get_posts( [
            'post_type' => 'attachment',
            'meta_query' => [
                [
                    'key' => '_media_file_hash',  // This IS indexed
                    'value' => $file_hash,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ] );

        // Cache for 24 hours
        wp_cache_set( $cache_key, $existing, 'media_sync', DAY_IN_SECONDS );
    }

    if ( ! empty( $existing ) ) {
        restore_current_blog();
        return $existing[0]; // Reuse existing reference
    }

    // Create new attachment...
    $attach_id = wp_insert_post( [...] );

    // Store hash for future lookups
    update_post_meta( $attach_id, '_media_file_hash', $file_hash );

    restore_current_blog();
    return $attach_id;
}
```

**Performance improvement:**
- Without optimization: O(n) = 50-200ms per query
- With hash + cache: O(1) = 0.1ms per query (after first lookup)
- **40-2000x performance improvement**

- **Pros:** Massive performance boost, uses WordPress object cache, no schema changes
- **Cons:** Adds one meta key per attachment (~100 bytes each = 1.5 MB total)
- **Effort:** Medium (2-3 hours)
- **Risk:** Low (hash collisions extremely unlikely with MD5)

### Option 2: Add Custom Database Index

Add composite index on meta_key + meta_value:

```sql
-- Run once during setup
CREATE INDEX idx_attached_file
ON wp_postmeta (meta_key(191), meta_value(100));
```

Then use optimized query:

```php
$existing = $wpdb->get_var( $wpdb->prepare(
    "SELECT post_id FROM $wpdb->postmeta
     WHERE meta_key = '_wp_attached_file'
     AND meta_value = %s
     LIMIT 1",  -- Add LIMIT
    $attachment_meta['file']
) );
```

**Performance improvement:** O(log n) = 1-5ms per query

- **Pros:** Simple, direct solution
- **Cons:** Requires database access, index on LONGTEXT limited to 100 chars (may miss some edge cases)
- **Effort:** Small (if you have database access)
- **Risk:** Medium (custom indexes can break on WordPress updates, though unlikely)

### Option 3: Batch Processing to Reduce Switching Overhead

Even with optimized queries, minimize database switching:

```php
public function batch_sync_media_references( $attachment_ids, $target_blog_id, $source_blog_id ) {
    // Switch once for source blog
    switch_to_blog( $source_blog_id );

    // Preload all metadata in single batch
    $metadata_batch = [];
    foreach ( $attachment_ids as $attachment_id ) {
        $metadata_batch[ $attachment_id ] = wp_get_attachment_metadata( $attachment_id );
    }

    restore_current_blog();

    // Switch once for target blog
    switch_to_blog( $target_blog_id );

    $results = [];
    foreach ( $attachment_ids as $attachment_id ) {
        $results[] = $this->create_media_reference(
            $attachment_id,
            $metadata_batch[ $attachment_id ]
        );
    }

    restore_current_blog();

    return $results;
}
```

**Performance improvement:**
- Original: 15,000 × 3 switches = 45,000 switches (15-60ms each)
- Batched: 2 switches per batch of 100 = 300 switches
- **150x reduction in switching overhead**

- **Pros:** Complements Option 1, reduces multisite overhead
- **Cons:** Requires refactoring sync workflow
- **Effort:** Medium (3-4 hours)
- **Risk:** Low

## Recommended Action

**Implement ALL THREE OPTIONS** (they complement each other):

1. **Option 1 (hash + cache):** Primary optimization - mandatory
2. **Option 3 (batch processing):** Secondary optimization - highly recommended
3. **Option 2 (database index):** Optional backup if Option 1 insufficient

**Revised sync timeline:**
- Without optimization: 20-60 minutes
- With Option 1 + 3: **2-5 minutes** ✅

## Technical Details

**Affected Files:**
- `/plugins/acrylicon-multisite-sync/includes/class-media-handler.php` (modify sync_media_reference method)
- Optional: Database (add index)

**Components:**
- WordPress postmeta queries
- WordPress object cache
- Multisite blog switching
- Sync plugin performance

**Database Impact:**
- Additional meta keys: ~15,000 × 100 bytes = 1.5 MB
- Memory cache: ~15,000 × 100 bytes = 1.5 MB
- Negligible impact on total database size

**Code Changes Required:**

1. Add hash generation on source attachment
2. Store hash in `_media_file_hash` meta key
3. Query by hash instead of file path
4. Implement WordPress object cache
5. Add batch processing for multiple syncs

## Acceptance Criteria

- [ ] Hash-based lookup implemented
- [ ] WordPress object cache used for queries
- [ ] Batch processing for multiple image syncs
- [ ] Sync performance < 5 minutes for 15,000 images
- [ ] Database CPU usage < 50% during sync
- [ ] No query timeouts on shared hosting
- [ ] Cache hit rate > 90% on repeat syncs
- [ ] Memory usage remains < 512 MB during sync
- [ ] Existing synced media still works after optimization

## Work Log

### 2026-01-26
- Identified O(n) full table scan performance issue
- Validated that meta_value has no index in WordPress schema
- Recommended hash-based lookup + caching solution

## Resources

- Plan section: Lines 486-493 (inefficient query)
- [WordPress Object Cache](https://developer.wordpress.org/reference/classes/wp_object_cache/)
- [WordPress Postmeta Schema](https://codex.wordpress.org/Database_Description#Table:_wp_postmeta)
- [MySQL Index Best Practices](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [WordPress WP_Query Performance](https://10up.github.io/Engineering-Best-Practices/php/#performance)
