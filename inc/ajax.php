<?php
/**
 * AJAX endpoints.
 *
 * Security model
 * --------------
 * Every endpoint is registered for both logged-in and logged-out users,
 * because guests must be able to shop. That makes the nonce a CSRF control
 * only — it is never treated as proof of identity or permission.
 *
 * Consequently each handler independently:
 *
 *   1. Verifies the nonce (assurance_verify_request).
 *   2. Re-derives every price, stock level and product state from the
 *      database. Nothing money-related is ever read from the request.
 *   3. Validates that the product exists, is published, is visible and is
 *      purchasable before acting on it.
 *   4. Escapes on output; all HTML returned here is built by the same
 *      escaped renderers the page templates use.
 *
 * The one thing a hostile caller can do with a valid nonce is manipulate
 * their own cart, which they can already do through WooCommerce's own
 * public endpoints.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register an AJAX action for both authenticated and anonymous callers.
 *
 * @param string   $action   Action name, without the ap_ prefix.
 * @param callable $callback Handler.
 */
function assurance_ajax( $action, $callback ) {
	add_action( 'wp_ajax_ap_' . $action, $callback );
	add_action( 'wp_ajax_nopriv_ap_' . $action, $callback );
}

/**
 * Verify the request nonce, or terminate with a machine-readable error.
 *
 * A stale nonce is a normal condition (page cached, tab left open
 * overnight), not an attack, so it gets its own code the client can react
 * to by refreshing rather than a generic failure.
 */
function assurance_verify_request() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'assurance_ajax' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'stale_nonce',
				'message' => __( 'সেশনের মেয়াদ শেষ। পেজটি রিফ্রেশ করুন।', 'assurance' ),
			),
			403
		);
	}
}

/**
 * Mint a fresh nonce for the caller's session.
 *
 * Deliberately does not verify a nonce itself — it exists precisely for the
 * case where the one printed into the page is already stale, which happens
 * on any host with full-page caching: the cached HTML outlives the session
 * the token was issued against, so every subsequent AJAX call 403s.
 *
 * Returning one is not a disclosure. wp_create_nonce() is bound to the
 * current user/session, so a token minted here is only usable by the caller
 * that requested it, and it grants nothing on its own — every endpoint still
 * re-validates its own inputs. WooCommerce refreshes its cart nonces the
 * same way.
 */
function assurance_ajax_refresh_nonce() {
	nocache_headers();

	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'assurance_ajax' ) ) );
}
assurance_ajax( 'refresh_nonce', 'assurance_ajax_refresh_nonce' );

/**
 * Fetch and validate a purchasable product from the request.
 *
 * @param string $key POST key holding the product ID.
 * @return WC_Product Never returns on failure — sends JSON and exits.
 */
function assurance_require_product( $key = 'product_id' ) {
	$id = isset( $_POST[ $key ] ) ? absint( wp_unslash( $_POST[ $key ] ) ) : 0;

	$product = $id ? wc_get_product( $id ) : null;

	// is_visible() also covers draft, private, trashed and catalog-hidden
	// products, so a guessed ID cannot be added by URL manipulation.
	if ( ! $product || ! $product->is_visible() ) {
		wp_send_json_error(
			array(
				'code'    => 'not_found',
				'message' => __( 'পণ্যটি পাওয়া যায়নি', 'assurance' ),
			),
			404
		);
	}

	return $product;
}

/**
 * Cart fragments plus the derived state the UI needs.
 *
 * Returned by every cart-mutating endpoint so the client never has to make
 * a second round trip to refresh the header count or the drawer.
 *
 * @return array
 */
