---
title: "Security Testing Checklist: Language Switcher"
date: 2026-02-11
type: security-testing
status: READY
---

# Language Switcher Security Testing Checklist

## Overview

This checklist must be completed before deploying the language switcher to production. Each test is designed to validate specific security mitigations from the security audit.

**Tester**: _________________
**Test Date**: _________________
**Environment**: [ ] Localhost [ ] Staging [ ] Production
**Code Version**: _________________

---

## Pre-Testing Setup

- [ ] Security audit document reviewed: `docs/security/2026-02-11-language-switcher-security-audit.md`
- [ ] Secure implementation code deployed: `docs/security/language-switcher-secure-implementation.php`
- [ ] WordPress `WP_DEBUG` set to `true` for testing
- [ ] Error log monitoring enabled
- [ ] Browser DevTools open (Console + Network tabs)

---

## 1. Open Redirect Vulnerability Tests

**Objective**: Verify URL validation prevents redirects to external sites.

### Test 1.1: Protocol-Relative Open Redirect
```
URL: https://acrylicon.no//evil.com
Expected: Redirect to https://acrylicon.no/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 1.2: Double-Slash Open Redirect
```
URL: https://acrylicon.no/norway//@evil.com/phishing
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 1.3: HTTPS Open Redirect
```
URL: https://acrylicon.no/norway/https://evil.com
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 1.4: Subdomain Open Redirect
```
URL: https://acrylicon.no/norway/@attacker.acrylicon.no
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 1.5: Whitelist Validation
```
Action: Change language on valid page
URL: https://acrylicon.no/norway/produkter/
Click: English language switcher
Expected: https://acrylicon.no/products/ (valid internal URL)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] All redirect attempts lead to internal pages only
- [ ] Error log shows blocked external hosts
- [ ] No warnings in browser console

---

## 2. Path Traversal Tests

**Objective**: Verify path normalization prevents directory traversal attacks.

### Test 2.1: Dot-Dot-Slash Traversal
```
URL: https://acrylicon.no/norway/../../../etc/passwd
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 2.2: URL-Encoded Traversal
```
URL: https://acrylicon.no/norway/%2e%2e%2f%2e%2e%2fwp-config.php
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 2.3: Mixed Case Traversal
```
URL: https://acrylicon.no/norway/%2E%2E%2FWP-ADMIN/
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 2.4: Null Byte Injection
```
URL: https://acrylicon.no/norway/page%00.php
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 2.5: WordPress Reserved Paths
```
URL: https://acrylicon.no/norway/wp-content/uploads/
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] No access to wp-admin, wp-content, wp-includes
- [ ] No access to .php files directly
- [ ] Error log shows suspicious path detections

---

## 3. XSS (Cross-Site Scripting) Tests

**Objective**: Verify all output is properly escaped.

### Test 3.1: XSS in Hreflang Tags
```
Action: View page source
Check: <link rel="alternate" hreflang="..." href="..." />
Verify: All href values are properly escaped (no < > " ')
Search for: "><script> or javascript: in hreflang tags
Status: [ ] PASS [ ] FAIL
```

### Test 3.2: XSS in Language Dropdown
```
Action: Inspect language dropdown HTML
Check: All href, hreflang, aria-label attributes
Verify: No unescaped user input in attributes
Search for: onclick=, onerror=, javascript: in dropdown
Status: [ ] PASS [ ] FAIL
```

### Test 3.3: XSS in Footer Links
```
Action: Inspect footer language links
Check: All href and hreflang attributes
Verify: Proper esc_url() and esc_attr() usage
Status: [ ] PASS [ ] FAIL
```

### Test 3.4: SVG XSS
```
Action: Inspect SVG icons (globe, flags) in page source
Search for: <script>, <style>, onclick=, onerror= inside SVG
Verify: No inline scripts or event handlers in SVG
Status: [ ] PASS [ ] FAIL
```

