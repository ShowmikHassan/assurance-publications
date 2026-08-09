<?php
/**
 * Per-product checkout flags: "Online Payment Only" and "Free Shipping".
 *
 * Replaces the standalone "Preorder COD Restrict" plugin (still in
 * wp-content/plugins/preorder-cod-restrict, now safe to deactivate once this
 * is verified live) so the two checkboxes on the product edit screen keep
 * working with zero data migration — this reads the exact same post meta
 * keys (`_is_preorder`, `_is_free_shipping_item`) that plugin already wrote,
 * it just stops calling the first one "preorder" anywhere a shopper sees it.
 *
 * Two independent flags, either or both settable per product:
 *
 *   - Online Payment Only (`_is_preorder`): while a flagged product is in
 *     the cart, Cash on Delivery is removed from the available payment
 *     methods — full payment has to go through bKash. The meta key keeps
 *     its original name for backward compatibility with products already
 *     flagged by the old plugin; nothing about that name is shown to
 *     anyone. See assurance_cart_has_payment_only_item().
 *   - Free Shipping (`_is_free_shipping_item`): while a flagged product is
 *     in the cart, delivery is free. This one reaches further than the old
 *     plugin did, because this store's checkout is not a stock WooCommerce
 *     one:
 *       - The actual shipping line is zeroed at the source, inside
 *         Assurance_Courier_Shipping_Method::calculate_shipping() (see
 *         inc/shipping.php) — not bolted on afterwards with a
 *         woocommerce_package_rates filter, which is what a generic plugin
 *         has to do because it doesn't own the rate calculation. Doing it
 *         at the source also gets the rate's label right ("ফ্রি
 *         ডেলিভারি" instead of "ঢাকার ভিতরে ডেলিভারি" showing ৳0).
 *       - assurance_current_courier_fee() (inc/shipping.php) — the
 *         checkout's own estimate of what the COD-with-bKash-prepay
 *         gateway is about to charge — is taught the same flag, so the
 *         "pay the courier fee now via bKash" note and button label don't
 *         ask for money that was never going to be owed.
 *       - assurance_cod_bkash_fee_for_order() in the assurance-cod-bkash
 *         plugin — the function that actually decides the bKash charge at
 *         order time — gets the same check. That plugin is this store's
 *         own bespoke code (not a marketplace plugin an update could wipe;
 *         its own docblocks already lean on theme functions the same way),
 *         so it's the one plugin edit in this feature, and it's not
 *         optional: without it, a flagged product would still trigger a
 *         real bKash charge for a delivery that's supposed to be free.
 *
 * Both flags also print a short note under the product's short description
 * — see assurance_single_product_flag_notes().
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * The two checkboxes on the product edit screen (General tab).
 *
 * No show_if_* wrapper class, matching the old plugin: visible regardless
 * of product type, since this catalogue is books sold as simple products
 * and there's no reason to special-case it.
 */
function assurance_product_flag_fields() {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_is_preorder',
			'label'       => __( 'Online Payment Only', 'assurance' ),
			'description' => __( 'Removes Cash on Delivery from checkout while this product is in the cart — full payment has to go through bKash. Shows a note under the short description.', 'assurance' ),
		)
	);
	woocommerce_wp_checkbox(
		array(
			'id'          => '_is_free_shipping_item',
			'label'       => __( 'Free Shipping Item', 'assurance' ),
			'description' => __( 'Removes the delivery charge at checkout while this product is in the cart. Shows a note under the short description.', 'assurance' ),
		)
	);
}
add_action( 'woocommerce_product_options_general_product_data', 'assurance_product_flag_fields' );

/**
 * Save both checkboxes.
 *
 * @param int $post_id Product ID.
 */
function assurance_save_product_flag_fields( $post_id ) {
	update_post_meta( $post_id, '_is_preorder', isset( $_POST['_is_preorder'] ) ? 'yes' : 'no' );
	update_post_meta( $post_id, '_is_free_shipping_item', isset( $_POST['_is_free_shipping_item'] ) ? 'yes' : 'no' );
}
add_action( 'woocommerce_process_product_meta', 'assurance_save_product_flag_fields' );

/**
 * Product IDs relevant to the current request: the cart normally, or the
 * order being retried on WooCommerce's "Pay for order" page, where
 * WC()->cart is already empty.
 *
 * @return int[]
 */
function assurance_flag_context_product_ids() {
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		global $wp;
		$order_id = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( $order ) {
			$ids = array();
			foreach ( $order->get_items() as $item ) {
				$ids[] = $item->get_product_id();
			}
			return $ids;
		}
	}

	if ( function_exists( 'WC' ) && WC()->cart ) {
		$ids = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$ids[] = $item['product_id'];
		}
		return $ids;
	}

	return array();
}

