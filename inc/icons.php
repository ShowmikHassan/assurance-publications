<?php
/**
 * Inline SVG icons.
 *
 * Inlined rather than served as a sprite file or icon font: the set is
 * small, it avoids a render-blocking request, and inline paths inherit
 * currentColor so a single icon works on paper, ink and accent grounds
 * without a second asset.
 *
 * All icons are drawn on a 24×24 grid with a 1.6 stroke so they sit
 * optically consistent next to Anek Bangla at body size.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon path data, keyed by name.
 *
 * @return array<string, array{d:string, fill?:bool, extra?:string}>
 */
function assurance_icon_paths() {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array(
		// Shopping bag — the add-to-cart affordance on every card.
		'bag'        => array(
			'd' => 'M6 8h12l-1 12H7L6 8Zm3 0V6a3 3 0 0 1 6 0v2',
		),
		'bag-filled' => array(
			'd'    => 'M6.2 7h11.6a1 1 0 0 1 1 1.08l-.92 11A1 1 0 0 1 16.88 20H7.12a1 1 0 0 1-1-.92l-.92-11A1 1 0 0 1 6.2 7Z',
			'fill' => true,
			'extra' => '<path d="M9 7V6a3 3 0 0 1 6 0v1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
		),
		'cart'       => array(
			'd' => 'M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.55L21 8H6M10 20.5h.01M17 20.5h.01',
		),
		'bolt'       => array(
			'd' => 'M13 3 5 13.5h5.5L11 21l8-10.5h-5.5L13 3Z',
		),
		'bookmark'   => array(
			'd' => 'M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1Z',
		),
		'bookmark-filled' => array(
			'd'    => 'M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1Z',
			'fill' => true,
		),
		'star'       => array(
			'd' => 'm12 3.6 2.6 5.28 5.83.85-4.22 4.11 1 5.81L12 16.9l-5.21 2.75 1-5.81-4.22-4.11 5.83-.85L12 3.6Z',
		),
		'star-filled' => array(
			'd'    => 'm12 3.6 2.6 5.28 5.83.85-4.22 4.11 1 5.81L12 16.9l-5.21 2.75 1-5.81-4.22-4.11 5.83-.85L12 3.6Z',
			'fill' => true,
		),
		'close'      => array(
			'd' => 'M6 6l12 12M18 6 6 18',
		),
		'chevron-down' => array(
			'd' => 'm6 9 6 6 6-6',
		),
		'chevron-right' => array(
			'd' => 'm9 6 6 6-6 6',
		),
		'chevron-left' => array(
			'd' => 'm15 6-6 6 6 6',
		),
		'arrow-right' => array(
			'd' => 'M4 12h15m-6-6 6 6-6 6',
		),
		'plus'       => array(
			'd' => 'M12 5v14M5 12h14',
		),
		'minus'      => array(
			'd' => 'M5 12h14',
		),
		'trash'      => array(
			'd' => 'M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0-.8 12.1a1 1 0 0 1-1 .9H7.8a1 1 0 0 1-1-.9L6 7M10 11v5M14 11v5',
		),
		'search'     => array(
			'd' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35',
		),
		'filter'     => array(
			'd' => 'M4 6h16M7 12h10M10 18h4',
		),
		'check'      => array(
			'd' => 'm5 13 4.5 4.5L19 7',
		),
		'shield'     => array(
			'd' => 'M12 3.2 5 6v6c0 4.2 2.9 7.5 7 8.8 4.1-1.3 7-4.6 7-8.8V6l-7-2.8Zm-2.4 8.6 1.9 1.9 3.6-3.7',
		),
		'truck'      => array(
			'd' => 'M3 7a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v9H3V7Zm11 3h3.6a1 1 0 0 1 .84.46L21 14v2h-7v-6ZM7 19.5A1.75 1.75 0 1 0 7 16a1.75 1.75 0 0 0 0 3.5Zm10 0a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Z',
		),
		'parcel'     => array(
			'd'     => 'M12 3 3.8 7.2v9.6L12 21l8.2-4.2V7.2L12 3Zm0 0v18M3.8 7.2 12 11.4l8.2-4.2',
			'extra' => '<path d="M12 11.4v-2.6l5.6-2.9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
		),
		'book'       => array(
			'd' => 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Zm2 14h13M8 7h7',
		),
		'rotate'     => array(
			'd' => 'M3.5 12a8.5 8.5 0 1 1 2.6 6.1M3.5 18.5V13H9',
		),
		'phone'      => array(
			'd' => 'M6.2 3.5h3l1.5 3.8-2 1.4a12 12 0 0 0 5.6 5.6l1.4-2 3.8 1.5v3a1.7 1.7 0 0 1-1.9 1.7A15.5 15.5 0 0 1 4.5 5.4 1.7 1.7 0 0 1 6.2 3.5Z',
		),
		'file-text'  => array(
			'd' => 'M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7l-4-4Zm0 0v4h4M9 12h6M9 16h4',
		),
		'menu'       => array(
			'd' => 'M4 7h16M4 12h16M4 17h16',
		),
		'grid'       => array(
			'd' => 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z',
		),
		'zoom-in'    => array(
			'd' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35M11 8v6M8 11h6',
		),
		'info'       => array(
			'd' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13h.01M11 12h1v4h1',
		),
		'tag'        => array(
			'd' => 'M3.5 11.2V4.5a1 1 0 0 1 1-1h6.7a1 1 0 0 1 .7.3l8 8a1 1 0 0 1 0 1.4l-6.7 6.7a1 1 0 0 1-1.4 0l-8-8a1 1 0 0 1-.3-.7ZM8 8h.01',
		),
		'mail'       => array(
			'd' => 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 0 8 7 8-7',
		),
		'map-pin'    => array(
			'd' => 'M12 21.5S5 15.4 5 10a7 7 0 1 1 14 0c0 5.4-7 11.5-7 11.5Z',
			'extra' => '<circle cx="12" cy="10" r="2.4" fill="none" stroke="currentColor" stroke-width="1.6"/>',
		),
		'globe'      => array(
			'd'     => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
			'extra' => '<path d="M3 12h18M12 3c2.4 2.5 3.6 5.6 3.6 9s-1.2 6.5-3.6 9c-2.4-2.5-3.6-5.6-3.6-9S9.6 5.5 12 3Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
		),
		'clock'      => array(
			'd' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-14v5l3.5 2',
		),
		'send'       => array(
			'd' => 'm21 3-9.5 9.5M21 3 14.5 21l-3-7.5L4 10.5 21 3Z',
		),
		'users'      => array(
			'd' => 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 9v-1.2A4.8 4.8 0 0 1 7.8 14h2.4a4.8 4.8 0 0 1 4.8 4.8V20',
			'extra' => '<path d="M15.5 4.3A3.5 3.5 0 0 1 17 11m1.4 3.2A4.8 4.8 0 0 1 21 18.8V20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
		),
		'target'     => array(
			'd' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0-3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
		),
		'award'      => array(
			'd' => 'M12 14.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11Zm-3.4-.6L7 21l5-2.5L17 21l-1.6-7.1',
		),
		'heart'      => array(
			'd' => 'M12 20.2s-7.4-4.4-9.6-9A5.4 5.4 0 0 1 12 6.3a5.4 5.4 0 0 1 9.6 4.9c-2.2 4.6-9.6 9-9.6 9Z',
		),
		'user'       => array(
			'd' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9v-.6A5.4 5.4 0 0 1 10.4 15h3.2A5.4 5.4 0 0 1 19 20.4v.6',
		),
		// Brand marks — drawn filled, so they read correctly at small sizes.
		'facebook'   => array(
			'd'    => 'M13.5 21v-7.5h2.5l.4-2.9h-2.9V8.78c0-.84.23-1.41 1.44-1.41h1.54V4.79c-.27-.04-1.2-.12-2.28-.12-2.26 0-3.8 1.38-3.8 3.91v2.02H8v2.9h2.4V21h3.1Z',
			'fill' => true,
		),
		'facebook-group' => array(
			'd'    => 'M8.5 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 .5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM2.5 19v-1.1A4.4 4.4 0 0 1 6.9 13.5h3.2a4.4 4.4 0 0 1 4.4 4.4V19h-12Zm14-5.5h-1.2a5.9 5.9 0 0 1 1.4 3.9V19h5v-1.1a4.4 4.4 0 0 0-4.4-4.4Z',
			'fill' => true,
		),
		'whatsapp'   => array(
			'd'    => 'M12.04 2.5A9.46 9.46 0 0 0 2.56 12c0 1.67.44 3.3 1.27 4.74L2.5 21.5l4.9-1.28a9.44 9.44 0 0 0 4.64 1.19c5.22 0 9.47-4.25 9.47-9.48a9.4 9.4 0 0 0-2.78-6.7 9.4 9.4 0 0 0-6.69-2.73Zm0 17.03a7.87 7.87 0 0 1-4.01-1.1l-.29-.17-2.98.78.8-2.91-.19-.3A7.85 7.85 0 0 1 4.17 12c0-4.34 3.53-7.87 7.88-7.87 2.1 0 4.08.82 5.57 2.31a7.82 7.82 0 0 1 2.3 5.57c0 4.34-3.53 7.87-7.88 7.87Zm4.32-5.9c-.24-.12-1.4-.69-1.61-.77-.22-.08-.38-.12-.53.12-.16.24-.61.77-.75.93-.14.16-.28.18-.51.06-.24-.12-1-.37-1.9-1.18-.7-.63-1.18-1.4-1.32-1.63-.14-.24-.01-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.53-1.28-.73-1.75-.19-.46-.39-.4-.53-.4h-.45c-.16 0-.4.06-.61.3-.21.24-.8.78-.8 1.9s.82 2.21.94 2.36c.12.16 1.62 2.47 3.92 3.46.55.24.97.38 1.31.48.55.18 1.05.15 1.44.9.44-.07 1.35-.55 1.54-1.09.19-.53.19-.99.13-1.09-.06-.1-.21-.16-.45-.28Z',
			'fill' => true,
		),
		'cash'       => array(
			'd'     => 'M2.5 6.5h19v11h-19v-11Z',
			'extra' => '<circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5.5 6.5v11M18.5 6.5v11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
		),
	);

	return $icons;
}

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon key.
 * @param array  $args  {
 *     @type int    $size  Pixel size. Default 20.
 *     @type string $class Extra classes.
 *     @type string $label Accessible label. When empty the icon is
 *                         aria-hidden, which is correct whenever the icon
 *                         sits beside visible text.
 * }
 * @return string SVG markup, safe to echo.
 */
