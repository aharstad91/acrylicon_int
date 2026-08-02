<?php
/**
 * Module 11: Image Alt Fallback
 *
 * Content-embedded <img> tags freeze their alt attribute at insert time,
 * so alt text added to the media library later never reaches old content.
 * This module fills empty/missing alt attributes at render time from
 * _wp_attachment_image_alt, via the wp_content_img_tag filter (WP 6.0+,
 * applied to the_content, the_excerpt and block widget content).
 *
 * Images without alt text in the media library are left untouched
 * (empty alt = decorative, which is correct for logos/icons).
 */

class Acrylicon_SEO_Image_Alt {

	public function __construct() {
		add_filter( 'wp_content_img_tag', [ $this, 'fill_empty_alt' ], 10, 3 );
	}

	/**
	 * @param string $filtered_image Full <img> tag.
	 * @param string $context        Filter context (e.g. 'the_content').
	 * @param int    $attachment_id  Attachment ID from wp-image-N class, 0 if unknown.
	 */
	public function fill_empty_alt( $filtered_image, $context, $attachment_id ) {
		if ( ! $attachment_id ) {
			return $filtered_image;
		}

		// Respect any existing non-empty alt
		if ( preg_match( '/\balt=(["\'])(?!\1)[^"\']*\1/', $filtered_image ) ) {
			return $filtered_image;
		}

		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( '' === $alt ) {
			return $filtered_image;
		}

		$alt_attr = 'alt="' . esc_attr( $alt ) . '"';

		// Replace empty alt=""/alt='' if present, otherwise inject after <img
		$replaced = preg_replace( '/\balt=(["\'])\1/', $alt_attr, $filtered_image, 1, $count );
		if ( $count > 0 ) {
			return $replaced;
		}

		return preg_replace( '/<img\s/', '<img ' . $alt_attr . ' ', $filtered_image, 1 );
	}
}
