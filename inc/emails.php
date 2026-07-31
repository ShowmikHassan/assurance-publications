<?php
/**
 * WooCommerce transactional email customisation.
 *
 * The templates themselves live in woocommerce/emails/. This file supplies
 * the Bangla copy, the delivery/payment facts those templates print, and the
 * PDF-invoice button wiring for the WebToffee plugin.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand colours for the email shell.
 *
 * Mail clients cannot read CSS custom properties, so the token values are
 * repeated here as plain hex and every template pulls from this one array.
 *
 * @return array<string, string>
 */
function assurance_email_palette() {
	return array(
		'ink'        => '#0f1f3d',
		'ink_soft'   => '#33465f',
		'ink_muted'  => '#64748b',
		'ink_faint'  => '#94a3b8',
		'accent'     => '#ea580c',
		'accent_deep' => '#c2410c',
		'accent_tint' => '#fff3ea',
		'green'      => '#1b7f4c',
		'green_tint' => '#e8f5ee',
		'brass'      => '#b7862a',
		'brass_tint' => '#fbf3e0',
		'paper'      => '#eaeef5',
		'surface'    => '#ffffff',
		'surface_alt' => '#f4f6fa',
		'line'       => '#dbe3ec',
	);
}

/**
 * Font stack for the emails.
 *
 * Bengali needs a font the client already has — webfonts are stripped by
 * most mail clients — so the stack names the Bangla faces that ship with
 * Windows, macOS and Android before falling back to the generic sans.
 *
 * @return string
 */
function assurance_email_font() {
	return "'Nirmala UI', 'Shonar Bangla', 'Vrinda', 'Noto Sans Bengali', 'Kohinoor Bangla', 'Segoe UI', Helvetica, Arial, sans-serif";
}

/**
 * The address shoppers should write to.
 *
 * Falls back to the shop's own constant when WooCommerce's "from" address is
 * empty or still a local-development placeholder, so a staging copy does not
 * print dev-email@…​.local to a real customer.
 *
 * @return string
 */
function assurance_shop_contact_email() {
	$configured = get_option( 'woocommerce_email_from_address' );

	$is_placeholder = ! $configured
		|| ! is_email( $configured )
		|| preg_match( '/\.(local|test|invalid|example)$/i', $configured );

	$email = $is_placeholder ? ASSURANCE_CONTACT_EMAIL : $configured;

	return apply_filters( 'assurance_shop_contact_email', $email );
}

/**
 * Reply-To on every WooCommerce email.
 *
 * The From address has to stay on the site's own domain or the message fails
 * SPF/DKIM and lands in spam — so the shop's mailbox goes in Reply-To
 * instead, which is what actually matters when a customer hits reply.
 *
 * @param string $headers Existing headers.
 * @return string
 */
function assurance_email_reply_to( $headers ) {
	$contact = assurance_shop_contact_email();

	if ( ! $contact ) {
		return $headers;
	}

	return $headers . 'Reply-To: ' . get_bloginfo( 'name' ) . ' <' . $contact . '>' . "\r\n";
}
add_filter( 'woocommerce_email_headers', 'assurance_email_reply_to' );

/* ==========================================================================
   Bangla subjects and headings
   ========================================================================== */

/**
 * Default subject/heading strings, keyed by email id.
 *
 * Applied as defaults only — anything the shop has typed into
 * WooCommerce → Settings → Emails still wins, because those are stored as
 * options and read before these filters run.
 *
 * @return array<string, array{subject:string, heading:string}>
 */
