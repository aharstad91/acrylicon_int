<?php
// Main content section
?>
<main class="max-w-screen-2xl mx-auto px-4 pt-20 lg:pt-44 pb-8">
	<section>
		<div class="editor"><?php the_content(); ?></div>

		<?php
		// Get category terms for the current post
		$category_terms = get_the_terms(get_the_ID(), 'referanser-kategorier');

		// Check and use terms for related posts
		if ($category_terms && !is_wp_error($category_terms)):
			$related_args = array(
				'post_type' => 'referanser',
				'posts_per_page' => 3,
				'post__not_in' => array(get_the_ID()),
				'orderby' => 'rand',
				'tax_query' => array(
					array(
						'taxonomy' => 'referanser-kategorier',
						'field' => 'term_id',
						'terms' => wp_list_pluck($category_terms, 'term_id'),
					),
				),
			);
			$related_query = new WP_Query($related_args);
			
			if ($related_query->have_posts()): ?>
				<div class="mt-16 mb-20">
					<h2 class="text-2xl">Lignende prosjekter</h2>
					<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10 mt-8">
						<?php while ($related_query->have_posts()): $related_query->the_post(); ?>
							<div class="flex flex-col">
								<?php if (has_post_thumbnail()): ?>
									<div class="mb-4">
										<div class="block relative">
											<?php 
											// Get the terms for the current post
											$type_terms = get_the_terms(get_the_ID(), 'referanser-type');
											$post_terms = get_the_terms(get_the_ID(), 'referanser-kategorier');
											?>
											
											<?php if (($type_terms && !is_wp_error($type_terms)) || ($post_terms && !is_wp_error($post_terms))): ?>
												<div class="mb-2 absolute">
													<?php 
													// Display dybdecase if it exists
													if ($type_terms && !is_wp_error($type_terms)): 
														foreach($type_terms as $term): 
															if($term->slug === 'dybdecase'): ?>
																<span class="inline-block bg-red text-white no-underline rounded-full px-3 py-1 text-sm mr-2 relative top-3 left-3">
																	<?php echo esc_html($term->name); ?>
																</span>
															<?php endif;
														endforeach;
													endif; ?>
													
													<?php 
													// Display category terms
													if ($post_terms && !is_wp_error($post_terms)): 
														foreach($post_terms as $term): ?>
															<span class="inline-block bg-neutral-3 no-underline rounded-full px-3 py-1 text-sm mr-2 relative top-3 left-3">
																<?php echo esc_html($term->name); ?>
															</span>
														<?php endforeach;
													endif; ?>
												</div>
											<?php endif; ?>
											
											<a href="<?php echo esc_url(get_permalink()); ?>" class="block">
												<?php 
												the_post_thumbnail('large', array(
													'class' => 'h-124 w-full object-cover rounded-lg',
													'alt' => get_the_title()
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
								
								<?php 
								$product_terms = get_the_terms(get_the_ID(), 'referanser-produkter');
								if ($product_terms && !is_wp_error($product_terms)): ?>
									<div class="text-base black font-sohne-mono">
										<?php echo esc_html(implode(', ', wp_list_pluck($product_terms, 'name'))); ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endwhile; ?>
					</div>
					
					<div class="mt-12 text-center">
						<a href="<?php echo home_url(); ?>/referanser" class="inline-block bg-gray-900 text-white px-6 py-3 rounded-full">
							Se alle referanser (101) ›
						</a>
					</div>
					
				</div>
				<?php 
				wp_reset_postdata();
			endif;
		endif; 
		?>
	</section>
</main>