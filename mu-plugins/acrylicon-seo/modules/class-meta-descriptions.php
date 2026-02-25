<?php
/**
 * Module 2: Meta Descriptions
 *
 * Outputs <meta name="description"> via wp_head.
 * Ported from themes/acrylicon-2024/inc/meta-descriptions.php
 * with added support for archives, taxonomy, and front page.
 *
 * Fallback chain per page:
 * 1. _acrylicon_seo_description postmeta
 * 2. _yoast_wpseo_metadesc postmeta (migration)
 * 3. Auto-generated from CPT data
 */

class Acrylicon_SEO_Meta_Descriptions {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output' ], 2 );
	}

	public function output() {
		// No description for noindex pages
		if ( is_404() || is_search() ) {
			return;
		}

		$description = $this->get_description();

		if ( ! empty( $description ) ) {
			printf(
				'<meta name="description" content="%s" />' . "\n",
				esc_attr( $description )
			);
		}
	}

	private function get_description() {
		$is_no = ( get_current_blog_id() === 3 );

		// Front page (check before is_singular since front page is also singular)
		if ( is_front_page() ) {
			$page_id = get_option( 'page_on_front' );
			if ( $page_id ) {
				$custom = get_post_meta( $page_id, '_acrylicon_seo_description', true );
				if ( ! empty( $custom ) ) {
					return $custom;
				}
			}
			return $is_no
				? 'AcryliCon leverer sømløse gulv- og veggløsninger for industri og næring i hele Norge.'
				: 'AcryliCon delivers seamless floor and wall solutions for industry and commercial use across Norway.';
		}

		// Singular pages
		if ( is_singular() ) {
			return $this->get_singular_description( $is_no );
		}

		// Industry archive
		if ( is_post_type_archive( 'industrier' ) ) {
			return $is_no
				? 'Industriløsninger fra AcryliCon — sømløse gulv for alle bransjer.'
				: 'Industrial solutions by AcryliCon — seamless flooring for all industries.';
		}

		// Taxonomy archive
		if ( is_tax() ) {
			$term = get_queried_object();
			if ( $term && ! is_wp_error( $term ) ) {
				return $is_no
					? "{$term->name} — se referanseprosjekter fra AcryliCon."
					: "{$term->name} — see reference projects by AcryliCon.";
			}
		}

		return '';
	}

	private function get_singular_description( $is_no ) {
		$post_id = get_the_ID();

		// 1. Custom postmeta
		$custom = get_post_meta( $post_id, '_acrylicon_seo_description', true );
		if ( ! empty( $custom ) ) {
			return $custom;
		}

		// 2. Yoast fallback
		$yoast = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! empty( $yoast ) ) {
			return $yoast;
		}

		// 3. Auto-generate from CPT data
		$post_type = get_post_type( $post_id );

		switch ( $post_type ) {
			case 'produkter':
				return $this->meta_produkter( $post_id, $is_no );
			case 'referanser':
				return $this->meta_referanser( $post_id, $is_no );
			case 'kontor':
				return $this->meta_kontor( $post_id, $is_no );
			case 'bruksomrader':
				return $this->meta_bruksomrader( $post_id, $is_no );
			case 'industrier':
				return $this->meta_industrier( $post_id, $is_no );
			case 'godegrunner':
			case 'levetidskostnader':
			case 'baerekreaftig':
				return $this->meta_generic_cpt( $post_id, $is_no );
			case 'page':
				return $this->meta_page( $post_id );
			default:
				return '';
		}
	}

	private function meta_produkter( $post_id, $is_no ) {
		$title   = get_the_title( $post_id );
		$excerpt = get_field( 'product_excerpt', $post_id );

		if ( ! empty( $excerpt ) ) {
			$clean = wp_strip_all_tags( $excerpt, true );
			$clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
			$meta  = "AcryliCon {$title} — {$clean}";
		} else {
			$meta = $is_no
				? "AcryliCon {$title} — profesjonelt gulvsystem for industri og næring fra AcryliCon."
				: "AcryliCon {$title} — professional flooring system for industry and commercial use.";
		}

		return $this->truncate( $meta );
	}

	private function meta_referanser( $post_id, $is_no ) {
		$title = get_the_title( $post_id );
		$terms = wp_get_post_terms( $post_id, 'referanser-produkter' );
		$term_names = [];

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$term_names = array_map( function( $t ) { return $t->name; }, $terms );
		}

		if ( ! empty( $term_names ) ) {
			$products = implode( ', ', array_slice( $term_names, 0, 2 ) );
			$meta = $is_no
				? "{$title} — referanseprosjekt med {$products} fra AcryliCon."
				: "{$title} — reference project with {$products} by AcryliCon.";
		} else {
			$meta = $is_no
				? "{$title} — referanseprosjekt fra AcryliCon. Sømløse industrigulv med dokumentert levetid."
				: "{$title} — reference project by AcryliCon. Seamless industrial flooring with proven durability.";
		}

		return $this->truncate( $meta );
	}

	private function meta_kontor( $post_id, $is_no ) {
		$title   = get_the_title( $post_id );
		$address = get_field( 'office_adress', $post_id );

		if ( ! empty( $address ) ) {
			$address = wp_strip_all_tags( $address, true );
			$meta = $is_no
				? "{$title} — {$address}. Kontakt oss for profesjonelle gulvløsninger i din region."
				: "{$title} — {$address}. Contact us for professional flooring solutions in your region.";
		} else {
			$meta = $is_no
				? "{$title} — AcryliCon kontor. Kontakt oss for profesjonelle gulvløsninger."
				: "{$title} — AcryliCon office. Contact us for professional flooring solutions.";
		}

		return $this->truncate( $meta );
	}

	private function meta_bruksomrader( $post_id, $is_no ) {
		$title = get_the_title( $post_id );
		$meta = $is_no
			? "Gulvløsninger for {$title} — skreddersydde gulv- og veggløsninger fra AcryliCon."
			: "Flooring solutions for {$title} — tailored floor and wall solutions by AcryliCon.";
		return $this->truncate( $meta );
	}

	private function meta_industrier( $post_id, $is_no ) {
		$title = get_the_title( $post_id );
		$meta = $is_no
			? "Gulvløsninger for {$title} — slitesterke og hygieniske systemer fra AcryliCon."
			: "Flooring for {$title} — durable and hygienic systems by AcryliCon.";
		return $this->truncate( $meta );
	}

	private function meta_generic_cpt( $post_id, $is_no ) {
		$title = get_the_title( $post_id );
		$meta = $is_no
			? "{$title} — les mer fra AcryliCon."
			: "{$title} — learn more from AcryliCon.";
		return $this->truncate( $meta );
	}

	private function meta_page( $post_id ) {
		$post = get_post( $post_id );

		if ( ! empty( $post->post_excerpt ) ) {
			return $this->truncate( $post->post_excerpt );
		}

		if ( ! empty( $post->post_content ) ) {
			$clean = wp_strip_all_tags( $post->post_content, true );
			return wp_trim_words( $clean, 25, '...' );
		}

		return '';
	}

	/**
	 * Truncate text to max chars on word boundary. mb-safe for æøå.
	 */
	private function truncate( $text, $max = 155 ) {
		$text = wp_strip_all_tags( $text, true );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );

		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}

		$text = mb_substr( $text, 0, $max );
		$last_space = mb_strrpos( $text, ' ' );
		if ( $last_space !== false ) {
			$text = mb_substr( $text, 0, $last_space );
		}

		return $text . '...';
	}
}
