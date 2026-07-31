<?php
/**
 * Email styles.
 *
 * WooCommerce runs this through an inliner, so these rules end up as inline
 * style attributes on the matching elements — which is the only reliable way
 * to style the order tables that core builds for us.
 *
 * @package Assurance
 * @version 9.8.0 (base)
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();
?>
body {
	margin: 0;
	padding: 0;
	background-color: <?php echo esc_attr( $palette['paper'] ); ?>;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
	font-size: 15px;
	line-height: 1.75;
}

p {
	margin: 0 0 16px;
}

a {
	color: <?php echo esc_attr( $palette['accent'] ); ?>;
	font-weight: 600;
	text-decoration: none;
}

h2 {
	margin: 30px 0 12px;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	font-size: 16px;
	font-weight: 700;
	line-height: 1.4;
	color: <?php echo esc_attr( $palette['ink'] ); ?>;
}

h3 {
	margin: 22px 0 8px;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	font-size: 14px;
	font-weight: 700;
	color: <?php echo esc_attr( $palette['ink'] ); ?>;
}

/* Order items table built by email-order-items.php. */
#body_content table.td,
.td {
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
	border: 0;
}

table.order_details,
table.shop_table {
	width: 100%;
	border-collapse: collapse;
	border: 1px solid <?php echo esc_attr( $palette['line'] ); ?>;
	border-radius: 8px;
	margin-bottom: 24px;
}

table.order_details th,
table.shop_table th {
	padding: 11px 14px;
	background-color: <?php echo esc_attr( $palette['surface_alt'] ); ?>;
	border-bottom: 1px solid <?php echo esc_attr( $palette['line'] ); ?>;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.04em;
	color: <?php echo esc_attr( $palette['ink_muted'] ); ?>;
	text-align: left;
	text-transform: uppercase;
}

table.order_details td,
table.shop_table td {
	padding: 13px 14px;
	border-bottom: 1px solid <?php echo esc_attr( $palette['line'] ); ?>;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	font-size: 14px;
	line-height: 1.6;
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
	vertical-align: top;
}

table.order_details td.td,
table.shop_table td.td {
	text-align: left;
}

table.order_details tfoot td,
table.shop_table tfoot td,
table.order_details tfoot th,
table.shop_table tfoot th {
	padding: 10px 14px;
	background-color: <?php echo esc_attr( $palette['surface'] ); ?>;
	border-bottom: 0;
	border-top: 1px solid <?php echo esc_attr( $palette['line'] ); ?>;
	font-size: 14px;
	text-transform: none;
	letter-spacing: 0;
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
}

table.order_details tfoot tr:last-child td,
table.shop_table tfoot tr:last-child td,
table.order_details tfoot tr:last-child th,
table.shop_table tfoot tr:last-child th {
	font-size: 17px;
	font-weight: 700;
	color: <?php echo esc_attr( $palette['ink'] ); ?>;
}

/* Product thumbnails inside the items table. */
table.order_details td img,
table.shop_table td img {
	border-radius: 3px;
	vertical-align: middle;
	margin-right: 10px;
}

.address {
	padding: 14px 16px;
	background-color: <?php echo esc_attr( $palette['surface_alt'] ); ?>;
	border: 1px solid <?php echo esc_attr( $palette['line'] ); ?>;
	border-radius: 8px;
	font-size: 14px;
	line-height: 1.7;
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
}

.text {
	color: <?php echo esc_attr( $palette['ink_soft'] ); ?>;
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
	font-size: 15px;
	line-height: 1.75;
}

/* WebToffee's invoice button, restyled in inc/emails.php. */
.wt_pklist_email_btn {
	font-family: <?php echo $font; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed font stack. ?>;
}
