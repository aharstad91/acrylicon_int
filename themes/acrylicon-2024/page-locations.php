<?php
/**
 * Template Name: Locations
 * Template for the Locations page (/locations/).
 *
 * Displays all worldwide AcryliCon offices:
 * - International offices: hardcoded from inc/international-offices.php
 * - Norwegian offices: dynamic from Kontor CPT (blog 3)
 *
 * @package Acrylicon2024
 */

get_header();

$international_offices = require get_template_directory() . '/inc/international-offices.php';
?>

<main>
	<?php // Hero Section ?>
	<section class="bg-acryl-dark-blue text-white">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-24">
			<p class="font-sohne-mono text-base text-acryl-light-blue mb-4">Contact Us</p>
			<h1 class="text-4xl lg:text-6xl font-normal mb-6">Worldwide Locations</h1>
			<p class="text-xl text-white/80 max-w-2xl">
				AcryliCon has licensed distributors and trained contractors in 18 countries. Find your local office below.
			</p>
		</div>
	</section>

	<?php // International Offices Listing ?>
	<section class="bg-acryl-beige-lightest">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-24">

			<?php foreach ( $international_offices as $key => $country_data ) : ?>
			<div class="mb-12 last:mb-0" id="<?php echo esc_attr( $key ); ?>">
				<?php // Country heading with flag ?>
				<div class="flex items-center gap-3 mb-6 pb-3 border-b border-acryl-beige-light">
					<?php echo svg_icon( 'flags/' . $country_data['flag'], [ 'width' => '24', 'height' => '17', 'class' => 'inline-block flex-shrink-0' ] ); ?>
					<h2 class="text-2xl lg:text-3xl font-normal text-acryl-dark-blue"><?php echo esc_html( $country_data['country'] ); ?></h2>
				</div>

				<?php // Office cards grid ?>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php foreach ( $country_data['offices'] as $office ) : ?>
					<div class="bg-white rounded-lg p-6 shadow-sm">
						<h3 class="text-lg font-normal text-acryl-dark-blue mb-1"><?php echo esc_html( $office['name'] ); ?></h3>
						<?php if ( $office['company'] !== $office['name'] ) : ?>
						<p class="text-sm text-acryl-gray-2 mb-3"><?php echo esc_html( $office['company'] ); ?></p>
						<?php endif; ?>

						<?php // Address ?>
						<div class="text-base text-acryl-black mb-3">
							<?php foreach ( $office['address'] as $line ) : ?>
							<p><?php echo esc_html( $line ); ?></p>
							<?php endforeach; ?>
						</div>

						<?php // Contact details ?>
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

			<?php
			// Norwegian offices — dynamic from Kontor CPT (blog 3)
			$norway_blog_id = 3;
			if ( get_blog_details( $norway_blog_id ) ) :
				switch_to_blog( $norway_blog_id );

				$kontor_query = new WP_Query( [
					'post_type'      => 'kontor',
					'posts_per_page' => 50,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
				] );
			?>

			<?php if ( $kontor_query->have_posts() ) : ?>
			<div class="mb-12" id="norway">
				<?php // Norway heading with flag ?>
				<div class="flex items-center gap-3 mb-6 pb-3 border-b border-acryl-beige-light">
					<?php echo svg_icon( 'flags/no', [ 'width' => '24', 'height' => '17', 'class' => 'inline-block flex-shrink-0' ] ); ?>
					<h2 class="text-2xl lg:text-3xl font-normal text-acryl-dark-blue">Norway</h2>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php while ( $kontor_query->have_posts() ) : $kontor_query->the_post(); ?>
					<div class="bg-white rounded-lg p-6 shadow-sm">
						<h3 class="text-lg font-normal text-acryl-dark-blue mb-3"><?php echo esc_html( get_the_title() ); ?></h3>

						<?php
						$address = get_field( 'office_adress' );
						$phone   = get_field( 'office_tel' );
						?>

						<?php if ( $address ) : ?>
						<div class="text-base text-acryl-black mb-3">
							<p><?php echo esc_html( $address ); ?></p>
						</div>
						<?php endif; ?>

						<?php if ( $phone ) : ?>
						<div class="text-sm">
							<p>
								<span class="text-acryl-gray-2">Tel:</span>
								<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>" class="text-acryl-dark-blue hover:text-acryl-red transition-colors"><?php echo esc_html( $phone ); ?></a>
							</p>
						</div>
						<?php endif; ?>
					</div>
					<?php endwhile; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php
				wp_reset_postdata();
				restore_current_blog();
			endif;
			?>

		</div>
	</section>

	<?php // Contact CTA Section ?>
	<section class="bg-acryl-dark-blue text-white">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-20 text-center">
			<h2 class="text-3xl lg:text-4xl font-normal mb-4">Get in Touch</h2>
			<p class="text-lg text-white/80 mb-8 max-w-xl mx-auto">
				Can't find your country? Contact our head office in Norway and we'll connect you with the right team.
			</p>
			<div class="flex flex-col md:flex-row items-center justify-center gap-6">
				<a href="mailto:<?php echo esc_attr( antispambot( 'info@acrylicon.no' ) ); ?>"
				   class="inline-flex items-center gap-3 px-8 py-4 bg-white text-acryl-dark-blue rounded-full text-lg hover:bg-white/90 transition-colors duration-200">
					<span>Email Head Office</span>
				</a>
				<a href="tel:+4773901000"
				   class="inline-flex items-center gap-3 px-8 py-4 border border-white text-white rounded-full text-lg hover:bg-white/10 transition-colors duration-200">
					<span>+47 73 90 10 00</span>
				</a>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
