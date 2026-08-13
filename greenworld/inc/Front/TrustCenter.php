<?php
declare( strict_types=1 );

namespace GreenWorld\Front;

use GreenWorld\Core\Bootable;

defined( 'ABSPATH' ) || exit;

/**
 * Trust Center: the credibility architecture for a Kenyan health retailer.
 * Real business identity, sourcing, authenticity, conservative regulatory
 * language, customer protection (delivery / returns / payments / privacy),
 * team and contact. Every fact is Customizer-editable; nothing is invented.
 *
 * Rendered by template-trust-center.php and via [gw_trust_center].
 * Homepage trust band via [gw_why_trust] or TrustCenter::why_trust().
 */
final class TrustCenter implements Bootable {

	public function boot(): void {
		add_action( 'customize_register', array( $this, 'customize' ) );
		add_shortcode( 'gw_trust_center', array( __CLASS__, 'render_sc' ) );
		add_shortcode( 'gw_why_trust', array( __CLASS__, 'why_trust_sc' ) );
	}

	/* ---------------------------------------------------------------- */
	/*  Facts (confirmed defaults, all Customizer-editable)             */
	/* ---------------------------------------------------------------- */

	private static function v( string $key, string $default ): string {
		$val = trim( (string) get_theme_mod( 'gw_tc_' . $key, $default ) );
		return $val !== '' ? $val : $default;
	}

	private static function paras( string $text ): string {
		$out   = '';
		$parts = preg_split( '/\n\s*\n/', trim( $text ) );
		foreach ( (array) $parts as $p ) {
			$p = trim( (string) $p );
			if ( $p !== '' ) { $out .= '<p>' . esc_html( $p ) . '</p>'; }
		}
		return $out;
	}

	private static function about_default(): string {
		return "Green World Health Solutions is a Kenyan health and wellness retailer based in Nairobi. We are an authorized distributor of Green World products, supplying genuine health and wellness items to customers across Kenya and, on request, worldwide.\n\nWe back every order with clear product information, secure local payment options, reliable delivery and real customer support. Our aim is simple: make trusted wellness products easy to buy, with none of the guesswork.";
	}
	private static function sourcing_default(): string {
		return "Our products are Green World brand products, manufactured by the international Green World / World Food group (en.world-food.com). Green World Health Solutions is an authorized distributor of these products in Kenya.\n\nThe Green World group maintains a registered office in Upperhill, Nairobi, and product documentation is held at group level. We select products from the Green World range and make them available to Kenyan customers with clear, honest information.";
	}
	private static function authenticity_default(): string {
		return "Every item we sell is a genuine Green World brand product sourced through the group's official supply chain, never counterfeit or grey-market stock. If you ever have a question about a product's authenticity, contact us and we will help you verify it.";
	}
	private static function regulatory_default(): string {
		return "Green World is an established international health-products brand, and the group's product documentation is held at its Nairobi (Upperhill) office. We provide available product documentation on request.\n\nWe do not make disease treatment or cure claims, and we do not display approval badges we cannot substantiate. Our product information is for general wellness purposes and is not a substitute for professional medical advice, diagnosis or treatment.";
	}
	private static function returns_default(): string {
		return "Report window: contact us within 7 days of delivery, via WhatsApp or phone, with your order number.\n\nDamaged, defective or incorrect items: we replace them free of charge or issue a full refund. Please send a photo when you report the issue.\n\nSealed, unopened non-consumable items: returnable within 7 days in their original condition.\n\nOpened health or supplement products: for safety and hygiene reasons these cannot be returned unless they arrived damaged, defective or incorrect.\n\nRefunds: processed to your original payment method (M-Pesa or bank transfer) within 3 to 5 business days of approval.";
	}
	private static function privacy_default(): string {
		return "We collect only what we need to fulfil your orders and answer your enquiries. Because our free wellness consultation asks about your health situation, that information is treated as sensitive: it is used only to guide product suggestions, is never sold or shared for marketing, and is handled in line with Kenya's data-protection expectations. You can ask us to delete your information at any time.";
	}

	/* ---------------------------------------------------------------- */
	/*  Rendering                                                       */
	/* ---------------------------------------------------------------- */

	public static function render_sc(): string { ob_start(); self::render(); return (string) ob_get_clean(); }
	public static function why_trust_sc(): string { ob_start(); self::why_trust(); return (string) ob_get_clean(); }

	private static function avatar(): string {
		return '<span class="gw-team__avatar" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="32" fill="#e8f0ea"/><circle cx="32" cy="25" r="11" fill="#b8ccbe"/><path d="M12 56c2-11 10-17 20-17s18 6 20 17" fill="#b8ccbe"/></svg></span>';
	}

