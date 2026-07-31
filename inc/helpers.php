<?php
/**
 * Shared helpers: formatting, shipping introspection, safe rendering.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * The free-shipping threshold, in store currency.
 *
 * Our courier method (inc/shipping.php) decides free vs. paid shipping
 * itself off ASSURANCE_FREE_SHIPPING_MIN, so the progress bar reads the
 * same constant rather than scanning zones for a WooCommerce native
 * "Free Shipping" method — this store no longer uses one.
 *
 * @return float
 */
function assurance_free_shipping_threshold() {
	return defined( 'ASSURANCE_FREE_SHIPPING_MIN' ) ? (float) ASSURANCE_FREE_SHIPPING_MIN : 0.0;
}

/**
 * Cart subtotal measured the same way WooCommerce measures it for the
 * free-shipping gate, so the progress bar cannot disagree with the result.
 *
 * WC_Shipping_Free_Shipping compares against the cart contents total plus
 * (optionally) discounts, excluding shipping and fees.
 *
 * @return float
 */
function assurance_cart_total_for_free_shipping() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$total = WC()->cart->get_displayed_subtotal();

	if ( 'incl' === WC()->cart->get_tax_price_display_mode() ) {
		$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
	} else {
		$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
	}

	return (float) $total;
}

/**
 * Free-shipping progress as a renderable struct.
 *
 * @return array{show:bool,threshold:float,current:float,remaining:float,percent:float,met:bool}
 */
function assurance_free_shipping_progress() {
	$threshold = assurance_free_shipping_threshold();
	$current   = assurance_cart_total_for_free_shipping();

	if ( $threshold <= 0 ) {
		return array(
			'show'      => false,
			'threshold' => 0.0,
			'current'   => $current,
			'remaining' => 0.0,
			'percent'   => 100.0,
			'met'       => true,
		);
	}

	$remaining = max( 0, $threshold - $current );

	return array(
		'show'      => true,
		'threshold' => $threshold,
		'current'   => $current,
		'remaining' => $remaining,
		'percent'   => min( 100, ( $current / $threshold ) * 100 ),
		'met'       => $remaining <= 0,
	);
}

/**
 * Convert Western digits to Bengali numerals.
 *
 * Applied to display-only strings. Never applied to values that are parsed
 * back (quantities, form inputs, data attributes) — WooCommerce and the
 * browser both expect ASCII digits there.
 *
 * @param string $value Text to convert.
 * @return string
 */
function assurance_bn_digits( $value ) {
	return strtr(
		(string) $value,
		array(
			'0' => '০',
			'1' => '১',
			'2' => '২',
			'3' => '৩',
			'4' => '৪',
			'5' => '৫',
			'6' => '৬',
			'7' => '৭',
			'8' => '৮',
			'9' => '৯',
		)
	);
}

/**
 * Whether prices should render with Bengali numerals.
 *
 * Off by default: Bangladeshi e-commerce overwhelmingly shows prices in
 * ASCII digits, and mixing scripts inside a price hurts scannability. The
 * filter exists so the client can flip it without touching templates.
 *
 * @return bool
 */
function assurance_use_bn_price_digits() {
	return (bool) apply_filters( 'assurance_bn_price_digits', false );
}

/**
 * Drop the ".00" from whole-taka prices.
 *
 * Every book in this catalogue is priced in whole taka, so the store's
 * two-decimal setting renders "৳320.00" everywhere — two characters of
 * noise on every price on every card.
 *
 * Done as a display filter rather than by setting the store's decimal
 * count to zero, because that setting also rounds: a ৳320.50 price would
 * silently become ৳321 in the cart, the order and the invoice. Trimming
 * only removes zeros that carry no information, and reverts the moment
 * the theme is deactivated.
 *
 * @return bool
 */
function assurance_trim_price_zeros() {
	return true;
}
add_filter( 'woocommerce_price_trim_zeros', 'assurance_trim_price_zeros' );

