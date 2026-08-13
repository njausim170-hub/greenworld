<?php
declare( strict_types=1 );

namespace GreenWorld\Seo;

use GreenWorld\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * One authoritative JSON-LD knowledge graph for Green World Health Solutions.
 *
 * Emits a single connected @graph keyed by stable @id references:
 *   OnlineStore (#organization) + logo (#logo) + WebSite (#website) sitewide,
 *   plus a context-appropriate WebPage subtype (#webpage), BreadcrumbList
 *   (#breadcrumb), and Product / ItemList / Article / FAQPage entities that
 *   link back to the organization and website.
 *
 * Yields entirely to Yoast / Rank Math when either is active so the final HTML
 * carries exactly one Organization / WebSite / Product / Breadcrumb graph.
 * Override with the `greenworld_force_schema` filter.
 */
final class Schema implements Bootable {

	public function boot(): void {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	private function seo_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
	}

	public function output(): void {
		if ( true === (bool) apply_filters( 'greenworld_disable_schema', false ) ) {
			return;
		}
		if ( $this->seo_plugin_active() && false === (bool) apply_filters( 'greenworld_force_schema', false ) ) {
			return; // A dedicated SEO plugin owns the graph; do not duplicate entities.
		}

		$graph = [ $this->organization(), $this->logo_object(), $this->website() ];

		$page = $this->webpage();
		if ( null !== $page ) {
			$graph[] = $page;
		}
		$crumb = $this->breadcrumbs();
		if ( null !== $crumb ) {
			$graph[] = $crumb;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			$product = $this->product_schema();
			if ( null !== $product ) {
				$graph[] = $product;
			}
		} elseif ( ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_shop' ) && is_shop() ) ) {
			$list = $this->collection_list();
			if ( null !== $list ) {
				$graph[] = $list;
			}
		} elseif ( is_singular( 'post' ) ) {
			$graph[] = $this->article();
		}

		$faq = $this->faq_entities();
		if ( count( $faq ) > 0 ) {
			// Attach visible FAQ Q&A to the current WebPage as mainEntity.
			foreach ( $graph as $i => $node ) {
				if ( isset( $node['@id'] ) && $node['@id'] === $this->id( '#webpage' ) ) {
					$graph[ $i ]['@type']      = $this->page_is( 'faq' ) ? 'FAQPage' : $node['@type'];
					$graph[ $i ]['mainEntity'] = $faq;
				}
			}
		}

		$data = [
			'@context' => 'https://schema.org',
			'@graph'   => array_values( array_filter( $graph ) ),
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/* --------------------------------------------------------------------- */
	/* Sitewide entities                                                     */
	/* --------------------------------------------------------------------- */

	private function organization(): array {
		$org = [
			'@type'              => 'OnlineStore',
			'@id'                => $this->id( '#organization', true ),
			'name'               => get_bloginfo( 'name' ),
			'alternateName'      => apply_filters( 'greenworld_org_alternate_names', [ 'Green World Health', 'Green World Health Solutions Kenya' ] ),
			'url'                => home_url( '/' ),
			'logo'               => [ '@id' => $this->id( '#logo', true ) ],
			'image'              => [ '@id' => $this->id( '#logo', true ) ],
			'description'        => $this->org_description(),
			'telephone'          => get_option( 'greenworld_phone', '+254723579873' ),
			'email'              => get_option( 'greenworld_email', 'info@greenworldheath.com' ),
			'address'            => $this->postal_address(),
			'areaServed'         => [ '@type' => 'Country', 'name' => 'Kenya' ],
			'currenciesAccepted' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'KES',
			'paymentAccepted'    => 'M-Pesa, Cash on Delivery, Bank Transfer',
			'priceRange'         => apply_filters( 'greenworld_price_range', 'KES' ),
			'contactPoint'       => [
				'@type'             => 'ContactPoint',
				'telephone'         => get_option( 'greenworld_phone', '+254723579873' ),
				'email'             => get_option( 'greenworld_email', 'info@greenworldheath.com' ),
				'contactType'       => 'customer service',
				'areaServed'        => 'KE',
				'availableLanguage' => [ 'en', 'sw' ],
			],
			'knowsAbout'         => apply_filters( 'greenworld_knows_about', [
				'Health and wellness products',
				'Natural health products',
				'Nutritional supplements',
				'Herbal products',
				'Healthy living',
			] ),
		];
		$hours = $this->opening_hours();
		if ( count( $hours ) > 0 ) {
			$org['openingHoursSpecification'] = $hours;
		}
		$same = $this->social_links();
		if ( count( $same ) > 0 ) {
			$org['sameAs'] = $same;
		}
		return $org;
	}

	private function logo_object(): array {
		$url = $this->logo_url();
		return array_filter( [
			'@type'      => 'ImageObject',
			'@id'        => $this->id( '#logo', true ),
			'url'        => $url,
			'contentUrl' => $url,
			'caption'    => get_bloginfo( 'name' ),
		] );
	}

	private function website(): array {
		return [
			'@type'           => 'WebSite',
			'@id'             => $this->id( '#website', true ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'description'     => (string) get_bloginfo( 'description' ),
			'publisher'       => [ '@id' => $this->id( '#organization', true ) ],
			'inLanguage'      => $this->lang(),
			'potentialAction' => [
				'@type'       => 'SearchAction',
				'target'      => [
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				],
				'query-input' => 'required name=search_term_string',
			],
		];
	}

	/* --------------------------------------------------------------------- */
	/* Per-page WebPage node                                                 */
	/* --------------------------------------------------------------------- */

	private function webpage(): ?array {
		$url  = $this->current_url();
		$type = $this->webpage_type();
		$node = [
			'@type'      => $type,
			'@id'        => $this->id( '#webpage' ),
			'url'        => $url,
			'name'       => $this->page_name(),
			'isPartOf'   => [ '@id' => $this->id( '#website', true ) ],
			'inLanguage' => $this->lang(),
		];
		if ( is_front_page() ) {
			$node['about'] = [ '@id' => $this->id( '#organization', true ) ];
		}
		$img = $this->page_image();
		if ( '' !== $img ) {
			$node['primaryImageOfPage'] = [ '@type' => 'ImageObject', 'url' => $img ];
		}
		if ( is_singular() ) {
			$obj = get_queried_object();
			if ( $obj instanceof \WP_Post ) {
				$node['datePublished'] = get_the_date( 'c', $obj );
				$node['dateModified']  = get_the_modified_date( 'c', $obj );
			}
		}
		return $node;
	}

	private function webpage_type(): string {
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'ItemPage';
		}
		if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_category' ) && is_product_category() ) || is_post_type_archive() || is_archive() ) {
			return 'CollectionPage';
		}
		if ( $this->page_is( 'about-us' ) || $this->page_is( 'about' ) ) {
			return 'AboutPage';
		}
		if ( $this->page_is( 'contact-us' ) || $this->page_is( 'contact' ) ) {
			return 'ContactPage';
		}
		if ( $this->page_is( 'faq' ) ) {
			return 'FAQPage';
		}
		if ( is_search() ) {
			return 'SearchResultsPage';
		}
		return 'WebPage';
	}

	/* --------------------------------------------------------------------- */
	/* Product                                                               */
	/* --------------------------------------------------------------------- */

	private function product_schema(): ?array {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}
		$pid   = $product->get_id();
		$brand = $this->product_brand( $product );
		$gtin  = (string) get_post_meta( $pid, '_greenworld_gtin', true );
		$mpn   = (string) get_post_meta( $pid, '_greenworld_mpn', true );
		if ( strlen( $mpn ) === 0 ) {
			$mpn = (string) $product->get_sku();
		}

		$schema = [
			'@type'           => $product->is_type( 'variable' ) ? 'ProductGroup' : 'Product',
			'@id'             => get_permalink( $pid ) . '#product',
			'name'            => $product->get_name(),
			'description'     => $this->clean( $product->get_short_description() ?: $product->get_description() ),
			'sku'             => $product->get_sku(),
			'image'           => $this->product_images( $product ),
			'url'             => get_permalink( $pid ),
			'mainEntityOfPage'=> [ '@id' => $this->id( '#webpage' ) ],
		];
		if ( strlen( $brand ) > 0 ) {
			$schema['brand'] = [ '@type' => 'Brand', 'name' => $brand ];
		}
		$cat = $this->primary_category_name( $pid );
		if ( '' !== $cat ) {
			$schema['category'] = $cat;
		}
		if ( strlen( $gtin ) > 0 ) {
			$schema['gtin'] = $gtin;
		}
		if ( strlen( $mpn ) > 0 ) {
			$schema['mpn'] = $mpn;
		}
		$props = $this->additional_properties( $product );
		if ( count( $props ) > 0 ) {
			$schema['additionalProperty'] = $props;
		}
		$related = $this->related_refs( $product );
		if ( count( $related ) > 0 ) {
			$schema['isRelatedTo'] = $related;
		}
		if ( $product->is_type( 'variable' ) ) {
			$schema['hasVariant'] = $this->variant_nodes( $product );
			$schema['offers']     = $this->build_offers( $product );
		} else {
			$schema['offers'] = $this->build_offers( $product );
		}
		if ( $product->get_review_count() > 0 && 'yes' === get_option( 'woocommerce_enable_review_rating' ) ) {
			$schema['aggregateRating'] = [
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $product->get_average_rating(),
				'reviewCount' => (int) $product->get_review_count(),
			];
			$reviews = $this->real_reviews( $pid );
			if ( count( $reviews ) > 0 ) {
				$schema['review'] = $reviews;
			}
		}
		return $schema;
	}

	private function variant_nodes( \WC_Product $product ): array {
		$out = [];
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v instanceof \WC_Product ) {
				continue;
			}
			$out[] = array_filter( [
				'@type'  => 'Product',
				'@id'    => get_permalink( $product->get_id() ) . '#variant-' . $vid,
				'name'   => $v->get_name(),
				'sku'    => $v->get_sku(),
				'image'  => wp_get_attachment_image_url( (int) $v->get_image_id(), 'full' ) ?: null,
				'offers' => [
					'@type'         => 'Offer',
					'priceCurrency' => get_woocommerce_currency(),
					'price'         => $v->get_price(),
					'availability'  => $v->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
					'url'           => get_permalink( $product->get_id() ),
					'itemCondition' => 'https://schema.org/NewCondition',
					'seller'        => [ '@id' => $this->id( '#organization', true ) ],
				],
			] );
		}
		return $out;
	}

	private function build_offers( \WC_Product $product ): array {
		$currency = get_woocommerce_currency();
		$avail    = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
		$url      = get_permalink( $product->get_id() );
		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );
			$list   = ( isset( $prices['price'] ) && is_array( $prices['price'] ) ) ? array_values( $prices['price'] ) : [];
			if ( count( $list ) > 0 ) {
				return [
					'@type'         => 'AggregateOffer',
					'priceCurrency' => $currency,
					'lowPrice'      => (string) min( $list ),
					'highPrice'     => (string) max( $list ),
					'offerCount'    => count( $list ),
					'availability'  => $avail,
					'url'           => $url,
					'seller'        => [ '@id' => $this->id( '#organization', true ) ],
				];
			}
		}
		$offer = [
			'@type'                   => 'Offer',
			'priceCurrency'           => $currency,
			'price'                   => $product->get_price(),
			'priceValidUntil'         => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'itemCondition'           => 'https://schema.org/NewCondition',
			'availability'            => $avail,
			'url'                     => $url,
			'seller'                  => [ '@id' => $this->id( '#organization', true ) ],
			'hasMerchantReturnPolicy' => $this->return_policy(),
		];
		$sd = $this->shipping_details();
		if ( count( $sd ) > 0 ) {
			$offer['shippingDetails'] = $sd;
		}
		return $offer;
	}

	private function product_brand( \WC_Product $product ): string {
		$brand = (string) get_post_meta( $product->get_id(), '_greenworld_brand', true );
		if ( strlen( $brand ) === 0 ) {
			$attr  = (string) $product->get_attribute( 'brand' );
			$brand = strlen( $attr ) > 0 ? $attr : (string) $product->get_attribute( 'pa_brand' );
		}
		return $brand;
	}

	private function additional_properties( \WC_Product $product ): array {
		$out = [];
		foreach ( $product->get_attributes() as $attr ) {
			if ( ! $attr instanceof \WC_Product_Attribute || ! $attr->get_visible() || $attr->get_variation() ) {
				continue;
			}
			$name   = wc_attribute_label( $attr->get_name() );
			$values = $product->get_attribute( $attr->get_name() );
			if ( '' === $name || '' === $values ) {
				continue;
			}
			$out[] = [ '@type' => 'PropertyValue', 'name' => $name, 'value' => $values ];
		}
		return $out;
	}

	private function related_refs( \WC_Product $product ): array {
		if ( ! function_exists( 'wc_get_related_products' ) ) {
			return [];
		}
		$ids = wc_get_related_products( $product->get_id(), 4 );
		$out = [];
		foreach ( $ids as $rid ) {
			$out[] = [ '@type' => 'Product', '@id' => get_permalink( (int) $rid ) . '#product', 'url' => get_permalink( (int) $rid ), 'name' => get_the_title( (int) $rid ) ];
		}
		return $out;
	}

	private function real_reviews( int $pid ): array {
		$comments = get_comments( [ 'post_id' => $pid, 'status' => 'approve', 'type' => 'review', 'number' => 3 ] );
		$out      = [];
		foreach ( (array) $comments as $c ) {
			$rating = (int) get_comment_meta( $c->comment_ID, 'rating', true );
			if ( $rating <= 0 ) {
				continue;
			}
			$out[] = [
				'@type'         => 'Review',
				'reviewRating'  => [ '@type' => 'Rating', 'ratingValue' => (string) $rating, 'bestRating' => '5' ],
				'author'        => [ '@type' => 'Person', 'name' => $c->comment_author ],
				'datePublished' => gmdate( 'Y-m-d', strtotime( $c->comment_date ) ),
				'reviewBody'    => $this->clean( $c->comment_content ),
			];
		}
		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* Collection ItemList + Article + FAQ                                   */
	/* --------------------------------------------------------------------- */

	private function collection_list(): ?array {
		global $wp_query;
		if ( ! isset( $wp_query->posts ) || ! is_array( $wp_query->posts ) ) {
			return null;
		}
		$items = [];
		$pos   = 1;
		foreach ( $wp_query->posts as $p ) {
			if ( ! $p instanceof \WP_Post ) {
				continue;
			}
			$items[] = [ '@type' => 'ListItem', 'position' => $pos++, 'url' => get_permalink( $p->ID ), 'name' => get_the_title( $p->ID ) ];
			if ( $pos > 30 ) {
				break;
			}
		}
		if ( count( $items ) === 0 ) {
			return null;
		}
		return [
			'@type'           => 'ItemList',
			'@id'             => $this->id( '#itemlist' ),
			'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		];
	}

	private function article(): array {
		$obj = get_queried_object();
		$img = $this->page_image();
		return array_filter( [
			'@type'            => 'Article',
			'@id'              => $this->id( '#article' ),
			'headline'         => get_the_title(),
			'description'      => $this->clean( get_the_excerpt() ),
			'image'            => '' !== $img ? $img : null,
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => [ '@type' => 'Organization', '@id' => $this->id( '#organization', true ) ],
			'publisher'        => [ '@id' => $this->id( '#organization', true ) ],
			'mainEntityOfPage' => [ '@id' => $this->id( '#webpage' ) ],
			'inLanguage'       => $this->lang(),
		] );
	}

	/**
	 * Parse visible <h3>Question</h3><p>Answer</p> pairs from the current
	 * page content. Returns Question nodes only when at least two are found so
	 * FAQ markup never ships without matching visible content.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function faq_entities(): array {
		if ( ! is_singular() ) {
			return [];
		}
		$obj = get_queried_object();
		if ( ! $obj instanceof \WP_Post ) {
			return [];
		}
		$html = (string) $obj->post_content;
		if ( false === stripos( $html, '<h3' ) ) {
			return [];
		}
		if ( ! preg_match_all( '/<h3[^>]*>(.*?)<\/h3>\s*(.*?)(?=<h3|\z)/is', $html, $m, PREG_SET_ORDER ) ) {
			return [];
		}
		$out = [];
		foreach ( $m as $pair ) {
			$q = trim( wp_strip_all_tags( $pair[1] ) );
			$a = trim( wp_strip_all_tags( $pair[2] ) );
			if ( strlen( $q ) < 3 || strlen( $a ) < 3 ) {
				continue;
			}
			$out[] = [
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $a ],
			];
		}
		return count( $out ) >= 2 ? $out : [];
	}

	/* --------------------------------------------------------------------- */
	/* Breadcrumbs                                                           */
	/* --------------------------------------------------------------------- */

	private function crumb( int $pos, string $name, string $url ): array {
		return [ '@type' => 'ListItem', 'position' => $pos, 'name' => $name, 'item' => $url ];
	}

	private function term_chain( \WP_Term $term, int &$pos, array &$items ): void {
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $aid ) {
			$anc = get_term( (int) $aid, 'product_cat' );
			if ( $anc instanceof \WP_Term ) {
				$link = get_term_link( $anc );
				if ( ! is_wp_error( $link ) ) {
					$items[] = $this->crumb( $pos++, $anc->name, (string) $link );
				}
			}
		}
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$items[] = $this->crumb( $pos++, $term->name, (string) $link );
		}
	}

	private function breadcrumbs(): ?array {
		if ( is_front_page() ) {
			return null;
		}
		$pos   = 1;
		$items = [ $this->crumb( $pos++, __( 'Home', 'greenworld' ), home_url( '/' ) ) ];

		if ( function_exists( 'is_product' ) && is_product() ) {
			$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
			$items[] = $this->crumb( $pos++, __( 'Shop', 'greenworld' ), (string) $shop );
			$terms   = get_the_terms( get_the_ID(), 'product_cat' );
			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$this->term_chain( $terms[0], $pos, $items );
			}
			$items[] = $this->crumb( $pos++, get_the_title(), (string) get_permalink() );
		} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
			$items[] = $this->crumb( $pos++, __( 'Shop', 'greenworld' ), (string) $shop );
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$this->term_chain( $term, $pos, $items );
			}
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$items[] = $this->crumb( $pos++, __( 'Shop', 'greenworld' ), (string) wc_get_page_permalink( 'shop' ) );
		} elseif ( is_singular() ) {
			$obj = get_queried_object();
			if ( $obj instanceof \WP_Post && $obj->post_parent > 0 ) {
				foreach ( array_reverse( get_post_ancestors( $obj ) ) as $anc ) {
					$items[] = $this->crumb( $pos++, get_the_title( $anc ), (string) get_permalink( $anc ) );
				}
			}
			$items[] = $this->crumb( $pos++, get_the_title(), $this->current_url() );
		} else {
			return null;
		}

		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => $this->id( '#breadcrumb' ),
			'itemListElement' => $items,
		];
	}

	/* --------------------------------------------------------------------- */
	/* Shared helpers                                                        */
	/* --------------------------------------------------------------------- */

	private function id( string $fragment, bool $home = false ): string {
		$base = $home ? home_url( '/' ) : $this->current_url();
		return $base . ltrim( $fragment, '/' );
	}

	private function current_url(): string {
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( ( function_exists( 'is_shop' ) && is_shop() ) ) {
			return (string) wc_get_page_permalink( 'shop' );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$t = get_queried_object();
			if ( $t instanceof \WP_Term ) {
				$l = get_term_link( $t );
				if ( ! is_wp_error( $l ) ) {
					return (string) $l;
				}
			}
		}
		if ( is_front_page() || is_home() ) {
			return home_url( '/' );
		}
		return home_url( add_query_arg( [], $GLOBALS['wp']->request ? '/' . $GLOBALS['wp']->request . '/' : '/' ) );
	}

	private function page_name(): string {
		$t = wp_get_document_title();
		return is_string( $t ) ? $t : (string) get_bloginfo( 'name' );
	}

	private function page_image(): string {
		if ( is_singular() && has_post_thumbnail() ) {
			return (string) get_the_post_thumbnail_url( null, 'large' );
		}
		return $this->logo_url();
	}

	private function page_is( string $slug ): bool {
		return is_page( $slug ) || ( is_singular() && get_post_field( 'post_name', get_queried_object_id() ) === $slug );
	}

	private function primary_category_name( int $pid ): string {
		$terms = get_the_terms( $pid, 'product_cat' );
		if ( is_array( $terms ) && isset( $terms[0] ) && $terms[0] instanceof \WP_Term ) {
			return $terms[0]->name;
		}
		return '';
	}

	private function lang(): string {
		$l = str_replace( '_', '-', (string) get_locale() );
		return '' !== $l ? $l : 'en-KE';
	}

	private function clean( string $s ): string {
		$s = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $s ) ) );
		return $s;
	}

	private function org_description(): string {
		$d = trim( (string) get_bloginfo( 'description' ) );
		if ( '' !== $d ) {
			return $d;
		}
		return 'Green World Health Solutions is a Kenyan health and wellness company offering carefully selected natural health and wellness products with delivery across Kenya.';
	}

	private function postal_address(): array {
		return [
			'@type'           => 'PostalAddress',
			'streetAddress'   => get_option( 'greenworld_street', 'Development House, 11th Floor, Room 7' ),
			'addressLocality' => get_option( 'greenworld_city', 'Nairobi' ),
			'addressCountry'  => 'KE',
		];
	}

	private function opening_hours(): array {
		$hours = apply_filters( 'greenworld_opening_hours', [
			[ 'days' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ], 'opens' => '08:30', 'closes' => '18:00' ],
		] );
		$spec = [];
		foreach ( (array) $hours as $h ) {
			if ( ! isset( $h['days'], $h['opens'], $h['closes'] ) ) {
				continue;
			}
			$spec[] = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $h['days'],
				'opens'     => $h['opens'],
				'closes'    => $h['closes'],
			];
		}
		return $spec;
	}

	private function logo_url(): string {
		$id = (int) get_theme_mod( 'custom_logo' );
		if ( $id > 0 ) {
			$u = wp_get_attachment_url( $id );
			if ( is_string( $u ) ) {
				return $u;
			}
		}
		return trailingslashit( get_template_directory_uri() ) . 'assets/img/logo-badge.png';
	}

	/** @return array<int,string> */
	private function social_links(): array {
		$links = apply_filters( 'greenworld_social_profiles', [] );
		return is_array( $links ) ? array_values( array_filter( array_map( 'esc_url_raw', $links ) ) ) : [];
	}

	/** @return array<int,string> */
	private function product_images( \WC_Product $product ): array {
		$ids  = array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() );
		$urls = [];
		foreach ( $ids as $iid ) {
			$u = wp_get_attachment_image_url( (int) $iid, 'full' );
			if ( is_string( $u ) && strlen( $u ) > 0 ) {
				$urls[] = $u;
			}
		}
		if ( count( $urls ) === 0 && function_exists( 'wc_placeholder_img_src' ) ) {
			$ph = wc_placeholder_img_src( 'full' );
			if ( is_string( $ph ) ) {
				$urls[] = $ph;
			}
		}
		return $urls;
	}

	/** @return array<string,mixed> */
	private function return_policy(): array {
		return apply_filters( 'greenworld_return_policy', [
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'KE',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays'   => 7,
			'returnMethod'         => 'https://schema.org/ReturnByMail',
			'returnFees'           => 'https://schema.org/FreeReturn',
		] );
	}

	/** @return array<string,mixed> */
	private function shipping_details(): array {
		$rate = trim( (string) get_option( 'greenworld_flat_shipping', '' ) );
		if ( '' === $rate ) {
			return [];
		}
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'KES';
		return apply_filters( 'greenworld_shipping_details', [
			'@type'               => 'OfferShippingDetails',
			'shippingRate'        => [ '@type' => 'MonetaryAmount', 'value' => $rate, 'currency' => $currency ],
			'shippingDestination' => [ '@type' => 'DefinedRegion', 'addressCountry' => 'KE' ],
			'deliveryTime'        => [
				'@type'        => 'ShippingDeliveryTime',
				'handlingTime' => [ '@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY' ],
				'transitTime'  => [ '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 4, 'unitCode' => 'DAY' ],
			],
		] );
	}
}
