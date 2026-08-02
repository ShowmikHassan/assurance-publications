<?php
/**
 * Single payment method tile.
 *
 * The radio is kept because it is what submits the choice and what
 * WooCommerce's checkout.js listens to, but it is never shown; the tile is
 * the whole control. $gateway->payment_fields() still runs inside a hidden
 * wrapper so a gateway that declares has_fields can bootstrap itself.
 *
 * @package Assurance
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Payment_Gateway $gateway Supplied by wc_get_template(). */
?>
<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?> ap-pay">
	<input
		id="payment_method_<?php echo esc_attr( $gateway->id ); ?>"
		type="radio"
		class="input-radio ap-pay__input"
		name="payment_method"
		value="<?php echo esc_attr( $gateway->id ); ?>"
		<?php checked( $gateway->chosen, true ); ?>
		data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>"
		style="display:none!important;"
	/>

	<label class="ap-pay__tile" for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
		<span class="ap-pay__logo"><?php echo wp_kses_post( $gateway->get_icon() ); ?></span>
		<span class="ap-pay__name screen-reader-text"><?php echo wp_kses_post( $gateway->get_title() ); ?></span>
	</label>

	<?php if ( $gateway->has_fields() ) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?> ap-pay__fields">
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
