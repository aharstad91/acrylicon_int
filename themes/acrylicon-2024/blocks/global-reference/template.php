<?php
/**
 * Block Name: References Grid
 * Description: Display reference posts with client-side filtering by industry, product, and office.
 */

$is_english    = ( get_current_blog_id() === 1 );
$show_taxonomy = get_field( 'show_taxonomy' ) ?: false;
$post_count    = get_field( 'post_count' ) ?: -1;
$selected_posts = get_field( 'specific_references' );

// Query all references (or selected subset)
if ( $selected_posts ) {
	$references = $selected_posts;
} else {
	$query = new WP_Query( [
		'post_type'      => 'referanser',
		'posts_per_page' => $post_count,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
	$references = $query->posts;
	wp_reset_postdata();
}

if ( empty( $references ) ) {
	echo '<p class="text-gray-600">' . ( $is_english ? 'No references found.' : 'Ingen referanser funnet.' ) . '</p>';
	return;
}

// Pre-fetch all taxonomy data and sort: case studies first, then by date
$cards = [];
foreach ( $references as $ref ) {
	$post_id = is_object( $ref ) ? $ref->ID : $ref;

	$cat_terms     = get_the_terms( $post_id, 'referanser-kategorier' ) ?: [];
	$product_terms = get_the_terms( $post_id, 'referanser-produkter' ) ?: [];
	$office_terms  = get_the_terms( $post_id, 'referanser-kontor' ) ?: [];
	$type_terms    = get_the_terms( $post_id, 'referanser-type' ) ?: [];

	$is_case_study = false;
	if ( is_array( $type_terms ) ) {
		foreach ( $type_terms as $t ) {
			if ( in_array( $t->slug, [ 'dybdecase', 'case-study' ], true ) ) {
				$is_case_study = true;
				break;
			}
		}
	}

	$cards[] = [
		'id'            => $post_id,
		'is_case_study' => $is_case_study,
		'date'          => get_post_field( 'post_date', $post_id ),
		'categories'    => is_array( $cat_terms ) ? $cat_terms : [],
		'products'      => is_array( $product_terms ) ? $product_terms : [],
		'offices'       => is_array( $office_terms ) ? $office_terms : [],
	];
}

// Sort: case studies first, then newest first
usort( $cards, function ( $a, $b ) {
	if ( $a['is_case_study'] !== $b['is_case_study'] ) {
		return $b['is_case_study'] <=> $a['is_case_study'];
	}
	return strcmp( $b['date'], $a['date'] );
} );

// Collect terms for filter UI (only terms that actually appear on these posts)
$filter_categories = [];
$filter_products   = [];
$filter_offices    = [];

foreach ( $cards as $card ) {
	foreach ( $card['categories'] as $t ) {
		$filter_categories[ $t->slug ] = $t->name;
	}
	foreach ( $card['products'] as $t ) {
		$filter_products[ $t->slug ] = $t->name;
	}
	foreach ( $card['offices'] as $t ) {
		// Strip "Acrylicon" prefix for cleaner display
		$name = preg_replace( '/^Acrylicon\s+/i', '', $t->name );
		$filter_offices[ $t->slug ] = $name;
	}
}

asort( $filter_categories );
asort( $filter_products );
asort( $filter_offices );

$total = count( $cards );
?>

<?php if ( $show_taxonomy ) : ?>
<div class="reference-filters mb-10 space-y-6" data-total="<?php echo $total; ?>">

	<?php if ( $filter_categories ) : ?>
	<div class="filter-group" data-filter-taxonomy="categories">
		<h4 class="font-sohne-mono text-base mb-3"><?php echo $is_english ? 'Industry' : 'Industri'; ?></h4>
		<div class="flex flex-wrap gap-2">
			<button type="button" class="filter-pill active rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono bg-acryl-dark-blue text-white transition-colors" data-filter-value="all">
				<?php echo $is_english ? 'All' : 'Alle'; ?>
			</button>
			<?php foreach ( $filter_categories as $slug => $name ) : ?>
			<button type="button" class="filter-pill rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono hover:bg-gray-100 transition-colors" data-filter-value="<?php echo esc_attr( $slug ); ?>">
				<?php echo esc_html( $name ); ?>
			</button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $filter_products ) : ?>
	<div class="filter-group" data-filter-taxonomy="products">
		<h4 class="font-sohne-mono text-base mb-3"><?php echo $is_english ? 'Product system' : 'Produktsystem'; ?></h4>
		<div class="flex flex-wrap gap-2">
			<button type="button" class="filter-pill active rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono bg-acryl-dark-blue text-white transition-colors" data-filter-value="all">
				<?php echo $is_english ? 'All' : 'Alle'; ?>
			</button>
			<?php foreach ( $filter_products as $slug => $name ) : ?>
			<button type="button" class="filter-pill rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono hover:bg-gray-100 transition-colors" data-filter-value="<?php echo esc_attr( $slug ); ?>">
				<?php echo esc_html( $name ); ?>
			</button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $filter_offices ) : ?>
	<div class="filter-group" data-filter-taxonomy="offices">
		<h4 class="font-sohne-mono text-base mb-3"><?php echo $is_english ? 'Office' : 'Kontor'; ?></h4>
		<div class="flex flex-wrap gap-2">
			<button type="button" class="filter-pill active rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono bg-acryl-dark-blue text-white transition-colors" data-filter-value="all">
				<?php echo $is_english ? 'All' : 'Alle'; ?>
			</button>
			<?php foreach ( $filter_offices as $slug => $name ) : ?>
			<button type="button" class="filter-pill rounded-full px-4 py-2 border border-solid border-acryl-neutral-1 text-sm font-sohne-mono hover:bg-gray-100 transition-colors" data-filter-value="<?php echo esc_attr( $slug ); ?>">
				<?php echo esc_html( $name ); ?>
			</button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<p class="reference-count font-sohne-mono text-sm text-acryl-gray-1">
		<?php echo $is_english ? 'Showing' : 'Viser'; ?>
		<span class="reference-count-visible"><?php echo $total; ?></span>
		<?php echo $is_english ? 'of' : 'av'; ?>
		<?php echo $total; ?>
		<?php echo $is_english ? 'references' : 'referanser'; ?>
	</p>
