<?php
/**
 * GreenWorld Wellness Child - functions.
 *
 * @package GreenWorldChild
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the child stylesheet after the parent design system (assets/css/main.css,
 * which the parent enqueues as the "greenworld-main" handle).
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'greenworld-child',
			get_stylesheet_directory_uri() . '/style.css',
			array( 'greenworld-main' ),
			wp_get_theme()->get( 'Version' )
		);
	},
	30
);

/*
 * Example overrides (uncomment and edit):
 *
 * add_filter( 'greenworld_social_profiles', function ( $links ) {
 *     return array(
 *         'https://www.facebook.com/yourpage',
 *         'https://www.instagram.com/yourpage',
 *     );
 * } );
 *
 * add_filter( 'greenworld_opening_hours', function () {
 *     return array(
 *         array( 'days' => array( 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ), 'opens' => '08:30', 'closes' => '18:00' ),
 *     );
 * } );
 */
