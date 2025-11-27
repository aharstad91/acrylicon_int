<?php
/**
 * Header With Red Back Link Block Template.
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
$class_name = 'header-with-red-back-link';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}

// Get field values
$back_text = get_field('back_text');
$back_link_text = get_field('back_link_text');
$title = get_field('title');
?>

<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?> mb-8">
	<?php if ($back_text && $back_link_text) : ?>
		<a href="<?php echo esc_url(home_url($back_link_text)); ?>" class="text-red flex items-center gap-2 text-red-600 mb-4 font-sohne-mono">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
				<path d="M19 12H5"></path>
				<path d="M12 19l-7-7 7-7"></path>
			</svg>
			<?php echo esc_html($back_text); ?>
		</a>
	<?php endif; ?>

	<?php if ($title) : ?>
		<h1 class="text-3xl lg:text-7xl font-buch mt-4 text-gray-900">
			<?php echo esc_html($title); ?>
		</h1>
	<?php endif; ?>
</div>
