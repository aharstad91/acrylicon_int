<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Taxonomy_Handler {

	/**
	 * Sync taxonomies from source to target post
	 * Taxonomies are already shared, so we just assign the same term IDs
	 *
	 * @param int $source_post_id Source post ID
	 * @param int $target_post_id Target post ID
	 * @param int $source_blog_id Source blog ID
	 */
	public function sync_taxonomies( $source_post_id, $target_post_id, $source_blog_id ) {
		$current_blog = get_current_blog_id();

		// Get all taxonomies for source post type
		switch_to_blog( $source_blog_id );
		$taxonomies = get_object_taxonomies( get_post_type( $source_post_id ) );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $source_post_id, $taxonomy, [ 'fields' => 'ids' ] );

			// Switch back to target
			switch_to_blog( $current_blog );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				// Assign same term IDs (terms are shared via MU-plugin)
				wp_set_post_terms( $target_post_id, $terms, $taxonomy );
			}

			switch_to_blog( $source_blog_id );
		}

		switch_to_blog( $current_blog );
	}
}
