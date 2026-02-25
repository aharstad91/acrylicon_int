<?php
/**
 * Meta Description Fallbacks
 *
 * Generates meta descriptions from ACF data when Yoast SEO field is empty.
 * Yoast has no built-in fallback — without this, pages get no meta description tag.
 *
 * @package Acrylicon2024
 */

/**
 * Truncate text to a maximum character length on a word boundary.
 * Uses mb_* functions for Norwegian æøå safety.
 */
function acrylicon_truncate_meta( $text, $max = 155 ) {
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

	return $text . '…';
}

/**
 * Main filter: generate meta description when Yoast field is empty.
 *
 * Filter signature: apply_filters( 'wpseo_metadesc', $description, $presentation )
 * $presentation->model has: object_type, object_sub_type, object_id
 */
add_filter( 'wpseo_metadesc', 'acrylicon_meta_description_fallback', 10, 2 );

function acrylicon_meta_description_fallback( $description, $presentation ) {
	// Keep manually written Yoast descriptions
	if ( ! empty( trim( $description ) ) ) {
		return $description;
	}

	// Only handle singular posts — skip archives, 404, search, taxonomy pages
	$object_type     = $presentation->model->object_type ?? '';
	$object_sub_type = $presentation->model->object_sub_type ?? '';
	$object_id       = $presentation->model->object_id ?? 0;

	if ( 'post' !== $object_type || empty( $object_id ) ) {
		return $description;
	}

	$is_norwegian = ( get_current_blog_id() === 3 );

	switch ( $object_sub_type ) {
		case 'produkter':
		case 'products':
			return acrylicon_meta_produkter( $object_id, $is_norwegian );

		case 'referanser':
		case 'references':
			return acrylicon_meta_referanser( $object_id, $is_norwegian );

		case 'kontor':
		case 'offices':
			return acrylicon_meta_kontor( $object_id, $is_norwegian );

		case 'bruksomrader':
		case 'applications':
			return acrylicon_meta_bruksomrader( $object_id, $is_norwegian );

		case 'industrier':
		case 'industries':
			return acrylicon_meta_industrier( $object_id, $is_norwegian );

		case 'page':
			return acrylicon_meta_page( $object_id, $is_norwegian );

		default:
			return $description;
	}
}

/**
 * Produkter: Use product_excerpt ACF field (bullet-style text).
 * 10/12 products have this field. Fallback to title for the 2 without.
 */
function acrylicon_meta_produkter( $post_id, $is_norwegian ) {
	$title   = get_the_title( $post_id );
	$excerpt = get_field( 'product_excerpt', $post_id );

	if ( ! empty( $excerpt ) ) {
		$clean = wp_strip_all_tags( $excerpt, true );
		// Excerpt is "/" or newline separated bullets — already readable
		$clean = trim( preg_replace( '/\s+/', ' ', $clean ) );

		if ( $is_norwegian ) {
			$meta = "AcryliCon {$title} — {$clean}";
		} else {
			$meta = "AcryliCon {$title} — {$clean}";
		}
	} else {
		if ( $is_norwegian ) {
			$meta = "AcryliCon {$title} — profesjonelt gulvsystem for industri og næring fra AcryliCon.";
		} else {
			$meta = "AcryliCon {$title} — professional flooring system for industry and commercial use.";
		}
	}

	return acrylicon_truncate_meta( $meta );
}

/**
 * Referanser: Use title + referanse-produkter taxonomy terms.
 * ~58/100 references have product terms assigned.
 */
function acrylicon_meta_referanser( $post_id, $is_norwegian ) {
	$title = get_the_title( $post_id );
	// Taxonomy ID is always 'referanser-produkter' on both blogs
	// (only the URL rewrite slug differs per blog)
	$tax = 'referanser-produkter';

	$terms = wp_get_post_terms( $post_id, $tax );
	$term_names = [];

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$term_names = array_map( function( $t ) { return $t->name; }, $terms );
	}

	if ( ! empty( $term_names ) ) {
		$products = implode( ', ', array_slice( $term_names, 0, 2 ) );
		if ( $is_norwegian ) {
			$meta = "{$title} — referanseprosjekt med {$products} fra AcryliCon.";
		} else {
			$meta = "{$title} — reference project with {$products} by AcryliCon.";
		}
	} else {
		if ( $is_norwegian ) {
			$meta = "{$title} — referanseprosjekt fra AcryliCon. Sømløse industrigulv med dokumentert levetid.";
		} else {
			$meta = "{$title} — reference project by AcryliCon. Seamless industrial flooring with proven durability.";
		}
	}

	return acrylicon_truncate_meta( $meta );
}

/**
 * Kontor: Use office_adress ACF field. All 5 offices have data.
 */
function acrylicon_meta_kontor( $post_id, $is_norwegian ) {
	$title   = get_the_title( $post_id );
	$address = get_field( 'office_adress', $post_id );

	if ( ! empty( $address ) ) {
		$address = wp_strip_all_tags( $address, true );
		if ( $is_norwegian ) {
			$meta = "{$title} — {$address}. Kontakt oss for profesjonelle gulvløsninger i din region.";
		} else {
			$meta = "{$title} — {$address}. Contact us for professional flooring solutions in your region.";
		}
	} else {
		if ( $is_norwegian ) {
			$meta = "{$title} — AcryliCon kontor. Kontakt oss for profesjonelle gulvløsninger.";
		} else {
			$meta = "{$title} — AcryliCon office. Contact us for professional flooring solutions.";
		}
	}

	return acrylicon_truncate_meta( $meta );
}

/**
 * Bruksomrader: Title-based template (no ACF fields available).
 */
function acrylicon_meta_bruksomrader( $post_id, $is_norwegian ) {
	$title = get_the_title( $post_id );

	if ( $is_norwegian ) {
		$meta = "Gulvløsninger for {$title} — skreddersydde gulv- og veggløsninger fra AcryliCon.";
	} else {
		$meta = "Flooring solutions for {$title} — tailored floor and wall solutions by AcryliCon.";
	}

	return acrylicon_truncate_meta( $meta );
}

/**
 * Industrier: Title-based template (no ACF fields available).
 */
function acrylicon_meta_industrier( $post_id, $is_norwegian ) {
	$title = get_the_title( $post_id );

	if ( $is_norwegian ) {
		$meta = "Gulvløsninger for {$title} — slitesterke og hygieniske systemer fra AcryliCon.";
	} else {
		$meta = "Flooring for {$title} — durable and hygienic systems by AcryliCon.";
	}

	return acrylicon_truncate_meta( $meta );
}

/**
 * Pages: Use post_excerpt, fallback to first 25 words of content.
 * Excerpt is enabled for pages via functions.php:14.
 */
function acrylicon_meta_page( $post_id, $is_norwegian ) {
	$post = get_post( $post_id );

	if ( ! empty( $post->post_excerpt ) ) {
		return acrylicon_truncate_meta( $post->post_excerpt );
	}

	if ( ! empty( $post->post_content ) ) {
		$clean = wp_strip_all_tags( $post->post_content, true );
		$words = wp_trim_words( $clean, 25, '…' );
		return $words;
	}

	return '';
}
