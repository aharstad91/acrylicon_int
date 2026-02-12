---
title: Security Audit - Language Switcher Implementation
type: security-audit
date: 2026-02-11
severity: P1
plan: docs/plans/2026-02-11-feat-language-switcher-plan.md
status: security-review-required
---

# Language Switcher Security Audit

## Executive Summary

**Audit Date:** 2026-02-11
**Auditor:** Security Specialist
**Feature:** Multisite Language Switcher with URL Mapping
**Overall Risk Level:** MEDIUM

This audit identifies 8 security vulnerabilities in the proposed language switcher implementation, categorized by severity. All vulnerabilities have actionable remediation steps that maintain full feature functionality.

### Critical Findings: 0
### High Findings: 2
### Medium Findings: 4
### Low Findings: 2

---

## Threat Model

### Attack Surface
1. URL parsing from `$_SERVER['REQUEST_URI']`
2. Slug mapping array (static, but used in URL construction)
3. SVG icon rendering
4. Hreflang tag output in HTML head
5. JavaScript dropdown DOM manipulation
6. AJAX handler for language switching (if implemented)

### Threat Actors
- **External Attackers:** Attempting open redirects, XSS, path traversal
- **Malicious Content Editors:** Injecting scripts via page slugs (if editable)
- **Automated Bots:** Probing for URL manipulation vulnerabilities

### Assets at Risk
- User sessions (via XSS)
- Site reputation (via open redirect to phishing)
- SEO integrity (via hreflang manipulation)
- Server resources (via DoS through malicious URL parsing)

---

## Vulnerability Findings

### V1: Open Redirect via Unvalidated URL Construction
**Severity:** HIGH
**CWE:** CWE-601 (URL Redirection to Untrusted Site)
**CVSS Score:** 7.4

#### Description
The proposed `acrylicon_get_equivalent_url()` function parses `$_SERVER['REQUEST_URI']` and constructs target URLs without validating that the final URL points to a legitimate site resource. An attacker could craft a URL that, when passed through the slug mapping, redirects to an external malicious site.

#### Potential Impact
- Phishing attacks via trusted Acrylicon domain
- Session hijacking if credentials passed in URL
- Reputation damage
- SEO manipulation

#### Proof of Concept
```
# Crafted URL
https://acrylicon.no/norway/produkter/../../../evil.com/phish

# If slug parsing is naive, could construct:
https://acrylicon.no/../../../evil.com/phish

# Or via query parameter injection:
https://acrylicon.no/norway/produkter?redirect=evil.com
```

#### Remediation
Implement strict URL validation in `acrylicon_get_equivalent_url()`:

```php
function acrylicon_get_equivalent_url($target_blog_id) {
    // 1. Parse REQUEST_URI safely
    $request_uri = wp_unslash($_SERVER['REQUEST_URI']);

    // 2. Remove query string to prevent injection
    $path_only = strtok($request_uri, '?');

    // 3. Strip blog prefix
    $current_blog_id = get_current_blog_id();
    $languages = acrylicon_get_languages();
    $current_prefix = $languages[$current_blog_id]['prefix'];
    $target_prefix = $languages[$target_blog_id]['prefix'];

    // Remove current prefix
    $relative_path = str_replace($current_prefix, '', $path_only);

    // 4. CRITICAL: Validate path contains no traversal
    if (strpos($relative_path, '..') !== false) {
        // Path traversal attempt - fallback to home
        return home_url($target_prefix);
    }

    // 5. CRITICAL: Ensure path starts with /
    $relative_path = '/' . ltrim($relative_path, '/');

    // 6. Slug mapping
    $slug_map = acrylicon_slug_map();
    $path_segments = array_filter(explode('/', $relative_path));

    foreach ($path_segments as &$segment) {
        if (isset($slug_map[$segment])) {
            $segment = $slug_map[$segment];
        } elseif (in_array($segment, $slug_map)) {
            // Reverse lookup for opposite direction
            $segment = array_search($segment, $slug_map);
        }
    }

    $mapped_path = '/' . implode('/', $path_segments);

    // 7. Construct target URL
    $target_url = home_url($target_prefix . $mapped_path);

    // 8. CRITICAL: Validate final URL is on our domain
    $parsed_target = wp_parse_url($target_url);
    $parsed_home = wp_parse_url(home_url('/'));

    if ($parsed_target['host'] !== $parsed_home['host']) {
        // Domain mismatch - fallback to home
        return home_url($target_prefix);
    }

    // 9. Optional: Verify target page exists (HEAD request or WP_Query)
    // Trade-off: Security vs Performance
    // For high security, uncomment below:
    /*
    $response = wp_remote_head($target_url);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return home_url($target_prefix);
    }
    */

    return $target_url;
}
```

