<?php
/**
 * Product card renderer.
 *
 * The card is the most-reused component on the site: the shop grid, the
 * home tabs, the category showcase rows, the cart suggestions carousel and
 * the off-canvas cart's "also bought" strip all render the same markup.
 *
 * It is therefore a standalone function rather than only a WooCommerce
 * loop template — the loop template (woocommerce/content-product.php) is a
 * three-line wrapper that calls into here. That keeps a single source of
 * truth for card markup, so a change to the badge or the price block
 * cannot drift between contexts.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render one product card.
 *
 * @param WC_Product|int $product Product or ID.
 * @param array          $args    {
 *     @type string $size    'default' | 'compact' — compact drops the rating
 *                           and buy-now row for dense contexts (drawer).
 *     @type bool   $lazy    Lazy-load the cover. Pass false above the fold.
 *     @type bool   $buy_now Show the Buy Now button. Default true.
 *     @type string $context Free-form label emitted as a data attribute so
 *                           analytics can tell a home-tab add from a
 *                           shop-grid add.
 * }
 * @return string HTML.
 */
function assurance_product_card( $product, $args = array() ) {
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product || ! $product->is_visible() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'size'    => 'default',
			'lazy'    => true,
			'buy_now' => true,
			'context' => 'grid',
		)
	);

	$compact = 'compact' === $args['size'];
	$id      = $product->get_id();
	$link    = $product->get_permalink();
	$title   = $product->get_name();

	$sale_pct  = assurance_sale_percentage( $product );
	$stock     = assurance_stock_state( $product );
	$category  = assurance_primary_category( $product );
	$in_stock  = $product->is_in_stock();
	$is_variable = $product->is_type( 'variable' );

	// A variable product cannot be added from the card without a variation,
	// so the bag icon opens a popover instead of posting straight to the cart.
	$needs_options = $is_variable || ! $product->is_purchasable() || ! $in_stock;

	ob_start();
	?>
	<article
		class="ap-card<?php echo $compact ? ' ap-card--compact' : ''; ?><?php echo $in_stock ? '' : ' is-out-of-stock'; ?>"
		data-ap-card
		data-product-id="<?php echo esc_attr( $id ); ?>"
		data-context="<?php echo esc_attr( $args['context'] ); ?>"
	>
		<div class="ap-card__media">
			<a
				class="ap-cover"
				href="<?php echo esc_url( $link ); ?>"
				tabindex="-1"
				aria-hidden="true"
			>
				<?php
				echo assurance_cover_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image output.
					$product,
					$compact ? 'assurance-cover-sm' : 'assurance-cover',
					$args['lazy']
				);
				?>
			</a>

			<?php if ( $sale_pct > 0 ) : ?>
				<span class="ap-seal" aria-hidden="true">
					<span class="ap-seal__num"><?php echo esc_html( assurance_bn_digits( $sale_pct ) ); ?>%</span>
					<span class="ap-seal__label"><?php esc_html_e( 'ছাড়', 'assurance' ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( $stock && 'out' === $stock['state'] ) : ?>
				<span class="ap-card__veil">
					<span class="ap-badge ap-badge--out"><?php echo esc_html( $stock['label'] ); ?></span>
				</span>
			<?php endif; ?>

		</div>

		<div class="ap-card__body">
			<?php if ( $category ) : ?>
				<a class="ap-card__cat" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
					<?php echo esc_html( $category->name ); ?>
				</a>
			<?php endif; ?>

			<h3 class="ap-card__title">
				<?php
				/*
				 * This anchor is the card's single accessible name. The cover
				 * link above is aria-hidden and removed from the tab order so
				 * keyboard and screen-reader users get one stop per card
				 * rather than two links pointing at the same page.
				 */
				?>
				<a class="ap-card__link" href="<?php echo esc_url( $link ); ?>">
					<span class="ap-truncate-2"><?php echo esc_html( $title ); ?></span>
				</a>
			</h3>

			<?php
			/*
			 * The rating row is omitted entirely — not just left empty —
			 * when there is nothing to show. Reserving the slot with an
			 * empty div left a visible dead gap between the title and price
			 * on every card in this catalogue, since none have reviews yet;
			 * an empty row is worse than a slightly shorter card.
			 */
			$count = (int) $product->get_review_count();
			?>
			<?php if ( ! $compact && wc_review_ratings_enabled() && $count > 0 ) : ?>
				<div class="ap-card__rating">
					<?php echo assurance_rating_html( (float) $product->get_average_rating(), $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<?php if ( $stock && 'out' !== $stock['state'] ) : ?>
				<span class="ap-badge ap-badge--<?php echo esc_attr( $stock['state'] ); ?> ap-card__stock">
					<?php echo esc_html( $stock['label'] ); ?>
				</span>
			<?php endif; ?>

			<div class="ap-card__foot">
				<?php echo assurance_card_price_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div class="ap-card__actions">
					<?php if ( $args['buy_now'] && ! $compact && $in_stock ) : ?>
						<button
							type="button"
							class="ap-btn ap-btn--ink ap-btn--sm ap-card__buy"
							data-ap-buy-now
							data-product-id="<?php echo esc_attr( $id ); ?>"
							<?php echo $needs_options ? 'data-needs-options="1"' : ''; ?>
						>
							<?php assurance_the_icon( 'parcel', array( 'size' => 15 ) ); ?>
							<span><?php esc_html_e( 'এখনই কিনুন', 'assurance' ); ?></span>
						</button>
					<?php endif; ?>

					<?php if ( $in_stock ) : ?>
						<button
							type="button"
							class="ap-card__bag"
							data-ap-add-to-cart
							data-product-id="<?php echo esc_attr( $id ); ?>"
							<?php echo $needs_options ? 'data-needs-options="1"' : ''; ?>
							aria-label="<?php echo esc_attr(
								$needs_options
									/* translators: %s: product name. */
									? sprintf( __( '%s — অপশন নির্বাচন করুন', 'assurance' ), $title )
									/* translators: %s: product name. */
									: sprintf( __( '%s কার্টে যোগ করুন', 'assurance' ), $title )
							); ?>"
						>
							<?php assurance_the_icon( $needs_options ? 'grid' : 'bag', array( 'size' => 18 ) ); ?>
						</button>
					<?php else : ?>
						<a class="ap-btn ap-btn--outline ap-btn--sm" href="<?php echo esc_url( $link ); ?>">
							<?php esc_html_e( 'বিস্তারিত', 'assurance' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Price block for a card.
 *
 * Rendered from raw values rather than $product->get_price_html() because
 * the default output bakes in <del>/<ins> markup and a "–" range separator
 * we cannot restyle into the two-line "from" treatment the card uses.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function assurance_card_price_html( $product ) {
	$bn = assurance_use_bn_price_digits();

	$fmt = function ( $amount ) use ( $bn ) {
		$html = wc_price( $amount );
		return $bn ? assurance_bn_digits( $html ) : $html;
	};

	// Variable: advertise the floor with a "from" qualifier.
	// instanceof rather than is_type() so the variation-only price methods
	// below are statically resolvable, not just correct at runtime.
	if ( $product instanceof WC_Product_Variable ) {
		$min = $product->get_variation_price( 'min', true );
		$max = $product->get_variation_price( 'max', true );

		$out = '<div class="ap-price">';

		if ( $min !== $max ) {
			$out .= '<span class="ap-price__from">' . esc_html__( 'থেকে', 'assurance' ) . '</span>';
		}

		$out .= '<span class="ap-price__now">' . $fmt( $min ) . '</span>';

		$reg_min = $product->get_variation_regular_price( 'min', true );

		if ( $reg_min > $min ) {
			$out .= '<span class="ap-price__was">' . $fmt( $reg_min ) . '</span>';
		}

		return $out . '</div>';
	}

	if ( '' === $product->get_price() ) {
		return '<div class="ap-price"><span class="ap-price__from">' .
			esc_html__( 'মূল্য জানতে যোগাযোগ করুন', 'assurance' ) .
			'</span></div>';
	}

	$out = '<div class="ap-price">';
	$out .= '<span class="ap-price__now">' . $fmt( wc_get_price_to_display( $product ) ) . '</span>';

	if ( $product->is_on_sale() && $product->get_regular_price() ) {
		$regular = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		$out    .= '<span class="ap-price__was">' . $fmt( $regular ) . '</span>';
	}

	return $out . '</div>';
}

/**
 * Echo a product card.
 *
 * @param WC_Product|int $product Product or ID.
 * @param array          $args    See assurance_product_card().
 */
function assurance_the_product_card( $product, $args = array() ) {
	echo assurance_product_card( $product, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped at construction.
}

/**
 * Render a grid of cards from a WP_Query or an array of products/IDs.
 *
 * @param WP_Query|array $products Query or list.
 * @param array          $args     Card args, plus 'columns' and 'class'.
 * @return string
 */
function assurance_product_grid( $products, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'columns' => 4,
			'class'   => '',
		)
	);

	$items = array();

	if ( $products instanceof WP_Query ) {
		foreach ( $products->posts as $post ) {
			$items[] = is_object( $post ) ? $post->ID : $post;
		}
	} else {
		$items = (array) $products;
	}

	if ( empty( $items ) ) {
		return '';
	}

	$cards = '';
	$index = 0;

	foreach ( $items as $item ) {
		// The first row is above the fold on most viewports; eager-load it
		// so the LCP candidate is not deferred behind the lazy queue.
		$cards .= assurance_product_card(
			$item,
			array_merge(
				$args,
				array( 'lazy' => $index >= $args['columns'] )
			)
		);
		$index++;
	}

	if ( '' === $cards ) {
		return '';
	}

	return sprintf(
		'<div class="ap-grid %s" style="--ap-cols:%d">%s</div>',
		esc_attr( $args['class'] ),
		absint( $args['columns'] ),
		$cards
	);
}
