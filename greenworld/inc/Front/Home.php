<?php
declare( strict_types=1 );

namespace GreenWorld\Front;

defined( 'ABSPATH' ) || exit;

use GreenWorld\Customizer\Customizer;
use GreenWorld\Support\Cache;

/**
 * Homepage section renderer. Pulls live WooCommerce data with graceful,
 * premium fallbacks so the page never looks empty before products exist.
 * Fewer, better sections - generous whitespace, editorial hierarchy.
 */
final class Home {

	private static function shop(): string {
		return function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	}

	/** @return array<int,int> */
	private static function product_ids( array $args ): array {
		$defaults = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 8, 'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true );
		$q = new \WP_Query( array_merge( $defaults, $args ) );
		return array_map( 'intval', (array) $q->posts );
	}

	private static function render_products( array $ids, int $cols = 4 ): void {
		if ( count( $ids ) === 0 ) {
			return;
		}
		echo do_shortcode( sprintf( '[products ids="%s" columns="%d" limit="%d"]', esc_attr( implode( ',', array_map( 'strval', $ids ) ) ), $cols, count( $ids ) ) );
	}

	private static function section_head( string $eyebrow, string $title, string $link = '', string $link_text = '' ): void {
		echo '<div class="gw-sec__head">';
		echo '<div class="gw-sec__heads">';
		if ( $eyebrow !== '' ) { echo '<span class="gw-eyebrow">' . esc_html( $eyebrow ) . '</span>'; }
		echo '<h2 class="gw-sec__title">' . esc_html( $title ) . '</h2>';
		echo '</div>';
		if ( $link !== '' && $link_text !== '' ) {
			echo '<a class="gw-sec__more" href="' . esc_url( $link ) . '">' . esc_html( $link_text ) . '</a>';
		}
		echo '</div>';
	}

	/* --------------------------------------------------------------------- */

	public static function hero(): void {
		$shop  = self::shop();
		$image = (string) get_theme_mod( 'gw_hero_image', GREENWORLD_URI . 'assets/img/hero.jpg' );
		$eye   = (string) get_theme_mod( 'gw_hero_eyebrow', __( 'Trusted health & wellness in Kenya', 'greenworld' ) );
		$title = (string) get_theme_mod( 'gw_hero_title', __( 'Your Health. Your Wellness. Your Better Tomorrow.', 'greenworld' ) );
		$sub   = (string) get_theme_mod( 'gw_hero_sub', __( 'Discover carefully selected health and wellness products designed to support healthier everyday living.', 'greenworld' ) );
		$style = ( $image !== '' ) ? ' style="background-image:linear-gradient(90deg, rgba(18,55,38,.78), rgba(18,55,38,.28)), url(' . esc_url( $image ) . ')"' : '';
		?>
		<section class="gw-hero"<?php echo $style; // phpcs:ignore ?> aria-label="<?php esc_attr_e( 'Featured', 'greenworld' ); ?>">
			<div class="gw-container gw-hero__inner">
				<div class="gw-hero__copy">
					<span class="gw-hero__eyebrow"><?php echo esc_html( $eye ); ?></span>
					<h1 class="gw-hero__title"><?php echo esc_html( $title ); ?></h1>
					<p class="gw-hero__sub"><?php echo esc_html( $sub ); ?></p>
					<div class="gw-hero__cta">
						<a class="button gw-btn--gold" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Shop Health Products', 'greenworld' ); ?></a>
						<a class="button button-ghost gw-btn--onhero" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Explore Categories', 'greenworld' ); ?></a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	public static function trust_strip(): void {
		echo '<section class="gw-trust gw-trust--hero" aria-label="' . esc_attr__( 'Our promise', 'greenworld' ) . '"><div class="gw-container">';
		echo do_shortcode( '[gw_trust_badges]' );
		echo '</div></section>';
	}

