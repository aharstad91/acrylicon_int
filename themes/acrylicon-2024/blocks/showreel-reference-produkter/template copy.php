<?php
/**
 * Block Name: Taxonomy Term Posts
 * Description: A block to display posts with images from referanser-kategorier taxonomy.
 */
$term_id = get_field('showreel-reference-produkter');
$posts_per_page = get_field('post_count') ?: 3; // Using post_count field with fallback to 3

if ($term_id):
	$term = get_term($term_id);
	
	global $wp_query;
	$original_query = $wp_query;
	$wp_query = new WP_Query([
		'post_type' => 'referanser',
		'tax_query' => [
			[
				'taxonomy' => 'referanser-produkter',
				'field'    => 'term_id',
				'terms'    => $term_id,
			],
		],
		'posts_per_page' => $posts_per_page,
		'post_status' => 'publish',
		'orderby' => 'rand', // Add this line to randomize the order

	]); ?>
	<div class="grid grid-cols-3 gap-10">
		<?php if (have_posts()): ?>
			<?php while (have_posts()): the_post(); 
				// Get the terms for the current post
				$terms = get_the_terms(get_the_ID(), 'referanser-produkter');
			?>
				<div class="flex flex-col">
					<?php if (has_post_thumbnail()): ?>
						<div class="mb-4">
							<a href="<?= esc_url(get_permalink()) ?>">
								<?php 
								the_post_thumbnail('large', array(
									'class' => 'h-500 w-full object-cover rounded-lg',
									'alt'   => get_the_title()
								)); 
								?>
							</a>
						</div>
					<?php endif; ?>
					<?php if ($terms && !is_wp_error($terms)): ?>
						<div class="mb-2">
							<?php foreach($terms as $term): ?>
								<a href="<?php echo esc_url(get_term_link($term)); ?>" 
								   class="inline-block bg-gray-200 rounded px-3 py-1 text-sm mr-2 hover:bg-gray-300">
									<?php echo esc_html($term->name); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<h3 class="text-xl font-bold mb-2">
						<a href="<?= esc_url(get_permalink()) ?>" class="hover:text-gray-600">
							<?php the_title() ?>
						</a>
					</h3>
					<?php if (has_excerpt()): ?>
						<p class="text-gray-600"><?php the_excerpt() ?></p>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php else: ?>
			<p class="text-gray-600">No posts found in this term.</p>
		<?php endif;
		$wp_query = $original_query;
		wp_reset_postdata();
	else: ?>
		<p class="text-gray-600">Please select a taxonomy term.</p>
	<?php endif; ?>
</div>