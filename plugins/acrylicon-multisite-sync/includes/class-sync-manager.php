<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sync_Manager {

	private $media_handler;
	private $acf_handler;
	private $taxonomy_handler;

	public function __construct() {
		$this->media_handler = new Media_Handler();
		$this->acf_handler = new ACF_Handler();
		$this->taxonomy_handler = new Taxonomy_Handler();
	}

	/**
	 * Main sync orchestration with draft-first pattern
	 *
	 * @param int $source_post_id Source post ID
	 * @param int $target_blog_id Target blog/site ID
	 * @return array ['success' => bool, 'post_id' => int, 'errors' => array]
	 */
	public function sync_post( $source_post_id, $target_blog_id ) {
		// Increase limits for large media
		$this->prepare_environment();

		$source_blog_id = get_current_blog_id();
		$errors = [];

		// Switch to target site
		switch_to_blog( $target_blog_id );

		try {
			// STEP 1: Create draft post (safe, can be deleted)
			$new_post_id = $this->create_draft_post( $source_post_id, $source_blog_id );

			if ( is_wp_error( $new_post_id ) ) {
				throw new \Exception( $new_post_id->get_error_message() );
			}

			// STEP 2: Copy content
			$this->copy_post_content( $source_post_id, $new_post_id, $source_blog_id );

			// STEP 3: Copy featured image
			$thumb_errors = $this->copy_featured_image( $source_post_id, $new_post_id, $source_blog_id );
			if ( ! empty( $thumb_errors ) ) {
				$errors = array_merge( $errors, $thumb_errors );
			}

			// STEP 4: Copy ACF fields
			$acf_errors = $this->acf_handler->sync_fields( $source_post_id, $new_post_id, $source_blog_id );
			if ( ! empty( $acf_errors ) ) {
				$errors = array_merge( $errors, $acf_errors );
			}

			// STEP 5: Assign taxonomies
			$this->taxonomy_handler->sync_taxonomies( $source_post_id, $new_post_id, $source_blog_id );

			// STEP 6: Save sync metadata
			$this->save_sync_metadata( $source_post_id, $new_post_id, $source_blog_id, $target_blog_id, $errors );

			restore_current_blog();

			return [
				'success' => true,
				'post_id' => $new_post_id,
				'errors' => $errors
			];

		} catch ( \Exception $e ) {
			// CLEANUP on failure
			if ( isset( $new_post_id ) && $new_post_id ) {
				$this->cleanup_failed_sync( $new_post_id );
			}

			restore_current_blog();

			// Log error
			error_log( sprintf(
				'[Acrylicon Sync] FAILED - Source: %d, Target Blog: %d, Error: %s',
				$source_post_id,
				$target_blog_id,
				$e->getMessage()
			) );

			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	/**
	 * Prepare environment for large media operations
	 */
	private function prepare_environment() {
		@ini_set( 'memory_limit', '256M' );
		@ini_set( 'max_execution_time', '300' );
		@set_time_limit( 300 );
	}

	/**
	 * Create draft post on target site
	 */
	private function create_draft_post( $source_post_id, $source_blog_id ) {
		switch_to_blog( $source_blog_id );
		$post_type = get_post_type( $source_post_id );
		restore_current_blog();

		$new_post_id = wp_insert_post( [
			'post_title' => 'Synkroniserer...',
			'post_status' => 'draft',
			'post_type' => $post_type,
			'post_author' => get_current_user_id()
		] );

		return $new_post_id;
	}

	/**
	 * Copy post content fields
	 */
	private function copy_post_content( $source_post_id, $target_post_id, $source_blog_id ) {
		switch_to_blog( $source_blog_id );
		$source_post = get_post( $source_post_id );
		restore_current_blog();

		wp_update_post( [
			'ID' => $target_post_id,
			'post_title' => $source_post->post_title,
			'post_content' => $source_post->post_content,
			'post_excerpt' => $source_post->post_excerpt,
		] );
	}

	/**
	 * Copy featured image
	 */
	private function copy_featured_image( $source_post_id, $target_post_id, $source_blog_id ) {
		$errors = [];

		switch_to_blog( $source_blog_id );
		$thumb_id = get_post_thumbnail_id( $source_post_id );
		restore_current_blog();

		if ( $thumb_id ) {
			$new_thumb_id = $this->media_handler->copy_media( $thumb_id, get_current_blog_id(), $source_blog_id );

			if ( $new_thumb_id ) {
				set_post_thumbnail( $target_post_id, $new_thumb_id );
			} else {
				$errors[] = 'Featured image copy failed';
			}
		}

		return $errors;
	}

	/**
	 * Save sync metadata on both posts
	 */
	private function save_sync_metadata( $source_post_id, $target_post_id, $source_blog_id, $target_blog_id, $errors ) {
		// On target post
		update_post_meta( $target_post_id, '_synced_from_post', $source_post_id );
		update_post_meta( $target_post_id, '_synced_from_blog', $source_blog_id );
		update_post_meta( $target_post_id, '_synced_date', current_time( 'mysql' ) );
		update_post_meta( $target_post_id, '_synced_by_user', get_current_user_id() );

		if ( ! empty( $errors ) ) {
			update_post_meta( $target_post_id, '_sync_errors', $errors );
		}

		// On source post
		switch_to_blog( $source_blog_id );
		update_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id, $target_post_id );
		update_post_meta( $source_post_id, '_synced_to_blog_' . $target_blog_id, $target_blog_id );
		update_post_meta( $source_post_id, '_synced_date_' . $target_blog_id, current_time( 'mysql' ) );
		restore_current_blog();
	}

	/**
	 * Cleanup failed sync - delete post and orphan attachments
	 */
	private function cleanup_failed_sync( $post_id ) {
		// Get all attachments for this post
		$attachments = get_posts( [
			'post_type' => 'attachment',
			'post_parent' => $post_id,
			'numberposts' => -1
		] );

		// Delete attachments (files + database)
		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		// Delete post (force delete, not trash)
		wp_delete_post( $post_id, true );
	}

	/**
	 * Check if post is already synced to target blog
	 */
	public function is_synced( $source_post_id, $target_blog_id ) {
		$synced_post_id = get_post_meta( $source_post_id, '_synced_to_post_' . $target_blog_id, true );
		return ! empty( $synced_post_id );
	}
}
