<?php
/**
 * International Office Data
 *
 * Returns an array of all international offices (non-Norwegian).
 * Norwegian offices are queried dynamically from the Kontor CPT.
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
				'company' => 'AcryliCon Canada Inc.',
				'address' => [ '390 Nantucket Blvd', 'Scarborough, ON M1P 2N5' ],
				'phone'   => '+1 416 792 9953',
				'email'   => 'sales@acrylicon.ca',
				'web'     => 'www.acrylicon.ca',
			],
			[
				'name'    => 'AcryliCon Canada — West',
				'company' => 'AcryliCon Canada Inc.',
				'address' => [ '110-11500 Bridgeport Rd', 'Richmond, BC V6X 1T2' ],
				'phone'   => '+1 604 370 3626',
				'email'   => 'west@acrylicon.ca',
				'web'     => 'www.acrylicon.ca',
			],
		],
	],

	'central-asia' => [
		'country' => 'Central Asia',
		'flag'    => 'kz',
		'offices' => [
			[
				'name'    => 'AcryliCon Central Asia',
				'company' => 'TOO AcryliCon Central Asia',
				'address' => [ 'Almaty', 'Kazakhstan' ],
				'phone'   => '+7 727 350 5233',
				'email'   => 'info@acrylicon.kz',
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
				'company' => 'AcryliCon ApS',
				'address' => [ 'Mileparken 22A', '2740 Skovlunde' ],
				'phone'   => '+45 36 72 76 72',
				'email'   => 'info@acrylicon.dk',
				'web'     => 'www.acrylicon.dk',
			],
		],
	],

	'egypt' => [
		'country' => 'Egypt',
		'flag'    => 'eg',
		'offices' => [
			[
				'name'    => 'AcryliCon Egypt',
				'company' => 'AcryliCon Egypt LLC',
				'address' => [ '90th Street, Fifth Settlement', 'New Cairo, Cairo' ],
				'phone'   => '+20 2 2564 1234',
				'email'   => 'info@acrylicon.eg',
			],
		],
	],

	'faroe-islands' => [
		'country' => 'Faroe Islands & Greenland',
		'flag'    => 'fo',
		'offices' => [
			[
				'name'    => 'AcryliCon Faroe Islands',
				'company' => 'P/F AcryliCon Foroyar',
				'address' => [ 'Hoydalsvegur 21', 'FO-100 Torshavn' ],
				'phone'   => '+298 31 14 14',
				'email'   => 'info@acrylicon.fo',
				'web'     => 'www.acrylicon.fo',
			],
		],
	],

	'finland' => [
		'country' => 'Finland',
		'flag'    => 'fi',
		'offices' => [
			[
				'name'    => 'AcryliCon Finland',
				'company' => 'AcryliCon Finland Oy',
				'address' => [ 'Perintokuja 4', '01510 Vantaa' ],
				'phone'   => '+358 9 2510 7370',
				'email'   => 'info@acrylicon.fi',
				'web'     => 'www.acrylicon.fi',
			],
		],
	],

	'germany' => [
		'country' => 'Germany',
		'flag'    => 'de',
		'offices' => [
			[
				'name'    => 'AcryliCon Polymers GmbH',
				'company' => 'AcryliCon Polymers GmbH',
				'address' => [ 'Gewerbegebiet 2', '56357 Miehlen' ],
				'phone'   => '+49 6772 9615 0',
				'email'   => 'info@acrylicon-polymers.com',
				'web'     => 'www.acrylicon-polymers.com',
			],
			[
				'name'    => 'AcryliCon Services GmbH',
				'company' => 'AcryliCon Services GmbH',
				'address' => [ 'Gewerbegebiet 2', '56357 Miehlen' ],
				'phone'   => '+49 6772 9615 0',
				'email'   => 'info@acrylicon-services.de',
				'web'     => 'www.acrylicon-services.de',
			],
		],
	],

	'ireland' => [
		'country' => 'Ireland',
		'flag'    => 'ie',
		'offices' => [
			[
				'name'    => 'AcryliCon Ireland',
				'company' => 'AcryliCon Ireland Ltd',
				'address' => [ 'Unit 3, Willsborough Industrial Estate', 'Clonshaugh, Dublin 17' ],
				'phone'   => '+353 1 847 8288',
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
				'name'    => 'AcryliCon Jamaica — Kingston',
				'company' => 'AcryliCon Jamaica Ltd',
				'address' => [ '2B Retirement Crescent', 'Kingston 5' ],
				'phone'   => '+1 876 920 0851',
				'email'   => 'info@acrylicon.com.jm',
			],
			[
				'name'    => 'AcryliCon Jamaica — Montego Bay',
				'company' => 'AcryliCon Jamaica Ltd',
				'address' => [ 'Lot 2, Bogue Industrial Estate', 'Montego Bay, St. James' ],
				'phone'   => '+1 876 979 0222',
				'email'   => 'info@acrylicon.com.jm',
			],
		],
	],

	'lithuania' => [
		'country' => 'Lithuania',
		'flag'    => 'lt',
		'offices' => [
			[
				'name'    => 'AcryliCon Lithuania',
				'company' => 'UAB AcryliCon Lietuva',
				'address' => [ 'Savanoriu pr. 176', 'LT-03154 Vilnius' ],
				'phone'   => '+370 5 233 6699',
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
				'company' => 'AcryliCon Middle East FZE',
				'address' => [ 'Dubai Silicon Oasis', 'Dubai, UAE' ],
				'phone'   => '+971 4 320 6440',
				'email'   => 'info@acrylicon.ae',
			],
		],
	],

	'south-korea' => [
		'country' => 'South Korea',
		'flag'    => 'kr',
		'offices' => [
			[
				'name'    => 'AcryliCon South Korea',
				'company' => 'AcryliCon Korea Co., Ltd',
				'address' => [ '3F, 42 Seongsui-ro 20-gil', 'Seongdong-gu, Seoul 04790' ],
				'phone'   => '+82 2 466 3850',
				'email'   => 'info@acrylicon.co.kr',
				'web'     => 'www.acrylicon.co.kr',
			],
		],
	],

	'united-kingdom' => [
		'country' => 'United Kingdom',
		'flag'    => 'gb',
		'offices' => [
			[
				'name'    => 'AcryliCon UK — Head Office',
				'company' => 'AcryliCon UK Ltd',
				'address' => [ '1 Furzeground Way', 'Stockley Park, Uxbridge UB11 1BD' ],
				'phone'   => '+44 208 610 6767',
				'email'   => 'info@acrylicon.co.uk',
				'web'     => 'www.acrylicon.co.uk',
			],
			[
				'name'    => 'AcryliCon UK — Scotland',
				'company' => 'AcryliCon UK Ltd',
				'address' => [ '4 Greenside Row', 'Edinburgh EH1 3AA' ],
				'phone'   => '+44 131 557 9191',
				'email'   => 'scotland@acrylicon.co.uk',
			],
			[
				'name'    => 'AcryliCon UK — North',
				'company' => 'AcryliCon UK Ltd',
				'address' => [ 'Deansgate', 'Manchester M3 4LQ' ],
				'phone'   => '+44 161 850 1122',
				'email'   => 'north@acrylicon.co.uk',
			],
			[
				'name'    => 'AcryliCon UK — South West',
				'company' => 'AcryliCon UK Ltd',
				'address' => [ '1 Temple Way', 'Bristol BS2 0BY' ],
				'phone'   => '+44 117 457 6500',
				'email'   => 'southwest@acrylicon.co.uk',
			],
			[
				'name'    => 'AcryliCon UK — Midlands',
				'company' => 'AcryliCon UK Ltd',
				'address' => [ '2 Colmore Row', 'Birmingham B3 2BJ' ],
				'phone'   => '+44 121 260 0202',
				'email'   => 'midlands@acrylicon.co.uk',
			],
		],
	],

	'usa' => [
		'country' => 'United States',
		'flag'    => 'us',
		'offices' => [
			[
				'name'    => 'AcryliCon USA — Head Office',
				'company' => 'AcryliCon USA Inc.',
				'address' => [ '150 N. Wacker Dr, Suite 2400', 'Chicago, IL 60606' ],
				'phone'   => '+1 312 981 7979',
				'email'   => 'sales@acrylicon.us',
				'web'     => 'www.acrylicon.us',
			],
			[
				'name'    => 'AcryliCon USA — Northeast/Midwest',
				'company' => 'AcryliCon USA Inc.',
				'address' => [ '228 E 45th Street, Suite 9E', 'New York, NY 10017' ],
				'phone'   => '+1 212 297 0220',
				'email'   => 'northeast@acrylicon.us',
			],
		],
	],
];
