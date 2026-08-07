<?php
/**
 * Steadfast COD amount — keep it honest.
 *
 * This store's COD gateway (assurance-cod-bkash, see inc/checkout.php +
 * the plugin of the same name) is a hybrid: the courier charge is
 * collected up front via bKash, and only the book price is due at the
 * door. Neither WooCommerce core nor the third-party Steadfast API plugin
 * know about that split:
 *
 *   - WooCommerce's admin "Paid" row always shows the full order total
 *     once payment_complete() has run — correct for a normal gateway, but
 *     read literally here it implies nothing is owed at the door.
 *   - The Steadfast plugin's COD amount (both on the printed invoice and,
 *     more importantly, in the parcel actually booked with the courier)
 *     defaults to the full order total whenever staff haven't typed an
 *     override into its "amount" column — which double-charges the
 *     customer for delivery on every hybrid order left at its default.
 *
 * Both are fixed here without touching WooCommerce core or a single line
 * of the vendor Steadfast plugin, so nothing here is at risk from a
 * plugin/core update — this file is the entire fix:
 *
 *   - assurance_backfill_steadfast_amount() writes the correct number into
 *     the Steadfast plugin's own `steadfast_amount` post meta, which the
 *     plugin already treats as the authoritative COD amount for both the
 *     invoice and the API call — so giving it the right default fixes both
 *     from the outside, with no plugin edit. It only ever fills an empty
 *     value, never overwrites one, so a staff member's manual override for
 *     a genuine exception is never silently replaced.
 *   - assurance_admin_due_at_delivery_row() adds a plain "Due at delivery"
 *     line under the order totals, so staff read the real cash-on-hand
 *     figure straight off the order screen instead of the (correct, but
 *     easily misread) "Paid: ৳<full total>" row.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * What the courier should actually collect at the door for this order.
 *
 * @param WC_Order $order Order.
 * @return float
 */
function assurance_cod_amount_due( $order ) {
	if ( 'assurance_cod' !== $order->get_payment_method() ) {
		// Any other gateway (bKash full payment, etc.) collects everything
		// online — nothing left for the courier to collect.
		return 0.0;
	}

	$fee = (float) $order->get_meta( '_assurance_cod_courier_fee' );

	return max( 0.0, (float) $order->get_total() - $fee );
}

/**
 * Pre-fill the Steadfast plugin's own amount field with the real
 * due-at-delivery figure, unless staff have already typed something in.
 *
 * Nothing here edits the vendor plugin — it already treats the
 * `steadfast_amount` post meta as the authoritative COD figure for both
 * the printed invoice and the actual parcel booked with the courier, so
 * writing the correct number into that same meta is enough to fix both,
 * and it survives a Steadfast plugin update untouched.
 *
 * Deliberately never overwrites a value that's already there — once set
 * (by this function or by a human), it's left alone, so a staff member's
 * manual correction for a genuine exception is never silently replaced.
 *
 * @param int $order_id Order ID.
 */
function assurance_backfill_steadfast_amount( $order_id ) {
	if ( '' !== get_post_meta( $order_id, 'steadfast_amount', true ) ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	update_post_meta( $order_id, 'steadfast_amount', assurance_cod_amount_due( $order ) );
}

/*
 * Three independent triggers, so the figure is right well before anyone
 * can act on it — no single one of these is relied on alone:
 *
 *   - payment_complete: the normal case, fires the moment bKash confirms
 *     the courier fee (or immediately for free-shipping COD).
 *   - order_status_changed: catches an order pushed to Processing by hand,
 *     or any flow that skips payment_complete().
 *   - the admin order screen itself: a last-resort self-heal for orders
 *     placed before this fix existed, or anything the two hooks above
 *     somehow missed — it runs the instant staff open the order, which is
 *     always before they can click "Send" to Steadfast.
 */
add_action( 'woocommerce_payment_complete', 'assurance_backfill_steadfast_amount' );
add_action( 'woocommerce_order_status_changed', 'assurance_backfill_steadfast_amount' );
add_action( 'woocommerce_admin_order_data_after_order_details', function ( $order ) {
	assurance_backfill_steadfast_amount( $order->get_id() );
} );

/**
 * Surface the real due-at-delivery amount on the admin order screen.
 *
 * Deliberately does not touch WooCommerce's own "Paid" row: that value
 * (get_total()) is core behaviour shared by every COD-style gateway and
 * isn't itself wrong — "paid" there means "this gateway's flow completed",
 * not "cash in hand" — and it isn't reliably overridable from a theme.
 * Adding a row next to it is the safe way to make the courier-cash figure
 * unmissable without fighting a core admin template.
 *
 * @param int $order_id Order ID.
 */
function assurance_admin_due_at_delivery_row( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order || 'assurance_cod' !== $order->get_payment_method() ) {
		return;
	}

	$fee = (float) $order->get_meta( '_assurance_cod_courier_fee' );

	if ( $fee <= 0 ) {
		return; // Free-delivery COD order — nothing was prepaid, so the full total already is the due amount.
	}

	?>
	<tr>
		<td class="label label-highlight"><?php esc_html_e( 'ডেলিভারিতে বাকি (Due at delivery)', 'assurance' ); ?>:</td>
		<td width="1%"></td>
		<td class="total">
			<?php echo wp_kses_post( wc_price( assurance_cod_amount_due( $order ), array( 'currency' => $order->get_currency() ) ) ); ?>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_admin_order_totals_after_total', 'assurance_admin_due_at_delivery_row' );
