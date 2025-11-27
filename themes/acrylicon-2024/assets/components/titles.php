<?php 
register_component('title', function($args) {
	$variants = [
		'small' => [
			'class' => 'text-lg lg:text-3xl mb-0',
			'level' => 3,
		],
		'medium' => [
			'class' => 'text-2xl lg:text-3xl mb-0',
			'level' => 2,
		],
		'large' => [
			'class' => 'text-2xl lg:text-5xl mb-0',
			'level' => 1,
		],
	];
	$defaults = array(
		'text' => 'Default Title',
		'class' => '',
		'level' => 2,
		'id' => '',
		'php' => true,
		'variant' => '',
		'permalink' => '', // Added permalink parameter
	);
	$args = wp_parse_args($args, $defaults);    
	
	// Apply variant if specified
	if (!empty($args['variant']) && isset($variants[$args['variant']])) {
		$variant = $variants[$args['variant']];
		$args['class'] = !empty($args['class']) ? $args['class'] . ' ' . $variant['class'] : $variant['class'];
		$args['level'] = $variant['level'];
	}
	// Ensure the heading level is between 1 and 4
	$level = max(1, min(4, intval($args['level'])));
	
	$tag = "h{$level}";
	
	$id_attr = !empty($args['id']) ? ' id="' . esc_attr($args['id']) . '"' : '';
	
	// Process the text content
	if ($args['php']) {
		ob_start();
		eval('?>' . $args['text']);
		$content = ob_get_clean();
	} else {
		$content = esc_html($args['text']);
	}

	// Wrap content in link if permalink is provided
	if (!empty($args['permalink'])) {
		$content = sprintf('<a href="%s" class="hover:text-gray-600">%s</a>', 
			esc_url($args['permalink']),
			$content
		);
	}
	
	return sprintf('<%1$s%2$s class="%3$s">%4$s</%1$s>', 
		$tag,
		$id_attr,
		esc_attr($args['class']), 
		$content
	);
});
?>