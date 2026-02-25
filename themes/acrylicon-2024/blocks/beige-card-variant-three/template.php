<?php
/**
 * Beige Card Variant Three Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'beige-card-variant-three-' . $block['id'];
if (!empty($block['anchor'])) {
	$id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$className = 'beige-card-variant-three';
if (!empty($block['className'])) {
	$className .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$className .= ' align' . $block['align'];
}

// Get the column layout choice
$columns_count = get_field('columns_count');

// Convert to Tailwind classes
$grid_classes = 'grid gap-3 md:gap-6';
switch($columns_count) {
    case 'two-columns':
        $grid_classes .= ' grid-cols-1 md:grid-cols-2';
        break;
    case 'three-columns':
        $grid_classes .= ' grid-cols-1 md:grid-cols-3';
        break;
    default:
        $grid_classes .= ' grid-cols-1';
}
$className .= " {$grid_classes}";
?>

<?php
// Process repeater field
if (have_rows('beige-card-variant-three-repeater')): ?>
	<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
			<?php while (have_rows('beige-card-variant-three-repeater')): the_row();
				$img = get_sub_field('img');
				$subtitle = get_sub_field('subtitle');
				$title = get_sub_field('title');
				$text = get_sub_field('text');
				
				// Get sizing settings
				$sizing_settings = get_sub_field('sizing_settings');
				$title_size = $sizing_settings['title_size'];
				
				// Process text to avoid nested paragraphs
				$processed_text = '';
				if ($text) {
					if (substr(trim($text), 0, 3) === '<p>' && substr(trim($text), -4) === '</p>' && substr_count(trim($text), '<p>') === 1) {
						$processed_text = substr(trim($text), 3, -4);
					} else {
						$processed_text = $text;
					}
				}
				
				// Add title size classes
				$title_class = 'font-normal ';
				$title_class .= ($title_size === 'large') ? 'text-2xl' : 'text-lg';
			?>
				<div class="beige-card bg-acryl-beige-lighter rounded-lg overflow-hidden <?php echo $img ? 'md:grid md:grid-cols-2 md:auto-rows-auto' : 'block'; ?>">
					<?php if ($img): ?>
						<div class="relative md:h-full">
							<?php echo wp_get_attachment_image($img, 'large', false, array('class' => 'object-cover w-full md:absolute md:h-full object-center')); ?>
						</div>
						<div class="py-6 px-6">
							<?php if ($subtitle): ?>
								<h4 class="subtitle text-base font-sohne-mono mb-1 text-dark"><?php echo esc_html($subtitle); ?></h4>
							<?php endif; ?>
							
							<?php if ($title): ?>
								<h3 class="<?php echo esc_attr($title_class); ?> mb-4">
									<?php echo esc_html($title); ?>
								</h3>
							<?php endif; ?>
							
							<?php if ($processed_text): ?>
								<div class="list-style-initial">
									<?php if (strpos($processed_text, '<ul>') !== false): ?>
										<?php echo wp_kses_post($text); ?>
									<?php else: ?>
										<p class="text-base"><?php echo $processed_text; ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php else: ?>
						<div class="px-5 py-5 md:px-8 md:py-8 text-dark">
							<?php if ($subtitle): ?>
								<p class="my-reset subtitle text-base font-sohne-mono mb-1"><?php echo esc_html($subtitle); ?></p>
							<?php endif; ?>
							
							<?php if ($title): ?>
								<h3 class="<?php echo esc_attr($title_class); ?> my-reset">
									<?php echo esc_html($title); ?>
								</h3>
							<?php endif; ?>
							
							<?php if ($processed_text): ?>
								<div class="[&_ul]:list-inside mt-4 md:mt-8">
									<?php if (strpos($processed_text, '<ul>') !== false): ?>
										<?php echo wp_kses_post($text); ?>
									<?php else: ?>
										<p class="text-lg "><?php echo $processed_text; ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
<?php endif; ?>