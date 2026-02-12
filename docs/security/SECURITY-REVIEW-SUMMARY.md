---
title: "Security Review Summary: Language Switcher"
date: 2026-02-11
reviewer: Claude Sonnet 4.5 (Application Security Specialist)
status: REVIEW COMPLETE - REQUIRES MITIGATION
severity: MEDIUM-HIGH
---

# Language Switcher Security Review Summary

## Executive Decision

**🔴 DO NOT IMPLEMENT** the language switcher as currently planned without security mitigations.

**Risk Level**: MEDIUM-HIGH
**Primary Concerns**: Open redirect, path traversal, XSS in hreflang tags

---

## Critical Findings

### 1. CRITICAL: Open Redirect Vulnerability (CWE-601)

**Location**: `acrylicon_get_equivalent_url()` function

**Vulnerability**: The plan calls for parsing `$_SERVER['REQUEST_URI']` and constructing target URLs without validating the resulting URL is internal to the WordPress installation.

**Exploit**: Attacker can craft URLs like `//evil.com` or `@phishing.site` that bypass naive URL construction and redirect users to external sites.

**Impact**:
- Phishing attacks using legitimate domain trust
- Credential harvesting
- SEO poisoning via malicious hreflang tags

**Mitigation**: REQUIRED before implementation
- Use `wp_parse_url()` and `home_url()` instead of raw `$_SERVER`
- Validate all constructed URLs against whitelist of allowed hosts
- Use WordPress API (`get_home_url()`) for URL construction

---

### 2. HIGH: Path Traversal (CWE-22)

**Location**: URL parsing logic

**Vulnerability**: URL paths are not normalized before processing, allowing `../` sequences to traverse directories.

**Exploit**: `https://acrylicon.no/norway/../../../wp-config.php`

**Impact**:
- Access to sensitive WordPress files
- Exposure of wp-config.php credentials
- Bypass of blog prefix restrictions

**Mitigation**: REQUIRED before implementation
- Use `wp_normalize_path()` on all paths
- Remove URL-encoded traversal sequences (`%2e%2e%2f`)
- Block WordPress reserved paths (`wp-admin`, `wp-content`, `.php`)

---

### 3. MEDIUM: XSS in Hreflang Tags (CWE-79)

**Location**: `acrylicon_hreflang_tags()` function

**Vulnerability**: URLs output in `<head>` without proper escaping.

**Exploit**: If URL contains `"><script>alert(1)</script><a href="`, XSS executes in `<head>` context.

**Impact**:
- Stored XSS via malicious database content
- Session hijacking
- Cookie theft

**Mitigation**: REQUIRED before implementation
- Use `esc_url()` for all href attributes
- Use `esc_attr()` for all hreflang attributes
- Validate URL scheme before output

---

### 4. MEDIUM: Insufficient Slug Validation

**Location**: `acrylicon_slug_map()` lookup logic

**Vulnerability**: Slug values from URL are not validated before use.

**Exploit**: Special characters, SQL patterns, or path traversal sequences in slugs.

**Mitigation**: REQUIRED before implementation
- Regex validation: `/^[a-z0-9_-]+$/i` (alphanumeric + hyphen/underscore only)
- Length limit: 200 characters max
- Reject invalid slugs before database lookup

---

### 5. LOW: SVG Security (ACCEPTABLE)

**Location**: Existing `svg_icon()` function

**Current Status**: ADEQUATE - basic sanitization present (strips scripts, styles, data URIs)

**Enhancement Recommended**: Add path traversal protection and event handler stripping

---

## Security Mitigations Required

### Must Implement (Blocking)

1. **URL Validation Pipeline**
   - Parse with `wp_parse_url()` and `home_url()`
   - Normalize with `wp_normalize_path()`
   - Validate against host whitelist
   - Enforce HTTPS on production

2. **Output Escaping**
   - `esc_url()` for all href attributes
   - `esc_attr()` for all HTML attributes
   - `esc_html()` for all text content

3. **Input Validation**
   - Slug regex: `/^[a-z0-9_-]+$/i`
   - Length limits: 200 chars max
   - Block reserved paths: `wp-admin`, `wp-content`, `.php`

4. **Error Handling**
   - Fallback to homepage on invalid input
   - Log security events in debug mode
   - No error disclosure to users

### Should Implement (Recommended)

5. **SVG Hardening**
   - Path traversal prevention (`realpath()` + containment check)
   - Event handler stripping (`onclick`, `onload`, etc.)
   - JavaScript protocol removal

6. **Security Headers**
   - X-Content-Type-Options: nosniff
   - X-Frame-Options: SAMEORIGIN
   - Referrer-Policy: strict-origin-when-cross-origin

7. **wp-config.php Updates**
   - Set `FORCE_SSL_ADMIN` to `true` on production

---

## Documents Generated

Three security documents have been created:

### 1. Security Audit (Comprehensive Analysis)
**File**: `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/2026-02-11-language-switcher-security-audit.md`

**Contents**:
- Detailed vulnerability analysis with exploit scenarios
- OWASP Top 10 compliance review
- Mitigation strategies with secure code examples
- Security requirements checklist
- Recommended enhancements

**Use for**: Understanding vulnerabilities and mitigations

---

### 2. Secure Implementation (Copy-Paste Ready Code)
**File**: `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/language-switcher-secure-implementation.php`

