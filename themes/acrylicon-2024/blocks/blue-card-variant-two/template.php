<?php
/**
 * Blue Card Variant Two Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */
// Create id attribute allowing for custom "anchor" value.
$id = 'blue-card-variant-two-' . $block['id'];
if (!empty($block['anchor'])) {
	$id = $block['anchor'];
}
// Create class attribute allowing for custom "className" and "align" values.
$className = 'blue-card-variant-two';
if (!empty($block['className'])) {
	$className .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$className .= ' align' . $block['align'];
}
// Get the column layout choice
$columns_count = get_field('columns_count');
$className .= " {$columns_count}";
?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
	<ul class="grid <?php 
		if ($columns_count === 'two-columns') {
			echo 'md-grid-cols-2';
		} elseif ($columns_count === 'three-columns') {
			echo 'md-grid-cols-3';
		} else {
			echo 'grid-cols-1';
		}
	?> gap-6">
		<?php 
		if (have_rows('blue-card-variant-two')):
			while (have_rows('blue-card-variant-two')): the_row();
				$number = get_sub_field('number');
				$title = get_sub_field('title');
				$active = get_sub_field('active');
				$link = get_sub_field('link');
				
				// Add border class if card is active/checked
				$card_class = 'bg-light-blue rounded-lg flex flex-row items-center justify-between px-4 lg:px-6 py-4 lg:py-8';
				if ($active) {
					$card_class .= ' border border-solid border-black';
				}
				
				// If we have a link, wrap the content in an anchor tag
				$has_link = !empty($link);
				
				if ($has_link) {
					$card_class .= ' cursor-pointer';
				}
		?>
			<li class="<?php echo esc_attr($card_class); ?>">
			<?php if ($has_link): ?>
				<a href="<?php echo esc_url(home_url($link)); ?>" class="w-full h-full flex flex-row items-center justify-between">
			<?php endif; ?>
				
				<div class="flex gap-2 items-center">
					<?php if ($number): ?>
						<div class="text-base font-sohne-mono">
							<?php echo esc_html($number); ?>.
						</div>
					<?php endif; ?>
					
					<?php if ($title): ?>
						<span class="text-lg lg:text-2xl font-normal">
							<?php echo esc_html($title); ?>
						</span>
					<?php endif; ?>
				</div>
				
				<div class="card-icon">
					<?php if ($active): ?>
						<svg xmlns="http://www.w3.org/2000/svg" class="check-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5"/>
						</svg>
					<?php else: ?>
						<svg xmlns="http://www.w3.org/2000/svg" class="arrow-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M5 12h14M12 5l7 7-7 7"/>
						</svg>
					<?php endif; ?>
				</div>
				
				<?php if ($has_link): ?>
				</a>
				<?php endif; ?>
			</li>
			<?php endwhile; ?>
		<?php endif; ?>
	</ul>
</div>