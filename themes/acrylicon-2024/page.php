<?php 
	get_header(); 
	the_post();
?>


<main class="max-w-screen-2xl mx-auto px-20 pt-20 lg:pt-44 pb-8">
	<section class="">
		<!--<h1 class="text-7xl font-normal mb-12"><?php the_title(); ?></h1>-->
		<div class="editor">
			<?php the_content();?>
		</div>
	</section>
</main>



<?php 
	get_footer();
?>