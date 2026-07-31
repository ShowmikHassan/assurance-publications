<?php
/**
 * Bangladesh districts.
 *
 * WooCommerce already ships all 64 districts as states for country BD
 * (BD-01 … BD-64), and this store is configured to BD. So the checkout
 * "District" field is WooCommerce's native state field, not a bolt-on
 * custom field. That matters well beyond convenience:
 *
 *   - Shipping zones can target districts natively (Inside/Outside Dhaka).
 *   - Tax, courier and analytics integrations read a standard address.
 *   - Orders export with a real state code instead of free text.
 *
 * This file only relabels those states into Bangla. The stored value is
 * always the ISO-ish code, so nothing downstream depends on the label.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * District code → Bangla name.
 *
 * Keys match WooCommerce's BD state codes exactly; the labels are the
 * conventional Bangla spellings used on Bangladeshi delivery forms.
 *
 * @return array<string, string>
 */
function assurance_districts() {
	static $districts = null;

	if ( null !== $districts ) {
		return $districts;
	}

	$districts = array(
		'BD-01' => 'বান্দরবান',
		'BD-02' => 'বরগুনা',
		'BD-03' => 'বগুড়া',
		'BD-04' => 'ব্রাহ্মণবাড়িয়া',
		'BD-05' => 'বাগেরহাট',
		'BD-06' => 'বরিশাল',
		'BD-07' => 'ভোলা',
		'BD-08' => 'কুমিল্লা',
		'BD-09' => 'চাঁদপুর',
		'BD-10' => 'চট্টগ্রাম',
		'BD-11' => 'কক্সবাজার',
		'BD-12' => 'চুয়াডাঙ্গা',
		'BD-13' => 'ঢাকা',
		'BD-14' => 'দিনাজপুর',
		'BD-15' => 'ফরিদপুর',
		'BD-16' => 'ফেনী',
		'BD-17' => 'গোপালগঞ্জ',
		'BD-18' => 'গাজীপুর',
		'BD-19' => 'গাইবান্ধা',
		'BD-20' => 'হবিগঞ্জ',
		'BD-21' => 'জামালপুর',
		'BD-22' => 'যশোর',
		'BD-23' => 'ঝিনাইদহ',
		'BD-24' => 'জয়পুরহাট',
		'BD-25' => 'ঝালকাঠি',
		'BD-26' => 'কিশোরগঞ্জ',
		'BD-27' => 'খুলনা',
		'BD-28' => 'কুড়িগ্রাম',
		'BD-29' => 'খাগড়াছড়ি',
		'BD-30' => 'কুষ্টিয়া',
		'BD-31' => 'লক্ষ্মীপুর',
		'BD-32' => 'লালমনিরহাট',
		'BD-33' => 'মানিকগঞ্জ',
		'BD-34' => 'ময়মনসিংহ',
		'BD-35' => 'মুন্সিগঞ্জ',
		'BD-36' => 'মাদারীপুর',
		'BD-37' => 'মাগুরা',
		'BD-38' => 'মৌলভীবাজার',
		'BD-39' => 'মেহেরপুর',
		'BD-40' => 'নারায়ণগঞ্জ',
		'BD-41' => 'নেত্রকোণা',
		'BD-42' => 'নরসিংদী',
		'BD-43' => 'নড়াইল',
		'BD-44' => 'নাটোর',
		'BD-45' => 'চাঁপাইনবাবগঞ্জ',
		'BD-46' => 'নীলফামারী',
		'BD-47' => 'নোয়াখালী',
		'BD-48' => 'নওগাঁ',
		'BD-49' => 'পাবনা',
		'BD-50' => 'পিরোজপুর',
		'BD-51' => 'পটুয়াখালী',
		'BD-52' => 'পঞ্চগড়',
		'BD-53' => 'রাজবাড়ী',
		'BD-54' => 'রাজশাহী',
		'BD-55' => 'রংপুর',
		'BD-56' => 'রাঙ্গামাটি',
		'BD-57' => 'শেরপুর',
		'BD-58' => 'সাতক্ষীরা',
		'BD-59' => 'সিরাজগঞ্জ',
		'BD-60' => 'সিলেট',
		'BD-61' => 'সুনামগঞ্জ',
		'BD-62' => 'শরীয়তপুর',
		'BD-63' => 'টাঙ্গাইল',
		'BD-64' => 'ঠাকুরগাঁও',
	);

	return apply_filters( 'assurance_districts', $districts );
}

/**
 * District codes that count as "Inside Dhaka" for shipping.
 *
 * Defaults to Dhaka district only. Some sellers treat Gazipur and
 * Narayanganj as inside-Dhaka because the couriers charge the metro rate
 * there; that is a commercial decision, so it is a filter rather than a
 * hard-coded list.
 *
 * @return string[]
 */
function assurance_inside_dhaka_codes() {
	return (array) apply_filters( 'assurance_inside_dhaka_codes', array( 'BD-13' ) );
}

/**
 * Whether a district code is inside the Dhaka delivery band.
 *
 * @param string $code District/state code.
 * @return bool
 */
function assurance_is_inside_dhaka( $code ) {
	return in_array( (string) $code, assurance_inside_dhaka_codes(), true );
}

/**
 * Replace WooCommerce's English BD state labels with Bangla ones.
 *
 * Front-end only by default. Order exports, courier plugins and CSV
 * reports run in admin context and are safer reading the English labels
 * WooCommerce ships; the stored value is the code either way, so both
 * views describe the same address.
 *
 * @param array $states All country states.
 * @return array
 */
function assurance_localise_bd_states( $states ) {
	if ( ! isset( $states['BD'] ) ) {
		return $states;
	}

	$use_bangla = ! is_admin() || wp_doing_ajax();

	if ( ! apply_filters( 'assurance_bangla_district_labels', $use_bangla ) ) {
		return $states;
	}

	$districts = assurance_districts();

	foreach ( $states['BD'] as $code => $label ) {
		if ( isset( $districts[ $code ] ) ) {
			$states['BD'][ $code ] = $districts[ $code ];
		}
	}

	// Collate in Bangla alphabetical order. PHP's default sort would order
	// by UTF-8 byte value, which for Bengali happens to match the script's
	// own order closely, but Collator gives the correct result where it
	// does not (e.g. vowel signs).
	if ( class_exists( 'Collator' ) ) {
		$collator = new Collator( 'bn_BD' );
		$labels   = $states['BD'];
		$collator->asort( $labels );
		$states['BD'] = $labels;
	} else {
		asort( $states['BD'], SORT_STRING );
	}

	return $states;
}
add_filter( 'woocommerce_states', 'assurance_localise_bd_states', 20 );

/**
 * Bangla name for a district code, falling back to the code itself.
 *
 * @param string $code District code.
 * @return string
 */
function assurance_district_name( $code ) {
	$districts = assurance_districts();

	return isset( $districts[ $code ] ) ? $districts[ $code ] : (string) $code;
}
