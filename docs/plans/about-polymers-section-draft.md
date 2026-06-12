# Polymers-seksjon: About-side (EN, page 86)

**Plassering:** Etter det store fabrikkbildet (image ID 4929) og rett før heading "A sustainable choice".

**Beslutninger (Andreas 2026-05-28):**
- Logo over tekst (ikke ved siden av)
- "Excellent credit rating" osv. som bullet-liste
- Firmainfo som blockquote
- "Certifications" og "CrefoZert by Creditreform" som h3 under Polymers-h2

**Logo:** `themes/acrylicon-2024/assets/gfx/crefozert-2026.png` — lokalt attachment ID 6011 (etter `wp media import`). NB: Må også lastes opp til prod media library, og attachment ID i blocken oppdateres ved deploy.

---

## Prose-versjon (for visuell bygging i Block Editor)

### AcryliCon Polymers (h2)

> Since 2014, we have been active as a resin producer with our own production facility in Germany and produce "Made in Germany" resins for our industrial floors under our own management. This allows us to guarantee and ensure a very high quality standard.
>
> Our large and modern production plant has sufficient capacity for the forward-looking planning of your national as well as international business. Our extensive supplier network and the bundling of financial and logistical resources offer your business additional added value such as quality, efficiency and reliability.

**Blockquote:**
> **AcryliCon Polymers GmbH**
> Lederstraße 19, 19306 Neustadt-Glewe, Deutschland
> Tel: +49 38757 5955-10
> info@acryliconpolymers.com

### Certifications (h3)

> Various certificates such as the Environmental Product Declaration (EPD), the M1 emission class certificate or food safety tests provide our flooring systems with the framework for sustainable action and ecological building in accordance with LEED or BREEAM. In the course of our certification according to DIN EN ISO 14001, we strive for continuous monitoring and improvement of our environmental management system.

### CrefoZert by Creditreform (h3)

**[Bilde: crefozert-2026.png, ~200px bred, sentrert eller venstre]**

> AcryliCon Polymers GmbH has been awarded the CrefoZert by Creditreform. This credit rating certificate stands for excellent financial stability, sustainable corporate management and a very good future outlook. Only a small percentage of German companies meet the strict criteria for this award.

The CrefoZert confirms:
- Excellent credit rating
- Minimal default risk
- Economic stability
- Trustworthiness towards customers, partners and investors

> We are proud of this recognition – it underlines our solid corporate development and sustainable growth.

---

## Ferdige Gutenberg-blokker (paste i Code Editor)

Følger samme pattern som resten av siden: to-kolonner med innhold venstre / tom høyre, `lg-text-2xl`-heading, `text-lg`-paragrafer, `fsb/flexible-spacer` mellom seksjoner.

