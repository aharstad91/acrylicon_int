<?php
/**
 * Module 6: Robots Meta
 *
 * Uses WordPress 5.7+ wp_robots filter.
 * Core already handles search noindex via wp_robots_noindex_search().
 * We add: 404, author archive, date archive, paginated, per-post noindex.
 */

class Acrylicon_SEO_Robots {

	public function __construct() {
		add_filter( 'wp_robots', [ $this, 'filter_robots' ] );
		// Feeds have no <head>, so wp_robots can't reach them — send the header instead.
		add_action( 'template_redirect', [ $this, 'noindex_feeds' ] );
	}

	public function noindex_feeds() {
		if ( is_feed() && ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, follow' );
		}
	}

	public function filter_robots( $robots ) {
		// Per-post noindex from postmeta
		if ( is_singular() ) {
			$noindex = get_post_meta( get_the_ID(), '_acrylicon_seo_robots', true );
			if ( $noindex === 'noindex' ) {
				$robots['noindex'] = true;
				$robots['follow']  = true;
				unset( $robots['max-image-preview'] );
				return $robots;
			}
		}

		// 404
		if ( is_404() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
			return $robots;
		}

		// Author archive
		if ( is_author() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
			return $robots;
		}

		// Standard category/tag archives on the international (EN) site.
		// Blog 1 has no editorial blog — its only category is the legacy
		// Norwegian "Ukategorisert" pulling in untranslated NO posts.
		// (Reference archives use custom taxonomies, so they're unaffected.)
		if ( ( is_category() || is_tag() ) && get_current_blog_id() === 1 ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
			return $robots;
		}

		// Date archive
		if ( is_date() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
			return $robots;
		}

		// Paginated pages (page 2+)
		$paged = get_query_var( 'paged', 0 );
		if ( $paged > 1 ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
			return $robots;
		}

		return $robots;
	}
}
