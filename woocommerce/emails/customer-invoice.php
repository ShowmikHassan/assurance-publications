<?php
/**
 * Customer email: order details / invoice, sent manually from admin.
 *
 * @package Assurance
 * @version 10.4.0
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

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php
	if ( $order->needs_payment() ) {
		printf(
			/* translators: %1$s: pay link open tag, %2$s: close tag. */
			wp_kses_post( __( 'আপনার অর্ডারটি এখনও পরিশোধ করা হয়নি। %1$sএখানে ক্লিক করে পরিশোধ করুন%2$s।', 'assurance' ) ),
			'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" style="color:' . esc_attr( $palette['accent'] ) . '; font-weight:700;">',
			'</a>'
		);
	} else {
		esc_html_e( 'আপনার অনুরোধ অনুযায়ী অর্ডারের সম্পূর্ণ বিবরণ নিচে দেওয়া হলো।', 'assurance' );
	}
	?></p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_footer', $email );
