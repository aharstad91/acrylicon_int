---
title: "Security Audit: Language Switcher Implementation"
date: 2026-02-11
type: security-audit
severity: HIGH
plan: docs/plans/2026-02-11-feat-language-switcher-plan.md
status: REVIEW
---

# Language Switcher Security Audit

## Executive Summary

**Risk Level: MEDIUM-HIGH**

The language switcher implementation poses several security risks that require immediate mitigation before deployment. Primary concerns include:

1. **CRITICAL**: Open redirect vulnerability via unsanitized URL construction
2. **HIGH**: Path traversal risk in URL parsing
3. **MEDIUM**: XSS risk in hreflang tag generation
4. **MEDIUM**: Insufficient input validation on user-controlled data
5. **LOW**: CSRF risk in language switching (acceptable for GET requests)

**Status**: DO NOT IMPLEMENT until all CRITICAL and HIGH vulnerabilities are mitigated.

---

## Vulnerability Analysis

### 1. CRITICAL: Open Redirect Vulnerability

**Location**: `acrylicon_get_equivalent_url()` function

**Attack Vector**:
```php
// VULNERABLE CODE (from plan):
function acrylicon_get_equivalent_url($target_blog_id) {
    $current_uri = $_SERVER['REQUEST_URI'];  // ❌ UNSAFE
    // ... slug mapping logic ...
    return $target_url;  // ❌ UNVALIDATED
}
```

**Exploit Scenario**:
```
# Attacker crafts malicious URL:
https://acrylicon.no/norway/../../../etc/passwd
https://acrylicon.no/norway/@evil.com/phishing

# Language switcher constructs:
https://acrylicon.no/@evil.com/phishing  (open redirect)
```

**Impact**:
- Phishing attacks using legitimate domain
- Credential harvesting via redirect to attacker-controlled site
- SEO poisoning via malicious hreflang tags
- User trust exploitation

**Mitigation Requirements**:

1. **Sanitize REQUEST_URI immediately after reading**:
```php
function acrylicon_get_equivalent_url($target_blog_id) {
    // Use WordPress native function instead of raw $_SERVER
    $current_uri = wp_parse_url(home_url($_SERVER['REQUEST_URI']), PHP_URL_PATH);

    // Fallback to empty string if parsing fails
    if (!$current_uri) {
        $current_uri = '/';
    }

    // Remove any encoded null bytes, newlines, or other control characters
    $current_uri = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $current_uri);

    // Normalize path (removes ../, ./, etc.)
    $current_uri = wp_normalize_path($current_uri);

    // ... rest of function
}
```

2. **Validate target URL before returning**:
```php
function acrylicon_get_equivalent_url($target_blog_id) {
    // ... slug mapping logic ...

    // Construct target URL
    $target_url = get_home_url($target_blog_id, $target_path);

    // CRITICAL: Validate constructed URL before returning
    $parsed = wp_parse_url($target_url);

    // Ensure URL is internal to our WordPress installation
    $allowed_hosts = [
        parse_url(get_site_url(1), PHP_URL_HOST),  // Blog 1
        parse_url(get_site_url(3), PHP_URL_HOST),  // Blog 3
        'acrylicon.no',
        'acryli-28355.jana-osl.servebolt.cloud',
    ];

    if (!isset($parsed['host']) || !in_array($parsed['host'], $allowed_hosts, true)) {
        // URL is external or malformed - return safe fallback
        return get_home_url($target_blog_id);
    }

    // Ensure scheme is HTTPS (never HTTP on production)
    if (isset($parsed['scheme']) && $parsed['scheme'] !== 'https' && !WP_DEBUG) {
        return get_home_url($target_blog_id);
    }

    return $target_url;
}
```

3. **Use WordPress API for URL construction**:
```php
// ALWAYS use get_home_url() or get_site_url() - never manual concatenation
$target_url = get_home_url($target_blog_id, $target_path);  // ✅ SAFE

// NEVER do this:
$target_url = 'https://acrylicon.no' . $target_path;  // ❌ UNSAFE
```

