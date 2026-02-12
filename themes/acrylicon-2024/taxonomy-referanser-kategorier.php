<?php
/**
 * Template for displaying referanser-kategorier taxonomy archives
 */
get_header();

$is_english   = ( get_current_blog_id() === 1 );
$current_term = get_queried_object();

$terms = get_terms( [
	'taxonomy'   => 'referanser-kategorier',
	'hide_empty' => true,
] );

$references_query = new WP_Query( [
	'post_type'      => 'referanser',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'tax_query'      => [ [
		'taxonomy' => 'referanser-kategorier',
		'field'    => 'term_id',
		'terms'    => $current_term->term_id,
	] ],
] );
?>

<div class="max-w-screen-2xl mx-auto px-4 mt-44 pb-8">
	<div class="header-with-red-back-link mb-8">
		<a href="<?php bloginfo( 'url' ); ?>/<?php echo $is_english ? 'references' : 'referanser'; ?>" class="text-red flex items-center gap-2 text-red-600 mb-4 font-sohne-mono">
			<?php echo svg_icon( 'arrow', [
				'width'  => '16px',
				'height' => '16px',
				'stroke' => '#E2241C',
			] ); ?>
			<?php echo $is_english ? 'References' : 'Referanser'; ?>
		</a>
		<h1 class="text-3xl lg:text-7xl font-buch mt-4 text-gray-900">
			<?php echo esc_html( $current_term->name ); ?>
		</h1>
	</div>

	<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
		<div class="mb-8">
			<h4 class="font-sohne-mono my-reset"><?php echo $is_english ? 'Filter by industry' : 'Filtrer på industri'; ?></h4>
			<ul class="mt-4 flex flex-wrap gap-2 px-0 list-none">
				<?php foreach ( $terms as $nav_term ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $nav_term, 'referanser-kategorier' ) ); ?>"
						   class="flex rounded-full px-4 py-2 border border-solid border-neutral-1 no-underline <?php echo ( $current_term->term_id == $nav_term->term_id ) ? 'bg-acryl-dark-blue text-white border-acryl-dark-blue' : 'hover:bg-gray-100'; ?>">
							<?php echo esc_html( $nav_term->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
		<?php if ( $references_query->have_posts() ) :
			while ( $references_query->have_posts() ) : $references_query->the_post();
				$post_id       = get_the_ID();
				$post_terms    = get_the_terms( $post_id, 'referanser-kategorier' );
				$product_terms = get_the_terms( $post_id, 'referanser-produkter' );
				$type_terms    = get_the_terms( $post_id, 'referanser-type' );

				$is_case_study = false;
				if ( is_array( $type_terms ) ) {
					foreach ( $type_terms as $t ) {
						if ( in_array( $t->slug, [ 'dybdecase', 'case-study' ], true ) ) {
							$is_case_study = true;
							break;
						}
					}
				}
		?>
			<div class="flex flex-col">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="mb-4">
						<div class="block relative">
							<div class="absolute top-3 left-3 flex flex-wrap gap-1 z-10">
								<?php if ( $is_case_study ) : ?>
									<span class="inline-block bg-acryl-red text-white rounded-full px-3 py-1 text-sm">
										<?php echo $is_english ? 'Case Study' : 'Dybdecase'; ?>
									</span>
								<?php endif; ?>
								<?php if ( is_array( $post_terms ) ) : foreach ( $post_terms as $term ) : ?>
									<span class="inline-block bg-acryl-beige-lightest rounded-full px-3 py-1 text-sm">
										<?php echo esc_html( $term->name ); ?>
									</span>
								<?php endforeach; endif; ?>
							</div>
							<a href="<?php the_permalink(); ?>" class="block">
								<?php the_post_thumbnail( 'large', [
									'class' => 'h-124 w-full object-cover rounded-lg',
									'alt'   => get_the_title(),
								] ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
				<h3 class="text-3xl font-normal my-0 mb-2">
					<a href="<?php the_permalink(); ?>" class="no-underline">
						<?php the_title(); ?>
					</a>
				</h3>
				<?php if ( is_array( $product_terms ) ) : ?>
					<div class="text-base text-black font-sohne-mono">
						<?php echo esc_html( implode( ', ', wp_list_pluck( $product_terms, 'name' ) ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endwhile;
			wp_reset_postdata();
		else : ?>
			<p class="text-gray-600"><?php echo $is_english ? 'No references found.' : 'Ingen referanser funnet.'; ?></p>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>
