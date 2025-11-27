<?php
/**
 * Block Name: References Grid
 * Description: A block to display reference posts in a grid layout with category filter
 */

// Get selected posts
$selected_posts = get_field('specific_references');
$show_taxonomy = get_field('show_taxonomy') ?: false;
$post_count = get_field('post_count') ?: -1; // Default to all posts if not specified

// Get all terms for the navigation
$terms = get_terms([
	'taxonomy' => 'referanser-kategorier',
	'hide_empty' => true,
]);

// Get current term ID from URL if we're on a taxonomy archive
$current_term_id = get_queried_object_id();

// Store original query
global $wp_query;
$original_query = $wp_query;
?>

<?php if ($show_taxonomy && !empty($terms) && !is_wp_error($terms)): ?>
	<div class="mb-8">
		<h4 class="font-sohne-mono">Filtrer på industri</h4>
		<ul class="mt-4 flex flex-wrap gap-2 px-0 list-none reference-tax">
			<?php foreach ($terms as $term): ?>
				<li>
					<a href="<?php echo esc_url(get_term_link($term, 'referanser-kategorier')); ?>" 
					   class="flex rounded-full px-4 py-2 border border-solid border-neutral-1 no-underline <?php echo ($current_term_id == $term->term_id) ? 'bg-gray-900 text-white' : 'hover:bg-gray-100'; ?>">
					   <?php echo esc_html($term->name); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
<?php
// Check if specific posts are selected
if ($selected_posts): 
	// Use selected posts
	foreach ($selected_posts as $post): 
		setup_postdata($post);
		?>
		<div class="flex flex-col">
			<?php if (has_post_thumbnail($post)): ?>
				<div class="mb-4">
					<div class="block relative">
						<?php 
						// Get the terms for the current post
						$post_terms = get_the_terms($post->ID, 'referanser-kategorier');
						$product_terms = get_the_terms($post->ID, 'referanser-produkter');
						$type_terms = get_the_terms($post->ID, 'referanser-type');
						
						if (($post_terms && !is_wp_error($post_terms)) || ($type_terms && !is_wp_error($type_terms))): ?>
							<div class="mb-2 absolute">
								<?php 
								// First display dybdecase if it exists
								if ($type_terms && !is_wp_error($type_terms)): 
									foreach($type_terms as $term): 
										if($term->slug === 'dybdecase'): ?>
											<a href="<?php echo esc_url(get_term_link($term)); ?>" 
											class="inline-block bg-red text-white no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
												<?php echo esc_html($term->name); ?>
											</a>
										<?php endif;
									endforeach;
								endif; ?>
								<?php if ($post_terms && !is_wp_error($post_terms)): ?>
									<?php foreach($post_terms as $term): ?>
										<a href="<?php echo esc_url(get_term_link($term)); ?>" 
										class="inline-block bg-neutral-3 no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
											<?php echo esc_html($term->name); ?>
										</a>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="block">
							<?php 
							echo get_the_post_thumbnail($post->ID, 'large', array(
								'class' => 'h-124 w-full object-cover rounded-lg',
								'alt'   => get_the_title($post->ID)
							)); 
							?>
						</a>
					</div>
				</div>
			<?php endif; ?>
			<h3 class="text-3xl font-normal my-0 mb-2">
				<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="no-underline">
					<?php echo get_the_title($post->ID); ?>
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
			<?php if (has_excerpt($post->ID)): ?>
				<p class="text-gray-600">
					<?php echo get_the_excerpt($post->ID); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endforeach; 
	wp_reset_postdata();
else: 
	// No specific posts selected, query all reference posts
	$query_args = [
		'post_type' => 'referanser',
		'posts_per_page' => $post_count,
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC'
	];
	
	// Add tax query if we're on a taxonomy archive
	if ($current_term_id && term_exists($current_term_id, 'referanser-kategorier')) {
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'referanser-kategorier',
				'field'    => 'term_id',
				'terms'    => $current_term_id,
			]
		];
	}
	
	$references_query = new WP_Query($query_args);
	
	if ($references_query->have_posts()):
		while ($references_query->have_posts()): $references_query->the_post();
			$post = get_post();
			?>
			<div class="flex flex-col">
				<?php if (has_post_thumbnail($post->ID)): ?>
					<div class="mb-4">
						<div class="block relative">
							<?php 
							// Get the terms for the current post
							$post_terms = get_the_terms($post->ID, 'referanser-kategorier');
							$product_terms = get_the_terms($post->ID, 'referanser-produkter');
							$type_terms = get_the_terms($post->ID, 'referanser-type');
							
							if (($post_terms && !is_wp_error($post_terms)) || ($type_terms && !is_wp_error($type_terms))): ?>
								<div class="mb-2 absolute">
									<?php 
									// First display dybdecase if it exists
									if ($type_terms && !is_wp_error($type_terms)): 
										foreach($type_terms as $term): 
											if($term->slug === 'dybdecase'): ?>
												<a href="<?php echo esc_url(get_term_link($term)); ?>" 
												class="inline-block bg-red text-white no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
													<?php echo esc_html($term->name); ?>
												</a>
											<?php endif;
										endforeach;
									endif; ?>
									<?php if ($post_terms && !is_wp_error($post_terms)): ?>
										<?php foreach($post_terms as $term): ?>
											<a href="<?php echo esc_url(get_term_link($term)); ?>" 
											class="inline-block bg-neutral-3 no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
												<?php echo esc_html($term->name); ?>
											</a>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="block">
								<?php 
								echo get_the_post_thumbnail($post->ID, 'large', array(
									'class' => 'h-124 w-full object-cover rounded-lg',
									'alt'   => get_the_title($post->ID)
								)); 
								?>
							</a>
						</div>
					</div>
				<?php endif; ?>
				<h3 class="text-3xl font-normal my-0 mb-2">
					<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="no-underline">
						<?php echo get_the_title($post->ID); ?>
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
				<?php if (has_excerpt($post->ID)): ?>
					<p class="text-gray-600">
						<?php echo get_the_excerpt($post->ID); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endwhile;
		wp_reset_postdata();
	else: ?>
		<p class="text-gray-600">No references found.</p>
	<?php endif;
endif; ?>
</div>
<?php 
// Restore original query
$wp_query = $original_query;
wp_reset_query();
?>