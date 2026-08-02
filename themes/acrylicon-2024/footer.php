<footer class="bg-acryl-red text-white py-10 font-normal">
	<div class="max-w-screen-2xl mx-auto px-5 md:px-20">
		<div class="grid lg:grid-cols-3  gap-12">
			<!-- Left Column -->
			<div class="space-y-8">
				<?php
				wp_nav_menu(array(
					'theme_location'  => 'footer-one',
					'menu_class'      => 'flex flex-col gap-1 list-none',
					'container'       => 'nav',
					'container_class' => 'text-3xl font-normal',
				));
				?>
			</div>
			<div class="space-y-8">
				<!-- Industri Section -->
				<div class="space-y-4">
					<h2 class="text-base font-normal mb-4"><?php echo ( get_current_blog_id() === 1 ) ? 'Applications' : 'Bruksområder'; ?></h2>
					<?php
					wp_nav_menu(array(
						'theme_location'  => 'footer-three',
						'menu_class'      => 'flex flex-col gap-1 list-none',
						'container'       => 'nav',
						'container_class' => 'font-normal',
					));
					?>
				</div>
			</div>
			<div class="space-y-4">
				<h2 class="text-base font-normal mb-4"><?php echo ( get_current_blog_id() === 1 ) ? 'Locations' : 'Kontorer'; ?></h2>
				<?php
				wp_nav_menu(array(
					'theme_location'  => 'footer-four',
					'menu_class'      => 'flex flex-col gap-1 list-none',
					'container'       => 'nav',
					'container_class' => 'font-normal',
				));
				?>
			</div>
		</div>
		<div class="md:grid md:grid-cols-2 mt-12 md:mt-0">
			<?php
			wp_nav_menu(array(
				'theme_location'  => 'footer-two',
				'menu_class'      => 'flex flex-col gap-1 list-none',
				'container'       => 'nav',
				'container_class' => 'font-normal',
			));
			?>
			<!-- Logo and Language Switcher -->
			<div class="mt-14 flex flex-col md:items-end gap-4">
				<img class="w-full max-w-420" src="<?php bloginfo('template_directory'); ?>/assets/gfx/acrylicon-logo-light.svg" alt="Acrylicon logo">
				<?php acrylicon_render_language_switcher( 'footer' ); ?>
			</div>
		</div>
	</div>
</footer>



<script>
const mobileMenu = () => {
  const menuButton = document.getElementById('menuButton');
  const menuPanel = document.getElementById('menuPanel');
  const hamburger = document.querySelector('.mm-toggle');
  
  menuButton?.addEventListener('click', () => {
	const isOpen = menuPanel.classList.contains('opacity-100');
	
	// Toggle menu visibility and opacity
	if (isOpen) {
	  // Lukker menyen - først setter vi opacity til 0 med animasjon
	  menuPanel.classList.remove('opacity-100', 'visible');
	  menuPanel.classList.add('opacity-0');
	  
	  // Fjerner hamburger-til-kryss animasjon
	  hamburger.classList.remove('mm-open');
	  
	  // Oppdaterer ARIA-attributt
	  menuButton.setAttribute('aria-expanded', 'false');
	  
	  // Aktiverer scrolling igjen
	  bodyScrollLock.enableBodyScroll(menuPanel);
	  
	  // Legger til invisible klassen etter at animasjonen er ferdig
	  setTimeout(() => {
		menuPanel.classList.add('invisible');
	  }, 300);
	} else {
	  // Åpner menyen - først fjerner vi invisible for å gjøre elementet synlig
	  menuPanel.classList.remove('invisible');
	  
	  // Lille forsinkelse for å sikre at DOM-oppdatering rekker å skje
	  setTimeout(() => {
		menuPanel.classList.remove('opacity-0');
		menuPanel.classList.add('opacity-100', 'visible');
		
		// Aktiverer hamburger-til-kryss animasjon
		hamburger.classList.add('mm-open');
		
		// Oppdaterer ARIA-attributt
		menuButton.setAttribute('aria-expanded', 'true');
		
		// Deaktiverer scrolling på body
		bodyScrollLock.disableBodyScroll(menuPanel);
	  }, 10);
	}
  });
};

document.addEventListener('DOMContentLoaded', mobileMenu);

// Language switcher dropdown
const langSwitcher = () => {
  const toggle = document.getElementById('langToggle');
  const dropdown = document.getElementById('langDropdown');
  if (!toggle || !dropdown) return;

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains('opacity-100');
    if (isOpen) {
      dropdown.classList.remove('opacity-100', 'visible');
      dropdown.classList.add('opacity-0', 'invisible');
      toggle.setAttribute('aria-expanded', 'false');
    } else {
      dropdown.classList.remove('opacity-0', 'invisible');
      dropdown.classList.add('opacity-100', 'visible');
      toggle.setAttribute('aria-expanded', 'true');
    }
  });

  document.addEventListener('click', () => {
    dropdown.classList.remove('opacity-100', 'visible');
    dropdown.classList.add('opacity-0', 'invisible');
    toggle.setAttribute('aria-expanded', 'false');
  });
};

document.addEventListener('DOMContentLoaded', langSwitcher);
</script>


<?php wp_footer(); ?>

</body>
</html>