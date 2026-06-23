<?php
/**
 * Plugin Name: Acrylicon Security
 * Description: Stenger users-endepunktene i REST API for uinnloggede, skjuler WP-versjon, og blokkerer numerisk author-enumerering.
 * Version: 1.1.0
 * Author: Acrylicon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fjern /wp/v2/users-endepunktene fra REST for uinnloggede besøkende.
 * Innloggede brukere (wp-admin, blokk-editor) beholder full tilgang.
 */
add_filter( 'rest_endpoints', 'acrylicon_restrict_users_endpoint' );

function acrylicon_restrict_users_endpoint( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	unset( $endpoints['/wp/v2/users'] );
	unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

	return $endpoints;
}

/**
 * Skjul WordPress-versjonen (reduserer fingerprinting for målrettede exploits).
 * Fjerner <meta name="generator"> og generator-taggen i RSS/Atom-feeds.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Blokker numerisk author-enumerering (?author=N), som ellers 301-redirecter til
 * /author/<user_nicename>/ og lekker innloggingsavledede brukernavn. Slug-baserte
 * author-arkiv (en innholdsfunksjon på bloggen) påvirkes ikke. Innloggede beholder tilgang.
 */
add_action( 'template_redirect', 'acrylicon_block_author_enum', 0 );

function acrylicon_block_author_enum() {
	if ( is_user_logged_in() ) {
		return;
	}

	if ( isset( $_GET['author'] ) && is_numeric( (string) $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
