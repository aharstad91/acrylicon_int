---
title: "Security Quick Reference: Language Switcher"
date: 2026-02-11
type: quick-reference
---

# Language Switcher Security Quick Reference Card

**Keep this card visible while implementing the language switcher**

---

## Critical Security Rules

### Rule 1: NEVER Use Raw `$_SERVER['REQUEST_URI']`

```php
// ❌ WRONG - Vulnerable to injection
$uri = $_SERVER['REQUEST_URI'];

// ✅ CORRECT - Always parse through WordPress
$uri = wp_parse_url(home_url($_SERVER['REQUEST_URI']), PHP_URL_PATH);
```

---

### Rule 2: ALWAYS Validate URLs Before Output

```php
// ❌ WRONG - Open redirect vulnerability
$url = 'https://acrylicon.no' . $user_path;
echo '<a href="' . $url . '">';

// ✅ CORRECT - Validate then escape
$url = get_home_url($blog_id, $path);
if (!acrylicon_validate_internal_url($url)) {
    $url = get_home_url($blog_id); // Fallback
}
echo '<a href="' . esc_url($url) . '">';
```

---

### Rule 3: ALWAYS Escape Output by Context

| Context | Escaping Function | Example |
|---------|------------------|---------|
| HTML href | `esc_url()` | `<a href="<?php echo esc_url($url); ?>">` |
| HTML attribute | `esc_attr()` | `<div data-lang="<?php echo esc_attr($code); ?>">` |
| HTML text | `esc_html()` | `<span><?php echo esc_html($label); ?></span>` |
| JavaScript string | `wp_json_encode()` | `var data = <?php echo wp_json_encode($array); ?>;` |

---

### Rule 4: ALWAYS Normalize Paths

```php
// ❌ WRONG - Path traversal possible
$path = str_replace('/norway/', '/', $_SERVER['REQUEST_URI']);

// ✅ CORRECT - Normalize and validate
$path = wp_normalize_path($parsed_path);
$path = str_replace(['%2e', '%2E'], '', $path); // Remove encoded dots
if (strpos($path, '..') !== false) {
    return '/'; // Block traversal
}
```

---

### Rule 5: NEVER Concatenate URLs

```php
// ❌ WRONG - Vulnerable to manipulation
$url = 'https://acrylicon.no' . $path;

// ✅ CORRECT - Use WordPress API
$url = get_home_url($blog_id, $path);
```

---

## Security Checklist (Before Commit)

Copy this into your PR description:

```markdown
## Security Checklist

- [ ] All `$_SERVER['REQUEST_URI']` parsed through `wp_parse_url()` and `home_url()`
- [ ] All URLs constructed with `get_home_url()` (never manual concatenation)
- [ ] All URLs validated with `acrylicon_validate_internal_url()`
- [ ] All URLs output with `esc_url()`
- [ ] All HTML attributes output with `esc_attr()`
- [ ] All text content output with `esc_html()`
- [ ] All paths normalized with `wp_normalize_path()`
- [ ] Path traversal blocked (check for `..`)
- [ ] WordPress reserved paths blocked (`wp-admin`, `wp-content`, `.php`)
- [ ] JavaScript uses NO `innerHTML`, `eval`, or `document.write()`
- [ ] Tested: `/norway/../../../wp-config.php` → fallback to homepage
- [ ] Tested: `//evil.com` in URL → fallback to homepage
- [ ] Tested: Page source shows properly escaped hreflang tags
```

---

## Common Vulnerabilities & Quick Fixes

### Open Redirect

**Symptom**: User can craft URL that redirects to external site

**Fix**:
```php
function acrylicon_validate_internal_url($url) {
    $parsed = wp_parse_url($url);
    $allowed_hosts = ['acrylicon.no', 'www.acrylicon.no', /* add prod host */];
    return isset($parsed['host']) && in_array($parsed['host'], $allowed_hosts, true);
}

// Use before returning URL
if (!acrylicon_validate_internal_url($target_url)) {
    $target_url = get_home_url($blog_id);
}
```

---

### Path Traversal

**Symptom**: URL like `/norway/../../../etc/passwd` accesses files

**Fix**:
```php
$path = wp_normalize_path($path);
$path = str_replace(['%2e', '%2E'], '', $path);

// Block traversal attempts
if (strpos($path, '..') !== false) {
    return '/'; // Safe fallback
}

// Block reserved paths
$disallowed = ['wp-admin', 'wp-content', 'wp-includes', '.php'];
foreach ($disallowed as $pattern) {
    if (stripos($path, $pattern) !== false) {
        return '/';
    }
}
```

---

### XSS in Hreflang

**Symptom**: Malicious script in `<link rel="alternate">` tags

**Fix**:
```php
// BEFORE
echo '<link rel="alternate" hreflang="' . $lang . '" href="' . $url . '" />';