### Test 3.5: DOM-Based XSS
```
Action: Open browser console, check dropdown toggle JavaScript
Verify: No eval(), innerHTML, or document.write() usage
Test: Click language switcher multiple times
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] All dynamic content escaped in appropriate context
- [ ] No JavaScript errors in console
- [ ] No CSP violations (if CSP headers enabled)

---

## 4. Input Validation Tests

**Objective**: Verify slug validation prevents injection attacks.

### Test 4.1: Special Characters in Slug
```
URL: https://acrylicon.no/norway/<script>alert(1)</script>/
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 4.2: SQL Injection Pattern in Slug
```
URL: https://acrylicon.no/norway/'; DROP TABLE wp_posts;--/
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 4.3: Long Slug (DoS Attempt)
```
URL: https://acrylicon.no/norway/[301+ characters]
Expected: Redirect to https://acrylicon.no/norway/ (homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 4.4: Valid Slug Mapping
```
URL: https://acrylicon.no/norway/produkter/
Click: English language switcher
Expected: https://acrylicon.no/products/
Verify: Slug mapping works correctly
Status: [ ] PASS [ ] FAIL
```

### Test 4.5: Unmapped Slug Fallback
```
URL: https://acrylicon.no/norway/nonexistent-page/
Click: English language switcher
Expected: https://acrylicon.no/ (fallback to homepage)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] Invalid slugs rejected before database lookup
- [ ] Valid slugs mapped correctly
- [ ] Unmapped slugs fallback gracefully

---

## 5. HTTPS Enforcement Tests

**Objective**: Verify HTTPS is enforced on production.

### Test 5.1: HTTP to HTTPS Redirect (Production Only)
```
URL: http://acrylicon.no/norway/produkter/
Expected: Auto-redirect to https://acrylicon.no/norway/produkter/
Actual: _________________
Status: [ ] PASS [ ] FAIL [ ] N/A (localhost)
```

### Test 5.2: Mixed Content Check
```
Action: Open DevTools Console
Check: No "Mixed Content" warnings
Verify: All language switcher URLs use HTTPS
Status: [ ] PASS [ ] FAIL [ ] N/A (localhost)
```

### Test 5.3: Hreflang HTTPS Validation
```
Action: View page source
Check: All <link rel="alternate" hreflang="..."> tags
Verify: All URLs start with https://
Status: [ ] PASS [ ] FAIL [ ] N/A (localhost)
```

**Verification (Production Only)**:
- [ ] All URLs use HTTPS scheme
- [ ] No mixed content warnings
- [ ] HSTS header present (optional)

---

## 6. SVG Security Tests

**Objective**: Verify SVG sanitization prevents XSS.

### Test 6.1: SVG Path Traversal
```
Action: Manually call svg_icon() with malicious filename
Code: svg_icon('../../wp-config')
Expected: Empty string returned
Status: [ ] PASS [ ] FAIL
```

### Test 6.2: SVG Script Tag
```
Action: Temporarily add <script>alert(1)</script> to globe.svg
Expected: Script tag removed when rendered
Status: [ ] PASS [ ] FAIL
```

### Test 6.3: SVG Event Handler
```
Action: Add onclick="alert(1)" to SVG path
Expected: Event handler removed when rendered
Status: [ ] PASS [ ] FAIL
```

### Test 6.4: SVG Data URI
```
Action: Add data:text/html;base64,... to SVG
Expected: Data URI removed when rendered
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] SVG files loaded from gfx directory only
- [ ] No inline scripts or event handlers in rendered SVG
- [ ] No data URIs in rendered SVG

---

## 7. Accessibility & UX Tests

**Objective**: Ensure security measures don't break usability.

### Test 7.1: Keyboard Navigation
```
Action: Tab to language switcher, press Enter
Expected: Dropdown opens, arrow keys navigate
Status: [ ] PASS [ ] FAIL
```

### Test 7.2: Screen Reader Support
```
Action: Use screen reader (NVDA/JAWS/VoiceOver)
Check: ARIA labels announced correctly
Verify: aria-expanded, aria-current states work
Status: [ ] PASS [ ] FAIL
```

### Test 7.3: Mobile Dropdown
```
Action: Test on mobile device/emulator
Verify: Touch events work, dropdown closes on outside tap
Status: [ ] PASS [ ] FAIL
```