function assurance_email_strings() {
	return array(
		'new_order' => array(
			'subject' => __( '[{site_title}] নতুন অর্ডার #{order_number}', 'assurance' ),
			'heading' => __( 'নতুন অর্ডার এসেছে', 'assurance' ),
		),
		'cancelled_order' => array(
			'subject' => __( '[{site_title}] অর্ডার বাতিল #{order_number}', 'assurance' ),
			'heading' => __( 'অর্ডার বাতিল হয়েছে', 'assurance' ),
		),
		'failed_order' => array(
			'subject' => __( '[{site_title}] ব্যর্থ অর্ডার #{order_number}', 'assurance' ),
			'heading' => __( 'অর্ডারটি সম্পন্ন হয়নি', 'assurance' ),
		),
		'customer_on_hold_order' => array(
			'subject' => __( 'আপনার অর্ডারটি যাচাই করা হচ্ছে — {site_title}', 'assurance' ),
			'heading' => __( 'অর্ডারটি যাচাই করা হচ্ছে', 'assurance' ),
		),
		'customer_processing_order' => array(
			'subject' => __( 'আপনার অর্ডারটি আমরা পেয়েছি — {site_title}', 'assurance' ),
			'heading' => __( 'ধন্যবাদ! অর্ডারটি নিশ্চিত হয়েছে', 'assurance' ),
		),
		'customer_completed_order' => array(
			'subject' => __( 'আপনার বই পাঠানো হয়েছে — {site_title}', 'assurance' ),
			'heading' => __( 'আপনার অর্ডারটি সম্পন্ন', 'assurance' ),
		),
		'customer_refunded_order' => array(
			'subject' => __( 'আপনার টাকা ফেরত দেওয়া হয়েছে — {site_title}', 'assurance' ),
			'heading' => __( 'রিফান্ড সম্পন্ন হয়েছে', 'assurance' ),
		),
		'customer_invoice' => array(
			'subject' => __( 'আপনার অর্ডার #{order_number} — {site_title}', 'assurance' ),
			'heading' => __( 'আপনার অর্ডারের বিবরণ', 'assurance' ),
		),
		'customer_note' => array(
			'subject' => __( 'আপনার অর্ডার সম্পর্কে নতুন তথ্য — {site_title}', 'assurance' ),
			'heading' => __( 'অর্ডার সম্পর্কে বার্তা', 'assurance' ),
		),
		'customer_new_account' => array(
			'subject' => __( '{site_title}-এ আপনাকে স্বাগতম', 'assurance' ),
			'heading' => __( 'অ্যাকাউন্ট তৈরি হয়েছে', 'assurance' ),
		),
		'customer_reset_password' => array(
			'subject' => __( 'পাসওয়ার্ড রিসেট — {site_title}', 'assurance' ),
			'heading' => __( 'পাসওয়ার্ড রিসেট করুন', 'assurance' ),
		),
	);
}

/**
 * Register the Bangla defaults against WooCommerce's per-email option
 * defaults, so the Settings → Emails screen shows them as the placeholder
 * and an untouched install sends Bangla out of the box.
 */
function assurance_email_defaults() {
	foreach ( assurance_email_strings() as $id => $strings ) {
		add_filter(
			'woocommerce_email_subject_' . $id,
			function ( $subject, $object, $email ) use ( $strings ) {
				return $email->format_string( $strings['subject'] );
			},
			5,
			3
		);

		add_filter(
			'woocommerce_email_heading_' . $id,
			function ( $heading, $object, $email ) use ( $strings ) {
				return $email->format_string( $strings['heading'] );
			},
			5,
			3
		);
	}
}
add_action( 'init', 'assurance_email_defaults' );

/**
 * Footer text under the email card.
 *
 * @param string $text Existing footer text.
 * @return string
 */
function assurance_email_footer_text( $text ) {
	return $text ? $text : __( 'বই কেনার জন্য ধন্যবাদ। যেকোনো প্রয়োজনে আমাদের সাথে যোগাযোগ করুন।', 'assurance' );
}
add_filter( 'option_woocommerce_email_footer_text', 'assurance_email_footer_text' );

/* ==========================================================================
   Order facts the templates print
   ========================================================================== */

/**
 * How this order was paid, expressed as "what was collected when".
 *
 * The COD gateway takes the courier charge up front through bKash and the
 * book price at the door, so a single "Payment method: COD" line would be
 * actively misleading in the confirmation email.
 *
 * @param WC_Order $order Order.
 * @return array<int, array{label:string, value:string, tone:string}>
 */
function assurance_email_payment_lines( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return array();
	}

	$lines  = array();
	$method = $order->get_payment_method();
	$total  = (float) $order->get_total();

	if ( 'assurance_cod' === $method ) {
		$fee = (float) $order->get_meta( '_assurance_cod_courier_fee' );

		if ( $fee > 0 ) {
			$lines[] = array(
				'label' => __( 'বিকাশে পরিশোধিত (ডেলিভারি চার্জ)', 'assurance' ),
				'value' => wc_price( $fee, array( 'currency' => $order->get_currency() ) ),
				'tone'  => 'paid',
			);
			$lines[] = array(
				'label' => __( 'ডেলিভারিতে ক্যাশে দিতে হবে', 'assurance' ),
				'value' => wc_price( max( 0, $total - $fee ), array( 'currency' => $order->get_currency() ) ),
				'tone'  => 'due',
			);
		} else {
			$lines[] = array(
				'label' => __( 'ডেলিভারিতে ক্যাশে দিতে হবে', 'assurance' ),
				'value' => wc_price( $total, array( 'currency' => $order->get_currency() ) ),
				'tone'  => 'due',
			);
		}

		$trxid = $order->get_meta( '_assurance_cod_courier_bkash_trxid' );

		if ( $trxid ) {
			$lines[] = array(
				'label' => __( 'বিকাশ ট্রানজেকশন আইডি', 'assurance' ),
				'value' => esc_html( $trxid ),
				'tone'  => 'meta',
			);
		}
	} elseif ( 'bkash' === $method ) {
		$lines[] = array(
			'label' => __( 'বিকাশে সম্পূর্ণ পরিশোধিত', 'assurance' ),
			'value' => wc_price( $total, array( 'currency' => $order->get_currency() ) ),
			'tone'  => 'paid',
		);
	} else {
		$lines[] = array(
			'label' => __( 'পেমেন্ট পদ্ধতি', 'assurance' ),
			'value' => esc_html( $order->get_payment_method_title() ),
			'tone'  => 'meta',
		);
	}

	return $lines;
}