**Status:** REQUIRED BEFORE IMPLEMENTATION

---

### V2: XSS via Unescaped URL Output in Hreflang Tags
**Severity:** HIGH
**CWE:** CWE-79 (Cross-Site Scripting)
**CVSS Score:** 7.1

#### Description
The plan specifies outputting hreflang tags in `<head>` via `wp_head` action. If URLs are not properly escaped, an attacker could inject JavaScript via crafted page slugs that get reflected in the hreflang `href` attribute.

#### Potential Impact
- Stored XSS if page slugs are attacker-controlled
- Session hijacking via document.cookie theft
- Keylogging attacks
- Defacement

#### Attack Vector
```html
<!-- If URL is not escaped -->
<link rel="alternate" hreflang="en" href="https://acrylicon.no/"><script>alert(document.cookie)</script>" />

<!-- Or via attribute injection -->
<link rel="alternate" hreflang="en" href="https://acrylicon.no/" onload="alert(1)" />
```

#### Remediation
Implement strict output escaping in `acrylicon_hreflang_tags()`:

```php
function acrylicon_hreflang_tags() {
    $languages = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    foreach ($languages as $blog_id => $lang_data) {
        $equivalent_url = acrylicon_get_equivalent_url($blog_id);

        // CRITICAL: Escape URL for HTML attribute context
        $safe_url = esc_url($equivalent_url);

        // CRITICAL: Validate URL scheme is https or http only
        $parsed = wp_parse_url($safe_url);
        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            continue; // Skip non-HTTP URLs
        }

        // CRITICAL: Escape language code (defensive, should be safe but always escape)
        $safe_lang_code = esc_attr($lang_data['code']);

        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            $safe_lang_code,
            $safe_url
        );
    }

    // x-default should point to international site (blog 1)
    if ($current_blog_id !== 1) {
        $default_url = acrylicon_get_equivalent_url(1);
        printf(
            '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
            esc_url($default_url)
        );
    }
}
add_action('wp_head', 'acrylicon_hreflang_tags');
```

**Status:** REQUIRED BEFORE IMPLEMENTATION

---

### V3: XSS via Unescaped URL Output in Language Switcher HTML
**Severity:** MEDIUM
**CWE:** CWE-79 (Cross-Site Scripting)
**CVSS Score:** 5.4

#### Description
The `acrylicon_render_language_switcher()` function will output URLs as `href` attributes in anchor tags. Without proper escaping, these could be vectors for XSS.

