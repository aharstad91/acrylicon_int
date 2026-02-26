<?php
/**
 * Module 3: JSON-LD Schema
 *
 * Outputs structured data as JSON-LD in wp_head.
 * Organization schema on homepage only. Other pages reference via @id.
 * BreadcrumbList on all pages.
 */

class Acrylicon_SEO_Schema {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	public function output() {
		if ( is_404() || is_search() ) {
			return;
		}

		$graph = [];

		// Organization — only on front page
		if ( is_front_page() ) {
			$graph[] = $this->get_organization();
			$graph[] = $this->get_website();
		}

		// WebPage (all pages)
		$graph[] = $this->get_webpage();

		// BreadcrumbList (all pages)
		$breadcrumb = $this->get_breadcrumb();
		if ( $breadcrumb ) {
			$graph[] = $breadcrumb;
		}

		// CPT-specific schema
		if ( is_singular() ) {
			$post_type = get_post_type();

			switch ( $post_type ) {
				case 'produkter':
					$product = $this->get_product();
					if ( $product ) {
						$graph[] = $product;
					}
					break;

				case 'kontor':
					$local = $this->get_local_business();
					if ( $local ) {
						$graph[] = $local;
					}
					break;

				case 'bruksomrader':
				case 'industrier':
					$service = $this->get_service();
					if ( $service ) {
						$graph[] = $service;
					}
					break;

				case 'referanser':
					$article = $this->get_article();
					if ( $article ) {
						$graph[] = $article;
					}
					break;
			}
		}

		// CollectionPage for archives
		if ( is_post_type_archive() || is_tax() ) {
			$graph[] = $this->get_collection_page();
		}

		if ( empty( $graph ) ) {
			return;
		}

