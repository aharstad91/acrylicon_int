---
title: "Mobil UX og PageSpeed-optimalisering"
type: feat
date: 2026-02-20
deepened: 2026-02-20
reviewed: 2026-02-20
trello: https://trello.com/c/ClySejp0
brainstorm: docs/brainstorms/2026-02-20-mobil-ux-pagespeed-brainstorm.md
---

# Mobil UX og PageSpeed-optimalisering

## Enhancement Summary

**Deepened:** 2026-02-20 | **Reviewed:** 2026-02-20
**Research agents:** Best Practices, Performance Oracle, Security Sentinel, Frontend Races
**Review agents:** DHH (8/10), Code Simplicity, Performance Oracle

### Critical Corrections

1. **FJERNET GSAP/bodyScrollLock/Swiper fra defer-listen** — bryter avhengighetskjeder og inline-scripts
2. **Riktig hero-blokk identifisert** — `image-split` (ikke `split-image-text-banner`) er LCP-elementet
3. **Fikset Docu analytics URL** — riktig URL er `docu-snippet.js` med `data-site-id` og `data-domain-id`
4. **Lagt til Swiper-guard i scripts.js** — obligatorisk ved conditional lasting
5. **Fjern standalone gtag.js** — duplikat GA4-lasting (146 KB spart)
6. **Fikset bildestørrelser** — ikoner i feature-card/product-card bruker `'thumbnail'`, ikke `'large'`
7. **Forenklet conditional Swiper** — bruker enkel `has_block()`, ikke over-engineered helper

---

## Overview

Systematisk ytelsesoptimalisering av acrylicon.no fra PageSpeed 43 (mobil) til 90+, med grønne Core Web Vitals. Jobber i 4 prioriterte lag som bygger på hverandre — hvert lag gir målbar forbedring.

**Baseline (Lighthouse mobil, 2026-02-20):**

| Side | Score | FCP | LCP | TBT | CLS |
|------|-------|-----|-----|-----|-----|
| `/no/` | **43** | 1.9s | **11.7s** | **1,690ms** | 0.035 |
| `/` | **75** | 1.0s | **8.6s** | 20ms | 0 |
| `/no/referanser/` | **84** | 1.1s | 4.6s | 0ms | 0 |

**Mål:** PageSpeed 90+, LCP < 2.5s, CLS < 0.1, INP < 200ms

---

## Lag 1: Fjern død kode

Raskeste gevinst — fjern ting som lastes men aldri brukes.

### 1.1 Fjern Headroom.js (4.6 KB)

**Fil:** `functions.php` ~linje 50

Fjern enqueue-kallet:
```php
// FJERN DENNE LINJEN:
wp_enqueue_script('headroom', get_template_directory_uri() . '/assets/scripts/headroom.js', array(), '1.0.0', true);
```

Slett filen: `assets/scripts/headroom.js`

### 1.2 Slett scrollreveal.min.js (44.6 KB)

Filen ligger i `assets/scripts/scrollreveal.min.js` men er ikke enqueued. Bekreft at den ikke refereres fra noen PHP/JS-fil, og slett.

### 1.3 Fjern standalone gtag.js (146 KB spart!)

**Fil:** `header.php` linje 15-23

GA4 lastes allerede via GTM (`GTM-TJ93BLWH`). Den separate `gtag.js`-scriptet (`G-D2YGZGKMXP`) er en duplikat som koster 146 KB og dobbelt-teller sidevisninger.

```html
<!-- FJERN DISSE LINJENE (linje 15-23): -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-D2YGZGKMXP"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-D2YGZGKMXP');
</script>
```

**Verifisering:** Sjekk i GTM at GA4-taggen (`G-D2YGZGKMXP`) er konfigurert og fyrer. Etter fjerning, verifiser at GA4 fortsatt mottar data via GTM.

### 1.4 Slett ubrukte legacy-filer

Bekreft at disse ikke er enqueued (de er det ikke), og slett:
- `assets/css/utility.css`, `assets/css/utility-md.css`, `assets/css/utility-lg.css`
- `assets/css/-title-block.css` (uvanlig filnavn med leading dash)
- `blocks/showreel-reference-produkter/template copy.php` (dead copy-fil)

**Forventet gevinst:** ~200 KB fjernet (inkl. gtag), renere codebase.

---

## Lag 2: Fiks kritiske rendering-problemer

### 2.1 GSAP bfcache-bug (blank side ved tilbake-navigasjon)

**Problem:** `main, footer { opacity: 0 }` i CSS + GSAP fade-in på `DOMContentLoaded`. Ved bfcache-restore kjører ikke `DOMContentLoaded` igjen → blank side.

