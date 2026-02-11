<?php
/**
 * Table Variant One Block Template.
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
$class_name = 'table-variant-one';
if (!empty($block['className'])) {
	$class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
	$class_name .= ' align' . $block['align'];
}
?>
<div <?php echo $anchor; ?> class="<?php echo esc_attr($class_name); ?>">
	<ul class="font-sohne-mono list-none">
		<?php 
		if (have_rows('download_table_repeater')): 
			while (have_rows('download_table_repeater')) : the_row();
				$title = get_sub_field('title');
				$detail = get_sub_field('detail');
				$is_red = get_sub_field('red_color');
				
				// Set row class based on red_color field
				$row_class = $is_red ? 'row-red' : '';
				$text_class = $is_red ? 'text-red' : 'text-dark';
				// Set detail text class based on red_color field
				$detail_class = $is_red ? '' : 'text-acryl-gray-1';
		?>
			<li class="table-row <?php echo esc_attr($row_class); ?> border-t border-solid border-gray-200">
				<dl class="grid grid-cols-2 py-4 <?php echo esc_attr($text_class); ?>">
					<dt class="table-title"><?php echo esc_html($title); ?></dt>
					<dd class="table-detail <?php echo esc_attr($detail_class); ?>"><?php echo esc_html($detail); ?></dd>
				</dl>
			</li>
		<?php 
			endwhile;
		endif; 
		?>
	</ul>
</div>