function assurance_cart_payload() {
	if ( ! WC()->cart ) {
		return array();
	}

	WC()->cart->calculate_totals();

	ob_start();
	woocommerce_mini_cart();
	$mini_cart = ob_get_clean();

	$fragments = apply_filters(
		'woocommerce_add_to_cart_fragments',
		array(
			'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
		)
	);

	return array(
		'fragments'  => $fragments,
		'cartCount'  => WC()->cart->get_cart_contents_count(),
		'cartTotal'  => html_entity_decode( wp_strip_all_tags( WC()->cart->get_cart_subtotal() ) ),
		'cartHash'   => WC()->cart->get_cart_hash(),
		'drawerHtml' => assurance_render_drawer_contents(),
		'freeShip'   => assurance_free_shipping_progress(),
	);
}

/* ==========================================================================
   Add to cart
   ========================================================================== */

/**
 * Add a product to the cart from a card, a popover or the single page.
 */
function assurance_ajax_add_to_cart() {
	assurance_verify_request();

	$product      = assurance_require_product();
	$product_id   = $product->get_id();
	$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

	if ( $quantity <= 0 ) {
		$quantity = 1;
	}

	$variation = array();

	if ( $variation_id ) {
		$variation = assurance_sanitize_variation_attributes(
			isset( $_POST['variation'] ) ? wp_unslash( $_POST['variation'] ) : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in the callee.
		);

		// The variation must genuinely belong to the parent; otherwise a
		// caller could pair a cheap parent with someone else's variation.
		$variation_product = wc_get_product( $variation_id );

		if (
			! $variation_product
			|| ! $variation_product->is_type( 'variation' )
			|| $variation_product->get_parent_id() !== $product_id
		) {
			wp_send_json_error(
				array(
					'code'    => 'bad_variation',
					'message' => __( 'নির্বাচিত অপশনটি সঠিক নয়', 'assurance' ),
				),
				400
			);
		}
	}

	// Delegates the real work to WooCommerce so stock holds, sold-individually,
	// min/max quantity rules and third-party validation all still apply.
	$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

	if ( ! $added ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		wp_send_json_error(
			array(
				'code'    => 'add_failed',
				'message' => ! empty( $notices )
					? wp_strip_all_tags( $notices[0]['notice'] )
					: __( 'কার্টে যোগ করা যায়নি', 'assurance' ),
			),
			400
		);
	}

	wc_clear_notices();

	wp_send_json_success(
		array_merge(
			assurance_cart_payload(),
			array(
				'message'   => __( 'কার্টে যোগ হয়েছে', 'assurance' ),
				'productId' => $product_id,
				'openCart'  => true,
			)
		)
	);
}
assurance_ajax( 'add_to_cart', 'assurance_ajax_add_to_cart' );

/**
 * Sanitise a submitted variation attribute map.
 *
 * @param mixed $raw Raw attribute => value map.
 * @return array
 */
function assurance_sanitize_variation_attributes( $raw ) {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$clean = array();

	foreach ( $raw as $key => $value ) {
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$key = sanitize_text_field( (string) $key );

		// WooCommerce expects keys in attribute_<taxonomy> form.
		if ( 0 !== strpos( $key, 'attribute_' ) ) {
			$key = 'attribute_' . $key;
		}

		$clean[ $key ] = sanitize_text_field( (string) $value );
	}

	return $clean;
}

/* ==========================================================================
   Buy now
   ========================================================================== */

/**
 * Add to cart, then hand back the checkout URL for the client to follow.
 *
 * The cart is deliberately NOT emptied first. A shopper who has three books
 * in the basket and taps Buy Now on a fourth almost never means "discard the
 * other three" — and silently doing so is unrecoverable. Confirmed as the
 * intended behaviour in the build spec.
 */