	public static function shop_by_category(): void {
		$shop  = self::shop();
		$terms = array();
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = Cache::remember( 'gw_home_cats_10', 6 * HOUR_IN_SECONDS, static function () {
				$r = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 10, 'orderby' => 'count', 'order' => 'DESC', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
				return is_array( $r ) ? $r : array();
			} );
		}
		echo '<section class="gw-section gw-cats"><div class="gw-container">';
		self::section_head( __( 'Shop by need', 'greenworld' ), __( 'Shop by Health Category', 'greenworld' ), $shop, __( 'View all', 'greenworld' ) );
		echo '<div class="gw-cats__grid">';
		if ( count( $terms ) > 0 ) {
			foreach ( $terms as $t ) {
				$link  = get_term_link( $t );
				if ( is_wp_error( $link ) ) { continue; }
				$thumb = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
				$media = $thumb ? wp_get_attachment_image( $thumb, 'medium', false, array( 'loading' => 'lazy', 'class' => 'gw-cat-card__img' ) ) : self::placeholder( (string) $t->name );
				printf(
					'<a class="gw-cat-card" href="%s"><span class="gw-cat-card__media">%s</span><span class="gw-cat-card__body"><span class="gw-cat-card__name">%s</span><span class="gw-cat-card__count">%d %s</span></span></a>',
					esc_url( (string) $link ), $media, esc_html( $t->name ), (int) $t->count, esc_html__( 'products', 'greenworld' )
				);
			}
		} else {
			foreach ( \greenworld_health_categories() as $name ) {
				$url = add_query_arg( array( 's' => rawurlencode( $name ), 'post_type' => 'product' ), home_url( '/' ) );
				printf(
					'<a class="gw-cat-card" href="%s"><span class="gw-cat-card__media">%s</span><span class="gw-cat-card__body"><span class="gw-cat-card__name">%s</span><span class="gw-cat-card__count">%s</span></span></a>',
					esc_url( $url ), self::placeholder( $name ), esc_html( $name ), esc_html__( 'Explore', 'greenworld' )
				);
			}
		}
		echo '</div></div></section>';
	}

	private static function placeholder( string $name ): string {
		$initial = function_exists( 'mb_substr' ) ? mb_substr( trim( $name ), 0, 1 ) : substr( trim( $name ), 0, 1 );
		return '<span class="gw-ph" aria-hidden="true"><span>' . esc_html( strtoupper( $initial ) ) . '</span></span>';
	}

	public static function featured_products(): void {
		$ids = self::product_ids( array( 'posts_per_page' => 8, 'tax_query' => array( array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured', 'operator' => 'IN' ) ) ) );
		if ( count( $ids ) === 0 ) {
			$ids = self::product_ids( array( 'posts_per_page' => 8, 'orderby' => 'date' ) );
		}
		if ( count( $ids ) === 0 ) { return; }
		echo '<section class="gw-section"><div class="gw-container">';
		self::section_head( __( 'Handpicked', 'greenworld' ), __( 'Featured Health & Wellness Products', 'greenworld' ), self::shop(), __( 'Shop all', 'greenworld' ) );
		self::render_products( $ids, 4 );
		echo '</div></section>';
	}

	public static function best_sellers(): void {
		$ids = self::product_ids( array( 'posts_per_page' => 8, 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ) );
		// Drop products with zero sales to avoid a meaningless "best sellers" row.
		$ids = array_values( array_filter( $ids, static function ( $id ) { return (int) get_post_meta( (int) $id, 'total_sales', true ) > 0; } ) );
		if ( count( $ids ) < 3 ) { return; }
		echo '<section class="gw-section gw-section--muted gw-bestsellers"><div class="gw-container">';
		self::section_head( __( 'Loved by customers', 'greenworld' ), __( 'Our Best Sellers', 'greenworld' ), add_query_arg( 'orderby', 'popularity', self::shop() ), __( 'See all', 'greenworld' ) );
		self::render_products( $ids, 4 );
		echo '</div></section>';
	}

	public static function join_band(): void {
		$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		?>
		<section class="gw-section gw-join">
			<div class="gw-container gw-join__grid">
				<article class="gw-join__card gw-join__card--customer">
					<span class="gw-eyebrow"><?php esc_html_e( 'For everyday wellness', 'greenworld' ); ?></span>
					<h2><?php esc_html_e( 'Register as a Customer', 'greenworld' ); ?></h2>
					<p><?php esc_html_e( 'Create a free account for faster checkout, saved delivery details, order tracking and a wishlist.', 'greenworld' ); ?></p>
					<a class="button" href="<?php echo esc_url( add_query_arg( 'gw_type', 'customer', $account ) ); ?>"><?php esc_html_e( 'Create free account', 'greenworld' ); ?></a>
				</article>
				<article class="gw-join__card gw-join__card--dist">
					<span class="gw-eyebrow"><?php esc_html_e( 'Build a business', 'greenworld' ); ?></span>
					<h2><?php esc_html_e( 'Become a Distributor', 'greenworld' ); ?></h2>
					<p><?php esc_html_e( 'Join the Green World direct-selling business, shop at distributor prices and grow your own network with our support.', 'greenworld' ); ?></p>
					<a class="button gw-btn--gold" href="<?php echo esc_url( home_url( '/become-a-distributor/' ) ); ?>"><?php esc_html_e( 'Start as distributor', 'greenworld' ); ?></a>
				</article>
			</div>
		</section>
		<?php
	}

	public static function collections(): void {
		$shop = self::shop();
		$defs = array(
			array( 'title' => __( "Men's Wellness", 'greenworld' ), 'text' => __( 'Products selected for men’s health and vitality.', 'greenworld' ), 'q' => "Men's Health" ),
			array( 'title' => __( "Women's Wellness", 'greenworld' ), 'text' => __( 'A carefully organized collection for women’s wellness.', 'greenworld' ), 'q' => "Women's Health" ),
			array( 'title' => __( 'Everyday Wellness', 'greenworld' ), 'text' => __( 'Popular products for general health and wellbeing.', 'greenworld' ), 'q' => 'General Health' ),
			array( 'title' => __( 'Nutrition & Supplements', 'greenworld' ), 'text' => __( 'Selected nutrition and wellness essentials.', 'greenworld' ), 'q' => 'Nutrition' ),
		);
		echo '<section class="gw-section gw-collections"><div class="gw-container">';
		self::section_head( __( 'Curated', 'greenworld' ), __( 'Wellness Collections', 'greenworld' ) );
		echo '<div class="gw-collections__grid">';
		foreach ( $defs as $d ) {
			$term = get_term_by( 'name', $d['q'], 'product_cat' );
			$url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : add_query_arg( array( 's' => rawurlencode( $d['q'] ), 'post_type' => 'product' ), home_url( '/' ) );
			printf(
				'<a class="gw-collection" href="%s"><span class="gw-collection__media">%s</span><span class="gw-collection__body"><span class="gw-collection__title">%s</span><span class="gw-collection__text">%s</span><span class="gw-collection__link">%s</span></span></a>',
				esc_url( is_wp_error( $url ) ? $shop : (string) $url ), self::placeholder( (string) $d['title'] ), esc_html( $d['title'] ), esc_html( $d['text'] ), esc_html__( 'Shop collection', 'greenworld' )
			);
		}
		echo '</div></div></section>';
	}

	public static function consultation_band(): void {
		?>
		<section class="gw-section gw-consultband">
			<div class="gw-container gw-consultband__inner">
				<div class="gw-consultband__copy">
					<span class="gw-eyebrow"><?php esc_html_e( 'We are here to help', 'greenworld' ); ?></span>
					<h2><?php esc_html_e( 'Not sure what you need? Get a free health consultation', 'greenworld' ); ?></h2>
					<p><?php esc_html_e( 'Tell us about your health concern and our team will recommend suitable products and guidance — online, at your convenience.', 'greenworld' ); ?></p>
					<a class="button gw-btn--gold" href="<?php echo esc_url( home_url( '/health-consultation/' ) ); ?>"><?php esc_html_e( 'Start free consultation', 'greenworld' ); ?></a>
					<p class="gw-consultband__note"><?php esc_html_e( 'General wellness guidance — not a medical diagnosis or emergency service.', 'greenworld' ); ?></p>
				</div>
			</div>
		</section>
		<?php
	}

	public static function why_choose(): void {
		$points = array(
			array( __( 'Carefully Selected Products', 'greenworld' ), __( 'A focused range of quality health and wellness products.', 'greenworld' ) ),
			array( __( 'Customer-Focused Service', 'greenworld' ), __( 'Friendly, knowledgeable support before and after you buy.', 'greenworld' ) ),
			array( __( 'Reliable Delivery', 'greenworld' ), __( 'Convenient delivery options across Kenya.', 'greenworld' ) ),
			array( __( 'Secure Payments', 'greenworld' ), __( 'Pay safely with M-Pesa, bank transfer or cash on delivery.', 'greenworld' ) ),
		);
		echo '<section class="gw-section gw-section--muted gw-why"><div class="gw-container">';
		self::section_head( __( 'The Green World difference', 'greenworld' ), __( 'Why Choose Green World Health Solutions', 'greenworld' ) );
		echo '<div class="gw-why__grid">';
		foreach ( $points as $p ) {
			printf( '<div class="gw-why__card"><h3>%s</h3><p>%s</p></div>', esc_html( $p[0] ), esc_html( $p[1] ) );
		}
		echo '</div></div></section>';
	}

	public static function journal(): void {
		$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
		if ( count( $posts ) === 0 ) { return; }
		echo '<section class="gw-section gw-journal"><div class="gw-container">';
		self::section_head( __( 'Learn', 'greenworld' ), __( 'Health & Wellness Journal', 'greenworld' ), get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/journal/' ), __( 'Read more', 'greenworld' ) );
		echo '<div class="gw-journal__grid">';
		foreach ( $posts as $post ) {
			$img = has_post_thumbnail( $post ) ? get_the_post_thumbnail( $post, 'medium', array( 'loading' => 'lazy', 'class' => 'gw-journal__img' ) ) : self::placeholder( (string) get_the_title( $post ) );
			printf(
				'<a class="gw-journal__card" href="%s"><span class="gw-journal__media">%s</span><span class="gw-journal__body"><span class="gw-journal__date">%s</span><span class="gw-journal__title">%s</span></span></a>',
				esc_url( (string) get_permalink( $post ) ), $img, esc_html( get_the_date( '', $post ) ), esc_html( get_the_title( $post ) )
			);
		}
		echo '</div></div></section>';
	}

	public static function disclaimer(): void {
		$text = Customizer::val( 'gw_default_disclaimer' );
		if ( $text === '' ) { return; }
		echo '<aside class="gw-section gw-disclaimerband"><div class="gw-container gw-disclaimerband__inner">';
		echo '<span class="gw-disclaimerband__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg></span>';
		echo '<p>' . esc_html( $text ) . '</p>';
		echo '</div></aside>';
	}

	public static function newsletter(): void {
		$email = Customizer::val( 'gw_email' );
		echo '<section class="gw-section gw-newsletter"><div class="gw-container gw-newsletter__inner">';
		echo '<h2 class="gw-newsletter__title">' . esc_html__( 'Join our wellness list', 'greenworld' ) . '</h2>';
		echo '<p>' . esc_html__( 'Health tips, new arrivals and offers — straight to your inbox.', 'greenworld' ) . '</p>';
		$html = do_shortcode( '[contact-form-7 id="newsletter" title="Newsletter"]' );
		if ( strpos( (string) $html, 'wpcf7' ) !== false && strpos( (string) $html, 'not found' ) === false ) {
			echo $html; // phpcs:ignore
		} else {
			echo '<form class="gw-news gw-news--lg" method="post" action="' . esc_url( $email !== '' ? 'mailto:' . $email : '#' ) . '">';
			echo '<label class="screen-reader-text" for="gw-news2">' . esc_html__( 'Email address', 'greenworld' ) . '</label>';
			echo '<input id="gw-news2" type="email" name="subject" placeholder="' . esc_attr__( 'Your email address', 'greenworld' ) . '" />';
			echo '<button class="button" type="submit">' . esc_html__( 'Subscribe', 'greenworld' ) . '</button>';
			echo '</form>';
		}
		echo '</div></section>';
	}
}
