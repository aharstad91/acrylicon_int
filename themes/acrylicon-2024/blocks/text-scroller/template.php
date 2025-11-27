<?php
/**
 * Text Scroller Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 * @param   array $context The context provided to the block by the post or its parent block.
 */
// Block preview
if (!empty($block['data']['preview_image_help'])) {
	echo '<img src="' . $block['data']['preview_image_help'] . '" style="width:100%; height:auto;">';
	return;
}
// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) {
	$anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}
// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'text-scroller';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}
// Get field values
$orientation = get_field('orientation') ?: 'right';
$scroll_direction = $orientation === 'left' ? 'scroll-left' : 'scroll-right';
?>
<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?> relative overflow-hidden">	
	<?php if (have_rows('text_scroller_repeater')): ?>
		<div class="scroller-outer">
			<!-- Left fade gradient -->
			<div class="fade-overlay fade-left"></div>
			
			<div class="scroller-container <?php echo esc_attr($scroll_direction); ?>">
				<div class="scroller-track">
					<?php 
					// Count total items for calculations
					$total_items = count(get_field('text_scroller_repeater'));
					if ($total_items > 0):
						// Display items three times to ensure seamless looping
						for ($i = 0; $i < 3; $i++):
							while (have_rows('text_scroller_repeater')): the_row();
								$value = get_sub_field('value');
								if (!empty($value)):
					?>
						<div class="scroller-item text-2xl lg:text-5xl">
							<span class="font-buch text-gray-900"><?php echo esc_html($value); ?></span>
							<span class="bullet px-4 text-neutral-1">•</span>
						</div>
					<?php 
								endif;
							endwhile;
							// Reset the repeater for next iteration
							reset_rows();
						endfor; 
					endif;
					?>
				</div>
			</div>
			
			<!-- Right fade gradient -->
			<div class="fade-overlay fade-right"></div>
		</div>
	<?php endif; ?>
</div>