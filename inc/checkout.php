<?php
/**
 * Checkout.
 *
 * Field set is reduced to exactly what a Bangladeshi book delivery needs:
 *
 *   Full name · Full address · District · Mobile · Email · Order note
 *
 * Everything else is removed rather than hidden — a hidden-but-registered
 * field still runs validation, still writes empty order meta, and still
 * shows up in exports. Removing them keeps the order record honest.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reduce and relabel the checkout fields.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function assurance_checkout_fields( $fields ) {
	// Shipping is never collected separately: books go to the billing
	// address, and a second address block roughly doubles checkout
	// abandonment on mobile.
	$fields['shipping'] = array();

	$billing = array();

	$billing['billing_first_name'] = array(
		'label'        => __( 'পুরো নাম', 'assurance' ),
		'placeholder'  => __( 'আপনার নাম লিখুন', 'assurance' ),
		'required'     => true,
		'class'        => array( 'form-row-wide' ),
		'autocomplete' => 'name',
		'priority'     => 10,
	);

	$billing['billing_state'] = array(
		'label'        => __( 'জেলা', 'assurance' ),
		'placeholder'  => __( 'জেলা নির্বাচন করুন', 'assurance' ),
		'required'     => true,
		'type'         => 'state',
		'class'        => array( 'form-row-wide', 'address-field' ),
		'validate'     => array( 'state' ),
		'autocomplete' => 'address-level1',
		'priority'     => 20,
	);

	$billing['billing_address_1'] = array(
		'label'        => __( 'সম্পূর্ণ ঠিকানা', 'assurance' ),
		'placeholder'  => __( 'বাসা/হোল্ডিং, রোড, এলাকা, থানা', 'assurance' ),
		'required'     => true,
		'class'        => array( 'form-row-wide' ),
		'autocomplete' => 'street-address',
		'priority'     => 30,
	);

	$billing['billing_phone'] = array(
		'label'        => __( 'মোবাইল নম্বর', 'assurance' ),
		'placeholder'  => __( '01XXXXXXXXX', 'assurance' ),
		'required'     => true,
		'type'         => 'tel',
		'class'        => array( 'form-row-first' ),
		'autocomplete' => 'tel',
		'priority'     => 40,
		'custom_attributes' => array(
			'inputmode' => 'numeric',
			'maxlength' => '14',
		),
	);

	$billing['billing_email'] = array(
		'label'        => __( 'ইমেইল', 'assurance' ),
		'placeholder'  => __( 'you@example.com', 'assurance' ),
		'required'     => false,
		'type'         => 'email',
		'class'        => array( 'form-row-last' ),
		'autocomplete' => 'email',
		'priority'     => 50,
	);

	$fields['billing'] = $billing;

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'assurance_checkout_fields', 20 );

/**
 * Drop the removed fields from the address form definition too.
 *
 * woocommerce_checkout_fields alone leaves company/city/postcode declared
 * in the locale, which makes them reappear in the My Account address form
 * and in admin order editing.
 *
 * @param array $fields Default address fields.
 * @return array
 */
function assurance_default_address_fields( $fields ) {
	unset(
		$fields['company'],
		$fields['address_2'],
		$fields['city'],
		$fields['postcode'],
		$fields['last_name']
	);

	if ( isset( $fields['country'] ) ) {
		// Locked to Bangladesh — the store does not ship abroad. Kept in the
		// data (so the address is valid) but never shown.
		$fields['country']['type']     = 'hidden';
		$fields['country']['required'] = false;
		$fields['country']['class']    = array( 'ap-hidden' );
	}

	if ( isset( $fields['first_name'] ) ) {
		$fields['first_name']['label'] = __( 'পুরো নাম', 'assurance' );
		$fields['first_name']['class'] = array( 'form-row-wide' );
	}

	if ( isset( $fields['state'] ) ) {
		$fields['state']['label'] = __( 'জেলা', 'assurance' );
	}

	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'assurance_default_address_fields', 20 );