**Fil:** `assets/scripts/transitions.js`

Legg til `pageshow`-handler etter eksisterende `DOMContentLoaded`-handler:

```js
// Fix bfcache: restore visibility when navigating back
window.addEventListener('pageshow', function(event) {
  if (event.persisted) {
    gsap.killTweensOf('main, footer');
    gsap.set('main, footer', { opacity: 1, y: 0, clearProps: 'all' });
  }
});
```

### 2.2 Preconnect-hints for CDN-ressurser

**Fil:** `functions.php` — legg til via `wp_head` action (prioritet 1):

```php
add_action('wp_head', function() {
    // preconnect for jsdelivr (GSAP + Swiper CDN)
    echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>' . "\n";

    // dns-prefetch for delayed scripts
    echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    echo '<link rel="dns-prefetch" href="https://www.google-analytics.com">' . "\n";

    // Preload primary font
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/fonts/soehne-buch.woff2" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1);
```

### 2.3 fetchpriority="high" på LCP-bilde

**LCP-elementet er `image-split`-blokken** — to store bilder (900px høyde) i 2-kolonne grid.

**Fil:** `blocks/image-split/template.php` linje 40

Legg til `fetchpriority` og `loading` attributter på det FØRSTE bildet:

```php
<?php if ($image_one) : ?>
    <?php echo wp_get_attachment_image($image_one['ID'], 'large', false, array(
        'class'         => 'w-full h-96 lg:h-900 object-cover rounded-lg',
        'fetchpriority' => 'high',
        'loading'       => 'eager',
        'decoding'      => 'async',
    )); ?>
<?php endif; ?>
```

Det andre bildet i samme blokk beholder standard `loading="lazy"`. Endre `'full'` til `'large'` på begge bilder (se Lag 4.2).

**Merk:** `fetchpriority="high"` bør KUN brukes på ett bilde per side.

### 2.4 Bredde/høyde på logo

**Fil:** `header.php` linje 48 og 67

Legg til eksplisitte `width` og `height` for å unngå CLS. Mål faktisk SVG-størrelse.

### 2.5 theme-color meta tag

**Fil:** `header.php` — legg til i `<head>`:

```html
<meta name="theme-color" content="#253761">
```

**Forventet gevinst:** bfcache-bug fikset, ~100-300ms bedre LCP fra preconnect + fetchpriority. CLS-forbedring fra logo-dimensjoner.

---

## Lag 3: Optimaliser asset-levering

### 3.1 Conditional Swiper-lasting + Swiper-guard

**Fil:** `functions.php` — endre `theme_enqueue_scripts()`

```php
// Only load Swiper when slider blocks are present
if ( is_singular() && has_block('acf/slider-block') ) {
    wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
}
```

**Merk:** Sjekk også om `single-referanser-old.php` fortsatt er i bruk — den har hardkodet Swiper-markup som `has_block()` ikke fanger. Hvis den er aktiv, last Swiper globalt inntil templaten er migrert.

**KRITISK — Legg til Swiper-guard i scripts.js:**

**Fil:** `assets/scripts/scripts.js` linje 1

```js
// Endre fra:
var swiper = new Swiper(".mySwiper", { ... });

// Til:
if (typeof Swiper !== 'undefined' && document.querySelector('.mySwiper')) {
    var swiper = new Swiper(".mySwiper", { ... });
}
```

**Gevinst:** 44 KB JS + CSS spart på alle sider uten slider.

### 3.2 Script-strategi (IKKE defer GSAP/bodyScrollLock)

BEHOLD eksisterende defer-liste uendret (`jquery-core`, `jquery-migrate`). Alle øvrige scripts lastes allerede i footer og er ikke render-blocking. Defer gir minimal ekstra gevinst.

### 3.3 Delayed tredjepartsskript (analytics)

GTM, Facebook pixel og Docu analytics delayed-loades til etter brukerinteraksjon eller timeout.

**Fil:** `header.php` — erstatt ALLE inline GTM/gtag/Docu-skript (linje 7-27) med:

