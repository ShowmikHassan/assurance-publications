<?php
/**
 * Cart page.
 *
 * Rendered as a list of rows rather than a <table>. WooCommerce's default
 * table needs a separate stacked-card stylesheet at mobile widths and the
 * two layouts drift apart; one flex/grid row that reflows handles both.
 *
 * Quantity changes and removals go through the same AJAX endpoints as the
 * drawer, so the two views cannot disagree.
 *
 * @package Assurance
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<div class="ap-cart">
	<div class="ap-cart__main">
	<form class="woocommerce-cart-form ap-cart__form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="ap-cart__items" role="list">
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}

				$permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<div
					class="ap-cart-row <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>"
					role="listitem"
					data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
				>
					<div class="ap-cart-row__media">
						<?php
						$thumbnail = apply_filters(
							'woocommerce_cart_item_thumbnail',
							$_product->get_image( 'assurance-cover-sm', array( 'class' => 'ap-cart-row__img' ) ),
							$cart_item,
							$cart_item_key
						);

						if ( $permalink ) {
							printf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( $permalink ), wp_kses_post( $thumbnail ) );
						} else {
							echo wp_kses_post( $thumbnail );
						}
						?>
					</div>

					<div class="ap-cart-row__info">
						<h3 class="ap-cart-row__title">
							<?php
							if ( $permalink ) {
								printf( '<a href="%s">%s</a>', esc_url( $permalink ), esc_html( $_product->get_name() ) );
							} else {
								echo esc_html( $_product->get_name() );
							}

							do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
							?>
						</h3>

						<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>

						<?php if ( $_product->is_sold_individually() ) : ?>
							<p class="ap-cart-row__note"><?php esc_html_e( 'একটির বেশি নেওয়া যাবে না', 'assurance' ); ?></p>
						<?php elseif ( ! $_product->is_in_stock() ) : ?>
							<p class="ap-cart-row__note ap-cart-row__note--warn"><?php esc_html_e( 'স্টকে নেই', 'assurance' ); ?></p>
						<?php endif; ?>

						<div class="ap-cart-row__unit">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) ); ?>
							<span class="ap-cart-row__unit-label"><?php esc_html_e( 'প্রতি কপি', 'assurance' ); ?></span>
						</div>
					</div>

					<div class="ap-cart-row__qty">
						<?php
						if ( $_product->is_sold_individually() ) {
							printf( '<input type="hidden" name="cart[%s][qty]" value="1" />', esc_attr( $cart_item_key ) );
							echo '<span class="ap-cart-row__qty-fixed">1</span>';
						} else {
							assurance_the_qty_switcher(
								array(
									'value'    => $cart_item['quantity'],
									'min'      => 0,
									'max'      => $_product->get_max_purchase_quantity(),
									'cart_key' => $cart_item_key,
									'name'     => 'cart[' . $cart_item_key . '][qty]',
									'label'    => $_product->get_name(),
								)
							);
						}
						?>
					</div>

					<div class="ap-cart-row__total">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
					</div>

					<button
						type="button"
						class="ap-cart-row__remove ap-icon-btn ap-icon-btn--bare"
						data-ap-remove-item
						data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( '%s কার্ট থেকে সরান', 'assurance' ), $_product->get_name() ) ); ?>"
					>
						<?php assurance_the_icon( 'trash', array( 'size' => 17 ) ); ?>
					</button>
				</div>
				<?php
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</div>

		<div class="ap-cart__actions">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="ap-coupon" data-ap-coupon>
					<label class="ap-coupon__label" for="coupon_code">
						<?php assurance_the_icon( 'tag', array( 'size' => 15 ) ); ?>
						<?php esc_html_e( 'কুপন কোড', 'assurance' ); ?>
					</label>
					<div class="ap-coupon__row">
						<input
							type="text"
							name="coupon_code"
							class="ap-coupon__input"
							id="coupon_code"
							value=""
							placeholder="<?php esc_attr_e( 'কোড লিখুন', 'assurance' ); ?>"
							autocomplete="off"
						/>
						<button type="button" class="ap-btn ap-btn--outline" data-ap-apply-coupon>
							<?php esc_html_e( 'প্রয়োগ করুন', 'assurance' ); ?>
						</button>
					</div>
					<p class="ap-coupon__msg" role="status" aria-live="polite" data-ap-coupon-msg></p>
				</div>
			<?php endif; ?>

			<a class="ap-btn ap-btn--link ap-cart__continue" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
				<?php assurance_the_icon( 'chevron-left', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'আরও বই দেখুন', 'assurance' ); ?>
			</a>

			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php assurance_cart_suggestions_carousel(); ?>
	</div>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<aside class="ap-cart__totals cart-collaterals">
		<?php do_action( 'woocommerce_cart_collaterals' ); ?>
	</aside>
</div>

<?php
/*
 * Mobile pay bar.
 *
 * The totals panel sits far below the item list on a phone, so the primary
 * action would otherwise be off-screen for the whole scroll. Rendered here
 * (outside .ap-cart__totals) so the AJAX totals refresh cannot destroy it;
 * cart.js re-syncs the amount instead.
 */
?>
<div class="ap-cartbar" data-ap-cartbar>
	<div class="ap-cartbar__sum">
		<span class="ap-cartbar__label"><?php esc_html_e( 'সর্বমোট', 'assurance' ); ?></span>
		<strong class="ap-cartbar__amount" data-ap-cartbar-amount><?php echo wp_kses_post( WC()->cart->get_total() ); ?></strong>
	</div>
	<a class="ap-btn ap-btn--primary ap-cartbar__cta" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
		<?php esc_html_e( 'চেকআউট', 'assurance' ); ?>
		<?php assurance_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
	</a>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