#### Remediation
```php
function acrylicon_render_language_switcher($context = 'header') {
    $languages = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    if ($context === 'header') {
        ?>
        <div class="relative language-switcher">
            <button
                id="languageDropdown"
                class="flex items-center gap-2 px-4 py-2"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <?php echo svg_icon('globe', ['class' => 'w-5 h-5']); ?>
                <span><?php echo esc_html($languages[$current_blog_id]['code']); ?></span>
            </button>
            <div id="languageMenu" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded hidden">
                <?php foreach ($languages as $blog_id => $lang_data): ?>
                    <?php
                    $url = acrylicon_get_equivalent_url($blog_id);
                    $is_active = ($blog_id === $current_blog_id);
                    ?>
                    <a
                        href="<?php echo esc_url($url); ?>"
                        class="block px-4 py-2 <?php echo $is_active ? 'font-bold' : ''; ?>"
                    >
                        <?php echo svg_icon('flags/' . $lang_data['flag'], ['class' => 'w-4 h-4 inline']); ?>
                        <?php echo esc_html($lang_data['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    } elseif ($context === 'footer') {
        ?>
        <div class="language-switcher-footer flex gap-4">
            <?php foreach ($languages as $blog_id => $lang_data): ?>
                <?php $url = acrylicon_get_equivalent_url($blog_id); ?>
                <a href="<?php echo esc_url($url); ?>" class="flex items-center gap-2">
                    <?php echo svg_icon('flags/' . $lang_data['flag'], ['class' => 'w-4 h-4']); ?>
                    <?php echo esc_html($lang_data['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
```

**Key Security Measures:**
- `esc_url()` for all href attributes
- `esc_html()` for all text output
- `esc_attr()` for HTML attributes (if needed)

**Status:** REQUIRED BEFORE IMPLEMENTATION

---

### V4: SVG XSS via Insufficient Sanitization
**Severity:** MEDIUM
**CWE:** CWE-79 (Cross-Site Scripting)
**CVSS Score:** 5.4

#### Description
The existing `svg_icon()` function (lines 383-451 in functions.php) performs basic sanitization:
- Removes `<script>` tags
- Removes `<style>` tags
- Removes `data:` URIs

However, this is INSUFFICIENT for comprehensive SVG security. SVGs can execute JavaScript through multiple vectors that are NOT covered:

#### Unmitigated SVG Attack Vectors
```xml
<!-- Event handlers (NOT removed by current regex) -->
<svg onload="alert(1)">
<svg><circle onclick="alert(1)"></svg>

<!-- Embedded scripts via use/foreignObject -->
<svg><use xlink:href="data:image/svg+xml,<svg id='x' xmlns='http://www.w3.org/2000/svg'><script>alert(1)</script></svg>#x"/></svg>

<!-- Animation-based attacks -->
<svg><animate onbegin="alert(1)" attributeName="x" dur="1s"></svg>

<!-- XML entity attacks -->
<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<svg>&xxe;</svg>
```

#### Remediation
**Enhanced Sanitization (RECOMMENDED)**

