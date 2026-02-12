---
title: Client-side Taxonomy Filtering for ACF Block Grids
category: best-practices
tags: [acf, blocks, filtering, javascript, taxonomies, performance]
module: themes/acrylicon-2024
date: 2026-02-12
---

# Client-side Taxonomy Filtering for ACF Block Grids

## Problem
Reference grid block used server-side page reloads (taxonomy archive links) for filtering. Only one taxonomy (industry) was filterable despite four being registered. Each filter click was a full page load.

## Solution
Render all posts in a single page load with `data-*` attributes per taxonomy, then filter client-side with vanilla JS.

### Why Client-side?
- Small dataset (15 posts) — all fit on one page
- Instant filtering — no server roundtrip
- Multiple taxonomy filters combinable (AND between groups, OR within)
- Zero backend complexity — no REST API, no AJAX handlers

### Implementation Pattern

**PHP: Data attributes on cards**
```php
$cat_slugs = implode( ',', wp_list_pluck( $card['categories'], 'slug' ) );
<div class="reference-card"
  data-categories="<?php echo esc_attr( $cat_slugs ); ?>"
  data-products="<?php echo esc_attr( $product_slugs ); ?>"
  data-offices="<?php echo esc_attr( $office_slugs ); ?>">
```

**PHP: Filter pills from actual data (not all terms)**
```php
// Only show terms that appear on displayed posts
foreach ( $cards as $card ) {
    foreach ( $card['categories'] as $t ) {
        $filter_categories[ $t->slug ] = $t->name;
    }
}
```

**JS: AND between groups, OR within**
```js
for ( var taxonomy in activeFilters ) {
    var selected = activeFilters[ taxonomy ];
    if ( selected.has( 'all' ) ) continue;
    var values = card.getAttribute( 'data-' + taxonomy ).split( ',' );
    var match = false;
    selected.forEach( function ( filter ) {
        if ( values.indexOf( filter ) !== -1 ) match = true;
    } );
    if ( ! match ) { show = false; break; }
}
```

### Sorting: Case Studies First
```php
usort( $cards, function ( $a, $b ) {
    if ( $a['is_case_study'] !== $b['is_case_study'] ) {
        return $b['is_case_study'] <=> $a['is_case_study'];
    }
    return strcmp( $b['date'], $a['date'] );
} );
```

## Gotchas
- **`query_posts()` is deprecated** — always use `WP_Query` with `wp_reset_postdata()`
- **Empty terms in filter UI** — use `hide_empty: true` AND only collect terms from displayed posts
- **Tailwind `hover:` classes need toggling** — when adding active state, also remove hover classes to prevent visual conflicts
- **`has_block()` for conditional enqueue** — only load filter JS when `acf/global-reference` block is on the page

## Files
- `blocks/global-reference/template.php` — Grid with data attributes and filter UI
- `assets/js/reference-filter.js` — Client-side filter logic
- `functions.php` — Conditional JS enqueue
- `taxonomy-referanser-kategorier.php` — Cleaned up taxonomy archive (WP_Query)
