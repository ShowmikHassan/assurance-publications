<?php
/**
 * Checkout form.
 *
 * Two columns: the customer's details on the left, a sticky order summary
 * on the right. Collapses to a single column below 992px with the summary
 * first, so a mobile shopper confirms what they are buying before typing.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Checkout $checkout Supplied by wc_get_template(). */
$checkout = isset( $checkout ) ? $checkout : WC()->checkout();

do_action( 'woocommerce_before_checkout_form', $checkout );

// Guest checkout disabled and the visitor is logged out — WooCommerce
// handles the messaging, we just stop here.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<form
	name="checkout"
	method="post"
	class="checkout woocommerce-checkout ap-checkout"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
	enctype="multipart/form-data"
	novalidate
>
	<div class="ap-checkout__grid">

		<div class="ap-checkout__main">
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<section class="ap-panel" id="customer_details">
					<header class="ap-panel__head">
						<h2 class="ap-panel__title"><?php esc_html_e( 'ডেলিভারির তথ্য', 'assurance' ); ?></h2>
					</header>

					<div class="ap-panel__body">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
				</section>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>

			<?php
			/*
			 * Order note.
			 *
			 * Rendered here as a collapsible rather than inside the address
			 * panel: it is optional, most shoppers skip it, and an always-open
			 * textarea reads as another required field on a form we have
			 * deliberately kept short.
			 */
			$note_field = WC()->checkout->get_checkout_fields( 'order' );

			if ( ! empty( $note_field['order_comments'] ) ) :
				?>
				<section class="ap-panel ap-note">
					<div class="ap-note__toggle">
						<input
							type="checkbox"
							id="ap-note-toggle"
							class="ap-note__check"
							data-ap-note-toggle
						/>
						<label for="ap-note-toggle" class="ap-note__label">
							<span class="ap-note__box" aria-hidden="true">
								<?php assurance_the_icon( 'check', array( 'size' => 13 ) ); ?>
							</span>
							<span><?php esc_html_e( 'অর্ডার সম্পর্কে নোট যোগ করুন', 'assurance' ); ?></span>
						</label>
					</div>

					<div class="ap-note__field" data-ap-note-field hidden>
						<?php
						woocommerce_form_field(
							'order_comments',
							$note_field['order_comments'],
							$checkout->get_value( 'order_comments' )
						);
						?>
					</div>
				</section>
			<?php endif; ?>

			<?php
			/*
			 * Payment block.
			 *
			 * Unhooked from woocommerce_checkout_order_review in
			 * inc/checkout.php and emitted here instead, so the method
			 * tiles, the terms checkbox and the order button sit with the
			 * form the shopper is completing rather than inside the
			 * read-only summary — and stay under the order note when the
			 * columns stack on a phone.
			 */
			woocommerce_checkout_payment();
			?>

			<?php
			/*
			 * Reassurance strip.
			 *
			 * Inside the left column so it always sits directly under the
			 * payment card on desktop. Ordered to the very bottom once the
			 * columns stack — see the display:contents rule in the
			 * stylesheet.
			 */
			?>
			<ul class="ap-assure ap-assure--inline">
				<li>
					<span class="ap-assure__icon"><?php assurance_the_icon( 'shield', array( 'size' => 16 ) ); ?></span>
					<?php esc_html_e( 'নিরাপদ পেমেন্ট', 'assurance' ); ?>
				</li>
				<li>
					<span class="ap-assure__icon"><?php assurance_the_icon( 'truck', array( 'size' => 16 ) ); ?></span>
					<?php esc_html_e( 'দ্রুত ডেলিভারি', 'assurance' ); ?>
				</li>
				<li>
					<span class="ap-assure__icon"><?php assurance_the_icon( 'bag', array( 'size' => 16 ) ); ?></span>
					<?php esc_html_e( 'সহজ রিটার্ন', 'assurance' ); ?>
				</li>
				<li>
					<span class="ap-assure__icon"><?php assurance_the_icon( 'book', array( 'size' => 16 ) ); ?></span>
					<?php esc_html_e( 'শতভাগ আসল বই', 'assurance' ); ?>
				</li>
			</ul>

		</div>

		<aside class="ap-checkout__side">
			<div class="ap-checkout__sticky">
				<section class="ap-panel ap-summary">
					<header class="ap-panel__head">
						<h2 class="ap-panel__title"><?php esc_html_e( 'আপনার অর্ডার', 'assurance' ); ?></h2>
					</header>

					<div class="ap-panel__body">
						<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
						<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

						<div id="order_review" class="woocommerce-checkout-review-order">
							<?php do_action( 'woocommerce_checkout_order_review' ); ?>
						</div>

						<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

						<?php
						/*
						 * Breakdown + order button. Split out of the payment
						 * block server-side (see payment.php) so it sits under
						 * the total; refreshed by its own AJAX fragment.
						 */
						assurance_render_order_actions();
						?>
					</div>
				</section>
			</div>
		</aside>


	</div>
</form>

<div class="ap-modal-root" data-ap-modal-root hidden>
	<div class="ap-modal__scrim" data-ap-modal-close></div>
	<div class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="ap-modal-title">
		<header class="ap-modal__head">
			<h2 class="ap-modal__title" id="ap-modal-title" data-ap-modal-title></h2>
			<button type="button" class="ap-icon-btn ap-icon-btn--bare" data-ap-modal-close aria-label="<?php esc_attr_e( 'বন্ধ করুন', 'assurance' ); ?>">
				<?php assurance_the_icon( 'close', array( 'size' => 18 ) ); ?>
			</button>
		</header>
		<div class="ap-modal__body" data-ap-modal-body></div>
	</div>
</div>

<div class="ap-paybar" data-ap-paybar>
	<span class="ap-paybar__label" data-ap-paybar-label><?php esc_html_e( 'সর্বমোট', 'assurance' ); ?></span>
	<strong class="ap-paybar__amount" data-ap-paybar-amount>—</strong>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