function assurance_ajax_buy_now() {
	assurance_verify_request();

	$product    = assurance_require_product();
	$quantity   = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

	if ( $quantity <= 0 ) {
		$quantity = 1;
	}

	$variation = $variation_id
		? assurance_sanitize_variation_attributes(
			isset( $_POST['variation'] ) ? wp_unslash( $_POST['variation'] ) : array() // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in the callee.
		)
		: array();

	if ( $variation_id ) {
		$variation_product = wc_get_product( $variation_id );

		if (
			! $variation_product
			|| ! $variation_product->is_type( 'variation' )
			|| $variation_product->get_parent_id() !== $product->get_id()
		) {
			wp_send_json_error(
				array(
					'code'    => 'bad_variation',
					'message' => __( 'নির্বাচিত অপশনটি সঠিক নয়', 'assurance' ),
				),
				400
			);
		}
	}

	$added = WC()->cart->add_to_cart( $product->get_id(), $quantity, $variation_id, $variation );

	if ( ! $added ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		wp_send_json_error(
			array(
				'code'    => 'add_failed',
				'message' => ! empty( $notices )
					? wp_strip_all_tags( $notices[0]['notice'] )
					: __( 'কার্টে যোগ করা যায়নি', 'assurance' ),
			),
			400
		);
	}

	wc_clear_notices();

	wp_send_json_success(
		array(
			'redirect' => wc_get_checkout_url(),
		)
	);
}
assurance_ajax( 'buy_now', 'assurance_ajax_buy_now' );

/* ==========================================================================
   Variation popover
   ========================================================================== */

/**
 * Return the attribute options for a variable product.
 *
 * Only in-stock, purchasable variations are advertised, and the price range
 * comes from the product object rather than the request.
 */
function assurance_ajax_variation_form() {
	assurance_verify_request();

	$product = assurance_require_product();

	if ( ! $product->is_type( 'variable' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'not_variable',
				'message' => __( 'এই পণ্যের কোনো অপশন নেই', 'assurance' ),
			),
			400
		);
	}

	$attributes = array();

	foreach ( $product->get_variation_attributes() as $name => $values ) {
		$options = array();

		foreach ( $values as $value ) {
			$label = $value;

			if ( taxonomy_exists( $name ) ) {
				$term = get_term_by( 'slug', $value, $name );

				if ( $term && ! is_wp_error( $term ) ) {
					$label = $term->name;
				}
			}

			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		$attributes[] = array(
			'name'    => 'attribute_' . sanitize_title( $name ),
			'label'   => wc_attribute_label( $name, $product ),
			'options' => $options,
		);
	}

	$variations = array();

	foreach ( $product->get_available_variations() as $variation ) {
		$variations[] = array(
			'id'         => (int) $variation['variation_id'],
			'attributes' => $variation['attributes'],
			'price'      => wp_strip_all_tags( $variation['price_html'] ) ?: wp_strip_all_tags( wc_price( $variation['display_price'] ) ),
			'inStock'    => (bool) $variation['is_in_stock'],
			'purchasable' => (bool) $variation['is_purchasable'],
		);
	}

	wp_send_json_success(
		array(
			'title'      => $product->get_name(),
			'attributes' => $attributes,
			'variations' => $variations,
			'priceHtml'  => wp_strip_all_tags( $product->get_price_html() ),
		)
	);
}
assurance_ajax( 'variation_form', 'assurance_ajax_variation_form' );

/* ==========================================================================
   Cart mutations (drawer + cart page)
   ========================================================================== */

/**
 * Change the quantity of an existing cart line.
 */
function assurance_ajax_update_qty() {
	assurance_verify_request();

	$key      = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
	$quantity = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0;

	// The cart key is a hash WooCommerce generated for this session's cart.
	// Looking it up in the current cart is what scopes the operation to the
	// caller — there is no way to address another visitor's line.
	if ( '' === $key || ! WC()->cart->get_cart_item( $key ) ) {
		wp_send_json_error(
			array(
				'code'    => 'bad_key',
				'message' => __( 'কার্টের আইটেমটি পাওয়া যায়নি', 'assurance' ),
			),
			404
		);
	}

	if ( $quantity <= 0 ) {
		WC()->cart->remove_cart_item( $key );
		$message = __( 'কার্ট থেকে সরানো হয়েছে', 'assurance' );
	} else {
		WC()->cart->set_quantity( $key, $quantity, false );
		$message = __( 'কার্ট হালনাগাদ হয়েছে', 'assurance' );
	}

	wp_send_json_success(
		array_merge(
			assurance_cart_payload(),
			array( 'message' => $message )
		)
	);
}
assurance_ajax( 'update_qty', 'assurance_ajax_update_qty' );