/**
 * The delivery band label stored on the order at checkout.
 *
 * Read from order meta rather than recomputed, so an email resent months
 * later still describes the band the order was actually priced against.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function assurance_email_delivery_band( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$band = $order->get_meta( '_assurance_delivery_band' );

	if ( 'inside' === $band ) {
		return __( 'ঢাকার ভিতরে', 'assurance' );
	}

	if ( 'outside' === $band ) {
		return __( 'ঢাকার বাইরে', 'assurance' );
	}

	return '';
}

/* ==========================================================================
   PDF invoice (WebToffee)
   ========================================================================== */

/**
 * Whether the WebToffee invoice plugin is active.
 *
 * @return bool
 */
function assurance_has_invoice_plugin() {
	return class_exists( 'Wf_Woocommerce_Packing_List' )
		&& is_callable( array( 'Wf_Woocommerce_Packing_List', 'generate_print_button_for_user' ) );
}

/**
 * Bangla labels for the plugin's print/download buttons.
 *
 * @param string $label    Existing label.
 * @param string $action   'print' | 'download'.
 * @return string
 */
function assurance_invoice_button_label( $label, $action ) {
	if ( 'download' === $action ) {
		return __( 'ইনভয়েস ডাউনলোড', 'assurance' );
	}

	return __( 'ইনভয়েস দেখুন', 'assurance' );
}
add_filter( 'wt_pklist_alter_document_button_label', 'assurance_invoice_button_label', 10, 2 );

/**
 * Brand the plugin's in-email invoice button.
 *
 * It ships WordPress-admin blue with a hard-coded inline style; this is the
 * filter the plugin provides for exactly this purpose.
 *
 * @param array $style Style declarations.
 * @return array
 */
function assurance_invoice_email_button_style( $style ) {
	$palette = assurance_email_palette();

	return array(
		'display'         => 'inline-block',
		'background'      => $palette['accent'],
		'border'          => '0',
		'border-color'    => $palette['accent'],
		'box-shadow'      => 'none',
		'color'           => '#ffffff',
		'text-decoration' => 'none',
		'font-family'     => assurance_email_font(),
		'font-size'       => '15px',
		'font-weight'     => '700',
		'padding'         => '13px 26px',
		'border-radius'   => '6px',
	);
}
add_filter( 'wt_pklist_alter_style_for_email_button', 'assurance_invoice_email_button_style' );

/**
 * Take the order-detail invoice buttons off the plugin.
 *
 * It echoes them bare, one per line, with no heading — and because they are
 * printed directly rather than returned, there is no filter to wrap them in.
 * Turning them off here and re-rendering them below is the only way to give
 * them a heading and an inline layout.
 *
 * @param bool   $show     Whether to render the button.
 * @param string $action   'print' | 'download'.
 * @param string $location Where the button would appear.
 * @return bool
 */
function assurance_hide_plugin_invoice_buttons( $show, $action, $location ) {
	return 'my_account_order_details' === $location ? false : $show;
}
add_filter( 'wt_pklist_show_document_button', 'assurance_hide_plugin_invoice_buttons', 10, 3 );

/**
 * Our own invoice block: a heading plus both actions on one row.
 *
 * Renders on the order-received screen and the My Account order view.
 *
 * @param WC_Order $order Order.
 */
function assurance_invoice_section( $order ) {
	if ( ! assurance_has_invoice_plugin() || ! $order instanceof WC_Order ) {
		return;
	}

	?>
	<section class="ap-invoice">
		<h3 class="ap-invoice__title">
			<?php assurance_the_icon( 'file-text', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'ইনভয়েস', 'assurance' ); ?>
		</h3>

		<p class="ap-invoice__hint"><?php esc_html_e( 'অর্ডারের রসিদ দেখুন বা ডাউনলোড করে রাখুন।', 'assurance' ); ?></p>

		<div class="ap-invoice__actions">
			<?php
			Wf_Woocommerce_Packing_List::generate_print_button_for_user(
				$order,
				$order->get_id(),
				'download_invoice',
				__( 'ডাউনলোড', 'assurance' )
			);

			Wf_Woocommerce_Packing_List::generate_print_button_for_user(
				$order,
				$order->get_id(),
				'print_invoice',
				__( 'দেখুন', 'assurance' )
			);
			?>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_order_details_after_order_table', 'assurance_invoice_section', 20 );
