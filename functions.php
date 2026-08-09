<?php
/**
 * Assurance Publications — child theme bootstrap.
 *
 * This file stays a manifest. Every feature lives in its own file under
 * inc/ so that a change to, say, checkout never risks the cart AJAX.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

define( 'ASSURANCE_VERSION', '1.0.0' );
define( 'ASSURANCE_DIR', get_stylesheet_directory() );
define( 'ASSURANCE_URI', get_stylesheet_directory_uri() );

/*
 * The shop's public mailbox. Used as Reply-To on transactional email and
 * printed in the email footer. Kept here rather than read from WooCommerce's
 * "from" address so a staging copy cannot leak a dev placeholder to a real
 * customer; see assurance_shop_contact_email().
 */
define( 'ASSURANCE_CONTACT_EMAIL', 'assurance1996@gmail.com' );

/**
 * Load a feature module.
 *
 * Kept as a helper rather than a bare require loop so a missing or
 * renamed file surfaces as a clear notice in debug instead of a fatal.
 *
 * @param string $file Path relative to inc/, without extension.
 */
function assurance_load( $file ) {
	$path = ASSURANCE_DIR . '/inc/' . $file . '.php';

	if ( is_readable( $path ) ) {
		require_once $path;
		return;
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		trigger_error(
			esc_html( sprintf( 'Assurance: missing module inc/%s.php', $file ) ),
			E_USER_WARNING
		);
	}
}

/* Core — always loaded. */
assurance_load( 'setup' );        // Enqueues, theme supports, Blocksy interop.
assurance_load( 'helpers' );      // Shared formatting + render utilities.
assurance_load( 'icons' );        // Inline SVG sprite (no icon-font request).

/* WooCommerce-dependent modules. */
if ( class_exists( 'WooCommerce' ) ) {
	assurance_load( 'districts' );      // 64 BD districts, Bangla labels.
	assurance_load( 'product-card' );   // Card data + render helpers.
	assurance_load( 'ajax' );           // All wp_ajax_* endpoints.
	assurance_load( 'mini-cart' );      // Off-canvas cart + fragments.
	assurance_load( 'shop-filters' );   // Archive filtering + query vars.
	assurance_load( 'single-product' ); // Gallery, tabs, read-later.
	assurance_load( 'cart' );           // Cart page + suggestions.
	assurance_load( 'checkout' );       // Field trimming + district select.
	assurance_load( 'shipping' );       // Inside/Outside Dhaka rates.
	assurance_load( 'product-flags' );  // Online-payment-only + free-shipping product flags.
	assurance_load( 'read-later' );     // "একটু পরে দেখুন" list.
	assurance_load( 'emails' );         // Transactional email copy + invoice.
	assurance_load( 'pdf-invoice' );    // Bengali font for the mPDF invoice.
	assurance_load( 'steadfast' );      // Correct COD-due amount for the Steadfast plugin + admin screen.
}
