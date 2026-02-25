<?php get_header(); ?>

<?php
$is_english = ( get_current_blog_id() === 1 );
$heading    = $is_english ? 'Page not found' : 'Siden ble ikke funnet';
$message    = $is_english
	? 'The page you are looking for may have been moved or no longer exists.'
	: 'Siden du leter etter kan ha blitt flyttet eller finnes ikke lenger.';
$btn_text   = $is_english ? 'Go to homepage' : 'Gå til forsiden';
$btn_url    = home_url( '/' );
?>

<main class="max-w-screen-2xl mx-auto px-5 md:px-20 pt-12 md:pt-20 lg:pt-44 pb-20">
	<section class="flex flex-col items-center justify-center text-center py-20 lg:py-32">
		<p class="text-8xl lg:text-9xl font-sohne-buch text-acryl-red leading-none mb-6">404</p>
		<h1 class="text-3xl lg:text-5xl font-sohne-buch text-acryl-dark-blue mb-4"><?php echo esc_html( $heading ); ?></h1>
		<p class="text-lg text-acryl-gray-1 max-w-lg mb-10"><?php echo esc_html( $message ); ?></p>
		<a href="<?php echo esc_url( $btn_url ); ?>"
		   class="inline-block bg-acryl-red text-white px-8 py-3 text-lg hover:bg-acryl-dark-blue transition-colors duration-200">
			<?php echo esc_html( $btn_text ); ?>
		</a>
	</section>
</main>

<?php get_footer(); ?>
