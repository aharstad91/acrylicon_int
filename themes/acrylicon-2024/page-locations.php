<?php
/**
 * Template Name: Locations
 * Template for the Locations / Kontakt oss page.
 *
 * Norway first (with Google Maps + rich office cards),
 * then international offices from inc/international-offices.php.
 *
 * @package Acrylicon2024
 */

get_header();

$is_english = ( get_current_blog_id() === 1 );
$international_offices = require get_template_directory() . '/inc/international-offices.php';

// Query Norwegian offices from blog 3
$norway_offices = [];
$norway_blog_id = 3;
if ( get_blog_details( $norway_blog_id ) ) {
	switch_to_blog( $norway_blog_id );

	$kontor_query = new WP_Query( [
		'post_type'      => 'kontor',
		'posts_per_page' => 50,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post__not_in'   => [ 107 ], // Exclude "Norge" parent post
	] );

	while ( $kontor_query->have_posts() ) {
		$kontor_query->the_post();
		$location = get_field( 'location' );
		$norway_offices[] = [
			'id'        => get_the_ID(),
			'title'     => get_the_title(),
			'permalink' => get_the_permalink(),
			'phone'     => get_field( 'office_tel' ),
			'email'     => get_post_meta( get_the_ID(), 'office_email', true ),
			'address'   => get_field( 'office_adress' ),
			'lat'       => $location['lat'] ?? null,
			'lng'       => $location['lng'] ?? null,
			'city'      => $location['address'] ?? '',
			'thumbnail' => get_the_post_thumbnail( get_the_ID(), 'large', [
				'class'   => 'w-full h-48 object-cover rounded-t-lg',
				'loading' => 'lazy',
			] ),
		];
	}

	wp_reset_postdata();
	restore_current_blog();
}

// Prepare map markers JSON
$map_markers = [];
foreach ( $norway_offices as $office ) {
	if ( $office['lat'] && $office['lng'] ) {
		$short_name = preg_replace( '/^Acrylicon\s+/i', '', $office['title'] );
		$map_markers[] = [
			'lat'   => (float) $office['lat'],
			'lng'   => (float) $office['lng'],
			'title' => $short_name,
			'phone' => $office['phone'],
			'url'   => $office['permalink'],
		];
	}
}
?>

