<?php
/**
 * Staff Card Block Template.
 *
 * @param array $block The block settings and attributes.
 * @param string $content The block inner HTML (empty).
 * @param bool $is_preview True during backend preview render.
 * @param int $post_id The post ID the block is rendering content against.
 * @param array $context The context provided to the block by the post or its parent block.
 */

// Support custom "anchor" values.
$anchor = '';
if (!empty($block['anchor'])) {
	$anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

// Create class attribute allowing for custom "className", "align" and block styles
$classes = ['staff-card'];

if (!empty($block['className'])) {
	$classes[] = $block['className'];
}

if (!empty($block['align'])) {
	$classes[] = 'align' . $block['align'];
}

// Add background color class
$background_color = !empty($block['backgroundColor']) ? 'has-' . $block['backgroundColor'] . '-background-color' : '';
if ($background_color) {
	$classes[] = $background_color;
}

// Add text color class
$text_color = !empty($block['textColor']) ? 'has-' . $block['textColor'] . '-color' : '';
if ($text_color) {
	$classes[] = $text_color;
}

// Block styles (margin and padding)
$styles = '';
if (!empty($block['style'])) {
	if (!empty($block['style']['spacing']['margin'])) {
		$styles .= sprintf('margin: %s;', $block['style']['spacing']['margin']);
	}
	if (!empty($block['style']['spacing']['padding'])) {
		$styles .= sprintf('padding: %s;', $block['style']['spacing']['padding']);
	}
}

$wrapper_attributes = $anchor;
$wrapper_attributes .= 'class="' . esc_attr(implode(' ', $classes)) . '" ';
if ($styles) {
	$wrapper_attributes .= 'style="' . esc_attr($styles) . '"';
}
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="staff-card-inner flex flex-col overflow-hidden max-w-sm">
		<div class="bg-neutral-2 pt-12 mb-4 rounded-lg">
			<?php 
			$image_id = get_field('image');
			if($image_id): ?>
				<?php echo wp_get_attachment_image(
					$image_id,
					'large',
					false,
					array('class' => 'block h-72 w-full object-cover object-center my-0')
				); ?>
			<?php else: ?>
				<div class="block h-72 w-full bg-neutral-100 flex items-center justify-center my-0">
					<svg class="w-24 h-24 text-neutral-300" fill="currentColor" viewBox="0 0 24 24">
						<path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8c0 2.208-1.79 4-3.998 4-2.208 0-3.998-1.792-3.998-4s1.79-4 3.998-4c2.208 0 3.998 1.792 3.998 4z"/>
					</svg>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="p-6">
			<header class="mb-4 text-black">
			<?php if($name = get_field('name')): ?>
					<h3 class="text-lg font-normal my-0">
						<?php echo esc_html($name); ?>
					</h3>
				<?php endif; ?>
	
				<?php if($title = get_field('title')): ?>
					<div class="text-lg">
						<?php echo esc_html($title); ?>
					</div>
				<?php endif; ?>
			</header>

			<div class="font-sohne-mono text-gray-3">
				<?php if($tel = get_field('tel')): ?>
					<div class="flex items-center">
						<a href="tel:+47<?php echo str_replace(' ', '', esc_attr($tel)); ?>">
							+47 <?php echo esc_html($tel); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php if($email = get_field('email')): ?>
					<div class="flex items-center">
						<a href="mailto:<?php echo esc_attr($email); ?>">
							<?php echo esc_html($email); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