---

### 2. HIGH: Path Traversal Risk

**Location**: URL parsing and slug mapping

**Attack Vector**:
```php
// VULNERABLE CODE:
$slug = trim($path, '/');  // ❌ INSUFFICIENT

// Attacker input:
/norway/../wp-content/uploads/sensitive.pdf
/norway/./../../wp-config.php
/norway/%2e%2e%2fadmin/
```

**Impact**:
- Access to restricted WordPress directories
- Exposure of sensitive files (wp-config.php, uploads)
- Bypass of blog prefix restrictions

**Mitigation Requirements**:

```php
function acrylicon_parse_current_path() {
    // Use WordPress native URL parsing
    $current_url = home_url($_SERVER['REQUEST_URI']);
    $parsed = wp_parse_url($current_url);

    if (!isset($parsed['path'])) {
        return '/';
    }

    $path = $parsed['path'];

    // Normalize path (removes ../, ./, etc.)
    $path = wp_normalize_path($path);

    // Remove URL-encoded traversal attempts
    $path = str_replace(['%2e', '%2E'], '', $path);

    // Strip blog prefix
    $blog_id = get_current_blog_id();
    if ($blog_id === 3) {
        $path = preg_replace('#^/norway/?#', '/', $path);
    }

    // Ensure path starts with /
    if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }

    // Remove any remaining double slashes
    $path = preg_replace('#/+#', '/', $path);

    // Validate path doesn't contain WordPress reserved paths
    $disallowed_patterns = [
        '#wp-admin#i',
        '#wp-content#i',
        '#wp-includes#i',
        '#\.php#i',
    ];

    foreach ($disallowed_patterns as $pattern) {
        if (preg_match($pattern, $path)) {
            return '/';  // Fallback to homepage
        }
    }

    return $path;
}
```

---

### 3. MEDIUM: XSS Risk in Hreflang Tags

**Location**: `acrylicon_hreflang_tags()` function

**Attack Vector**:
```html
<!-- VULNERABLE OUTPUT: -->
<link rel="alternate" hreflang="en" href="<?php echo $url; ?>" />
<!-- If $url contains: "><script>alert(1)</script><a href=" -->
```

**Impact**:
- Stored XSS via malicious URL in database
- Session hijacking via cookie theft
- Defacement of page source

**Mitigation Requirements**:

```php
function acrylicon_hreflang_tags() {
    $languages = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    foreach ($languages as $blog_id => $lang) {
        if ($blog_id === $current_blog_id) {
            continue;  // Skip current language
        }

        $equiv_url = acrylicon_get_equivalent_url($blog_id);

        // CRITICAL: Escape URL for HTML attribute context
        $escaped_url = esc_url($equiv_url);

        // ADDITIONAL: Validate URL scheme
        if (strpos($escaped_url, 'http') !== 0) {
            continue;  // Skip invalid URLs
        }

        // Escape language code (though hardcoded, defense in depth)
        $lang_code = esc_attr($lang['code']);

        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            $lang_code,
            $escaped_url
        );
    }

    // x-default tag (always blog 1)
    $default_url = esc_url(acrylicon_get_equivalent_url(1));
    echo '<link rel="alternate" hreflang="x-default" href="' . $default_url . '" />' . "\n";
}

// Hook to wp_head with priority (after other head elements)
add_action('wp_head', 'acrylicon_hreflang_tags', 1);
```

---

### 4. MEDIUM: Insufficient Slug Map Validation

**Location**: `acrylicon_slug_map()` array

**Risk**: While the slug map is a static PHP array (not user input), the lookup logic must validate both keys and values.

**Attack Vector**:
```php
// If slug comes from URL and isn't validated:
$slug = $current_path;  // Could be "../../../etc/passwd"
$mapped = $slug_map[$slug] ?? $slug;  // Falls through to unvalidated $slug
```

**Mitigation Requirements**:

