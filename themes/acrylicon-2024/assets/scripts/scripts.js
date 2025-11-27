var swiper = new Swiper(".mySwiper", {
	slidesPerView: 1, // Fast verdi på 1 slide i visningen
	spaceBetween: 4,
	freeMode: false, // Deaktiverer freeMode siden det kan forstyrre pagination
	pagination: {
		el: ".swiper-pagination",
		clickable: true,
		type: "bullets"
	},
	navigation: {
		nextEl: ".swiper-button-next",
		prevEl: ".swiper-button-prev",
	},
	slideToClickedSlide: true,
	slidesPerGroup: 1, // Naviger ett og ett bilde
	loop: true, // Muliggjør uendelig navigasjon
	// Fjernet breakpoints med ulike verdier siden vi vil ha samme oppsett overalt
});

// Add click handlers for side area navigation
const prevArea = document.querySelector('.swiper-area-prev');
const nextArea = document.querySelector('.swiper-area-next');
if (prevArea && nextArea) {
	prevArea.addEventListener('click', () => {
		swiper.slidePrev();
	});
	
	nextArea.addEventListener('click', () => {
		swiper.slideNext();
	});
}