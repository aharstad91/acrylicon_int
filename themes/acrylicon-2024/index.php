<?php 
	get_header(); 
	the_post();
?>


<main class="max-w-screen-2xl mx-auto px-4 pt-20 lg:pt-44 pb-8">
	<section class="content">
		<?php the_content();?>
	</section>
</main>


<?php 
	get_footer();
?>