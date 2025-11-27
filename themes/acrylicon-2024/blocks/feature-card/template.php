<?php
/**
 * Reference Meta Block Template.
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
$class_name = 'reference-meta-block';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}
?>
<div <?php echo $anchor; ?> class="reference-meta-block-container grid lg:grid-cols-2 gap-2 <?php echo esc_attr($class_name); ?>">
	<?php if (have_rows('feature_cards_repeater')): 
		while (have_rows('feature_cards_repeater')) : the_row();
			$image = get_sub_field('image');
			$title = get_sub_field('title');
			$excerpt = get_sub_field('excerpt');
			?>
			<div class="featured-card bg-neutral-2 items-center py-6 px-4 flex gap-4 rounded-lg">
				<?php 
				if ($image) {
					echo wp_get_attachment_image($image, 'full', false, array(
						'width' => 38,
						'height' => 38,
						'class' => 'w-24'
					));
				}
				?>
				<dl>
					<?php if ($title) : ?>
						<dt class="text-base font-normal font-sohne-mono text-black mb-1"><?php echo esc_html($title); ?></dt>
					<?php endif; ?>
					<?php if ($excerpt) : ?>
						<dd class="text-lg text-black"><?php echo wp_kses_post($excerpt); ?></dd>
					<?php endif; ?>
				</dl>
			</div>
		<?php endwhile;
	endif; ?>
</div>