		$output = [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		];

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
		);
	}

	private function get_site_url() {
		return home_url( '/' );
	}

	private function get_canonical() {
		if ( is_singular() ) {
			return get_permalink();
		}
		if ( is_post_type_archive() ) {
			return get_post_type_archive_link( get_queried_object()->name );
		}
		if ( is_tax() ) {
			$term = get_queried_object();
			return get_term_link( $term );
		}
		return home_url( '/' );
	}

	private function get_organization() {
		$org_data = include ACRYLICON_SEO_DIR . '/data/organization.php';
		$site_url = $this->get_site_url();

		$org_data['@id']  = $site_url . '#organization';
		$org_data['url']  = $site_url;

		// Resolve logo URL
		if ( isset( $org_data['logo']['url'] ) && strpos( $org_data['logo']['url'], '{theme_url}' ) !== false ) {
			$org_data['logo']['url'] = str_replace( '{theme_url}', get_template_directory_uri(), $org_data['logo']['url'] );
		}

		return $org_data;
	}

	private function get_website() {
		$site_url = $this->get_site_url();
		$is_no = ( get_current_blog_id() === 3 );

		return [
			'@type'           => 'WebSite',
			'@id'             => $site_url . '#website',
			'url'             => $site_url,
			'name'            => 'AcryliCon',
			'inLanguage'      => $is_no ? 'nb-NO' : 'en',
			'publisher'       => [ '@id' => $site_url . '#organization' ],
		];
	}

	private function get_webpage() {
		$is_no    = ( get_current_blog_id() === 3 );
		$site_url = $this->get_site_url();

		$page = [
			'@type'       => is_front_page() ? 'WebPage' : 'WebPage',
			'@id'         => $this->get_canonical() . '#webpage',
			'url'         => $this->get_canonical(),
			'name'        => wp_get_document_title(),
			'inLanguage'  => $is_no ? 'nb-NO' : 'en',
			'isPartOf'    => [ '@id' => $site_url . '#website' ],
		];

		if ( is_singular() ) {
			$post = get_post();
			if ( $post ) {
				$page['datePublished'] = get_the_date( 'c', $post );
				$page['dateModified']  = get_the_modified_date( 'c', $post );
			}
		}

		return $page;
	}

	private function get_breadcrumb() {
		$items = [];
		$is_no = ( get_current_blog_id() === 3 );
		$site_url = $this->get_site_url();
		$pos = 1;

		// Home is always first
		$items[] = [
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => 'AcryliCon',
			'item'     => $site_url,
		];

		if ( is_singular() ) {
			$post_type = get_post_type();
			$pt_obj    = get_post_type_object( $post_type );

			// CPT archive as parent (if it has one)
			if ( $pt_obj && $pt_obj->has_archive ) {
				$archive_url = get_post_type_archive_link( $post_type );
				if ( $archive_url ) {
					$items[] = [
						'@type'    => 'ListItem',
						'position' => $pos++,
						'name'     => $pt_obj->labels->name,
						'item'     => $archive_url,
					];
				}
			}

			// Current page (no item URL for last breadcrumb)
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => get_the_title(),
			];
		} elseif ( is_post_type_archive() ) {
			$pt_obj = get_queried_object();
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => $pt_obj->labels->name,
			];
		} elseif ( is_tax() ) {
			$term = get_queried_object();
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => $term->name,
			];
		}

		if ( count( $items ) < 2 && is_front_page() ) {
			return null;
		}

		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => $this->get_canonical() . '#breadcrumb',
			'itemListElement' => $items,
		];
	}

	private function get_product() {
		$post    = get_post();
		$title   = $post->post_title;
		$excerpt = get_field( 'product_excerpt', $post->ID );

		$desc = '';
		if ( ! empty( $excerpt ) ) {
			$desc = wp_strip_all_tags( $excerpt, true );
			$desc = trim( preg_replace( '/\s+/', ' ', $desc ) );
		}

		if ( empty( $title ) ) {
			return null;
		}

		$product = [
			'@type'        => 'Product',
			'@id'          => get_permalink() . '#product',
			'name'         => $title,
			'brand'        => [ '@type' => 'Brand', 'name' => 'AcryliCon' ],
			'manufacturer' => [ '@id' => $this->get_site_url() . '#organization' ],
		];

		if ( ! empty( $desc ) ) {
			$product['description'] = $desc;
		}

		$image = get_the_post_thumbnail_url( $post->ID, 'large' );
		if ( $image ) {
			$product['image'] = $image;
		}

		return $product;
	}

	private function get_local_business() {
		$post    = get_post();
		$title   = $post->post_title;
		$address = get_field( 'office_adress', $post->ID );
		$phone   = get_field( 'office_tel', $post->ID );

		if ( empty( $title ) ) {
			return null;
		}

		$business = [
			'@type'              => 'ProfessionalService',
			'@id'                => get_permalink() . '#localbusiness',
			'name'               => $title,
			'parentOrganization' => [ '@id' => $this->get_site_url() . '#organization' ],
		];

		if ( ! empty( $address ) ) {
			$business['address'] = wp_strip_all_tags( $address, true );
		}

		if ( ! empty( $phone ) ) {
			$business['telephone'] = '+47 ' . wp_strip_all_tags( $phone, true );
		}

		$location = get_field( 'location', $post->ID );
		if ( ! empty( $location['lat'] ) && ! empty( $location['lng'] ) ) {
			$business['geo'] = [
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $location['lat'],
				'longitude' => (float) $location['lng'],
			];
		}

		$image = get_the_post_thumbnail_url( $post->ID, 'large' );
		if ( $image ) {
			$business['image'] = $image;
		}

		return $business;
	}

	private function get_service() {
		$post  = get_post();
		$title = $post->post_title;

		if ( empty( $title ) ) {
			return null;
		}

		$service = [
			'@type'    => 'Service',
			'@id'      => get_permalink() . '#service',
			'name'     => $title,
			'provider' => [ '@id' => $this->get_site_url() . '#organization' ],
		];

		$image = get_the_post_thumbnail_url( $post->ID, 'large' );
		if ( $image ) {
			$service['image'] = $image;
		}

		return $service;
	}

	private function get_article() {
		$post  = get_post();
		$title = $post->post_title;

		if ( empty( $title ) ) {
			return null;
		}

		$article = [
			'@type'         => 'Article',
			'@id'           => get_permalink() . '#article',
			'headline'      => $title,
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
			'publisher'     => [ '@id' => $this->get_site_url() . '#organization' ],
			'isPartOf'      => [ '@id' => get_permalink() . '#webpage' ],
		];

		$image = get_the_post_thumbnail_url( $post->ID, 'large' );
		if ( $image ) {
			$article['image'] = $image;
		}

		return $article;
	}

	private function get_collection_page() {
		return [
			'@type'      => 'CollectionPage',
			'@id'        => $this->get_canonical() . '#collectionpage',
			'url'        => $this->get_canonical(),
			'name'       => wp_get_document_title(),
			'isPartOf'   => [ '@id' => $this->get_site_url() . '#website' ],
		];
	}
}