/**
 * Remove a cart line outright.
 */
function assurance_ajax_remove_item() {
	assurance_verify_request();

	$key = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';

	if ( '' === $key || ! WC()->cart->get_cart_item( $key ) ) {
		wp_send_json_error(
			array(
				'code'    => 'bad_key',
				'message' => __( 'কার্টের আইটেমটি পাওয়া যায়নি', 'assurance' ),
			),
			404
		);
	}

	WC()->cart->remove_cart_item( $key );

	wp_send_json_success(
		array_merge(
			assurance_cart_payload(),
			array( 'message' => __( 'কার্ট থেকে সরানো হয়েছে', 'assurance' ) )
		)
	);
}
assurance_ajax( 'remove_item', 'assurance_ajax_remove_item' );

/**
 * Apply or remove a coupon.
 */
function assurance_ajax_coupon() {
	assurance_verify_request();

	$code   = isset( $_POST['code'] ) ? wc_format_coupon_code( wp_unslash( $_POST['code'] ) ) : '';
	$remove = ! empty( $_POST['remove'] );

	if ( '' === $code ) {
		wp_send_json_error(
			array(
				'code'    => 'empty',
				'message' => __( 'কুপন কোড লিখুন', 'assurance' ),
			),
			400
		);
	}

	if ( $remove ) {
		WC()->cart->remove_coupon( $code );
		wc_clear_notices();

		wp_send_json_success(
			array_merge(
				assurance_cart_payload(),
				array( 'message' => __( 'কুপন সরানো হয়েছে', 'assurance' ) )
			)
		);
	}

	// WooCommerce validates existence, expiry, usage limits and cart
	// eligibility; we surface whatever it decides rather than duplicating
	// those rules here.
	$applied = WC()->cart->apply_coupon( $code );

	$errors = wc_get_notices( 'error' );
	wc_clear_notices();

	if ( ! $applied ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_coupon',
				'message' => ! empty( $errors )
					? wp_strip_all_tags( $errors[0]['notice'] )
					: __( 'কুপনটি প্রয়োগ করা যায়নি', 'assurance' ),
			),
			400
		);
	}

	wp_send_json_success(
		array_merge(
			assurance_cart_payload(),
			array( 'message' => __( 'কুপন প্রয়োগ হয়েছে', 'assurance' ) )
		)
	);
}
assurance_ajax( 'coupon', 'assurance_ajax_coupon' );

/**
 * Refresh cart state without changing it.
 *
 * Used when the drawer is opened cold, so a cart mutated in another tab
 * does not render stale.
 */
function assurance_ajax_get_cart() {
	assurance_verify_request();

	wp_send_json_success( assurance_cart_payload() );
}
assurance_ajax( 'get_cart', 'assurance_ajax_get_cart' );

/* ==========================================================================
   Contact form
   ========================================================================== */

/**
 * Handle the contact page form.
 *
 * A honeypot field ("website") is included in the form but hidden with CSS
 * and never shown to real visitors; simple bots that fill every field trip
 * it, and get a fake success response so they do not learn to skip it.
 */