function assurance_icon( $name, $args = array() ) {
	$icons = assurance_icon_paths();

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'size'  => 20,
			'class' => '',
			'label' => '',
		)
	);

	$icon   = $icons[ $name ];
	$filled = ! empty( $icon['fill'] );

	$a11y = $args['label']
		? sprintf( 'role="img" aria-label="%s"', esc_attr( $args['label'] ) )
		: 'aria-hidden="true" focusable="false"';

	return sprintf(
		'<svg class="ap-icon ap-icon--%1$s %2$s" width="%3$d" height="%3$d" viewBox="0 0 24 24" ' .
		'fill="%4$s" stroke="%5$s" stroke-width="1.6" stroke-linecap="round" ' .
		'stroke-linejoin="round" %6$s><path d="%7$s"/>%8$s</svg>',
		esc_attr( $name ),
		esc_attr( $args['class'] ),
		absint( $args['size'] ),
		$filled ? 'currentColor' : 'none',
		$filled ? 'none' : 'currentColor',
		$a11y, // Built from esc_attr above.
		esc_attr( $icon['d'] ),
		isset( $icon['extra'] ) ? $icon['extra'] : ''
	);
}

/**
 * Echo an inline SVG icon.
 *
 * @param string $name Icon key.
 * @param array  $args See assurance_icon().
 */