// AFTER
printf(
    '<link rel="alternate" hreflang="%s" href="%s" />',
    esc_attr($lang),
    esc_url($url)
);
```

---

### XSS in HTML Output

**Symptom**: Unescaped URLs in dropdown/footer

**Fix**:
```php
// BEFORE
<a href="<?php echo $url; ?>">
    <?php echo $label; ?>
</a>

// AFTER
<a href="<?php echo esc_url($url); ?>">
    <?php echo esc_html($label); ?>
</a>
```

---

### SVG XSS

**Symptom**: Malicious SVG with scripts/event handlers

**Fix**: Use enhanced `svg_icon()` from `language-switcher-secure-implementation.php`

Key additions:
- Strip event handlers: `/on\w+\s*=\s*["'][^"']*["']/i`
- Block path traversal: `realpath()` + containment check
- Remove `<use>`, `<foreignObject>`, `<animate>`

---

### DOM XSS

**Symptom**: JavaScript manipulates HTML with user input

**Fix**: NEVER use these in language switcher JS:
- `innerHTML` ❌
- `outerHTML` ❌
- `document.write()` ❌
- `eval()` ❌

ALWAYS use:
- `classList.add()`/`classList.remove()` ✅
- `setAttribute()` ✅
- `textContent` ✅

---

## Testing Commands

### Test Open Redirect
```bash
curl -I "https://acrylicon.no//evil.com"
# Expected: Redirect to https://acrylicon.no/ (not evil.com)
```

### Test Path Traversal
```bash
curl -s "https://acrylicon.no/norway/../../../wp-config.php"
# Expected: Homepage content (not file contents)
```

### Test XSS in Hreflang
```bash
curl -s https://acrylicon.no/norway/produkter/ | grep 'rel="alternate"'
# Expected: All URLs properly encoded (no < > " ' unescaped)
```

### Test HTTPS Enforcement (Production)
```bash
curl -I "http://acrylicon.no/"
# Expected: 301 redirect to https://acrylicon.no/
```

---

## Emergency Procedures

### If Exploit Detected

1. **Immediate Action** (within 5 minutes):
   ```php
   // In functions.php, comment out:
   // acrylicon_render_language_switcher()

   // In header.php, comment out:
   // <?php acrylicon_render_language_switcher('header'); ?>
   ```

2. **Investigation** (within 1 hour):
   - Check error logs: `tail -f /path/to/error.log`
   - Check access logs for exploit attempts
   - Identify attack vector

3. **Hotfix** (within 4 hours):
   - Apply security patch
   - Re-test all security tests
   - Deploy to production

4. **Post-Incident** (within 24 hours):
   - Document incident
   - Update security testing
   - Review all similar code

---

## Who to Contact

| Issue | Contact | Action |
|-------|---------|--------|
| Security vulnerability found | Security Specialist | Immediate escalation |
| Uncertain about escaping | Senior Developer | Code review before commit |
| Test failing | Technical Lead | Block deployment |
| Production exploit | On-call Engineer | Rollback + hotfix |

---

## References (Keep These Bookmarked)

1. **Security Audit (Full Analysis)**
   `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/2026-02-11-language-switcher-security-audit.md`

2. **Secure Implementation (Copy-Paste Code)**
   `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/language-switcher-secure-implementation.php`

3. **Security Testing (70 Tests)**
   `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/language-switcher-security-testing.md`

4. **WordPress Security Handbook**
   https://developer.wordpress.org/apis/security/

5. **OWASP Top 10**
   https://owasp.org/Top10/

---

## WordPress Escaping Cheat Sheet

| Function | Use Case | Example |
|----------|----------|---------|
| `esc_html()` | Plain text in HTML | `<p><?php echo esc_html($text); ?></p>` |
| `esc_attr()` | HTML attributes | `<div class="<?php echo esc_attr($class); ?>">` |
| `esc_url()` | URLs in href/src | `<a href="<?php echo esc_url($url); ?>">` |
| `esc_js()` | JavaScript strings | `<script>var x = '<?php echo esc_js($str); ?>';</script>` |
| `esc_textarea()` | Textarea content | `<textarea><?php echo esc_textarea($val); ?></textarea>` |
| `wp_kses_post()` | Allow some HTML | `<?php echo wp_kses_post($content); ?>` |
| `sanitize_text_field()` | Single-line input | `$clean = sanitize_text_field($_POST['input']);` |
| `wp_json_encode()` | JSON output | `var data = <?php echo wp_json_encode($arr); ?>;` |

**Golden Rule**: When in doubt, escape with `esc_html()` — it's the safest default.

---

**Print this card and keep it visible during development!**

**Last Updated**: 2026-02-11
**Version**: 1.0
