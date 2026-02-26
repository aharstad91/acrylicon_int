<?php
/**
 * Template for referanser with type: new-reference or case-study
 * Uses Gutenberg editor content with structured layout
 */

$post_id    = get_the_ID();
$is_english = ( get_current_blog_id() === 1 );

// Taxonomy data
$category_terms = get_the_terms( $post_id, 'referanser-kategorier' ) ?: [];
$product_terms  = get_the_terms( $post_id, 'referanser-produkter' ) ?: [];
$office_terms   = get_the_terms( $post_id, 'referanser-kontor' ) ?: [];
$type_terms     = get_the_terms( $post_id, 'referanser-type' ) ?: [];

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

<main class="max-w-screen-2xl mx-auto px-5 md:px-20 pt-12 md:pt-20 lg:pt-44 pb-8">

	<?php if ( has_post_thumbnail() ) : ?>
	<div class="mb-8 -mx-5 md:mx-0">
		<?php the_post_thumbnail( 'large', [
			'class'         => 'w-full h-64 md:h-96 lg:h-124 object-cover md:rounded-lg',
			'fetchpriority' => 'high',
		] ); ?>
	</div>
	<?php endif; ?>

	<div class="flex flex-wrap gap-2 mb-4">
		<?php if ( $is_case_study ) : ?>
			<span class="inline-block bg-acryl-red text-white rounded-full px-3 py-1 text-sm">
				<?php echo $is_english ? 'Case Study' : 'Dybdecase'; ?>
			</span>
		<?php endif; ?>
		<?php foreach ( $category_terms as $term ) : ?>
			<span class="inline-block bg-acryl-beige-lighter rounded-full px-3 py-1 text-sm">
				<?php echo esc_html( $term->name ); ?>
			</span>
		<?php endforeach; ?>
	</div>

	<h1 class="text-3xl md:text-5xl lg:text-7xl font-light mb-8 leading-tight"><?php the_title(); ?></h1>

	<?php
	// Build fact box data
	$facts = [];
	if ( $product_terms ) {
		$facts[] = [
			'label' => $is_english ? 'Product system' : 'Produktsystem',
			'value' => implode( ', ', wp_list_pluck( $product_terms, 'name' ) ),
		];
	}
	if ( $office_terms ) {
		$names = array_map( function( $t ) {
			return preg_replace( '/^Acrylicon\s+/i', '', $t->name );
		}, $office_terms );
		$facts[] = [
			'label' => $is_english ? 'Office' : 'Kontor',
			'value' => implode( ', ', $names ),
		];
	}
	if ( $category_terms ) {
		$facts[] = [
			'label' => $is_english ? 'Industry' : 'Bruksområde',
			'value' => implode( ', ', wp_list_pluck( $category_terms, 'name' ) ),
		];
	}
	?>

	<?php if ( $facts ) : ?>
	<div class="bg-acryl-beige-lighter rounded-lg p-6 md:p-8 mb-10">
		<dl class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 m-0">
			<?php foreach ( $facts as $fact ) : ?>
			<div>
				<dt class="font-sohne-mono text-xs text-acryl-gray-1 mb-1"><?php echo esc_html( $fact['label'] ); ?></dt>
				<dd class="text-base m-0"><?php echo esc_html( $fact['value'] ); ?></dd>
			</div>
			<?php endforeach; ?>
		</dl>
	</div>
	<?php endif; ?>

	<section class="prose max-w-none text-xl">
		<div class="editor"><?php the_content(); ?></div>
	</section>

	<div class="mt-12 mb-8 py-8 border-t border-acryl-neutral-1">
		<p class="text-lg mb-4"><?php echo $is_english
			? 'Interested in a similar solution for your project?'
			: 'Interessert i en lignende løsning for ditt prosjekt?'; ?></p>
		<a href="<?php echo home_url( $is_english ? '/locations/' : '/kontakt-oss/' ); ?>" class="inline-block bg-acryl-red text-white px-6 py-3 rounded-full no-underline hover:opacity-90 transition-opacity">
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