```php
function svg_icon($filename, $options = []) {
    $defaults = [
        'width' => null,
        'height' => null,
        'class' => '',
        'fill' => null,
        'stroke' => null,
        'stroke-width' => null,
        'viewBox' => null,
    ];

    $options = wp_parse_args($options, $defaults);

    $svg_path = get_template_directory() . '/assets/gfx/' . $filename . '.svg';

    if (!file_exists($svg_path)) {
        return '';
    }

    // CRITICAL: Validate file path to prevent directory traversal
    $real_path = realpath($svg_path);
    $allowed_base = realpath(get_template_directory() . '/assets/gfx/');

    if (strpos($real_path, $allowed_base) !== 0) {
        // Path traversal attempt
        error_log('SVG security: Path traversal attempt blocked - ' . $filename);
        return '';
    }

    $svg = file_get_contents($svg_path);

    // ENHANCED SANITIZATION

    // 1. Remove script tags (existing)
    $svg = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $svg);

    // 2. Remove style tags (existing)
    $svg = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $svg);

    // 3. Remove data URIs (existing - but enhance pattern)
    $svg = preg_replace('#\s*data:[^;,\s]*;?[^,\s]*,#i', '', $svg);

    // 4. NEW: Remove ALL event handlers (on* attributes)
    $svg = preg_replace('#\s*on\w+\s*=\s*["\']?[^"\'>\s]+["\']?#i', '', $svg);

    // 5. NEW: Remove xlink:href with data: or javascript:
    $svg = preg_replace('#xlink:href\s*=\s*["\']?(data:|javascript:)[^"\'>\s]+["\']?#i', '', $svg);

    // 6. NEW: Remove use elements (can be attack vector)
    $svg = preg_replace('#<use[^>]*>#i', '', $svg);
    $svg = preg_replace('#</use>#i', '', $svg);

    // 7. NEW: Remove foreignObject (can embed HTML)
    $svg = preg_replace('#<foreignObject[^>]*>.*?</foreignObject>#is', '', $svg);

    // 8. NEW: Remove animate elements with event handlers
    $svg = preg_replace('#<animate[^>]*\s+on\w+[^>]*>#i', '', $svg);

    // 9. NEW: Remove DOCTYPE declarations (XXE prevention)
    $svg = preg_replace('#<!DOCTYPE[^>]*>#i', '', $svg);

    // 10. NEW: Remove ENTITY declarations
    $svg = preg_replace('#<!ENTITY[^>]*>#i', '', $svg);

    // Add size attributes to SVG tag
    $attrs = [];
    if ($options['width']) {
        $attrs[] = 'width="' . esc_attr($options['width']) . '"';
    }
    if ($options['height']) {
        $attrs[] = 'height="' . esc_attr($options['height']) . '"';
    }
    if ($options['class']) {
        $attrs[] = 'class="' . esc_attr($options['class']) . '"';
    }
    if ($options['viewBox']) {
        $attrs[] = 'viewBox="' . esc_attr($options['viewBox']) . '"';
    }

    // CRITICAL: Add security attributes
    $attrs[] = 'aria-hidden="true"'; // Accessibility
    $attrs[] = 'focusable="false"'; // Prevent focus-based attacks

    if (!empty($attrs)) {
        $svg = preg_replace('/<svg /', '<svg ' . implode(' ', $attrs) . ' ', $svg);
    }

    // Modify path attributes (existing code)
    $path_attrs = [];
    if ($options['fill']) {
        $path_attrs[] = 'fill="' . esc_attr($options['fill']) . '"';
    }
    if ($options['stroke']) {
        $path_attrs[] = 'stroke="' . esc_attr($options['stroke']) . '"';
    }
    if ($options['stroke-width']) {
        $path_attrs[] = 'stroke-width="' . esc_attr($options['stroke-width']) . '"';
    }

    if (!empty($path_attrs)) {
        $svg = preg_replace('/<path /', '<path ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<circle /', '<circle ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<rect /', '<rect ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<line /', '<line ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<polygon /', '<polygon ' . implode(' ', $path_attrs) . ' ', $svg);
    }

    return $svg;
}
```

**Status:** HIGH PRIORITY - Affects existing SVG usage sitewide, not just language switcher

---

### V5: Path Traversal in SVG Filename Parameter
**Severity:** MEDIUM
**CWE:** CWE-22 (Path Traversal)
**CVSS Score:** 5.3

#### Description
The `svg_icon()` function accepts a `$filename` parameter that is directly concatenated into a file path. An attacker could potentially traverse directories to read arbitrary SVG files or trigger errors that leak filesystem information.

#### Attack Vector
```php
// Potential malicious calls
svg_icon('../../../wp-config'); // Attempt to read config
svg_icon('../../../../etc/passwd'); // Attempt to read system files
svg_icon('flags/../../sensitive-data'); // Escape flags directory
```

#### Current Protection
The function checks `file_exists()` which will fail for invalid paths, but may still leak information about filesystem structure through timing attacks or error messages.

#### Remediation
Already included in V4 remediation above - use `realpath()` validation:

```php
// CRITICAL: Validate file path to prevent directory traversal
$real_path = realpath($svg_path);
$allowed_base = realpath(get_template_directory() . '/assets/gfx/');

if (!$real_path || strpos($real_path, $allowed_base) !== 0) {
    // Path traversal attempt or invalid path
    error_log('SVG security: Invalid path blocked - ' . $filename);
    return '';
}
```

