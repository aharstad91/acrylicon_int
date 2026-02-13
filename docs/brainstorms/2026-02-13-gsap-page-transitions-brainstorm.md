# GSAP Page Transitions & Hero Animations

**Date:** 2026-02-13
**Status:** Ready for planning

---

## What We're Building

Legge til subtile, profesjonelle animasjoner på Acrylicon-nettsiden:

1. **Page transitions** - Enkel fade-out/fade-in mellom sider
2. **Hero fade-in** - Hele hero-seksjonen fader inn som en enhet ved sidelasting
3. **Fjerne ScrollReveal** - Ubrukt bibliotek (ingen `.reveal()` calls i kodebasen), erstattes av GSAP

## Why This Approach

**GSAP Core only** (ingen plugins, ingen Barba.js):

- Enklest og mest robust for WordPress multisite
- GSAP core er ~24KB gzipped - erstatter ScrollReveal (~15KB) med minimal økning
- Ingen SPA-kompleksitet som kan krasje med WP plugins, admin bar, eller caching
- Page transitions via enkel `beforeunload`/`DOMContentLoaded` fade-logikk
- Subtil og elegant stil (200-400ms), profesjonelt B2B-preg

**Forkastet:**
- **GSAP + Barba.js** - For komplekst for WordPress, mange edge cases med plugins/cache
- **CSS View Transitions API** - For dårlig nettleserstøtte ennå, begrenset kontroll

## Key Decisions

1. **GSAP Core only** - Ingen ScrollTrigger, Barba.js eller andre plugins
2. **Fjerne ScrollReveal** - Ikke i bruk, spar ~15KB
3. **Hero: Hele seksjonen fader inn** - Ikke sekvensielt (tekst, bilde etc.), men som en enhet
4. **Page transitions: Enkel fade** - Fade-out ved klikk, fade-in ved DOMContentLoaded
5. **Timing: 200-400ms** - Subtilt og profesjonelt, ikke tregt

## Technical Notes

- ScrollReveal er enqueued i `functions.php:49` men har ingen aktive `.reveal()` calls
- `html.sr .ani-h` CSS-regel i `tailwind.css` kan fjernes
- GSAP kan lastes fra CDN eller som lokal fil
- Eksisterende scripts: jQuery, Swiper, Headroom.js, bodyScrollLock

## Open Questions

- Skal GSAP lastes fra CDN (jsDelivr/cdnjs) eller som lokal fil i assets/?
- Skal hero-animasjonen gjelde alle sider eller bare forsiden/landingssider?
