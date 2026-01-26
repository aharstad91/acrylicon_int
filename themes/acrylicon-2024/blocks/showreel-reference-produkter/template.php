<?php
/**
 * Block Name: Taxonomy Term Posts
 * Description: A block to display posts with images from referanser-kategorier taxonomy.
 */

// Initialiser variabler
$term_id = null;
$taxonomy = 'referanser-kategorier'; // Standard taksonomi
$field_used = '';
$debug = false; // Sett til false i produksjon

// Prøv alle mulige felt- og taksonomi-kombinasjoner
$acf_field_options = [
	'showreel-reference-bruksomrader' => 'referanser-kategorier',
	'showreel-reference-produkter' => 'referanser-produkter',
	'showreel-reference-kontor' => 'referanser-kategorier'
];

// Prøv alle mulige feltnavnkombinasjoner
foreach ($acf_field_options as $field_name => $tax_name) {
	$field_value = get_field($field_name);
	if ($field_value) {
		$term_id = $field_value;
		$taxonomy = $tax_name;
		$field_used = $field_name;
		break;
	}
}

// Verifisere at termen eksisterer
$term_exists = false;
if ($term_id) {
	// Sjekk først i den forventede taksonomien
	$term = get_term($term_id, $taxonomy);
	if (!is_wp_error($term) && $term) {
		$term_exists = true;
	} else {
		// Hvis ikke funnet, sjekk i alle taksonomier
		$all_taxonomies = ['referanser-kategorier', 'referanser-produkter', 'referanser-type'];
		foreach ($all_taxonomies as $tax) {
			$term = get_term($term_id, $tax);
			if (!is_wp_error($term) && $term) {
				$taxonomy = $tax; // Bruk denne taksonomien istedet
				$term_exists = true;
				break;
			}
		}
	}
}

$posts_per_page = get_field('post_count') ?: 3; // Using post_count field with fallback to 3

// Hvis termen ikke finnes, vis alle referanser istedet (fallback)
if (!$term_exists) {
	$query_args = [
		'post_type' => 'referanser',
		'posts_per_page' => $posts_per_page,
		'post_status' => 'publish',
		'orderby' => 'rand',
	];
} else {
	$query_args = [
		'post_type' => 'referanser',
		'tax_query' => [
			[
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_id,
			],
		],
		'posts_per_page' => $posts_per_page,
		'post_status' => 'publish',
		'orderby' => 'rand',
	];
}

// Lagre opprinnelig spørring
global $wp_query;
$original_query = $wp_query;

// Kjør spørringen
$wp_query = new WP_Query($query_args);
?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
	<?php if (have_posts()): ?>
		<?php while (have_posts()): the_post(); 
			$post_id = get_the_ID();
		?>
			<div class="flex flex-col">
				<?php if (has_post_thumbnail()): ?>
					<div class="mb-4">
						<div class="block relative">
							<?php 
							// Get the terms for the current post
							$post_terms = get_the_terms($post_id, 'referanser-kategorier');
							$product_terms = get_the_terms($post_id, 'referanser-produkter');
							$type_terms = get_the_terms($post_id, 'referanser-type');
							
							if (($post_terms && !is_wp_error($post_terms)) || ($type_terms && !is_wp_error($type_terms)) || ($product_terms && !is_wp_error($product_terms))): ?>
								<div class="mb-2 absolute">
									<?php 
									// First display dybdecase if it exists
									if ($type_terms && !is_wp_error($type_terms)): 
										foreach($type_terms as $term): 
											if($term->slug === 'dybdecase'): ?>
												<a href="<?php echo esc_url(get_term_link($term)); ?>" 
												class="inline-block bg-acryl-red text-white no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
													<?php echo esc_html($term->name); ?>
												</a>
											<?php endif;
										endforeach;
									endif; ?>
									
									<?php 
									// Display relevant terms based on context
									$display_terms = [];
									if (strpos($field_used, 'bruksomrader') !== false && $post_terms && !is_wp_error($post_terms)) {
										$display_terms = $post_terms;
									} elseif (strpos($field_used, 'produkter') !== false && $product_terms && !is_wp_error($product_terms)) {
										$display_terms = $product_terms;
									} elseif (strpos($field_used, 'kontor') !== false && $post_terms && !is_wp_error($post_terms)) {
										$display_terms = $post_terms;
									} elseif ($post_terms && !is_wp_error($post_terms)) {
										$display_terms = $post_terms;
									}
									
									foreach($display_terms as $term): ?>
										<a href="<?php echo esc_url(get_term_link($term)); ?>" 
										class="inline-block bg-acryl-beige-lightest no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
											<?php echo esc_html($term->name); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url(get_permalink()); ?>" class="block">
								<?php 
								echo get_the_post_thumbnail($post_id, 'large', array(
									'class' => 'h-124 w-full object-cover rounded-lg',
									'alt'   => get_the_title()
								)); 
								?>
							</a>
						</div>
					</div>
				<?php endif; ?>
				<h3 class="text-3xl font-normal my-0 mb-2">
					<a href="<?php echo esc_url(get_permalink()); ?>" class="no-underline">
						<?php the_title(); ?>
					</a>
				</h3>
				<?php if ($product_terms && !is_wp_error($product_terms)): ?>
					<div class="text-base black font-sohne-mono">
						<?php 
						$product_names = array_map(function($term) {
							return esc_html($term->name);
						}, $product_terms);
						echo implode(', ', $product_names);
						?>
					</div>
				<?php endif; ?>
				<?php if (has_excerpt()): ?>
					<p class="text-gray-600">
						<?php echo get_the_excerpt(); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	<?php else: ?>
		<p class="text-gray-600">No posts found in this term.</p>
	<?php endif;
	$wp_query = $original_query;
	wp_reset_query();
?>
</div>