### Test 7.4: Focus Management
```
Action: Open dropdown with keyboard, press Escape
Expected: Dropdown closes, focus returns to toggle button
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] Keyboard navigation works
- [ ] Screen reader friendly
- [ ] Mobile touch events work
- [ ] Focus management correct

---

## 8. Error Handling Tests

**Objective**: Verify graceful degradation on errors.

### Test 8.1: Invalid Blog ID
```
Action: Manually call acrylicon_get_equivalent_url(999)
Expected: Return homepage of blog 1 (fallback)
Status: [ ] PASS [ ] FAIL
```

### Test 8.2: Missing Slug Map Entry
```
Action: Navigate to page with unmapped slug, switch language
Expected: Fallback to homepage, no PHP errors
Status: [ ] PASS [ ] FAIL
```

### Test 8.3: Malformed URL
```
URL: https://acrylicon.no/norway/:::invalid:::/
Expected: Redirect to homepage, no PHP errors
Status: [ ] PASS [ ] FAIL
```

### Test 8.4: Missing SVG File
```
Action: Reference non-existent SVG in switcher
Expected: Empty string, no visible error
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] No PHP errors/warnings in log
- [ ] Graceful fallback to homepage
- [ ] User sees working site (degraded but functional)

---

## 9. Performance Tests

**Objective**: Verify security measures don't cause performance issues.

### Test 9.1: Page Load Time
```
Action: Measure page load time with language switcher
Tool: Browser DevTools Performance tab
Expected: < 2 seconds total, language switcher < 50ms
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 9.2: Database Queries
```
Action: Enable Query Monitor plugin, check query count
Verify: Language switcher adds < 2 queries per page
Status: [ ] PASS [ ] FAIL
```

### Test 9.3: Memory Usage
```
Action: Check PHP memory_get_peak_usage()
Verify: Language switcher adds < 1MB memory
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] No significant performance degradation
- [ ] Minimal database queries added
- [ ] Memory usage acceptable

---

## 10. Integration Tests

**Objective**: Verify language switcher works with existing features.

### Test 10.1: Cache Compatibility
```
Action: Enable WP Fastest Cache, switch language
Verify: Cached pages serve correct language
Status: [ ] PASS [ ] FAIL
```

### Test 10.2: Multisite Sync Compatibility
```
Action: Sync content from Norway to International
Verify: Language switcher still maps correctly
Status: [ ] PASS [ ] FAIL
```

### Test 10.3: SEO Plugin Compatibility (Yoast)
```
Action: Check Yoast SEO analysis
Verify: Hreflang tags recognized, no conflicts
Status: [ ] PASS [ ] FAIL
```

### Test 10.4: ACF Block Compatibility
```
Action: Load page with ACF blocks, use language switcher
Verify: No JavaScript errors, blocks render correctly
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] No conflicts with WP Fastest Cache
- [ ] No conflicts with multisite sync
- [ ] No conflicts with Yoast SEO
- [ ] No conflicts with ACF blocks

---

## 11. Edge Cases

**Objective**: Test unusual but valid scenarios.

### Test 11.1: Homepage Language Switch
```
URL: https://acrylicon.no/norway/
Click: English language switcher
Expected: https://acrylicon.no/
Status: [ ] PASS [ ] FAIL
```

### Test 11.2: Deep URL Path
```
URL: https://acrylicon.no/norway/produkter/category/subcategory/
Click: English language switcher
Expected: Intelligent mapping or homepage fallback
Status: [ ] PASS [ ] FAIL
```

### Test 11.3: Query Parameters Preservation
```
URL: https://acrylicon.no/norway/produkter/?utm_source=test
Click: English language switcher
Expected: Query params preserved or stripped (document behavior)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

### Test 11.4: URL Fragments (Anchors)
```
URL: https://acrylicon.no/norway/produkter/#section
Click: English language switcher
Expected: Fragment preserved or stripped (document behavior)
Actual: _________________
Status: [ ] PASS [ ] FAIL
```

**Verification**:
- [ ] Homepage switch works
- [ ] Deep URLs handled gracefully
- [ ] Query params behavior documented
- [ ] Anchors behavior documented

---

## 12. Production Readiness

**Objective**: Final checklist before production deployment.

