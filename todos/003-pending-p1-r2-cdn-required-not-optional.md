---
status: pending
priority: p1
issue_id: "003"
tags: [code-review, performance, phase3, r2, cdn]
dependencies: []
---

# R2 CDN Configuration Marked Optional But Is CRITICAL

## Problem Statement

The plan marks Cloudflare CDN configuration as "optional" (line 375: "Optional: Configure custom domain"), but performance analysis shows **CDN is REQUIRED, not optional**.

**Why it matters:** Without CDN, R2 external storage will cause 10x latency increase and degrade page load times from 2s to 3-4s (50-100% slower), severely impacting user experience and Google Core Web Vitals scores.

## Findings

**Source:** Performance-oracle agent

**Network Performance Analysis:**

**Current setup (local files on Servebolt):**
```
Browser → Servebolt (Norway) → Local filesystem
Latency: ~5-10ms (disk I/O)
Throughput: ~1 GB/s (local SSD)
```

**Proposed setup WITHOUT CDN (R2):**
```
Browser → Servebolt → Cloudflare R2 → Servebolt → Browser
Latency: 50-150ms (network round trip)
Throughput: ~50-100 MB/s (network bandwidth)
```

**Latency breakdown per image:**
- DNS resolution: 20-50ms (first request)
- TLS handshake: 30-80ms (first request)
- HTTP request: 20-50ms per image
- **Total first load: 70-180ms per image**

**For a typical page with 10 images:**
- Current (local): 10 × 10ms = 100ms
- With R2 NO CDN: 10 × 100ms = **1,000ms (1 second)** ❌
- **10x latency increase**

**With R2 + CDN:**
- First visitor: Same as above (1 second)
- Subsequent visitors: 10 × 15ms = 150ms (served from edge cache)
- **Acceptable performance** ✅

**Impact on Core Web Vitals:**
- **LCP (Largest Contentful Paint):** Could increase by 500-1000ms
- **Google ranking:** Negative impact if LCP > 2.5s
- **User experience:** Perceived slowness, higher bounce rate

## Proposed Solutions

### Option 1: Mandatory CDN with Custom Domain (Recommended)

Make CDN configuration a **required step in Phase 3**, not optional:

```php
// Required configuration (not optional)
define( 'MCS_PROVIDER', 'r2' );
define( 'MCS_BUCKET', 'acrylicon-media' );
define( 'MCS_CUSTOM_DOMAIN', 'https://media.acrylicon.no' ); // REQUIRED
define( 'MCS_CDN_ENABLED', true ); // REQUIRED

// Aggressive edge caching
define( 'MCS_CACHE_CONTROL', 'public, max-age=31536000, immutable' );
```

**Cloudflare DNS configuration:**
```
media.acrylicon.no CNAME → bucket.r2.cloudflarestorage.com
- Proxied: ✅ Enabled (orange cloud)
- CNAME Flattening: ❌ Disabled
- HTTPS: ✅ Required
```

**Cache-Control headers:**
```php
add_filter( 'wp_headers', function( $headers ) {
    if ( is_attachment() || strpos( $_SERVER['REQUEST_URI'], '/uploads/' ) !== false ) {
        $headers['Cache-Control'] = 'public, max-age=31536000, immutable';
        $headers['CDN-Cache-Control'] = 'max-age=31536000';
    }
    return $headers;
} );
```

- **Pros:** Maintains current page load performance, global edge caching, HTTPS included
- **Cons:** Requires DNS configuration, slight complexity
- **Effort:** Small (2-3 hours for DNS + testing)
- **Risk:** Low (Cloudflare handles edge caching automatically)

### Option 2: Keep Local Cache (Don't Delete Files)

Alternative: Don't enable `MCS_DELETE_LOCAL`, keep files both locally AND on R2:

```php
define( 'MCS_PROVIDER', 'r2' );
define( 'MCS_BUCKET', 'acrylicon-media' );
define( 'MCS_DELETE_LOCAL', false ); // Keep local cache
define( 'MCS_CACHE_TTL', 30 * DAY_IN_SECONDS ); // 30-day cache
```