/**
 * Bangladesh address format, without the fields we removed.
 *
 * Without this WooCommerce renders "{company}\n{address_2}\n{city}
 * {postcode}" as blank lines in order emails and the admin address block.
 *
 * @param array $formats Country formats.
 * @return array
 */
function assurance_bd_address_format( $formats ) {
	$formats['BD'] = "{name}\n{address_1}\n{state}\n{country}";

	return $formats;
}
add_filter( 'woocommerce_localisation_address_formats', 'assurance_bd_address_format' );

/**
 * Force Bangladesh as the customer country.
 *
 * The country field is hidden, so nothing populates it from the form; this
 * keeps the address valid and the shipping zone matchable.
 *
 * @param string $country Existing country.
 * @return string
 */
function assurance_default_country( $country ) {
	return 'BD';
}
add_filter( 'default_checkout_billing_country', 'assurance_default_country' );
add_filter( 'default_checkout_shipping_country', 'assurance_default_country' );

/**
 * Restrict selling to Bangladesh.
 *
 * @param string[] $countries Allowed countries.
 * @return string[]
 */
function assurance_allowed_countries( $countries ) {
	return array( 'BD' => isset( $countries['BD'] ) ? $countries['BD'] : 'Bangladesh' );
}
add_filter( 'woocommerce_countries_allowed_countries', 'assurance_allowed_countries' );
add_filter( 'woocommerce_countries_shipping_countries', 'assurance_allowed_countries' );

/**
 * Hide the bKash plugin's "bKash Charge" row.
 *
 * The row renders unconditionally at ৳0 whenever bKash is the selected
 * gateway — the plugin's own "Enable bKash Charge" setting is off, but it
 * never checked that before printing the row. Unhooking here rather than
 * editing the plugin means a plugin update won't undo it; if the charge
 * is ever turned on in bKash's settings, remove this to bring the row back.
 *
 * The same callback is also wired to the cart page's totals table, so both
 * places need unhooking.
 */
function assurance_hide_bkash_charge_row() {
	if ( ! function_exists( 'dc_bkash' ) || ! dc_bkash() ) {
		return;
	}

	remove_action(
		'woocommerce_review_order_before_order_total',
		array( dc_bkash()->gateway, 'dc_bkash_display_transaction_charge' )
	);
	remove_action(
		'woocommerce_cart_totals_before_order_total',
		array( dc_bkash()->gateway, 'dc_bkash_display_transaction_charge' )
	);
}
add_action( 'wp', 'assurance_hide_bkash_charge_row', 20 );

/**
 * Swap in real icons for the payment method buttons.
 *
 * Both gateways ship with their icon disabled ($this->icon = false/''), by
 * design of the vendor plugin and our own COD gateway, since the default
 * inline icon markup does not match this store's design. Supplying one
 * through the filter WooCommerce already calls means no template override
 * is needed for something this small.
 *
 * @param string $icon Existing icon HTML.
 * @param string $gateway_id Gateway id.
 * @return string
 */
function assurance_payment_gateway_icon( $icon, $gateway_id ) {
	if ( 'bkash' === $gateway_id ) {
		return '<img src="' . esc_url( ASSURANCE_URI . '/assets/images/bkash.svg' ) . '" class="ap-pay-icon" alt="" aria-hidden="true" />';
	}

	if ( 'assurance_cod' === $gateway_id ) {
		return '<img src="' . esc_url( ASSURANCE_URI . '/assets/images/cod.webp' ) . '" class="ap-pay-icon" alt="" aria-hidden="true" />';
	}

	return $icon;
}
add_filter( 'woocommerce_gateway_icon', 'assurance_payment_gateway_icon', 10, 2 );

/**
 * Drop the gateway descriptions at checkout.
 *
 * @param string $description Gateway description.
 * @param string $gateway_id  Gateway id.
 * @return string
 */
