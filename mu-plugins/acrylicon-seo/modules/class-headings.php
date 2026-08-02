<?php
/**
 * Module 9: Heading normalization (SEO H1)
 *
 * Many pages author their hero heading as a level-2 core heading block
 * (<h2 class="wp-block-heading ...">), which leaves the page with NO <h1>.
 * Google relies on a single, descriptive H1. This filter ensures exactly one:
 *
 *   - If the rendered main content already contains an <h1>, leave it untouched.
 *   - Otherwise promote the FIRST heading (h2–h4) to <h1>, preserving its
 *     attributes/classes — so there is zero visual change (size is driven by
 *     the Tailwind classes, not the tag).
 *   - If there is no heading at all (e.g. classic blog posts), prepend the
 *     post title as an <h1>.
 *
 * Runs once per request, only on the main singular content. No block in this
 * theme calls the_content() recursively, so a simple once-guard is safe.
 * Priority 20 = after do_blocks (9) and wpautop (10), so $content is rendered
 * HTML with real <h2> tags.
 */

class Acrylicon_SEO_Headings {

	private $done = false;

	public function __construct() {
		add_filter( 'the_content', [ $this, 'ensure_h1' ], 20 );
	}

	public function ensure_h1( $content ) {
		if ( $this->done ) {
			return $content;
		}
		if ( is_admin() || ! is_singular() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}
		$this->done = true;

		// Already has an H1 → respect it.
		if ( preg_match( '/<h1[\s>]/i', $content ) ) {
			return $content;
		}

		// Promote the first heading (h2–h4) to <h1>, keeping its attributes.
		$count = 0;
		$promoted = preg_replace_callback(
			'/<h([2-4])(\s[^>]*)?>(.*?)<\/h\1>/is',
			function ( $m ) {
				return '<h1' . $m[2] . '>' . $m[3] . '</h1>';
			},
			$content,
			1,
			$count
		);

		if ( $count > 0 ) {
			return $promoted;
		}

		// No heading at all → prepend the title as the H1.
		$title = get_the_title();
		if ( $title ) {
			return '<h1 class="wp-block-heading lg:text-5xl md:text-4xl text-3xl font-normal mb-6">'
				. esc_html( $title ) . '</h1>' . $content;
		}

		return $content;
	}
}
