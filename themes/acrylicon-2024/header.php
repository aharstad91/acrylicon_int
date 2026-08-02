<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#253761">

	<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
	<link rel="preconnect" href="https://www.googletagmanager.com">

	<?php wp_head(); ?>

	<script>
	/* Delayed analytics — loads after 3.5s or first user interaction */
	(function(){
		var loaded = false;
		function loadAnalytics() {
			if (loaded) return;
			loaded = true;
			/* GTM */
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
			var j = document.createElement('script');
			j.async = true;
			j.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-5496VPK';
			document.head.appendChild(j);
			/* Byggfakta Analytics Pro */
			var b = document.createElement('script');
			b.async = true;
			b.defer = true;
			b.src = '//stats.docu.info/docu-snippet.js';
			b.id = 'docu-snippet';
			b.setAttribute('data-site-id', '8');
			b.setAttribute('data-domain-id', '476');
			document.head.appendChild(b);
		}
		var t = setTimeout(loadAnalytics, 3500);
		['mouseover','touchstart','scroll','keydown'].forEach(function(evt) {
			document.addEventListener(evt, function handler() {
				clearTimeout(t);
				loadAnalytics();
				document.removeEventListener(evt, handler);
			}, {once: true, passive: true});
		});
	})();
	</script>	
	<link rel="icon" type="image/svg+xml" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon.svg" />
	<link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon-96x96.png" sizes="96x96" />
	<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/apple-touch-icon.png" />
	<link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/assets/favicon/site.webmanifest" />
</head>
<body <?php body_class('font-sohne-buch black text-base scroll-smooth bg-acryl-beige-lightest font-normal'); ?>>

	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5496VPK"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	
<header class="py-4 hidden lg:flex">
	<div class="max-w-max w-full mx-auto px-20">
		<div class="flex items-center justify-between">
			<a href="<?php echo home_url(); ?>" class="flex items-center">
				<img src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-dark.svg" alt="Acrylicon logo" width="208" height="45">
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
			<img class="w-48" src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-dark.svg" alt="Acrylicon logo" width="208" height="45">
		</a>
		<button
		id="menuButton"
		type="button"
		aria-controls="menuPanel"
		aria-expanded="false"
		aria-label="Menu"
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