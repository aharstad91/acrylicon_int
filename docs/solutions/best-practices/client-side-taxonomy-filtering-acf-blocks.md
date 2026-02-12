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

**PHP: Native `<select>` dropdowns (not pills)**
```php
<select class="filter-select ...">
    <option value="all">All industries</option>
    <?php foreach ( $filter_categories as $slug => $name ) : ?>
    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
    <?php endforeach; ?>
</select>
```

**JS: AND between dropdown groups**
```js
selects.forEach( function ( select ) {
    var taxonomy = select.closest( '.filter-group' ).getAttribute( 'data-filter-taxonomy' );
    filters[ taxonomy ] = select.value;
} );
// Each card: check if data-{taxonomy} contains selected value
```

### Design Decision: Dropdowns over Pills
Tried pills first (sidebar, then horizontal) — too much visual weight for 3 filter groups with 10+ options each. Native `<select>` dropdowns:
- Compact horizontal bar, doesn't push content down
- Familiar mobile UX (iOS spinner, Android dialog)
- Accessible out of the box (keyboard, screen readers)
- Can restyle later if needed

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
- **`has_block()` for conditional enqueue** — only load filter JS when `acf/global-reference` block is on the page
- **Pills don't scale** — with 10+ industry terms, pills dominated the page. Dropdowns solved this instantly
- **Native `<select>` styling** — `appearance-none` + custom `pr-8` for dropdown arrow space. Can't style the option list, but that's fine for MVP

## Files
- `blocks/global-reference/template.php` — Grid with data attributes and filter UI
- `assets/js/reference-filter.js` — Client-side filter logic
- `functions.php` — Conditional JS enqueue
- `taxonomy-referanser-kategorier.php` — Cleaned up taxonomy archive (WP_Query)