<main>
	<?php // Hero Section ?>
	<section class="bg-acryl-dark-blue text-white">
		<div class="max-w-screen-2xl mx-auto px-5 md:px-20 py-16 lg:py-24">
			<p class="font-sohne-mono text-base font-light text-acryl-light-blue mb-4">
				<?php echo $is_english ? 'Contact Us' : 'Kontakt oss'; ?>
			</p>
			<h1 class="text-4xl lg:text-6xl font-normal mb-6">
				<?php echo $is_english ? 'Worldwide Locations' : 'Våre kontorer'; ?>
			</h1>
			<p class="text-xl text-acryl-light-blue max-w-2xl">
				<?php echo $is_english
					? 'AcryliCon has licensed and trade contractors in 18 countries. Find your local office below.'
					: 'AcryliCon har lisensierte distributører og opplærte entreprenører i 18 land. Finn ditt lokale kontor nedenfor.'; ?>
			</p>
		</div>
	</section>

	<?php // Norwegian Offices — Rich cards with map (Norwegian site only) ?>
	<?php if ( $norway_offices && ! $is_english ) : ?>
	<section class="bg-white">
		<div class="max-w-screen-2xl mx-auto px-5 md:px-20 py-16 lg:py-24">
			<div class="flex items-center gap-3 mb-8 pb-3 border-b border-acryl-beige-light">
				<?php echo svg_icon( 'flags/no', [ 'width' => '24', 'height' => '17', 'class' => 'inline-block flex-shrink-0' ] ); ?>
				<h2 class="text-2xl lg:text-3xl font-normal text-acryl-dark-blue">
					Norge
				</h2>
			</div>

			<?php // Google Maps container ?>
			<?php if ( defined( 'GOOGLE_MAPS_API_KEY' ) && GOOGLE_MAPS_API_KEY ) : ?>
			<div id="office-map" class="w-full h-80 lg:h-96 rounded-lg mb-10 bg-acryl-beige-lightest"></div>
			<noscript>
				<p class="text-sm text-acryl-gray-2 mb-8">
					Aktiver JavaScript for å se det interaktive kartet.
				</p>
			</noscript>
			<?php endif; ?>

			<?php // Rich Norwegian office cards ?>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
				<?php foreach ( $norway_offices as $office ) : ?>
				<div class="bg-acryl-beige-lighter rounded-lg overflow-hidden">
					<?php if ( $office['thumbnail'] ) : ?>
					<a href="<?php echo esc_url( $office['permalink'] ); ?>" class="block">
						<?php echo $office['thumbnail']; ?>
					</a>
					<?php endif; ?>

					<div class="p-6">
						<?php
						$short_name = preg_replace( '/^Acrylicon\s+/i', '', $office['title'] );
						?>
						<h3 class="text-xl font-normal text-acryl-dark-blue mb-1">
							<a href="<?php echo esc_url( $office['permalink'] ); ?>" class="no-underline hover:text-acryl-red transition-colors">
								<?php echo esc_html( $short_name ); ?>
							</a>
						</h3>

						<?php if ( $office['address'] ) : ?>
						<p class="text-base text-acryl-gray-1 mb-3"><?php echo esc_html( $office['address'] ); ?></p>
						<?php endif; ?>

						<div class="flex flex-wrap items-center gap-4 text-sm">
							<?php if ( $office['phone'] ) : ?>
							<a href="tel:+47<?php echo esc_attr( preg_replace( '/\s+/', '', $office['phone'] ) ); ?>"
							   class="inline-flex items-center gap-1 text-acryl-dark-blue hover:text-acryl-red transition-colors">
								<span>+47 <?php echo esc_html( $office['phone'] ); ?></span>
							</a>
							<?php endif; ?>

							<?php if ( ! empty( $office['email'] ) ) : ?>
							<a href="mailto:<?php echo esc_attr( antispambot( $office['email'] ) ); ?>"
							   class="inline-flex items-center gap-1 text-acryl-dark-blue hover:text-acryl-red transition-colors">
								<span><?php echo esc_html( antispambot( $office['email'] ) ); ?></span>
							</a>
							<?php endif; ?>

							<a href="<?php echo esc_url( $office['permalink'] ); ?>"
							   class="inline-flex items-center gap-1 text-acryl-red hover:text-acryl-dark-blue transition-colors font-medium">
								Se kontor ›
							</a>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php // All Offices (EN: includes Norway in the list; NO: international only) ?>
	<section class="bg-acryl-beige-lightest">
		<div class="max-w-screen-2xl mx-auto px-5 md:px-20 py-16 lg:py-24">
			<h2 class="text-2xl lg:text-3xl font-normal text-acryl-dark-blue mb-10">
				<?php echo $is_english ? 'Our Offices' : 'Internasjonale kontorer'; ?>
			</h2>

			<?php
			// On the English site, merge Norwegian offices into the international list
			$all_offices = $international_offices;
			if ( $is_english && $norway_offices ) {
				$norway_int_offices = [];
				foreach ( $norway_offices as $no ) {
					$short_name = preg_replace( '/^Acrylicon\s+/i', '', $no['title'] );
					$office_entry = [
						'name'    => 'AcryliCon ' . $short_name,
						'company' => 'AcryliCon ' . $short_name,
						'address' => $no['address'] ? [ $no['address'] ] : [],
						'phone'   => $no['phone'] ? '+47 ' . $no['phone'] : '',
						'email'   => $no['email'] ?? '',
						'web'     => '',
					];
					$norway_int_offices[] = $office_entry;
				}
				$all_offices['norway'] = [
					'country' => 'Norway',
					'flag'    => 'no',
					'offices' => $norway_int_offices,
				];
				ksort( $all_offices );
			}
			?>

			<?php foreach ( $all_offices as $key => $country_data ) : ?>
			<div class="mb-12 last:mb-0" id="<?php echo esc_attr( $key ); ?>">
				<div class="flex items-center gap-3 mb-6 pb-3 border-b border-acryl-beige-light">
					<?php echo svg_icon( 'flags/' . $country_data['flag'], [ 'width' => '24', 'height' => '17', 'class' => 'inline-block flex-shrink-0' ] ); ?>
					<h3 class="text-xl lg:text-2xl font-normal text-acryl-dark-blue"><?php echo esc_html( $country_data['country'] ); ?></h3>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php foreach ( $country_data['offices'] as $office ) : ?>
					<div class="bg-white rounded-lg p-6">
						<h4 class="text-lg font-normal text-acryl-dark-blue mb-1"><?php echo esc_html( $office['name'] ); ?></h4>
						<?php if ( $office['company'] !== $office['name'] ) : ?>
						<p class="text-sm text-acryl-gray-2 mb-3"><?php echo esc_html( $office['company'] ); ?></p>
						<?php endif; ?>

						<div class="text-base text-acryl-black mb-3">
							<?php foreach ( $office['address'] as $line ) : ?>
							<p><?php echo esc_html( $line ); ?></p>
							<?php endforeach; ?>
						</div>

						<div class="space-y-1 text-sm">
							<?php if ( ! empty( $office['phone'] ) ) : ?>
							<p>
								<span class="text-acryl-gray-2">Tel:</span>
								<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $office['phone'] ) ); ?>" class="text-acryl-dark-blue hover:text-acryl-red transition-colors"><?php echo esc_html( $office['phone'] ); ?></a>
							</p>
							<?php endif; ?>

							<?php if ( ! empty( $office['email'] ) ) : ?>
							<p>
								<span class="text-acryl-gray-2">Email:</span>
								<a href="mailto:<?php echo esc_attr( antispambot( $office['email'] ) ); ?>" class="text-acryl-dark-blue hover:text-acryl-red transition-colors"><?php echo esc_html( antispambot( $office['email'] ) ); ?></a>
							</p>
							<?php endif; ?>

							<?php if ( ! empty( $office['web'] ) ) : ?>
							<p>
								<span class="text-acryl-gray-2">Web:</span>
								<a href="<?php echo esc_url( 'https://' . $office['web'] ); ?>" target="_blank" rel="noopener noreferrer" class="text-acryl-dark-blue hover:text-acryl-red transition-colors"><?php echo esc_html( $office['web'] ); ?></a>
							</p>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</section>

	<?php // Contact CTA Section ?>
	<section class="bg-acryl-dark-blue text-white">
		<div class="max-w-screen-2xl mx-auto px-5 md:px-20 py-16 lg:py-20 text-center">
			<h2 class="text-3xl lg:text-4xl font-normal mb-4">
				<?php echo $is_english ? 'Get in Touch' : 'Ta kontakt'; ?>
			</h2>
			<p class="text-lg text-acryl-light-blue mb-8 max-w-xl mx-auto">
				<?php echo $is_english
					? "Can't find your country? Contact our head office in Norway and we'll connect you with the right team."
					: 'Finner du ikke ditt land? Ta kontakt med hovedkontoret i Norge, så setter vi deg i kontakt med rett team.'; ?>
			</p>
			<div class="flex flex-col md:flex-row items-center justify-center gap-6">
				<a href="mailto:<?php echo esc_attr( antispambot( 'info@acrylicon.no' ) ); ?>"
				   class="inline-flex items-center gap-3 px-8 py-4 bg-white text-acryl-dark-blue rounded-full text-lg hover:bg-white/90 transition-colors duration-200">
					<span><?php echo $is_english ? 'Email Head Office' : 'Send e-post'; ?></span>
				</a>
				<a href="tel:+4773901000"
				   class="inline-flex items-center gap-3 px-8 py-4 border border-white text-white rounded-full text-lg hover:bg-white/10 transition-colors duration-200">
					<span>+47 73 90 10 00</span>
				</a>
			</div>
		</div>
	</section>
