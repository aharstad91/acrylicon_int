<?php
/**
 * Beige Card Variant Two Block Template.
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
$class_name = 'beige-card-variant-two';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}
// Get ACF fields
$title = get_field('title');
$excerpt = get_field('excerpt');
$button_settings = get_field('button_settings');

// Process excerpt to avoid nested paragraphs
$processed_excerpt = '';
if ($excerpt) {
	// Remove wpautop formatting if it's just wrapping the content with a single p tag
	if (substr(trim($excerpt), 0, 3) === '<p>' && substr(trim($excerpt), -4) === '</p>' && substr_count(trim($excerpt), '<p>') === 1) {
		$processed_excerpt = substr(trim($excerpt), 3, -4);
	} else {
		$processed_excerpt = $excerpt;
	}
}
?>
<div <?php echo $anchor; ?> class="beige-card-variant-two-container bg-neutral-2 p-6 rounded-lg px-10 py-10 mb-1 <?php echo esc_attr($class_name); ?>">
	<div class="card-content">
		<?php if ($title) : ?>
			<h3 class="text-lg lg:text-2xl font-normal text-black mb-1 mb-4"><?php echo esc_html($title); ?></h3>
		<?php endif; ?>
		
		<?php if ($processed_excerpt) : ?>
			<p class="text-lg text-black mb-4 my-reset"><?php echo $processed_excerpt; ?></p>
		<?php endif; ?>
		
		<?php if ($button_settings && !empty($button_settings['button_text']) && !empty($button_settings['button_link'])) : ?>
			<a href="<?php echo esc_url(home_url($button_settings['button_link'])); ?>" class="text-lg mt-10 gap-3 border border-solid border-black px-4 py-2 rounded-full inline-flex items-center text-black hover:underline">
			<?php echo esc_html($button_settings['button_text']); ?>
				<svg class="ml-2" width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" fill="currentColor"/>
				</svg>
			</a>
		<?php endif; ?>
	</div>
</div>