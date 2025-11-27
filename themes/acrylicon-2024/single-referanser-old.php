<main class="max-w-screen-2xl mx-auto px-4 md:px-12 lg:gap-12 lg:mt-44 md:mt-16 pb-8">
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<header class="mb-12 flex-col-reverse md:flex-col lg:items-center flex-col lg:flex-row flex justify-between gap-4">
			<!--<h1 class="lg:text-7xl md:text-6xl text-4xl font-light"><?php the_title(); ?></h1>-->
			<?php 
			$category_terms = get_the_terms(get_the_ID(), 'referanser-kategorier');
			if ($category_terms && !is_wp_error($category_terms)): 
			?>
				<div class="">
					<?php foreach($category_terms as $term): ?>
						<a href="<?php echo esc_url(get_term_link($term)); ?>" 
				   		class="flex-shrink no-wrap bg-red text-white rounded-full px-3 py-1 text-sm mr-2 hover:bg-gray-300">
							<?php echo esc_html($term->name); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</header>
		
		<!--<?php if (has_post_thumbnail()): ?>
			<div class="relative h-[60vh] mb-12">
				<?php 
				the_post_thumbnail('full', array(
					'class' => 'w-full h-600 object-cover rounded',
				)); 
				?>
			</div>
		<?php endif; ?> -->
	
		<div class="prose max-w-none text-xl">
			<h1 class="my-reset lg:text-7xl md:text-5xl text-3xl has-lg-text-7-xl-md-text-5-xl-text-3-xl-font-size"><?php the_title();?></h1>
			<div class="lg:mb-20 editor"><?php the_content(); ?></div>
			<div class="swiper mySwiper h-600">
				<div class="swiper-wrapper">
					<?php 
					if( have_rows('referance_images_repeater') ): 
						while ( have_rows('referance_images_repeater') ) : the_row();
							$images = get_sub_field('referance_images_repeater_image'); 
					?>
						<div class="swiper-slide">
							<img 
								src="<?php echo esc_url($images['url']); ?>" 
								alt="<?php echo esc_attr($images['alt']); ?>"
								class="w-full h-96 object-cover rounded-lg"
							>
						</div>
					<?php 
						endwhile; 
					endif; 
					?>
				</div>
				<div class="swiper-button-next"></div>
				<div class="swiper-button-prev"></div>
				<div class="swiper-pagination"></div>
			</div>
		</div>

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
  </article>
</main>