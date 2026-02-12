<?php
/**
 * Theme Functions
 *
 * @package Acrylicon2024
 */

// Theme Setup
function theme_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('align-wide');
	add_editor_style('editor-style.css');
	add_post_type_support('page', 'excerpt');
}
add_action('after_setup_theme', 'theme_setup');

// Register Menus
function register_theme_menus() {
	register_nav_menus(array(
		'primary-menu' => __('Hovedmeny'),
		'footer-one' => __('Bunn - Store bokstaver'),
		'footer-two' => __('Bunn - Små bokstaver til venstre'),
		'footer-three' => __('Bunn - Midten'),
		'footer-four' => __('Bunn - Høyre'),
		'mobile' => __('Mobile'),
	));
}
add_action('init', 'register_theme_menus');

// Enqueue Scripts and Styles
function theme_enqueue_scripts() {
	wp_enqueue_style('style', get_stylesheet_uri());
	
	wp_enqueue_style('fonts', get_template_directory_uri() . '/assets/fonts/fonts.css', array(), '1.0.0');
	//wp_enqueue_style('editor', get_template_directory_uri() . '/assets/css/editor.css', array(), '1.0.0');
	wp_enqueue_style('gravity', get_template_directory_uri() . '/assets/css/gravity.css', array(), '1.0.0');
	
	//wp_enqueue_style('title-block', get_template_directory_uri() . '/assets/css/title-block.css', array(), '1.0.0');

	// Tailwind CSS - replaces utility.css, utility-md.css, and utility-lg.css
	wp_enqueue_style('tailwind', get_template_directory_uri() . '/assets/css/tailwind.css', array(), filemtime(get_template_directory() . '/assets/css/tailwind.css'));

	// Swiper (was hardcoded in header.php and footer.php)
	wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
	wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);

	wp_enqueue_script('jquery');
	wp_enqueue_script('scrollreveal', get_template_directory_uri() . '/assets/scripts/scrollreveal.min.js', array(), '1.0.0', true);
	wp_enqueue_script('scrollock', get_template_directory_uri() . '/assets/scripts/bodyScrollLock.js', array(), '1.0.0', true);
	wp_enqueue_script('headroom', get_template_directory_uri() . '/assets/scripts/headroom.js', array(), '1.0.0', true);
	wp_enqueue_script('scripts', get_template_directory_uri() . '/assets/scripts/scripts.js', array('jquery', 'scrollreveal'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');


function enqueue_custom_block_editor_assets() {
	wp_enqueue_script(
		'block-panels',
		get_template_directory_uri() . '/assets/scripts/block-panels.js',
		array(
			'wp-blocks',
			'wp-dom-ready',
			'wp-edit-post',
			'wp-i18n',
			'wp-element',
			'wp-compose',
			'wp-components',
			'wp-block-editor'
		),
		null,
		true
	);
}
add_action('enqueue_block_editor_assets', 'enqueue_custom_block_editor_assets');

function enqueue_gutenberg_admin_styles() {
	wp_enqueue_style(
		'gutenberg-admin-styles',
		get_template_directory_uri() . '/assets/css/gutenberg-admin.css',
		array(),
		filemtime(get_template_directory() . '/assets/css/gutenberg-admin.css')
	);
	wp_enqueue_style(
		'editor-styles',
		get_template_directory_uri() . '/assets/css/editor.css',
		array(),
		filemtime(get_template_directory() . '/assets/css/editor.css')
	);

	// Legacy utility files removed - now using Tailwind CSS
	// wp_enqueue_style('utility', get_template_directory_uri() . '/assets/css/utility.css', array(), '1.0.0');
	// wp_enqueue_style('utility-md', get_template_directory_uri() . '/assets/css/utility-md.css', array(), '1.0.0');
	// wp_enqueue_style('utility-lg', get_template_directory_uri() . '/assets/css/utility-lg.css', array(), '1.0.0');

	wp_enqueue_style(
		'gravity',
		get_template_directory_uri() . '/assets/css/gravity.css',
		array(),
		'1.0.0'
	);

	// Tailwind CSS for block editor - replaces utility files
	wp_enqueue_style(
		'tailwind-editor',
		get_template_directory_uri() . '/assets/css/tailwind.css',
		array(),
		filemtime(get_template_directory() . '/assets/css/tailwind.css')
	);
}
add_action('enqueue_block_editor_assets', 'enqueue_gutenberg_admin_styles');

function enqueue_gutenberg_styles() {

}
add_action('enqueue_block_editor_assets', 'enqueue_gutenberg_styles');


// Custom Post Types and Taxonomies

/**
 * Get CPT/taxonomy rewrite slugs based on current blog.
 * Blog 1 (international) uses English slugs, blog 3 (Norway) uses Norwegian.
 */
function acrylicon_get_cpt_slugs() {
	$is_english = ( get_current_blog_id() === 1 );

	return [
		'industrier'          => $is_english ? 'industries'        : 'industrier',
		'kontor'              => $is_english ? 'offices'           : 'kontor',
		'produkter'           => $is_english ? 'products'          : 'produkter',
		'bruksomrader'        => $is_english ? 'applications'      : 'bruksomrader',
		'godegrunner'         => $is_english ? 'good-reasons'      : 'gode-grunner',
		'levetidskostnader'   => $is_english ? 'lifecycle-costs'   : 'levetids-kostnader',
		'baerekreaftig'       => $is_english ? 'sustainability'    : 'baerekraft',
		'referanser'          => $is_english ? 'references'        : 'referanser',
		'tax_kategorier'      => $is_english ? 'reference-category' : 'referanse-kategori',
		'tax_kontor'          => $is_english ? 'reference-office'   : 'referanse-kontor',
		'tax_produkter'       => $is_english ? 'reference-products' : 'referanse-produkter',
		'tax_type'            => $is_english ? 'reference-type'     : 'referanser-type',
	];
}

function register_custom_post_types_and_taxonomies() {
	$slugs = acrylicon_get_cpt_slugs();

	$args = array(
		'public'        => true,
		'has_archive'   => true,
		'hierarchical'  => true,
		'show_in_rest'  => true,
		'supports'      => array('title', 'editor', 'thumbnail', 'revisions', 'excerpt'),
	);
	$args_nopage = array(
		'public'		=> true,
		'has_archive'	=> false,
		'hierarchical'	=> false,
		'show_in_rest' => true,
		'supports'		=> array('title', 'editor', 'thumbnail', 'revisions', 'excerpt'),
	);

	register_post_type('industrier', wp_parse_args(array(
		'label'     => 'Industrier',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['industrier'], 'with_front' => false),
	), $args));

	register_post_type('kontor', wp_parse_args(array(
		'label'     => 'Kontor',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['kontor'], 'with_front' => false),
	), $args_nopage));

	register_post_type('produkter', wp_parse_args(array(
		'label'     => 'Produkter',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['produkter'], 'with_front' => false),
	), $args_nopage));

	register_post_type('bruksomrader', wp_parse_args(array(
		'label'     => 'Bruksområder',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['bruksomrader'], 'with_front' => false),
	), $args_nopage));

	register_post_type('godegrunner', wp_parse_args(array(
		'label'     => 'Gode Grunner',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['godegrunner'], 'with_front' => false),
	), $args_nopage));

	register_post_type('levetidskostnader', wp_parse_args(array(
		'label'     => 'Levetidskost',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['levetidskostnader'], 'with_front' => false),
	), $args_nopage));

	register_post_type('baerekreaftig', wp_parse_args(array(
		'label'     => 'Bærekraftig',
		'menu_icon' => 'dashicons-lightbulb',
		'rewrite'   => array('slug' => $slugs['baerekreaftig'], 'with_front' => false),
	), $args_nopage));

	register_taxonomy('referanser-type', 'referanser', array(
		'label'             => 'Referansetype',
		'hierarchical'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => $slugs['tax_type']),
	));
	register_taxonomy('referanser-kategorier', 'referanser', array(
		'label'             => 'Produktområder',
		'hierarchical'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => $slugs['tax_kategorier']),
	));
	register_taxonomy('referanser-kontor', 'referanser', array(
		'label'             => 'Kontor',
		'hierarchical'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => $slugs['tax_kontor']),
	));
	register_taxonomy('referanser-produkter', 'referanser', array(
		'label'             => 'Produkter',
		'hierarchical'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => $slugs['tax_produkter']),
	));
}
add_action('init', 'register_custom_post_types_and_taxonomies');



