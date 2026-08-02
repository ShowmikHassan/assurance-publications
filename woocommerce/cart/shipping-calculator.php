<?php
/**
 * Shipping calculator.
 *
 * Reduced to the one field this store actually prices on — the district.
 * Country is fixed to Bangladesh (restored server-side in inc/cart.php),
 * and city/postcode are not collected at checkout either, so offering them
 * here would invite an address shape the real order form never asks for.
 *
 * WooCommerce's own label for the state field is "State / County", which
 * translates into Bangla as রাজ্য / কাউন্টি — meaningless for Bangladeshi
 * addresses. Overriding the template is the only way to relabel it without
 * also renaming the field everywhere else it appears.
 *
 * @package Assurance
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_shipping_calculator' );
?>

<form class="woocommerce-shipping-calculator ap-calc" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<?php printf( '<a href="#" class="shipping-calculator-button ap-calc__toggle" aria-expanded="false" aria-controls="shipping-calculator-form" role="button">%s</a>', esc_html( ! empty( $button_text ) ? $button_text : __( 'ঠিকানা পরিবর্তন করুন', 'assurance' ) ) ); ?>

	<section class="shipping-calculator-form ap-calc__form" id="shipping-calculator-form" style="display:none;">
		<p class="form-row form-row-wide" id="calc_shipping_state_field">
			<?php
			$current_r = WC()->customer->get_shipping_state();
			$states    = WC()->countries->get_states( 'BD' );

			if ( is_array( $states ) && ! empty( $states ) ) :
				?>
				<label for="calc_shipping_state"><?php esc_html_e( 'শহর / জেলা', 'assurance' ); ?></label>
				<select name="calc_shipping_state" class="state_select" id="calc_shipping_state">
					<option value=""><?php esc_html_e( 'জেলা নির্বাচন করুন', 'assurance' ); ?></option>
					<?php foreach ( $states as $ckey => $cvalue ) : ?>
						<option value="<?php echo esc_attr( $ckey ); ?>" <?php selected( $current_r, $ckey ); ?>><?php echo esc_html( $cvalue ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<label for="calc_shipping_state"><?php esc_html_e( 'শহর / জেলা', 'assurance' ); ?></label>
				<input type="text" class="input-text" value="<?php echo esc_attr( $current_r ); ?>" name="calc_shipping_state" id="calc_shipping_state" />
			<?php endif; ?>
		</p>

		<input type="hidden" name="calc_shipping_country" value="BD" />

		<p class="ap-calc__actions">
			<button type="submit" name="calc_shipping" value="1" class="ap-btn ap-btn--outline ap-btn--block"><?php esc_html_e( 'আপডেট করুন', 'assurance' ); ?></button>
		</p>
		<?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
	</section>
</form>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>