- **Pros:** No latency increase, instant fallback if R2 fails
- **Cons:** Doesn't solve storage problem (still uses 10GB local)
- **Effort:** Minimal (configuration only)
- **Risk:** Very low

### Option 3: Hybrid Approach

Use both CDN AND local cache:

```php
define( 'MCS_CUSTOM_DOMAIN', 'https://media.acrylicon.no' ); // REQUIRED
define( 'MCS_DELETE_LOCAL', false ); // Keep local cache
define( 'MCS_CACHE_TTL', 7 * DAY_IN_SECONDS ); // 7-day local cache
define( 'MCS_SERVE_FROM_LOCAL_IF_EXISTS', true ); // Serve from local if available
```

- **Pros:** Best of both worlds - performance + storage savings over time
- **Cons:** More complex logic, gradual storage reduction
- **Effort:** Medium (requires plugin modification)
- **Risk:** Low

## Recommended Action

**Primary:** Implement Option 1 (mandatory CDN with custom domain)

**Rollout plan:**
1. Configure `media.acrylicon.no` CNAME in Cloudflare DNS
2. Enable Cloudflare proxy (orange cloud)
3. Set Cache-Control headers for aggressive edge caching
4. Test with 10 images first
5. Monitor TTFB (Time To First Byte) metrics
6. Only proceed with DELETE_LOCAL after validating CDN works

**Performance targets:**
- Maintain page load time < 2s ✅
- LCP (Largest Contentful Paint) < 2.5s ✅
- TTFB < 600ms ✅

**Update plan language:**
- Change "Optional: Configure custom domain" → "**Required:** Configure custom domain with CDN"
- Add warning: "⚠️ Do NOT enable DELETE_LOCAL without CDN - will cause 10x latency increase"

## Technical Details

**Affected Files:**
- Plan document: Lines 375-378 (change from optional to required)
- `/mu-plugins/acrylicon-r2-cache-control.php` (create for Cache-Control headers)
- Plugin configuration in wp-config.php

**Components:**
- Cloudflare DNS (CNAME record)
- Cloudflare CDN (edge caching)
- R2 bucket configuration
- WordPress image serving
- Browser cache headers

**DNS Configuration:**
```
Record Type: CNAME
Name: media
Content: <bucket-name>.r2.cloudflarestorage.com
Proxy status: Proxied (orange cloud) ✅
TTL: Auto
```

**Performance Monitoring:**
```php
// Add TTFB logging
add_action( 'wp_footer', function() {
    if ( WP_DEBUG ) {
        $ttfb = (int) ( ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000 );
        echo sprintf( '<!-- TTFB: %dms -->', $ttfb );
    }
} );
```

## Acceptance Criteria

- [ ] media.acrylicon.no CNAME configured in Cloudflare DNS
- [ ] Cloudflare proxy enabled (orange cloud)
- [ ] HTTPS working for media.acrylicon.no
- [ ] Cache-Control headers set to `max-age=31536000`
- [ ] Test 10 images upload to R2 successfully
- [ ] Test images accessible via https://media.acrylicon.no/...
- [ ] Browser DevTools Network tab shows cache HIT after second visit
- [ ] TTFB remains < 600ms
- [ ] Page load time remains < 2s
- [ ] LCP (Largest Contentful Paint) < 2.5s
- [ ] Plan document updated to mark CDN as REQUIRED

## Work Log

### 2026-01-26
- Performance analysis identified 10x latency increase without CDN
- Validated that current plan marks CDN as optional
- Recommended making CDN mandatory before DELETE_LOCAL

## Resources

- Plan section: Lines 375-378 (CDN marked optional)
- [Cloudflare R2 Custom Domains](https://developers.cloudflare.com/r2/buckets/public-buckets/#custom-domains)
- [Cloudflare CDN Cache Configuration](https://developers.cloudflare.com/cache/)
- [Core Web Vitals Guide](https://web.dev/vitals/)
- [WordPress Image CDN Best Practices](https://kinsta.com/blog/wordpress-cdn/)
- [Cache-Control Header Guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control)
