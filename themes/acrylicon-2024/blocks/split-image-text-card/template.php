<?php
/**
 * Info Card Block Template.
 *
 * @package YourTheme
 * @version 1.0.0
 * 
 * @param   array  $block      The block settings and attributes.
 * @param   string $content    The block inner HTML (empty).
 * @param   bool   $is_preview True during backend preview render.
 * @param   int    $post_id    The post ID the block is rendering content against.
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle block ID
 */
$block_id = 'info-card-' . $block['id'];
if (!empty($block['anchor'])) {
	$block_id = $block['anchor'];
}

/**
 * Handle block classes
 */
$class_name = '';
if (!empty($block['className'])) {
	$class_name .= ' ' . trim($block['className']);
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}

/**
 * Get ACF fields
 */
$image = get_field('img');
$title = get_field('title');
$text = get_field('text');
$link_text = get_field('link_text');
$link = get_field('link');

/**
 * Set content classes
 */
$icon_class = 'lg:w-1/3 flex-shrink-0 flex-1-3 h-72';
$title_class = 'text-dark mb-4 font-sohne-mono text-base font-light';
$text_class = 'text-3xl';

// Preview message for admin
if ($is_preview) {
	printf(
		'<div class="info-card-preview">%s</div>',
		esc_html__('Preview Mode: Info Card Block', 'your-theme-text-domain')
	);
}
?>
<div id="<?php echo esc_attr($block_id); ?>" class="lg:flex mb-10 <?php echo esc_attr($class_name); ?>">
	<?php if ($image) : ?>
	<div class="<?php echo esc_attr($icon_class); ?>">
		<?php 
		echo wp_get_attachment_image(
			$image,
			'large',
			false,
			array('class' => 'w-full h-full object-cover rounded-xl')
		); 
		?>
	</div>
	<?php endif; ?>

	<div class="lg:px-16 py-6 flex flex-col justify-center">
		<?php if ($title) : ?>
		<h3 class="<?php echo esc_attr($title_class); ?>">
			<?php echo esc_html($title); ?>
		</h3>
		<?php endif; ?>

		<?php if ($text) : ?>
		<p class="<?php echo esc_attr($text_class); ?> text-dark mt-reset mb-8">
			<?php echo wp_kses_post($text); ?>
		</p>
		<?php endif; ?>

		<?php if ($link && $link_text) : ?>
		<a class="w-fit px-8 py-3 text-white bg-acryl-red rounded-full" 
		   href="<?php echo esc_url(home_url($link)); ?>"
		   aria-label="<?php echo esc_attr(sprintf(__('%s - Read more', 'your-theme-text-domain'), $title)); ?>">
			<div class="flex gap-3 text-lg">
				<span><?php echo esc_html($link_text); ?></span>
				<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/gfx/arrow-right.svg" 
					 alt=""
					 aria-hidden="true">
			</div>
		</a>
		<?php endif; ?>
	</div>
</div>