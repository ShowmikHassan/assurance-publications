<?php
/**
 * Delivery address block.
 *
 * The store collects one address and copies billing to shipping, so printing
 * both columns would show the same lines twice. The district is appended in
 * Bangla because WooCommerce stores it as a BD-xx code.
 *
 * @package Assurance
 * @version 9.8.0 (base)
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order Supplied by wc_get_template(). */
if ( ! isset( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$palette = assurance_email_palette();
$font    = assurance_email_font();

$address  = $order->get_formatted_billing_address();
$phone    = $order->get_billing_phone();
$email_to = $order->get_billing_email();
$district = function_exists( 'assurance_district_name' ) && $order->get_billing_state()
	? assurance_district_name( $order->get_billing_state() )
	: '';
?>

<h2 style="font-family:<?php echo esc_attr( $font ); ?>; font-size:16px; font-weight:700; color:<?php echo esc_attr( $palette['ink'] ); ?>; margin:28px 0 10px;">
	<?php esc_html_e( 'ডেলিভারির ঠিকানা', 'assurance' ); ?>
</h2>

<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin-bottom:24px;">
	<tr>
		<td style="padding:15px 17px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; border-radius:8px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; line-height:1.8; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;">
			<strong style="color:<?php echo esc_attr( $palette['ink'] ); ?>;"><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></strong><br />
			<?php
			if ( $address ) {
				// Drop the name line core puts at the top of the block, since
				// it is printed above in the shop's own weight. Split on any
				// <br> shape because the separator has changed across versions.
				$lines = preg_split( '#<br\s*/?>#i', $address );
				array_shift( $lines );
				echo wp_kses_post( implode( '<br />', array_filter( $lines ) ) );
			}
			?>

			<?php if ( $district ) : ?>
				<br /><?php esc_html_e( 'জেলা', 'assurance' ); ?>: <?php echo esc_html( $district ); ?>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<br /><?php esc_html_e( 'মোবাইল', 'assurance' ); ?>:
				<a href="tel:<?php echo esc_attr( $phone ); ?>" style="color:<?php echo esc_attr( $palette['ink_soft'] ); ?>; text-decoration:none;"><?php echo esc_html( $phone ); ?></a>
			<?php endif; ?>

			<?php if ( $email_to ) : ?>
				<br /><?php esc_html_e( 'ইমেইল', 'assurance' ); ?>:
				<a href="mailto:<?php echo esc_attr( $email_to ); ?>" style="color:<?php echo esc_attr( $palette['ink_soft'] ); ?>; text-decoration:none;"><?php echo esc_html( $email_to ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
</table>
