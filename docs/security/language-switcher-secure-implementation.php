<?php
/**
 * SECURE Language Switcher Implementation
 *
 * This file contains security-hardened versions of all functions required
 * for the language switcher feature. Copy these functions to functions.php
 * after security review approval.
 *
 * Security audit: docs/security/2026-02-11-language-switcher-security-audit.md
 *
 * @package Acrylicon
 * @since 2026-02-11
 */

// =============================================================================
// CONFIGURATION FUNCTIONS (Static data - low risk)
// =============================================================================

/**
 * Get available languages configuration
 *
 * @return array Language configuration keyed by blog ID
 */
function acrylicon_get_languages() {
    return [
        1 => [
            'code'   => 'en',
            'label'  => 'English',
            'flag'   => 'gb',
            'prefix' => '/',
        ],
        3 => [
            'code'   => 'no',
            'label'  => 'Norsk',
            'flag'   => 'no',
            'prefix' => '/norway/',
        ],
    ];
}

/**
 * Get slug mapping between Norwegian and English
 *
 * @return array Map of Norwegian slug => English slug
 */
function acrylicon_slug_map() {
    return [
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

// =============================================================================
// SECURITY-CRITICAL FUNCTIONS (Require strict validation)
// =============================================================================

/**
 * Safely parse current request path
 *
 * SECURITY: Mitigates path traversal attacks by normalizing path and
 * removing encoded traversal sequences. Returns safe path or '/' on failure.
 *
 * @return string Normalized path starting with '/'
 */
function acrylicon_parse_current_path() {
    // Use WordPress native URL parsing instead of raw $_SERVER
    $current_url = home_url($_SERVER['REQUEST_URI']);
    $parsed = wp_parse_url($current_url);

    if (!isset($parsed['path'])) {
        return '/';
    }

    $path = $parsed['path'];

    // Normalize path (removes ../, ./, etc.)
    $path = wp_normalize_path($path);

    // Remove URL-encoded traversal attempts (%2e = ".")
    $path = str_replace(['%2e', '%2E', '%2f', '%2F'], '', $path);

    // Remove any encoded null bytes or control characters
    $path = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $path);

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

    // SECURITY: Validate path doesn't contain WordPress reserved paths
    $disallowed_patterns = [
        '#wp-admin#i',
        '#wp-content#i',
        '#wp-includes#i',
        '#\.php#i',
        '#\.\.#',  // Any remaining dot-dot
    ];

    foreach ($disallowed_patterns as $pattern) {
        if (preg_match($pattern, $path)) {
            // Suspicious path detected - log and return safe fallback
            if (WP_DEBUG) {
                error_log('[SECURITY] Suspicious path detected: ' . $path);
            }
            return '/';
        }
    }

    return $path;
}

/**
 * Map slug from one language to another with validation
 *
 * SECURITY: Validates slug format before lookup to prevent injection.
 * Only accepts alphanumeric characters, hyphens, and underscores.
 *
 * @param string $slug The slug to map
 * @param string $direction 'no_to_en' or 'en_to_no'
 * @return string|null Mapped slug or null if invalid/not found
 */
function acrylicon_map_slug($slug, $direction = 'no_to_en') {
    $map = acrylicon_slug_map();

    // SECURITY: Validate slug format before lookup
    // Only allow alphanumeric, hyphens, underscores
    if (!preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        if (WP_DEBUG) {
            error_log('[SECURITY] Invalid slug format: ' . $slug);
        }
        return null;
    }

    // SECURITY: Limit slug length (WordPress post slugs are typically < 200 chars)
    if (strlen($slug) > 200) {
        if (WP_DEBUG) {
            error_log('[SECURITY] Slug too long: ' . strlen($slug) . ' chars');
        }
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

/**
 * Validate URL is internal to WordPress installation
 *
 * SECURITY: Prevents open redirect attacks by validating target URL
 * is within allowed hosts (our WordPress multisite).
 *
 * @param string $url URL to validate
 * @return bool True if URL is safe, false otherwise
 */
function acrylicon_validate_internal_url($url) {
    $parsed = wp_parse_url($url);

    if (!isset($parsed['host'])) {
        return false;
    }

    // SECURITY: Whitelist of allowed hosts
    $allowed_hosts = [
        parse_url(get_site_url(1), PHP_URL_HOST),  // Blog 1
        parse_url(get_site_url(3), PHP_URL_HOST),  // Blog 3
        'acrylicon.no',
        'www.acrylicon.no',
        'acryli-28355.jana-osl.servebolt.cloud',
    ];

    // Remove www prefix for comparison
    $host = preg_replace('/^www\./i', '', $parsed['host']);
    $allowed_hosts_normalized = array_map(function($h) {
        return preg_replace('/^www\./i', '', $h);
    }, $allowed_hosts);

    if (!in_array($host, $allowed_hosts_normalized, true)) {
        if (WP_DEBUG) {
            error_log('[SECURITY] External host blocked: ' . $parsed['host']);
        }
        return false;
    }

    // SECURITY: Ensure scheme is HTTPS on production (not localhost)
    if (isset($parsed['scheme'])) {
        $is_local = strpos($parsed['host'], 'localhost') !== false
                 || strpos($parsed['host'], '127.0.0.1') !== false;

        if (!$is_local && $parsed['scheme'] !== 'https') {
            if (WP_DEBUG) {
                error_log('[SECURITY] Non-HTTPS URL blocked: ' . $url);
            }
            return false;
        }
    }

    return true;
}

/**
 * Get equivalent URL on target blog with security validation
 *
 * SECURITY: This is the primary attack surface. Validates all inputs,
 * normalizes paths, prevents traversal and open redirects.
 *
 * @param int $target_blog_id Target blog ID (1 or 3)
 * @return string Safe URL to equivalent page, or homepage fallback
 */
function acrylicon_get_equivalent_url($target_blog_id) {
    $current_blog_id = get_current_blog_id();

    // Early return if same blog
    if ($current_blog_id === $target_blog_id) {
        return get_home_url($current_blog_id);
    }

    // SECURITY: Use safe path parsing function
    $current_path = acrylicon_parse_current_path();

    // Extract slug from path (first segment after /)
    $path_segments = array_filter(explode('/', trim($current_path, '/')));

    if (empty($path_segments)) {
        // Homepage - map to target homepage
        return get_home_url($target_blog_id);
    }

    $current_slug = $path_segments[0];

    // Determine mapping direction
    $direction = ($current_blog_id === 3) ? 'no_to_en' : 'en_to_no';

    // SECURITY: Use validated slug mapping
    $mapped_slug = acrylicon_map_slug($current_slug, $direction);

    if ($mapped_slug) {
        // Found mapping - construct target URL
        $target_path = '/' . $mapped_slug . '/';

        // Preserve child segments if they exist
        if (count($path_segments) > 1) {
            array_shift($path_segments);  // Remove first segment
            $target_path .= implode('/', $path_segments) . '/';
        }
    } else {
        // No mapping - try same slug (works for identical slugs like "kontakt")
        $target_path = $current_path;
    }

    // SECURITY: Use WordPress API for URL construction
    $target_url = get_home_url($target_blog_id, $target_path);

    // CRITICAL SECURITY: Validate constructed URL before returning
    if (!acrylicon_validate_internal_url($target_url)) {
        // Validation failed - return safe fallback
        return get_home_url($target_blog_id);
    }

    // Optional: Verify target page exists (can be expensive, skip for performance)
    // If target doesn't exist, fallback to homepage
    // This is a UX enhancement, not a security requirement

    return $target_url;
}

// =============================================================================
// RENDERING FUNCTIONS (Output must be escaped)
// =============================================================================

/**
 * Render language switcher HTML
 *
 * SECURITY: All dynamic output is escaped in appropriate context:
 * - esc_url() for href attributes
 * - esc_attr() for HTML attributes (lang code, ARIA labels)
 * - esc_html() for text content (language labels)
 *
 * @param string $context 'header', 'mobile', or 'footer'
 */
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
                class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 transition-colors"
                data-lang-toggle
            >
                <?php
                // SVG icon uses sanitized filename (handled in svg_icon function)
                echo svg_icon('globe', [
                    'width' => 20,
                    'height' => 20,
                    'class' => 'icon-globe'
                ]);
                ?>
                <span class="lang-code font-medium text-sm uppercase">
                    <?php
                    // SECURITY: Escape language code (defense in depth, already hardcoded)
                    echo esc_html($languages[$current_blog_id]['code']);
                    ?>
                </span>
            </button>

            <div
                class="lang-dropdown absolute top-full right-0 mt-2 bg-white shadow-lg rounded-lg overflow-hidden hidden min-w-48"
                data-lang-dropdown
                role="menu"
            >
                <?php foreach ($languages as $blog_id => $lang): ?>
                    <?php
                    // SECURITY: Get validated URL
                    $equiv_url = acrylicon_get_equivalent_url($blog_id);
                    $is_current = ($blog_id === $current_blog_id);
                    ?>
                    <a
                        href="<?php echo esc_url($equiv_url); ?>"
                        class="lang-option flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors <?php echo $is_current ? 'bg-gray-100 font-medium' : ''; ?>"
                        role="menuitem"
                        <?php if ($is_current): ?>
                        aria-current="page"
                        <?php endif; ?>
                        hreflang="<?php echo esc_attr($lang['code']); ?>"
                    >
                        <?php
                        // SVG flag icon (filename sanitized in svg_icon)
                        echo svg_icon('flags/' . $lang['flag'], [
                            'width' => 20,
                            'height' => 15,
                            'class' => 'flag-icon'
                        ]);
                        ?>
                        <span>
                            <?php
                            // SECURITY: Escape language label
                            echo esc_html($lang['label']);
                            ?>
                        </span>
                        <?php if ($is_current): ?>
                            <svg class="ml-auto w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    } elseif ($context === 'footer') {
        ?>
        <div class="language-switcher-footer flex items-center gap-4">
            <?php foreach ($languages as $blog_id => $lang): ?>
                <?php
                // SECURITY: Get validated URL
                $equiv_url = acrylicon_get_equivalent_url($blog_id);
                $is_current = ($blog_id === $current_blog_id);
                ?>
                <a
                    href="<?php echo esc_url($equiv_url); ?>"
                    class="flex items-center gap-2 text-white hover:opacity-80 transition-opacity <?php echo $is_current ? 'font-bold' : ''; ?>"
                    <?php if ($is_current): ?>
                    aria-current="page"
                    <?php endif; ?>
                    hreflang="<?php echo esc_attr($lang['code']); ?>"
                >
                    <?php
                    // SVG flag icon
                    echo svg_icon('flags/' . $lang['flag'], [
                        'width' => 20,
                        'height' => 15,
                        'class' => 'flag-icon'
                    ]);
                    ?>
                    <span><?php echo esc_html($lang['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    } elseif ($context === 'mobile') {
        ?>
        <div class="language-switcher-mobile border-t border-gray-200 py-4">
            <?php foreach ($languages as $blog_id => $lang): ?>
                <?php
                // SECURITY: Get validated URL
                $equiv_url = acrylicon_get_equivalent_url($blog_id);
                $is_current = ($blog_id === $current_blog_id);
                ?>
                <a
                    href="<?php echo esc_url($equiv_url); ?>"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 transition-colors <?php echo $is_current ? 'bg-gray-100 font-bold' : ''; ?>"
                    <?php if ($is_current): ?>
                    aria-current="page"
                    <?php endif; ?>
                    hreflang="<?php echo esc_attr($lang['code']); ?>"
                >
                    <?php
                    echo svg_icon('flags/' . $lang['flag'], [
                        'width' => 24,
                        'height' => 18,
                        'class' => 'flag-icon'
                    ]);
                    ?>
                    <span><?php echo esc_html($lang['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

/**
 * Output hreflang tags in <head>
 *
 * SECURITY: All URLs are validated and escaped with esc_url().
 * Language codes are hardcoded but escaped as defense in depth.
 */
function acrylicon_hreflang_tags() {
    $languages = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    foreach ($languages as $blog_id => $lang) {
        if ($blog_id === $current_blog_id) {
            continue;  // Skip current language
        }

        // SECURITY: Get validated URL
        $equiv_url = acrylicon_get_equivalent_url($blog_id);

        // SECURITY: Escape URL for HTML attribute context
        $escaped_url = esc_url($equiv_url);

        // Additional validation: ensure URL starts with http
        if (strpos($escaped_url, 'http') !== 0) {
            continue;  // Skip invalid URLs
        }

        // SECURITY: Escape language code (defense in depth)
        $lang_code = esc_attr($lang['code']);

        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            $lang_code,
            $escaped_url
        );
    }

    // x-default tag (always points to blog 1)
    $default_url = esc_url(acrylicon_get_equivalent_url(1));
    if (strpos($default_url, 'http') === 0) {
        echo '<link rel="alternate" hreflang="x-default" href="' . $default_url . '" />' . "\n";
    }
}

// Hook hreflang tags to wp_head with high priority (early in <head>)
add_action('wp_head', 'acrylicon_hreflang_tags', 1);

// =============================================================================
// JAVASCRIPT (Inline in footer.php or separate file)
// =============================================================================

/*
 * SECURE JavaScript for dropdown toggle
 *
 * SECURITY NOTES:
 * - No eval() or innerHTML usage
 * - Event delegation for performance
 * - Keyboard accessibility (Escape key, focus management)
 * - ARIA attributes for screen readers
 *
 * Add this to footer.php after the existing mobileMenu() function:
 */
?>
<!-- PASTE THIS IN footer.php AFTER mobileMenu() -->
<script>
const languageSwitcher = () => {
    const switcher = document.querySelector('[data-lang-switcher]');
    if (!switcher) return;

    const toggle = switcher.querySelector('[data-lang-toggle]');
    const dropdown = switcher.querySelector('[data-lang-dropdown]');

    if (!toggle || !dropdown) return;

    // Toggle dropdown on button click
    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isOpen));
        dropdown.classList.toggle('hidden', isOpen);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!switcher.contains(e.target)) {
            toggle.setAttribute('aria-expanded', 'false');
            dropdown.classList.add('hidden');
        }
    });

    // Close dropdown on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' || e.key === 'Esc') {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                toggle.setAttribute('aria-expanded', 'false');
                dropdown.classList.add('hidden');
                toggle.focus();  // Return focus to toggle button
            }
        }
    });

    // Arrow key navigation within dropdown (accessibility enhancement)
    dropdown.addEventListener('keydown', (e) => {
        const items = Array.from(dropdown.querySelectorAll('[role="menuitem"]'));
        const currentIndex = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const nextIndex = (currentIndex + 1) % items.length;
            items[nextIndex].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prevIndex = (currentIndex - 1 + items.length) % items.length;
            items[prevIndex].focus();
        }
    });
};

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', languageSwitcher);
</script>
<?php

