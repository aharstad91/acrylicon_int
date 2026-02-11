---
module: WordPress Theme (acrylicon-2024)
date: 2026-02-11
problem_type: performance_issue
component: frontend_stimulus
symptoms:
  - "PageSpeed mobile Performance score 69/100"
  - "LCP 11.3s (red) caused by 2,090ms element render delay"
  - "Render-blocking chain of 4,410ms from 9 CSS/JS files"
  - "Hero PNG image 862 KiB unoptimized"
root_cause: config_error
resolution_type: config_change
severity: high
tags: [pagespeed, lcp, webp, render-blocking, defer, wordpress, performance, htaccess]
---

# Troubleshooting: WordPress PageSpeed 69 → 99 (mobile) / 100 (desktop)

## Problem
WordPress site scored 69/100 on PageSpeed Insights mobile with LCP at 11.3 seconds. The root cause was a combination of render-blocking CSS/JS chain (9 files, 4,410ms) and an unoptimized 862 KiB hero PNG image.

## Environment
- Module: WordPress Theme (acrylicon-2024)
- WordPress Version: 6.8.3
- PHP: 8.4 (Servebolt hosting)
- Web Server: Apache
- Affected Component: Theme asset loading (functions.php, header.php, footer.php) and image delivery
- Date: 2026-02-11

## Symptoms
- PageSpeed mobile Performance: 69/100
- LCP: 11.3s (red) — target is < 2.5s
- FCP: 2.0s
- TBT: 130ms
- Speed Index: 5.1s
- LCP breakdown showed **Element render delay: 2,090ms** — the image loaded in 150ms but couldn't paint because render-blocking resources hadn't finished
- 9 render-blocking resources totaling 4,410ms on Slow 4G: jQuery (680ms), jQuery Migrate (340ms), 6 CSS files, plus a duplicate ScrollReveal from unpkg CDN

## What Didn't Work

**Attempted Solution 1:** Moving jQuery to footer via `wp_scripts()->add_data('jquery', 'group', 1)`
- **Why it failed:** Increased Total Blocking Time from 110ms to 230ms. jQuery in footer still blocks DOMContentLoaded, and scripts depending on it created a longer main-thread task. Reverted.

**Attempted Solution 2:** Adding `preload="metadata"` to video blocks
- **Why it partially helped:** Reduced video download at page load, but LCP improvement was minimal because the video wasn't the LCP element — the hero image was.

## Solution

Three changes together achieved the result:

### 1. Defer non-critical CSS (functions.php)

```php
// Before: All CSS render-blocking in <head>
wp_enqueue_style('gravity', get_template_directory_uri() . '/assets/css/gravity.css', array(), '1.0.0');
wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
// block-panels.css also render-blocking

// After: Add filter to defer non-critical CSS
function defer_non_critical_css($html, $handle, $href, $media) {
    $defer_handles = array('gravity', 'custom-block-styles', 'swiper');
    if (in_array($handle, $defer_handles) && !is_admin()) {
        return sprintf(
            '<link rel="stylesheet" id="%s-css" href="%s" media="print" onload="this.media=\'all\'">' . "\n" .
            '<noscript><link rel="stylesheet" href="%s" media="%s"></noscript>' . "\n",
            esc_attr($handle),
            esc_url($href),
            esc_url($href),
            esc_attr($media)
        );
    }
    return $html;
}
add_filter('style_loader_tag', 'defer_non_critical_css', 10, 4);
```

### 2. Defer jQuery (functions.php)

```php
// Before: jQuery render-blocking in <head> (680ms on Slow 4G)
// <script src="jquery.min.js"></script>

// After: Add defer attribute
function add_defer_to_scripts($tag, $handle, $src) {
    $defer_handles = array('jquery-core', 'jquery-migrate');
    if (in_array($handle, $defer_handles) && !is_admin()) {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'add_defer_to_scripts', 10, 3);
```

