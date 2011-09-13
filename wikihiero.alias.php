<?php
/**
 * Aliases for special pages
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'Hieroglyphs' => array( 'Hieroglyphs' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'Hieroglyphs' => array( 'هيروغليفي' ),
);

/** Danish (Dansk) */
$specialPageAliases['da'] = array(
	'Hieroglyphs' => array( 'Hieroglyffer' ),
);

/** Macedonian (Македонски) */
$specialPageAliases['mk'] = array(
	'Hieroglyphs' => array( 'Хиероглифи' ),
);

/**
 * For backwards compatibility with MediaWiki 1.15 and earlier.
 */
$aliases =& $specialPageAliases;