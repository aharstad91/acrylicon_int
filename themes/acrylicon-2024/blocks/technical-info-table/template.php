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
<div <?php echo $anchor; ?> class="mt-6" <?php echo esc_attr($class_name); ?>">
	<?php if (have_rows('technical_info_repeater')): 
		while (have_rows('technical_info_repeater')) : the_row();
			$name = get_sub_field('tech_info_name');
			$desc = get_sub_field('tech_info_desc');
			?>
			<div class="items-center py-4 flex gap-4 border-solid border-gray border-t">
				<dl class="md:flex w-full font-sohne-mono">
					<?php if ($name) : ?>
						<dt class="text-black md:w-1-2 text-base font-normal font-sohne-mono mb-1"><?php echo esc_html($name); ?></dt>
					<?php endif; ?>
					<?php if ($desc) : ?>
						<dd class="text-gray-1 md:w-1-2"><?php echo wp_kses_post($desc); ?></dd>
					<?php endif; ?>
				</dl>
			</div>
		<?php endwhile;
	endif; ?>
</div>