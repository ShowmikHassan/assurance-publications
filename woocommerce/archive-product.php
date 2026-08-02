<?php
/**
 * Product archive.
 *
 * Two columns: filter sidebar, then the grid. Wrapped in Blocksy's
 * .ct-container so the width and gutters follow whatever the client sets
 * in the Customizer rather than a hardcoded max-width here.
 *
 * @package Assurance
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );
?>

<div class="ct-container ap-shop">

	<?php if ( apply_filters( 'assurance_show_shop_sidebar', true ) ) : ?>
		<?php assurance_shop_sidebar(); ?>
	<?php endif; ?>

	<div class="ap-shop__main">
		<header class="ap-shop__bar">
			<div class="ap-shop__bar-left">
				<button
					type="button"
					class="ap-btn ap-btn--outline ap-btn--sm ap-shop__filter-btn"
					data-ap-filters-open
					aria-expanded="false"
				>
					<?php assurance_the_icon( 'filter', array( 'size' => 16 ) ); ?>
					<?php esc_html_e( 'ফিল্টার', 'assurance' ); ?>
				</button>

				<p class="ap-shop__count" data-ap-result-count aria-live="polite">
					<?php
					global $wp_query;
					printf(
						/* translators: %s: number of results in Bengali numerals. */
						esc_html__( '%s টি বই পাওয়া গেছে', 'assurance' ),
						esc_html( assurance_bn_digits( (int) $wp_query->found_posts ) )
					);
					?>
				</p>
			</div>

			<?php woocommerce_catalog_ordering(); ?>
		</header>

		<?php if ( woocommerce_product_loop() ) : ?>

			<div class="ap-shop__grid" data-ap-grid>
				<?php
				woocommerce_product_loop_start();

				while ( have_posts() ) {
					the_post();

					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}

				woocommerce_product_loop_end();
				?>
			</div>

			<?php do_action( 'woocommerce_after_shop_loop' ); ?>

		<?php else : ?>

			<div class="ap-empty">
				<span class="ap-empty__icon"><?php assurance_the_icon( 'search', array( 'size' => 24 ) ); ?></span>
				<p class="ap-empty__title"><?php esc_html_e( 'কোনো বই পাওয়া যায়নি', 'assurance' ); ?></p>
				<p class="ap-empty__text"><?php esc_html_e( 'ফিল্টার পরিবর্তন করে আবার চেষ্টা করুন।', 'assurance' ); ?></p>
			</div>

		<?php endif; ?>
	</div>

	<div class="ap-shop__scrim" data-ap-filters-close hidden></div>
</div>

<?php
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
