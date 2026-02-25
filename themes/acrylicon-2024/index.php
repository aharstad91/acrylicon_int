<?php 
	get_header(); 
	the_post();
?>


<main class="max-w-screen-2xl mx-auto px-5 md:px-20 pt-12 md:pt-20 lg:pt-44 pb-8">
	<div class="editor">
		<?php the_content();?>
	</div>
</main>


<?php 
	get_footer();
?>