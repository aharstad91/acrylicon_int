<?php
/**
 * Template for old-reference type referanser
 * Provides a visual frame even for posts with thin content
 */

$post_id    = get_the_ID();
$is_english = ( get_current_blog_id() === 1 );

// Taxonomy data (newer taxonomy system)
$category_terms = get_the_terms( $post_id, 'referanser-kategorier' ) ?: [];
$product_terms  = get_the_terms( $post_id, 'referanser-produkter' ) ?: [];
$office_terms   = get_the_terms( $post_id, 'referanser-kontor' ) ?: [];

// Legacy ACF fields (serialized arrays of slugs)
$legacy_product = get_post_meta( $post_id, 'referance_productsystem', true );
$legacy_office  = get_post_meta( $post_id, 'referance_supplier', true );

// Product system label — prefer taxonomy, fallback to legacy
$product_label = '';
if ( $product_terms ) {
	$product_label = implode( ', ', wp_list_pluck( $product_terms, 'name' ) );
} elseif ( $legacy_product && is_array( $legacy_product ) ) {
	// Legacy slugs like "flake-system" → human-readable
	$product_label = implode( ', ', array_map( function( $slug ) {
		return ucwords( str_replace( '-', ' ', $slug ) );
	}, $legacy_product ) );
}

// Office label — prefer taxonomy, fallback to legacy
$office_label = '';
if ( $office_terms ) {
	$office_label = implode( ', ', array_map( function( $t ) {
		return preg_replace( '/^Acrylicon\s+/i', '', $t->name );
	}, $office_terms ) );
} elseif ( $legacy_office && is_array( $legacy_office ) ) {
	$office_label = implode( ', ', array_map( function( $slug ) {
		return ucwords( str_replace( '-', ' ', $slug ) );
	}, $legacy_office ) );
}

$category_label = $category_terms ? implode( ', ', wp_list_pluck( $category_terms, 'name' ) ) : '';
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

	<?php if ( $category_terms ) : ?>
	<div class="flex flex-wrap gap-2 mb-4">
		<?php foreach ( $category_terms as $term ) : ?>
			<span class="inline-block bg-acryl-beige-lighter rounded-full px-3 py-1 text-sm">
				<?php echo esc_html( $term->name ); ?>
			</span>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<h1 class="text-3xl md:text-5xl lg:text-7xl font-light mb-8 leading-tight"><?php the_title(); ?></h1>

	<?php
	$facts = [];
	if ( $product_label ) {
		$facts[] = [ 'label' => $is_english ? 'Product system' : 'Produktsystem', 'value' => $product_label ];
	}
	if ( $office_label ) {
		$facts[] = [ 'label' => $is_english ? 'Office' : 'Kontor', 'value' => $office_label ];
	}
	if ( $category_label ) {
		$facts[] = [ 'label' => $is_english ? 'Industry' : 'Bruksområde', 'value' => $category_label ];
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

	<?php
	$content = get_the_content();
	$has_content = ! empty( trim( wp_strip_all_tags( $content ) ) );
	?>

	<?php if ( $has_content ) : ?>
	<section class="prose max-w-none text-xl mb-10">
		<div class="editor"><?php the_content(); ?></div>
	</section>
	<?php endif; ?>

	<?php
	// Image gallery from ACF repeater
	if ( have_rows( 'referance_images_repeater' ) ) :
		$images = [];
		while ( have_rows( 'referance_images_repeater' ) ) {
			the_row();
			$img = get_sub_field( 'referance_images_repeater_image' );
			if ( $img ) $images[] = $img;
		}

		if ( count( $images ) > 1 ) : ?>
		<div class="grid grid-cols-2 gap-2 mb-10">
			<?php foreach ( $images as $img ) : ?>
			<div>
				<?php echo wp_get_attachment_image( $img['ID'], 'large', false, [
					'class'   => 'w-full h-48 md:h-72 object-cover rounded-lg',
					'loading' => 'lazy',
				] ); ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php elseif ( count( $images ) === 1 && ! has_post_thumbnail() ) : ?>
		<div class="mb-10">
			<?php echo wp_get_attachment_image( $images[0]['ID'], 'large', false, [
				'class'         => 'w-full h-64 md:h-96 object-cover rounded-lg',
				'fetchpriority' => 'high',
			] ); ?>
		</div>
		<?php endif;
	endif; ?>

	<div class="mt-8 mb-8 py-8 border-t border-acryl-neutral-1">
		<p class="text-lg mb-4"><?php echo $is_english
			? 'Want to know more about this project or a similar solution?'
			: 'Vil du vite mer om dette prosjektet eller en lignende løsning?'; ?></p>
		<a href="<?php echo home_url( $is_english ? '/locations/' : '/kontakt-oss/' ); ?>" class="inline-block bg-acryl-red text-white px-6 py-3 rounded-full no-underline hover:opacity-90 transition-opacity">
			<?php echo $is_english ? 'Contact us' : 'Kontakt oss'; ?> ›
		</a>
	</div>

	<?php
	// Related references — try category first, then any 3 random
	$tax_query = [];
	if ( $category_terms && ! is_wp_error( $category_terms ) ) {
		$tax_query = [ [
			'taxonomy' => 'referanser-kategorier',
			'field'    => 'term_id',
			'terms'    => wp_list_pluck( $category_terms, 'term_id' ),
		] ];
	}

	$related_args = [
		'post_type'      => 'referanser',
		'posts_per_page' => 3,
		'post__not_in'   => [ $post_id ],
		'orderby'        => 'rand',
	];
	if ( $tax_query ) {
		$related_args['tax_query'] = $tax_query;
	}

	$related_query = new WP_Query( $related_args );

	if ( $related_query->have_posts() ) : ?>
	<div class="mt-8 mb-20">
		<h2 class="text-2xl mb-8"><?php echo $is_english ? 'More references' : 'Flere referanser'; ?></h2>
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
	?>
</main>