```html
<!-- Definer dataLayer tidlig slik at events køes korrekt -->
<script>window.dataLayer = window.dataLayer || [];</script>

<script>
// Delay third-party scripts until user interaction or 3.5s timeout
(function() {
  var loaded = false;
  var timer;

  function loadScripts() {
    if (loaded) return;
    loaded = true;
    clearTimeout(timer);

    // GTM (inkluderer GA4 via GTM-config)
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TJ93BLWH');

    // Docu/Byggfakta (korrekt URL og attributter fra original)
    var docu = document.createElement('script');
    docu.src = 'https://stats.docu.info/docu-snippet.js';
    docu.id = 'docu-snippet';
    docu.dataset.siteId = '8';
    docu.dataset.domainId = '476';
    docu.async = true;
    document.head.appendChild(docu);
  }

  // Interaction triggers
  ['scroll', 'touchstart', 'click', 'keydown'].forEach(function(evt) {
    document.addEventListener(evt, loadScripts, { once: true, passive: true });
  });

  // Timeout fallback
  timer = setTimeout(loadScripts, 3500);
})();
</script>
```

**Risiko:** Analytics mister data fra de første 3.5 sekundene (eller til brukerinteraksjon). For B2B akseptabelt.

**Forventet gevinst:** TBT fra 1,690ms → < 200ms. Potensielt +20-30 poeng.

---

## Lag 4: Bildeoptimalisering

### 4.1 Fiks slider-blokk (raw img → wp_get_attachment_image)

**Fil:** `blocks/slider-block/template.php` linje 17-21

Endre fra rå `<img src>` til:
```php
<?php echo wp_get_attachment_image($image['id'], 'large', false, array(
    'class' => 'w-full h-auto object-contain rounded-lg',
)); ?>
```

**Merk:** ACF bildefeltet må returnere array (ikke bare URL) for å ha tilgang til `$image['id']`.

### 4.2 Fiks blokker som bruker 'full' størrelse

| Blokk | Linje | Nåværende | Endre til | Grunn |
|-------|-------|-----------|-----------|-------|
| `image-split/template.php` | 40, 44 | `'full'` | `'large'` | Store bilder (900px høyde) |
| `product-card/template.php` | 41 | `'full'` | `'large'` | Hovedbilde |
| `product-card/template.php` | 74 | `'full'` | `'thumbnail'` | Ikon (32x32) |
| `feature-card/template.php` | 43 | `'full'` | `'thumbnail'` | Ikon (38x38) |
| `office-contact-card/template.php` | 43 | `'full'` | `'large'` | Kontorbilde |

### 4.3 Installer ShortPixel

```bash
ssh acryli_28355@jana-osl.servebolt.cloud 'wp plugin install shortpixel-image-optimiser --activate'
```

**Konfigurasjon:**
- Komprimeringsmodus: Lossy, JPEG-kvalitet: 82%
- WebP-generering: Aktivert
- Maks bildestørrelse: 1920px
- Network-aktiver, deretter per-subsite med API-nøkkel
- Bulk-optimaliser i batches (11GB uploads!)

### 4.4 WebP-serving via .htaccess

```apache
# ShortPixel WebP delivery — BEFORE WordPress rewrite rules
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_ACCEPT} image/webp
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.webp -f
    RewriteRule ^(.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,L]
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "\.(jpe?g|png)$">
        Header append Vary Accept
    </FilesMatch>
</IfModule>
```

Verifiser at multisite-stier (`/wp-content/uploads/sites/3/`) matcher reglene. Legg til AVIF-regler først når ShortPixel genererer AVIF-filer.

**Forventet gevinst:** 60-80% reduksjon i bildestørrelser. LCP-bildet fra 2.6 MB → ~200-400 KB.

---

## Mål og iterer

### Test etter hvert lag

```bash
npx lighthouse https://acryli-28355.jana-osl.servebolt.cloud/no/ \
  --only-categories=performance --form-factor=mobile \
  --chrome-flags="--headless --no-sandbox" --output=json --quiet
```

### Forventet progresjon

| Lag | Tiltak | Forventet score |
|-----|--------|----------------|
| 0 | Baseline | 43 |
| 1 | Fjern død kode + gtag duplikat | 48-55 |
| 2 | Rendering-fixes (bfcache, preconnect, fetchpriority) | 55-65 |
| 3 | Conditional Swiper + delayed analytics | **70-85** |
| 4 | Bildeoptimalisering + WebP | **85-95** |

Dersom 90+ ikke nås etter Lag 4, mål og bestem neste steg basert på faktiske data.

---

## Acceptance Criteria

### Functional Requirements
- [ ] PageSpeed Insights mobil score 90+ på `/no/`
- [ ] LCP < 2.5s på alle hovedsider
- [ ] CLS < 0.1 på alle sider
- [ ] TBT < 200ms (INP < 200ms)
- [ ] Tilbake-navigasjon viser aldri blank side
- [ ] Alle analytics (GTM, GA4, Docu/Byggfakta) fungerer fortsatt
- [ ] Swiper-slidere fungerer på sider som har dem
- [ ] Sider UTEN slider kaster ikke JavaScript-feil

