<?php
/**
 * Off-canvas cart.
 *
 * Lives in the child theme rather than a block, because it is global
 * chrome — not editable page content. Rendered once into wp_footer and
 * refreshed through WooCommerce's own fragment mechanism so it can never
 * drift from the real cart.
 *
 * Blocksy Companion is the free edition here, which ships no off-canvas
 * cart of its own, so there is nothing to disable — we simply intercept
 * clicks on the theme's header cart link (a.ct-cart-item).
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the drawer's inner contents.
 *
 * Returned by every cart-mutating AJAX endpoint and used for the initial
 * server render, so both paths produce byte-identical markup.
 *
 * @return string
 */
function assurance_render_drawer_contents() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	$cart     = WC()->cart;
	$items    = $cart->get_cart();
	$progress = assurance_free_shipping_progress();

	ob_start();

	if ( empty( $items ) ) {
		?>
		<div class="ap-drawer__empty">
			<div class="ap-empty">
				<span class="ap-empty__icon"><?php assurance_the_icon( 'bag', array( 'size' => 24 ) ); ?></span>
				<p class="ap-empty__title"><?php esc_html_e( 'আপনার কার্ট খালি', 'assurance' ); ?></p>
				<p class="ap-empty__text"><?php esc_html_e( 'পছন্দের বই যোগ করে কেনাকাটা শুরু করুন।', 'assurance' ); ?></p>
				<a class="ap-btn ap-btn--primary" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'বই দেখুন', 'assurance' ); ?>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	if ( $progress['show'] ) :
		?>
		<div class="ap-freeship<?php echo $progress['met'] ? ' is-met' : ''; ?>">
			<p class="ap-freeship__text">
				<?php if ( $progress['met'] ) : ?>
					<?php assurance_the_icon( 'truck', array( 'size' => 16 ) ); ?>
					<strong><?php esc_html_e( 'অভিনন্দন! ফ্রি ডেলিভারি পেয়েছেন', 'assurance' ); ?></strong>
				<?php else : ?>
					<?php
					printf(
						/* translators: %s: formatted remaining amount. */
						esc_html__( 'আর %sবই কিনলেই ফ্রি ডেলিভারি', 'assurance' ),
						'<strong>' . wp_kses_post( wc_price( $progress['remaining'] ) ) . '</strong>'
					);
					?>
				<?php endif; ?>
			</p>
			<div
				class="ap-freeship__track"
				role="progressbar"
				aria-valuemin="0"
				aria-valuemax="100"
				aria-valuenow="<?php echo esc_attr( (string) round( $progress['percent'] ) ); ?>"
				aria-label="<?php esc_attr_e( 'ফ্রি ডেলিভারির অগ্রগতি', 'assurance' ); ?>"
			>
				<span class="ap-freeship__fill" style="width:<?php echo esc_attr( (string) $progress['percent'] ); ?>%">
					<span class="ap-freeship__truck" aria-hidden="true"><?php assurance_the_icon( 'truck', array( 'size' => 12 ) ); ?></span>
				</span>
			</div>
		</div>
		<?php
	endif;
	?>

	<ul class="ap-drawer__items">
		<?php
		foreach ( $items as $key => $item ) :
			$product = isset( $item['data'] ) ? $item['data'] : null;

			if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
				continue;
			}

			// Only show lines WooCommerce agrees are still visible, so a
			// product unpublished mid-session does not render a dead link.
			if ( ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) {
				continue;
			}

			$permalink = $product->is_visible() ? $product->get_permalink( $item ) : '';
			$meta      = wc_get_formatted_cart_item_data( $item, true );
			?>
			<li class="ap-line" data-cart-key="<?php echo esc_attr( $key ); ?>">
				<div class="ap-line__media">
					<?php if ( $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
					<?php endif; ?>
					<?php
					echo wp_kses_post(
						$product->get_image(
							'assurance-cover-sm',
							array(
								'class'    => 'ap-line__img',
								'loading'  => 'lazy',
								'decoding' => 'async',
							)
						)
					);
					?>
					<?php if ( $permalink ) : ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="ap-line__body">
					<p class="ap-line__title">
						<?php if ( $permalink ) : ?>
							<a href="<?php echo esc_url( $permalink ); ?>">
								<?php echo esc_html( $product->get_name() ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $product->get_name() ); ?>
						<?php endif; ?>
					</p>

					<?php if ( $meta ) : ?>
						<div class="ap-line__meta"><?php echo wp_kses_post( $meta ); ?></div>
					<?php endif; ?>

					<div class="ap-line__foot">
						<?php
						assurance_the_qty_switcher(
							array(
								'value'    => (int) $item['quantity'],
								'cart_key' => $key,
								'max'      => $product->get_max_purchase_quantity(),
								'min'      => $product->get_min_purchase_quantity(),
								'size'     => 'sm',
								'label'    => $product->get_name(),
							)
						);
						?>
						<span class="ap-line__price">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', $cart->get_product_subtotal( $product, $item['quantity'] ), $item, $key ) ); ?>
						</span>
					</div>
				</div>

				<button
					type="button"
					class="ap-line__remove ap-icon-btn ap-icon-btn--bare"
					data-ap-remove-item
					data-cart-key="<?php echo esc_attr( $key ); ?>"
					aria-label="<?php echo esc_attr(
						/* translators: %s: product name. */
						sprintf( __( '%s কার্ট থেকে সরান', 'assurance' ), $product->get_name() )
					); ?>"
				>
					<?php assurance_the_icon( 'trash', array( 'size' => 16 ) ); ?>
				</button>
			</li>
			<?php
		endforeach;
		?>
	</ul>

	<?php
	$suggestions = assurance_cart_suggestions( 3 );

	if ( ! empty( $suggestions ) ) :
		?>
		<div class="ap-drawer__suggest">
			<p class="ap-drawer__suggest-title"><?php esc_html_e( 'আরও কিনুন', 'assurance' ); ?></p>
			<div class="ap-drawer__suggest-row">
				<?php foreach ( $suggestions as $suggestion ) : ?>
					<?php assurance_the_product_card( $suggestion, array( 'size' => 'compact', 'context' => 'drawer' ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	endif;

	return ob_get_clean();
}

/**
 * The drawer's footer totals, rendered separately so it can be a sticky
 * sibling of the scrolling contents rather than scrolling with them.
 *
 * @return string
 */
function assurance_render_drawer_footer() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return '';
	}

	ob_start();
	?>
	<div class="ap-drawer__totals">
		<span><?php esc_html_e( 'সর্বমোট মূল্য', 'assurance' ); ?></span>
		<strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
	</div>
	<p class="ap-drawer__note"><?php esc_html_e( 'ডেলিভারি চার্জ চেকআউটে যোগ হবে', 'assurance' ); ?></p>
	<div class="ap-drawer__cta">
		<a class="ap-btn ap-btn--outline" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-ap-skip-drawer>
			<?php esc_html_e( 'কার্ট দেখুন', 'assurance' ); ?>
		</a>
		<a class="ap-btn ap-btn--primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
			<?php esc_html_e( 'চেকআউট', 'assurance' ); ?>
			<?php assurance_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Products to suggest alongside the current cart.
 *
 * Prefers genuine cross-sells the shop has curated; falls back to best
 * sellers. Anything already in the cart is excluded — suggesting a book the
 * shopper just added is the fastest way to make the strip look broken.
 *
 * @param int   $limit   How many to return.
 * @param int[] $exclude Extra IDs to skip (already shown in the carousel).
 * @return int[] Product IDs.
 */