**Contents**:
- Production-ready PHP functions with security hardening
- Secure URL parsing, validation, and construction
- Properly escaped rendering functions
- Secure JavaScript for dropdown
- Enhanced SVG icon function

**Use for**: Copy functions directly to `functions.php` during implementation

---

### 3. Security Testing Checklist (70 Tests)
**File**: `/Applications/MAMP/htdocs/acrylicon/wp-content/docs/security/language-switcher-security-testing.md`

**Contents**:
- 70 specific security tests covering all attack vectors
- Open redirect, path traversal, XSS, input validation tests
- Manual testing commands (cURL, grep, etc.)
- Automated test script
- Production readiness checklist

**Use for**: Validate implementation before deployment

---

## Implementation Workflow

### Phase 1: Security Review (COMPLETE)
- [x] Identify attack vectors
- [x] Document vulnerabilities
- [x] Create secure implementation
- [x] Design testing strategy

### Phase 2: Secure Implementation (PENDING)
- [ ] Copy functions from `language-switcher-secure-implementation.php` to `functions.php`
- [ ] Create SVG icons (globe, flags) in `/assets/gfx/`
- [ ] Update `header.php` with secure rendering
- [ ] Update `footer.php` with secure rendering
- [ ] Add secure JavaScript for dropdown
- [ ] Set `FORCE_SSL_ADMIN` to `true` in `wp-config.php` (production)

### Phase 3: Security Testing (PENDING)
- [ ] Complete all 70 tests from security testing checklist
- [ ] Verify 0 failures in critical categories (open redirect, path traversal, XSS)
- [ ] Review error logs for security events
- [ ] Performance testing (< 50ms impact)

### Phase 4: Code Review (PENDING)
- [ ] Senior developer review of security implementation
- [ ] Peer review of test results
- [ ] Security audit sign-off

### Phase 5: Deployment (PENDING)
- [ ] Deploy to staging first
- [ ] Re-run security tests on staging
- [ ] Monitor error logs for 24 hours
- [ ] Deploy to production
- [ ] Post-deployment security validation

---

## Risk Matrix

| Vulnerability | Severity | Exploitability | Impact | Mitigation Status |
|---|---|---|---|---|
| Open Redirect | CRITICAL | High | High | Documented |
| Path Traversal | HIGH | Medium | High | Documented |
| XSS in Hreflang | MEDIUM | Low | Medium | Documented |
| Slug Validation | MEDIUM | Medium | Low | Documented |
| SVG Security | LOW | Low | Low | Adequate (enhancement available) |

**Overall Risk**: MEDIUM-HIGH (before mitigation)
**Residual Risk**: LOW (after mitigation + testing)

---

## Sign-Off Requirements

Before production deployment, obtain approval from:

1. **Security Reviewer**: _________________ (Date: _________)
   - Confirms all CRITICAL and HIGH vulnerabilities mitigated
   - Reviews test results (70/70 pass required)

2. **Senior Developer**: _________________ (Date: _________)
   - Code review of security implementation
   - Validates WordPress best practices followed

3. **Project Lead**: _________________ (Date: _________)
   - Business approval for deployment
   - Acknowledges residual risks

---

## Post-Deployment Monitoring

For 30 days after deployment:

- **Daily**: Check error logs for security events
- **Weekly**: Review failed URL validations (if logging enabled)
- **Monthly**: Security audit of access logs

If any security issues detected:
1. Immediately disable language switcher (remove `acrylicon_render_language_switcher()` calls)
2. Review error logs for exploit attempts
3. Apply hotfix
4. Re-test per security checklist

---

## Questions & Answers

### Q: Can we skip the host whitelist validation?
**A**: NO. This is critical for preventing open redirect attacks. Without it, attackers can use your domain for phishing.

### Q: Is the existing SVG function secure enough?
**A**: YES, for controlled SVG files (globe, flags). The enhanced version is recommended but not blocking.

### Q: Do we need CSRF protection for language switching?
**A**: NO. Language switching is a GET request with no side effects. CSRF protection is not required.

### Q: Can we use `$_SERVER['REQUEST_URI']` directly?
**A**: NO. Always parse through `wp_parse_url()` and `home_url()` to prevent injection attacks.

### Q: What if a page doesn't exist on the target blog?
**A**: Fallback to homepage is secure and acceptable. UX enhancement (checking page existence) is optional.

---

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [CWE-601: Open Redirect](https://cwe.mitre.org/data/definitions/601.html)
- [CWE-22: Path Traversal](https://cwe.mitre.org/data/definitions/22.html)
- [CWE-79: Cross-Site Scripting](https://cwe.mitre.org/data/definitions/79.html)
- [WordPress Security Handbook](https://developer.wordpress.org/apis/security/)
- [WordPress Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [WordPress Escaping](https://developer.wordpress.org/apis/security/escaping/)

---

## Contact

**Security Reviewer**: Claude Sonnet 4.5 (Application Security Specialist)
**Review Date**: 2026-02-11
**Review Status**: COMPLETE - REQUIRES MITIGATION

**For questions about this security review**:
- Refer to detailed audit: `docs/security/2026-02-11-language-switcher-security-audit.md`
- Refer to secure implementation: `docs/security/language-switcher-secure-implementation.php`
- Refer to testing checklist: `docs/security/language-switcher-security-testing.md`

---

**CRITICAL REMINDER**: Do not implement without mitigations. All CRITICAL and HIGH vulnerabilities must be addressed before deployment.
