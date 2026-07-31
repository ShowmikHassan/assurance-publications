<?php
/**
 * Theme setup: asset loading, Blocksy interop, editor support.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cache-bust from file mtime in debug, fixed version in production.
 *
 * Hard-coding ASSURANCE_VERSION alone means edits during development are
 * invisible behind the browser cache; filemtime alone busts the cache for
 * every visitor on every deploy even when a file did not change.
 *
 * @param string $rel Path relative to the theme root.
 * @return string Version string.
 */
function assurance_asset_version( $rel ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$path = ASSURANCE_DIR . '/' . ltrim( $rel, '/' );
		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}
	}

	return ASSURANCE_VERSION;
}

/**
 * Register a stylesheet under assets/css/.
 *
 * @param string   $handle Handle suffix; prefixed with "assurance-".
 * @param string   $file   Filename inside assets/css/.
 * @param string[] $deps   Dependencies.
 */
function assurance_style( $handle, $file, $deps = array( 'assurance-tokens' ) ) {
	wp_enqueue_style(
		'assurance-' . $handle,
		ASSURANCE_URI . '/assets/css/' . $file,
		$deps,
		assurance_asset_version( 'assets/css/' . $file )
	);
}

/**
 * Register a script under assets/js/. Always deferred — none of our JS is
 * render-blocking and all of it guards on DOMContentLoaded.
 *
 * @param string   $handle Handle suffix; prefixed with "assurance-".
 * @param string   $file   Filename inside assets/js/.
 * @param string[] $deps   Dependencies.
 */