function assurance_cart_suggestions( $limit = 4, $exclude = array() ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return array();
	}

	$in_cart = array();

	foreach ( WC()->cart->get_cart() as $item ) {
		$in_cart[] = (int) $item['product_id'];
	}

	$skip = array_unique( array_merge( $in_cart, array_map( 'absint', (array) $exclude ) ) );

	$ids = array();

	// 1. Curated cross-sells.
	foreach ( WC()->cart->get_cross_sells() as $cross_sell_id ) {
		$cross_sell_id = (int) $cross_sell_id;

		if ( ! in_array( $cross_sell_id, $skip, true ) ) {
			$ids[] = $cross_sell_id;
		}
	}

	// 2. Top over the trailing period, then all-time, to backfill.
	if ( count( $ids ) < $limit ) {
		$query = new WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'posts_per_page'      => $limit * 3,
				'post__not_in'        => array_merge( $skip, $ids ),
				'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'             => 'meta_value_num',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
				'tax_query'           => WC()->query->get_tax_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);

		foreach ( $query->posts as $post_id ) {
			$product = wc_get_product( $post_id );

			if ( $product && $product->is_visible() && $product->is_in_stock() && $product->is_purchasable() ) {
				$ids[] = (int) $post_id;
			}
		}
	}

	return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
}

/**
 * Print the drawer shell into the footer.
 *
 * Rendered on every page because a card — and therefore an add-to-cart —
 * can appear anywhere, including inside a block on a static page.
 */