function assurance_gateway_description( $description, $gateway_id ) {
	return is_checkout() ? '' : $description;
}
add_filter( 'woocommerce_gateway_description', 'assurance_gateway_description', 10, 2 );

/**
 * The payment method the customer currently has selected, falling back to
 * whichever gateway WooCommerce would default to.
 *
 * @return string Gateway id, or '' if none are available.
 */
function assurance_chosen_payment_method() {
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return '';
	}

	$chosen = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';

	$available = WC()->payment_gateways()->get_available_payment_gateways();

	if ( $chosen && isset( $available[ $chosen ] ) ) {
		return $chosen;
	}

	return $available ? key( $available ) : '';
}

/**
 * Order button text that states what is actually being charged right now —
 * necessary because the COD gateway only collects the courier fee up front
 * and the rest at the door, which is not what "Place order" implies.
 *
 * @param string $text Default button text.
 * @return string
 */
function assurance_order_button_text( $text ) {
	if ( ! is_checkout() || ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_payment() ) {
		return $text;
	}

	$chosen = assurance_chosen_payment_method();

	if ( 'assurance_cod' === $chosen ) {
		$fee = function_exists( 'assurance_current_courier_fee' ) ? assurance_current_courier_fee() : 0;

		if ( $fee > 0 ) {
			return sprintf(
				/* translators: %s: courier fee amount. */
				__( 'অর্ডার কনফার্ম করুন  |  %s', 'assurance' ),
				html_entity_decode( wp_strip_all_tags( wc_price( $fee ) ) )
			);
		}

		return __( 'অর্ডার করুন', 'assurance' );
	}

	if ( 'bkash' === $chosen ) {
		return sprintf(
			/* translators: %s: order total amount. */
			__( 'অর্ডার কনফার্ম করুন  |  %s', 'assurance' ),
			html_entity_decode( wp_strip_all_tags( wc_price( WC()->cart->get_total( 'edit' ) ) ) )
		);
	}

	return $text;
}
add_filter( 'woocommerce_order_button_text', 'assurance_order_button_text' );

/**
 * A one-line explanation above the order button of what is being paid now
 * vs. at the door — the COD gateway's "pay the courier charge now, the book
 * price on delivery" flow is not something a shopper expects by default, so
 * it has to be spelled out at the exact moment they are about to confirm.
 */
