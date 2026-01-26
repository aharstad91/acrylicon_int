<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script>document.createElement('main'); /* IE-Fix */</script>
	<script src="https://unpkg.com/scrollreveal"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
	
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-TJ93BLWH');</script>
	<!-- End Google Tag Manager -->
	
	
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-D2YGZGKMXP"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());
	
	  gtag('config', 'G-D2YGZGKMXP');
	</script>
	
	<!-- Byggfakta Analytics Pro -->
	<script async defer type="text/javascript" src="//stats.docu.info/docu-snippet.js" id="docu-snippet" data-site-id="8" data-domain-id="476"></script>
	<!-- End Byggfakta Analytics Pro -->	


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
	
<header class="py-4 hidden lg:block lg:flex">
	<div class="max-w w-full mx-auto px-4">
		<div class="flex items-center justify-between">
			<a href="<?php echo home_url(); ?>" class="flex items-center hidden lg:flex">
				<img src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-dark.svg" alt="Acrylicon logo">
			</a>
			<?php
			wp_nav_menu(array(
				'theme_location'  => 'primary-menu',
				'menu_class'      => 'flex items-center gap-6 list-none text-lg font-normal no-underline my-reset',
				'container'       => 'nav',
				'container_class' => 'flex items-center hidden lg:flex',
			));
			?>
		</div>
	</div>
</header>
<header class="lg:hidden sticky relative top-0 z-50 w-full bg-acryl-beige-lightest" style="height: 76px;">
	<div class="flex items-center justify-between py-4 px-4 py-2">
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
	</div>
</header>