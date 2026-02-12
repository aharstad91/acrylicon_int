<?php
/**
 * Product Sheet Template
 *
 * Renders a printable product data sheet from ACF block content.
 * Accessed via ?view=sheet on any produkter CPT single post.
 */

$sheet = acrylicon_parse_product_sheet_data( get_the_ID() );
$is_english = ( get_current_blog_id() === 1 );

get_header();
?>

<main class="product-sheet max-w-screen-2xl mx-auto px-4 pt-20 lg:pt-44 pb-8">

	<?php // Print button — hidden in print ?>
	<div class="print-hide mb-8 flex items-center justify-between">
		<a href="<?php the_permalink(); ?>" class="text-lg font-sohne-mono text-acryl-gray-1 hover:text-black">
			&larr; <?php echo $is_english ? 'Back to product' : 'Tilbake til produkt'; ?>
		</a>
		<button onclick="window.print()" class="inline-flex items-center gap-3 px-8 py-3 text-white bg-acryl-red rounded-full text-lg hover:opacity-90 transition-opacity">
			<?php echo svg_icon('download-file', ['width' => 20, 'height' => 20, 'class' => 'invert brightness-0 filter']); ?>
			<span><?php echo $is_english ? 'Print / Save as PDF' : 'Skriv ut / Lagre som PDF'; ?></span>
		</button>
	</div>

	<?php // Sheet content — this is what gets printed ?>
	<article class="product-sheet-content bg-white rounded-lg overflow-hidden">

		<?php // Header with logo and product name ?>
		<header class="product-sheet-header bg-acryl-dark-blue text-white px-8 lg:px-16 py-8 flex items-center justify-between">
			<div>
				<p class="font-sohne-mono text-base opacity-70 mb-1"><?php echo $is_english ? 'Product Data Sheet' : 'Produktdatablad'; ?></p>
				<h1 class="text-2xl lg:text-5xl font-normal"><?php echo esc_html( $sheet['title'] ); ?></h1>
			</div>
			<img src="<?php echo get_template_directory_uri(); ?>/assets/gfx/acrylicon-logo-light.svg" alt="Acrylicon" class="w-48 lg:w-64 print-logo">
		</header>

		<?php // Featured image ?>
		<?php if ( $sheet['featured_image'] ) : ?>
		<div class="product-sheet-hero">
			<?php echo wp_get_attachment_image( $sheet['featured_image'], 'full', false, array(
				'class' => 'w-full h-64 lg:h-96 object-cover',
			) ); ?>
		</div>
		<?php endif; ?>

		<div class="px-8 lg:px-16 py-8 lg:py-12">

			<?php // Description + Benefits ?>
			<?php if ( $sheet['description'] || $sheet['benefits'] ) : ?>
			<section class="space-y-12 mb-12 print-section">
				<?php if ( $sheet['description'] ) : ?>
				<div>
					<h2 class="text-lg lg:text-2xl font-normal mb-4 pb-3 border-b border-solid border-gray-2">
						<?php echo $is_english ? 'Description & Use' : 'Beskrivelse og bruk'; ?>
					</h2>
					<div class="text-base text-acryl-gray-1 leading-relaxed editor">
						<?php echo wp_kses_post( $sheet['description'] ); ?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $sheet['benefits'] ) : ?>
				<div>
					<h2 class="text-lg lg:text-2xl font-normal mb-4 pb-3 border-b border-solid border-gray-2">
						<?php echo $is_english ? 'Key Properties & Benefits' : 'Viktigste egenskaper og fordeler'; ?>
					</h2>
					<ul class="list-none p-0 m-0 space-y-3">
						<?php foreach ( $sheet['benefits'] as $benefit ) : ?>
						<li class="flex items-start gap-3 font-sohne-mono text-base">
							<span class="text-acryl-red mt-0.5">&bull;</span>
							<span><?php echo esc_html( $benefit ); ?></span>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<?php // Feature cards ?>
			<?php if ( $sheet['features'] ) : ?>
			<section class="mb-12 print-section">
				<h2 class="text-lg lg:text-2xl font-normal mb-6 pb-3 border-b border-solid border-gray-2">
					<?php echo $is_english ? 'System Features' : 'Systemegenskaper'; ?>
				</h2>
				<div class="space-y-3">
					<?php foreach ( $sheet['features'] as $feature ) : ?>
					<div class="bg-acryl-beige-lighter p-4 rounded-lg flex items-start gap-4">
						<?php if ( ! empty( $feature['image'] ) ) : ?>
							<?php echo wp_get_attachment_image( $feature['image'], 'thumbnail', false, array(
								'class' => 'w-12 h-12 flex-shrink-0',
							) ); ?>
						<?php endif; ?>
						<div>
							<?php if ( ! empty( $feature['title'] ) ) : ?>
								<p class="font-sohne-mono text-base font-normal mb-1"><?php echo esc_html( $feature['title'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $feature['excerpt'] ) ) : ?>
								<p class="text-base text-acryl-gray-1"><?php echo wp_kses_post( $feature['excerpt'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php // Technical information — starts on page 2 in print ?>
			<?php if ( $sheet['technical_info'] ) : ?>
			<section class="mb-12 print-page-2">
				<h2 class="text-lg lg:text-2xl font-normal mb-6 pb-3 border-b border-solid border-gray-2">
					<?php echo $is_english ? 'Technical Information' : 'Teknisk informasjon'; ?>
				</h2>
				<div>
					<?php foreach ( $sheet['technical_info'] as $row ) : ?>
					<div class="py-3 flex gap-4 border-solid border-gray-2 border-t">
						<dl class="md:flex md:gap-4 w-full font-sohne-mono">
							<?php if ( ! empty( $row['tech_info_name'] ) ) : ?>
								<dt class="text-black md:w-1-2 text-base font-normal mb-1 flex-shrink-0"><?php echo esc_html( $row['tech_info_name'] ); ?></dt>
							<?php endif; ?>
							<?php if ( ! empty( $row['tech_info_desc'] ) ) : ?>
								<dd class="text-acryl-gray-1 md:w-1-2 m-0"><?php echo wp_kses_post( $row['tech_info_desc'] ); ?></dd>
							<?php endif; ?>
						</dl>
					</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php // Downloads ?>
			<?php if ( $sheet['downloads'] ) : ?>
			<section class="mb-12 print-hide">
				<h2 class="text-lg lg:text-2xl font-normal mb-6 pb-3 border-b border-solid border-gray-2">
					<?php echo $is_english ? 'Downloads' : 'Nedlastinger'; ?>
				</h2>
				<div>
					<?php foreach ( $sheet['downloads'] as $dl ) : ?>
					<div class="py-3 flex gap-4 border-solid border-gray-2 border-t">
						<div class="flex w-full font-sohne-mono gap-4">
							<img src="<?php echo get_template_directory_uri(); ?>/assets/gfx/download-file.svg" alt="" class="w-5 h-5">
							<div class="gap-4 w-full md:flex">
								<?php if ( ! empty( $dl['download_name'] ) ) : ?>
									<div class="text-black md:w-1-2 text-base"><?php echo esc_html( $dl['download_name'] ); ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $dl['download_link'] ) ) : ?>
									<a href="<?php echo esc_url( $dl['download_link'] ); ?>" class="md:w-1-2 md:flex justify-end text-black">
										<span class="underline"><?php echo $is_english ? 'Download' : 'Last ned'; ?></span>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

		</div>

		<?php // Footer ?>
		<footer class="product-sheet-footer bg-acryl-dark-blue text-white px-8 lg:px-16 py-6 flex items-center justify-between">
			<div class="font-sohne-mono text-sm opacity-70">
				<?php echo $is_english ? 'Visit' : 'Besøk'; ?> www.acrylicon.no
				&mdash;
				<?php echo $is_english
					? 'Find your nearest AcryliCon office'
					: 'Finn ditt nærmeste AcryliCon-kontor'; ?>
			</div>
			<img src="<?php echo get_template_directory_uri(); ?>/assets/gfx/acrylicon-logo-light.svg" alt="Acrylicon" class="w-32">
		</footer>

	</article>

</main>

<?php get_footer(); ?>
