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

// Get the image fields
$image_one = get_field('img_one');
$image_two = get_field('img_two');
?>

<div <?php echo $anchor; ?> class="mt-6 grid gap-1 grid-cols-2 object-cover h-full <?php echo esc_attr($class_name); ?>">
	<?php if ($image_one) : ?>
		<?php echo wp_get_attachment_image($image_one['ID'], 'full', false, array('class' => 'w-full h-96 lg:h-900 object-cover rounded-lg')); ?>
	<?php endif; ?>
	
	<?php if ($image_two) : ?>
		<?php echo wp_get_attachment_image($image_two['ID'], 'full', false, array('class' => 'w-full h-96 lg:h-900 object-cover rounded-lg')); ?>
	<?php endif; ?>
</div>