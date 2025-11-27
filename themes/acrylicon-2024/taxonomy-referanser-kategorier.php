<?php
/**
 * Template for displaying referanser-kategorier taxonomy archives
 */
get_header();

// Get the current taxonomy term
$current_term = get_queried_object();

// Get all terms for the navigation
$terms = get_terms([
	'taxonomy' => 'referanser-kategorier',
	'hide_empty' => true,
]);
?>

<div class="max-w-screen-2xl mx-auto px-4 mt-44 pb-8">
	<div class="header-with-red-back-link mb-8">
		<a href="<?php bloginfo('url');?>/referanser" class="text-red flex items-center gap-2 text-red-600 mb-4 font-sohne-mono">
			<?php 
			echo svg_icon('arrow', [
				'width' => '16px',
				'height' => '16px',
				'stroke' => '#E2241C',
			]); ?>
		Referanser</a>
		<h1 class="text-3xl lg:text-7xl font-buch mt-4 text-gray-900">
			<?php echo esc_html($current_term->name); ?>
		</h1>
	</div>

	<?php if (!empty($terms) && !is_wp_error($terms)): ?>
		<div class="mb-8">
			<h4 class="font-sohne-mono my-reset">Filtrer på industri</h4>
			<ul class="mt-4 flex flex-wrap gap-2 px-0 list-none">
				<?php foreach ($terms as $nav_term): ?>
					<li>
						<a href="<?php echo esc_url(get_term_link($nav_term, 'referanser-kategorier')); ?>" 
						   class="flex rounded-full px-4 py-2 border border-solid border-neutral-1 no-underline <?php echo ($current_term->term_id == $nav_term->term_id) ? 'bg-red text-white border-red' : 'hover:bg-gray-100'; ?>">
							<?php echo esc_html($nav_term->name); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
		<?php 
		// Endre spørring for å få tilfeldig rekkefølge før løkken
		global $wp_query;
		$args = array_merge($wp_query->query_vars, array('orderby' => 'rand'));
		query_posts($args);
		
		if (have_posts()): ?>
			<?php while (have_posts()): the_post(); ?>
				<div class="flex flex-col">
					<?php if (has_post_thumbnail()): ?>
						<div class="mb-4">
							<div class="block relative">
								<?php 
								// Get the terms for the current post
								$post_terms = get_the_terms(get_the_ID(), 'referanser-kategorier');
								$product_terms = get_the_terms(get_the_ID(), 'referanser-produkter');
								$type_terms = get_the_terms(get_the_ID(), 'referanser-type');
								
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
								<a href="<?php echo esc_url(get_permalink()); ?>" class="block">
									<?php 
									the_post_thumbnail('large', array(
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
							<?php the_excerpt(); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
			
			<?php 
			// Viktig: Gjenopprett den originale spørringen etter løkken
			wp_reset_query(); 
			?>
			
		<?php else: ?>
			<p class="text-gray-600">No references found.</p>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>