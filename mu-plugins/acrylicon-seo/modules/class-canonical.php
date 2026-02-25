<?php
/**
 * Module 5: Canonical URLs
 *
 * Outputs <link rel="canonical"> in wp_head.
 * Replaces WordPress core rel_canonical() which only handles singular pages.
 * Adds archive, taxonomy, and pagination support.
 */

class Acrylicon_SEO_Canonical {

	public function __construct() {
		// Remove core canonical (handles singular only)
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', [ $this, 'output' ], 10 );
	}

	public function output() {
		// No canonical on 404 or search
		if ( is_404() || is_search() ) {
			return;
		}

		$canonical = $this->get_canonical();

		if ( $canonical ) {
			printf(
				'<link rel="canonical" href="%s" />' . "\n",
				esc_url( $canonical )
			);
		}
	}

	private function get_canonical() {
		// Singular: check custom postmeta first
		if ( is_singular() ) {
			$post_id = get_the_ID();

			$custom = get_post_meta( $post_id, '_acrylicon_seo_canonical', true );
			if ( ! empty( $custom ) ) {
				return $custom;
			}

			return get_permalink();
		}

		// Front page
		if ( is_front_page() ) {
			return home_url( '/' );
		}

		// CPT archive
		if ( is_post_type_archive() ) {
			$url = get_post_type_archive_link( get_queried_object()->name );
			return $this->maybe_paginate_url( $url );
		}

		// Taxonomy archive
		if ( is_tax() ) {
			$term = get_queried_object();
			$url  = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				return null;
			}
			return $this->maybe_paginate_url( $url );
		}

		return null;
	}

	/**
	 * Self-referencing canonical includes /page/N/ for pagination.
	 */
	private function maybe_paginate_url( $url ) {
		$paged = get_query_var( 'paged', 0 );
		if ( $paged > 1 ) {
			$url = trailingslashit( $url ) . 'page/' . $paged . '/';
		}
		return $url;
	}
}
