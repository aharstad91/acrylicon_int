<?php
/**
 * Language Switcher for Acrylicon Multisite
 *
 * Handles language switching between Norwegian (blog 3, /no/)
 * and International/English (blog 1, /).
 *
 * To add a new language: add entry to acrylicon_get_languages()
 * and slug mappings to acrylicon_slug_map().
 */

/**
 * Get all available languages with their blog IDs and metadata.
 */
function acrylicon_get_languages() {
    return [
        1 => [
            'code'     => 'en',
            'hreflang' => 'en',
            'label'    => 'English',
            'flag'     => 'gb',
            'prefix'   => '/',
        ],
        3 => [
            'code'     => 'no',
            'hreflang' => 'nb',
            'label'    => 'Norsk',
            'flag'     => 'no',
            'prefix'   => '/no/',
        ],
    ];
}

/**
 * Bidirectional slug map between Norwegian and English.
 *
 * Norwegian slug => English slug.
 * Use acrylicon_map_slug() for lookups in either direction.
 *
 * When adding new pages: add the slug pair here.
 */
function acrylicon_slug_map() {
    return [
        // Pages and CPT archives: Norwegian slug => English slug
        'pages' => [
            'fordeler'            => 'benefits',
            'bruksomrader'        => 'applications',
            'produkter'           => 'products',
            'referanser'          => 'references',
            'om-acrylicon'        => 'about-acrylicon',
            'baerekraft'          => 'sustainability',
            'levetids-kostnader'  => 'lifecycle-costs',
            'gode-grunner'        => 'good-reasons',
            'kontor'              => 'locations',  // NO /kontor/ ↔ EN /locations/ (EN has no /offices/ listing)
            'sertifiseringer'     => 'certifications',
            'industrier'          => 'industries',
            'nedlastinger'        => 'downloads',
            'informasjonskapsler' => 'cookie-policy',
        ],
        // Taxonomies on Referanser CPT — keys MUST match the registered NO rewrite
        // slugs and values the registered EN slugs (see acrylicon_get_cpt_slugs()).
        'taxonomies' => [
            'referanser-type'     => 'reference-type',
            'referanse-kategori'  => 'reference-category',
            'referanse-kontor'    => 'reference-office',
            'referanse-produkter' => 'reference-products',
        ],
    ];
}

/**
 * Look up a slug in the given direction.
 *
 * @param string $slug      The slug to look up.
 * @param string $direction 'no_to_en' or 'en_to_no'.
 * @return string Mapped slug, or original slug if no mapping exists.
 */
function acrylicon_map_slug( $slug, $direction = 'no_to_en' ) {
    static $lookup = [];

    if ( empty( $lookup ) ) {
        $map = acrylicon_slug_map();
        $all = array_merge( $map['pages'], $map['taxonomies'] );
        $lookup['no_to_en'] = $all;
        $lookup['en_to_no'] = array_flip( $all );
    }

    return $lookup[ $direction ][ $slug ] ?? $slug;
}

/**
 * Validate a slug segment — only allow safe characters.
 *
 * @param string $slug The slug to validate.
 * @return bool True if valid.
 */
function acrylicon_is_valid_slug( $slug ) {
    return (bool) preg_match( '/^[a-z0-9_-]+$/', $slug );
}

/**
 * Get the equivalent URL on the target blog.
 *
 * Returns raw URL — caller is responsible for esc_url() at output.
 *
 * Only returns a real translated URL when one is verified to exist on the
 * target blog (same post ID, or a slug-mapped URL that resolves to a published
 * post). Otherwise it falls back to the target blog's front page and reports
 * $has_translation = false so callers (e.g. hreflang) can omit the alternate.
 *
 * @param int   $target_blog_id  The blog ID to switch to.
 * @param bool &$has_translation Set true when a genuine translation was found,
 *                               false when the front-page fallback was used.
 * @return string The equivalent URL on the target blog.
 */
