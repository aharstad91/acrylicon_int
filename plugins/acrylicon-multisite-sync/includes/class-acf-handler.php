<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACF_Handler {

	/**
	 * Sync all ACF fields from source to target post
	 *
	 * @param int $source_post_id Source post ID
	 * @param int $target_post_id Target post ID
	 * @param int $source_blog_id Source blog ID
	 * @return array Array of error messages
	 */
	public function sync_fields( $source_post_id, $target_post_id, $source_blog_id ) {
		$errors = [];

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return [ 'ACF Pro is not active' ];
		}

		// Get field groups for this post type
		switch_to_blog( $source_blog_id );
		$post_type = get_post_type( $source_post_id );
		$field_groups = acf_get_field_groups( [ 'post_type' => $post_type ] );

		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['key'] );

			foreach ( $fields as $field ) {
				$field_name = $field['name'];

				// Skip relationship fields (set empty on target)
				if ( in_array( $field['type'], [ 'relationship', 'post_object' ] ) ) {
					continue;
				}

				// Get value from source
				$value = get_field( $field_name, $source_post_id );

				// Switch to target and sync
				switch_to_blog( get_current_blog_id() );
				$synced = $this->sync_field_safely( $field_name, $value, $target_post_id );

				if ( ! $synced ) {
					$errors[] = "ACF field '{$field_name}' could not be synced";
				}

				switch_to_blog( $source_blog_id );
			}
		}

		restore_current_blog();
		return $errors;
	}

	/**
	 * Safely sync a single ACF field with graceful error handling
	 */
	private function sync_field_safely( $field_name, $value, $target_post_id ) {
		$field_object = get_field_object( $field_name, $target_post_id );

		if ( $field_object ) {
			update_field( $field_name, $value, $target_post_id );
			return true;
		} else {
			// Field doesn't exist on target site - log warning, don't crash
			error_log( "ACF field '{$field_name}' not found on target site - skipping" );
			return false;
		}
	}
}
