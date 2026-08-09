<?php
/**
 * Bangladesh courier shipping.
 *
 * One custom shipping method, added to one zone covering Bangladesh. The
 * method itself decides Dhaka vs. outside-Dhaka and the per-book cost at
 * calculate_shipping() time, reading the real destination state and cart
 * contents — it does not depend on WooCommerce's per-state zone-location
 * matching at all, which is what a previous version of this file did and
 * which produced malformed location rows and PHP warnings when two zones
 * both partially matched the same address.
 *
 * Pricing (client-specified):
 *   - Free when cart subtotal >= ৳2000.
 *   - Otherwise: 1 book = base rate. 2+ books = base + 10 × qty.
 *     Base is ৳70 inside Dhaka, ৳100 everywhere else in Bangladesh.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

const ASSURANCE_RATE_INSIDE_DHAKA  = 70;
const ASSURANCE_RATE_OUTSIDE_DHAKA = 100;
const ASSURANCE_FREE_SHIPPING_MIN  = 2000;

/**
 * The tiered courier cost for a given district + quantity.
 *
 * Exposed as a standalone function (not just inside the shipping method)
 * so the checkout's "pay the courier charge via bKash for COD" flow can
 * compute the exact same number without re-deriving the formula.
 *
 * @param bool $inside_dhaka Whether the destination is Dhaka district.
 * @param int  $qty          Total item quantity in the cart.
 * @return float
 */
function assurance_courier_cost( $inside_dhaka, $qty ) {
	$base = $inside_dhaka ? ASSURANCE_RATE_INSIDE_DHAKA : ASSURANCE_RATE_OUTSIDE_DHAKA;
	$qty  = max( 1, (int) $qty );

	if ( $qty <= 1 ) {
		return (float) $base;
	}

	return (float) ( $base + 10 * $qty );
}

/**
 * The courier fee for the current cart/customer, or 0 if free shipping
 * already applies. Used by the checkout's COD-prepays-courier-via-bKash
 * step, so that step and the real shipping line always agree.
 *
 * @return float
 */
function assurance_current_courier_fee() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	if ( WC()->cart->get_displayed_subtotal() >= ASSURANCE_FREE_SHIPPING_MIN ) {
		return 0.0;
	}

	// A Free Shipping-flagged product in the cart (see inc/product-flags.php)
	// makes the courier fee zero too, same as clearing the subtotal
	// threshold above — otherwise the COD-with-bKash-prepay flow would ask
	// the shopper to pay a courier fee for a delivery that's supposed to be
	// free.
	if ( function_exists( 'assurance_cart_has_free_shipping_item' ) && assurance_cart_has_free_shipping_item() ) {
		return 0.0;
	}

	$state = WC()->customer ? WC()->customer->get_shipping_state() : '';
	$qty   = WC()->cart->get_cart_contents_count();

	return assurance_courier_cost( assurance_is_inside_dhaka( $state ), $qty );
}

/**
 * The shipping method itself.
 *
 * Registered only when WooCommerce has loaded its base class.
 */
