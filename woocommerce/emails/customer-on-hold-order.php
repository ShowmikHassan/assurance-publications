<?php
/**
 * Customer email: order on hold, awaiting verification.
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

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'আপনার অর্ডারটি আমরা পেয়েছি, তবে পেমেন্টটি যাচাই করা বাকি আছে। যাচাই শেষ হলেই অর্ডারটি প্রক্রিয়াকরণ শুরু হবে।', 'assurance' ); ?></p>

<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 20px;">
	<tr>
		<td style="padding:14px 16px; background-color:<?php echo esc_attr( $palette['brass_tint'] ); ?>; border-left:3px solid <?php echo esc_attr( $palette['brass'] ); ?>; border-radius:6px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'যদি আপনি ইতিমধ্যে পেমেন্ট করে থাকেন, তবে ট্রানজেকশন আইডিটি সহ আমাদের সাথে যোগাযোগ করুন।', 'assurance' ); ?></td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_footer', $email );
