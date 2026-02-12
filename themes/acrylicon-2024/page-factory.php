<?php
/**
 * Template Name: Factory
 * Template for the Factory page (/factory/).
 *
 * Showcases AcryliCon Polymers GmbH production facility in Miehlen, Germany.
 * Content is hardcoded for version control. Images use placeholder structure
 * that can be replaced with real factory photos via attachment IDs.
 *
 * @package Acrylicon2024
 */

get_header();
?>

<main>
	<?php // Hero Section ?>
	<section class="bg-acryl-dark-blue text-white relative overflow-hidden">
		<div class="max-w-screen-2xl mx-auto px-4 py-20 lg:py-32">
			<div class="lg:flex lg:items-center lg:gap-16">
				<div class="lg:w-1/2 mb-8 lg:mb-0">
					<p class="font-sohne-mono text-base text-acryl-light-blue mb-4">AcryliCon Polymers GmbH</p>
					<h1 class="text-4xl lg:text-6xl font-normal mb-6">Made in Germany</h1>
					<p class="text-xl lg:text-2xl text-white/80 max-w-lg">
						World-class resin flooring systems, produced at our own manufacturing facility in Miehlen, Germany since 2014.
					</p>
				</div>
				<div class="lg:w-1/2">
					<div class="bg-acryl-dark-blue/50 rounded-lg aspect-video flex items-center justify-center">
						<span class="text-white/30 text-lg">[Factory exterior photo]</span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php // Introduction Section ?>
	<section class="bg-acryl-beige-lightest">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-24">
			<div class="lg:flex lg:items-center lg:gap-16">
				<div class="lg:w-1/2 mb-8 lg:mb-0">
					<div class="bg-acryl-beige-lighter rounded-lg aspect-[4/3] flex items-center justify-center">
						<span class="text-acryl-gray-2 text-lg">[Production interior photo]</span>
					</div>
				</div>
				<div class="lg:w-1/2">
					<p class="font-sohne-mono text-base text-acryl-gray-2 mb-4">Our Production</p>
					<h2 class="text-3xl lg:text-5xl font-normal mb-6 text-acryl-dark-blue">
						Resin Producer Since 2014
					</h2>
					<div class="text-lg text-acryl-black space-y-4">
						<p>
							AcryliCon Polymers GmbH is the heart of AcryliCon's global production. Based in Miehlen, Germany, our state-of-the-art manufacturing facility produces the full range of AcryliCon resin flooring systems.
						</p>
						<p>
							With in-house production, we maintain complete control over quality, consistency, and supply chain — ensuring every batch meets our rigorous standards before reaching our licensed partners worldwide.
						</p>
						<p>
							Our facility combines decades of chemical expertise with modern production technology, enabling us to develop and produce innovative flooring solutions for industries ranging from healthcare and education to commercial and industrial sectors.
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php // Sustainability Section ?>
	<section class="bg-acryl-dark-blue text-white">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-24">
			<div class="lg:flex lg:items-center lg:gap-16">
				<div class="lg:w-1/2 mb-8 lg:mb-0 order-2 lg:order-1">
					<p class="font-sohne-mono text-base text-acryl-light-blue mb-4">Sustainability</p>
					<h2 class="text-3xl lg:text-5xl font-normal mb-6">
						Powered by the Sun
					</h2>
					<p class="text-lg text-white/80 mb-8">
						Our commitment to sustainability goes beyond our products. The production facility in Miehlen is equipped with one of the region's largest photovoltaic systems, significantly reducing our carbon footprint.
					</p>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
						<div class="bg-white/10 rounded-lg p-6 text-center">
							<p class="text-3xl lg:text-4xl font-normal text-acryl-light-blue mb-2">765</p>
							<p class="font-sohne-mono text-sm text-white/60">kWp installed capacity</p>
						</div>
						<div class="bg-white/10 rounded-lg p-6 text-center">
							<p class="text-3xl lg:text-4xl font-normal text-acryl-light-blue mb-2">528</p>
							<p class="font-sohne-mono text-sm text-white/60">tonnes CO&#8322; saved/year</p>
						</div>
						<div class="bg-white/10 rounded-lg p-6 text-center">
							<p class="text-3xl lg:text-4xl font-normal text-acryl-light-blue mb-2">300+</p>
							<p class="font-sohne-mono text-sm text-white/60">households equivalent</p>
						</div>
					</div>
				</div>
				<div class="lg:w-1/2 order-1 lg:order-2">
					<div class="bg-acryl-dark-blue/50 rounded-lg aspect-[4/3] flex items-center justify-center">
						<span class="text-white/30 text-lg">[Solar panels / photovoltaic photo]</span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php // Key Figures Section ?>
	<section class="bg-acryl-beige-lightest">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-24">
			<div class="text-center mb-12">
				<p class="font-sohne-mono text-base text-acryl-gray-2 mb-4">AcryliCon in Numbers</p>
				<h2 class="text-3xl lg:text-5xl font-normal text-acryl-dark-blue">Key Figures</h2>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-screen-xl mx-auto">
				<div class="bg-white rounded-lg p-8 lg:p-12 text-center shadow-sm">
					<p class="text-5xl lg:text-7xl font-normal text-acryl-red mb-4">1977</p>
					<p class="text-lg text-acryl-dark-blue">Established</p>
					<p class="text-base text-acryl-gray-2 mt-2">Over four decades of excellence in flooring solutions</p>
				</div>
				<div class="bg-white rounded-lg p-8 lg:p-12 text-center shadow-sm">
					<p class="text-5xl lg:text-7xl font-normal text-acryl-red mb-4">1000s</p>
					<p class="text-lg text-acryl-dark-blue">Clients Worldwide</p>
					<p class="text-base text-acryl-gray-2 mt-2">Trusted by leading companies across all industries</p>
				</div>
				<div class="bg-white rounded-lg p-8 lg:p-12 text-center shadow-sm">
					<p class="text-5xl lg:text-7xl font-normal text-acryl-red mb-4">18</p>
					<p class="text-lg text-acryl-dark-blue">Locations</p>
					<p class="text-base text-acryl-gray-2 mt-2">Licensed distributors and contractors worldwide</p>
				</div>
			</div>
		</div>
	</section>

	<?php // CTA Section ?>
	<section class="bg-acryl-red text-white">
		<div class="max-w-screen-2xl mx-auto px-4 py-16 lg:py-20 text-center">
			<h2 class="text-3xl lg:text-4xl font-normal mb-4">Find Your Local AcryliCon Office</h2>
			<p class="text-lg text-white/80 mb-8 max-w-xl mx-auto">
				With 18 locations worldwide, we have a team ready to help with your next project.
			</p>
			<a href="<?php echo esc_url( home_url( '/locations/' ) ); ?>"
			   class="inline-flex items-center gap-3 px-8 py-4 bg-white text-acryl-red rounded-full text-lg hover:bg-white/90 transition-colors duration-200">
				<span>View All Locations</span>
				<?php echo svg_icon( 'arrow-right', [ 'width' => '20', 'height' => '20', 'fill' => '#E2241C' ] ); ?>
			</a>
		</div>
	</section>
</main>

<?php get_footer(); ?>