// =============================================================================
// SVG ICON FUNCTION HARDENING (Optional enhancement to existing function)
// =============================================================================

/**
 * Enhanced svg_icon() function with additional security hardening
 *
 * SECURITY ENHANCEMENTS:
 * - Path traversal prevention via basename() and realpath()
 * - Event handler stripping (onclick, onload, etc.)
 * - javascript: protocol removal
 * - Directory containment validation
 *
 * Replace existing svg_icon() in functions.php with this version.
 */
function svg_icon_secure($filename, $options = []) {
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

    // SECURITY: Validate and sanitize filename to prevent path traversal
    $filename = basename($filename);  // Strip any directory components
    $filename = preg_replace('/[^a-z0-9_\/-]/i', '', $filename);  // Only alphanumeric + hyphen/underscore/slash

    // Build path
    $svg_path = get_template_directory() . '/assets/gfx/' . $filename . '.svg';

    // SECURITY: Ensure path is within gfx directory (prevent traversal)
    $real_path = realpath($svg_path);
    $gfx_dir = realpath(get_template_directory() . '/assets/gfx/');

    if (!$real_path || !$gfx_dir || strpos($real_path, $gfx_dir) !== 0) {
        if (WP_DEBUG) {
            error_log('[SECURITY] SVG path traversal attempt: ' . $filename);
        }
        return '';  // Path traversal attempt
    }

    if (!file_exists($real_path)) {
        return '';
    }

    $svg = file_get_contents($real_path);

    // SECURITY: Comprehensive sanitization
    // Remove script tags
    $svg = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $svg);

    // Remove style tags
    $svg = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $svg);

    // Remove data URIs
    $svg = preg_replace('#data:[^,]*,#is', '', $svg);

    // SECURITY: Strip event handlers (onclick, onload, etc.)
    $svg = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg);

    // SECURITY: Strip javascript: protocol
    $svg = preg_replace('/javascript:/i', '', $svg);

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

    if (!empty($attrs)) {
        $svg = preg_replace('/<svg /', '<svg ' . implode(' ', $attrs) . ' ', $svg);
    }

    // Modify path attributes
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
        // Add attributes to paths
        $svg = preg_replace('/<path /', '<path ' . implode(' ', $path_attrs) . ' ', $svg);
        // Also target any other SVG elements
        $svg = preg_replace('/<circle /', '<circle ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<rect /', '<rect ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<line /', '<line ' . implode(' ', $path_attrs) . ' ', $svg);
        $svg = preg_replace('/<polygon /', '<polygon ' . implode(' ', $path_attrs) . ' ', $svg);
    }

    return $svg;
}