```php
function acrylicon_slug_map() {
    return [
        // Norsk slug => Engelsk slug
        'fordeler'           => 'benefits',
        'bruksomrader'       => 'applications',
        'produkter'          => 'products',
        'referanser'         => 'references',
        'om-acrylicon'       => 'about-acrylicon',
        'baerekraft'         => 'sustainability',
        'levetids-kostnader' => 'lifecycle-costs',
        'gode-grunner'       => 'good-reasons',
        'kontakt-oss'        => 'contact-us',
        'sertifiseringer'    => 'certifications',
    ];
}

function acrylicon_map_slug($slug, $direction = 'no_to_en') {
    $map = acrylicon_slug_map();

    // CRITICAL: Validate slug before lookup
    // Only allow alphanumeric, hyphens, underscores
    if (!preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        return null;  // Invalid slug format
    }

    // Limit slug length (WordPress post slugs are typically < 200 chars)
    if (strlen($slug) > 200) {
        return null;
    }

    if ($direction === 'no_to_en') {
        return $map[$slug] ?? null;
    } else {
        // Reverse lookup (en to no)
        $flipped = array_flip($map);
        return $flipped[$slug] ?? null;
    }
}
```

---

### 5. LOW: SVG Icon Security (Acceptable)

**Location**: `svg_icon()` function (lines 383-451 in functions.php)

**Current Implementation**: ADEQUATE

The existing `svg_icon()` function provides basic XSS protection:
- Strips `<script>` tags
- Strips `<style>` tags
- Removes data URIs

**Assessment**: ACCEPTABLE for controlled SVG files (flags, globe icon).

**Additional Hardening (Optional but Recommended)**:

```php
function svg_icon($filename, $options = []) {
    // ... existing code ...

    // ADDITIONAL: Validate filename to prevent path traversal
    $filename = basename($filename);  // Strip any directory components
    $filename = preg_replace('/[^a-z0-9_-]/i', '', $filename);  // Only alphanumeric + hyphen/underscore

    // Build safe path
    $svg_path = get_template_directory() . '/assets/gfx/' . $filename . '.svg';

    // ADDITIONAL: Ensure path is within gfx directory (prevent traversal)
    $real_path = realpath($svg_path);
    $gfx_dir = realpath(get_template_directory() . '/assets/gfx/');

    if (!$real_path || strpos($real_path, $gfx_dir) !== 0) {
        return '';  // Path traversal attempt
    }

    if (!file_exists($real_path)) {
        return '';
    }

    $svg = file_get_contents($real_path);

    // EXISTING sanitization (KEEP THIS):
    $svg = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $svg);
    $svg = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $svg);
    $svg = preg_replace('#data:[^,]*,#is', '', $svg);

    // ADDITIONAL: Strip event handlers (onclick, onload, etc.)
    $svg = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg);

    // ADDITIONAL: Strip javascript: protocol
    $svg = preg_replace('/javascript:/i', '', $svg);

    // ... rest of existing code ...
}
```

---

### 6. MEDIUM: JavaScript Dropdown Security

**Location**: Language switcher dropdown toggle script

**Risk**: DOM-based XSS if language data is rendered without escaping.

**Mitigation Requirements**:

```php
function acrylicon_render_language_switcher($context = 'header') {
    $languages = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    if ($context === 'header') {
        ?>
        <div class="language-switcher relative" data-lang-switcher>
            <button
                type="button"
                aria-haspopup="true"
                aria-expanded="false"
                aria-label="<?php echo esc_attr__('Language selector', 'acrylicon'); ?>"
                class="flex items-center gap-2"
                data-lang-toggle
            >
                <?php echo svg_icon('globe', ['width' => 20, 'height' => 20, 'class' => 'icon-globe']); ?>
                <span class="lang-code"><?php echo esc_html($languages[$current_blog_id]['code']); ?></span>
            </button>

            <div class="lang-dropdown hidden" data-lang-dropdown role="menu">
                <?php foreach ($languages as $blog_id => $lang): ?>
                    <?php
                    $equiv_url = acrylicon_get_equivalent_url($blog_id);
                    $is_current = ($blog_id === $current_blog_id);
                    ?>
                    <a
                        href="<?php echo esc_url($equiv_url); ?>"
                        class="lang-option <?php echo $is_current ? 'active' : ''; ?>"
                        role="menuitem"
                        <?php if ($is_current): ?>
                        aria-current="page"
                        <?php endif; ?>
                        hreflang="<?php echo esc_attr($lang['code']); ?>"
                    >
                        <?php echo svg_icon('flags/' . esc_attr($lang['flag']), ['width' => 20, 'height' => 15]); ?>
                        <span><?php echo esc_html($lang['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    // ... footer context ...
}
```

