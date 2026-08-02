<?php
/**
 * Order details table.
 *
 * Adds two things core does not print: the delivery band the order was
 * priced against, and a plain-language split of what has already been paid
 * versus what is still owed at the door — which the COD-pays-courier-charge
 * flow makes essential.
 *
 * @package Assurance
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Order $order Supplied by wc_get_template(). */
if ( ! isset( $order ) || ! $order instanceof WC_Order ) {
	return;
}

$palette = assurance_email_palette();
$font    = assurance_email_font();

$text_align = is_rtl() ? 'right' : 'left';

$band          = assurance_email_delivery_band( $order );
$payment_lines = assurance_email_payment_lines( $order );

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );
?>

<h2 style="font-family:<?php echo esc_attr( $font ); ?>; font-size:16px; font-weight:700; color:<?php echo esc_attr( $palette['ink'] ); ?>; margin:28px 0 6px;">
	<?php
	if ( $sent_to_admin ) {
		$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '" style="color:' . esc_attr( $palette['accent'] ) . '; text-decoration:none; font-weight:700;">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}

	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( __( 'অর্ডার #%s', 'assurance' ), $order->get_order_number() ) . $after );
	?>
</h2>

<p style="margin:0 0 18px; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;">
	<?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd F Y, g:i a' ) ); ?>
	<?php if ( $band ) : ?>
		&nbsp;·&nbsp;<?php echo esc_html( $band ); ?>
	<?php endif; ?>
</p>

<div style="margin-bottom:24px;">
	<table class="td" cellspacing="0" cellpadding="6" border="1" style="width:100%; font-family:<?php echo esc_attr( $font ); ?>; border-collapse:collapse; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>;">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:11px 14px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:12px; font-weight:700; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;"><?php esc_html_e( 'বই', 'assurance' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:11px 14px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:12px; font-weight:700; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;"><?php esc_html_e( 'পরিমাণ', 'assurance' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:11px 14px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:12px; font-weight:700; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;"><?php esc_html_e( 'মূল্য', 'assurance' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the template it renders.
				$order,
				array(
					'show_sku'      => $sent_to_admin,
					'show_image'    => true,
					// A registered crop, not an array: an unregistered array
					// size falls back to the square 150x150 `thumbnail` file
					// and squashes portrait covers. See email-order-items.php.
					'image_size'    => 'assurance-cover-sm',
					'plain_text'    => $plain_text,
					'sent_to_admin' => $sent_to_admin,
				)
			);
			?>
		</tbody>
		<tfoot>
			<?php
			$totals = $order->get_order_item_totals();

			if ( $totals ) {
				$i = 0;

				foreach ( $totals as $total ) {
					$i++;
					$is_last = ( count( $totals ) === $i );
					?>
					<tr>
						<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:10px 14px; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:<?php echo $is_last ? '16px' : '14px'; ?>; font-weight:<?php echo $is_last ? '700' : '400'; ?>; color:<?php echo esc_attr( $is_last ? $palette['ink'] : $palette['ink_soft'] ); ?>; background-color:<?php echo esc_attr( $is_last ? $palette['surface_alt'] : $palette['surface'] ); ?>;">
							<?php echo wp_kses_post( $total['label'] ); ?>
						</th>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:10px 14px; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:<?php echo $is_last ? '16px' : '14px'; ?>; font-weight:700; color:<?php echo esc_attr( $is_last ? $palette['accent'] : $palette['ink_soft'] ); ?>; background-color:<?php echo esc_attr( $is_last ? $palette['surface_alt'] : $palette['surface'] ); ?>;">
							<?php echo wp_kses_post( is_string( $total['value'] ) ? $total['value'] : '' ); ?>
						</td>
					</tr>
					<?php
				}
			}

			if ( $order->get_customer_note() ) {
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:10px 14px; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:14px; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php esc_html_e( 'গ্রাহকের নোট', 'assurance' ); ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; padding:10px 14px; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-size:14px; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
				<?php
			}
			?>
		</tfoot>
	</table>
</div>

<?php if ( ! empty( $payment_lines ) ) : ?>
	<h3 style="font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; font-weight:700; color:<?php echo esc_attr( $palette['ink'] ); ?>; margin:0 0 8px;">
		<?php esc_html_e( 'পেমেন্টের হিসাব', 'assurance' ); ?>
	</h3>

	<table cellspacing="0" cellpadding="0" border="0" style="width:100%; margin-bottom:24px; border:1px solid <?php echo esc_attr( $palette['line'] ); ?>; border-radius:8px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>;">
		<?php foreach ( $payment_lines as $line ) : ?>
			<tr>
				<td style="padding:11px 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:14px; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>;">
					<?php if ( 'paid' === $line['tone'] ) : ?>
						<span style="color:<?php echo esc_attr( $palette['green'] ); ?>; font-weight:700;">&#10003;</span>&nbsp;
					<?php endif; ?>
					<?php echo esc_html( $line['label'] ); ?>
				</td>
				<td align="<?php echo is_rtl() ? 'left' : 'right'; ?>" style="padding:11px 16px; font-family:<?php echo esc_attr( $font ); ?>; font-size:15px; font-weight:700; white-space:nowrap; color:<?php echo esc_attr( 'due' === $line['tone'] ? $palette['accent'] : $palette['ink'] ); ?>;">
					<?php echo wp_kses_post( $line['value'] ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
