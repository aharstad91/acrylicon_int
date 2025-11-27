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
	<?php if (have_rows('download_list_repeater')): 
		while (have_rows('download_list_repeater')) : the_row();
			$name = get_sub_field('download_name');
			$link = get_sub_field('download_link');
			?>
			<div class="items-center py-4 flex gap-4 border-solid border-gray-2 border-t">
				<div class="flex w-full font-sohne-mono gap-4 ">
					<img src="<?php bloginfo('template_directory'); ?>/assets/gfx/download-file.svg" alt="Last ned fil">
					<div class="gap-4 w-full md:flex">
						<?php if ($name) : ?>
							<div class="text-black md:w-1-2 text-base font-normal font-sohne-mono mb-1"><?php echo esc_html($name); ?></div>
						<?php endif; ?>
						<?php if ($link) : ?>
							<a href="<?php echo esc_html($link); ?>" class="md:w-1-2 md:flex justify-end text-black">
								<span class="underline">Last ned</span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endwhile;
	endif; ?>
</div>