<?php
/**
 * Bengali typography for the WebToffee PDF invoice / packing slip.
 *
 * The invoice is rendered by mPDF (the "mPDF add-on for PDF Invoices"
 * plugin, with `active_pdf_library` set to mpdf). mPDF's autoLangToFont
 * pass detects Bengali script runs and hard-maps them to the `freeserif`
 * family — see vendor/mpdf/mpdf/src/Language/LanguageToFont.php, case 'bn'.
 *
 * That mapping is only half usable: FreeSerif.ttf carries the Bengali
 * block, but FreeSerifBold.ttf carries none of it — zero codepoints in
 * U+0980–U+09FF, including the taka sign U+09F3. So every *bold* Bengali
 * string on the invoice (document title, "বিল প্রাপক" heading, the whole
 * product table header row, the grand-total figure) came out as a row of
 * .notdef boxes, while regular-weight Bengali rendered fine.
 *
 * The fix is to repoint `freeserif` at a Bengali family that actually has
 * a bold face. Nothing else on this site renders a non-Bengali script
 * through freeserif (`serif` resolves to dejavuserifcondensed first, and
 * freeserif is not in backupSubsFont), so the override is contained to
 * what it fixes.
 *
 * Why Hind Siliguri and not Noto Sans Bengali / Anek Bangla (the site's
 * web face): mPDF's OTL parser predates a lot of modern OpenType, and both
 * of those throw "GPOS Lookup Type 5, Format 3 not supported" out of
 * TTFontFile::_getGSUBtables() before a single page is written. Hind
 * Siliguri parses, shapes conjuncts and i-matra reordering correctly, and
 * has real 400/700 cuts plus Bengali digits and ৳. Verified by rendering
 * ০–৯, ৳, and conjunct-heavy strings (স্বত্বাধিকার, বিদ্যুৎ, শুক্রবার,
 * দুঃখ) in both weights.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

/**
 * Directory holding the TTFs handed to mPDF. Kept apart from
 * assets/fonts/, which is web (woff2) only — mPDF cannot read woff2.
 */
const ASSURANCE_PDF_FONT_DIR = '/assets/fonts/pdf';

/**
 * Register Hind Siliguri with mPDF and make it the Bengali face.
 *
 * Hooks the add-on's page-properties filter because that array is merged
 * straight into the \Mpdf\Mpdf constructor config, which is the only place
 * fontDir/fontdata can be set — the add-on rebuilds the Mpdf instance on
 * every generate() call and overwrites the runtime properties afterwards.
 *
 * Both `fontDir` and `fontdata` *replace* mPDF's defaults rather than
 * merging into them (see Mpdf::initConfig / initFontConfig, which run the
 * incoming config through array_intersect_key against the defaults), so we
 * start from the default arrays and add to them.
 *
 * The filter also passes $template_type (invoice | packinglist | ...) as a
 * second argument; the font applies to every document, so it is ignored.
 *
 * @param array $properties mPDF constructor config.
 * @return array
 */
function assurance_mpdf_bengali_font( $properties ) {
	if ( ! class_exists( '\Mpdf\Config\ConfigVariables' ) || ! class_exists( '\Mpdf\Config\FontVariables' ) ) {
		return $properties;
	}

	$font_dir = ASSURANCE_DIR . ASSURANCE_PDF_FONT_DIR;

	// Bail rather than half-register: a fontdata entry pointing at a
	// missing file makes mPDF throw instead of falling back.
	if ( ! is_readable( $font_dir . '/HindSiliguri-Regular.ttf' )
		|| ! is_readable( $font_dir . '/HindSiliguri-Bold.ttf' ) ) {
		return $properties;
	}

	$config_defaults = ( new \Mpdf\Config\ConfigVariables() )->getDefaults();
	$font_defaults   = ( new \Mpdf\Config\FontVariables() )->getDefaults();

	$font_dirs   = isset( $properties['fontDir'] ) ? (array) $properties['fontDir'] : $config_defaults['fontDir'];
	$font_dirs[] = $font_dir;

	$font_data = isset( $properties['fontdata'] ) ? (array) $properties['fontdata'] : $font_defaults['fontdata'];

	// Hind Siliguri has no italic cut; point I/BI at the upright faces so
	// an <em> in a Bengali run degrades to roman instead of throwing.
	$bengali = array(
		'R'      => 'HindSiliguri-Regular.ttf',
		'B'      => 'HindSiliguri-Bold.ttf',
		'I'      => 'HindSiliguri-Regular.ttf',
		'BI'     => 'HindSiliguri-Bold.ttf',
		'useOTL' => 0xFF, // Required for Bengali shaping (conjuncts, reph, i-matra reordering).
	);

	// Available to templates as font-family: hindsiliguri.
	$font_data['hindsiliguri'] = $bengali;

	// The actual fix: lang="bn" resolves to freeserif inside mPDF, so this
	// is what bold Bengali ends up asking for.
	$font_data['freeserif'] = $bengali;

	$properties['fontDir']  = $font_dirs;
	$properties['fontdata'] = $font_data;

	return $properties;
}
add_filter( 'wt_pklist_alter_page_properties_in_mpdf', 'assurance_mpdf_bengali_font' );
