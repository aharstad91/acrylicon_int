<?php
/**
 * Module 4: Open Graph + Twitter Card
 *
 * Outputs og: meta tags and twitter:card in wp_head.
 * og:type = website for all pages (B2B site, not blog).
 * Only twitter:card needed — rest falls back to OG tags.
 */

class Acrylicon_SEO_Open_Graph {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output' ], 3 );
	}

	public function output() {
		// No OG on noindex pages
		if ( is_404() || is_search() ) {
			return;
		}

		$is_no = ( get_current_blog_id() === 3 );

		$title       = $this->get_og_title();
		$description = $this->get_og_description( $is_no );
		$url         = $this->get_og_url();
		$image       = $this->get_og_image();
		$locale      = $is_no ? 'nb_NO' : 'en_GB';
		$alt_locale  = $is_no ? 'en_GB' : 'nb_NO';

		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="AcryliCon" />' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '" />' . "\n";
		echo '<meta property="og:locale:alternate" content="' . esc_attr( $alt_locale ) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";

		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
			echo '<meta property="og:image:width" content="1200" />' . "\n";
			echo '<meta property="og:image:height" content="630" />' . "\n";
		}

		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	}

	private function get_og_title() {
		if ( is_front_page() ) {
			$is_no = ( get_current_blog_id() === 3 );
			return $is_no
				? 'AcryliCon — Sømløse gulv- og veggløsninger'
				: 'AcryliCon — Seamless Floor and Wall Solutions';
		}

		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_post_type_archive() ) {
			// CPT labels are registered in Norwegian, so labels->name would leak
			// "Industrier" onto the English archive. Use the language-correct title.
			return wp_get_document_title();
		}

		if ( is_tax() ) {
			$term = get_queried_object();
			return $term->name . ' | AcryliCon';
		}

		return get_bloginfo( 'name' );
	}

	private function get_og_description( $is_no ) {
		if ( is_front_page() ) {
			return $is_no
				? 'AcryliCon leverer sømløse gulv- og veggløsninger for industri og næring i hele Norge.'
				: 'AcryliCon delivers seamless floor and wall solutions for industry and commercial use across Norway.';
		}

		if ( is_singular() ) {
			$post_id = get_the_ID();

			$custom = get_post_meta( $post_id, '_acrylicon_seo_description', true );
			if ( ! empty( $custom ) ) {
				return $custom;
			}

			$post = get_post();
			if ( ! empty( $post->post_excerpt ) ) {
				return wp_strip_all_tags( $post->post_excerpt, true );
			}
			if ( ! empty( $post->post_content ) ) {
				return wp_trim_words( wp_strip_all_tags( $post->post_content, true ), 25, '...' );
			}
		}

		if ( is_tax() ) {
			$term = get_queried_object();
			return $is_no
				? "{$term->name} — se referanseprosjekter fra AcryliCon."
				: "{$term->name} — see reference projects by AcryliCon.";
		}

		return $is_no
			? 'Sømløse gulv- og veggløsninger fra AcryliCon.'
			: 'Seamless floor and wall solutions by AcryliCon.';
	}

	private function get_og_url() {
		if ( is_singular() ) {
			return get_permalink();
		}
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_post_type_archive() ) {
			return get_post_type_archive_link( get_queried_object()->name );
		}
		if ( is_tax() ) {
			$term = get_queried_object();
			$link = get_term_link( $term );
			return is_wp_error( $link ) ? home_url( '/' ) : $link;
		}

		// Paginated: include page number for consistency with canonical
		global $wp;
		return home_url( $wp->request );
	}

	private function get_og_image() {
		// Singular: featured image in og-image size
		if ( is_singular() ) {
			$image = get_the_post_thumbnail_url( get_the_ID(), 'og-image' );
			if ( $image ) {
				return $image;
			}
			// Fallback to full size
			$image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
			if ( $image ) {
				return $image;
			}
		}

		// Site-wide fallback
		$default = get_template_directory_uri() . '/assets/gfx/acrylicon-og-default.jpg';
		return $default;
	}
}
