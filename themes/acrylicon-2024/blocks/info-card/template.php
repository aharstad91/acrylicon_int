<?php
/**
 * Info Cards Grid Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'info-cards-grid-' . $block['id'];
if (!empty($block['anchor'])) {
	$id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$className = 'info-cards-grid';
if (!empty($block['className'])) {
	$className .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$className .= ' align' . $block['align'];
}

// Get the column layout choice
$columns_count = get_field('columns_count');

// Convert to Tailwind classes
$grid_classes = 'grid gap-8';
switch($columns_count) {
    case 'single-column':
        $grid_classes .= ' grid-cols-1';
        break;
    case 'two-columns':
        $grid_classes .= ' grid-cols-1 lg:grid-cols-2';
        break;
    case 'three-columns':
        $grid_classes .= ' grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
        break;
    default:
        $grid_classes .= ' grid-cols-1 lg:grid-cols-3';
}
$className .= " {$grid_classes}";

// Get repeater field
if (have_rows('feature_card_repeater')): ?>
	<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?> flex flex-col">
	<?php while (have_rows('feature_card_repeater')): the_row(); 
			$icon = get_sub_field('icon');
			$title = get_sub_field('title');
			$text = get_sub_field('text');
			
			// Hent størrelsesinnstillinger fra gruppen
			$sizing_settings = get_sub_field('sizing_settings');
			$icon_size = $sizing_settings['icon_size'];
			$text_size = $sizing_settings['text_size'];
			
			// Hent knappinnstillinger fra gruppen
			$button_settings = get_sub_field('button_settings');
			$button_text = $button_settings['button_text'];
			$button_link = $button_settings['button_link'];
			
			// Legg til størrelsesklasser
			$icon_class = 'info-card-icon ';
			$icon_class .= ($icon_size === 'large') ? 'w-24 h-24' : 'w-20 h-20';
			
			$text_class = 'info-card-description ';
			$text_class .= ($text_size === 'large') ? 'text-2xl lg:text-3xl' : 'text-lg';
		?>
			<div class="flex flex-col py-8 px-8 bg-acryl-light-blue p-5 rounded-lg">
				<div>
					<div class="icon-wrapper mb-10">
						<?php echo wp_get_attachment_image($icon, 'thumbnail', false, array('class' => $icon_class)); ?>
					</div>
					
					<h3 class="info-card-title mb-4 font-sohne-mono text-base font-light">
						<?php echo esc_html($title); ?>
					</h3>
					
					<div class="<?php echo esc_attr($text_class); ?>">
						<?php echo wp_kses_post($text); ?>
					</div>
				</div>
				<?php if ($button_text && $button_link): ?>
					<a class="flex w-fit px-4 py-2 text-white bg-acryl-dark-blue rounded-full mt-12" 
					href="<?php echo esc_url(home_url($button_link)); ?>">
						<div class="flex gap-3 text-lg">
							<span><?php echo esc_html($button_text); ?></span>
							<!-- <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/gfx/arrow-right.svg" alt="" aria-hidden="true"> -->
						</div>
					</a>
				<?php endif; ?>
			</div>
		<?php endwhile; ?>
	</div>
<?php endif; ?>