**Important:** Verify no inline `<script>` tags use jQuery before deferring. Check with:
```bash
curl -s https://your-site.com/ | grep -oE '(jQuery|\$\()' | wc -l
# Must be 0 for defer to be safe
```

### 3. WebP image conversion + .htaccess auto-serving (on server)

```bash
# Convert key images to WebP (on Servebolt via SSH)
cwebp -q 80 image.png -o image.webp
# Hero PNG: 862 KiB → 51 KiB (94% reduction)
```

```apache
# .htaccess — prepend before WordPress rewrite rules
# Serve WebP when browser supports it and file exists
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTP_ACCEPT} image/webp
  RewriteCond %{REQUEST_URI} \.(jpe?g|png)$
  RewriteCond %{DOCUMENT_ROOT}/$1.webp -f
  RewriteRule ^(.*)\.(jpe?g|png)$ $1.webp [T=image/webp,L]
</IfModule>

<IfModule mod_headers.c>
  <FilesMatch "\.(jpe?g|png)$">
    Header append Vary Accept
  </FilesMatch>
</IfModule>
```

**Verify WebP serving:**
```bash
curl -sI -H "Accept: image/webp" "https://your-site.com/wp-content/uploads/image.png" | grep content-type
# Should return: content-type: image/webp
```

### Additional cleanup (header.php, footer.php)

- Removed duplicate ScrollReveal from unpkg CDN (was loading both unpkg and local copy)
- Removed IE-fix script (unnecessary in 2026)
- Moved Swiper CSS/JS from hardcoded tags to `wp_enqueue_style`/`wp_enqueue_script`

## Results

| Metric | Before | After (mobile) | Desktop |
|---|---|---|---|
| Performance | 69 | **99** | **100** |
| FCP | 2.0s | **0.9s** | 0.2s |
| LCP | 11.3s | **2.2s** | 0.7s |
| TBT | 130ms | **60ms** | — |
| Speed Index | 5.1s | **0.9s** | — |
| Render blocking | 4,410ms (9 files) | **650ms (4 files)** | — |
| LCP render delay | 2,090ms | **520ms** | — |

## Why This Works

1. **Render-blocking chain was the bottleneck, not image size.** The LCP breakdown showed the hero image loaded in 150ms but couldn't paint for 2,090ms because the browser was waiting for CSS/JS to finish. Deferring non-critical assets cut the blocking chain from 4,410ms to 650ms.

2. **WebP reduced total payload dramatically.** On Slow 4G (~1.5 Mbps), every KiB matters. The hero PNG at 862 KiB took significant bandwidth; at 51 KiB WebP, it's nearly instant. Combined total image savings of ~1.7 MB.

3. **Both fixes were needed together.** Defer alone dropped LCP render delay from 2,090ms → 520ms but kept LCP at 10.6s because the large PNG still competed for bandwidth. WebP alone wouldn't help if the browser couldn't paint the image due to render-blocking. Together: 11.3s → 2.2s.

4. **`defer` on jQuery is safer than moving to footer.** `defer` maintains execution order (jQuery loads before jQuery Migrate before scripts.js) but doesn't block HTML parsing. Moving to footer changed TBT because all scripts executed as one large main-thread task.

## Prevention

- **Always defer non-critical CSS** using `media="print" onload="this.media='all'"` pattern. Only CSS needed for above-fold content should be render-blocking.
- **Always defer jQuery** in WordPress if no inline scripts depend on it. Use `script_loader_tag` filter with `!is_admin()` guard.
- **Convert images to WebP on upload.** Set up automatic WebP generation (plugin or server-side hook with `cwebp`).
- **Use `.htaccess` WebP rewrite** for transparent delivery — no WordPress code changes needed per image.
- **Check LCP breakdown, not just LCP time.** The subparts (TTFB, resource load delay, resource load duration, element render delay) tell you exactly where to focus.
- **Never use hardcoded `<script>` or `<link>` tags** in WordPress templates. Always use `wp_enqueue_*` so filters can modify loading behavior.

## Related Issues

No related issues documented yet.
