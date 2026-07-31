<?php
/**
 * Admin email: an order was cancelled.
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php
	/* translators: 1: order number, 2: customer name. */
	printf(
		esc_html__( 'অর্ডার #%1$s (%2$s) বাতিল হয়েছে।', 'assurance' ),
		esc_html( $order->get_order_number() ),
		esc_html( $order->get_formatted_billing_full_name() )
	);
	?></p>

<table cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
	<tr>
		<td style="background-color:<?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px;">
			<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>" style="display:inline-block; padding:13px 28px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;"><?php esc_html_e( 'অর্ডারটি দেখুন', 'assurance' ); ?></a>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_footer', $email );
