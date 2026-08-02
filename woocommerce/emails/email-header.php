<?php
/**
 * Email header.
 *
 * Table-based with inline styles because mail clients strip <style> blocks
 * and external CSS. WooCommerce also runs the finished HTML through an
 * inliner using email-styles.php, so classes there reach Outlook too.
 *
 * @package Assurance
 * @version 10.7.0
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();
$logo    = get_option( 'woocommerce_email_header_image' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="color-scheme" content="light only" />
	<meta name="supported-color-schemes" content="light only" />
	<title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
</head>
<body <?php echo is_rtl() ? 'dir="rtl"' : 'dir="ltr"'; ?> style="margin:0; padding:0; width:100%; background-color:<?php echo esc_attr( $palette['paper'] ); ?>; font-family:<?php echo esc_attr( $font ); ?>; -webkit-font-smoothing:antialiased;">
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:<?php echo esc_attr( $palette['paper'] ); ?>;">
		<tr>
			<td align="center" valign="top" style="padding:32px 12px;">

				<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="width:100%; max-width:600px; background-color:<?php echo esc_attr( $palette['surface'] ); ?>; border-radius:14px; overflow:hidden;">

					<!-- Masthead -->
					<tr>
						<td id="template_header" style="padding:30px 34px; background-color:<?php echo esc_attr( $palette['ink'] ); ?>; background-image:linear-gradient(135deg,#081226 0%,#16305c 55%,<?php echo esc_attr( $palette['ink'] ); ?> 100%);">
							<?php if ( $logo ) : ?>
								<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" height="40" style="display:block; height:40px; width:auto; border:0; margin-bottom:14px;" />
							<?php else : ?>
								<span style="display:block; font-size:11px; font-weight:700; letter-spacing:0.09em; color:<?php echo esc_attr( $palette['accent'] ); ?>; text-transform:uppercase; margin-bottom:8px;">
									<?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?>
								</span>
							<?php endif; ?>

							<h1 style="margin:0; padding:0; font-family:<?php echo esc_attr( $font ); ?>; font-size:22px; line-height:1.45; font-weight:700; color:#ffffff;">
								<?php echo wp_kses_post( $email_heading ); ?>
							</h1>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<td id="template_body" style="padding:30px 34px 8px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; line-height:1.75; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;">
