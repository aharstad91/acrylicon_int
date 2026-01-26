<?php
namespace Acrylicon_Multisite_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Media_Handler {

	/**
	 * Copy media file from source blog to target blog
	 *
	 * @param int $attachment_id Source attachment ID
	 * @param int $target_blog_id Target blog/site ID
	 * @param int $source_blog_id Source blog/site ID
	 * @return int|false New attachment ID on success, false on failure
	 */
	public function copy_media( $attachment_id, $target_blog_id, $source_blog_id ) {
		// Get source file path
		switch_to_blog( $source_blog_id );
		$file_path = wp_get_original_image_path( $attachment_id );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );
		restore_current_blog();

		if ( ! file_exists( $file_path ) ) {
			error_log( "Media file not found: $attachment_id at $file_path" );
			return false;
		}

		// Switch to target site
		switch_to_blog( $target_blog_id );

		// Generate unique filename
		$upload_dir = wp_upload_dir();
		$filename = wp_unique_filename( $upload_dir['path'], basename( $file_path ) );
		$new_file = $upload_dir['path'] . '/' . $filename;

		// Check if file already exists (skip duplicate)
		if ( file_exists( $new_file ) ) {
			restore_current_blog();
			error_log( "Media already exists, skipping: $filename" );
			return false;
		}

		// Copy file
		if ( ! @copy( $file_path, $new_file ) ) {
			restore_current_blog();
			error_log( "Failed to copy media: $file_path to $new_file" );
			return false;
		}

		// Register attachment in database
		$attachment_data = [
			'guid' => $upload_dir['url'] . '/' . $filename,
			'post_mime_type' => mime_content_type( $new_file ),
			'post_title' => pathinfo( $filename, PATHINFO_FILENAME ),
			'post_content' => '',
			'post_status' => 'inherit'
		];

		$attach_id = wp_insert_attachment( $attachment_data, $new_file );

		if ( is_wp_error( $attach_id ) ) {
			@unlink( $new_file ); // Cleanup file on failure
			restore_current_blog();
			return false;
		}

		// Generate metadata (thumbnails, etc.)
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		wp_generate_attachment_metadata( $attach_id, $new_file );

		restore_current_blog();
		return $attach_id;
	}
}