function register_custom_post_type_with_template() {
	$slugs = acrylicon_get_cpt_slugs();
	$args = array(
		'public' => true,
		'label'  => 'Referanser',
		'has_archive'	=> false,
		'show_in_rest' => true,
		'supports' => array('editor', 'title', 'custom-fields', 'thumbnail'),
		'rewrite' => array('slug' => $slugs['referanser'], 'with_front' => false),
		'template' => array(
			array('core/heading', array(
				'level' => 2,
				'content' => 'Introduction',
			)),
			array('core/paragraph', array(
				'placeholder' => 'Write your introduction here...',
			)),
			array('core/block', array(
				'ref' => 4419 // Two Column: Title + Excerpt
			)),
			array('core/heading', array(
				'level' => 2,
				'content' => 'Main Content',
			)),
			array('acf/info-card', array(
				'data' => array(
					'icon' => get_template_directory_uri() . '/assets/gfx/fallback.svg',
					'title' => 'Default Title',
					'text' => 'This is the default text for the info card.',
					'icon_size' => 'medium',
					'text_size' => 'normal'
				)
			)), // ACF block
			array('core/paragraph', array(
				'placeholder' => 'Write your main content here...',
			)),
			array('core/heading', array(
				'level' => 2,
				'content' => 'Conclusion',
			)),
			array('core/paragraph', array(
				'placeholder' => 'Write your conclusion here...',
			)),
		),
	);
	register_post_type('referanser', $args);
}
add_action('init', 'register_custom_post_type_with_template');





