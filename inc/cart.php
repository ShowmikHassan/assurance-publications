<?php
/**
 * Cart page behaviour.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Free-shipping progress above the cart totals.
 */
function assurance_cart_free_shipping_bar() {
	$progress = assurance_free_shipping_progress();

	if ( ! $progress['show'] ) {
		return;
	}
	?>
	<div class="ap-freeship<?php echo $progress['met'] ? ' is-met' : ''; ?>">
		<p class="ap-freeship__text">
			<?php if ( $progress['met'] ) : ?>
				<?php assurance_the_icon( 'truck', array( 'size' => 16 ) ); ?>
				<strong><?php esc_html_e( 'ফ্রি ডেলিভারি উপভোগ করুন', 'assurance' ); ?></strong>
			<?php else : ?>
				<?php
				printf(
					/* translators: %s: formatted remaining amount. */
					esc_html__( 'আর %sবই কিনলেই ফ্রি ডেলিভারি', 'assurance' ),
					'<strong>' . wp_kses_post( wc_price( $progress['remaining'] ) ) . '</strong>'
				);
				?>
			<?php endif; ?>
		</p>
		<div
			class="ap-freeship__track"
			role="progressbar"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo esc_attr( (string) round( $progress['percent'] ) ); ?>"
			aria-label="<?php esc_attr_e( 'ফ্রি ডেলিভারির অগ্রগতি', 'assurance' ); ?>"
		>
			<span class="ap-freeship__fill" style="width:<?php echo esc_attr( (string) $progress['percent'] ); ?>%">
				<span class="ap-freeship__truck" aria-hidden="true"><?php assurance_the_icon( 'truck', array( 'size' => 12 ) ); ?></span>
			</span>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_cart_totals', 'assurance_cart_free_shipping_bar', 5 );

/**
 * "আরও কিনুন" carousel below the cart items.
 *
 * Replaces WooCommerce's default cross-sells block. Cards are paired into
 * columns of two and rendered in the compact horizontal style so the strip
 * matches the cart items column's width instead of running full-bleed.
 */
function assurance_cart_suggestions_carousel() {
	$ids = assurance_cart_suggestions( 8 );

	if ( count( $ids ) < 2 ) {
		return;
	}
	?>
	<section class="ap-band ap-suggest" data-ap-suggest>
		<div class="ap-section-head">
			<div class="ap-section-head__text">
				<span class="ap-eyebrow"><?php esc_html_e( 'জনপ্রিয়', 'assurance' ); ?></span>
				<h2 class="ap-section-title"><?php esc_html_e( 'আরও কিনুন', 'assurance' ); ?></h2>
			</div>
			<div class="ap-scroller-nav">
				<button type="button" class="ap-icon-btn" data-ap-scroll="-1" aria-label="<?php esc_attr_e( 'আগের', 'assurance' ); ?>">
					<?php assurance_the_icon( 'chevron-left', array( 'size' => 18 ) ); ?>
				</button>
				<button type="button" class="ap-icon-btn" data-ap-scroll="1" aria-label="<?php esc_attr_e( 'পরের', 'assurance' ); ?>">
					<?php assurance_the_icon( 'chevron-right', array( 'size' => 18 ) ); ?>
				</button>
			</div>
		</div>

		<?php
		/*
		 * Cells are flat siblings, not paired into a column wrapper — CSS
		 * decides how many sit per "page" (two side by side on desktop,
		 * one on mobile) via flex-basis, and the arrow buttons scroll by
		 * the track's own visible width, so one click always advances
		 * exactly one page at whatever size that page currently is.
		 */
		?>
		<div class="ap-scroller ap-suggest__track" data-ap-suggest-track>
			<?php foreach ( $ids as $id ) : ?>
				<div class="ap-suggest__cell" data-product-id="<?php echo esc_attr( $id ); ?>">
					<?php assurance_the_product_card( $id, array( 'size' => 'compact', 'context' => 'cart-suggest', 'buy_now' => false ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * WooCommerce's own cross-sell block is replaced by the carousel above.
 */
function assurance_remove_default_cross_sells() {
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
}
add_action( 'wp', 'assurance_remove_default_cross_sells' );

/**
 * Serve a replacement card for the suggestions carousel.
 *
 * When a shopper adds an item from the carousel, that cell is removed and
 * backfilled with the next popular book they do not already have — so the
 * strip never collapses to a gap mid-interaction.
 */
function assurance_ajax_cart_suggestions() {
	assurance_verify_request();

	$shown = isset( $_POST['shown'] ) && is_array( $_POST['shown'] )
		? array_map( 'absint', wp_unslash( $_POST['shown'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint is the sanitiser.
		: array();

	$ids = assurance_cart_suggestions( 1, $shown );

	if ( empty( $ids ) ) {
		wp_send_json_success( array( 'html' => '', 'productId' => 0 ) );
	}

	$id = (int) $ids[0];

	wp_send_json_success(
		array(
			'productId' => $id,
			'html'      => sprintf(
				'<div class="ap-suggest__cell" data-product-id="%d">%s</div>',
				$id,
				assurance_product_card( $id, array( 'size' => 'compact', 'context' => 'cart-suggest', 'buy_now' => false ) )
			),
		)
	);
}
assurance_ajax( 'cart_suggestions', 'assurance_ajax_cart_suggestions' );

/**
 * Show the delivery band, ETA and per-book charge breakdown on the cart
 * totals shipping row — so the shopper sees why the fee is what it is and
 * roughly when the book will arrive, not just the final number.
 */
function assurance_cart_shipping_note() {
	$band = assurance_delivery_band();

	if ( 'unknown' === $band ) {
		return;
	}

	$eta       = function_exists( 'assurance_delivery_eta_label' ) ? assurance_delivery_eta_label() : '';
	$breakdown = function_exists( 'assurance_shipping_breakdown_label' ) ? assurance_shipping_breakdown_label() : '';

	printf(
		'<tr class="ap-band-row"><th>%s</th><td>%s</td></tr>',
		esc_html__( 'ডেলিভারি এলাকা', 'assurance' ),
		esc_html( assurance_delivery_band_label() )
	);

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
add_action( 'woocommerce_cart_totals_after_shipping', 'assurance_cart_shipping_note' );

/**
 * Trim the cart's shipping-address calculator down to just the district
 * dropdown. Country is locked to Bangladesh already (one option, so it adds
 * nothing), and city/postcode are fields this store never collects at
 * checkout either — showing them here would let a shopper "confirm" an
 * address shape the real checkout form doesn't even ask for.
 */
function assurance_trim_shipping_calculator() {
	if ( ! is_cart() ) {
		return;
	}

	add_filter( 'woocommerce_shipping_calculator_enable_country', '__return_false' );
	add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_false' );
	add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_false' );
}
add_action( 'wp', 'assurance_trim_shipping_calculator' );

/**
 * Put the country back into the calculator's submitted address.
 *
 * Hiding the country field above means $_POST['calc_shipping_country'] is
 * never sent, and WC_Shortcode_Cart::calculate_shipping() treats an empty
 * country as "reset to store base" — which wiped the district the shopper
 * had just picked and left the delivery charge unchanged. The store only
 * ships to Bangladesh, so the country is a constant, not user input.
 *
 * @param array $address Submitted calculator address.
 * @return array
 */
function assurance_calculator_address( $address ) {
	$address['country'] = 'BD';

	return $address;
}
add_filter( 'woocommerce_cart_calculate_shipping_address', 'assurance_calculator_address' );

/**
 * Carry the district chosen in the cart calculator over to checkout.
 *
 * WooCommerce only copies the calculator's address to the billing address
 * while the customer has no billing first name saved, so a returning
 * shopper's checkout district select would still show the old district
 * while the cart quoted the new one. The checkout's district field reads
 * the billing state, so it has to be written explicitly.
 */
function assurance_sync_calculated_district() {
	if ( ! WC()->customer ) {
		return;
	}

	$state = WC()->customer->get_shipping_state();

	if ( '' === $state ) {
		return;
	}

	WC()->customer->set_billing_country( 'BD' );
	WC()->customer->set_billing_state( $state );
	WC()->customer->save();
}
add_action( 'woocommerce_calculated_shipping', 'assurance_sync_calculated_district' );

/**
 * Rename WooCommerce's "Shipment"/"Shipping N" package heading.
 *
 * @param string $name Package name.
 * @return string
 */
function assurance_shipping_package_name( $name ) {
	return __( 'চালান', 'assurance' );
}
add_filter( 'woocommerce_shipping_package_name', 'assurance_shipping_package_name' );