**JavaScript (SECURE)**:
```javascript
// footer.php or separate JS file
const languageSwitcher = () => {
    const switcher = document.querySelector('[data-lang-switcher]');
    if (!switcher) return;

    const toggle = switcher.querySelector('[data-lang-toggle]');
    const dropdown = switcher.querySelector('[data-lang-dropdown]');

    // Toggle dropdown
    toggle?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', !isOpen);
        dropdown.classList.toggle('hidden', isOpen);
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!switcher.contains(e.target)) {
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.add('hidden');
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.add('hidden');
            toggle.focus();  // Return focus to toggle
        }
    });
};

document.addEventListener('DOMContentLoaded', languageSwitcher);
```

---

## Security Requirements Checklist

### Input Validation
- [x] `$_SERVER['REQUEST_URI']` sanitized via `wp_parse_url()` and `home_url()`
- [x] Path normalization via `wp_normalize_path()`
- [x] URL-encoded traversal sequences removed
- [x] Slug validation (alphanumeric + hyphens only)
- [x] Disallowed path patterns blocked (wp-admin, wp-content, .php)

### Output Escaping
- [x] All URLs escaped with `esc_url()` in HTML attributes
- [x] Language codes escaped with `esc_attr()`
- [x] Language labels escaped with `esc_html()`
- [x] SVG filenames sanitized in `svg_icon()`

### URL Security
- [x] Target URLs validated against allowed hosts
- [x] HTTPS enforcement on production
- [x] Open redirect prevention via host whitelist
- [x] WordPress API used for URL construction (`get_home_url()`)

### XSS Prevention
- [x] All dynamic output escaped in appropriate context
- [x] SVG sanitization maintained (script/style/data URI removal)
- [x] Event handler stripping in SVG content
- [x] No `innerHTML` or `eval()` in JavaScript

### CSRF Protection
- [ ] N/A - Language switching is a GET request (safe to omit CSRF token)
- [ ] Consider nonce if POST method is used in future

### Access Control
- [ ] N/A - Language switcher is public-facing (no authentication required)

### Error Handling
- [x] Failed URL parsing returns safe fallback (homepage)
- [x] Invalid slugs return null (handled gracefully)
- [x] Missing SVG files return empty string (no error disclosure)

---

## OWASP Top 10 Compliance

| OWASP Category | Status | Notes |
|---|---|---|
| A01:2021 - Broken Access Control | ✅ PASS | Public feature, no access control needed |
| A02:2021 - Cryptographic Failures | ✅ PASS | HTTPS enforced on production |
| A03:2021 - Injection | ⚠️ MITIGATE | Path traversal and open redirect risks - see mitigations above |
| A04:2021 - Insecure Design | ✅ PASS | Design reviewed, fallback strategy appropriate |
| A05:2021 - Security Misconfiguration | ⚠️ CHECK | Ensure `FORCE_SSL_ADMIN` is true on production |
| A06:2021 - Vulnerable Components | ✅ PASS | No external dependencies |
| A07:2021 - Identification Failures | ✅ PASS | No authentication component |
| A08:2021 - Software Integrity Failures | ✅ PASS | No dynamic code loading |
| A09:2021 - Security Logging Failures | ⚠️ RECOMMEND | Consider logging failed URL validations |
| A10:2021 - Server-Side Request Forgery | ✅ PASS | No external requests generated |

---

## Recommended Security Enhancements

### 1. Content Security Policy (CSP)

