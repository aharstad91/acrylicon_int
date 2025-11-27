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
// Load values and handle defaults.
$image = get_field('image');
$title = get_field('title');
$type = get_field('product_type');
$permalink = get_field('product_permalink');

// Block preview
if (!empty($block['data']['preview_image_help'])) {
	echo '<img src="' . $block['data']['preview_image_help'] . '" style="width:100%; height:auto;">';
	return;
}
?>
<div <?php echo $anchor; ?> class="bg-neutral-2 mb-6 lg:flex rounded-lg overflow-hidden <?php echo esc_attr($class_name); ?>">
	<figure class="lg:w-1-3 flex-shrink-0 flex-1-3 h-full ">
		<a href="<?php echo home_url(); ?>/<?php echo esc_html($permalink); ?>">
			<?php echo wp_get_attachment_image($image, 'full', false, array(
				'class' => 'object-cover h-full w-full h-80'
			)); ?>
		</a>
	</figure>
	<div class="w-full flex flex-col justify-center py-8">
		<header class="flex justify-between items-center mb-4 lg:mb-10 px-6 lg:px-20">
			<?php if ($title) : ?>
				<?php echo get_component('title', array(
					'text' => $title,
					'variant' => 'medium',
					'level' => 1,
					'permalink' => home_url($permalink)
				)); ?>
			<?php endif; ?>
			<!--<?php if ($type) :
				$field = get_field_object('product_type');
				$type_label = $field['choices'][$type];
			?>
			<div class="text-sm bg-neutral-3 border rounded-3xl py-1 px-4 border-solid border-black ">
				<?php echo esc_html($type_label); ?>
			</div>
			<?php endif; ?>-->
		</header>
		<div class="px-6 lg:px-20">
			<?php if (have_rows('product_card_meta')) : ?>
				<ul class="items-start list-none grid lg:grid-cols-2 col-gap-6 row-gap-2 p-0 m-0 ">
					<?php while (have_rows('product_card_meta')) : the_row(); 
						$icon = get_sub_field('icon');
						$text = get_sub_field('text');
					?>
						<li class="flex gap-3 items-start font-sohne-mono">
							<?php if ($icon) : ?>
								<?php echo wp_get_attachment_image($icon, 'full', false, array(
									'width' => '32',
									'height' => '32',
									'class' => 'w-8 h-8'
								)); ?>
							<?php endif; ?>
							<?php if ($text) : ?>
								<span class="text-base text-dark"><?php echo esc_html($text); ?></span>
							<?php endif; ?>
						</li>
					<?php endwhile; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>