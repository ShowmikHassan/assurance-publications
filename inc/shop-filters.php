<?php
/**
 * Shop archive filtering.
 *
 * Category and price filters run over the real WP_Query, so the same URL
 * renders identically whether it was reached by AJAX, a shared link, or the
 * back button. The AJAX path exists only to avoid a full page repaint — it
 * is never the sole way to reach a filtered view.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read and sanitise the active filters from the request.
 *
 * @return array{cats:string[],min:float,max:float,orderby:string,paged:int}
 */
function assurance_current_filters() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public, read-only archive filtering.
	$cats = array();

	if ( isset( $_GET['product_cat'] ) ) {
		$raw  = wp_unslash( $_GET['product_cat'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised below.
		$list = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		$cats = array_filter( array_map( 'sanitize_title', $list ) );
	}

	$bounds = assurance_price_bounds();

	$min = isset( $_GET['min_price'] ) ? (float) wp_unslash( $_GET['min_price'] ) : $bounds['min'];
	$max = isset( $_GET['max_price'] ) ? (float) wp_unslash( $_GET['max_price'] ) : $bounds['max'];

	// Clamp to the real catalogue range and repair an inverted pair, so a
	// hand-edited URL cannot produce an empty grid or a huge scan.
	$min = max( $bounds['min'], min( $min, $bounds['max'] ) );
	$max = min( $bounds['max'], max( $max, $bounds['min'] ) );

	if ( $min > $max ) {
		list( $min, $max ) = array( $max, $min );
	}

	$allowed_orderby = array( 'menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc' );
	$orderby         = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';

	if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
		$orderby = '';
	}

	$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return array(
		'cats'    => array_values( $cats ),
		'min'     => $min,
		'max'     => $max,
		'orderby' => $orderby,
		'paged'   => max( 1, $paged ),
	);
}

/**
 * The catalogue's actual price floor and ceiling.
 *
 * Cached in a transient because it is a full-table aggregate and the
 * answer only changes when a product's price does.
 *
 * @return array{min:float,max:float}
 */
function assurance_price_bounds() {
	$cached = get_transient( 'assurance_price_bounds' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	// _price is WooCommerce's indexed lookup meta; wc_product_meta_lookup
	// is faster still and is the table WooCommerce itself filters on.
	$row = $wpdb->get_row(
		"SELECT MIN(min_price) AS min_price, MAX(max_price) AS max_price
		 FROM {$wpdb->wc_product_meta_lookup} lookup
		 INNER JOIN {$wpdb->posts} posts ON posts.ID = lookup.product_id
		 WHERE posts.post_status = 'publish' AND posts.post_type = 'product'",
		ARRAY_A
	);

	$bounds = array(
		'min' => isset( $row['min_price'] ) ? (float) floor( (float) $row['min_price'] ) : 0.0,
		'max' => isset( $row['max_price'] ) ? (float) ceil( (float) $row['max_price'] ) : 1000.0,
	);

	if ( $bounds['max'] <= $bounds['min'] ) {
		$bounds['max'] = $bounds['min'] + 100;
	}

	set_transient( 'assurance_price_bounds', $bounds, DAY_IN_SECONDS );

	return $bounds;
}

/**
 * Drop the cached bounds when a product is saved or deleted.
 *
 * @param int $product_id Product ID.
 */
function assurance_flush_price_bounds( $product_id = 0 ) {
	delete_transient( 'assurance_price_bounds' );
}
add_action( 'woocommerce_update_product', 'assurance_flush_price_bounds' );
add_action( 'woocommerce_new_product', 'assurance_flush_price_bounds' );
add_action( 'woocommerce_delete_product', 'assurance_flush_price_bounds' );

/**
 * Apply the price filter to the archive query.
 *
 * Category filtering is left to WooCommerce's own product_cat handling,
 * which already understands the taxonomy, hierarchy and term counts.
 *
 * @param array $meta_query Existing meta query.
 * @return array
 */
function assurance_price_meta_query( $meta_query ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['min_price'] ) && ! isset( $_GET['max_price'] ) ) {
		return $meta_query;
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$filters = assurance_current_filters();

	$meta_query[] = array(
		'key'     => '_price',
		'value'   => array( $filters['min'], $filters['max'] ),
		'compare' => 'BETWEEN',
		'type'    => 'DECIMAL(10,2)',
	);

	return $meta_query;
}
add_filter( 'woocommerce_product_query_meta_query', 'assurance_price_meta_query' );

/**
 * Support multiple categories in one request.
 *
 * WooCommerce's own product_cat handling takes a single slug; the sidebar
 * lets the shopper tick several, so a comma list is expanded here.
 *
 * @param array $tax_query Existing tax query.
 * @return array
 */
