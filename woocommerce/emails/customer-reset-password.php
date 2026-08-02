<?php
/**
 * Customer email: password reset link.
 *
 * @package Assurance
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php printf( esc_html__( 'অ্যাসসালামু আলাইকুম, %s,', 'assurance' ), esc_html( $user_login ) ); ?></p>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'আপনার অ্যাকাউন্টের পাসওয়ার্ড রিসেট করার অনুরোধ পাওয়া গেছে। নিচের বোতামে ক্লিক করে নতুন পাসওয়ার্ড দিন।', 'assurance' ); ?></p>

<table cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
	<tr>
		<td style="background-color:<?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px;">
			<a href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>" style="display:inline-block; padding:13px 28px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;"><?php esc_html_e( 'নতুন পাসওয়ার্ড সেট করুন', 'assurance' ); ?></a>
		</td>
	</tr>
</table>

<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 20px;">
	<tr>
		<td style="padding:14px 16px; background-color:<?php echo esc_attr( $palette['brass_tint'] ); ?>; border-left:3px solid <?php echo esc_attr( $palette['brass'] ); ?>; border-radius:6px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'আপনি যদি এই অনুরোধ না করে থাকেন, তবে এই ইমেইলটি উপেক্ষা করুন — আপনার পাসওয়ার্ড অপরিবর্তিত থাকবে।', 'assurance' ); ?></td>
	</tr>
</table>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
