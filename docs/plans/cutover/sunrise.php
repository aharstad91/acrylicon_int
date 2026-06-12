<?php
/**
 * Acrylicon multisite domain mapping.
 * Kjorer i ms-settings.php for WP bestemmer nettverk/blog.
 * Ruter kjente vertsnavn til riktig blog under nettverk 1.
 *
 * Validert lokalt 2026-05-27 (full integrasjonstest).
 * Deploy: wp-content/sunrise.php pa prod. Krever define('SUNRISE','on') i wp-config.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Eksplisitt host -> blog_id. www-varianter og port normaliseres bort under.
$acrylicon_domain_map = [
	'acrylicon.com'                          => 1,
	'acrylicon.no'                           => 3,
	'acryli-28355.jana-osl.servebolt.cloud'  => 1, // fallback/admin
];

$acrylicon_host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( $_SERVER['HTTP_HOST'] ) : '';
$acrylicon_host = preg_replace( '/:\d+$/', '', $acrylicon_host );   // strip port
$acrylicon_host = preg_replace( '/^www\./', '', $acrylicon_host );  // strip www

if ( isset( $acrylicon_domain_map[ $acrylicon_host ] ) ) {
	$acrylicon_blog_id = (int) $acrylicon_domain_map[ $acrylicon_host ];

	// WP_Site / WP_Network er lastet for sunrise inkluderes.
	$blog = get_site( $acrylicon_blog_id );
	if ( $blog ) {
		$current_blog          = $blog;
		$current_site          = WP_Network::get_instance( (int) $blog->site_id );
		$blog_id               = (int) $blog->blog_id;
		$site_id               = (int) $blog->site_id;
		$current_site->blog_id = $blog_id;
	}
}
// Ukjent host: ikke sett noe -> WP faller tilbake til standard deteksjon.