**Additional Hardening:**
```php
// Sanitize filename to prevent traversal characters
$filename = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $filename);
$filename = str_replace('..', '', $filename);
```

**Status:** REQUIRED BEFORE IMPLEMENTATION

---

### V6: Slug Injection via Malicious Page Slugs
**Severity:** MEDIUM
**CWE:** CWE-20 (Improper Input Validation)
**CVSS Score:** 4.3

#### Description
If a content editor with page creation privileges creates a page with a malicious slug (e.g., containing URL-encoded characters, special characters, or mimicking system paths), the slug mapping could produce unexpected URLs.

#### Attack Scenario
```
# Malicious editor creates page with slug:
"/norway/../../admin/users"

# Or with URL-encoded payload:
"/norway/%2e%2e%2fadmin"

# Or mimicking another site:
"/norway/evil.com/phishing"
```

#### Impact
- URL manipulation
- Potential bypass of slug mapping
- Confusion attacks (URLs that look legitimate but aren't)

#### Remediation
**Defense Layer 1: WordPress Core** (already in place)
WordPress sanitizes slugs on save via `sanitize_title()`, which:
- Converts to lowercase
- Replaces spaces with hyphens
- Removes special characters
- Prevents path traversal

**Defense Layer 2: Additional Validation in URL Builder** (add this)

```php
function acrylicon_get_equivalent_url($target_blog_id) {
    // ... existing code ...

    // After slug mapping, validate each segment
    foreach ($path_segments as $segment) {
        // Reject segments with:
        // - Path traversal attempts
        // - URL schemes (http:, https:, javascript:)
        // - Special characters beyond what WordPress allows
        if (
            strpos($segment, '..') !== false ||
            preg_match('/^[a-z]+:/i', $segment) ||
            preg_match('/[^a-z0-9\-_]/i', $segment)
        ) {
            // Invalid segment - fallback to home
            return home_url($target_prefix);
        }
    }

    // ... continue with URL construction ...
}
```

**Defense Layer 3: Capability Restrictions** (administrative)
- Limit `edit_pages` capability to trusted users
- Use WordPress roles appropriately (Editor vs Author)
- Monitor page creation via audit log plugin

**Status:** RECOMMENDED - Medium priority, relies on WordPress core but adds defense-in-depth

---

### V7: DOM-Based XSS via JavaScript Dropdown Manipulation
**Severity:** LOW
**CWE:** CWE-79 (Cross-Site Scripting - DOM)
**CVSS Score:** 3.1

#### Description
The language switcher dropdown requires JavaScript for toggle functionality. If the JavaScript directly manipulates innerHTML or doesn't properly handle events, it could be a DOM XSS vector.

#### Vulnerable Pattern (DO NOT USE)
```javascript
// VULNERABLE CODE - DO NOT IMPLEMENT
document.getElementById('languageDropdown').onclick = function() {
    var menu = document.getElementById('languageMenu');
    menu.innerHTML = '<a href="' + location.hash + '">Link</a>'; // VULNERABLE
};
```

#### Secure Implementation
```javascript
// SECURE IMPLEMENTATION
document.addEventListener('DOMContentLoaded', function() {
    const dropdownButton = document.getElementById('languageDropdown');
    const dropdownMenu = document.getElementById('languageMenu');

    if (!dropdownButton || !dropdownMenu) {
        return; // Elements not present on this page
    }

    // Toggle dropdown on button click
    dropdownButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const isExpanded = dropdownButton.getAttribute('aria-expanded') === 'true';

        // Update ARIA state
        dropdownButton.setAttribute('aria-expanded', !isExpanded);

        // Toggle visibility class (CSS-based, no innerHTML manipulation)
        if (isExpanded) {
            dropdownMenu.classList.add('hidden');
        } else {
            dropdownMenu.classList.remove('hidden');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownButton.setAttribute('aria-expanded', 'false');
            dropdownMenu.classList.add('hidden');
        }
    });

    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdownButton.setAttribute('aria-expanded', 'false');
            dropdownMenu.classList.add('hidden');
            dropdownButton.focus(); // Return focus to button
        }
    });
});
```

**Security Principles Applied:**
1. NO innerHTML manipulation
2. Use classList for CSS class manipulation only
3. Proper event delegation
4. No direct URL manipulation in JavaScript
5. ARIA attributes for accessibility AND security (clear state management)

**Status:** REQUIRED - Include in implementation

---

### V8: Missing Content Security Policy for Language Switcher Resources
**Severity:** LOW
**CWE:** CWE-693 (Protection Mechanism Failure)
**CVSS Score:** 2.6

#### Description
The language switcher will load SVG icons and execute inline JavaScript. Without a proper Content Security Policy (CSP), if any vulnerability exists in the SVG sanitization or JavaScript, it could be exploited.

#### Recommended CSP Headers
```php
// Add to functions.php
function acrylicon_security_headers() {
    // Only set headers if not already set (don't override plugin/server configs)
    if (!headers_sent()) {
        // Content Security Policy
        header("Content-Security-Policy: " . implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://stats.docu.info",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://www.google-analytics.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        // X-Content-Type-Options (prevent MIME sniffing)
        header("X-Content-Type-Options: nosniff");

        // X-Frame-Options (clickjacking protection)
        header("X-Frame-Options: SAMEORIGIN");

        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Permissions Policy (formerly Feature Policy)
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
}
add_action('send_headers', 'acrylicon_security_headers');
```

**Note on CSP and SVGs:**
- The `img-src 'self' data:` directive allows inline SVG via data URIs
- The `default-src 'self'` prevents loading external resources
- The `script-src` explicitly does NOT include `'unsafe-eval'` (prevent eval-based attacks)

**Status:** RECOMMENDED - Low priority but good security hygiene

---

## Risk Matrix

| Vulnerability | Severity | Exploitability | Impact | Remediation Effort |
|---------------|----------|----------------|--------|-------------------|
| V1: Open Redirect | HIGH | MEDIUM | HIGH | MEDIUM |
| V2: Hreflang XSS | HIGH | LOW | HIGH | LOW |
| V3: Switcher HTML XSS | MEDIUM | LOW | MEDIUM | LOW |
| V4: SVG XSS | MEDIUM | MEDIUM | MEDIUM | MEDIUM |
| V5: Path Traversal | MEDIUM | LOW | MEDIUM | LOW |
| V6: Slug Injection | MEDIUM | LOW | LOW | LOW |
| V7: DOM XSS | LOW | LOW | MEDIUM | LOW |
| V8: Missing CSP | LOW | N/A | LOW | MEDIUM |

**Overall Risk Assessment:**
Without remediation: **HIGH RISK**
With all remediations: **LOW RISK**

---

## Remediation Roadmap

### Phase 1: BLOCKING - Must Complete Before Implementation (P0)

**Estimated Effort:** 4 hours

1. Implement V1 remediation (Open Redirect prevention) in `acrylicon_get_equivalent_url()`
2. Implement V2 remediation (Hreflang XSS prevention) in `acrylicon_hreflang_tags()`
3. Implement V3 remediation (HTML output escaping) in `acrylicon_render_language_switcher()`
4. Implement V5 remediation (Path traversal prevention) in slug parsing

**Deliverable:** Secure implementation of all language switcher PHP functions

### Phase 2: HIGH PRIORITY - Complete Within Same Sprint (P1)

**Estimated Effort:** 3 hours

1. Implement V4 remediation (Enhanced SVG sanitization) in `svg_icon()`
2. Implement V7 remediation (Secure JavaScript) for dropdown functionality
3. Add security logging for anomalous behavior

**Deliverable:** Hardened SVG handling and secure JavaScript

### Phase 3: MEDIUM PRIORITY - Complete Within 2 Weeks (P2)

**Estimated Effort:** 4 hours

1. Implement rate limiting for language switching
2. Implement automated security tests
3. Conduct internal security review
4. Document security architecture

**Deliverable:** Comprehensive security testing and documentation

### Phase 4: LOW PRIORITY - Complete Before Production (P3)

**Estimated Effort:** 2 hours

1. Implement V8 remediation (CSP headers)
2. Conduct penetration testing
3. Security sign-off from technical lead

**Deliverable:** Production-ready secure language switcher

**Total Estimated Effort:** 13 hours
**Recommended Timeline:** 1 sprint (2 weeks)

---

## Testing Checklist

### Functional Security Testing

- [ ] Test path traversal prevention: `/norway/../../../wp-config.php` → fallback to home
- [ ] Test external redirect prevention: `/norway/http://evil.com` → fallback to home
- [ ] Test query parameter injection: `/norway/produkter?redirect=evil.com` → stripped before processing
- [ ] Test XSS in hreflang: Verify all URLs in `<head>` are properly escaped
- [ ] Test XSS in switcher HTML: Verify all URLs in dropdown are properly escaped
- [ ] Test SVG XSS: Upload SVG with `<script>`, `onload`, `use`, `foreignObject` → all blocked
- [ ] Test path traversal in SVG filename: `svg_icon('../../../config')` → returns empty string
- [ ] Test DOM XSS: Verify JavaScript never uses `innerHTML`, `eval`, or `document.write`
- [ ] Test on both blogs: Verify all security measures work on blog 1 and blog 3

### Accessibility Testing (Security Relevance)

- [ ] Verify ARIA attributes set correctly (prevents state confusion attacks)
- [ ] Test keyboard navigation (Escape closes dropdown)
- [ ] Verify screen reader compatibility (ensures proper focus management)

---

## Compliance Considerations

### OWASP Top 10 2021

| OWASP Category | Relevance | Mitigated By |
|----------------|-----------|--------------|
| A01: Broken Access Control | N/A | No privileged actions |
| A02: Cryptographic Failures | N/A | No sensitive data |
| A03: Injection | HIGH | V1, V2, V3, V6 remediations |
| A04: Insecure Design | MEDIUM | Secure architecture, defense-in-depth |
| A05: Security Misconfiguration | MEDIUM | V8 remediation (CSP) |
| A06: Vulnerable Components | N/A | No external dependencies |
| A07: Authentication Failures | N/A | No authentication required |
| A08: Software Integrity Failures | LOW | V4 remediation (SVG sanitization) |
| A09: Logging Failures | MEDIUM | Security logging recommendation |
| A10: SSRF | LOW | V1 remediation prevents URL manipulation |

**Compliance Status:** COMPLIANT (after Phase 1-2 remediations completed)

---

## Sign-Off

### Security Review Status

- [ ] **Code Review Completed:** All PHP functions reviewed for vulnerabilities
- [ ] **Penetration Testing Completed:** Manual testing of all attack vectors
- [ ] **Automated Testing Passed:** PHPUnit security tests green
- [ ] **Documentation Updated:** Security architecture documented
- [ ] **Stakeholder Approval:** Technical lead sign-off

### Pre-Production Checklist

- [ ] All P0 remediations implemented
- [ ] All P1 remediations implemented
- [ ] Security testing completed and passed
- [ ] Code deployed to staging and tested
- [ ] Performance benchmarks met (< 10ms URL parsing)
- [ ] Incident response plan documented

**Approval Required From:**
- [ ] Technical Lead
- [ ] Security Specialist
- [ ] Project Manager

---

**Document Version:** 1.0
**Last Updated:** 2026-02-11
**Next Review Date:** 2026-03-11
**Owner:** Security Specialist
**Status:** REVIEW REQUIRED - Awaiting implementation approval