/**
 * Discount percentage for a product, or 0 when not on sale.
 *
 * Variable products report the deepest discount across their variations,
 * which is what the badge should advertise.
 *
 * @param WC_Product $product Product.
 * @return int Rounded percentage, 0–100.
 */
function assurance_sale_percentage( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return 0;
	}

	$best = 0;

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );

			if ( ! $child || ! $child->is_on_sale() ) {
				continue;
			}

			$best = max( $best, assurance_percentage_between( $child->get_regular_price(), $child->get_sale_price() ) );
		}

		return $best;
	}

	return assurance_percentage_between( $product->get_regular_price(), $product->get_sale_price() );
}

/**
 * Percentage saved between two prices, guarding divide-by-zero.
 *
 * @param mixed $regular Regular price.
 * @param mixed $sale    Sale price.
 * @return int
 */
function assurance_percentage_between( $regular, $sale ) {
	$regular = (float) $regular;
	$sale    = (float) $sale;

	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return 0;
	}

	return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
}

/**
 * The product's most specific category term, for the card eyebrow.
 *
 * Prefers a child term over its parent — "বিসিএস লিখিত" tells the shopper
 * more than "বিসিএস". Falls back to the first assigned term.
 *
 * @param WC_Product $product Product.
 * @return WP_Term|null
 */
function assurance_primary_category( $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			return $term;
		}
	}

	return $terms[0];
}

/**
 * Render a product cover with correct sizing hints.
 *
 * WooCommerce's own thumbnail helper hardcodes the shop_catalog size and
 * emits no sizes attribute, which makes the browser download the 2x asset
 * on a 2-column phone grid. This uses our purpose-built cover sizes and a
 * sizes attribute matching the real grid breakpoints.
 *
 * @param WC_Product $product Product.
 * @param string     $size    Registered image size.
 * @param bool       $lazy    Whether to lazy-load. Pass false above the fold.
 * @return string HTML.
 */
function assurance_cover_html( $product, $size = 'assurance-cover', $lazy = true ) {
	$id = $product->get_image_id();

	if ( ! $id ) {
		return wc_placeholder_img( $size, array( 'class' => 'ap-cover__img' ) );
	}

	return wp_get_attachment_image(
		$id,
		$size,
		false,
		array(
			'class'    => 'ap-cover__img',
			'loading'  => $lazy ? 'lazy' : 'eager',
			'decoding' => 'async',
			'sizes'    => '(max-width: 575px) 44vw, (max-width: 991px) 30vw, 22vw',
			'alt'      => the_title_attribute(
				array(
					'echo' => false,
					'post' => $product->get_id(),
				)
			),
		)
	);
}

/**
 * Stock state as a label + modifier, or null when there is nothing to say.
 *
 * Deliberately silent for comfortably-stocked items: a green "In stock" tag
 * on every card is visual noise that trains shoppers to ignore the slot,
 * which is exactly where the useful "only 2 left" signal needs to land.
 *
 * @param WC_Product $product Product.
 * @return array{label:string,state:string}|null
 */
function assurance_stock_state( $product ) {
	if ( ! $product->is_in_stock() ) {
		return array(
			'label' => __( 'স্টক নেই', 'assurance' ),
			'state' => 'out',
		);
	}

	if ( $product->is_on_backorder( 1 ) ) {
		return array(
			'label' => __( 'প্রি-অর্ডার', 'assurance' ),
			'state' => 'backorder',
		);
	}

	$qty = $product->get_stock_quantity();

	if ( $product->managing_stock() && null !== $qty && $qty > 0 && $qty <= 5 ) {
		return array(
			/* translators: %s: remaining stock count in Bengali numerals. */
			'label' => sprintf( __( 'মাত্র %s কপি বাকি', 'assurance' ), assurance_bn_digits( $qty ) ),
			'state' => 'low',
		);
	}

	return null;
}