function assurance_script( $handle, $file, $deps = array() ) {
	wp_enqueue_script(
		'assurance-' . $handle,
		ASSURANCE_URI . '/assets/js/' . $file,
		$deps,
		assurance_asset_version( 'assets/js/' . $file ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

/**
 * Front-end assets.
 *
 * Loaded per-template rather than globally: the checkout page has no reason
 * to parse shop-filter CSS, and the home page has no reason to parse the
 * checkout's. Only tokens, the product card and the off-canvas cart are
 * global, because the card and cart can appear on any page via blocks.
 */
function assurance_enqueue_assets() {
	// Parent theme's compiled stylesheet is the dependency root so that our
	// tokens always cascade after Blocksy's own declarations.
	$root = wp_style_is( 'ct-main-styles', 'registered' ) ? array( 'ct-main-styles' ) : array();

	wp_enqueue_style(
		'assurance-tokens',
		ASSURANCE_URI . '/assets/css/tokens.css',
		$root,
		assurance_asset_version( 'assets/css/tokens.css' )
	);

	assurance_style( 'base', 'base.css' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Global: a product card or the cart drawer can be rendered anywhere.
	assurance_style( 'product-card', 'product-card.css' );
	assurance_style( 'off-canvas-cart', 'off-canvas-cart.css' );

	assurance_script( 'core', 'core.js' );
	assurance_script( 'product-card', 'product-card.js', array( 'assurance-core' ) );
	assurance_script( 'off-canvas-cart', 'off-canvas-cart.js', array( 'assurance-core' ) );
	assurance_script( 'read-later', 'read-later.js', array( 'assurance-core' ) );

	wp_localize_script( 'assurance-core', 'assuranceData', assurance_js_data() );

	if ( is_front_page() || is_home() ) {
		assurance_style( 'home', 'home.css' );
		assurance_script( 'home', 'home.js', array( 'assurance-core' ) );
	}

	if ( is_shop() || is_product_taxonomy() ) {
		assurance_style( 'shop', 'shop.css' );
		assurance_script( 'shop-filters', 'shop-filters.js', array( 'assurance-core' ) );
	}

	if ( is_product() ) {
		assurance_style( 'single-product', 'single-product.css' );
		assurance_script( 'single-product', 'single-product.js', array( 'assurance-core' ) );
	}

	if ( is_cart() || is_checkout() ) {
		assurance_style( 'cart-checkout', 'cart-checkout.css' );
	}

	if ( is_cart() ) {
		assurance_script( 'cart', 'cart.js', array( 'assurance-core' ) );
	}

	if ( is_checkout() ) {
		assurance_script( 'checkout', 'checkout.js', array( 'assurance-core' ) );
	}

	if ( is_page( 'about-us' ) ) {
		assurance_style( 'about', 'about.css' );
	}

	if ( is_page( 'contact' ) ) {
		assurance_style( 'contact', 'contact.css' );
		assurance_script( 'contact', 'contact.js', array( 'assurance-core' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'assurance_enqueue_assets', 20 );

/**
 * Keep cart, checkout and account pages out of full-page caches.
 *
 * These pages are per-visitor: they carry the cart contents, the delivery
 * quote and a session-bound nonce. Served from a shared cache they show one
 * shopper another's totals, and the stale nonce makes every AJAX call fail
 * CSRF — which is what a 403 storm on admin-ajax.php actually is.
 *
 * DONOTCACHEPAGE is honoured by LiteSpeed, WP Rocket, W3TC, WP Super Cache
 * and Hostinger's own cache; the headers cover proxies and Cloudflare that
 * do not read the constant. WooCommerce sets some of this itself, but only
 * once its own constants are defined, which is later than page caches decide.
 */
function assurance_no_cache_pages() {
	if ( is_admin() || ! function_exists( 'is_cart' ) ) {
		return;
	}

	if ( ! is_cart() && ! is_checkout() && ! is_account_page() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	if ( ! headers_sent() ) {
		nocache_headers();
		header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
	}
}
add_action( 'template_redirect', 'assurance_no_cache_pages', 0 );

/**
 * Data bridge to JS.
 *
 * One nonce covers every ap_* endpoint; each handler additionally
 * re-validates its own inputs and capability requirements. Cart-mutating
 * endpoints are intentionally reachable by logged-out users (guests must be
 * able to shop), so the nonce is the CSRF control, not an auth control.
 *
 * @return array
 */
function assurance_js_data() {
	$free_target = assurance_free_shipping_threshold();

	// Filterable so feature modules (read-later, shop filters) can add their
	// own state without setup.php needing to know about them.
	return apply_filters( 'assurance_js_data', array(
		'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
		'nonce'        => wp_create_nonce( 'assurance_ajax' ),
		'cartUrl'      => wc_get_cart_url(),
		'checkoutUrl'  => wc_get_checkout_url(),
		'shopUrl'      => get_permalink( wc_get_page_id( 'shop' ) ),
		'currency'     => get_woocommerce_currency_symbol(),
		'isRtl'        => is_rtl(),
		'freeShipping' => array(
			'enabled'   => $free_target > 0,
			'threshold' => $free_target,
		),
		'i18n'         => array(
			'added'         => __( 'কার্টে যোগ হয়েছে', 'assurance' ),
			'addFailed'     => __( 'যোগ করা যায়নি, আবার চেষ্টা করুন', 'assurance' ),
			'removed'       => __( 'কার্ট থেকে সরানো হয়েছে', 'assurance' ),
			'genericError'  => __( 'কিছু একটা সমস্যা হয়েছে', 'assurance' ),
			'selectOptions' => __( 'অপশন নির্বাচন করুন', 'assurance' ),
			'savedLater'    => __( 'পরে দেখার তালিকায় যোগ হয়েছে', 'assurance' ),
			'removedLater'  => __( 'তালিকা থেকে সরানো হয়েছে', 'assurance' ),
			'loading'       => __( 'লোড হচ্ছে…', 'assurance' ),
			'cartEmpty'     => __( 'আপনার কার্ট খালি', 'assurance' ),
			'closeCart'     => __( 'কার্ট বন্ধ করুন', 'assurance' ),
			'showMore'      => __( 'আরও দেখুন', 'assurance' ),
			'showLess'      => __( 'কম দেখুন', 'assurance' ),
		),
	) );
}

/**
 * Preload the Bengali font subset.
 *
 * Every page on this store renders Bangla, so the Bengali file is on the
 * critical path and would otherwise be discovered only after CSS parses.
 * The Latin subsets are deliberately NOT preloaded — they are small and
 * secondary, and preloading all three would contend for bandwidth with
 * the LCP image.
 *
 * crossorigin is required on font preloads even when same-origin; without
 * it the browser fetches the file twice.
 */
function assurance_preload_fonts() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( ASSURANCE_URI . '/assets/fonts/anek-bangla-bengali.woff2' )
	);
}
add_action( 'wp_head', 'assurance_preload_fonts', 1 );

/**
 * Theme supports.
 *
 * Blocksy already declares woocommerce and the gallery features; we only
 * add what the child introduces.
 */
function assurance_after_setup_theme() {
	load_child_theme_textdomain( 'assurance', ASSURANCE_DIR . '/languages' );

	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );

	// Cover thumbnail sized for the card grid at 2x on the largest column.
	add_image_size( 'assurance-cover', 400, 560, true );
	add_image_size( 'assurance-cover-sm', 160, 224, true );
}
add_action( 'after_setup_theme', 'assurance_after_setup_theme' );

/**
 * Make the custom cover sizes selectable in the media UI.
 *
 * @param array $sizes Existing size choices.
 * @return array
 */
function assurance_custom_image_sizes( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'assurance-cover'    => __( 'Book cover', 'assurance' ),
			'assurance-cover-sm' => __( 'Book cover (small)', 'assurance' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'assurance_custom_image_sizes' );

/**
 * Editor styles, so blocks preview with the real type system.
 */
function assurance_editor_assets() {
	wp_enqueue_style(
		'assurance-editor-tokens',
		ASSURANCE_URI . '/assets/css/tokens.css',
		array(),
		assurance_asset_version( 'assets/css/tokens.css' )
	);
}
add_action( 'enqueue_block_editor_assets', 'assurance_editor_assets' );

/**
 * Blocksy applies its own product-card layers via the
 * blocksy:woocommerce:product-card:* hooks that wrap WooCommerce's
 * content-product.php. Our child template replaces that markup wholesale,
 * so the theme's loop element callbacks would otherwise double-render
 * titles, prices and buttons inside our card.
 *
 * Unhooking them here rather than inside the template keeps the template
 * presentational and means the removal is visible in one place.
 */
function assurance_unhook_blocksy_card() {
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
}
add_action( 'wp', 'assurance_unhook_blocksy_card' );

/**
 * Body classes used as styling hooks for template-level layout switches.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function assurance_body_class( $classes ) {
	$classes[] = 'ap';

	if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$classes[] = 'ap-archive';
	}

	return $classes;
}
add_filter( 'body_class', 'assurance_body_class' );