function assurance_checkout_payment_note() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_payment() ) {
		return;
	}

	$chosen = assurance_chosen_payment_method();

	if ( ! $chosen ) {
		return;
	}

	$total = (float) WC()->cart->get_total( 'edit' );

	$heading = '';
	$rows    = array();
	$footer  = '';

	if ( 'assurance_cod' === $chosen ) {
		$fee = function_exists( 'assurance_current_courier_fee' ) ? assurance_current_courier_fee() : 0;

		if ( $fee > 0 ) {
			$heading = __( 'ক্যাশ অন ডেলিভারি — দুই ধাপে পরিশোধ', 'assurance' );
			$rows[]  = array( __( 'বিকাশে ডেলিভারি চার্জ পরিশোধ করুন', 'assurance' ), $fee, true );
			$rows[]  = array( __( 'বই হাতে পেয়ে মূল্য পরিশোধ করুন', 'assurance' ), max( 0, $total - $fee ), false );
			$footer  = __( 'ডেলিভারি চার্জটি অগ্রিম নেওয়া হয় যাতে অর্ডারটি নিশ্চিত করা যায়। বাকি টাকা বই বুঝে পাওয়ার পর রাইডারকে দেবেন।', 'assurance' );
		} else {
			$heading = __( 'ক্যাশ অন ডেলিভারি — ফ্রি ডেলিভারি', 'assurance' );
			$rows[]  = array( __( 'বই হাতে পেয়ে মূল্য পরিশোধ করুন', 'assurance' ), $total, true );
			$footer  = __( 'এখন কোনো টাকা দিতে হবে না। সম্পূর্ণ মূল্য ডেলিভারির সময় রাইডারকে পরিশোধ করবেন।', 'assurance' );
		}
	} elseif ( 'bkash' === $chosen ) {
		$heading = __( 'বিকাশে সম্পূর্ণ পেমেন্ট', 'assurance' );
		$rows[]  = array( __( 'সম্পূর্ণ টাকা বিকাশে পরিশোধ করুন', 'assurance' ), $total, true );
		$footer  = __( 'বইয়ের মূল্য ও ডেলিভারি চার্জ একসাথে এখনই পরিশোধ হবে। ডেলিভারির সময় আর কোনো টাকা দিতে হবে না।', 'assurance' );
	}

	if ( empty( $rows ) ) {
		return;
	}

	echo '<div class="ap-pay-note">';

	printf(
		'<p class="ap-pay-note__head">%s%s</p>',
		assurance_icon( 'info', array( 'size' => 15 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon markup.
		esc_html( $heading )
	);

	foreach ( $rows as $row ) {
		printf(
			'<div class="ap-pay-note__row%1$s"><span class="ap-pay-note__label">%2$s</span><span class="ap-pay-note__amount">%3$s</span></div>',
			$row[2] ? ' is-now' : '',
			esc_html( $row[0] ),
			wp_kses_post( wc_price( $row[1] ) )
		);
	}

	if ( $footer ) {
		printf( '<p class="ap-pay-note__foot">%s</p>', esc_html( $footer ) );
	}

	echo '</div>';
}
add_action( 'woocommerce_review_order_before_submit', 'assurance_checkout_payment_note' );

/**
 * Bangla wording for the terms-and-conditions error.
 *
 * This deliberately does NOT suppress the notice. An earlier version
 * returned '' here to keep the message off the page — but wc_add_notice()
 * skips empty messages entirely, so no error was ever registered,
 * wc_notice_count( 'error' ) stayed at zero and process_checkout() placed
 * the order without consent. The notice is the thing that blocks checkout,
 * so it has to survive; checkout.js stops the submit client-side and turns
 * the checkbox red, which means a shopper with JS never reaches this.
 *
 * @param string $message Notice text.
 * @return string
 */
function assurance_terms_notice_text( $message ) {
	$default = __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' );

	return $message === $default
		? __( 'অর্ডার করতে হলে শর্তাবলী ও নীতিমালায় সম্মতি দিন।', 'assurance' )
		: $message;
}
add_filter( 'woocommerce_add_error', 'assurance_terms_notice_text' );

/**
 * Drop WooCommerce's inline terms accordion.
 *
 * The core template prints the whole terms page into a hidden div under the
 * checkbox and expands it in place, which shoves the order button down the
 * page mid-checkout. The link opens our modal instead (checkout.js).
 */
function assurance_remove_inline_terms() {
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
}
add_action( 'wp', 'assurance_remove_inline_terms' );

/**
 * Move the payment block out of the order summary.
 *
 * Choosing a payment method belongs with the rest of the form the shopper is
 * filling in, not inside the read-only summary — and on a phone, where the
 * columns stack, it otherwise landed far below the order note. The template
 * re-emits it in the left column directly under the note.
 *
 * Safe to relocate: WC_AJAX::update_order_review() returns
 * '.woocommerce-checkout-payment' as its own fragment and swaps it by
 * selector, so the block still refreshes wherever it sits in the form.
 */
function assurance_move_payment_block() {
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
}
add_action( 'wp', 'assurance_move_payment_block' );

/**
 * The order button and its payment breakdown, rendered under the total.
 *
 * The other half of the split started in woocommerce/checkout/payment.php.
 * Kept inside the <form> so submission is unaffected, and re-emitted as its
 * own AJAX fragment (below) so the button label and the figures refresh with
 * the rest of the totals.
 */
function assurance_render_order_actions() {
	$button_text = apply_filters( 'woocommerce_order_button_text', __( 'অর্ডার করুন', 'assurance' ) );
	?>
	<div class="ap-order-actions">
		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php
		echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core filter, escaped below.
			'woocommerce_order_button_html',
			'<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $button_text ) . '" data-value="' . esc_attr( $button_text ) . '">' . esc_html( $button_text ) . '</button>'
		);
		?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
	</div>
	<?php
}

