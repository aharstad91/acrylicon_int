/**
 * Reference Grid — Client-side filtering
 *
 * Filters .reference-card elements by data-categories, data-products, data-offices.
 * AND between filter groups, OR within a group.
 */
( function () {
	'use strict';

	var container = document.querySelector( '.reference-filters' );
	if ( ! container ) return;

	var cards       = document.querySelectorAll( '.reference-card' );
	var counterEl   = document.querySelector( '.reference-count-visible' );
	var activeClass = 'bg-acryl-dark-blue text-white';
	var inactiveHover = 'hover:bg-gray-100';

	// Track active filters per group: { categories: Set, products: Set, offices: Set }
	var activeFilters = {};

	// Init each group with "all" selected
	container.querySelectorAll( '.filter-group' ).forEach( function ( group ) {
		var taxonomy = group.getAttribute( 'data-filter-taxonomy' );
		activeFilters[ taxonomy ] = new Set( [ 'all' ] );
	} );

	// Delegate click on filter pills
	container.addEventListener( 'click', function ( e ) {
		var pill = e.target.closest( '.filter-pill' );
		if ( ! pill ) return;

		var group    = pill.closest( '.filter-group' );
		var taxonomy = group.getAttribute( 'data-filter-taxonomy' );
		var value    = pill.getAttribute( 'data-filter-value' );

		if ( value === 'all' ) {
			// Reset this group to "all"
			activeFilters[ taxonomy ] = new Set( [ 'all' ] );
		} else {
			// Remove "all" if present
			activeFilters[ taxonomy ].delete( 'all' );

			// Toggle this value
			if ( activeFilters[ taxonomy ].has( value ) ) {
				activeFilters[ taxonomy ].delete( value );
			} else {
				activeFilters[ taxonomy ].add( value );
			}

			// If nothing selected, revert to "all"
			if ( activeFilters[ taxonomy ].size === 0 ) {
				activeFilters[ taxonomy ] = new Set( [ 'all' ] );
			}
		}

		updatePillStyles( group, taxonomy );
		filterCards();
	} );

	function updatePillStyles( group, taxonomy ) {
		var selected = activeFilters[ taxonomy ];
		group.querySelectorAll( '.filter-pill' ).forEach( function ( pill ) {
			var val      = pill.getAttribute( 'data-filter-value' );
			var isActive = selected.has( val );

			// Remove both active and inactive classes, then add the right one
			activeClass.split( ' ' ).forEach( function ( cls ) {
				pill.classList.toggle( cls, isActive );
			} );
			inactiveHover.split( ' ' ).forEach( function ( cls ) {
				pill.classList.toggle( cls, ! isActive );
			} );
		} );
	}

	function filterCards() {
		var visible = 0;

		cards.forEach( function ( card ) {
			var show = true;

			// Check each active filter group (AND between groups)
			for ( var taxonomy in activeFilters ) {
				var selected = activeFilters[ taxonomy ];
				if ( selected.has( 'all' ) ) continue;

				// Get card's values for this taxonomy
				var attr   = card.getAttribute( 'data-' + taxonomy ) || '';
				var values = attr ? attr.split( ',' ) : [];

				// OR within group: card must have at least one matching value
				var match = false;
				selected.forEach( function ( filter ) {
					if ( values.indexOf( filter ) !== -1 ) {
						match = true;
					}
				} );

				if ( ! match ) {
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