</div>
<?php endif; ?>

<div class="reference-grid grid md:grid-cols-2 lg:grid-cols-3 gap-10">
	<?php foreach ( $cards as $card ) :
		$post_id       = $card['id'];
		$is_case_study = $card['is_case_study'];

		$cat_slugs     = implode( ',', wp_list_pluck( $card['categories'], 'slug' ) );
		$product_slugs = implode( ',', wp_list_pluck( $card['products'], 'slug' ) );
		$office_slugs  = implode( ',', wp_list_pluck( $card['offices'], 'slug' ) );
	?>
	<div class="reference-card flex flex-col<?php echo $is_case_study ? ' lg:col-span-2' : ''; ?>"
		data-categories="<?php echo esc_attr( $cat_slugs ); ?>"
		data-products="<?php echo esc_attr( $product_slugs ); ?>"
		data-offices="<?php echo esc_attr( $office_slugs ); ?>"
		data-type="<?php echo $is_case_study ? 'case-study' : 'reference'; ?>">

		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
		<div class="mb-4">
			<div class="block relative">
				<div class="absolute top-3 left-3 flex flex-wrap gap-1 z-10">
					<?php if ( $is_case_study ) : ?>
						<span class="inline-block bg-acryl-red text-white rounded-full px-3 py-1 text-sm">
							<?php echo $is_english ? 'Case Study' : 'Dybdecase'; ?>
						</span>
					<?php endif; ?>
					<?php foreach ( $card['categories'] as $term ) : ?>
						<span class="inline-block bg-acryl-beige-lightest rounded-full px-3 py-1 text-sm">
							<?php echo esc_html( $term->name ); ?>
						</span>
					<?php endforeach; ?>
				</div>
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="block">
					<?php echo get_the_post_thumbnail( $post_id, 'large', [
						'class' => 'w-full object-cover rounded-lg h-124',
						'alt'   => get_the_title( $post_id ),
					] ); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>

		<h3 class="<?php echo $is_case_study ? 'text-4xl' : 'text-3xl'; ?> font-normal my-0 mb-2">
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="no-underline">
				<?php echo get_the_title( $post_id ); ?>
			</a>
		</h3>

		<?php if ( $card['products'] ) : ?>
		<div class="text-base text-black font-sohne-mono">
			<?php echo esc_html( implode( ', ', wp_list_pluck( $card['products'], 'name' ) ) ); ?>
		</div>
		<?php endif; ?>

		<?php if ( has_excerpt( $post_id ) ) : ?>
		<p class="text-acryl-gray-1 mt-2">
			<?php echo get_the_excerpt( $post_id ); ?>
		</p>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>
