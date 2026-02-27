<?php
/**
 * Block Name: References Grid
 * Description: A block to display reference posts in a grid layout with category filter
 */

// Get selected posts
$selected_posts = get_field('specific_references');
$show_taxonomy = get_field('show_taxonomy') ?: false;

// Get all terms for the navigation
$terms = get_terms([
	'taxonomy' => 'referanser-kategorier',
	'hide_empty' => true,
]);

// Get current term ID from URL if we're on a taxonomy archive
$current_term_id = get_queried_object_id();
?>

<?php if ($show_taxonomy && !empty($terms) && !is_wp_error($terms)): ?>
	<div class="mb-8">
		<h4 class="font-sohne-mono"><?php echo ( get_current_blog_id() === 1 ) ? 'Filter by industry' : 'Filtrer på industri'; ?></h4>
		<ul class="mt-4 flex flex-wrap gap-2 px-0 list-none reference-tax">
			<?php foreach ($terms as $term): ?>
				<li>
					<a href="<?php echo esc_url(get_term_link($term, 'referanser-kategorier')); ?>" 
					   class="flex rounded-full px-4 py-2 border border-solid border-acryl-beige-light no-underline <?php echo ($current_term_id == $term->term_id) ? 'bg-gray-900 text-white' : 'hover:bg-gray-100'; ?>">
					   <?php echo esc_html($term->name); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
	<?php if ($selected_posts): ?>
		<?php $ref_index = 0; ?>
		<?php foreach ($selected_posts as $post): ?>
			<?php setup_postdata($post); ?>
			<div class="flex flex-col">
				<?php if (has_post_thumbnail($post)): ?>
					<div class="mb-4">
						<div class="block relative">
							<?php 
							// Get the terms for the current post
							$post_terms = get_the_terms($post->ID, 'referanser-kategorier');
							$product_terms = get_the_terms($post->ID, 'referanser-produkter');
							$is_dybde_case = has_term('dybdecase', 'referanser-type', $post->ID);

							if (($post_terms && !is_wp_error($post_terms)) || $is_dybde_case): ?>
								<div class="mb-2 absolute">
									<?php if ($is_dybde_case):
										$type_term = get_term_by('slug', 'dybdecase', 'referanser-type');
									?>
										<span class="inline-block bg-acryl-red text-white no-underline rounded-full px-3 py-1 text-sm mr-2 relative top-3 left-3">
											<?php echo $type_term ? esc_html($type_term->name) : 'Case study'; ?>
										</span>
									<?php endif; ?>
									<?php if ($post_terms && !is_wp_error($post_terms)): ?>
										<?php foreach($post_terms as $term): ?>
											<a href="<?php echo esc_url(get_term_link($term)); ?>" 
											class="inline-block bg-acryl-beige-lightest no-underline rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300 relative top-3 left-3">
												<?php echo esc_html($term->name); ?>
											</a>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="block">
								<?php
								$img_attrs = [
									'class' => 'h-124 w-full object-cover rounded-lg',
									'alt'   => get_the_title($post->ID),
									'sizes' => '(max-width: 639px) 100vw, (max-width: 959px) 50vw, 33vw',
								];
								// First 3 cards are above the fold — eager load, no lazy
								if ( $ref_index < 3 ) {
									$img_attrs['loading'] = 'eager';
								}
								echo get_the_post_thumbnail($post->ID, 'large', $img_attrs);
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
		<?php $ref_index++; ?>
		<?php endforeach; ?>
		<?php wp_reset_postdata(); ?>
	<?php else: ?>
		<p class="text-gray-600">No references selected.</p>
	<?php endif; ?>
</div>