```html
<!-- wp:fsb/flexible-spacer {"heightLg":"80px","heightMd":"48px","heightSm":"36px"} -->
<div aria-hidden="true" class="wp-block-fsb-flexible-spacer fsb-flexible-spacer"><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--lg" style="height:80px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--md" style="height:48px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--sm" style="height:36px"></div></div>
<!-- /wp:fsb/flexible-spacer -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"lg-text-2xl md:text-xl text-lg","fontSize":"lg-text-2xl,md-text-xl,text-lg"} -->
<h2 class="wp-block-heading lg:text-2xl md:text-xl text-lg has-lg-text-2-xl-md-text-xl-text-lg-font-size">AcryliCon Polymers</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">Since 2014, we have been active as a resin producer with our own production facility in Germany and produce "Made in Germany" resins for our industrial floors under our own management. This allows us to guarantee and ensure a very high quality standard.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">Our large and modern production plant has sufficient capacity for the forward-looking planning of your national as well as international business. Our extensive supplier network and the bundling of financial and logistical resources offer your business additional added value such as quality, efficiency and reliability.</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><p><strong>AcryliCon Polymers GmbH</strong><br>Lederstraße 19, 19306 Neustadt-Glewe, Deutschland<br>Tel: +49 38757 5955-10<br>info@acryliconpolymers.com</p></blockquote>
<!-- /wp:quote -->

<!-- wp:fsb/flexible-spacer {"heightLg":"40px","heightMd":"24px","heightSm":"18px"} -->
<div aria-hidden="true" class="wp-block-fsb-flexible-spacer fsb-flexible-spacer"><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--lg" style="height:40px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--md" style="height:24px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--sm" style="height:18px"></div></div>
<!-- /wp:fsb/flexible-spacer -->

<!-- wp:heading {"level":3,"className":"lg-text-xl md:text-lg text-base","fontSize":"lg-text-xl,md-text-lg,text-base"} -->
<h3 class="wp-block-heading lg:text-xl md:text-lg text-base has-lg-text-xl-md-text-lg-text-base-font-size">Certifications</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">Various certificates such as the Environmental Product Declaration (EPD), the M1 emission class certificate or food safety tests provide our flooring systems with the framework for sustainable action and ecological building in accordance with LEED or BREEAM. In the course of our certification according to DIN EN ISO 14001, we strive for continuous monitoring and improvement of our environmental management system.</p>
<!-- /wp:paragraph -->

<!-- wp:fsb/flexible-spacer {"heightLg":"40px","heightMd":"24px","heightSm":"18px"} -->
<div aria-hidden="true" class="wp-block-fsb-flexible-spacer fsb-flexible-spacer"><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--lg" style="height:40px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--md" style="height:24px"></div><div class="fsb-flexible-spacer__device fsb-flexible-spacer__device--sm" style="height:18px"></div></div>
<!-- /wp:fsb/flexible-spacer -->

<!-- wp:image {"id":6011,"width":"200px","sizeSlug":"medium","linkDestination":"none"} -->
<figure class="wp-block-image size-medium is-resized"><img src="/wp-content/uploads/2026/05/crefozert-2026.png" alt="CrefoZert by Creditreform — AcryliCon Polymers GmbH" class="wp-image-6011" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"className":"lg-text-xl md:text-lg text-base","fontSize":"lg-text-xl,md-text-lg,text-base"} -->
<h3 class="wp-block-heading lg:text-xl md:text-lg text-base has-lg-text-xl-md-text-lg-text-base-font-size">CrefoZert by Creditreform</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">AcryliCon Polymers GmbH has been awarded the CrefoZert by Creditreform. This credit rating certificate stands for excellent financial stability, sustainable corporate management and a very good future outlook. Only a small percentage of German companies meet the strict criteria for this award.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">The CrefoZert confirms:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>Excellent credit rating</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Minimal default risk</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Economic stability</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Trustworthiness towards customers, partners and investors</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"className":"text-lg","fontSize":"text-lg"} -->
<p class="text-lg has-text-lg-font-size">We are proud of this recognition – it underlines our solid corporate development and sustainable growth.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
```

---

## Bullet-listen som fjernes fra bunnen

Slett denne blokken (mellom "From local to global"-spacer og første beige-card):

```html
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"lg:text-2xl md:text-xl text-lg",...} -->
<h2 ...>Certifications</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul class="wp-block-list">
  <li>Breeam Nor confirmation...</li>
  <li>EPD (Environmental Product Declaration)</li>
  <li>Listed in the Scandinavian Building Products Portal...</li>
  <li>Completed fire tests</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
```

NB: Beige-card-variant-three-blokkene (M1/DNV GL/RIBA og SINTEF/Bureau Veritas) **beholdes** rett etter.

---

## Sjekkliste for utførelse

- [ ] Last opp `crefozert-2026.png` til prod media library — noter prod attachment ID
- [ ] Lokalt: rediger About-page 86 i Block Editor, sett inn Polymers-blokker rett etter `<wp:image id=4929 ...>`-blokken
- [ ] Lokalt: slett "Certifications"-bullet-listen lengre ned
- [ ] Test visuelt at sertifiseringene viser riktig + logoen ser bra ut
- [ ] Deploy: enten `wp post update 86 --post_content="..."` på prod, eller redigér tilsvarende på prod og bytt ut wp-image-6011 med prod-ID
