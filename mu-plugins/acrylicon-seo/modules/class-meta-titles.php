<?php
/**
 * Module 1: Meta Titles
 *
 * Filters document_title_parts for all page types.
 * Manual override via _acrylicon_seo_title postmeta.
 * Yoast migration fallback via _yoast_wpseo_title postmeta.
 */

class Acrylicon_SEO_Meta_Titles {

	public function __construct() {
		add_filter( 'document_title_parts', [ $this, 'filter_title' ] );
		add_filter( 'document_title_separator', [ $this, 'separator' ] );
	}

	public function separator( $sep ) {
		return '|';
	}

	public function filter_title( $title ) {
		$is_no = ( get_current_blog_id() === 3 );

		// Always use consistent brand name in title
		if ( isset( $title['site'] ) ) {
			$title['site'] = 'AcryliCon';
		}

		// Front page (must be checked before is_singular since front page is also singular)
		if ( is_front_page() ) {
			$title['title'] = 'AcryliCon';
			$title['tagline'] = $is_no
				? 'Sømløse gulv- og veggløsninger'
				: 'Seamless Floor and Wall Solutions';
			unset( $title['site'] );
			return $title;
		}

		// Singular pages: check manual override and Yoast fallback
		if ( is_singular() ) {
			$post_id = get_the_ID();

			$custom = get_post_meta( $post_id, '_acrylicon_seo_title', true );
			if ( ! empty( $custom ) ) {
				$title['title'] = $custom;
				return $title;
			}

			$yoast = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			if ( ! empty( $yoast ) ) {
				$title['title'] = $this->parse_yoast_title( $yoast );
				return $title;
			}
		}

		// Search
		if ( is_search() ) {
			$query = get_search_query();
			$title['title'] = $is_no ? "Søk: {$query}" : "Search: {$query}";
		}

		// 404
		if ( is_404() ) {
			$title['title'] = $is_no ? 'Side ikke funnet' : 'Page Not Found';
		}

		// Taxonomy archive
		if ( is_tax() ) {
			$term  = get_queried_object();
			$label = $is_no ? 'Referanser' : 'References';
			$title['title'] = "{$term->name} — {$label}";
		}

		// CPT archive (industrier)
		if ( is_post_type_archive( 'industrier' ) ) {
			$title['title'] = $is_no ? 'Industrier' : 'Industries';
		}

		// Author archive
		if ( is_author() ) {
			$title['title'] = $is_no ? 'Forfatter' : 'Author';
		}

		// Date archive
		if ( is_date() ) {
			$title['title'] = $is_no ? 'Arkiv' : 'Archive';
		}

		return $title;
	}

	/**
	 * Parse basic Yoast title variables.
	 * Only %%title%% and %%sitename%% are supported (migration fallback).
	 */
	private function parse_yoast_title( $template ) {
		$post = get_post();
		$replacements = [
			'%%title%%'    => $post ? $post->post_title : '',
			'%%sitename%%' => get_bloginfo( 'name' ),
			'%%sep%%'      => '|',
		];
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}
}