function assurance_ajax_contact_form() {
	assurance_verify_request();

	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success(
			array( 'message' => __( 'ধন্যবাদ! আপনার বার্তাটি পাঠানো হয়েছে।', 'assurance' ) )
		);
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$errors = array();

	if ( '' === $name ) {
		$errors['name'] = __( 'নাম লিখুন', 'assurance' );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		$errors['email'] = __( 'সঠিক ইমেইল ঠিকানা লিখুন', 'assurance' );
	}

	if ( '' === $phone ) {
		$errors['phone'] = __( 'মোবাইল নম্বর লিখুন', 'assurance' );
	}

	if ( '' === $message ) {
		$errors['message'] = __( 'আপনার বার্তাটি লিখুন', 'assurance' );
	}

	if ( ! empty( $errors ) ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid',
				'message' => __( 'ফর্মটি সঠিকভাবে পূরণ করুন', 'assurance' ),
				'fields'  => $errors,
			),
			400
		);
	}

	$site_name = get_bloginfo( 'name' );
	$to        = function_exists( 'assurance_shop_contact_email' )
		? assurance_shop_contact_email()
		: get_option( 'admin_email' );

	$admin_subject = sprintf(
		/* translators: %s: sender name. */
		__( '[যোগাযোগ ফর্ম] %s লিখেছেন', 'assurance' ),
		$name
	);

	// The visitor's own address goes in Reply-To rather than From — From
	// stays the site's own domain so the message does not get flagged for
	// spoofing a sender the mail server does not control.
	$admin_headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = assurance_send_html_mail(
		$to,
		$admin_subject,
		assurance_contact_admin_email( $name, $email, $phone, $message ),
		$admin_headers
	);

	if ( ! $sent ) {
		wp_send_json_error(
			array(
				'code'    => 'send_failed',
				'message' => __( 'বার্তাটি পাঠানো যায়নি, একটু পর আবার চেষ্টা করুন।', 'assurance' ),
			),
			500
		);
	}

	// Best-effort — the visitor's own confirmation email is a courtesy, not
	// the operation the form exists for, so its failure never blocks the
	// success response the admin notice above already earned.
	assurance_send_html_mail(
		$email,
		sprintf(
			/* translators: %s: site name. */
			__( 'আপনার বার্তা পেয়েছি — %s', 'assurance' ),
			$site_name
		),
		assurance_contact_user_email( $name, $message ),
		// From stays on the site's own domain — sending as the shop's Gmail
		// address would fail SPF and land the reply in spam. The mailbox the
		// visitor should actually write to goes in Reply-To.
		array( 'Reply-To: ' . $site_name . ' <' . $to . '>' )
	);

	wp_send_json_success(
		array( 'message' => __( 'ধন্যবাদ! আপনার বার্তাটি পাঠানো হয়েছে, শীঘ্রই যোগাযোগ করা হবে।', 'assurance' ) )
	);
}
assurance_ajax( 'contact_form', 'assurance_ajax_contact_form' );

/* ==========================================================================
   Checkout privacy policy modal
   ========================================================================== */

/**
 * Return the terms or privacy page for the checkout's in-page modal, so a
 * shopper can read either without losing their filled-in checkout form.
 *
 * The requested URL is resolved to a post ID and then checked against the
 * two pages this endpoint is allowed to serve — so it cannot be turned into
 * a reader for arbitrary (including draft or private) content.
 */
function assurance_ajax_policy_page() {
	assurance_verify_request();

	$allowed = array_filter(
		array(
			(int) wc_terms_and_conditions_page_id(),
			(int) get_option( 'wp_page_for_privacy_policy' ),
		)
	);

	$url     = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	$page_id = $url ? (int) url_to_postid( $url ) : 0;

	if ( ! in_array( $page_id, $allowed, true ) ) {
		$page_id = (int) reset( $allowed );
	}

	$page = $page_id ? get_post( $page_id ) : null;

	if ( ! $page || 'publish' !== $page->post_status ) {
		wp_send_json_error(
			array(
				'code'    => 'not_found',
				'message' => __( 'পেজটি পাওয়া যায়নি', 'assurance' ),
			),
			404
		);
	}

	wp_send_json_success(
		array(
			'title' => get_the_title( $page ),
			'html'  => wp_kses_post( apply_filters( 'the_content', $page->post_content ) ),
		)
	);
}
assurance_ajax( 'policy_page', 'assurance_ajax_policy_page' );
