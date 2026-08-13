<?php
/**
 * GreenWorld homepage. Renders premium, restrained sections from live
 * WooCommerce data. If a static front page with content is set, that content
 * prints between the hero and the category sections.
 *
 * @package GreenWorld
 */
defined( 'ABSPATH' ) || exit;

use GreenWorld\Front\Home;

get_header();

Home::hero();
Home::trust_strip();
Home::shop_by_category();

if ( is_page() && have_posts() ) {
	while ( have_posts() ) {
		the_post();
		if ( trim( (string) get_the_content() ) \!== '' ) {
			echo '<div class="gw-container gw-section gw-userblock">';
			the_content();
			echo '</div>';
		}
	}
}

Home::featured_products();
Home::best_sellers();
Home::join_band();
Home::collections();
Home::consultation_band();
Home::why_choose();
Home::journal();
Home::disclaimer();
Home::newsletter();

get_footer();
