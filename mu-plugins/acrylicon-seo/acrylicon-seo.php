<?php
/**
 * Plugin Name: AcryliCon SEO
 * Description: Custom SEO module — meta titles, descriptions, schema, OG, canonical, robots
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ACRYLICON_SEO_DIR', __DIR__ );

// Load all module classes (not instantiated yet)
foreach ( glob( ACRYLICON_SEO_DIR . '/modules/class-*.php' ) as $module ) {
	require_once $module;
}

// Register OG image size early (before init)
add_action( 'after_setup_theme', 'acrylicon_seo_register_image_sizes' );
function acrylicon_seo_register_image_sizes() {
	add_image_size( 'og-image', 1200, 630, true );
}

// Initialize modules at init:5 — before core builds the sitemap server (init:10),
// so the Sitemap module's wp_sitemaps_add_provider filter is registered in time.
// Constructors only register hooks (which fire later), so the earlier priority is safe.
add_action( 'init', 'acrylicon_seo_init', 5 );
function acrylicon_seo_init() {
	if ( ! defined( 'ACRYLICON_SEO_URL' ) ) {
		define( 'ACRYLICON_SEO_URL', plugins_url( '', __FILE__ ) );
	}

	new Acrylicon_SEO_Meta_Titles();
	new Acrylicon_SEO_Meta_Descriptions();
	new Acrylicon_SEO_Headings();
	new Acrylicon_SEO_Schema();
	new Acrylicon_SEO_Open_Graph();
	new Acrylicon_SEO_Canonical();
	new Acrylicon_SEO_Robots();
	new Acrylicon_SEO_Sitemap_Integration();
	new Acrylicon_SEO_Legacy_Redirects();
	new Acrylicon_SEO_Image_Alt();

	if ( is_admin() ) {
		new Acrylicon_SEO_Admin_Metabox();
	}
}
