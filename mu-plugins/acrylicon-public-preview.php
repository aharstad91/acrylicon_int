<?php
/**
 * Plugin Name: AcryliCon Public Preview
 * Description: Tokenisert offentlig forhåndsvisning av kladder — for kundegodkjenning uten innlogging. Generer lenke: `wp acrylicon-preview <post_id>` (token gyldig 14 dager). Kun ikke-publiserte poster, alltid noindex + nocache.
 * Author: Initial Force AS
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

const ACRYLICON_PREVIEW_TOKEN_META   = '_acrylicon_preview_token';
const ACRYLICON_PREVIEW_EXPIRES_META = '_acrylicon_preview_expires';
const ACRYLICON_PREVIEW_TTL          = 14 * DAY_IN_SECONDS;

/**
 * Slipp gjennom en kladd på hovedspørringen når gyldig token følger med.
 * URL-format (genereres av CLI-kommandoen):
 *   ?p=<ID>&post_type=<type>&preview=true&acpreview=<token>
 */
add_filter( 'posts_results', function ( $posts, $query ) {
	if ( is_admin()
		|| ! $query->is_main_query()
		|| ! $query->is_preview()
		|| ! $query->is_singular()
		|| empty( $_GET['acpreview'] )
	) {
		return $posts;
	}

	$post_id = (int) ( $query->get( 'p' ) ?: $query->get( 'page_id' ) );
	if ( ! $post_id ) {
		return $posts;
	}

	$post = get_post( $post_id );
	if ( ! $post || 'publish' === $post->post_status ) {
		return $posts; // publiserte sider har vanlig URL — token skal ikke trengs
	}

	$token   = (string) get_post_meta( $post_id, ACRYLICON_PREVIEW_TOKEN_META, true );
	$expires = (int) get_post_meta( $post_id, ACRYLICON_PREVIEW_EXPIRES_META, true );

	if ( '' === $token
		|| ! hash_equals( $token, (string) wp_unslash( $_GET['acpreview'] ) )
		|| ( $expires && time() > $expires )
	) {
		return $posts; // ugyldig/utløpt token → vanlig 404-løype
	}

	if ( ! headers_sent() ) {
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
	add_filter( 'wp_robots', 'wp_robots_no_robots' );

	$post->post_status = 'publish'; // kun i minnet for denne responsen, lagres aldri
	return array( $post );
}, 10, 2 );

/**
 * WP-CLI: `wp acrylicon-preview <post_id>` — genererer nytt token + lenke.
 * Kjør på nytt for å rotere token (gammel lenke slutter å virke).
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'acrylicon-preview', function ( $args ) {
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		$post    = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( "Fant ikke post {$post_id}." );
		}
		if ( 'publish' === $post->post_status ) {
			WP_CLI::error( "Post {$post_id} er allerede publisert — den har vanlig URL." );
		}

		$token   = wp_generate_password( 32, false );
		$expires = time() + ACRYLICON_PREVIEW_TTL;
		update_post_meta( $post_id, ACRYLICON_PREVIEW_TOKEN_META, $token );
		update_post_meta( $post_id, ACRYLICON_PREVIEW_EXPIRES_META, $expires );

		$query = array( 'preview' => 'true', 'acpreview' => $token );
		if ( 'page' === $post->post_type ) {
			$query['page_id'] = $post_id;
		} else {
			$query['p'] = $post_id;
			if ( 'post' !== $post->post_type ) {
				$query['post_type'] = $post->post_type;
			}
		}

		$url = add_query_arg( $query, home_url( '/' ) );
		WP_CLI::success( sprintf(
			"Forhåndsvisning av «%s» (gyldig til %s):\n%s",
			$post->post_title,
			wp_date( 'Y-m-d H:i', $expires ),
			$url
		) );
	} );
}