/**
 * Refresh the relocated actions alongside WooCommerce's own fragments.
 *
 * Without this the button label and the "pay now vs. on delivery" figures
 * would freeze at whatever they were on page load, because they no longer
 * sit inside the .woocommerce-checkout-payment block WooCommerce replaces.
 *
 * @param array $fragments Fragment map.
 * @return array
 */
function assurance_order_actions_fragment( $fragments ) {
	ob_start();
	assurance_render_order_actions();
	$fragments['.ap-order-actions'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'assurance_order_actions_fragment' );

/**
 * The estimated delivery window for the customer's current district band.
 *
 * @return DateTimeImmutable[] Empty when no district is selected yet.
 */
function assurance_delivery_window() {
	$band = assurance_delivery_band();

	if ( 'unknown' === $band ) {
		return array();
	}

	// Days from today. Orders placed after the cut-off are not handed to the
	// courier until the next working day, so the whole window shifts by one.
	$offsets = 'inside' === $band ? array( 1, 2 ) : array( 2, 3 );
	$offsets = (array) apply_filters( 'assurance_delivery_day_offsets', $offsets, $band );

	$now    = current_datetime();
	$cutoff = (int) apply_filters( 'assurance_delivery_cutoff_hour', 18 );

	if ( (int) $now->format( 'G' ) >= $cutoff ) {
		$offsets = array( $offsets[0] + 1, $offsets[1] + 1 );
	}

	return array(
		$now->modify( '+' . (int) $offsets[0] . ' days' ),
		$now->modify( '+' . (int) $offsets[1] . ' days' ),
	);
}

/**
 * @return string Empty when no district is selected yet.
 */
function assurance_delivery_eta_label() {
	$window = assurance_delivery_window();

	if ( empty( $window ) ) {
		return '';
	}

	return sprintf(
		/* translators: 1: window start date, 2: window end date. */
		__( 'প্রত্যাশিত ডেলিভারি: %1$s – %2$s', 'assurance' ),
		'<strong>' . esc_html( wp_date( 'd M', $window[0]->getTimestamp() ) ) . '</strong>',
		'<strong>' . esc_html( wp_date( 'd M, Y', $window[1]->getTimestamp() ) ) . '</strong>'
	);
}

/**
 * The courier formula spelled out for the current cart, e.g. "ভিত্তি ৳100 +
 * (৳10 × ৩ বই) = ৳130" — so a shopper adding a second or third book can see
 * why the delivery charge just went up instead of assuming an error.
 *
 * @return string Empty when shipping is free or nothing is in the cart.
 */
function assurance_shipping_breakdown_label() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	if ( WC()->cart->get_displayed_subtotal() >= ASSURANCE_FREE_SHIPPING_MIN ) {
		return '';
	}

	$qty = WC()->cart->get_cart_contents_count();

	if ( $qty < 1 ) {
		return '';
	}

	$state        = WC()->customer ? WC()->customer->get_shipping_state() : '';
	$inside_dhaka = assurance_is_inside_dhaka( $state );
	$base         = $inside_dhaka ? ASSURANCE_RATE_INSIDE_DHAKA : ASSURANCE_RATE_OUTSIDE_DHAKA;

	if ( $qty <= 1 ) {
		return sprintf(
			/* translators: %s: base delivery charge. */
			__( '১টি বইয়ের ভিত্তি চার্জ %s', 'assurance' ),
			wp_kses_post( wc_price( $base ) )
		);
	}

	return sprintf(
		/* translators: 1: base charge, 2: per-book charge, 3: quantity, 4: total charge. */
		__( 'ভিত্তি চার্জ %1$s + (%2$s × %3$d বই) = %4$s', 'assurance' ),
		wp_kses_post( wc_price( $base ) ),
		wp_kses_post( wc_price( 10 ) ),
		$qty,
		wp_kses_post( wc_price( assurance_courier_cost( $inside_dhaka, $qty ) ) )
	);
}

