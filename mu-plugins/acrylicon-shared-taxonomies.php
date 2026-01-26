<?php
/**
 * Plugin Name: Acrylicon Shared Taxonomies
 * Description: Forces all multisite blogs to share taxonomy tables from main site
 * Version: 1.0.0
 * Author: Acrylicon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force all sites to use main site taxonomy tables.
 * Must run before 'init' and on every blog switch.
 */
add_action( 'init', 'acrylicon_share_taxonomy_tables', 0 );
add_action( 'switch_blog', 'acrylicon_share_taxonomy_tables', 0 );

function acrylicon_share_taxonomy_tables() {
	global $wpdb;

	// Force use of main site tables instead of blog-specific tables
	$wpdb->terms = $wpdb->base_prefix . 'terms';
	$wpdb->term_taxonomy = $wpdb->base_prefix . 'term_taxonomy';
	$wpdb->term_relationships = $wpdb->base_prefix . 'term_relationships';
}
