/**
 * Acrylicon Page Transitions & Hero Animations
 * Uses GSAP for subtle, elegant fade effects
 */

(function () {
  // Page fade-in with subtle lift (main starts at opacity:0 via CSS)
  document.addEventListener('DOMContentLoaded', function () {
    gsap.fromTo('main, footer',
      { opacity: 0, y: 5 },
      { opacity: 1, y: 0, duration: 0.2, ease: 'power2.out' }
    );
  });

  // Page fade-out on navigation
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href) return;

    // Skip external links, anchors, tel/mailto, new tabs, and wp-admin
    if (
      link.target === '_blank' ||
      href.startsWith('#') ||
      href.startsWith('tel:') ||
      href.startsWith('mailto:') ||
      href.indexOf('wp-admin') !== -1 ||
      href.indexOf('wp-login') !== -1 ||
      link.hasAttribute('download') ||
      e.ctrlKey || e.metaKey || e.shiftKey
    ) return;

    // Only transition for same-origin links
    try {
      var url = new URL(href, window.location.origin);
      if (url.origin !== window.location.origin) return;
    } catch (err) {
      return;
    }

    e.preventDefault();

    gsap.to('main, footer', {
      opacity: 0,
      y: -3,
      duration: 0.12,
      ease: 'power2.in',
      onComplete: function () {
        window.location.href = href;
      }
    });
  });
})();
