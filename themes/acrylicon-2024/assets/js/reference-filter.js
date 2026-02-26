/**
 * Reference Grid — Client-side filtering via dropdowns
 *
 * Filters .reference-card elements by data-categories, data-products, data-offices.
 * AND between filter groups (each dropdown is one group).
 */
( function () {
	'use strict';

	var container = document.querySelector( '.reference-filters' );
	if ( ! container ) return;

	var cards     = document.querySelectorAll( '.reference-card' );
	var counterEl = document.querySelector( '.reference-count-visible' );
	var resetBtn  = document.querySelector( '.filter-reset' );
	var selects   = container.querySelectorAll( '.filter-select' );

	// Filter on every dropdown change + highlight active selects
	selects.forEach( function ( select ) {
		select.addEventListener( 'change', function () {
			// Visual active state on the select
			if ( select.value !== 'all' ) {
				select.classList.add( 'filter-active' );
			} else {
				select.classList.remove( 'filter-active' );
			}
			filterCards();
		} );
	} );

	// Reset button
	if ( resetBtn ) {
		resetBtn.addEventListener( 'click', function () {
			selects.forEach( function ( select ) {
				select.value = 'all';
				select.classList.remove( 'filter-active' );
			} );
			filterCards();
		} );
	}

	function filterCards() {
		var visible    = 0;
		var hasFilters = false;

		// Collect active filters from each dropdown
		var filters = {};
		selects.forEach( function ( select ) {
			var group = select.closest( '.filter-group' );
			var taxonomy = group.getAttribute( 'data-filter-taxonomy' );
			var value = select.value;
			filters[ taxonomy ] = value;
			if ( value !== 'all' ) hasFilters = true;
		} );

		// Show/hide reset button
		if ( resetBtn ) {
			resetBtn.classList.toggle( 'hidden', ! hasFilters );
		}

		// Filter cards: AND between groups
		cards.forEach( function ( card ) {
			var show = true;

			for ( var taxonomy in filters ) {
				var selected = filters[ taxonomy ];
				if ( selected === 'all' ) continue;

				var attr   = card.getAttribute( 'data-' + taxonomy ) || '';
				var values = attr ? attr.split( ',' ) : [];

				if ( values.indexOf( selected ) === -1 ) {
					show = false;
					break;
				}
			}

			card.classList.toggle( 'hidden', ! show );
			if ( show ) visible++;
		} );

		if ( counterEl ) {
			counterEl.textContent = visible;
		}
	}
} )();