function acrylicon_get_equivalent_url( $target_blog_id, &$has_translation = null ) {
    static $cache = [];

    if ( isset( $cache[ $target_blog_id ] ) ) {
        $has_translation = $cache[ $target_blog_id ]['real'];
        return $cache[ $target_blog_id ]['url'];
    }

    // Store result in cache, set the by-ref flag, and return the URL.
    $finish = static function ( $url, $real ) use ( &$cache, $target_blog_id, &$has_translation ) {
        $cache[ $target_blog_id ] = [ 'url' => $url, 'real' => $real ];
        $has_translation          = $real;
        return $url;
    };

    $current_blog_id = get_current_blog_id();
    $languages       = acrylicon_get_languages();

    // Parse current path (sanitize at intake)
    $request_uri = esc_url_raw( $_SERVER['REQUEST_URI'] );
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );

    if ( ! $path ) {
        return $finish( acrylicon_get_fallback_url( $target_blog_id ), false );
    }

    // Strip the site base path (handles both local /acrylicon/ and production /)
    $site_path = wp_parse_url( home_url(), PHP_URL_PATH );
    if ( $site_path && $site_path !== '/' ) {
        $full_prefix = rtrim( $site_path, '/' ) . '/';
        if ( strpos( $path, $full_prefix ) === 0 ) {
            $path = substr( $path, strlen( $full_prefix ) );
        }
    } else {
        // Production: strip blog prefix
        $current_prefix = $languages[ $current_blog_id ]['prefix'] ?? '/';
        if ( $current_prefix !== '/' && strpos( $path, $current_prefix ) === 0 ) {
            $path = substr( $path, strlen( $current_prefix ) );
        }
    }

    // Clean up the path
    $path = trim( $path, '/' );

    // If target is current blog, return this page's URL directly (self is always "real")
    if ( $target_blog_id === $current_blog_id ) {
        $relative = $path ? $path . '/' : '';
        return $finish( home_url( '/' . $relative ), true );
    }

    // Determine mapping direction
    $direction = ( $current_blog_id === 3 ) ? 'no_to_en' : 'en_to_no';

    // Front page: home <-> home is always a verified pair (same page ID on both
    // blogs), so the target front page IS the genuine equivalent — emit it as real.
    if ( empty( $path ) && is_front_page() ) {
        return $finish( acrylicon_get_fallback_url( $target_blog_id ), true );
    }

    // Search, 404, or an otherwise-empty path: no specific translation exists.
    if ( empty( $path ) || is_search() || is_404() ) {
        return $finish( acrylicon_get_fallback_url( $target_blog_id ), false );
    }

    // For singular posts/pages: prefer the same post ID on the target blog
    // (multisite-sync keeps the same IDs across blogs).
    if ( is_singular() ) {
        $post_id = get_queried_object_id();
        if ( $post_id ) {
            switch_to_blog( $target_blog_id );
            $target_post = get_post( $post_id );
            $real_url    = null;
            if ( $target_post && $target_post->post_status === 'publish' ) {
                // switch_to_blog() does NOT re-register post types, so get_permalink()
                // would build CPT URLs with the SOURCE blog's rewrite base (e.g.
                // /produkter/ on .com instead of /products/). For CPTs, build the URL
                // from the TARGET blog's rewrite slug (acrylicon_get_cpt_slugs() is
                // blog-aware and now resolves to the target). Pages/posts have no
                // blog-conditional rewrite base, so get_permalink() is correct for them.
                $cpt_slugs = function_exists( 'acrylicon_get_cpt_slugs' ) ? acrylicon_get_cpt_slugs() : [];
                if ( isset( $cpt_slugs[ $target_post->post_type ] ) ) {
                    $real_url = home_url( '/' . $cpt_slugs[ $target_post->post_type ] . '/' . $target_post->post_name . '/' );
                } else {
                    $real_url = get_permalink( $post_id );
                }
            }
            restore_current_blog();
            if ( $real_url ) {
                return $finish( $real_url, true );
            }
        }
    }

    // Split path into segments and map each one
    $segments        = explode( '/', $path );
    $mapped_segments = [];

    foreach ( $segments as $segment ) {
        // Skip pagination segments
        if ( $segment === 'page' ) {
            break;
        }

        // Validate slug
        if ( ! acrylicon_is_valid_slug( $segment ) ) {
            return $finish( acrylicon_get_fallback_url( $target_blog_id ), false );
        }

        $mapped_segments[] = acrylicon_map_slug( $segment, $direction );
    }

    if ( empty( $mapped_segments ) ) {
        return $finish( acrylicon_get_fallback_url( $target_blog_id ), false );
    }

    $mapped_path = implode( '/', $mapped_segments ) . '/';

    // Build the slug-mapped target URL and verify it actually exists.
    switch_to_blog( $target_blog_id );
    $target_url = home_url( '/' . $mapped_path );
    if ( is_singular() ) {
        // Singular source must resolve to a published singular post on the target,
        // otherwise the slug-mapped URL would 404 (untranslated content).
        $resolved = url_to_postid( $target_url );
        $exists   = ( $resolved && get_post_status( $resolved ) === 'publish' );
    } elseif ( is_tax() ) {
        // Taxonomy terms are NOT synced 1:1 (EN references are a subset of NO), so
        // verify the same term slug exists in this taxonomy on the target blog before
        // claiming a translation — otherwise the (correctly-based) alternate 404s.
        $src_term = get_queried_object();
        $term     = ( $src_term && ! empty( $src_term->slug ) )
            ? get_term_by( 'slug', $src_term->slug, $src_term->taxonomy )
            : false;
        $exists   = ( $term && ! is_wp_error( $term ) );
    } else {
        // CPT/date/author archives: slug-mapped archive URLs are known-valid.
        $exists = true;
    }
    restore_current_blog();

    if ( $exists ) {
        return $finish( $target_url, true );
    }

    return $finish( acrylicon_get_fallback_url( $target_blog_id ), false );
}