Add CSP header to restrict inline scripts (future enhancement):

```php
// functions.php
function acrylicon_security_headers() {
    if (!is_admin()) {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // CSP (adjust as needed for inline scripts/styles)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline';";
        header("Content-Security-Policy: " . $csp);
    }
}
add_action('send_headers', 'acrylicon_security_headers');
```

### 2. Rate Limiting (Future Enhancement)

Consider rate limiting language switch requests to prevent abuse:

```php
// Transient-based simple rate limiting
function acrylicon_check_rate_limit($identifier, $limit = 10, $period = 60) {
    $transient_key = 'rate_limit_' . md5($identifier);
    $count = get_transient($transient_key);

    if ($count === false) {
        set_transient($transient_key, 1, $period);
        return true;
    }

    if ($count >= $limit) {
        return false;  // Rate limit exceeded
    }

    set_transient($transient_key, $count + 1, $period);
    return true;
}
```

### 3. Security Logging

Log suspicious activity for monitoring:

```php
function acrylicon_log_security_event($event_type, $details) {
    if (!WP_DEBUG) {
        return;  // Only log in debug mode or implement custom logging
    }

    error_log(sprintf(
        '[SECURITY] %s: %s | IP: %s | Time: %s',
        $event_type,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        date('Y-m-d H:i:s')
    ));
}

// Usage in acrylicon_get_equivalent_url():
if (!in_array($parsed['host'], $allowed_hosts, true)) {
    acrylicon_log_security_event('OPEN_REDIRECT_ATTEMPT', $target_url);
    return get_home_url($target_blog_id);
}
```

---

## Implementation Priority

### MUST FIX (Before Launch)
1. **Open redirect prevention** - URL host validation
2. **Path traversal protection** - Path normalization and validation
3. **XSS mitigation** - Output escaping with `esc_url()`, `esc_attr()`, `esc_html()`
4. **Slug validation** - Regex pattern matching

### SHOULD FIX (Next Sprint)
5. **SVG hardening** - Path traversal in `svg_icon()`
6. **Error logging** - Security event monitoring
7. **HTTPS enforcement** - Set `FORCE_SSL_ADMIN` to `true` in wp-config.php

### NICE TO HAVE (Future)
8. **CSP headers** - Defense in depth
9. **Rate limiting** - Abuse prevention
10. **Automated security testing** - Unit tests for validation functions

---

## Testing Checklist

Before deployment, verify:

- [ ] Open redirect: Try `?url=//evil.com` - should redirect to homepage
- [ ] Path traversal: Try `/../../../wp-config.php` - should redirect to homepage
- [ ] XSS in hreflang: Inspect page source, verify `esc_url()` is applied
- [ ] Invalid slugs: Try special characters in URL - should fallback gracefully
- [ ] HTTPS enforcement: Verify production URLs use https://
- [ ] Host validation: Manually test constructed URLs against allowed hosts
- [ ] Dropdown XSS: Inspect DOM, verify all attributes are escaped
- [ ] SVG path traversal: Try `../../etc/passwd` as filename - should return empty

---

## Sign-Off

**Security Reviewer**: Claude Sonnet 4.5 (Application Security Specialist)
**Review Date**: 2026-02-11
**Recommendation**: DO NOT IMPLEMENT until CRITICAL and HIGH vulnerabilities are mitigated.

**Required Actions Before Implementation**:
1. Implement all "MUST FIX" mitigations
2. Code review by senior developer
3. Security testing per checklist above
4. Update plan document with secure code examples

**Follow-up Review**: Required after implementation, before production deployment.

---

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [WordPress Codex: Validating Sanitizing and Escaping](https://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data)
- [WordPress Developer Handbook: Security](https://developer.wordpress.org/apis/security/)
- [CWE-601: Open Redirect](https://cwe.mitre.org/data/definitions/601.html)
- [CWE-79: Cross-site Scripting (XSS)](https://cwe.mitre.org/data/definitions/79.html)
- [CWE-22: Path Traversal](https://cwe.mitre.org/data/definitions/22.html)
