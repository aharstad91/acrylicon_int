<?php
/**
 * Plugin Name: Acrylicon Multisite Sync
 * Plugin URI: https://acrylicon.com
 * Description: Synkroniser innhold fra engelsk til norsk site i multisite-nettverk
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.0
 * Author: Acrylicon
 * Text Domain: acrylicon-multisite-sync
 * Domain Path: /languages
 * Network: false
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'ACRYLICON_SYNC_VERSION', '1.0.0' );
define( 'ACRYLICON_SYNC_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ACRYLICON_SYNC_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'ACRYLICON_SYNC_BASENAME', plugin_basename( __FILE__ ) );

// Load plugin classes
require_once ACRYLICON_SYNC_PATH . '/includes/class-sync-manager.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-media-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-acf-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-taxonomy-handler.php';
require_once ACRYLICON_SYNC_PATH . '/includes/class-admin-ui.php';

// Initialize plugin
function acrylicon_sync_init() {
	// Only load for administrators
	if ( ! current_user_can( 'manage_network' ) ) {
		return;
	}

	// Initialize admin UI
	new Acrylicon_Multisite_Sync\Admin_UI();
}
add_action( 'plugins_loaded', 'acrylicon_sync_init' );

// Activation hook
register_activation_hook( __FILE__, 'acrylicon_sync_activate' );
function acrylicon_sync_activate() {
	// Check requirements
	if ( ! is_multisite() ) {
		deactivate_plugins( ACRYLICON_SYNC_BASENAME );
		wp_die( 'Dette pluginet krever WordPress Multisite.' );
	}

	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		deactivate_plugins( ACRYLICON_SYNC_BASENAME );
		wp_die( 'Dette pluginet krever PHP 8.0 eller høyere.' );
	}

	// Log activation
	error_log( 'Acrylicon Multisite Sync aktivert på site ' . get_current_blog_id() );
}
