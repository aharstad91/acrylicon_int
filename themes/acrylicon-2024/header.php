<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-TJ93BLWH');</script>
	<!-- End Google Tag Manager -->

	<!-- Byggfakta Analytics Pro -->
	<script async defer type="text/javascript" src="//stats.docu.info/docu-snippet.js" id="docu-snippet" data-site-id="8" data-domain-id="476"></script>

	<?php wp_head(); ?>	
	<link rel="icon" type="image/svg+xml" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon.svg" />
	<link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon-96x96.png" sizes="96x96" />
	<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/apple-touch-icon.png" />
	<link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/site.webmanifest" />
</head>
<body <?php body_class('font-sohne-buch black text-base scroll-smooth bg-acryl-beige-lightest font-normal'); ?>>

	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TJ93BLWH"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	
<header class="py-4 hidden lg:flex">
	<div class="max-w-max w-full mx-auto px-20">
		<div class="flex items-center justify-between">
			<a href="<?php echo home_url(); ?>" class="flex items-center">
				<img src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-dark.svg" alt="Acrylicon logo">
			</a>
			<div class="flex items-center gap-6">
				<?php
				wp_nav_menu(array(
					'theme_location'  => 'primary-menu',
					'menu_class'      => 'flex items-center gap-6 list-none text-lg font-normal no-underline my-reset',
					'container'       => 'nav',
					'container_class' => 'flex items-center',
				));
				?>
				<?php acrylicon_render_language_switcher( 'header' ); ?>
			</div>
		</div>
	</div>
</header>
<header class="lg:hidden sticky relative top-0 z-50 w-full bg-acryl-beige-lightest" style="height: 76px;">
	<div class="flex items-center justify-between py-4 px-5 py-2">
		<a href="<?php echo home_url(); ?>" class="flex items-center">
			<img class="w-48" src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-dark.svg" alt="Acrylicon logo">
		</a>
		<button 
		id="menuButton"
		type="button"
		aria-controls="menuPanel"
		aria-expanded="false"
		class="relative z-50 flex flex-col items-center justify-center w-8 h-8 space-y-1.5 group h-6 w-6"
		>
			<div class="mm-toggle">
				<span class=""></span>
				<span class=""></span>
				<span class=""></span>
			</div>
		</button>
	</div>
	
	<div id="menuPanel" class="z-40 w-full h-screen bg-acryl-beige-lighter overflow-y-auto opacity-0 invisible">
		<?php wp_nav_menu(array(
			'theme_location'  => 'mobile',
			'menu_class'      => 'flex flex-col w-full',
			'container'       => 'nav',
			'container_class' => 'flex h-full',
		));
		?>
		<?php acrylicon_render_language_switcher( 'mobile' ); ?>
	</div>
</header>