function assurance_category_tax_query( $tax_query ) {
	$filters = assurance_current_filters();

	if ( count( $filters['cats'] ) < 2 ) {
		return $tax_query;
	}

	$tax_query[] = array(
		'taxonomy'         => 'product_cat',
		'field'            => 'slug',
		'terms'            => $filters['cats'],
		'operator'         => 'IN',
		'include_children' => true,
	);

	return $tax_query;
}
add_filter( 'woocommerce_product_query_tax_query', 'assurance_category_tax_query' );

/**
 * Render the filter sidebar.
 */
function assurance_shop_sidebar() {
	$filters = assurance_current_filters();
	$bounds  = assurance_price_bounds();
	$active  = ! empty( $filters['cats'] )
		|| $filters['min'] > $bounds['min']
		|| $filters['max'] < $bounds['max'];
	?>
	<aside class="ap-shop__side" data-ap-filters>
		<div class="ap-filters__head">
			<h2 class="ap-filters__title">
				<?php assurance_the_icon( 'filter', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'ফিল্টার', 'assurance' ); ?>
			</h2>
			<a
				class="ap-filters__clear<?php echo $active ? '' : ' ap-hidden'; ?>"
				href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"
				data-ap-clear-filters
			>
				<?php esc_html_e( 'সব মুছুন', 'assurance' ); ?>
			</a>
			<button
				type="button"
				class="ap-icon-btn ap-icon-btn--bare ap-filters__close"
				data-ap-filters-close
				aria-label="<?php esc_attr_e( 'ফিল্টার বন্ধ করুন', 'assurance' ); ?>"
			>
				<?php assurance_the_icon( 'close', array( 'size' => 20 ) ); ?>
			</button>
		</div>

		<?php assurance_filter_price( $filters, $bounds ); ?>
		<?php assurance_filter_categories( $filters ); ?>
		<?php assurance_filter_bestsellers(); ?>
	</aside>
	<?php
}

/**
 * Category checkboxes with counts.
 *
 * @param array $filters Active filters.
 */
function assurance_filter_categories( $filters ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}
	?>
	<section class="ap-filter">
		<h3 class="ap-filter__title"><?php esc_html_e( 'বিভাগ', 'assurance' ); ?></h3>
		<ul class="ap-filter__list">
			<?php foreach ( $terms as $term ) : ?>
				<li class="ap-filter__item">
					<label class="ap-check">
						<input
							type="checkbox"
							value="<?php echo esc_attr( $term->slug ); ?>"
							data-ap-cat
							<?php checked( in_array( $term->slug, $filters['cats'], true ) ); ?>
						/>
						<span class="ap-check__box" aria-hidden="true">
							<?php assurance_the_icon( 'check', array( 'size' => 12 ) ); ?>
						</span>
						<span class="ap-check__label"><?php echo esc_html( $term->name ); ?></span>
						<span class="ap-check__count"><?php echo esc_html( assurance_bn_digits( $term->count ) ); ?></span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

/**
 * Dual-handle price range.
 *
 * Built from two native range inputs rather than a slider library: it is
 * keyboard-accessible and screen-reader-announceable for free, works
 * without JS as two plain sliders, and costs no third-party bytes.
 *
 * @param array $filters Active filters.
 * @param array $bounds  Catalogue bounds.
 */
function assurance_filter_price( $filters, $bounds ) {
	?>
	<section class="ap-filter">
		<h3 class="ap-filter__title"><?php esc_html_e( 'মূল্যসীমা', 'assurance' ); ?></h3>

		<div
			class="ap-range"
			data-ap-range
			data-floor="<?php echo esc_attr( (string) $bounds['min'] ); ?>"
			data-ceil="<?php echo esc_attr( (string) $bounds['max'] ); ?>"
		>
			<div class="ap-range__track">
				<span class="ap-range__fill" data-ap-range-fill></span>
			</div>

			<input
				type="range"
				class="ap-range__input ap-range__input--min"
				data-ap-range-min
				min="<?php echo esc_attr( (string) $bounds['min'] ); ?>"
				max="<?php echo esc_attr( (string) $bounds['max'] ); ?>"
				step="10"
				value="<?php echo esc_attr( (string) $filters['min'] ); ?>"
				aria-label="<?php esc_attr_e( 'সর্বনিম্ন মূল্য', 'assurance' ); ?>"
			/>
			<input
				type="range"
				class="ap-range__input ap-range__input--max"
				data-ap-range-max
				min="<?php echo esc_attr( (string) $bounds['min'] ); ?>"
				max="<?php echo esc_attr( (string) $bounds['max'] ); ?>"
				step="10"
				value="<?php echo esc_attr( (string) $filters['max'] ); ?>"
				aria-label="<?php esc_attr_e( 'সর্বোচ্চ মূল্য', 'assurance' ); ?>"
			/>
		</div>

		<p class="ap-range__readout" aria-live="polite">
			<span data-ap-range-out-min><?php echo wp_kses_post( wc_price( $filters['min'] ) ); ?></span>
			<span class="ap-range__dash">–</span>
			<span data-ap-range-out-max><?php echo wp_kses_post( wc_price( $filters['max'] ) ); ?></span>
		</p>
	</section>
	<?php
}

