---
status: pending
priority: p1
issue_id: "007"
tags: [code-review, architecture, data-integrity, phase3, r2]
dependencies: []
---

# Atomic Upload-Then-Delete Transaction Not Guaranteed

## Problem Statement

The plan proposes `MCS_DELETE_LOCAL = true` to delete local files after R2 upload, but **doesn't specify transaction-like guarantees**. This creates a critical data loss scenario:

```php
1. Upload file to R2
2. Update database with R2 URL
3. Delete local file  ← If this succeeds but step 2 failed, DATA LOSS
```

**Why it matters:** If database write fails after local file is deleted, the file is lost permanently with no rollback mechanism.

## Findings

**Source:** Architecture-strategist agent

**Problem:** The plan doesn't specify how to ensure atomicity of the upload-verify-delete sequence.

**Data loss scenario:**

```
Upload Flow:
1. User uploads image.jpg → Local temp storage
2. R2 offload plugin uploads to R2 → Success
3. Database write wp_postmeta with R2 URL → FAILS (DB connection lost)
4. MCS_DELETE_LOCAL triggers → Deletes local file
5. Result: File exists on R2, but WordPress doesn't know about it (orphan)
          Local file deleted, no way to recover if R2 delete happens
```

**OR worse:**

```
Upload Flow:
1. User uploads image.jpg → Local temp storage
2. R2 offload plugin uploads to R2 → FAILS (network timeout)
3. Database write wp_postmeta with R2 URL → Success (points to non-existent R2 file)
4. MCS_DELETE_LOCAL triggers → Deletes local file
5. Result: Database points to R2, but file doesn't exist on R2
          Local file deleted, IMAGE COMPLETELY LOST
```

**Current plugins tested:** Most R2 offload plugins do NOT implement proper rollback on partial failures.

## Proposed Solutions

### Option 1: State Machine with Explicit Verification (Recommended)

Implement explicit state transitions with rollback capability:

```php
/**
 * State machine for media storage
 */
class Media_Storage_State_Machine {

    const STATE_LOCAL = 'local';
    const STATE_UPLOADING = 'uploading';
    const STATE_REMOTE_VERIFIED = 'remote_verified';
    const STATE_LOCAL_DELETED = 'local_deleted';

    public function upload_to_r2( $attachment_id, $file_path ) {
        // Step 1: Mark as uploading
        $this->set_state( $attachment_id, self::STATE_UPLOADING );

        try {
            // Step 2: Upload to R2
            $r2_url = $this->perform_r2_upload( $file_path );

            // Step 3: VERIFY upload succeeded
            if ( ! $this->verify_r2_file_exists( $r2_url ) ) {
                throw new Exception( 'R2 upload verification failed' );
            }

            // Step 4: Update database with R2 URL
            update_post_meta( $attachment_id, '_r2_url', $r2_url );
            update_post_meta( $attachment_id, '_r2_uploaded_at', current_time( 'mysql' ) );

            // Step 5: Mark as verified
            $this->set_state( $attachment_id, self::STATE_REMOTE_VERIFIED );

            // Step 6: Delete local file ONLY after verification
            if ( defined( 'MCS_DELETE_LOCAL' ) && MCS_DELETE_LOCAL ) {
                $this->delete_local_file_safely( $attachment_id, $file_path );
                $this->set_state( $attachment_id, self::STATE_LOCAL_DELETED );
            }

            return $r2_url;

        } catch ( Exception $e ) {
            // Rollback: Delete R2 file if upload succeeded but verification failed
            if ( isset( $r2_url ) ) {
                $this->delete_r2_file( $r2_url );
            }

            // Revert state
            $this->set_state( $attachment_id, self::STATE_LOCAL );

            // Log error
            error_log( "R2 upload failed for attachment {$attachment_id}: " . $e->getMessage() );

            return false;
        }
    }

    private function verify_r2_file_exists( $r2_url ) {
        $response = wp_remote_head( $r2_url );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        return 200 === $status_code;
    }

    private function delete_local_file_safely( $attachment_id, $file_path ) {
        // Double-check R2 state before deletion
        $r2_url = get_post_meta( $attachment_id, '_r2_url', true );
        $state = get_post_meta( $attachment_id, '_media_storage_state', true );

        if ( self::STATE_REMOTE_VERIFIED !== $state ) {
            throw new Exception( 'Cannot delete local file: R2 not verified' );
        }

        if ( ! $this->verify_r2_file_exists( $r2_url ) ) {
            throw new Exception( 'Cannot delete local file: R2 file not accessible' );
        }

        // Safe to delete
        if ( file_exists( $file_path ) ) {
            wp_delete_file( $file_path );
        }
    }

    private function set_state( $attachment_id, $state ) {
        update_post_meta( $attachment_id, '_media_storage_state', $state );
        update_post_meta( $attachment_id, '_media_storage_state_updated', current_time( 'mysql' ) );
    }
}
```