function assurance_render_drawer() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$count = WC()->cart->get_cart_contents_count();
	?>
	<div class="ap-drawer-root" data-ap-drawer-root hidden>
		<div class="ap-drawer__scrim" data-ap-drawer-close tabindex="-1"></div>

		<aside
			class="ap-drawer"
			role="dialog"
			aria-modal="true"
			aria-labelledby="ap-drawer-title"
			data-ap-drawer
		>
			<header class="ap-drawer__head">
				<h2 class="ap-drawer__title" id="ap-drawer-title">
					<?php esc_html_e( 'আপনার কার্ট', 'assurance' ); ?>
					<span class="ap-drawer__count" data-ap-drawer-count><?php echo esc_html( assurance_bn_digits( $count ) ); ?></span>
				</h2>
				<button
					type="button"
					class="ap-icon-btn ap-icon-btn--bare"
					data-ap-drawer-close
					aria-label="<?php esc_attr_e( 'কার্ট বন্ধ করুন', 'assurance' ); ?>"
				>
					<?php assurance_the_icon( 'close', array( 'size' => 20 ) ); ?>
				</button>
			</header>

			<div class="ap-drawer__body" data-ap-drawer-body>
				<?php echo assurance_render_drawer_contents(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction. ?>
			</div>

			<footer class="ap-drawer__foot" data-ap-drawer-foot>
				<?php echo assurance_render_drawer_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction. ?>
			</footer>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'assurance_render_drawer', 20 );

/**
 * Add our drawer regions to WooCommerce's fragment payload.
 *
 * Registering them here means the drawer also refreshes after an add that
 * came from somewhere else entirely — WooCommerce's own AJAX buttons, a
 * block, or another plugin — not only from our endpoints.
 *
 * @param array $fragments Existing fragments.
 * @return array
 */
function assurance_cart_fragments( $fragments ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}

	$count = WC()->cart->get_cart_contents_count();

	$fragments['[data-ap-drawer-body]'] = sprintf(
		'<div class="ap-drawer__body" data-ap-drawer-body>%s</div>',
		assurance_render_drawer_contents()
	);

	$fragments['[data-ap-drawer-foot]'] = sprintf(
		'<footer class="ap-drawer__foot" data-ap-drawer-foot>%s</footer>',
		assurance_render_drawer_footer()
	);

	$fragments['[data-ap-drawer-count]'] = sprintf(
		'<span class="ap-drawer__count" data-ap-drawer-count>%s</span>',
		esc_html( assurance_bn_digits( $count ) )
	);

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'assurance_cart_fragments' );

/**
 * Quantity switcher.
 *
 * Replaces WooCommerce's bare number input everywhere it appears — drawer,
 * cart table and single product — so the control is one component with one
 * set of keyboard and screen-reader semantics.
 *
 * @param array $args {
 *     @type int    $value    Current quantity.
 *     @type int    $min      Minimum.
 *     @type int    $max      Maximum; 0 or -1 for unlimited.
 *     @type string $cart_key Cart line key, when editing an existing line.
 *     @type string $name     Input name, when used inside a form.
 *     @type string $size     'sm' for the compact variant.
 *     @type string $label    Product name, for the accessible label.
 * }
 * @return string
 */
function assurance_qty_switcher( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'value'    => 1,
			'min'      => 1,
			'max'      => 0,
			'cart_key' => '',
			'name'     => 'quantity',
			'size'     => '',
			'label'    => '',
		)
	);

	$max = ( $args['max'] > 0 ) ? (int) $args['max'] : '';
	$min = max( 0, (int) $args['min'] );

	$label = $args['label']
		/* translators: %s: product name. */
		? sprintf( __( '%s — পরিমাণ', 'assurance' ), $args['label'] )
		: __( 'পরিমাণ', 'assurance' );

	ob_start();
	?>
	<div class="ap-qty<?php echo $args['size'] ? ' ap-qty--' . esc_attr( $args['size'] ) : ''; ?>" data-ap-qty>
		<button
			type="button"
			class="ap-qty__btn"
			data-ap-qty-step="-1"
			aria-label="<?php esc_attr_e( 'পরিমাণ কমান', 'assurance' ); ?>"
		>
			<?php assurance_the_icon( 'minus', array( 'size' => 14 ) ); ?>
		</button>

		<input
			type="number"
			class="ap-qty__input"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( (string) $args['value'] ); ?>"
			min="<?php echo esc_attr( (string) $min ); ?>"
			<?php echo '' !== $max ? 'max="' . esc_attr( (string) $max ) . '"' : ''; ?>
			step="1"
			inputmode="numeric"
			autocomplete="off"
			aria-label="<?php echo esc_attr( $label ); ?>"
			<?php echo $args['cart_key'] ? 'data-cart-key="' . esc_attr( $args['cart_key'] ) . '"' : ''; ?>
		/>

		<button
			type="button"
			class="ap-qty__btn"
			data-ap-qty-step="1"
			aria-label="<?php esc_attr_e( 'পরিমাণ বাড়ান', 'assurance' ); ?>"
		>
			<?php assurance_the_icon( 'plus', array( 'size' => 14 ) ); ?>
		</button>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Echo a quantity switcher.
 *
 * @param array $args See assurance_qty_switcher().
 */
function assurance_the_qty_switcher( $args = array() ) {
	echo assurance_qty_switcher( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction.
}
