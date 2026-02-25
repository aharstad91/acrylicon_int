<?php 
	get_header(); 
	the_post();
?>


<main class="max-w-screen-2xl mx-auto px-5 md:px-20 mt-44 pb-8">
	<section class="">
		<!--<h1 class="lg:text-7xl md:text-5xl text-3xl mb-12"><?php the_title(); ?></h1>-->
		<div class="editor">
			<?php the_content();?>
		</div>
	</section>
</main>



<?php 
	get_footer();
?>