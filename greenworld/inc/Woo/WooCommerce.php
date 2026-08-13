<?php
declare( strict_types=1 );

namespace GreenWorld\Woo;

use GreenWorld\Core\Bootable;
use GreenWorld\Customizer\Customizer;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce presentation layer for a premium health store: badges, wishlist,
 * trust signals, delivery estimate, sticky add-to-cart, ingredients / how-to-use
 * tabs and a responsible health disclaimer. No medical claims are invented.
 */
final class WooCommerce implements Bootable {

	public function boot(): void {
		add_action( 'after_setup_theme', [ $this, 'columns' ] );

		// Loop.
		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'badges' ], 9 );
		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'wishlist_button' ], 8 );

		// Single product.
		add_action( 'woocommerce_single_product_summary', [ $this, 'trust_badges' ], 35 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'delivery_estimate' ], 36 );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'product_disclaimer' ], 6 );
		add_action( 'wp_footer', [ $this, 'sticky_atc' ] );

		// Editable product info + tabs.
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'info_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_info' ] );
		add_filter( 'woocommerce_product_tabs', [ $this, 'tabs' ] );

		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_fragments' ] );

		// Clean shop chrome; the theme provides its own wrappers.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		// One sale badge only: drop WooCommerce's default flash; badges() is the single indicator.
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	}

	public function columns(): void {
		add_filter( 'loop_shop_columns', static fn() => 4 );
		add_filter( 'loop_shop_per_page', static fn() => 24 );
	}

	/**
	 * @param array<string,string> $fragments
	 * @return array<string,string>
	 */
	public function cart_fragments( array $fragments ): array {
		$count = ( WC()->cart instanceof \WC_Cart ) ? WC()->cart->get_cart_contents_count() : 0;
		$fragments['span.gw-cart__count'] = '<span class="gw-cart__count">' . esc_html( (string) $count ) . '</span>';
		ob_start();
		woocommerce_mini_cart();
		$fragments['div.gw-minicart__body'] = '<div class="gw-minicart__body">' . (string) ob_get_clean() . '</div>';
		return $fragments;
	}

	public function badges(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		echo '<div class="gw-badges">';
		if ( $product->is_on_sale() ) {
			echo '<span class="gw-badge gw-badge--sale">' . esc_html__( 'Sale', 'greenworld' ) . '</span>';
		}
		$date = $product->get_date_created();
		if ( $date && ( time() - $date->getTimestamp() ) < 30 * DAY_IN_SECONDS ) {
			echo '<span class="gw-badge gw-badge--new">' . esc_html__( 'New', 'greenworld' ) . '</span>';
		}
		if ( ! $product->is_in_stock() ) {
			echo '<span class="gw-badge gw-badge--oos">' . esc_html__( 'Out of stock', 'greenworld' ) . '</span>';
		}
		echo '</div>';
	}

	public function wishlist_button(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		printf(
			'<button class="gw-wish" type="button" data-gw-wishlist="%d" aria-label="%s" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21S4 14.5 4 8.8A4.2 4.2 0 0 1 12 6a4.2 4.2 0 0 1 8 2.8C20 14.5 12 21 12 21Z"/></svg></button>',
			(int) $product->get_id(),
			esc_attr__( 'Add to wishlist', 'greenworld' )
		);
	}

	public function trust_badges(): void {
		$badges = [
			__( 'Quality health & wellness products', 'greenworld' ),
			__( 'Secure payment: M-Pesa, bank transfer or cash on delivery', 'greenworld' ),
			__( 'Discreet delivery across Kenya', 'greenworld' ),
		];
		echo '<ul class="gw-ptrust" aria-label="' . esc_attr__( 'Store guarantees', 'greenworld' ) . '">';
		foreach ( $badges as $b ) {
			echo '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>' . esc_html( $b ) . '</li>';
		}
		echo '</ul>';
	}

	public function delivery_estimate(): void {
		$text = (string) get_theme_mod( 'gw_delivery_note', __( 'Reliable delivery across Kenya. Nairobi same/next day; countrywide in 1–4 business days.', 'greenworld' ) );
		if ( '' === trim( $text ) ) {
			return;
		}
		echo '<p class="gw-delivery"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="2"/><circle cx="18" cy="17" r="2"/></svg>' . esc_html( $text ) . '</p>';
	}

	public function product_disclaimer(): void {
		$text = Customizer::val( 'gw_default_disclaimer' );
		if ( '' === $text ) {
			return;
		}
		echo '<div class="gw-product-disclaimer"><p>' . esc_html( $text ) . '</p></div>';
	}

	public function info_fields(): void {
		echo '<div class="options_group">';
		woocommerce_wp_textarea_input( array(
			'id'          => '_gw_ingredients',
			'label'       => __( 'Ingredients / Composition', 'greenworld' ),
			'description' => __( 'Shown as a product tab. Enter only accurate, supplied information.', 'greenworld' ),
			'desc_tip'    => true,
		) );
		woocommerce_wp_textarea_input( array(
			'id'          => '_gw_howtouse',
			'label'       => __( 'How to Use', 'greenworld' ),
			'description' => __( 'Directions for use as provided on the product/label. Shown as a product tab.', 'greenworld' ),
			'desc_tip'    => true,
		) );
		echo '</div>';
	}

	public function save_info( $post_id ): void {
		$pid = (int) $post_id;
		if ( 0 === $pid ) {
			return;
		}
		foreach ( [ '_gw_ingredients', '_gw_howtouse' ] as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $pid, $key, sanitize_textarea_field( wp_unslash( (string) $_POST[ $key ] ) ) );
			} else {
				delete_post_meta( $pid, $key );
			}
		}
	}

	/**
	 * @param array<string,array<string,mixed>> $tabs
	 * @return array<string,array<string,mixed>>
	 */
	public function tabs( array $tabs ): array {
		global $product;
		if ( $product instanceof \WC_Product ) {
			$ingredients = trim( (string) get_post_meta( $product->get_id(), '_gw_ingredients', true ) );
			$howto       = trim( (string) get_post_meta( $product->get_id(), '_gw_howtouse', true ) );
			if ( $ingredients !== '' ) {
				$tabs['gw_ingredients'] = [
					'title'    => __( 'Ingredients', 'greenworld' ),
					'priority' => 22,
					'callback' => static function () use ( $ingredients ): void {
						echo '<h2>' . esc_html__( 'Ingredients / Composition', 'greenworld' ) . '</h2>';
						echo wp_kses_post( wpautop( $ingredients ) );
					},
				];
			}
			if ( $howto !== '' ) {
				$tabs['gw_howtouse'] = [
					'title'    => __( 'How to Use', 'greenworld' ),
					'priority' => 24,
					'callback' => static function () use ( $howto ): void {
						echo '<h2>' . esc_html__( 'How to Use', 'greenworld' ) . '</h2>';
						echo wp_kses_post( wpautop( $howto ) );
					},
				];
			}
		}
		$tabs['gw_delivery'] = [
			'title'    => __( 'Delivery', 'greenworld' ),
			'priority' => 30,
			'callback' => static function (): void {
				echo '<h2>' . esc_html__( 'Delivery Information', 'greenworld' ) . '</h2>';
				echo '<p>' . esc_html( (string) get_theme_mod( 'gw_delivery_note', __( 'Reliable delivery across Kenya. Nairobi same/next day; countrywide in 1–4 business days. Pay by M-Pesa, bank transfer or cash on delivery.', 'greenworld' ) ) ) . '</p>';
			},
		];
		return $tabs;
	}

	public function sticky_atc(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		printf(
			'<div class="gw-sticky-atc" role="region" aria-label="%1$s"><span class="gw-sticky-atc__name">%2$s</span><span class="gw-sticky-atc__price">%3$s</span><a class="gw-sticky-atc__btn button" href="#" data-add-to-cart="%4$d">%5$s</a></div>',
			esc_attr__( 'Add to cart', 'greenworld' ),
			esc_html( $product->get_name() ),
			wp_kses_post( $product->get_price_html() ),
			(int) $product->get_id(),
			esc_html__( 'Add to Cart', 'greenworld' )
		);
	}
}