	public static function render(): void {
		$name    = self::v( 'name', 'Green World Health Solutions' );
		$address = self::v( 'address', 'Development House, 11th Floor, Room 7, Nairobi, Kenya' );
		$hours   = self::v( 'hours', 'Monday to Saturday, 8:30 AM to 6:00 PM' );
		$phone   = self::v( 'phone', '0723 579 873' );
		$email   = self::v( 'email', 'info@greenworldheath.com' );
		$wa      = preg_replace( '/[^0-9]/', '', self::v( 'whatsapp', '254723579873' ) );
		$pickup  = self::v( 'pickup', 'Development House, 11th Floor, Room 7, Nairobi' );
		$fee_bus = self::v( 'fee_bus', 'KSh 300' );
		$fee_cur = self::v( 'fee_courier', 'KSh 550' );
		$fee_dhl = self::v( 'fee_dhl', 'KSh 7,000' );

		echo '<div class="gw-container gw-trust">';

		// Intro
		echo '<header class="gw-trust__intro">';
		echo '<span class="gw-eyebrow">' . esc_html__( 'Why you can trust us', 'greenworld' ) . '</span>';
		echo '<h1>' . esc_html__( 'Trust Center', 'greenworld' ) . '</h1>';
		echo '<p>' . esc_html( sprintf( '%s is a real, Nairobi-based business. Here is exactly who we are, where our products come from, how we protect you as a customer, and how to reach us.', $name ) ) . '</p>';
		echo '</header>';

		// Anchor nav
		$nav = array(
			'about'       => __( 'About', 'greenworld' ),
			'business'    => __( 'Our Business', 'greenworld' ),
			'quality'     => __( 'Product Quality', 'greenworld' ),
			'authentic'   => __( 'Authenticity', 'greenworld' ),
			'regulatory'  => __( 'Regulatory', 'greenworld' ),
			'protection'  => __( 'Customer Protection', 'greenworld' ),
			'team'        => __( 'Our Team', 'greenworld' ),
			'contact'     => __( 'Contact', 'greenworld' ),
		);
		echo '<nav class="gw-trust__toc" aria-label="' . esc_attr__( 'Trust Center sections', 'greenworld' ) . '">';
		foreach ( $nav as $id => $label ) {
			echo '<a href="#tc-' . esc_attr( $id ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';

		// About
		echo '<section id="tc-about" class="gw-trust__sec"><h2>' . esc_html__( 'About Green World Health Solutions', 'greenworld' ) . '</h2>';
		echo self::paras( self::v( 'about', self::about_default() ) ); // phpcs:ignore
		echo '</section>';

		// Our Business
		echo '<section id="tc-business" class="gw-trust__sec"><h2>' . esc_html__( 'Our Business', 'greenworld' ) . '</h2>';
		echo '<ul class="gw-trust__facts">';
		printf( '<li><span>%s</span><strong>%s</strong></li>', esc_html__( 'Registered name', 'greenworld' ), esc_html( $name ) );
		printf( '<li><span>%s</span><strong>%s</strong></li>', esc_html__( 'Location', 'greenworld' ), esc_html( $address ) );
		printf( '<li><span>%s</span><strong>%s</strong></li>', esc_html__( 'Business hours', 'greenworld' ), esc_html( $hours ) );
		printf( '<li><span>%s</span><strong><a href="tel:%s">%s</a></strong></li>', esc_html__( 'Phone / WhatsApp', 'greenworld' ), esc_attr( preg_replace( '/\s+/', '', $phone ) ), esc_html( $phone ) );
		printf( '<li><span>%s</span><strong><a href="mailto:%s">%s</a></strong></li>', esc_html__( 'Email', 'greenworld' ), esc_attr( $email ), esc_html( $email ) );
		echo '</ul></section>';

		// Product Quality & Sourcing
		echo '<section id="tc-quality" class="gw-trust__sec"><h2>' . esc_html__( 'Product Quality & Sourcing', 'greenworld' ) . '</h2>';
		echo self::paras( self::v( 'sourcing', self::sourcing_default() ) ); // phpcs:ignore
		echo '</section>';

		// Authenticity
		echo '<section id="tc-authentic" class="gw-trust__sec"><h2>' . esc_html__( 'Product Authenticity', 'greenworld' ) . '</h2>';
		echo self::paras( self::v( 'authenticity', self::authenticity_default() ) ); // phpcs:ignore
		echo '</section>';

		// Regulatory
		echo '<section id="tc-regulatory" class="gw-trust__sec"><h2>' . esc_html__( 'Regulatory Information', 'greenworld' ) . '</h2>';
		echo self::paras( self::v( 'regulatory', self::regulatory_default() ) ); // phpcs:ignore
		echo '</section>';

		// Customer Protection
		echo '<section id="tc-protection" class="gw-trust__sec"><h2>' . esc_html__( 'Customer Protection', 'greenworld' ) . '</h2>';

		// Delivery
		echo '<h3>' . esc_html__( 'Delivery', 'greenworld' ) . '</h3>';
		echo '<div class="gw-trust__delivery">';
		printf( '<div class="gw-trust__ship"><span class="gw-trust__fee">%s</span><strong>%s</strong><p>%s</p></div>', esc_html__( 'Free', 'greenworld' ), esc_html__( 'Nairobi same-day', 'greenworld' ), esc_html__( 'Order before 5:00 PM for same-day delivery within Nairobi.', 'greenworld' ) );
		printf( '<div class="gw-trust__ship"><span class="gw-trust__fee">%s</span><strong>%s</strong><p>%s</p></div>', esc_html( $fee_bus ), esc_html__( 'Bus & shuttle parcel', 'greenworld' ), esc_html__( 'Sent the same day via trusted bus and shuttle parcel services to your town anywhere in Kenya.', 'greenworld' ) );
		printf( '<div class="gw-trust__ship"><span class="gw-trust__fee">%s</span><strong>%s</strong><p>%s</p></div>', esc_html( $fee_cur ), esc_html__( 'Wells Fargo / G4S courier', 'greenworld' ), esc_html__( 'Dispatched via Wells Fargo or G4S courier to your nearest branch or address.', 'greenworld' ) );
		printf( '<div class="gw-trust__ship"><span class="gw-trust__fee">%s</span><strong>%s</strong><p>%s</p></div>', esc_html( $fee_dhl ), esc_html__( 'Worldwide via DHL', 'greenworld' ), esc_html__( 'International shipping for parcels 0.5 kg or less. We have delivered to clients across Africa, Europe, Asia and North America.', 'greenworld' ) );
		echo '</div>';
		echo '<ul class="gw-trust__times">';
		echo '<li>' . esc_html__( 'Estimated times: Nairobi same-day (before 5pm); major towns next day; other Kenyan areas 2 to 3 days; international via DHL typically 3 to 5 working days.', 'greenworld' ) . '</li>';
		echo '<li>' . esc_html( sprintf( 'Pickup: collect free from our office (%s) during business hours.', $pickup ) ) . '</li>';
		echo '<li class="gw-trust__note">' . esc_html__( 'International orders: your country may charge import duties or taxes on arrival. These are the customer\'s responsibility — please check your local import policy before ordering.', 'greenworld' ) . '</li>';
		echo '</ul>';

		// Returns
		echo '<h3>' . esc_html__( 'Returns & Refunds', 'greenworld' ) . '</h3>';
		echo self::paras( self::v( 'returns', self::returns_default() ) ); // phpcs:ignore

		// Payments
		echo '<h3>' . esc_html__( 'Payments', 'greenworld' ) . '</h3>';
		echo '<p>' . esc_html__( 'We accept M-Pesa, Bank Transfer and Cash (on delivery within Nairobi, or on pickup). All online interactions run over a secure (SSL) connection.', 'greenworld' ) . '</p>';

		// Privacy
		echo '<h3>' . esc_html__( 'Privacy & your data', 'greenworld' ) . '</h3>';
		echo self::paras( self::v( 'privacy', self::privacy_default() ) ); // phpcs:ignore

		// Customer service
		echo '<h3>' . esc_html__( 'Customer Service', 'greenworld' ) . '</h3>';
		echo '<p>' . esc_html( sprintf( 'Reach us on WhatsApp or phone %s, or email %s, %s.', $phone, $email, $hours ) ) . '</p>';
		echo '</section>';

		// Team
		echo '<section id="tc-team" class="gw-trust__sec"><h2>' . esc_html__( 'Our Team', 'greenworld' ) . '</h2>';
		echo '<p>' . esc_html__( 'Real photos and names are on the way. Until then, these are the roles that look after you. We will never invent names or credentials.', 'greenworld' ) . '</p>';
		echo '<div class="gw-team__grid">';
		$roles = array(
			__( 'Founder / Managing Director', 'greenworld' ),
			__( 'Wellness Product Advisor', 'greenworld' ),
			__( 'Customer Care & Orders', 'greenworld' ),
		);
		foreach ( $roles as $role ) {
			echo '<div class="gw-team__card">' . self::avatar() . '<strong>' . esc_html( $role ) . '</strong><span>' . esc_html__( 'Photo & bio coming soon', 'greenworld' ) . '</span></div>';
		}
		echo '</div></section>';

		// Contact
		echo '<section id="tc-contact" class="gw-trust__sec gw-trust__contact"><h2>' . esc_html__( 'Contact', 'greenworld' ) . '</h2>';
		echo '<p>' . esc_html( $address ) . '</p>';
		echo '<p><a class="button gw-btn--gold" href="' . esc_url( $wa !== '' ? 'https://wa.me/' . $wa : 'tel:' . preg_replace( '/\s+/', '', $phone ) ) . '">' . esc_html__( 'Chat on WhatsApp', 'greenworld' ) . '</a> ';
		echo '<a class="button" href="mailto:' . esc_attr( $email ) . '">' . esc_html__( 'Email us', 'greenworld' ) . '</a></p>';
		echo '<p>' . esc_html( $hours ) . '</p>';
		echo '</section>';

		echo '</div>';
	}

	public static function why_trust(): void {
		$cards = array(
			array( __( 'Authorized Green World distributor', 'greenworld' ), __( 'Genuine products from the international Green World / World Food group.', 'greenworld' ) ),
			array( __( 'Kenya-based', 'greenworld' ), __( 'A real Nairobi office you can visit or call, Monday to Saturday.', 'greenworld' ) ),
			array( __( 'Secure local payments', 'greenworld' ), __( 'M-Pesa, Bank Transfer and Cash, over a secure connection.', 'greenworld' ) ),
			array( __( 'Nationwide & worldwide delivery', 'greenworld' ), __( 'Same-day in Nairobi, countrywide courier, and DHL international shipping.', 'greenworld' ) ),
			array( __( 'Real support', 'greenworld' ), __( 'WhatsApp, phone and email — talk to a person, not a bot.', 'greenworld' ) ),
			array( __( 'Transparent policies', 'greenworld' ), __( 'Clear returns, refunds, delivery and privacy — no surprises.', 'greenworld' ) ),
		);
		echo '<section class="gw-section gw-whytrust"><div class="gw-container">';
		echo '<div class="gw-sec__head"><div class="gw-sec__heads"><span class="gw-eyebrow">' . esc_html__( 'Peace of mind', 'greenworld' ) . '</span><h2 class="gw-sec__title">' . esc_html__( 'Why Customers Trust Green World Health Solutions', 'greenworld' ) . '</h2></div>';
		echo '<a class="gw-sec__more" href="' . esc_url( home_url( '/trust-center/' ) ) . '">' . esc_html__( 'Visit our Trust Center', 'greenworld' ) . '</a></div>';
		echo '<div class="gw-whytrust__grid">';
		foreach ( $cards as $c ) {
			printf( '<div class="gw-whytrust__card"><strong>%s</strong><p>%s</p></div>', esc_html( $c[0] ), esc_html( $c[1] ) );
		}
		echo '</div></div></section>';
	}

	/* ---------------------------------------------------------------- */
	/*  Customizer                                                      */
	/* ---------------------------------------------------------------- */

	public function customize( \WP_Customize_Manager $wp ): void {
		if ( ! $wp->get_panel( 'greenworld' ) ) {
			$wp->add_panel( 'greenworld', array( 'title' => __( 'GreenWorld Theme', 'greenworld' ), 'priority' => 30 ) );
		}
		$wp->add_section( 'gw_trust', array( 'title' => __( 'Trust Center', 'greenworld' ), 'panel' => 'greenworld', 'priority' => 40 ) );

		$text = array(
			'name'    => __( 'Registered business name', 'greenworld' ),
			'address' => __( 'Address', 'greenworld' ),
			'hours'   => __( 'Business hours', 'greenworld' ),
			'phone'   => __( 'Phone', 'greenworld' ),
			'email'   => __( 'Email', 'greenworld' ),
			'whatsapp'=> __( 'WhatsApp number (digits only)', 'greenworld' ),
			'pickup'  => __( 'Pickup address', 'greenworld' ),
			'fee_bus' => __( 'Bus & shuttle parcel fee', 'greenworld' ),
			'fee_courier' => __( 'Wells Fargo / G4S courier fee', 'greenworld' ),
			'fee_dhl' => __( 'Worldwide DHL fee', 'greenworld' ),
		);
		foreach ( $text as $k => $label ) {
			$wp->add_setting( 'gw_tc_' . $k, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			$wp->add_control( 'gw_tc_' . $k, array( 'label' => $label, 'section' => 'gw_trust', 'type' => 'text' ) );
		}

		$areas = array(
			'about'        => __( 'About text', 'greenworld' ),
			'sourcing'     => __( 'Product quality & sourcing', 'greenworld' ),
			'authenticity' => __( 'Authenticity', 'greenworld' ),
			'regulatory'   => __( 'Regulatory information', 'greenworld' ),
			'returns'      => __( 'Returns & refunds', 'greenworld' ),
			'privacy'      => __( 'Privacy', 'greenworld' ),
		);
		foreach ( $areas as $k => $label ) {
			$wp->add_setting( 'gw_tc_' . $k, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
			$wp->add_control( 'gw_tc_' . $k, array( 'label' => $label, 'section' => 'gw_trust', 'type' => 'textarea', 'description' => __( 'Leave blank to use the built-in copy.', 'greenworld' ) ) );
		}
	}
}
