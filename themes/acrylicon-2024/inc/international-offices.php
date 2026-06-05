<?php
/**
 * International Office Data
 *
 * Returns an array of all international offices (non-Norwegian).
 * Norwegian offices are queried dynamically from the Kontor CPT.
 *
 * Source of truth: https://www.acryliconpolymers.com/locations
 *
 * Structure: country key => [ country, flag, offices[] ]
 * Each office: [ name, company, address[], phone, email, web ]
 *
 * @return array
 */

return [
	'australia' => [
		'country' => 'Australia',
		'flag'    => 'au',
		'offices' => [
			[
				'name'    => 'AcryliCon Australia',
				'company' => 'Andersens Floor Coverings PTY Ltd',
				'address' => [ '29 Western Drive', 'Gatton QLD 4343' ],
				'phone'   => '+61 1800 016 016',
				'email'   => 'enquires@acrylicon.com.au',
				'web'     => 'www.acrylicon.com.au',
			],
		],
	],

	'canada' => [
		'country' => 'Canada',
		'flag'    => 'ca',
		'offices' => [
			[
				'name'    => 'AcryliCon Canada — Head Office',
				'company' => 'AcryliCon Canada',
				'address' => [ '14305 Rolland-Desjardins, suite 103', 'Mirabel, Quebec J7J 0K5' ],
				'phone'   => '+1 450 818 9182',
				'email'   => '',
				'web'     => 'www.acrylicon.com',
			],
			[
				'name'    => 'AcryliCon Canada — West Division (AB–BC)',
				'company' => 'AcryliCon Canada',
				'address' => [],
				'phone'   => '+1 587 899 4243',
				'email'   => '',
				'web'     => '',
			],
		],
	],

	'central-asia' => [
		'country' => 'Central Asia',
		'flag'    => 'kz',
		'offices' => [
			[
				'name'    => 'AcryliCon Central Asia',
				'company' => 'AcryliCon Central Asia',
				'address' => [ '58, Otrarskaya st.', 'Almaty, Kazakhstan' ],
				'phone'   => '+7 776 911 55 11',
				'email'   => 'office@acrylicon.kz',
				'web'     => 'www.acrylicon.kz',
			],
		],
	],

	'denmark' => [
		'country' => 'Denmark',
		'flag'    => 'dk',
		'offices' => [
			[
				'name'    => 'AcryliCon Denmark',
				'company' => 'Acrylicon Danmark ApS',
				'address' => [ 'Hoddeskovvej 9', '6823 Ansager' ],
				'phone'   => '+45 21 54 24 94',
				'email'   => 'jno@acrylicon.dk',
				'web'     => '',
			],
		],
	],

	'egypt' => [
		'country' => 'Egypt',
		'flag'    => 'eg',
		'offices' => [
			[
				'name'    => 'AcryliCon Egypt',
				'company' => 'Acrylicon Egypt for Flooring',
				'address' => [ 'North 90th Street', 'New Cairo, Cairo' ],
				'phone'   => '+20 2 23465134',
				'email'   => 'info@acrylicon-me.com',
				'web'     => '',
			],
		],
	],

	'faroe-islands' => [
		'country' => 'Faroe Islands & Greenland',
		'flag'    => 'fo',
		'offices' => [
			[
				'name'    => 'AcryliCon Faroe Islands & Greenland',
				'company' => 'Sp/f Sri',
				'address' => [ 'Rundingur 20', '100 Tórshavn, Faroe Islands' ],
				'phone'   => '+298 227215',
				'email'   => 'sri@sri.fo',
				'web'     => '',
			],
		],
	],

	'finland' => [
		'country' => 'Finland',
		'flag'    => 'fi',
		'offices' => [
			[
				'name'    => 'AcryliCon Finland',
				'company' => 'Kausalan Pinnoite OY',
				'address' => [ 'Teollisuustie 10', 'Kausala, SF-47400' ],
				'phone'   => '+358 20 787 0380',
				'email'   => 'info@kausalanpinnoite.fi',
				'web'     => 'www.kausalanpinnoite.fi',
			],
		],
	],

	'germany' => [
		'country' => 'Germany',
		'flag'    => 'de',
		'offices' => [
			[
				'name'    => 'AcryliCon Germany — North',
				'company' => 'AcryliCon Services GmbH',
				'address' => [ 'Lederstraße 19', '19306 Neustadt-Glewe' ],
				'phone'   => '+49 38757 595560',
				'email'   => 'info@acrylicon.de',
				'web'     => '',
			],
			[
				'name'    => 'AcryliCon Germany — South',
				'company' => 'AcryliCon Süd GmbH',
				'address' => [ 'Babenhäuser Straße 50, IndustrieHandelsPark Nord', '63762 Großostheim' ],
				'phone'   => '+49 1520 8945505',
				'email'   => 'info@acrylicon.de',
				'web'     => '',
			],
		],
	],

	'ireland' => [
		'country' => 'Ireland',
		'flag'    => 'ie',
		'offices' => [
			[
				'name'    => 'AcryliCon Ireland',
				'company' => 'Acrylicon Ireland',
				'address' => [ 'Unit 3D, Clane Business Park', 'Clane, Co. Kildare' ],
				'phone'   => '+353 459 82632',
				'email'   => 'info@acrylicon.ie',
				'web'     => 'www.acrylicon.ie',
			],
		],
	],

	'jamaica' => [
		'country' => 'Jamaica',
		'flag'    => 'jm',
		'offices' => [
			[
				'name'    => 'Turmax Construction + Plus',
				'company' => 'Rodmax Orlando Brown',
				'address' => [ '183d Lighthouse Road', 'Port Antonio, Portland' ],
				'phone'   => '+1 876 461 7140',
				'email'   => 'rodmaxbrown@acrylicon.com',
				'web'     => '',
			],
		],
	],

	'lithuania' => [
		'country' => 'Lithuania',
		'flag'    => 'lt',
		'offices' => [
			[
				'name'    => 'AcryliCon Lithuania',
				'company' => 'UAB AcryliCon Baltic',
				'address' => [ 'Savanorių pr. 151', 'Vilnius, LT-03150' ],
				'phone'   => '+370 5 233 2400',
				'email'   => 'info@acrylicon.lt',
				'web'     => 'www.acrylicon.lt',
			],
		],
	],

	'middle-east' => [
		'country' => 'Middle East / UAE',
		'flag'    => 'ae',
		'offices' => [
			[
				'name'    => 'AcryliCon Middle East',
				'company' => 'Acrylicon Middle East Flooring',
				'address' => [ 'P.O. Box 125334', 'Dubai, United Arab Emirates' ],
				'phone'   => '',
				'email'   => 'info@acrylicon-me.com',
				'web'     => 'www.acrylicon-me.com',
			],
		],
	],

	'south-korea' => [
		'country' => 'South Korea',
		'flag'    => 'kr',
		'offices' => [
			[
				'name'    => 'AcryliCon South Korea',
				'company' => 'Acrylicon Korea Inc.',
				'address' => [ '171, Noksan Saneop Jung-Ro, GangSeo-gu', 'Busan 46752' ],
				'phone'   => '+82 51 504 2080',
				'email'   => 'nhogy@acrylicon-korea.com',
				'web'     => 'www.acrylicon-korea.com',
			],
		],
	],

	'united-kingdom' => [
		'country' => 'United Kingdom',
		'flag'    => 'gb',
		'offices' => [
			[
				'name'    => 'AcryliCon UK — Head Office',
				'company' => 'Acrylicon UK',
				'address' => [ 'AcryliCon House, The Knowledge Centre', 'Wyboston Lakes, Great North Road', 'Wyboston, Bedfordshire MK44 3BY' ],
				'phone'   => '+44 (0) 844 800 7191',
				'email'   => 'UK@acrylicon.com',
				'web'     => 'www.acryliconuk.com',
			],
			[
				'name'    => 'AcryliCon UK — North East',
				'company' => 'Resinance Flooring Ltd',
				'address' => [ 'Woodsome Leas, 12 Blackwell Scar', 'Darlington, DL3 8DL' ],
				'phone'   => '+44 (0) 333 0124705',
				'email'   => 'john.wilson@acrylicon.com',
				'web'     => 'www.resinflooringnorth.com',
			],
			[
				'name'    => 'AcryliCon UK — London & South East',
				'company' => 'Durofloor Limited',
				'address' => [ '8 Crest Industrial Estate, Pattenden Lane', 'Marden, Kent, TN12 9QJ' ],
				'phone'   => '+44 (0) 3330 124 821',
				'email'   => 'tony.levy@durofloor.co.uk',
				'web'     => '',
			],
			[
				'name'    => 'AcryliCon UK — East Midlands & East Anglia',
				'company' => 'Elan Flooring Ltd',
				'address' => [ '1 Ramsay Court, Kingfisher Way', 'Huntingdon, Buckinghamshire, PE29 6FY' ],
				'phone'   => '+44 (0) 7577 434733',
				'email'   => '',
				'web'     => '',
			],
			[
				'name'    => 'AcryliCon UK — South West',
				'company' => 'Trusted Flooring Solutions Limited',
				'address' => [ 'The Green, Kingham', 'Oxfordshire, OX7 6YD' ],
				'phone'   => '+44 (0) 7876 144 440',
				'email'   => 'info@trustedflooringsolutions.com',
				'web'     => 'www.trustedflooringsolutions.com',
			],
		],
	],

	'usa' => [
		'country' => 'United States',
		'flag'    => 'us',
		'offices' => [
			[
				'name'    => 'AcryliCon USA — Headquarters',
				'company' => 'AcryliCon USA',
				'address' => [ '12460 Crabapple Road, Ste. 202-106', 'Alpharetta, GA 30004' ],
				'phone'   => '+1 888 736 7550',
				'email'   => 'jasonbye@acryliconusa.com',
				'web'     => 'www.acryliconusa.com',
			],
			[
				'name'    => 'AcryliCon USA — North East & Mid West',
				'company' => 'AcryliCon USA',
				'address' => [ 'Rochester, New York, NY 14450' ],
				'phone'   => '+1 888 736 7550',
				'email'   => '',
				'web'     => '',
			],
		],
	],
];
