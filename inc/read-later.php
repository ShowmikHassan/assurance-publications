<?php
/**
 * "একটু পরে দেখুন" — read-later list.
 *
 * Storage strategy
 * ----------------
 * Guests are the overwhelming majority on this store, and forcing a login
 * to bookmark a book would kill the feature. So the list is client-side in
 * localStorage by default, with no account required.
 *
 * When a user *is* logged in the same list is mirrored to user meta, so it
 * survives a device change. On login the two are merged rather than one
 * overwriting the other — a shopper who bookmarked three books logged-out
 * and two logged-in on their phone expects five, not whichever list synced
 * last.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

const ASSURANCE_READ_LATER_META = '_assurance_read_later';

/**
 * Cap on stored items.
 *
 * Bounded so a scripted caller cannot inflate a user's meta row without
 * limit, and so the sync payload stays small.
 */
const ASSURANCE_READ_LATER_MAX = 100;

/**
 * The current user's stored list.
 *
 * @return int[] Product IDs.
 */
function assurance_get_read_later() {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$stored = get_user_meta( get_current_user_id(), ASSURANCE_READ_LATER_META, true );

	if ( ! is_array( $stored ) ) {
		return array();
	}

	return array_values( array_filter( array_map( 'absint', $stored ) ) );
}

/**
 * Persist a list to user meta.
 *
 * @param int[] $ids Product IDs.
 * @return int[] The normalised list actually stored.
 */
function assurance_save_read_later( $ids ) {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$ids = array_slice(
		array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) ),
		0,
		ASSURANCE_READ_LATER_MAX
	);

	update_user_meta( get_current_user_id(), ASSURANCE_READ_LATER_META, $ids );

	return $ids;
}

/**
 * Toggle a product in the list, and merge in whatever the client holds.
 *
 * The client sends its localStorage list alongside the toggle so that a
 * newly-logged-in user's guest bookmarks are absorbed on their first
 * interaction, without a separate sync endpoint.
 */
function assurance_ajax_toggle_read_later() {
	assurance_verify_request();

	$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$active     = ! empty( $_POST['active'] );

	if ( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_visible() ) {
			wp_send_json_error(
				array(
					'code'    => 'not_found',
					'message' => __( 'পণ্যটি পাওয়া যায়নি', 'assurance' ),
				),
				404
			);
		}
	}

	// Guests get an acknowledgement and keep their list locally; there is
	// nothing to persist server-side and no error to report.
	if ( ! is_user_logged_in() ) {
		wp_send_json_success(
			array(
				'synced' => false,
				'ids'    => array(),
			)
		);
	}

	$ids = assurance_get_read_later();

	// Merge the client's list first so guest-era bookmarks are not lost.
	if ( isset( $_POST['local'] ) && is_array( $_POST['local'] ) ) {
		$local = array_map( 'absint', wp_unslash( $_POST['local'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint is the sanitiser.
		$ids   = array_unique( array_merge( $ids, array_filter( $local ) ) );
	}

	if ( $product_id ) {
		if ( $active ) {
			$ids[] = $product_id;
		} else {
			$ids = array_diff( $ids, array( $product_id ) );
		}
	}

	$ids = assurance_save_read_later( $ids );

	wp_send_json_success(
		array(
			'synced' => true,
			'ids'    => $ids,
		)
	);
}
assurance_ajax( 'toggle_read_later', 'assurance_ajax_toggle_read_later' );

/**
 * Render the saved list as cards, for the account page or a shortcode.
 *
 * @param int[] $ids Product IDs; falls back to the stored list.
 * @return string
 */
function assurance_read_later_grid( $ids = null ) {
	$ids = null === $ids ? assurance_get_read_later() : array_map( 'absint', (array) $ids );

	if ( empty( $ids ) ) {
		return sprintf(
			'<div class="ap-empty"><span class="ap-empty__icon">%s</span>' .
			'<p class="ap-empty__title">%s</p><p class="ap-empty__text">%s</p></div>',
			assurance_icon( 'bookmark', array( 'size' => 24 ) ),
			esc_html__( 'তালিকা খালি', 'assurance' ),
			esc_html__( 'পছন্দের বই পরে দেখার জন্য বুকমার্ক করে রাখুন।', 'assurance' )
		);
	}

	return assurance_product_grid( $ids, array( 'columns' => 4 ) );
}

/**
 * [assurance_read_later] — the saved list anywhere on the site.
 *
 * @return string
 */
function assurance_read_later_shortcode() {
	return assurance_read_later_grid();
}
add_shortcode( 'assurance_read_later', 'assurance_read_later_shortcode' );

/**
 * Expose the server-side list to JS so a logged-in user's saved state
 * renders correctly on first paint rather than flashing in after hydration.
 *
 * @param array $data Existing JS data.
 * @return array
 */
function assurance_read_later_js_data( $data ) {
	$data['readLater'] = assurance_get_read_later();
	$data['loggedIn']  = is_user_logged_in();

	return $data;
}
add_filter( 'assurance_js_data', 'assurance_read_later_js_data' );