- **Pros:** Explicit state tracking, safe rollback, verifiable at each step, audit trail
- **Cons:** More complex, requires custom code
- **Effort:** Medium (4-6 hours)
- **Risk:** Low (prevents data loss)

### Option 2: Keep Local Cache, Gradual Cleanup

Safer approach: Never delete local files immediately:

```php
define( 'MCS_PROVIDER', 'r2' );
define( 'MCS_DELETE_LOCAL', false );  // NEVER delete immediately
define( 'MCS_CACHE_TTL', 30 * DAY_IN_SECONDS );  // 30-day local cache

// Cron job: Clean up local files after 30 days if R2 verified
add_action( 'acrylicon_cleanup_old_local_files', function() {
    $cutoff_date = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

    $attachments = get_posts( [
        'post_type' => 'attachment',
        'meta_query' => [
            [
                'key' => '_r2_uploaded_at',
                'value' => $cutoff_date,
                'compare' => '<',
                'type' => 'DATETIME'
            ]
        ],
        'posts_per_page' => 100
    ] );

    foreach ( $attachments as $attachment ) {
        // Verify R2 file still exists
        $r2_url = get_post_meta( $attachment->ID, '_r2_url', true );
        if ( $this->verify_r2_file_exists( $r2_url ) ) {
            // Safe to delete local
            $file_path = get_attached_file( $attachment->ID );
            wp_delete_file( $file_path );
        }
    }
} );
```

- **Pros:** Zero risk of data loss, 30-day safety window, automatic cleanup
- **Cons:** Doesn't solve storage problem immediately, requires 10GB+ for 30 days
- **Effort:** Medium (3-4 hours)
- **Risk:** Very low

### Option 3: Retry Logic with Exponential Backoff

Add robust retry mechanism:

```php
private function upload_with_retry( $file_path, $attachment_id, $max_retries = 3 ) {
    $attempt = 0;

    while ( $attempt < $max_retries ) {
        try {
            $r2_url = $this->upload_to_r2( $file_path );

            // Verify upload
            if ( $this->verify_r2_file_exists( $r2_url ) ) {
                return $r2_url;
            }

            throw new Exception( 'Upload verification failed' );

        } catch ( Exception $e ) {
            $attempt++;

            if ( $attempt >= $max_retries ) {
                // Log failure, alert admin
                $this->alert_upload_failure( $attachment_id, $e->getMessage() );
                throw $e;
            }

            // Exponential backoff: 1s, 2s, 4s
            sleep( pow( 2, $attempt ) );
        }
    }
}
```

- **Pros:** Handles transient failures, improves reliability
- **Cons:** Doesn't solve atomicity issue alone
- **Effort:** Small (2 hours)
- **Risk:** Low

## Recommended Action

**Implement Option 1 + Option 3** (state machine with retry logic)

**OR**

**Implement Option 2** (never delete immediately, gradual cleanup) - safest approach

**DO NOT** enable `MCS_DELETE_LOCAL = true` without implementing either Option 1 or Option 2.

## Technical Details

**Affected Files:**
- Create `/mu-plugins/acrylicon-media-storage-state-machine.php` (Option 1)
- OR modify R2 offload plugin configuration (Option 2)

**Components:**
- R2 upload handling
- Database integrity
- File system operations
- Cron jobs (Option 2)

**New Metadata Keys (Option 1):**
- `_media_storage_state` (string): Current state in state machine
- `_media_storage_state_updated` (datetime): Last state change
- `_r2_url` (string): R2 file URL
- `_r2_uploaded_at` (datetime): When uploaded to R2

**Rollback Procedure:**
If upload fails:
1. Delete partial R2 upload (if exists)
2. Revert state to 'local'
3. Keep local file
4. Log error for admin review

## Acceptance Criteria

- [ ] State machine implemented with explicit transitions
- [ ] Upload verification with HTTP HEAD request
- [ ] Retry logic with exponential backoff
- [ ] Local file deletion ONLY after R2 verification
- [ ] Rollback mechanism for failed uploads
- [ ] Admin alert system for persistent failures
- [ ] Cron job for gradual local cleanup (Option 2)
- [ ] Audit log of all state transitions
- [ ] Test: Simulate R2 failure, verify local file preserved
- [ ] Test: Simulate DB failure, verify R2 upload rolled back
- [ ] Test: Simulate network timeout, verify retry works

## Work Log

### 2026-01-26
- Identified lack of transaction guarantees during architecture review
- Documented data loss scenarios
- Recommended state machine with explicit verification

## Resources

- Plan section: Lines 389-393 (proposes DELETE_LOCAL without transaction guarantees)
- [WordPress wp_remote_head()](https://developer.wordpress.org/reference/functions/wp_remote_head/)
- [Database Transaction Patterns](https://en.wikipedia.org/wiki/Two-phase_commit_protocol)
- [State Machine Pattern](https://refactoring.guru/design-patterns/state)
- [Exponential Backoff Algorithm](https://en.wikipedia.org/wiki/Exponential_backoff)
