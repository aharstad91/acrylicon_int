<?php
/**
 * Module 9: Legacy Redirects
 *
 * Handles dead URLs left over from the pre-cutover site structure (cutover June 2026).
 * The old acrylicon.no had an English section under /en/. English content now lives
 * on acrylicon.com, so any remaining /en/* request that would otherwise 404 is
 * 301-redirected to the English site.
 *
 * Note: /en/produkter/<slug>/ is already rescued by WordPress' built-in 404-slug
 * guessing (redirect_canonical, priority 10) → /produkter/<slug>/, so this handler
 * (priority 99, gated on is_404()) never touches those — only genuine dead /en/ URLs
 * (the /en/ root, old English pages, and the removed /en/wp-content/uploads/*.pdf files).
 */

class Acrylicon_SEO_Legacy_Redirects {

	public function __construct() {
		add_action( 'template_redirect', [ $this, 'redirect_legacy_en' ], 99 );
	}

	/**
	 * 301 dead /en/* URLs (old English section) to the English site.
	 * Only fires on the Norwegian domain and only for genuine 404s.
	 */
	public function redirect_legacy_en() {
		if ( ! is_404() ) {
			return;
		}

		// The /en/ section was unique to acrylicon.no — never redirect on other hosts.
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		if ( 'acrylicon.no' !== $host ) {
			return;
		}

		$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );

		// Match the /en root and the /en/ prefix only (not /english, /entreprenor, …).
		if ( '/en' !== $path && strpos( $path, '/en/' ) !== 0 ) {
			return;
		}

		wp_redirect( 'https://acrylicon.com/', 301 );
		exit;
	}
}
