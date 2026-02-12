<?php
get_header();
the_post();

$terms = get_the_terms(get_the_ID(), 'referanser-type');
if ($terms && !is_wp_error($terms)) {
	$term_slug = $terms[0]->slug;
	
	if ($term_slug === 'dybdecase') {
		get_template_part('single-referanser-dybdecase');
	} else if ($term_slug === 'new-reference' || $term_slug === 'case-study') {
		get_template_part('single-referanser-referanse');
	} else {
		get_template_part('single-referanser-old');
	}
}

get_footer();
?>