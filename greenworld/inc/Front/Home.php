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

	/**
	 * Built-in + Customizer-driven hero slides. Defaults to three premium
	 * slides (general / women / men) so the carousel looks complete out of the box.
	 *
	 * @return array<int,array<string,string>>
	 */
	private static function hero_slides(): array {
		$defaults = array(
			array(
				'img'   => 'assets/img/hero-general.jpg',
				'eye'   => __( 'Trusted health & wellness in Kenya', 'greenworld' ),
				'title' => __( 'Your Health. Your Wellness. Your Better Tomorrow.', 'greenworld' ),
				'sub'   => __( 'Discover carefully selected health and wellness products designed to support healthier everyday living.', 'greenworld' ),
				'cta'   => __( 'Shop Health Products', 'greenworld' ),
			),
			array(
				'img'   => 'assets/img/hero-women.jpg',
				'eye'   => __( "Women's Health", 'greenworld' ),
				'title' => __( "Natural Care Made for Women's Wellness", 'greenworld' ),
				'sub'   => __( 'From daily balance to gentle self-care, explore products chosen with women in mind.', 'greenworld' ),
				'cta'   => __( "Shop Women's Health", 'greenworld' ),
			),
			array(
				'img'   => 'assets/img/hero-men.jpg',
				'eye'   => __( "Men's Health", 'greenworld' ),
				'title' => __( 'Strength, Energy & Vitality for Men', 'greenworld' ),
				'sub'   => __( 'Support performance, stamina and everyday wellbeing with our range for men.', 'greenworld' ),
				'cta'   => __( "Shop Men's Health", 'greenworld' ),
			),
		);

		$slides = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$d        = $defaults[ $i - 1 ];
			$slides[] = array(
				'image'   => (string) get_theme_mod( "gw_hero{$i}_image", GREENWORLD_URI . $d['img'] ),
				'eyebrow' => (string) get_theme_mod( "gw_hero{$i}_eyebrow", $d['eye'] ),
				'title'   => (string) get_theme_mod( "gw_hero{$i}_title", $d['title'] ),
				'sub'     => (string) get_theme_mod( "gw_hero{$i}_sub", $d['sub'] ),
				'cta'     => (string) get_theme_mod( "gw_hero{$i}_cta", $d['cta'] ),
				'url'     => (string) get_theme_mod( "gw_hero{$i}_url", self::shop() ),
			);
		}

		// A slide renders only if it has a title or image (lets an editor hide 2/3).
		return array_values( array_filter( $slides, static function ( $s ) {
			return $s['title'] !== '' || $s['image'] !== '';
		} ) );
	}

	public static function hero(): void {
		$slides = self::hero_slides();
		$count  = count( $slides );
		if ( $count === 0 ) { return; }
		$shop = self::shop();

		echo '<section class="gw-herocar" data-gw-hero aria-roledescription="carousel" aria-label="' . esc_attr__( 'Featured', 'greenworld' ) . '">';
		echo '<div class="gw-herocar__track" data-gw-hero-track>';
		foreach ( $slides as $i => $s ) {
			$style = ( $s['image'] !== '' )
				? ' style="background-image:linear-gradient(90deg, rgba(11,63,46,.86), rgba(11,63,46,.42) 55%, rgba(11,63,46,.12)), url(' . esc_url( $s['image'] ) . ')"'
				: '';
			$tag = ( 0 === $i ) ? 'h1' : 'h2';
			echo '<article class="gw-herocar__slide' . ( 0 === $i ? ' is-active' : '' ) . '"' . $style . ' data-gw-hero-slide="' . (int) $i . '" aria-hidden="' . ( 0 === $i ? 'false' : 'true' ) . '">';
			echo '<div class="gw-container gw-herocar__inner"><div class="gw-hero__copy">';
			if ( $s['eyebrow'] !== '' ) { echo '<span class="gw-hero__eyebrow">' . esc_html( $s['eyebrow'] ) . '</span>'; }
			echo '<' . $tag . ' class="gw-hero__title">' . esc_html( $s['title'] ) . '</' . $tag . '>';
			if ( $s['sub'] !== '' ) { echo '<p class="gw-hero__sub">' . esc_html( $s['sub'] ) . '</p>'; }
			echo '<div class="gw-hero__cta">';
			echo '<a class="button gw-btn--gold" href="' . esc_url( $s['url'] !== '' ? $s['url'] : $shop ) . '">' . esc_html( $s['cta'] !== '' ? $s['cta'] : __( 'Shop now', 'greenworld' ) ) . '</a>';
			echo '<a class="button button-ghost gw-btn--onhero" href="' . esc_url( $shop ) . '">' . esc_html__( 'Explore Categories', 'greenworld' ) . '</a>';
			echo '</div></div></div></article>';
		}
		echo '</div>';

		if ( $count > 1 ) {
			echo '<button type="button" class="gw-herocar__nav gw-herocar__nav--prev" data-gw-hero-prev aria-label="' . esc_attr__( 'Previous slide', 'greenworld' ) . '">&#8249;</button>';
			echo '<button type="button" class="gw-herocar__nav gw-herocar__nav--next" data-gw-hero-next aria-label="' . esc_attr__( 'Next slide', 'greenworld' ) . '">&#8250;</button>';
			echo '<div class="gw-herocar__dots" role="tablist" data-gw-hero-dots>';
			foreach ( $slides as $i => $s ) {
				echo '<button type="button" role="tab" class="gw-herocar__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-gw-hero-dot="' . (int) $i . '" aria-label="' . esc_attr( sprintf( __( 'Go to slide %d', 'greenworld' ), $i + 1 ) ) . '"></button>';
			}
			echo '</div>';
		}
		echo '</section>';
	}

	public static function trust_strip(): void {
		echo '<section class="gw-trust gw-trust--hero" aria-label="' . esc_attr__( 'Our promise', 'greenworld' ) . '"><div class="gw-container">';
		echo do_shortcode( '[gw_trust_badges]' );
		echo '</div></section>';
	}

	/** @return array<int,string> */
	private static function home_categories(): array {
		$default = array( 'General Health', "Men's Health", "Women's Health", 'Immunity & Energy', 'Wellness & Nutrition', 'Detox & Wellness', 'Heart & Circulation', 'Bone & Joint', 'Digestive Care', 'Weight Management' );
		$csv     = (string) get_theme_mod( 'gw_home_categories', '' );
		if ( trim( $csv ) !== '' ) {
			$list = array_values( array_filter( array_map( 'trim', explode( ',', $csv ) ) ) );
			if ( count( $list ) > 0 ) { return array_slice( $list, 0, 10 ); }
		}
		return $default;
	}

	/** Maps a category name to a bundled tile image assets/img/cat/{slug}.jpg when present. */
	private static function cat_image( string $name ): string {
		$rel = 'assets/img/cat/' . sanitize_title( $name ) . '.jpg';
		return is_readable( GREENWORLD_DIR . $rel ) ? GREENWORLD_URI . $rel : '';
	}

	public static function shop_by_category(): void {
		$shop = self::shop();
		$cats = self::home_categories();
		echo '<section class="gw-section gw-cats"><div class="gw-container">';
		self::section_head( __( 'Shop by need', 'greenworld' ), __( 'Shop by Health Category', 'greenworld' ), $shop, __( 'View all', 'greenworld' ) );
		echo '<div class="gw-cats__grid gw-cats__grid--5">';
		foreach ( $cats as $name ) {
			$term  = taxonomy_exists( 'product_cat' ) ? get_term_by( 'name', $name, 'product_cat' ) : false;
			$img   = self::cat_image( $name );
			$count = 0;
			if ( $term && ! is_wp_error( $term ) ) {
				$link  = get_term_link( $term );
				$count = (int) $term->count;
				$tid   = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				if ( $tid ) {
					$thumb = wp_get_attachment_image_url( $tid, 'medium' );
					if ( $thumb ) { $img = (string) $thumb; }
				}
			} else {
				$link = add_query_arg( array( 's' => rawurlencode( $name ), 'post_type' => 'product' ), home_url( '/' ) );
			}
			if ( is_wp_error( $link ) ) { $link = $shop; }
			$media = ( $img !== '' )
				? '<img class="gw-cat-card__img" src="' . esc_url( $img ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" />'
				: self::placeholder( $name );
			$meta = ( $count > 0 )
				? esc_html( sprintf( _n( '%d product', '%d products', $count, 'greenworld' ), $count ) )
				: esc_html__( 'Explore', 'greenworld' );
			printf(
				'<a class="gw-cat-card" href="%s"><span class="gw-cat-card__media">%s</span><span class="gw-cat-card__body"><span class="gw-cat-card__name">%s</span><span class="gw-cat-card__count">%s</span></span></a>',
				esc_url( (string) $link ), $media, esc_html( $name ), $meta
			);
		}
		echo '</div></div></section>';
	}

	private static function placeholder( string $name ): string {
		$initial = function_exists( 'mb_substr' ) ? mb_substr( trim( $name ), 0, 1 ) : substr( trim( $name ), 0, 1 );
		return '<span class="gw-ph" aria-hidden="true"><span>' . esc_html( strtoupper( $initial ) ) . '</span></span>';
	}

	public static function featured_products(): void {
		// Randomize on every load so the row feels fresh; prefer real "featured" products.
		$ids = self::product_ids( array( 'posts_per_page' => 12, 'orderby' => 'rand', 'tax_query' => array( array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured', 'operator' => 'IN' ) ) ) );
		if ( count( $ids ) < 4 ) {
			$ids = self::product_ids( array( 'posts_per_page' => 12, 'orderby' => 'rand' ) );
		}
		if ( count( $ids ) === 0 ) { return; }
		echo '<section class="gw-section gw-featured"><div class="gw-container">';
		self::section_head( __( 'Handpicked', 'greenworld' ), __( 'Featured Health & Wellness Products', 'greenworld' ), self::shop(), __( 'Shop all', 'greenworld' ) );
		echo '<div class="gw-scroller" data-gw-scroller>';
		echo '<button type="button" class="gw-scroller__nav gw-scroller__nav--prev" data-gw-scroll="prev" aria-label="' . esc_attr__( 'Scroll left', 'greenworld' ) . '">&#8249;</button>';
		self::render_products( $ids, 4 );
		echo '<button type="button" class="gw-scroller__nav gw-scroller__nav--next" data-gw-scroll="next" aria-label="' . esc_attr__( 'Scroll right', 'greenworld' ) . '">&#8250;</button>';
		echo '</div></div></section>';
	}

	/** Men / Women / General tri-section band with photographic cards. */
	public static function health_focus(): void {
		$shop  = self::shop();
		$cards = array(
			array( 'name' => __( "Men's Health", 'greenworld' ), 'q' => "Men's Health", 'img' => 'assets/img/hero-men.jpg', 'mod' => 'gw_focus_men_image', 'text' => __( 'Energy, stamina and everyday vitality for men.', 'greenworld' ) ),
			array( 'name' => __( "Women's Health", 'greenworld' ), 'q' => "Women's Health", 'img' => 'assets/img/hero-women.jpg', 'mod' => 'gw_focus_women_image', 'text' => __( 'Balance, beauty and gentle natural self-care.', 'greenworld' ) ),
			array( 'name' => __( 'General Health', 'greenworld' ), 'q' => 'General Health', 'img' => 'assets/img/hero-general.jpg', 'mod' => 'gw_focus_general_image', 'text' => __( 'Everyday wellbeing for the whole family.', 'greenworld' ) ),
		);
		echo '<section class="gw-section gw-focus"><div class="gw-container">';
		self::section_head( __( 'Shop by person', 'greenworld' ), __( 'Health for Everyone', 'greenworld' ) );
		echo '<div class="gw-focus__grid">';
		foreach ( $cards as $c ) {
			$img  = (string) get_theme_mod( $c['mod'], GREENWORLD_URI . $c['img'] );
			$term = taxonomy_exists( 'product_cat' ) ? get_term_by( 'name', $c['q'], 'product_cat' ) : false;
			$url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : add_query_arg( array( 's' => rawurlencode( $c['q'] ), 'post_type' => 'product' ), home_url( '/' ) );
			if ( is_wp_error( $url ) ) { $url = $shop; }
			$style = ( $img !== '' ) ? ' style="background-image:linear-gradient(180deg, rgba(11,63,46,.05), rgba(11,63,46,.78)), url(' . esc_url( $img ) . ')"' : '';
			printf(
				'<a class="gw-focus__card" href="%s"%s><span class="gw-focus__body"><span class="gw-focus__name">%s</span><span class="gw-focus__text">%s</span><span class="gw-focus__link">%s &rarr;</span></span></a>',
				esc_url( (string) $url ), $style, esc_html( $c['name'] ), esc_html( $c['text'] ), esc_html__( 'Shop now', 'greenworld' )
			);
		}
		echo '</div></div></section>';
	}

	/** Bookable "Computerized Full Body Scan" promo band (Customizer-driven). */
	public static function full_body_scan(): void {
		if ( '1' !== (string) get_theme_mod( 'gw_scan_enable', '1' ) ) { return; }
		$title = (string) get_theme_mod( 'gw_scan_title', __( 'Computerized Full Body Health Scan', 'greenworld' ) );
		$price = (string) get_theme_mod( 'gw_scan_price', __( 'KSh 1,500', 'greenworld' ) );
		$desc  = (string) get_theme_mod( 'gw_scan_desc', __( 'Book a quick, non-invasive computerized full body scan and get a clear picture of your wellbeing. Walk in or reserve a time ahead.', 'greenworld' ) );
		$phone = preg_replace( '/[^0-9]/', '', (string) get_theme_mod( 'gw_whatsapp', '254723579873' ) );
		$wa    = 'https://wa.me/' . $phone . '?text=' . rawurlencode( sprintf( 'Hello Green World Health Solutions, I would like to book a Computerized Full Body Scan (%s).', $price ) );
		$url   = (string) get_theme_mod( 'gw_scan_url', '' );
		if ( $url === '' ) { $url = ( $phone !== '' ) ? $wa : home_url( '/health-consultation/' ); }
		?>
		<section class="gw-section gw-scanband">
			<div class="gw-container gw-scanband__inner">
				<div class="gw-scanband__copy">
					<span class="gw-eyebrow"><?php esc_html_e( 'Book an appointment', 'greenworld' ); ?></span>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $desc ); ?></p>
					<a class="button gw-btn--gold" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Book your scan', 'greenworld' ); ?></a>
				</div>
				<div class="gw-scanband__price" aria-hidden="true">
					<span class="gw-scanband__from"><?php esc_html_e( 'Only', 'greenworld' ); ?></span>
					<span class="gw-scanband__amount"><?php echo esc_html( $price ); ?></span>
					<span class="gw-scanband__per"><?php esc_html_e( 'per scan', 'greenworld' ); ?></span>
				</div>
			</div>
		</section>
		<?php
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
