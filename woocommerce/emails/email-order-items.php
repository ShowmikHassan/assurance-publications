<?php
/**
 * Order items table rows.
 *
 * Overridden for layout, not content. Core's markup put the cover and the
 * title in one nested table with no padding on either cell and no width on
 * the image cell, and wrapped the title in an <h3> — which inherits
 * `margin: 22px 0 8px` from email-styles.php, since that rule is written for
 * real headings. Three things followed: the title sat 22px below the top of
 * its own thumbnail, the two touched with no gutter, and because core asked
 * for an unregistered array( 48, 64 ) size, WordPress fell back to the
 * hard-cropped 150x150 `thumbnail` file and emitted width="48" height="48" —
 * squaring off portrait book covers.
 *
 * Here the row is a fixed-width image column plus a text column, both
 * top-aligned, with the title in a plain <span> that carries no inherited
 * margin. The source is `assurance-cover-sm` (160x224), the theme's own
 * book-cover crop, drawn at 56x78 so the ratio is honest and every row's
 * title starts at the same x.
 *
 * Kept from core: every filter and action, the refunded-quantity display,
 * SKU on admin copies, and the purchase-note row.
 *
 * @package Assurance
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();

$text_align  = is_rtl() ? 'left' : 'right';
$gutter_side = is_rtl() ? 'left' : 'right';

/* Display box for the cover: the 48px the table was always designed around,
   at assurance-cover-sm's 5:7 rather than forced square. */
$thumb_w = 48;
$thumb_h = 67;

$cell = 'padding:14px; border-bottom:1px solid ' . $palette['line'] . '; vertical-align:top; font-family:' . $font . ';';

foreach ( $items as $item_id => $item ) :
	$product       = $item->get_product();
	$sku           = '';
	$purchase_note = '';
	$image_url     = '';

	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	if ( is_object( $product ) ) {
		$sku           = $product->get_sku();
		$purchase_note = $product->get_purchase_note();
		$image_id      = $product->get_image_id();
		$image_url     = $image_id
			? wp_get_attachment_image_url( $image_id, $image_size )
			: wc_placeholder_img_src( $image_size );
	}

	/*
	 * Built by hand rather than via get_image(): that adds srcset, sizes and
	 * loading="lazy", none of which survive an email client, and it is the
	 * path that produced the squashed dimensions in the first place.
	 */
	$image = $image_url
		? sprintf(
			'<img src="%1$s" alt="" width="%2$d" height="%3$d" style="width:%2$dpx; height:%3$dpx; display:block; border:1px solid %4$s; border-radius:3px; object-fit:cover;" />',
			esc_url( $image_url ),
			$thumb_w,
			$thumb_h,
			esc_attr( $palette['line'] )
		)
		: '';
	?>
	<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
		<td class="td" style="<?php echo esc_attr( $cell ); ?> word-wrap:break-word;">
			<table cellspacing="0" cellpadding="0" border="0" role="presentation" style="border-collapse:collapse;">
				<tr>
					<?php if ( $show_image && $image ) : ?>
						<td width="<?php echo esc_attr( $thumb_w ); ?>" style="width:<?php echo esc_attr( $thumb_w ); ?>px; padding-<?php echo esc_attr( $gutter_side ); ?>:14px; vertical-align:top;">
							<?php
							/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $item ) );
							?>
						</td>
					<?php endif; ?>

					<td style="vertical-align:top; padding-top:1px;">
						<span style="display:block; font-size:14px; font-weight:600; line-height:1.5; color:<?php echo esc_attr( $palette['ink'] ); ?>;">
							<?php
							/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );

							if ( $show_sku && $sku ) {
								echo wp_kses_post( ' <span style="font-weight:400; color:' . esc_attr( $palette['ink_muted'] ) . ';">(#' . $sku . ')</span>' );
							}
							?>
						</span>

						<?php
						/** This action is documented in woocommerce/templates/emails/email-order-items.php */
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

						$item_meta = wc_display_item_meta(
							$item,
							array(
								'before'       => '',
								'after'        => '',
								'separator'    => '<br />',
								'echo'         => false,
								'label_before' => '<span>',
								'label_after'  => ':</span> ',
							)
						);

						if ( $item_meta ) {
							echo '<span style="display:block; margin-top:4px; font-size:13px; line-height:1.6; color:' . esc_attr( $palette['ink_muted'] ) . ';">';
							// wp_kses, not wp_kses_post: block elements would break the cell.
							echo wp_kses(
								$item_meta,
								array(
									'br'   => array(),
									'span' => array(),
									'a'    => array(
										'href'   => true,
										'target' => true,
										'rel'    => true,
										'title'  => true,
									),
								)
							);
							echo '</span>';
						}

						/** This action is documented in woocommerce/templates/emails/email-order-items.php */
						do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
						?>
					</td>
				</tr>
			</table>
		</td>

		<td class="td" style="<?php echo esc_attr( $cell ); ?> text-align:<?php echo esc_attr( $text_align ); ?>; font-size:14px; color:<?php echo esc_attr( $palette['ink_soft'] ); ?>; white-space:nowrap;">
			<?php
			$qty          = $item->get_quantity();
			$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

			if ( $refunded_qty ) {
				$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
			} else {
				$qty_display = esc_html( $qty );
			}

			/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
			$quantity = apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item );

			if ( '' !== $quantity ) {
				echo wp_kses_post( '&times;' . $quantity );
			}
			?>
		</td>

		<td class="td" style="<?php echo esc_attr( $cell ); ?> text-align:<?php echo esc_attr( $text_align ); ?>; font-size:14px; font-weight:600; color:<?php echo esc_attr( $palette['ink'] ); ?>; white-space:nowrap;">
			<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
		</td>
	</tr>
	<?php

	if ( $show_purchase_note && $purchase_note ) {
		?>
		<tr>
			<td colspan="3" style="padding:0 14px 14px; border-bottom:1px solid <?php echo esc_attr( $palette['line'] ); ?>; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;">
				<?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?>
			</td>
		</tr>
		<?php
	}

endforeach;