### Pre-Deployment
- [ ] All tests above marked PASS
- [ ] Error log reviewed (no critical errors)
- [ ] Code reviewed by senior developer
- [ ] Security audit recommendations implemented
- [ ] wp-config.php: FORCE_SSL_ADMIN set to true (production)
- [ ] Backup created before deployment

### Post-Deployment (Production)
- [ ] Language switcher visible on all pages
- [ ] Test all paths from Test 1-11 on production
- [ ] Monitor error logs for 24 hours
- [ ] User testing with real traffic
- [ ] Google Search Console: Verify hreflang tags recognized
- [ ] No increase in server errors (check hosting metrics)

---

## Test Results Summary

**Total Tests**: 70
**Passed**: _________________
**Failed**: _________________
**Pass Rate**: _________________ %

**Critical Failures** (must be 0 before deployment):
- Open Redirect: [ ] 0 failures
- Path Traversal: [ ] 0 failures
- XSS: [ ] 0 failures

**Recommendation**:
[ ] APPROVED FOR PRODUCTION
[ ] REQUIRES FIXES
[ ] BLOCKED - SECURITY ISSUES

**Tester Signature**: _________________
**Date**: _________________

---

## Appendix: Manual Testing Commands

### Check Error Log
```bash
# Local (MAMP)
tail -f /Applications/MAMP/logs/php_error.log

# Production (Servebolt)
ssh acryli_28355@jana-osl.servebolt.cloud 'tail -f /cust/0/acryli_15806/acryli_28355/site/logs/error.log'
```

### Test with cURL
```bash
# Open redirect test
curl -I "https://acrylicon.no//evil.com"

# Path traversal test
curl -I "https://acrylicon.no/norway/../../../etc/passwd"

# HTTPS enforcement test
curl -I "http://acrylicon.no/"
```

### Validate Hreflang with Google
```bash
# Extract hreflang tags from page
curl -s https://acrylicon.no/norway/produkter/ | grep -o '<link rel="alternate" hreflang="[^"]*" href="[^"]*"'
```

### Performance Testing
```bash
# Apache Bench (simple load test)
ab -n 100 -c 10 https://acrylicon.no/norway/

# Check response time
time curl -s https://acrylicon.no/norway/ > /dev/null
```

---

## Appendix: Automated Test Script (Optional)

Save as `test-language-switcher-security.sh`:

```bash
#!/bin/bash

echo "=== Language Switcher Security Tests ==="
echo ""

BASE_URL="https://acrylicon.no"

# Test 1: Open Redirect
echo "[Test 1] Open Redirect Protection"
RESPONSE=$(curl -sI "$BASE_URL//evil.com" | grep -i "Location:")
if [[ $RESPONSE == *"evil.com"* ]]; then
    echo "❌ FAIL: Open redirect vulnerability detected"
else
    echo "✅ PASS: Open redirect blocked"
fi

# Test 2: Path Traversal
echo "[Test 2] Path Traversal Protection"
RESPONSE=$(curl -s "$BASE_URL/norway/../../../etc/passwd")
if [[ $RESPONSE == *"root:"* ]] || [[ $RESPONSE == *"<?php"* ]]; then
    echo "❌ FAIL: Path traversal vulnerability detected"
else
    echo "✅ PASS: Path traversal blocked"
fi

# Test 3: HTTPS Enforcement
echo "[Test 3] HTTPS Enforcement"
RESPONSE=$(curl -sI "http://acrylicon.no/" | grep -i "Location:")
if [[ $RESPONSE == *"https://"* ]]; then
    echo "✅ PASS: HTTPS redirect working"
else
    echo "⚠️  WARN: No HTTPS redirect detected"
fi

# Test 4: Hreflang Tags Present
echo "[Test 4] Hreflang Tags"
HREFLANG_COUNT=$(curl -s "$BASE_URL/norway/produkter/" | grep -c 'rel="alternate" hreflang=')
if [ "$HREFLANG_COUNT" -ge 2 ]; then
    echo "✅ PASS: Hreflang tags present ($HREFLANG_COUNT found)"
else
    echo "❌ FAIL: Hreflang tags missing or incomplete"
fi

echo ""
echo "=== Tests Complete ==="
```

Run with: `chmod +x test-language-switcher-security.sh && ./test-language-switcher-security.sh`
