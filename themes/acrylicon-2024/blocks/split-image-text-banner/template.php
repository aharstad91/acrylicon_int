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
$class_name = 'bg-acryl-dark-blue text-white rounded-lg overflow-hidden';
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
$img_size = get_field('img_size') ?: 'default';
$text_size = get_field('text_size') ?: 'default';

/**
 * Set content classes
 */
$icon_class = 'info-card-icon ';
$icon_class .= ($img_size === 'small') ? 'lg:w-1/5' : 'lg:w-1/3';

$title_class = 'info-card-title mb-2 font-sohne-mono text-base font-light';

$text_class = 'info-card-description ';
$text_class .= ($text_size === 'large') ? 'text-3xl lg:text-5xl' : 'text-2xl lg:text-3xl';

// Add size classes to main block
$class_name .= " icon-{$img_size} text-{$text_size}"; ?>

<div id="<?php echo esc_attr($block_id); ?>" class="info-card-container">
	<div class="<?php echo esc_attr($class_name); ?> lg:flex h-full">
		<?php if ($image) : ?>
		<div class="<?php echo esc_attr($icon_class); ?>">
			<?php if ($link) :
				// Image-only link: guarantee a discernible name (image alt + title/link_text are often empty).
				// Fall back to a readable label derived from the link slug, e.g. "/locations/" -> "Locations".
				$image_link_label = $title ?: $link_text;
				if (!$image_link_label) {
					$path_parts = array_filter(explode('/', (string) wp_parse_url($link, PHP_URL_PATH)));
					$last_slug  = $path_parts ? end($path_parts) : '';
					$image_link_label = $last_slug
						? ucwords(str_replace(array('-', '_'), ' ', $last_slug))
						: get_bloginfo('name');
				}
			?>
			<a href="<?php echo esc_url(home_url($link)); ?>" class="block h-full" aria-label="<?php echo esc_attr($image_link_label); ?>">
			<?php endif; ?>
			
			<?php 
			echo wp_get_attachment_image(
				$image,
				'large',
				false,
				array('class' => 'w-full h-full object-cover')
			); 
			?>

			<?php if ($link) : ?>
			</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="px-6 lg:px-16 <?php echo ($text_size === 'large') ? 'py-10 lg:py-24' : 'py-8'; ?> flex flex-col justify-center flex-grow max-w-3xl">
			<?php if ($title) : ?>
			<h3 class="<?php echo esc_attr($title_class); ?>">
				<?php echo esc_html($title); ?>
			</h3>
			<?php endif; ?>

			<?php if ($text) : ?>
			<div class="<?php echo esc_attr($text_class); ?> mt-reset mb-8">
				<?php echo wp_kses_post($text); ?>
			</div>
			<?php endif; ?>

			<?php if ($link && $link_text) : ?>
			<a class="w-fit px-6 py-3 border border-solid border-acryl-beige-light rounded-full hover-opacity-80" 
		    href="<?php echo esc_url(home_url($link)); ?>"
			   aria-label="<?php echo esc_attr(sprintf(__('%s - Read more', 'your-theme-text-domain'), $title)); ?>">
				<div class="flex gap-3 text-lg items-center">
					<span><?php echo esc_html($link_text); ?></span>
					<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/gfx/arrow-right.svg" 
						 alt=""
						 class="w-6"
						 aria-hidden="true">
				</div>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>

