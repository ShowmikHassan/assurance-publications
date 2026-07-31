<?php
/**
 * Customer email: order received and confirmed.
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

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php printf( esc_html__( 'অ্যাসসালামু আলাইকুম, %s,', 'assurance' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'আপনার অর্ডারটি আমরা পেয়েছি এবং প্রস্তুত করা শুরু করেছি। বই প্যাক হয়ে কুরিয়ারে দেওয়ার সাথে সাথে আপনাকে জানানো হবে।', 'assurance' ); ?></p>

<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 20px;">
	<tr>
		<td style="padding:14px 16px; background-color:<?php echo esc_attr( $palette['accent_tint'] ); ?>; border-left:3px solid <?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><strong style="color:<?php echo esc_attr( $palette['ink'] ); ?>;"><?php esc_html_e( 'পরবর্তী ধাপ', 'assurance' ); ?>:</strong> <?php esc_html_e( 'আমাদের একজন প্রতিনিধি অর্ডারটি নিশ্চিত করতে আপনার নম্বরে কল করতে পারেন। দয়া করে ফোনটি খেয়াল রাখুন।', 'assurance' ); ?></td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_footer', $email );