### Non-Functional Requirements
- [ ] Ingen visuell endring
- [ ] GSAP fade-in-animasjon fungerer som før
- [ ] Mobilnavigasjon (bodyScrollLock) fungerer som før
- [ ] Ingen console errors på noen side

### Quality Gates
- [ ] Lighthouse-test kjørt før og etter hvert lag
- [ ] Manuell testing av mobil UX (navigasjon, tilbake-knapp, slidere)
- [ ] Analytics verifisert i GA4 etter deploy
- [ ] Console sjekket for JavaScript-feil på minst 5 forskjellige sidetyper

---

## Dependencies & Risks

| Risiko | Sannsynlighet | Konsekvens | Mitigering |
|--------|---------------|------------|------------|
| Delayed analytics mister data | Middels | Lav | 3.5s timeout + dataLayer tidlig, kun 0-3.5s gap |
| Conditional Swiper + manglende guard | **Høy hvis glemt** | Høy | `typeof Swiper` guard er **obligatorisk** |
| `single-referanser-old.php` har Swiper uten blokk | Lav | Middels | Sjekk om template er aktiv; last Swiper globalt om nødvendig |
| Standalone gtag-fjerning bryter analytics | Lav | Høy | Verifiser GTM GA4-tag **før** fjerning |
| ShortPixel forringer bildekvalitet | Lav | Middels | Lossy 82% knapt synlig, kan justeres |
| bfcache-fix + page transitions | Lav | Høy | `gsap.killTweensOf()` forhindrer konflikter |
| WebP .htaccess + CDN cache | Lav | Middels | `Vary: Accept` header inkludert |

---

## Rekkefølge og implementeringsnotater

### Lag 1-2 deployes sammen (lav risiko)
- Fjern død kode + rendering-fixes er trygge endringer
- Verifiser gtag-fjerning lokalt — sjekk at GTM GA4-tag fyrer

### Lag 3 deployes separat (middels risiko)
- **Swiper-guard i scripts.js MÅ deployes samtidig med conditional Swiper**
- Test delayed analytics: verifiser GA4 og Docu/Byggfakta data
- Sjekk minst 5 sidetyper for console errors

### Lag 4 krever plugin-installasjon på prod
- ShortPixel: network-aktiver, per-subsite API-nøkkel
- Bulk-optimalisering i batches (11GB, kan ta dager)
- WebP .htaccess testes med multisite paths

---

## Filer som endres

| Fil | Endring |
|-----|---------|
| `functions.php` | Fjern Headroom enqueue, conditional Swiper, preconnect/preload via `wp_head` |
| `header.php` | Fjern standalone gtag + Docu-tag, theme-color, logo dimensjoner, delayed analytics |
| `assets/scripts/transitions.js` | pageshow bfcache-handler |
| `assets/scripts/scripts.js` | Swiper typeof guard |
| `blocks/image-split/template.php` | `fetchpriority="high"` på første bilde, 'full' → 'large' |
| `blocks/slider-block/template.php` | `wp_get_attachment_image()` istedenfor raw img |
| `blocks/product-card/template.php` | Linje 41: 'full' → 'large', linje 74: 'full' → 'thumbnail' |
| `blocks/feature-card/template.php` | 'full' → 'thumbnail' (38x38 ikoner) |
| `blocks/office-contact-card/template.php` | 'full' → 'large' |
| `assets/scripts/headroom.js` | SLETT |
| `assets/scripts/scrollreveal.min.js` | SLETT |
| `.htaccess` (prod) | WebP-rewrite regler + Vary header |

---

## References

### Internal
- Brainstorm: `docs/brainstorms/2026-02-20-mobil-ux-pagespeed-brainstorm.md`
- Bevist løsning: `docs/solutions/performance-issues/pagespeed-69-to-99-render-blocking-webp-20260211.md`
- Medieoptimeringsplan: `docs/plans/2026-01-26-feat-wordpress-media-storage-optimization-plan.md`

### External
- [PageSpeed Insights](https://pagespeed.web.dev/)
- [web.dev LCP](https://web.dev/articles/lcp)
- [web.dev bfcache](https://web.dev/articles/bfcache)
- [ShortPixel](https://shortpixel.com/)
- [WP 6.3 Image Performance](https://make.wordpress.org/core/2023/07/13/image-performance-enhancements-in-wordpress-6-3/)
- [Fast GTM Loading](https://crystallize.com/blog/fast-loading-google-tag-manager)
