<?php
/**
 * Single product.
 *
 * A styling and interaction pass, not a rebuild — WooCommerce's own
 * template structure and hook order are preserved so the PDF Preview
 * Modal, Video Reviews and bKash plugins keep their insertion points.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Swap WooCommerce's bare number input for the shared quantity switcher.
 *
 * @param string     $html    Default markup.
 * @param WC_Product $product Product.
 * @return string
 */
function assurance_quantity_input( $html, $product = null, $args = array() ) {
	if ( ! $product instanceof WC_Product ) {
		return $html;
	}

	// Sold-individually products render a hidden input, not a stepper.
	if ( $product->is_sold_individually() ) {
		return $html;
	}

	return sprintf(
		'<div class="quantity">%s</div>',
		assurance_qty_switcher(
			array(
				'value' => isset( $args['input_value'] ) ? $args['input_value'] : 1,
				'min'   => isset( $args['min_value'] ) ? (int) $args['min_value'] : 1,
				'max'   => isset( $args['max_value'] ) && $args['max_value'] > 0 ? (int) $args['max_value'] : 0,
				'name'  => isset( $args['input_name'] ) ? $args['input_name'] : 'quantity',
				'size'  => 'lg',
				'label' => $product->get_name(),
			)
		)
	);
}
add_filter( 'woocommerce_quantity_input_html', 'assurance_quantity_input', 10, 3 );

/**
 * Full-width Buy Now button, right after the Add to Cart form (30).
 */
function assurance_single_buy_now() {
	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	?>
	<button type="button" class="ap-btn ap-btn--primary ap-btn--block ap-single__buynow" data-ap-buy-now-single>
		<?php assurance_the_icon( 'parcel', array( 'size' => 17 ) ); ?>
		<?php esc_html_e( 'এখনই কিনুন', 'assurance' ); ?>
	</button>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'assurance_single_buy_now', 32 );

/**
 * Trust row under the buy box.
 */
function assurance_single_assurances() {
	?>
	<ul class="ap-single__assure">
		<li>
			<?php assurance_the_icon( 'truck', array( 'size' => 17 ) ); ?>
			<span><?php esc_html_e( 'সারা দেশে হোম ডেলিভারি', 'assurance' ); ?></span>
		</li>
		<li>
			<?php assurance_the_icon( 'book', array( 'size' => 17 ) ); ?>
			<span><?php esc_html_e( 'শতভাগ আসল বই', 'assurance' ); ?></span>
		</li>
		<li>
			<?php assurance_the_icon( 'shield', array( 'size' => 17 ) ); ?>
			<span><?php esc_html_e( 'ক্যাশ অন ডেলিভারি', 'assurance' ); ?></span>
		</li>
	</ul>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'assurance_single_assurances', 45 );

/**
 * Rename the product tabs into Bangla.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function assurance_product_tabs( $tabs ) {
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = __( 'বিবরণ', 'assurance' );
	}

	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = __( 'অতিরিক্ত তথ্য', 'assurance' );
	}

	if ( isset( $tabs['reviews'] ) ) {
		$tabs['reviews']['title'] = sprintf(
			/* translators: %s: review count in Bengali numerals. */
			__( 'রিভিউ (%s)', 'assurance' ),
			assurance_bn_digits( get_comments_number() )
		);
	}

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'assurance_product_tabs', 98 );

/**
 * Related products heading.
 *
 * @param string $heading Existing heading.
 * @return string
 */
function assurance_related_heading( $heading ) {
	return __( 'সম্পর্কিত বই', 'assurance' );
}
add_filter( 'woocommerce_product_related_products_heading', 'assurance_related_heading' );

/**
 * Upsell heading.
 *
 * @param string $heading Existing heading.
 * @return string
 */
function assurance_upsell_heading( $heading ) {
	return __( 'এগুলোও দেখতে পারেন', 'assurance' );
}
add_filter( 'woocommerce_product_upsells_products_heading', 'assurance_upsell_heading' );

/**
 * Four related products, in one row.
 *
 * @param array $args Related product args.
 * @return array
 */
function assurance_related_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'assurance_related_args', 20 );

/**
 * Mark up the gallery for the lightbox.
 *
 * WooCommerce's own lightbox (PhotoSwipe) is disabled in favour of ours:
 * PhotoSwipe is ~40 KB of JS plus a stylesheet, and its default chrome
 * cannot be restyled to match without fighting it.
 */
function assurance_disable_wc_lightbox() {
	remove_theme_support( 'wc-product-gallery-lightbox' );
}
add_action( 'after_setup_theme', 'assurance_disable_wc_lightbox', 99 );

/**
 * Breadcrumb separator and home label.
 *
 * @param array $args Breadcrumb args.
 * @return array
 */
function assurance_breadcrumb_args( $args ) {
	$args['delimiter']   = '<span class="ap-crumb__sep" aria-hidden="true">/</span>';
	$args['home']        = __( 'হোম', 'assurance' );
	$args['wrap_before'] = '<nav class="ap-crumb" aria-label="' . esc_attr__( 'ব্রেডক্রাম্ব', 'assurance' ) . '">';
	$args['wrap_after']  = '</nav>';

	return $args;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'assurance_breadcrumb_args' );

/**
 * Drop the SKU row — an internal stock code, not something a shopper
 * needs to see.
 */
function assurance_hide_sku( $html, $product ) {
	return '';
}
add_filter( 'woocommerce_sku_html', 'assurance_hide_sku', 10, 2 );