function assurance_register_shipping_method_class() {
	if ( class_exists( 'Assurance_Courier_Shipping_Method' ) || ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}

	/**
	 * @package Assurance
	 */
	class Assurance_Courier_Shipping_Method extends WC_Shipping_Method {

		/**
		 * @param int $instance_id Zone instance ID.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'assurance_courier';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'কুরিয়ার চার্জ (Assurance)', 'assurance' );
			$this->method_description = __( 'ঢাকা/ঢাকার বাইরে এবং বইয়ের সংখ্যা অনুযায়ী স্বয়ংক্রিয় ডেলিভারি চার্জ, নির্দিষ্ট সাবটোটালের উপরে ফ্রি।', 'assurance' );
			$this->supports            = array( 'shipping-zones', 'instance-settings' );

			$this->init();
		}

		/**
		 * Load settings and instance form fields.
		 */
		public function init() {
			$this->instance_form_fields = array(
				'title' => array(
					'title'   => __( 'Title', 'assurance' ),
					'type'    => 'text',
					'default' => __( 'হোম ডেলিভারি', 'assurance' ),
				),
			);

			$this->title = $this->get_option( 'title', __( 'হোম ডেলিভারি', 'assurance' ) );

			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		/**
		 * The one calculation this whole file exists for.
		 *
		 * @param array $package Shipping package.
		 */
		public function calculate_shipping( $package = array() ) {
			$subtotal   = 0.0;
			$free_item  = false;

			foreach ( $package['contents'] as $item ) {
				$subtotal += (float) $item['line_subtotal'];

				// A single Free Shipping-flagged product (see
				// inc/product-flags.php) makes the whole package free,
				// same as clearing the subtotal threshold below — checked
				// here, at the source, rather than with a
				// woocommerce_package_rates filter, so the free rate also
				// gets the right label instead of "ঢাকার ভিতরে ডেলিভারি"
				// showing ৳0.
				if ( ! $free_item && isset( $item['product_id'] ) && 'yes' === get_post_meta( $item['product_id'], '_is_free_shipping_item', true ) ) {
					$free_item = true;
				}
			}

			if ( $subtotal >= ASSURANCE_FREE_SHIPPING_MIN || $free_item ) {
				$this->add_rate(
					array(
						'id'    => $this->get_rate_id(),
						'label' => __( 'ফ্রি ডেলিভারি', 'assurance' ),
						'cost'  => 0,
					)
				);
				return;
			}

			$qty          = 0;
			$inside_dhaka = assurance_is_inside_dhaka( isset( $package['destination']['state'] ) ? $package['destination']['state'] : '' );

			foreach ( $package['contents'] as $item ) {
				$qty += (int) $item['quantity'];
			}

			$cost  = assurance_courier_cost( $inside_dhaka, $qty );
			$label = $inside_dhaka
				? __( 'ঢাকার ভিতরে ডেলিভারি', 'assurance' )
				: __( 'ঢাকার বাইরে ডেলিভারি', 'assurance' );

			$this->add_rate(
				array(
					'id'    => $this->get_rate_id(),
					'label' => $label,
					'cost'  => $cost,
				)
			);
		}
	}
}
add_action( 'woocommerce_shipping_init', 'assurance_register_shipping_method_class' );

/**
 * Register the method with WooCommerce.
 *
 * @param string[] $methods Existing methods.
 * @return string[]
 */
function assurance_register_shipping_method( $methods ) {
	$methods['assurance_courier'] = 'Assurance_Courier_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'assurance_register_shipping_method' );

/**
 * Which delivery band the customer currently falls into — for display only
 * (the cart/checkout shipping line label, and the order meta admin sees).
 * The real pricing decision lives in calculate_shipping() above.
 *
 * @return string 'inside' | 'outside' | 'unknown'
 */
function assurance_delivery_band() {
	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		return 'unknown';
	}

	$state = WC()->customer->get_shipping_state();

	if ( '' === $state ) {
		return 'unknown';
	}

	return assurance_is_inside_dhaka( $state ) ? 'inside' : 'outside';
}

/**
 * @return string
 */
function assurance_delivery_band_label() {
	switch ( assurance_delivery_band() ) {
		case 'inside':
			return __( 'ঢাকার ভিতরে', 'assurance' );
		case 'outside':
			return __( 'ঢাকার বাইরে', 'assurance' );
		default:
			return __( 'জেলা নির্বাচন করুন', 'assurance' );
	}
}

/* ==========================================================================
   One-click zone setup
   ========================================================================== */

/**
 * @param array $settings Existing settings fields.
 * @return array
 */
function assurance_shipping_settings( $settings ) {
	$configured = assurance_zone_configured();

	$settings[] = array(
		'title' => __( 'Assurance — courier charge', 'assurance' ),
		'type'  => 'title',
		'desc'  => $configured
			? __( 'The Assurance courier method is active. Edit its title in the zone above; the rate formula itself is fixed by the store\'s pricing rules.', 'assurance' )
			: sprintf(
				/* translators: 1: inside rate, 2: outside rate, 3: free-shipping threshold. */
				__( 'Create one shipping zone covering Bangladesh with the Assurance courier method: ৳%1$s inside Dhaka, ৳%2$s outside, +৳10 per extra book, free at ৳%3$s+.', 'assurance' ),
				ASSURANCE_RATE_INSIDE_DHAKA,
				ASSURANCE_RATE_OUTSIDE_DHAKA,
				ASSURANCE_FREE_SHIPPING_MIN
			),
		'id'    => 'assurance_shipping_setup_title',
	);

	if ( ! $configured ) {
		$settings[] = array(
			'title' => __( 'Delivery method', 'assurance' ),
			'type'  => 'assurance_shipping_setup',
			'id'    => 'assurance_shipping_setup',
		);
	}

	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'assurance_shipping_setup_title',
	);

	return $settings;
}
add_filter( 'woocommerce_shipping_settings', 'assurance_shipping_settings' );

/**
 * Render the setup button.
 */
function assurance_shipping_setup_field() {
	$url = wp_nonce_url(
		add_query_arg( 'assurance_setup_shipping', '1' ),
		'assurance_setup_shipping'
	);
	?>
	<tr valign="top">
		<th scope="row" class="titledesc"><?php esc_html_e( 'Delivery method', 'assurance' ); ?></th>
		<td class="forminp">
			<a href="<?php echo esc_url( $url ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Set up Bangladesh courier charge', 'assurance' ); ?>
			</a>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_admin_field_assurance_shipping_setup', 'assurance_shipping_setup_field' );

/**
 * Whether a zone already has our method attached.
 *
 * @return bool
 */
function assurance_zone_configured() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return false;
	}

	foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
		$zone = new WC_Shipping_Zone( $zone_data['id'] );

		foreach ( $zone->get_shipping_methods() as $method ) {
			if ( 'assurance_courier' === $method->id ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Create one zone (Bangladesh, country-level) with our method attached.
 *
 * Guarded by capability + nonce, and by assurance_zone_configured() so it
 * can only ever create the setup once — running it twice is a no-op.
 */
function assurance_maybe_setup_shipping() {
	if ( ! isset( $_GET['assurance_setup_shipping'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to change shipping settings.', 'assurance' ) );
	}

	check_admin_referer( 'assurance_setup_shipping' );

	if ( assurance_zone_configured() ) {
		wp_safe_redirect( remove_query_arg( array( 'assurance_setup_shipping', '_wpnonce' ) ) );
		exit;
	}

	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( __( 'Bangladesh', 'assurance' ) );
	$zone->set_locations( array( array( 'code' => 'BD', 'type' => 'country' ) ) );
	$zone->save();
	$zone->add_shipping_method( 'assurance_courier' );

	wp_safe_redirect(
		add_query_arg(
			'assurance_shipping_created',
			'1',
			remove_query_arg( array( 'assurance_setup_shipping', '_wpnonce' ) )
		)
	);
	exit;
}
add_action( 'admin_init', 'assurance_maybe_setup_shipping' );

/**
 * Confirmation notice.
 */
function assurance_shipping_created_notice() {
	if ( empty( $_GET['assurance_shipping_created'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice.
		return;
	}

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html__( 'Bangladesh delivery zone created with the Assurance courier method.', 'assurance' )
	);
}
add_action( 'admin_notices', 'assurance_shipping_created_notice' );