/**
 * Escape an attribute value that holds JSON.
 *
 * wp_json_encode + esc_attr double-escapes quotes in a way that breaks
 * JSON.parse on the client; this produces attribute-safe JSON that parses.
 *
 * @param mixed $data Data to encode.
 * @return string
 */
function assurance_json_attr( $data ) {
	return esc_attr( wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/* ==========================================================================
   HTML email — contact form notifications
   --------------------------------------------------------------------------
   Table-based, fully inline-styled markup (no <style> block, no external
   CSS) because mail clients strip both. Colours are the token hex values
   copied by hand — CSS custom properties are not readable by Outlook/Gmail,
   so tokens.css cannot be the source of truth here.
   ========================================================================== */

/**
 * Wrap a content block in the shared branded email shell.
 *
 * @param string $heading Big heading inside the navy card header.
 * @param string $body    Inner HTML, already built by the caller.
 * @return string Full HTML document.
 */
function assurance_email_shell( $heading, $body ) {
	$site_name = get_bloginfo( 'name' );
	$shop_url  = home_url( '/' );

	ob_start();
	?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $heading ); ?></title>
</head>
<body style="margin:0; padding:0; background:#eaeef5; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eaeef5; padding:32px 16px;">
		<tr>
			<td align="center">
				<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(15,31,61,0.08);">
					<tr>
						<td style="background:#0f1f3d; background:linear-gradient(135deg,#081226 0%,#16305c 55%,#0f1f3d 100%); padding:28px 32px;">
							<span style="display:inline-block; font-size:12px; font-weight:600; letter-spacing:0.06em; color:#fb923c; text-transform:uppercase;">
								<?php echo esc_html( $site_name ); ?>
							</span>
							<h1 style="margin:6px 0 0; font-size:21px; line-height:1.4; color:#ffffff; font-weight:700;">
								<?php echo esc_html( $heading ); ?>
							</h1>
						</td>
					</tr>
					<tr>
						<td style="padding:28px 32px; color:#33465f; font-size:15px; line-height:1.7;">
							<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- callers build this from esc_html()'d pieces. ?>
						</td>
					</tr>
					<tr>
						<td style="padding:20px 32px; background:#f4f6fa; border-top:1px solid #dbe3ec;">
							<p style="margin:0 0 4px; font-size:13px; color:#64748b;">
								<?php echo esc_html( $site_name ); ?> — ৩, নিউ পল্টন লাইন, আজিমপুর, ঢাকা - ১০০০
							</p>
							<p style="margin:0; font-size:13px;">
								<a href="<?php echo esc_url( $shop_url ); ?>" style="color:#ea580c; text-decoration:none; font-weight:600;">
									<?php echo esc_html( $shop_url ); ?>
								</a>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
	<?php
	return ob_get_clean();
}

/**
 * One label/value row inside an email body table.
 *
 * @param string $label Row label.
 * @param string $value Row value (already escaped by the caller).
 * @return string
 */
function assurance_email_row( $label, $value ) {
	return sprintf(
		'<tr>
			<td style="padding:6px 0; width:100px; color:#94a3b8; font-size:13px; vertical-align:top;">%s</td>
			<td style="padding:6px 0; color:#0f1f3d; font-size:15px; font-weight:600; vertical-align:top;">%s</td>
		</tr>',
		esc_html( $label ),
		$value // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- callers pass pre-escaped values.
	);
}

/**
 * Admin notification: a visitor submitted the contact form.
 *
 * @param string $name    Sender name.
 * @param string $email   Sender email.
 * @param string $phone   Sender phone.
 * @param string $message Message body.
 * @return string HTML email.
 */
function assurance_contact_admin_email( $name, $email, $phone, $message ) {
	$rows  = assurance_email_row( __( 'নাম', 'assurance' ), esc_html( $name ) );
	$rows .= assurance_email_row( __( 'ইমেইল', 'assurance' ), '<a href="mailto:' . esc_attr( $email ) . '" style="color:#ea580c; text-decoration:none;">' . esc_html( $email ) . '</a>' );
	$rows .= assurance_email_row( __( 'ফোন', 'assurance' ), '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '" style="color:#0f1f3d; text-decoration:none;">' . esc_html( $phone ) . '</a>' );

	$body = sprintf(
		'<p style="margin:0 0 16px;">%s</p>
		<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">%s</table>
		<p style="margin:0 0 6px; font-size:13px; color:#94a3b8; text-transform:uppercase; letter-spacing:0.04em; font-weight:600;">%s</p>
		<div style="padding:16px; background:#f8fafc; border:1px solid #e8edf3; border-radius:8px; color:#33465f; white-space:pre-wrap;">%s</div>',
		esc_html__( 'ওয়েবসাইটের যোগাযোগ ফর্ম থেকে একটি নতুন বার্তা এসেছে।', 'assurance' ),
		$rows,
		esc_html__( 'বার্তা', 'assurance' ),
		esc_html( $message )
	);

	return assurance_email_shell( __( 'নতুন যোগাযোগ বার্তা', 'assurance' ), $body );
}

/**
 * Auto-reply sent back to the visitor confirming receipt.
 *
 * @param string $name    Sender name.
 * @param string $message The message they submitted, quoted back to them.
 * @return string HTML email.
 */
function assurance_contact_user_email( $name, $message ) {
	$body = sprintf(
		'<p style="margin:0 0 16px;">%1$s,</p>
		<p style="margin:0 0 16px;">%2$s</p>
		<div style="padding:16px; background:#f8fafc; border:1px solid #e8edf3; border-radius:8px; color:#33465f; white-space:pre-wrap; margin-bottom:20px;">%3$s</div>
		<p style="margin:0 0 22px;">%4$s</p>
		<table role="presentation" cellpadding="0" cellspacing="0">
			<tr><td style="border-radius:8px; background:#ea580c;">
				<a href="%5$s" target="_blank" style="display:inline-block; padding:12px 26px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">%6$s</a>
			</td></tr>
		</table>',
		esc_html( sprintf( /* translators: %s: visitor name. */ __( 'প্রিয় %s', 'assurance' ), $name ) ),
		esc_html__( 'আপনার বার্তাটি আমরা পেয়েছি, ধন্যবাদ। আমাদের টিম শীঘ্রই আপনার সাথে যোগাযোগ করবে। আপনার পাঠানো বার্তাটি নিচে দেওয়া হলো —', 'assurance' ),
		esc_html( $message ),
		esc_html__( 'এর মধ্যে আমাদের বইসমূহ দেখে নিতে পারেন —', 'assurance' ),
		esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ),
		esc_html__( 'বইসমূহ দেখুন', 'assurance' )
	);

	return assurance_email_shell( __( 'আপনার বার্তা পেয়েছি', 'assurance' ), $body );
}

/**
 * Send an HTML email through wp_mail.
 *
 * Any SMTP plugin (FluentSMTP, WP Mail SMTP, …) hooks into wp_mail /
 * PHPMailer directly, so routing mail through the delivery service the
 * client has configured needs nothing beyond calling wp_mail() itself —
 * no plugin-specific integration code. The only thing this wrapper adds is
 * forcing the content type to HTML for the duration of this one call,
 * without leaving that filter attached for any other wp_mail() caller
 * (WooCommerce order emails, password resets, …) on the same request.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject line.
 * @param string $html    Full HTML body (see assurance_email_shell()).
 * @param array  $headers Extra headers (e.g. Reply-To).
 * @return bool
 */
function assurance_send_html_mail( $to, $subject, $html, $headers = array() ) {
	$set_html = function () {
		return 'text/html';
	};

	add_filter( 'wp_mail_content_type', $set_html );
	$sent = wp_mail( $to, $subject, $html, $headers );
	remove_filter( 'wp_mail_content_type', $set_html );

	return $sent;
}