function assurance_the_icon( $name, $args = array() ) {
	echo assurance_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from a fixed path table with escaped attributes.
}

/**
 * Star rating markup.
 *
 * Rendered as filled/empty star pairs with a clipped overlay for the
 * fractional star, rather than WooCommerce's default width-percentage
 * approach, so the shape stays crisp and the colour is themeable.
 *
 * @param float $rating Average rating, 0–5.
 * @param int   $count  Review count.
 * @return string
 */
function assurance_rating_html( $rating, $count = 0 ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$pct    = ( $rating / 5 ) * 100;

	$stars = '';
	for ( $i = 0; $i < 5; $i++ ) {
		$stars .= assurance_icon( 'star-filled', array( 'size' => 13 ) );
	}

	return sprintf(
		'<span class="ap-rating" role="img" aria-label="%1$s">
			<span class="ap-rating__track">%2$s</span>
			<span class="ap-rating__fill" style="width:%3$s%%">%2$s</span>
			%4$s
		</span>',
		esc_attr(
			$count > 0
				/* translators: 1: rating out of 5, 2: number of reviews. */
				? sprintf( __( '৫-এর মধ্যে %1$s, %2$s টি রিভিউ', 'assurance' ), assurance_bn_digits( number_format( $rating, 1 ) ), assurance_bn_digits( $count ) )
				/* translators: %s: rating out of 5. */
				: sprintf( __( '৫-এর মধ্যে %s', 'assurance' ), assurance_bn_digits( number_format( $rating, 1 ) ) )
		),
		$stars, // Built from assurance_icon().
		esc_attr( (string) $pct ),
		$count > 0
			? '<span class="ap-rating__count">(' . esc_html( assurance_bn_digits( $count ) ) . ')</span>'
			: ''
	);
}
