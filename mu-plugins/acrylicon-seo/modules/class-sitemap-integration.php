<?php
/**
 * Module 8: Sitemap Integration
 *
 * Filters WordPress core sitemaps to exclude noindexed posts.
 * Redirects old Yoast sitemap URLs to wp-sitemap.xml.
 * WordPress 6.8.3 already includes lastmod natively.
 */

class Acrylicon_SEO_Sitemap_Integration {

	public function __construct() {
		add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'exclude_noindex' ], 10, 2 );
		add_filter( 'wp_sitemaps_add_provider', [ $this, 'remove_users_sitemap' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'redirect_yoast_sitemap' ], 5 );
	}

	/**
	 * Drop the users (authors) sitemap — author archives are noindexed and author
	 * enumeration is blocked, so exposing them in the sitemap is a conflicting signal.
	 */
	public function remove_users_sitemap( $provider, $name ) {
		return ( 'users' === $name ) ? false : $provider;
	}

	/**
	 * Exclude posts with _acrylicon_seo_robots = "noindex" from sitemaps.
	 */
	public function exclude_noindex( $args, $post_type ) {
		$args['meta_query'] = $args['meta_query'] ?? [];
		$args['meta_query'][] = [
			'relation' => 'OR',
			[ 'key' => '_acrylicon_seo_robots', 'compare' => 'NOT EXISTS' ],
			[ 'key' => '_acrylicon_seo_robots', 'value' => 'noindex', 'compare' => '!=' ],
		];
		return $args;
	}

	/**
	 * Redirect old Yoast sitemap URLs to WordPress core sitemap.
	 * Matches both sitemap_index.xml and per-type sitemaps (post-sitemap.xml, etc.).
	 */
	public function redirect_yoast_sitemap() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';

		// Early exit for non-sitemap URLs
		if ( strpos( $uri, 'sitemap' ) === false ) {
			return;
		}

		// Don't redirect WordPress core sitemap URLs (wp-sitemap*.xml)
		if ( strpos( $uri, 'wp-sitemap' ) !== false ) {
			return;
		}

		if ( preg_match( '#/sitemap_index\.xml#', $uri ) ||
		     preg_match( '#/[a-z-]+-sitemap\d*\.xml#', $uri ) ) {
			wp_redirect( home_url( '/wp-sitemap.xml' ), 301 );
			exit;
		}
	}
}