/**
 * Small best-selling list in the sidebar.
 */
function assurance_filter_bestsellers() {
	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 4,
			'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'fields'              => 'ids',
		)
	);

	if ( empty( $query->posts ) ) {
		return;
	}
	?>
	<section class="ap-filter ap-filter--best">
		<h3 class="ap-filter__title"><?php esc_html_e( 'বেস্ট সেলিং', 'assurance' ); ?></h3>
		<ul class="ap-minilist">
			<?php
			foreach ( $query->posts as $post_id ) :
				$product = wc_get_product( $post_id );

				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}
				?>
				<li class="ap-minilist__item">
					<a class="ap-minilist__link" href="<?php echo esc_url( $product->get_permalink() ); ?>">
						<span class="ap-minilist__media">
							<?php echo wp_kses_post( $product->get_image( 'assurance-cover-sm', array( 'loading' => 'lazy' ) ) ); ?>
						</span>
						<span class="ap-minilist__body">
							<span class="ap-minilist__title"><?php echo esc_html( $product->get_name() ); ?></span>
							<span class="ap-minilist__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

/**
 * Return a filtered product grid.
 */
function assurance_ajax_shop_filter() {
	assurance_verify_request();

	$cats = isset( $_POST['cats'] ) && is_array( $_POST['cats'] )
		? array_filter( array_map( 'sanitize_title', wp_unslash( $_POST['cats'] ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_title is the sanitiser.
		: array();

	$bounds = assurance_price_bounds();

	$min = isset( $_POST['min'] ) ? (float) wp_unslash( $_POST['min'] ) : $bounds['min'];
	$max = isset( $_POST['max'] ) ? (float) wp_unslash( $_POST['max'] ) : $bounds['max'];

	$min = max( $bounds['min'], min( $min, $bounds['max'] ) );
	$max = min( $bounds['max'], max( $max, $bounds['min'] ) );

	if ( $min > $max ) {
		list( $min, $max ) = array( $max, $min );
	}

	$paged   = isset( $_POST['paged'] ) ? max( 1, absint( wp_unslash( $_POST['paged'] ) ) ) : 1;
	$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : '';

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => (int) apply_filters( 'loop_shop_per_page', 12 ),
		'paged'          => $paged,
		'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	);

	// Respect catalogue visibility exactly as the archive does.
	$args['tax_query'][] = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => 'exclude-from-catalog',
		'operator' => 'NOT IN',
	);

	if ( ! empty( $cats ) ) {
		$args['tax_query'][] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $cats,
			'operator'         => 'IN',
			'include_children' => true,
		);
	}

	// Only constrain price when the shopper actually narrowed the range;
	// a BETWEEN across the full catalogue just costs a scan.
	if ( $min > $bounds['min'] || $max < $bounds['max'] ) {
		$args['meta_query'][] = array(
			'key'     => '_price',
			'value'   => array( $min, $max ),
			'compare' => 'BETWEEN',
			'type'    => 'DECIMAL(10,2)',
		);
	}

	switch ( $orderby ) {
		case 'price':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'ASC';
			break;
		case 'price-desc':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'popularity':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'rating':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'date':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
		default:
			$args['orderby'] = 'menu_order title';
			$args['order']   = 'ASC';
	}

	$query = new WP_Query( $args );

	$cards = '';

	foreach ( $query->posts as $index => $post ) {
		$cards .= assurance_product_card(
			$post->ID,
			array(
				'context' => 'shop',
				'lazy'    => $index >= 4,
			)
		);
	}

	if ( '' === $cards ) {
		$cards = sprintf(
			'<div class="ap-empty"><span class="ap-empty__icon">%s</span>' .
			'<p class="ap-empty__title">%s</p><p class="ap-empty__text">%s</p></div>',
			assurance_icon( 'search', array( 'size' => 24 ) ),
			esc_html__( 'কোনো বই পাওয়া যায়নি', 'assurance' ),
			esc_html__( 'ফিল্টার পরিবর্তন করে আবার চেষ্টা করুন।', 'assurance' )
		);
	}

	wp_send_json_success(
		array(
			'html'    => $cards,
			'found'   => (int) $query->found_posts,
			'pages'   => (int) $query->max_num_pages,
			'paged'   => $paged,
			/* translators: %s: number of results in Bengali numerals. */
			'summary' => sprintf(
				esc_html__( '%s টি বই পাওয়া গেছে', 'assurance' ),
				assurance_bn_digits( $query->found_posts )
			),
		)
	);
}
assurance_ajax( 'shop_filter', 'assurance_ajax_shop_filter' );

/**
 * Expose the catalogue bounds to JS.
 *
 * @param array $data Existing data.
 * @return array
 */
function assurance_shop_js_data( $data ) {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$data['priceBounds'] = assurance_price_bounds();
	}

	return $data;
}
add_filter( 'assurance_js_data', 'assurance_shop_js_data' );
