---
title: Dynamic Product Sheets from ACF Block Content
category: best-practices
tags: [acf, blocks, gutenberg, product-sheets, print-css, templates]
module: themes/acrylicon-2024
date: 2026-02-12
---

# Dynamic Product Sheets from ACF Block Content

## Problem
10 static PDF product sheets (created in Illustrator) needed manual updates whenever product data changed. The same data already existed in WordPress as ACF blocks on product pages.

## Solution
Parse Gutenberg block content with `parse_blocks()` to extract ACF block data, then re-render in a product sheet layout with print CSS.

### Key Insight
ACF blocks store their repeater data in `$block['attrs']['data']` with keys like `{repeater}_{index}_{field}`. This means you can extract structured data from block content without rendering the blocks.

### Implementation Pattern
```php
// Extract repeater data from ACF block attrs
$block_data = $block['attrs']['data'];
$count = intval( $block_data['technical_info_repeater'] );
for ( $i = 0; $i < $count; $i++ ) {
    $name = $block_data["technical_info_repeater_{$i}_tech_info_name"];
    $desc = $block_data["technical_info_repeater_{$i}_tech_info_desc"];
}
```

### Template Routing
Used `query_vars` filter + `template_include` filter to load a custom template when `?view=sheet` is present on a `produkter` CPT single page.

### Print CSS
- `@media print` hides header, footer, nav, print button
- `print-color-adjust: exact` forces background colors
- `break-inside: avoid` on sections prevents splitting
- A4 page size with 10mm margins

## Gotchas
- **Short paragraphs captured as description**: Tags like "Produkter" got picked up. Fixed by filtering paragraphs shorter than 50 chars.
- **Recursive block parsing needed**: Content blocks are nested inside `wp:group` and `wp:columns` wrappers. Must recurse into innerBlocks.
- **Image IDs vs URLs**: ACF block image fields store attachment IDs, not URLs. Use `wp_get_attachment_image()` to render.

## Files
- `inc/product-sheet-helpers.php` — Block parser
- `single-produkter-sheet.php` — Sheet template
- `assets/css/product-sheet-print.css` — Print styles
- `functions.php` — Query var + template routing