/**
 * Print the ETA + breakdown note under the checkout's shipping row.
 */
function assurance_checkout_shipping_note() {
	$eta       = assurance_delivery_eta_label();
	$breakdown = assurance_shipping_breakdown_label();

	if ( ! $eta && ! $breakdown ) {
		return;
	}

	printf(
		'<tr class="ap-shipping-note-row"><td colspan="2"><div class="ap-shipnote">%s%s</div></td></tr>',
		$eta
			? '<span class="ap-shipnote__line">' . assurance_icon( 'clock', array( 'size' => 14 ) ) . '<span>' . wp_kses_post( $eta ) . '</span></span>'
			: '',
		$breakdown
			? '<span class="ap-shipnote__line">' . assurance_icon( 'truck', array( 'size' => 14 ) ) . '<span>' . wp_kses_post( $breakdown ) . '</span></span>'
			: ''
	);
}
add_action( 'woocommerce_review_order_after_shipping', 'assurance_checkout_shipping_note' );

/**
 * Distraction-free checkout: no header, no footer, no nav to click away to.
 *
 * @param bool $enabled Whether Blocksy would render the builder area.
 * @return bool
 */
function assurance_checkout_hide_chrome( $enabled ) {
	return is_checkout() ? false : $enabled;
}
add_filter( 'blocksy:builder:header:enabled', 'assurance_checkout_hide_chrome' );
add_filter( 'blocksy:builder:footer:enabled', 'assurance_checkout_hide_chrome' );

/**
 * Minimal branded top bar to replace the removed header on checkout.
 */
