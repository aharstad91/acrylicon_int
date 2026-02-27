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
	'telephone'    => '+47 73 90 10 00',
	'email'        => 'info@acrylicon.no',
	'address'      => [
		'@type'           => 'PostalAddress',
		'streetAddress'   => 'Industrivegen 24',
		'addressLocality' => 'Brumunddal',
		'postalCode'      => '2386',
		'addressCountry'  => 'NO',
	],
	'contactPoint' => [
		'@type'       => 'ContactPoint',
		'telephone'   => '+47 73 90 10 00',
		'email'       => 'info@acrylicon.no',
		'contactType' => 'customer service',
	],
	'sameAs'       => [
		'https://www.linkedin.com/company/acrylicon/',
		'https://www.facebook.com/acrylicon/',
	],
];