// ACF Blocks
add_action('init', 'register_acf_blocks');
function register_acf_blocks() {
	$blocks = [
		'feature-card'          => '/blocks/feature-card/block.json',
		'beige-card-variant-two' => '/blocks/beige-card-variant-two/block.json',
		'beige-card-variant-three' => '/blocks/beige-card-variant-three/block.json',
		'blue-card-variant-two' => '/blocks/blue-card-variant-two/block.json',
		'info-card'    		    => '/blocks/info-card/block.json',
		'office-staff-card'     => '/blocks/office-staff-card/block.json',
		'global-reference'      => '/blocks/global-reference/block.json',
		'contact-form'          => '/blocks/contact-form/block.json',
		'product-card'          => '/blocks/product-card/block.json',
		'showreel-reference-bruksomrader' => '/blocks/showreel-reference-bruksomrader/block.json',
		'showreel-reference-produkter'    => '/blocks/showreel-reference-produkter/block.json',
		'showreel-reference-kontor'       => '/blocks/showreel-reference-kontor/block.json',
		'technical-info-table'            => '/blocks/technical-info-table/block.json',
		'download-list'                   => '/blocks/download-list/block.json',
		'download-table'                  => '/blocks/download-table/block.json',
		'split-image-text-banner'         => '/blocks/split-image-text-banner/block.json',
		'split-image-text-card'           => '/blocks/split-image-text-card/block.json',
		'global-bruksomrader'             => '/blocks/global-bruksomrader/block.json',
		'office-contact-card'             => '/blocks/office-contact-card/block.json',
		'slider-block'             		  => '/blocks/slider-block/block.json',
		'specific-references-loop'        => '/blocks/specific-references-loop/block.json',
		'image-split'        			  => '/blocks/image-split/block.json',
		'table-variant-one'        		  => '/blocks/table-variant-one/block.json',
		'header-with-red-back-link'       => '/blocks/header-with-red-back-link/block.json',
		'text-scroller'       			  => '/blocks/text-scroller/block.json',
		'section-title-with-red-button-right'   => '/blocks/section-title-with-red-button-right/block.json',




	];

	foreach ($blocks as $block_name => $json_path) {
		register_block_type(__DIR__ . $json_path);
	}
}

// Optimize video blocks: add preload="metadata" to reduce LCP impact
function optimize_video_block($block_content, $block) {
	if ($block['blockName'] === 'core/video') {
		$block_content = str_replace('<video ', '<video preload="metadata" ', $block_content);
	}
	return $block_content;
}
add_filter('render_block', 'optimize_video_block', 10, 2);

// Defer non-critical CSS to reduce render-blocking (PageSpeed: saves ~1,160ms)
function defer_non_critical_css($html, $handle, $href, $media) {
	$defer_handles = array('gravity', 'custom-block-styles', 'swiper');
	if (in_array($handle, $defer_handles) && !is_admin()) {
		return sprintf(
			'<link rel="stylesheet" id="%s-css" href="%s" media="print" onload="this.media=\'all\'">' . "\n" .
			'<noscript><link rel="stylesheet" href="%s" media="%s"></noscript>' . "\n",
			esc_attr($handle),
			esc_url($href),
			esc_url($href),
			esc_attr($media)
		);
	}
	return $html;
}
add_filter('style_loader_tag', 'defer_non_critical_css', 10, 4);

