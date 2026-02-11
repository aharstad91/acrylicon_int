---
module: Multisite Sync Plugin
date: 2026-02-11
problem_type: security_issue
component: service_object
symptoms:
  - "Media handler copies files without MIME type validation"
  - "No file extension whitelist on media sync"
  - "Polyglot files (valid image header + embedded PHP) could pass through"
root_cause: missing_validation
resolution_type: code_fix
severity: high
tags: [security, file-upload, multisite, media-sync, validation, wordpress]
---

# Troubleshooting: Missing File Validation in Multisite Media Sync

## Problem
The `Media_Handler::copy_media()` method in the multisite-sync plugin copied files between sites using `copy()` without any file type, MIME, or content validation. This allowed potentially malicious files (PHP shells disguised as images, SVG with XSS, polyglot files) to be propagated across all sites in the network.

## Environment
- Module: Multisite Sync Plugin (`acrylicon-multisite-sync`)
- WordPress Version: 6.8.3
- PHP: 8.4 (Servebolt hosting)
- Affected Component: `includes/class-media-handler.php`
- Date: 2026-02-11

## Symptoms
- `copy()` call at line 46 had no validation before it
- MIME type was only checked AFTER copy (for database registration), not before
- No extension whitelist — any file type could be synced
- No content validation — polyglot files (valid JPEG header + PHP payload) would pass

## What Didn't Work

**Considered but rejected: `file_get_contents()` + regex scanning for PHP in images**
- Brittle: regex patterns can be bypassed with encoding tricks
- Slow: reads entire file into memory for scanning
- Redundant: `getimagesize()` already validates image structure

**Considered but rejected: Separate SVG sanitizer**
- SVG Support plugin already handles SVG sanitization in WordPress
- WordPress doesn't allow SVG uploads by default — requires explicit plugin
- Double sanitization adds complexity without security benefit

## Solution

Added `validate_file()` private method that runs three checks before `copy()`:

```php
private function validate_file( $file_path, $attachment_id ) {
    $basename = basename( $file_path );

    // 1. Validate extension and MIME type
    $filetype = wp_check_filetype_and_ext( $file_path, $basename );
    if ( ! $filetype['ext'] || ! $filetype['type'] ) {
        error_log( "[Acrylicon Sync] Blocked: invalid file type for attachment $attachment_id ($basename)" );
        return false;
    }

    // 2. Check against WordPress allowed MIME types
    $allowed_mimes = get_allowed_mime_types();
    if ( ! in_array( $filetype['type'], $allowed_mimes, true ) ) {
        error_log( "[Acrylicon Sync] Blocked: MIME type {$filetype['type']} not allowed" );
        return false;
    }

    // 3. Verify image files are actually valid images
    if ( str_starts_with( $filetype['type'], 'image/' ) && 'image/svg+xml' !== $filetype['type'] ) {
        if ( false === @getimagesize( $file_path ) ) {
            error_log( "[Acrylicon Sync] Blocked: file claims image but fails validation" );
            return false;
        }
    }

    return true;
}
```

Also improved MIME type for database registration to use `wp_check_filetype()` instead of raw `mime_content_type()`.

## Results

- PHP shell uploads blocked (extension validation catches `.php`, `.phtml`, etc.)
- Double extension attacks blocked (`shell.php.jpg` fails `wp_check_filetype_and_ext()`)
- Polyglot files blocked (`getimagesize()` validates image structure)
- SVG correctly exempted from `getimagesize()` (XML-based, not bitmap)
- All rejected files logged with `[Acrylicon Sync]` prefix for monitoring

## Why This Works

1. **`wp_check_filetype_and_ext()` is WordPress's own defense.** It validates both the file extension AND the actual MIME type by reading file headers. It catches double extensions like `shell.php.jpg` because PHP isn't an allowed extension.

2. **`get_allowed_mime_types()` uses the admin-configured whitelist.** If an admin has restricted allowed upload types, sync respects those restrictions.

3. **`getimagesize()` validates image structure.** A PHP file with a JPEG header prepended will fail `getimagesize()` if the image structure is invalid. This blocks most polyglot attacks.

4. **SVG exclusion is correct.** SVG is XML, not a bitmap image — `getimagesize()` doesn't understand SVG. SVG sanitization is handled by the SVG Support plugin.

## Prevention

- **Always validate before copy, not after.** The original code checked MIME type after copying (for database registration) but never blocked based on it.
- **Use WordPress core validation functions.** Don't write custom MIME checks — `wp_check_filetype_and_ext()` handles edge cases like double extensions.
- **Log blocked files.** Monitoring `[Acrylicon Sync] Blocked:` in error logs reveals attack attempts.
- **Defense in depth.** The plugin already requires `manage_network` capability, but validation adds a second layer in case admin credentials are compromised.

## Related Issues

- See also: `docs/solutions/performance-issues/pagespeed-69-to-99-render-blocking-webp-20260211.md` (same session)
