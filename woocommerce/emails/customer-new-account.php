<?php
/**
 * Customer email: account created.
 *
 * @package Assurance
 * @version 9.8.0 (base)
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php printf( esc_html__( 'অ্যাসসালামু আলাইকুম, %s,', 'assurance' ), esc_html( $user_login ) ); ?></p>

<p style="margin:0 0 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php printf( esc_html__( '%s-এ আপনার অ্যাকাউন্ট তৈরি হয়েছে। এখন থেকে অর্ডার ট্র্যাক করা ও পুরনো অর্ডার দেখা আরও সহজ।', 'assurance' ), esc_html( $blogname ) ); ?></p>

<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin:0 0 20px;">
	<tr>
		<td style="padding:14px 16px; background-color:<?php echo esc_attr( $palette['accent_tint'] ); ?>; border-left:3px solid <?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'আপনার ইউজারনেম', 'assurance' ); ?>: <strong style="color:<?php echo esc_attr( $palette['ink'] ); ?>;"><?php echo esc_html( $user_login ); ?></strong></td>
	</tr>
</table>

<?php if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated ) : ?>
	<table cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
	<tr>
		<td style="background-color:<?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px;">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" style="display:inline-block; padding:13px 28px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;"><?php esc_html_e( 'পাসওয়ার্ড সেট করুন', 'assurance' ); ?></a>
		</td>
	</tr>
</table>
<?php else : ?>
	<table cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
	<tr>
		<td style="background-color:<?php echo esc_attr( $palette['accent'] ); ?>; border-radius:6px;">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" style="display:inline-block; padding:13px 28px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none;"><?php esc_html_e( 'আমার অ্যাকাউন্টে যান', 'assurance' ); ?></a>
		</td>
	</tr>
</table>
<?php endif; ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