// Add defer to jQuery to stop it from render-blocking (PageSpeed: saves ~680ms)
function add_defer_to_scripts($tag, $handle, $src) {
	$defer_handles = array('jquery-core', 'jquery-migrate');
	if (in_array($handle, $defer_handles) && !is_admin()) {
		return str_replace(' src=', ' defer src=', $tag);
	}
	return $tag;
}
add_filter('script_loader_tag', 'add_defer_to_scripts', 10, 3);

// SVG Support
function cc_mime_types($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

// Remove <p> tags from images
function filter_ptags_on_images($content) {
	return preg_replace('/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content);
}
add_filter('the_content', 'filter_ptags_on_images');

// Add target="_blank" to edit post link
add_filter('edit_post_link', function($link, $post_id, $text) {
	if (false === strpos($link, 'target=')) {
		$link = str_replace('<a ', '<a target="_blank" ', $link);
	}
	return $link;
}, 10, 3);






require_once get_template_directory() . '/assets/components/register.php';
require_once get_template_directory() . '/assets/components/titles.php';
require_once get_template_directory() . '/inc/language-switcher.php';




function enqueue_custom_block_assets() {
	// Enqueue the JavaScript for the editor
	wp_enqueue_script(
		'custom-block-styles-js',
		get_template_directory_uri() . '/custom-block-styles.js',
		array('wp-blocks', 'wp-dom-ready', 'wp-edit-post'),
		filemtime(get_template_directory() . '/custom-block-styles.js')
	);
}

// Hook for editor assets
add_action('enqueue_block_editor_assets', 'enqueue_custom_block_assets');

function svg_icon($filename, $options = []) {
	$defaults = [
		'width' => null,
		'height' => null,
		'class' => '',
		'fill' => null,
		'stroke' => null,
		'stroke-width' => null,
		'viewBox' => null,
	];
	
	$options = wp_parse_args($options, $defaults);
	
	$svg_path = get_template_directory() . '/assets/gfx/' . $filename . '.svg';
	
	if (!file_exists($svg_path)) {
		return '';
	}
	
	$svg = file_get_contents($svg_path);
	
	// Sanitization — strip dangerous elements and attributes
	$svg = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $svg);
	$svg = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $svg);
	$svg = preg_replace('#<foreignObject(.*?)>(.*?)</foreignObject>#is', '', $svg);
	$svg = preg_replace('#<use[^>]*/?>#is', '', $svg);
	$svg = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $svg);
	$svg = preg_replace('#\s+on\w+\s*=\s*\'[^\']*\'#i', '', $svg);
	$svg = preg_replace('#data:[^,]*,#is', '', $svg);
	
	// Add size attributes to SVG tag
	$attrs = [];
	if ($options['width']) {
		$attrs[] = 'width="' . esc_attr($options['width']) . '"';
	}
	if ($options['height']) {
		$attrs[] = 'height="' . esc_attr($options['height']) . '"';
	}
	if ($options['class']) {
		$attrs[] = 'class="' . esc_attr($options['class']) . '"';
	}
	if ($options['viewBox']) {
		$attrs[] = 'viewBox="' . esc_attr($options['viewBox']) . '"';
	}
	
	if (!empty($attrs)) {
		$svg = preg_replace('/<svg /', '<svg ' . implode(' ', $attrs) . ' ', $svg);
	}
	
	// Modify path attributes
	$path_attrs = [];
	if ($options['fill']) {
		$path_attrs[] = 'fill="' . esc_attr($options['fill']) . '"';
	}
	if ($options['stroke']) {
		$path_attrs[] = 'stroke="' . esc_attr($options['stroke']) . '"';
	}
	if ($options['stroke-width']) {
		$path_attrs[] = 'stroke-width="' . esc_attr($options['stroke-width']) . '"';
	}
	
	if (!empty($path_attrs)) {
		// Add attributes to paths
		$svg = preg_replace('/<path /', '<path ' . implode(' ', $path_attrs) . ' ', $svg);
		// Also target any other SVG elements that might need the same attributes
		$svg = preg_replace('/<circle /', '<circle ' . implode(' ', $path_attrs) . ' ', $svg);
		$svg = preg_replace('/<rect /', '<rect ' . implode(' ', $path_attrs) . ' ', $svg);
		$svg = preg_replace('/<line /', '<line ' . implode(' ', $path_attrs) . ' ', $svg);
		$svg = preg_replace('/<polygon /', '<polygon ' . implode(' ', $path_attrs) . ' ', $svg);
	}
	
	return $svg;
}


