<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
	<div class="prose max-w-none text-xl">
		<div class="swiper-container">
			<!-- Fjernet h-96 lg:h-160 fra swiper container siden vi vil at den skal tilpasse seg bildene -->
			<div class="swiper mySwiper mobile-optimized">
				<!-- Click areas -->
				<div class="swiper-area-prev"></div>
				<div class="swiper-area-next"></div>
				
				<div class="swiper-wrapper">
					<?php 
					$images = get_field('slider_images');
					if($images): 
						foreach($images as $image):
					?>
						<div class="swiper-slide">
							<img 
								src="<?php echo esc_url($image['url']); ?>" 
								alt="<?php echo esc_attr($image['alt']); ?>"
								class="w-full h-auto object-contain rounded-lg"
							>
						</div>
					<?php 
						endforeach; 
					endif; 
					?>
				</div>
				
				<!-- Navigation arrows -->
				<div class="swiper-button-prev"></div>
				<div class="swiper-button-next"></div>
				
				<!-- Pagination -->
				<div class="swiper-pagination"></div>
			</div>
		</div>
	</div>
</div>