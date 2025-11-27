<?php
/**
 * Section Title with Red Button Right Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 * @param   array $context The context provided to the block by the post or its parent block.
 */
// Block preview image support
if (!empty($block['data']['preview_image_help'])) {
	echo '<img src="' . $block['data']['preview_image_help'] . '" style="width:100%; height:auto;">';
	return;
}
// Support custom "anchor" values
$anchor = '';
if (!empty($block['anchor'])) {
	$anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}
// Create class attribute allowing for custom "className" and "align" values
$class_name = 'section-title-red-button-right';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}
// Get ACF field values
$title = get_field('title');
$button_text = get_field('button_text');
$button_link = get_field('button_link');
?>
<div <?php echo $anchor; ?>class="<?php echo esc_attr($class_name); ?>">
	<div class="max-w-screen-2xl mx-auto">
		<div class="flex flex-col lg:flex-row lg:items-center justify-between lg:gap-40">
			<div class="flex-1">
				<h2 class="text-2xl md:text-3xl lg:text-5xl font-normal"><?php echo esc_html($title); ?></h2>
			</div>
			<div>
				<a href="<?php echo $button_link ? esc_url(home_url($button_link)) : esc_url(home_url('/')); ?>" style="white-space: nowrap; display: inline-block;" class="bg-red text-white rounded-full px-8 py-3 no-underline">
					<?php echo esc_html($button_text); ?>
					<svg xmlns="http://www.w3.org/2000/svg" style="margin-left: 0.5rem; width: 1rem; height: 1rem; display: inline-block; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 12h14"></path>
						<path d="M12 5l7 7-7 7"></path>
					</svg>
				</a>
			</div>
		</div>
	</div>
</div>