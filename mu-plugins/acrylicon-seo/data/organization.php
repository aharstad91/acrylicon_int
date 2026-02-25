<?php
/**
 * Organization schema data (hardcoded).
 *
 * Logo must be PNG/WebP — Google rejects SVG for schema.
 * Minimum 112x112px.
 */
return [
	'@type'        => 'Organization',
	'name'         => 'AcryliCon',
	'legalName'    => 'AcryliCon Industrigulv AS',
	'logo'         => [
		'@type'  => 'ImageObject',
		'url'    => '{theme_url}/assets/gfx/acrylicon-logo-dark.png',
		'width'  => 600,
		'height' => 120,
	],
	'foundingDate' => '1977',
	'address'      => [
		'@type'           => 'PostalAddress',
		'streetAddress'   => 'Industrivegen 24',
		'addressLocality' => 'Brumunddal',
		'postalCode'      => '2386',
		'addressCountry'  => 'NO',
	],
	'sameAs'       => [],
];
