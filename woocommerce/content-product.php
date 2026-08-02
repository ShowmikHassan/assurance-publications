<?php
/**
 * Product card in the WooCommerce loop.
 *
 * Blocksy wraps this template with its own
 * blocksy:woocommerce:product-card:before/after hooks rather than replacing
 * it, so overriding it here composes cleanly with the parent theme. The
 * parent's individual loop-element callbacks are unhooked in
 * inc/setup.php — see assurance_unhook_blocksy_card().
 *
 * The <li> wrapper and its WooCommerce classes are preserved because
 * WooCommerce's own column CSS, the products <ul> and several third-party
 * plugins select on them.
 *
 * @package Assurance
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

?>
<li <?php wc_product_class( 'ap-card-item', $product ); ?>>
	<?php assurance_the_product_card( $product, array( 'context' => 'loop' ) ); ?>
</li>