/**
 * Get fallback URL (front page) for a target blog.
 *
 * Returns raw URL — caller is responsible for esc_url() at output.
 *
 * @param int $target_blog_id The blog ID.
 * @return string The front page URL.
 */
function acrylicon_get_fallback_url( $target_blog_id ) {
    switch_to_blog( $target_blog_id );
    $url = home_url( '/' );
    restore_current_blog();

    return $url;
}

/**
 * Render the language switcher.
 *
 * @param string $context 'header', 'mobile', or 'footer'.
 */
function acrylicon_render_language_switcher( $context = 'header' ) {
    $languages       = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();

    if ( $context === 'header' ) {
        acrylicon_render_header_switcher( $languages, $current_blog_id );
    } elseif ( $context === 'mobile' ) {
        acrylicon_render_mobile_switcher( $languages, $current_blog_id );
    } elseif ( $context === 'footer' ) {
        acrylicon_render_footer_switcher( $languages, $current_blog_id );
    }
}

/**
 * Desktop header dropdown switcher.
 */
function acrylicon_render_header_switcher( $languages, $current_blog_id ) {
    $current_lang = $languages[ $current_blog_id ];
    ?>
    <div class="relative" id="langSwitcher">
        <button
            type="button"
            id="langToggle"
            class="flex items-center gap-2 text-lg text-acryl-black hover:text-acryl-red transition-colors duration-200"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <?php echo svg_icon( 'globe', [ 'width' => '20', 'height' => '20', 'class' => 'inline-block' ] ); ?>
            <span class="uppercase font-normal"><?php echo esc_html( $current_lang['code'] ); ?></span>
        </button>
        <div
            id="langDropdown"
            class="absolute top-full mt-2 bg-acryl-beige-lightest rounded shadow-lg py-2 min-w-[160px] opacity-0 invisible transition-all duration-200 z-50"
            style="right: -16px;"
            role="menu"
        >
            <?php foreach ( $languages as $blog_id => $lang ) :
                $url       = acrylicon_get_equivalent_url( $blog_id );
                $is_active = ( $blog_id === $current_blog_id );
            ?>
                <a
                    href="<?php echo esc_url( $url ); ?>"
                    class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-acryl-beige-lighter transition-colors duration-150 <?php echo $is_active ? 'text-acryl-red font-medium' : 'text-acryl-black'; ?>"
                    role="menuitem"
                    <?php echo $is_active ? 'aria-current="true"' : ''; ?>
                >
                    <?php echo svg_icon( 'flags/' . $lang['flag'], [ 'width' => '20', 'height' => '14', 'class' => 'inline-block flex-shrink-0' ] ); ?>
                    <span><?php echo esc_html( $lang['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Mobile menu switcher (pinned to the bottom of the menu panel, inline row).
 */
function acrylicon_render_mobile_switcher( $languages, $current_blog_id ) {
    ?>
    <div class="mt-auto flex items-center gap-4 border-t border-acryl-beige-light px-6 pt-5 pb-8 text-lg">
        <?php foreach ( $languages as $blog_id => $lang ) :
            $url       = acrylicon_get_equivalent_url( $blog_id );
            $is_active = ( $blog_id === $current_blog_id );
        ?>
            <a
                href="<?php echo esc_url( $url ); ?>"
                class="flex items-center gap-2 transition-colors duration-150 <?php echo $is_active ? 'text-acryl-red underline' : 'text-acryl-black hover:text-acryl-red'; ?>"
                <?php echo $is_active ? 'aria-current="true"' : ''; ?>
            >
                <?php echo svg_icon( 'flags/' . $lang['flag'], [ 'width' => '24', 'height' => '16', 'class' => 'inline-block flex-shrink-0' ] ); ?>
                <span><?php echo esc_html( $lang['label'] ); ?></span>
            </a>
            <?php if ( $blog_id !== array_key_last( $languages ) ) : ?>
                <span class="text-acryl-gray-2" aria-hidden="true">|</span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Footer inline switcher (white text, flag + language name).
 */
function acrylicon_render_footer_switcher( $languages, $current_blog_id ) {
    ?>
    <div class="flex items-center gap-4 mt-6">
        <?php foreach ( $languages as $blog_id => $lang ) :
            $url       = acrylicon_get_equivalent_url( $blog_id );
            $is_active = ( $blog_id === $current_blog_id );
        ?>
            <a
                href="<?php echo esc_url( $url ); ?>"
                class="flex items-center gap-2 text-white hover:text-acryl-light-blue transition-colors duration-150 <?php echo $is_active ? 'underline' : ''; ?>"
                <?php echo $is_active ? 'aria-current="true"' : ''; ?>
            >
                <?php echo svg_icon( 'flags/' . $lang['flag'], [ 'width' => '20', 'height' => '14', 'class' => 'inline-block flex-shrink-0' ] ); ?>
                <span><?php echo esc_html( $lang['label'] ); ?></span>
            </a>
            <?php if ( $blog_id !== array_key_last( $languages ) ) : ?>
                <span class="text-white opacity-50">|</span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Output hreflang tags in <head>.
 *
 * Only emits an alternate for a language when a genuine translation exists
 * (the current language is always included as a self-reference). This avoids
 * declaring hreflang alternates that point to non-existent (404) URLs.
 */
function acrylicon_hreflang_tags() {
    // Non-canonical pages (404, search) have no language equivalents; emitting
    // hreflang here would self-reference the home page. Bail to match how
    // canonical/OG/schema already skip these.
    if ( is_404() || is_search() ) {
        return;
    }

    $languages       = acrylicon_get_languages();
    $current_blog_id = get_current_blog_id();
    $english_url     = '';

    echo "\n<!-- Language alternates -->\n";

    foreach ( $languages as $blog_id => $lang ) {
        $has_translation = false;
        $url             = acrylicon_get_equivalent_url( $blog_id, $has_translation );

        // Include the current language (self) always; others only when real.
        if ( $blog_id !== $current_blog_id && ! $has_translation ) {
            continue;
        }

        if ( $blog_id === 1 ) {
            $english_url = $url;
        }
        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            esc_attr( $lang['hreflang'] ),
            esc_url( $url )
        );
    }

    // x-default reuses the English URL when present, else the current page URL.
    if ( ! $english_url ) {
        $english_url = acrylicon_get_equivalent_url( $current_blog_id );
    }
    printf(
        '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
        esc_url( $english_url )
    );
}
add_action( 'wp_head', 'acrylicon_hreflang_tags', 1 );
