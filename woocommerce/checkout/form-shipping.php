<?php
/**
 * Shipping fields + "Additional information".
 *
 * Emptied deliberately.
 *
 * WooCommerce renders both the shipping address block and the
 * "Additional information" order-note field from this one template. This
 * store collects a single address (billing is copied to shipping in
 * inc/checkout.php), and the order note is presented as a collapsible
 * further down form-checkout.php.
 *
 * Leaving the default template in place produced a *second*
 * order_comments textarea on the page — two fields with the same name,
 * where whichever posted last silently won. Overriding to nothing is the
 * fix; the note is still rendered, just once and where we want it.
 *
 * The two action hooks are preserved so plugins that inject into the
 * shipping step (courier pickers, delivery-date fields) still fire.
 *
 * @package Assurance
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_order_notes', $checkout );
do_action( 'woocommerce_after_order_notes', $checkout );