// In your theme's functions.php
function enqueue_block_styles() {
	wp_enqueue_style(
		'custom-block-styles',
		get_template_directory_uri() . '/assets/css/block-panels.css',
		array(),
		'1.0'
	);
}
add_action('wp_enqueue_scripts', 'enqueue_block_styles');
add_action('enqueue_block_editor_assets', 'enqueue_block_styles'); // This loads styles in editor too


function disable_heading_typography_support() {
	wp_enqueue_script(
		'disable-heading-typography',
		get_template_directory_uri() . '/assets/scripts/disable-typography.js',
		array('wp-blocks', 'wp-dom-ready', 'wp-edit-post')
	);
}
add_action('enqueue_block_editor_assets', 'disable_heading_typography_support');


function remove_block_font_size_styles() {
	// Legg til global for å få tilgang til WordPress-versjonen
	global $wp_version;
	
	// Remove font size styles 
	wp_deregister_style('wp-block-library');
	// Re-register without font-size styles
	wp_register_style(
		'wp-block-library',
		includes_url('css/dist/block-library/style.min.css'),
		array(),
		$wp_version  // Merk dollartegnet her
	);

	// Remove editor font sizes
	add_theme_support('editor-font-sizes', array());
	
	// Disable custom font sizes
	add_theme_support('disable-custom-font-sizes');
}
add_action('init', 'remove_block_font_size_styles');

// Remove the inline styles for font sizes
function remove_font_size_inline_styles($block_content, $block) {
	if ($block['blockName'] === 'core/heading') {
		// Remove has-*-font-size classes and their inline styles
		$block_content = preg_replace('/\shas-(small|medium|large|x-large|xx-large)-font-size/', '', $block_content);
		$block_content = preg_replace('/\sstyle="font-size:[^"]*"/', '', $block_content);
	}
	return $block_content;
}
add_filter('render_block', 'remove_font_size_inline_styles', 10, 2);

function remove_font_size_support() {
	remove_theme_support('editor-font-sizes');
	remove_theme_support('custom-font-sizes');
}
add_action('after_setup_theme', 'remove_font_size_support');

function remove_font_size_attributes($block_content, $block) {
	if ($block['blockName'] === 'core/heading') {
		$block_content = preg_replace('/\shas-[^-]*-font-size/', '', $block_content);
	}
	return $block_content;
}
add_filter('render_block', 'remove_font_size_attributes', 10, 2);

remove_theme_support( 'core-block-patterns' );

/**
 * 301 Redirect old Norwegian slugs to English on blog 1 (international).
 * Only redirects top-level page/archive slugs, not individual posts.
 */
function acrylicon_redirect_old_norwegian_slugs() {
	if ( get_current_blog_id() !== 1 || is_admin() ) {
		return;
	}

	$redirects = [
		'referanser'         => 'references',
		'produkter'          => 'products',
		'industrier'         => 'industries',
		'bruksomrader'       => 'applications',
		'kontor'             => 'offices',
		'gode-grunner'       => 'good-reasons',
		'levetids-kostnader' => 'lifecycle-costs',
		'baerekraft'         => 'sustainability',
		'fordeler'           => 'benefits',
		'om-acrylicon'       => 'about-acrylicon',
		'nedlastinger'       => 'downloads',
		'informasjonskapsler' => 'cookie-policy',
		'contact-us'          => 'locations',
		'kontakt-oss'         => 'locations',
	];

	$request_uri = $_SERVER['REQUEST_URI'];
	$path = trim( wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );

	// Strip local dev base path (e.g. /acrylicon/)
	$site_path = wp_parse_url( home_url(), PHP_URL_PATH );
	if ( $site_path && $site_path !== '/' ) {
		$base = trim( $site_path, '/' ) . '/';
		if ( strpos( $path, $base ) === 0 ) {
			$path = substr( $path, strlen( $base ) );
		}
	}

	// Get the first path segment
	$segments = explode( '/', $path );
	$first    = $segments[0] ?? '';

	if ( isset( $redirects[ $first ] ) ) {
		$segments[0] = $redirects[ $first ];
		$new_path    = implode( '/', $segments );
		wp_redirect( home_url( '/' . $new_path . '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'acrylicon_redirect_old_norwegian_slugs' );