</main>

<?php
// Google Maps script — only loaded on this page when API key is defined
if ( defined( 'GOOGLE_MAPS_API_KEY' ) && GOOGLE_MAPS_API_KEY && $map_markers ) :
?>
<script>
function initOfficeMap() {
	var markers = <?php echo wp_json_encode( $map_markers ); ?>;
	var bounds = new google.maps.LatLngBounds();
	var map = new google.maps.Map(document.getElementById('office-map'), {
		zoom: 5,
		center: { lat: 63.0, lng: 10.0 },
		mapTypeControl: false,
		streetViewControl: false,
		styles: [
			{ featureType: 'poi', stylers: [{ visibility: 'off' }] },
			{ featureType: 'transit', stylers: [{ visibility: 'off' }] }
		]
	});

	var infoWindow = new google.maps.InfoWindow();

	markers.forEach(function(m) {
		var marker = new google.maps.Marker({
			position: { lat: m.lat, lng: m.lng },
			map: map,
			title: m.title,
			icon: {
				path: google.maps.SymbolPath.CIRCLE,
				scale: 8,
				fillColor: '#E2241C',
				fillOpacity: 1,
				strokeColor: '#fff',
				strokeWeight: 2
			}
		});
		bounds.extend(marker.getPosition());

		marker.addListener('click', function() {
			var content = '<div style="font-family:sans-serif;min-width:160px">' +
				'<strong style="font-size:14px">' + m.title + '</strong>';
			if (m.phone) {
				content += '<br><a href="tel:+47' + m.phone.replace(/\s/g, '') + '" style="color:#253761">+47 ' + m.phone + '</a>';
			}
			content += '<br><a href="' + m.url + '" style="color:#E2241C;font-weight:500"><?php echo $is_english ? 'View office' : 'Se kontor'; ?> ›</a>';
			content += '</div>';
			infoWindow.setContent(content);
			infoWindow.open(map, marker);
		});
	});

	map.fitBounds(bounds);
}
</script>
<script async defer
	src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr( GOOGLE_MAPS_API_KEY ); ?>&callback=initOfficeMap">
</script>
<?php endif; ?>

<?php get_footer(); ?>
