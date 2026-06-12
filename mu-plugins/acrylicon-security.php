<?php
/**
 * Plugin Name: Acrylicon Security
 * Description: Stenger users-endepunktene i REST API for uinnloggede (hindrer e-post-/brukerlekkasje)
 * Version: 1.0.0
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
