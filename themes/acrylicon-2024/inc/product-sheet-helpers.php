<?php
/**
 * Product Sheet Helpers
 *
 * Parses Gutenberg block content from Produkter CPT posts
 * and extracts structured data for the product sheet template.
 */

/**
 * Parse product post blocks and return structured data for sheet rendering.
 *
 * @param int $post_id The product post ID.
 * @return array Structured product data.
 */
function acrylicon_parse_product_sheet_data( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array();
	}

	$blocks = parse_blocks( $post->post_content );
	$data   = array(
		'title'           => get_the_title( $post_id ),
		'featured_image'  => get_post_thumbnail_id( $post_id ),
		'description'     => '',
		'description_parts' => array(),
		'features'        => array(),
		'technical_info'  => array(),
		'benefits'        => array(),
		'downloads'       => array(),
		'product_excerpt' => get_post_meta( $post_id, 'product_excerpt', true ),
	);

	foreach ( $blocks as $block ) {
		acrylicon_extract_block_data( $block, $data );
	}

	// Combine description parts — skip short tags like "Produkter"
	if ( empty( $data['description'] ) && ! empty( $data['description_parts'] ) ) {
		$long_parts = array_filter( $data['description_parts'], function( $html ) {
			return strlen( trim( wp_strip_all_tags( $html ) ) ) > 50;
		} );
		$data['description'] = implode( "\n", $long_parts );
	}
	unset( $data['description_parts'] );

	return $data;
}

/**
 * Recursively extract data from a block and its inner blocks.
 *
 * @param array $block The parsed block.
 * @param array &$data The data array to populate.
 */
function acrylicon_extract_block_data( $block, &$data ) {
	$name = $block['blockName'] ?? '';

	switch ( $name ) {
		case 'acf/feature-card':
			$features = acrylicon_extract_acf_block_repeater(
				$block,
				'feature_cards_repeater',
				array( 'image', 'title', 'excerpt' )
			);
			if ( $features ) {
				$data['features'] = array_merge( $data['features'], $features );
			}
			break;

		case 'acf/technical-info-table':
			$rows = acrylicon_extract_acf_block_repeater(
				$block,
				'technical_info_repeater',
				array( 'tech_info_name', 'tech_info_desc' )
			);
			if ( $rows ) {
				$data['technical_info'] = array_merge( $data['technical_info'], $rows );
			}
			break;

		case 'acf/download-list':
		case 'acf/download-table':
			$repeater_key = ( $name === 'acf/download-list' ) ? 'download_list_repeater' : 'download_table_repeater';
			$downloads = acrylicon_extract_acf_block_repeater(
				$block,
				$repeater_key,
				array( 'download_name', 'download_link' )
			);
			if ( $downloads ) {
				$data['downloads'] = array_merge( $data['downloads'], $downloads );
			}
			break;

		case 'core/paragraph':
			$html = $block['innerHTML'] ?? '';
			$text = trim( wp_strip_all_tags( $html ) );
			if ( $text ) {
				$data['description_parts'][] = $html;
			}
			break;

		case 'core/list':
			$items = array();
			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $item_block ) {
					if ( ( $item_block['blockName'] ?? '' ) === 'core/list-item' ) {
						$item_text = trim( wp_strip_all_tags( $item_block['innerHTML'] ?? '' ) );
						if ( $item_text ) {
							$items[] = $item_text;
						}
					}
				}
			}
			if ( $items && empty( $data['benefits'] ) ) {
				$data['benefits'] = $items;
			}
			break;
	}

	// Recurse into inner blocks (for groups, columns, etc.)
	if ( ! empty( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $inner ) {
			acrylicon_extract_block_data( $inner, $data );
		}
	}
}

/**
 * Extract repeater data from an ACF block's data attribute.
 *
 * ACF blocks store their field data in $block['attrs']['data'].
 *
 * @param array  $block      The parsed block.
 * @param string $repeater   The repeater field name.
 * @param array  $sub_fields List of sub-field names to extract.
 * @return array Array of row data.
 */
function acrylicon_extract_acf_block_repeater( $block, $repeater, $sub_fields ) {
	$block_data = $block['attrs']['data'] ?? array();
	if ( empty( $block_data ) ) {
		return array();
	}

	$count = intval( $block_data[ $repeater ] ?? 0 );
	if ( $count < 1 ) {
		return array();
	}

	$rows = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$row = array();
		foreach ( $sub_fields as $field ) {
			$key = $repeater . '_' . $i . '_' . $field;
			$row[ $field ] = $block_data[ $key ] ?? '';
		}
		$rows[] = $row;
	}

	return $rows;
}