function assurance_checkout_brand_bar() {
	if ( ! is_checkout() ) {
		return;
	}
	?>
	<div class="ap-checkout__brand">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_before_checkout_form', 'assurance_checkout_brand_bar', 0 );

/**
 * A way off the order-received screen.
 *
 * is_checkout() is true on the thank-you endpoint too, so the header and
 * footer are both suppressed there by assurance_checkout_hide_chrome() —
 * and the branded top bar above only runs on woocommerce_before_checkout_form,
 * which the thank-you template never fires. The result is a page with no
 * link off it at all once the order is placed.
 *
 * Priority 30 puts this after the order table (woocommerce_thankyou, 10) and
 * the invoice block that hangs off it at 20, so it reads as the last step.
 * Hooking woocommerce_thankyou rather than the shared order-details action
 * keeps it off the My Account order view, which has its own navigation.
 *
 * @param int $order_id Order ID.
 */
function assurance_thankyou_home_action( $order_id ) {
	if ( ! $order_id ) {
		return;
	}
	?>
	<div class="ap-thankyou-actions">
		<a class="ap-btn ap-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php assurance_the_icon( 'chevron-left', array( 'size' => 18 ) ); ?>
			<?php esc_html_e( 'হোমপেজে ফিরে যান', 'assurance' ); ?>
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_thankyou', 'assurance_thankyou_home_action', 30 );

/**
 * Validate the mobile number.
 *
 * Bangladeshi mobiles are 11 digits starting 013–019, optionally written
 * with a +880 or 880 prefix. Anything else is a typo that will cost the
 * shop a failed delivery, so it is worth catching at the form.
 */
function assurance_validate_phone() {
	// Nonce is verified by WooCommerce before this hook fires.
	$raw = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' === $raw ) {
		return;
	}

	$digits = preg_replace( '/\D+/', '', $raw );

	// Normalise +8801XXXXXXXXX and 8801XXXXXXXXX down to 01XXXXXXXXX.
	if ( 13 === strlen( $digits ) && 0 === strpos( $digits, '880' ) ) {
		$digits = substr( $digits, 3 );
	} elseif ( 12 === strlen( $digits ) && 0 === strpos( $digits, '88' ) ) {
		$digits = substr( $digits, 2 );
	}

	if ( ! preg_match( '/^01[3-9]\d{8}$/', $digits ) ) {
		wc_add_notice(
			__( 'সঠিক মোবাইল নম্বর দিন (যেমন ০১৭XXXXXXXX)', 'assurance' ),
			'error'
		);
		return;
	}

	// Store the normalised form so the courier integration and SMS gateway
	// always receive the same shape regardless of how it was typed.
	$_POST['billing_phone'] = $digits;
}
add_action( 'woocommerce_after_checkout_validation', 'assurance_validate_phone', 10, 0 );

/**
 * Relabel the order-note field.
 *
 * @param array $fields Order fields.
 * @return array
 */
function assurance_order_fields( $fields ) {
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = __( 'অর্ডার সম্পর্কে নোট', 'assurance' );
		$fields['order']['order_comments']['placeholder'] = __( 'ডেলিভারি সংক্রান্ত বিশেষ নির্দেশনা থাকলে লিখুন', 'assurance' );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'assurance_order_fields', 30 );

/**
 * Copy billing to shipping on save.
 *
 * The shipping field set is empty, so WooCommerce would otherwise store a
 * blank shipping address and the courier plugin would have nothing to read.
 *
 * @param WC_Order $order Order being created.
 */
function assurance_copy_billing_to_shipping( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$order->set_shipping_first_name( $order->get_billing_first_name() );
	$order->set_shipping_last_name( $order->get_billing_last_name() );
	$order->set_shipping_address_1( $order->get_billing_address_1() );
	$order->set_shipping_state( $order->get_billing_state() );
	$order->set_shipping_country( $order->get_billing_country() );

	if ( is_callable( array( $order, 'set_shipping_phone' ) ) ) {
		$order->set_shipping_phone( $order->get_billing_phone() );
	}
}
add_action( 'woocommerce_checkout_create_order', 'assurance_copy_billing_to_shipping', 20 );

/**
 * Make the district select a searchable control without pulling in
 * select2's stylesheet fight.
 *
 * WooCommerce enqueues select2 for state fields by default. With only 64
 * options and a custom-styled native select that already matches the
 * design, select2 adds ~90 KB and its own inconsistent styling for no
 * usability gain on mobile — where the native picker is better anyway.
 */
function assurance_dequeue_select2() {
	if ( ! is_checkout() && ! is_cart() ) {
		return;
	}

	wp_dequeue_style( 'select2' );
	wp_dequeue_script( 'select2' );
	wp_dequeue_script( 'selectWoo' );
	wp_dequeue_style( 'selectWoo' );
}
add_action( 'wp_enqueue_scripts', 'assurance_dequeue_select2', 100 );

/**
 * Expose the delivery band to the order, so admin and the courier
 * integration can read it without recomputing from the district.
 *
 * @param WC_Order $order Order.
 */
function assurance_store_delivery_band( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$state = $order->get_billing_state();

	$order->update_meta_data(
		'_assurance_delivery_band',
		assurance_is_inside_dhaka( $state ) ? 'inside' : 'outside'
	);
}
add_action( 'woocommerce_checkout_create_order', 'assurance_store_delivery_band', 30 );

/**
 * Show the district in Bangla on the admin order screen.
 *
 * Admin keeps WooCommerce's English state labels (see inc/districts.php),
 * so this adds the Bangla name alongside rather than replacing it —
 * useful when the shop calls the customer to confirm.
 *
 * @param WC_Order $order Order.
 */
function assurance_admin_district_row( $order ) {
	$state = $order->get_billing_state();

	if ( ! $state ) {
		return;
	}

	printf(
		'<p><strong>%s:</strong> %s</p>',
		esc_html__( 'জেলা', 'assurance' ),
		esc_html( assurance_district_name( $state ) )
	);
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'assurance_admin_district_row' );
