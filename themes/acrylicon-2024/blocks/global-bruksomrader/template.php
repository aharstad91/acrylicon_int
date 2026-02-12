<?php
/**
 * Block Name: Bruksområder Grid
 * Description: A block to display bruksområder posts in a grid layout with category filter
 */

// Get number of posts to display from ACF field, default to -1 (all posts)
$post_count = get_field('post_count') ?: -1;

// Get all terms for the navigation
$terms = get_terms([
	'taxonomy' => 'bruksomrader-kategorier',
	'hide_empty' => true,
]);

// Get current term ID from URL if we're on a taxonomy archive
$current_term_id = get_queried_object_id();

// Store original query
global $wp_query;
$original_query = $wp_query;

// Setup query args
$query_args = [
	'post_type' => 'bruksomrader',
	'posts_per_page' => $post_count,
	'post_status' => 'publish',
	'orderby' => 'date',
	'order' => 'DESC'
];

// Add tax query if we're on a taxonomy archive
if ($current_term_id && term_exists($current_term_id, 'bruksomrader-kategorier')) {
	$query_args['tax_query'] = [
		[
			'taxonomy' => 'bruksomrader-kategorier',
			'field'    => 'term_id',
			'terms'    => $current_term_id,
		],
	];
}

// Setup new query
$wp_query = new WP_Query($query_args);
?>

<?php if (!empty($terms) && !is_wp_error($terms)): ?>
	<div class="mb-8">
		<h4 class="font-sohne-mono">Filtrer på kategori</h4>
		<ul class="mt-4 flex flex-wrap gap-2 px-0 list-none reference-tax">
			<?php foreach ($terms as $term): ?>
				<li>
					<a href="<?php echo esc_url(get_term_link($term, 'bruksomrader-kategorier')); ?>" 
					   class="flex rounded-full px-4 py-2 border border-solid border-acryl-beige-light no-underline <?php echo ($current_term_id == $term->term_id) ? 'bg-gray-900 text-white' : 'hover:bg-gray-100'; ?>">
					   <?php echo esc_html($term->name); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
	<?php if (have_posts()): ?>
		<?php while (have_posts()): the_post(); ?>
			<div class="flex flex-col">
				<?php if (has_post_thumbnail()): ?>
					<div class="mb-4">
						<div class="block relative">
							<?php 
							// Get the terms for the current post
							$post_terms = get_the_terms(get_the_ID(), 'bruksomrader-kategorier');
							if ($post_terms && !is_wp_error($post_terms)): ?>
								<div class="mb-2 absolute">
									<?php foreach($post_terms as $term): ?>
										<a href="<?php echo esc_url(get_term_link($term)); ?>" 
										class="inline-block bg-acryl-beige-lightest no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
											<?php echo esc_html($term->name); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url(get_permalink()); ?>" class="block">
								<?php 
								the_post_thumbnail('large', array(
									'class' => 'h-[440px] w-full object-cover rounded-lg',
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
				<?php if (has_excerpt()): ?>
					<p class="text-gray-600">
						<?php the_excerpt(); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endwhile; ?>
	<?php else: ?>
		<p class="text-gray-600">No bruksområder found.</p>
	<?php endif; ?>
</div>

<?php
// Restore original query
$wp_query = $original_query;
wp_reset_postdata();
?>