/**
 * Whether the current cart (or the order being paid for) contains a
 * product flagged Online Payment Only.
 *
 * @return bool
 */
function assurance_cart_has_payment_only_item() {
	foreach ( assurance_flag_context_product_ids() as $product_id ) {
		if ( 'yes' === get_post_meta( $product_id, '_is_preorder', true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether the current cart (or the order being paid for) contains a
 * product flagged Free Shipping.
 *
 * @return bool
 */
function assurance_cart_has_free_shipping_item() {
	foreach ( assurance_flag_context_product_ids() as $product_id ) {
		if ( 'yes' === get_post_meta( $product_id, '_is_free_shipping_item', true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Order-context version of assurance_cart_has_free_shipping_item(), for
 * the assurance-cod-bkash plugin's fee calculation — by the time that runs
 * the order already exists and the cart may already be emptied.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function assurance_order_has_free_shipping_item( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	foreach ( $order->get_items() as $item ) {
		if ( 'yes' === get_post_meta( $item->get_product_id(), '_is_free_shipping_item', true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Drop Cash-on-Delivery-style gateways when the cart requires online
 * payment. Priority 9999 so nothing re-adds COD after this runs.
 *
 * Matches on the gateway id containing "cod" (not an allow-list of known
 * ids) so it also catches this store's own hybrid "assurance_cod" gateway
 * without needing to name it explicitly.
 *
 * @param array $gateways Available gateways.
 * @return array
 */
function assurance_restrict_cod_for_payment_only_items( $gateways ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return $gateways;
	}

	if ( ! assurance_cart_has_payment_only_item() ) {
		return $gateways;
	}

	foreach ( $gateways as $gateway_id => $gateway ) {
		if ( false !== strpos( strtolower( $gateway_id ), 'cod' ) ) {
			unset( $gateways[ $gateway_id ] );
		}
	}

	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'assurance_restrict_cod_for_payment_only_items', 9999 );

/**
 * Explain on the checkout page why Cash on Delivery isn't in the list.
 */
function assurance_payment_only_checkout_notice() {
	if ( ! assurance_cart_has_payment_only_item() ) {
		return;
	}

	wc_print_notice(
		__( 'আপনার কার্টে এমন একটি পণ্য আছে যার জন্য সম্পূর্ণ মূল্য অনলাইনে পরিশোধ করতে হবে — তাই এই অর্ডারে ক্যাশ অন ডেলিভারি সুবিধাটি নেই।', 'assurance' ),
		'notice'
	);
}
add_action( 'woocommerce_before_checkout_form', 'assurance_payment_only_checkout_notice' );

/**
 * One flag note, styled to match: brand orange for the payment restriction
 * (client direction — --ap-accent-tint), green for free shipping (this
 * palette's colour for "free-shipping threshold met"). Both get a light
 * gradient wash and a slow attention sweep — see single-product.css §12.
 *
 * @param string $type      'payment' or 'freeship' — becomes the modifier class.
 * @param string $icon_name Icon key from inc/icons.php.
 * @param string $text      Note text, already translated.
 */
function assurance_render_product_flag_note( $type, $icon_name, $text ) {
	printf(
		'<div class="ap-flag-note ap-flag-note--%1$s" role="note">%2$s<span>%3$s</span></div>',
		esc_attr( $type ),
		assurance_icon( $icon_name, array( 'size' => 17 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted markup from assurance_icon()'s fixed path table.
		esc_html( $text )
	);
}

/**
 * Print both notes (as applicable) directly under the short description —
 * priority 25 sits between woocommerce_template_single_excerpt (20) and the
 * add-to-cart form (30).
 */
function assurance_single_product_flag_notes() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$product_id = $product->get_id();

	if ( 'yes' === get_post_meta( $product_id, '_is_preorder', true ) ) {
		assurance_render_product_flag_note(
			'payment',
			'award',
			__( 'অগ্রিম মূল্য পরিশোধ করতে হবে', 'assurance' )
		);
	}

	if ( 'yes' === get_post_meta( $product_id, '_is_free_shipping_item', true ) ) {
		assurance_render_product_flag_note(
			'freeship',
			'truck',
			__( 'ফ্রি ডেলিভারি — কোনো ডেলিভারি চার্জ দিতে হবে না', 'assurance' )
		);
	}
}
add_action( 'woocommerce_single_product_summary', 'assurance_single_product_flag_notes', 25 );
