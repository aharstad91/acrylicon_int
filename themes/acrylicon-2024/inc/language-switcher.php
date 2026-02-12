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
            'kontakt-oss'         => 'locations',
            'sertifiseringer'     => 'certifications',
            'industrier'          => 'industries',
            'kontor'              => 'offices',
            'nedlastinger'        => 'downloads',
            'informasjonskapsler' => 'cookie-policy',
        ],
        // Taxonomies on Referanser CPT
        'taxonomies' => [
            'referanser-type'       => 'reference-type',
            'referanser-kategorier' => 'reference-categories',
            'referanser-kontor'     => 'reference-offices',
            'referanser-produkter'  => 'reference-products',
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
 * @param int $target_blog_id The blog ID to switch to.
 * @return string The equivalent URL on the target blog.
 */
function acrylicon_get_equivalent_url( $target_blog_id ) {
    static $cache = [];

    if ( isset( $cache[ $target_blog_id ] ) ) {
        return $cache[ $target_blog_id ];
    }

    $current_blog_id = get_current_blog_id();
    $languages       = acrylicon_get_languages();

    // Parse current path (sanitize at intake)
    $request_uri = esc_url_raw( $_SERVER['REQUEST_URI'] );
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );

    if ( ! $path ) {
        $cache[ $target_blog_id ] = acrylicon_get_fallback_url( $target_blog_id );
        return $cache[ $target_blog_id ];
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

    // If target is current blog, return this page's URL directly
    if ( $target_blog_id === $current_blog_id ) {
        $relative                 = $path ? $path . '/' : '';
        $cache[ $target_blog_id ] = home_url( '/' . $relative );
        return $cache[ $target_blog_id ];
    }

    // Determine mapping direction
    $direction = ( $current_blog_id === 3 ) ? 'no_to_en' : 'en_to_no';

    // Edge cases: empty path, search, 404
    if ( empty( $path ) || is_search() || is_404() ) {
        $cache[ $target_blog_id ] = acrylicon_get_fallback_url( $target_blog_id );
        return $cache[ $target_blog_id ];
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
            $cache[ $target_blog_id ] = acrylicon_get_fallback_url( $target_blog_id );
            return $cache[ $target_blog_id ];
        }

        $mapped_segments[] = acrylicon_map_slug( $segment, $direction );
    }

    if ( empty( $mapped_segments ) ) {
        $cache[ $target_blog_id ] = acrylicon_get_fallback_url( $target_blog_id );
        return $cache[ $target_blog_id ];
    }

    $mapped_path = implode( '/', $mapped_segments ) . '/';

    // Build target URL using switch_to_blog for correct home_url
    switch_to_blog( $target_blog_id );
    $target_url = home_url( '/' . $mapped_path );
    restore_current_blog();

    $cache[ $target_blog_id ] = $target_url;
    return $cache[ $target_blog_id ];
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
            class="absolute right-0 top-full mt-2 bg-acryl-beige-lightest rounded shadow-lg py-2 min-w-[160px] opacity-0 invisible transition-all duration-200 z-50"
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
 * Mobile menu switcher (full width, inline).
 */
function acrylicon_render_mobile_switcher( $languages, $current_blog_id ) {
    ?>
    <div class="flex flex-col w-full mt-4 border-t border-acryl-beige-light pt-4 px-6">
        <?php foreach ( $languages as $blog_id => $lang ) :
            $url       = acrylicon_get_equivalent_url( $blog_id );
            $is_active = ( $blog_id === $current_blog_id );
        ?>
            <a
                href="<?php echo esc_url( $url ); ?>"
                class="flex items-center gap-3 py-2 text-2xl <?php echo $is_active ? 'text-acryl-red' : 'text-acryl-black'; ?>"
                <?php echo $is_active ? 'aria-current="true"' : ''; ?>
            >
                <?php echo svg_icon( 'flags/' . $lang['flag'], [ 'width' => '24', 'height' => '16', 'class' => 'inline-block flex-shrink-0' ] ); ?>
                <span><?php echo esc_html( $lang['label'] ); ?></span>
            </a>
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
 */
function acrylicon_hreflang_tags() {
    $languages   = acrylicon_get_languages();
    $english_url = '';

    echo "\n<!-- Language alternates -->\n";

    foreach ( $languages as $blog_id => $lang ) {
        $url = acrylicon_get_equivalent_url( $blog_id );
        if ( $blog_id === 1 ) {
            $english_url = $url;
        }
        printf(
            '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
            esc_attr( $lang['hreflang'] ),
            esc_url( $url )
        );
    }

    // x-default reuses English URL
    printf(
        '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
        esc_url( $english_url )
    );
}
add_action( 'wp_head', 'acrylicon_hreflang_tags', 1 );
