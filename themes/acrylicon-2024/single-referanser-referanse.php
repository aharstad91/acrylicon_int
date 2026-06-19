<?php
/**
 * Template for referanser with type: new-reference or case-study
 * Uses Gutenberg editor content with structured layout
 */

$post_id    = get_the_ID();
$is_english = ( get_current_blog_id() === 1 );

// Taxonomy data for related references
$category_terms = get_the_terms( $post_id, 'referanser-kategorier' ) ?: [];
?>

<main class="max-w-screen-2xl mx-auto px-5 md:px-20 pt-12 md:pt-20 lg:pt-44 pb-8">

	<section class="prose max-w-none text-xl">
		<div class="editor"><?php the_content(); ?></div>
	</section>

	<div class="mt-12 mb-8 py-8 border-t border-acryl-neutral-1">
		<p class="text-lg mb-4"><?php echo $is_english
			? 'Interested in a similar solution for your project?'
			: 'Interessert i en lignende løsning for ditt prosjekt?'; ?></p>
		<a href="<?php echo home_url( $is_english ? '/locations/' : '/kontor/' ); ?>" class="inline-block bg-acryl-red text-white px-6 py-3 rounded-full no-underline hover:opacity-90 transition-opacity">
			<?php echo $is_english ? 'Contact us' : 'Kontakt oss'; ?> ›
		</a>
	</div>

	<?php
	// Related references
	if ( $category_terms && ! is_wp_error( $category_terms ) ) :
		$related_args = [
			'post_type'      => 'referanser',
			'posts_per_page' => 3,
			'post__not_in'   => [ $post_id ],
			'orderby'        => 'rand',
			'tax_query'      => [ [
				'taxonomy' => 'referanser-kategorier',
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $category_terms, 'term_id' ),
			] ],
		];
		$related_query = new WP_Query( $related_args );

		if ( $related_query->have_posts() ) : ?>
		<div class="mt-8 mb-20">
			<h2 class="text-2xl mb-8"><?php echo $is_english ? 'Related projects' : 'Lignende prosjekter'; ?></h2>
			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
				<?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
				<div class="flex flex-col">
					<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>" class="block mb-4">
						<?php the_post_thumbnail( 'large', [
							'class' => 'h-124 w-full object-cover rounded-lg',
						] ); ?>
					</a>
					<?php endif; ?>
					<h3 class="text-3xl font-normal my-0 mb-2">
						<a href="<?php the_permalink(); ?>" class="no-underline"><?php the_title(); ?></a>
					</h3>
					<?php
					$rel_products = get_the_terms( get_the_ID(), 'referanser-produkter' );
					if ( $rel_products && ! is_wp_error( $rel_products ) ) : ?>
					<div class="text-base text-black font-sohne-mono">
						<?php echo esc_html( implode( ', ', wp_list_pluck( $rel_products, 'name' ) ) ); ?>
					</div>
					<?php endif; ?>
				</div>
				<?php endwhile; ?>
			</div>
			<div class="mt-12 text-center">
				<a href="<?php echo home_url( $is_english ? '/references/' : '/referanser/' ); ?>" class="inline-block bg-gray-900 text-white px-6 py-3 rounded-full no-underline">
					<?php echo $is_english ? 'See all references' : 'Se alle referanser'; ?> ›
				</a>
			</div>
		</div>
		<?php
		wp_reset_postdata();
		endif;
	endif;
	?>
</main>
