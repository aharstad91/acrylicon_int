<?php
/**
 * Info Card Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'info-card-' . $block['id'];
if (!empty($block['anchor'])) {
	$id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$className = 'info-card bg-light-blue p-5 rounded-lg max-w-sm mx-auto';
if (!empty($block['className'])) {
	$className .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$className .= ' align' . $block['align'];
}

// Load values and handle defaults.
$icon = get_field('icon');
$title = get_field('title');
$text = get_field('text');
$icon_size = get_field('icon_size');
$text_size = get_field('text_size');

// Add size classes
$className .= " icon-{$icon_size} text-{$text_size} py-6 px-6 bg-blue";

// Determine icon size class
$icon_class = 'info-card-icon ';
$icon_class .= ($icon_size === 'large') ? 'w-24 h-24' : 'w-20 h-20';

// Determine text size classes
$title_class = 'info-card-title mb-2 font-sohne-mono text-base font-light';

$text_class = 'info-card-description ';
$text_class .= ($text_size === 'large') ? 'text-3xl' : 'text-lg';

?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
	<div class="icon-wrapper mb-4">
		<?php echo wp_get_attachment_image($icon, 'thumbnail', false, array('class' => $icon_class)); ?>
	</div>
	<h3 class="<?php echo esc_attr($title_class); ?>"><?php echo esc_html($title); ?></h3>
	<p class="<?php echo esc_attr($text_class); ?>"><?php echo esc_html($text); ?></p>
</div>