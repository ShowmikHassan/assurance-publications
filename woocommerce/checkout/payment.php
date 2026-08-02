<?php
/**
 * Checkout payment section — method tiles and the terms row only.
 *
 * The submit button and the payment breakdown are deliberately NOT here.
 * They are rendered by assurance_render_order_actions() in the summary
 * column instead, so the shopper confirms under the total.
 *
 * That split has to happen server-side. WooCommerce rebuilds this whole
 * block on every AJAX totals update and swaps it in by selector, so moving
 * the button out with JS just left the previous copy behind and produced a
 * second button on each refresh.
 *
 * @package Assurance
 * @version 9.8.0 (base)
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<h2 class="ap-panel__title ap-pay-methods__heading"><?php esc_html_e( 'পেমেন্ট অপশন', 'assurance' ); ?></h2>
		<ul class="wc_payment_methods payment_methods methods ap-pay-methods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li>';
				wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="form-row place-order">
		<?php wc_get_template( 'checkout/terms.php' ); ?>
		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
