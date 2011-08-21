<?php

//////////////////////////////////////////////////////////////////////////
//
// WikiHiero - A PHP convert from text using "Manual for the encoding of
// hieroglyphic texts for computer input" syntax to HTML entities (table and
// images).
//
// Copyright (C) 2004 Guillaume Blanchard (Aoineko)
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//////////////////////////////////////////////////////////////////////////

$wgHooks['ParserFirstCallInit'][] = 'wfRegisterWikiHiero';
$wgHooks['BeforePageDisplay'][] = 'wfHieroBeforePageDisplay';

// Register MediaWiki extension
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'WikiHiero',
	'author'         => array( 'Guillaume Blanchard', 'Max Semenik' ),
	'version'        => '2.0',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:WikiHiero',
	'descriptionmsg' => 'wikihiero-desc',
);

$dir = dirname( __FILE__ );

$wgExtensionMessagesFiles['Wikihiero'] = "$dir/wikihiero.i18n.php";
$wgExtensionAliasesFiles['Hieroglyphs'] = "$dir/wikihiero.alias.php";

$wgAutoloadClasses['WikiHiero'] = "$dir/wikihiero.body.php";
$wgAutoloadClasses['SpecialHieroglyphs'] = "$dir/SpecialHieroglyphs.php";

$wgSpecialPages['Hieroglyphs'] = 'SpecialHieroglyphs';
$wgSpecialPageGroups['Hieroglyphs'] = 'wiki';

$wgResourceModules['ext.wikihiero'] = array(
	'styles' => 'ext.wikihiero.css',
	'localBasePath' => "$dir/modules",
	'remoteExtPath' => 'wikihiero/modules',
);

$wgResourceModules['ext.wikihiero.Special'] = array(
	'scripts' => 'ext.wikihiero.Special.js',
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'wikihiero/modules',
	'messages' => array(
		'wikihiero-input',
		'wikihiero-result',
		'wikihiero-load-error',
	),
);


// Because <hiero> tag is used rarely, we don't need to load its body on every hook call,
// so we keep our simple hook handlers here.
function wfRegisterWikiHiero( &$parser ) {
	$parser->setHook( 'hiero', 'WikiHiero::parserHook' );
	return true;
}

function wfHieroBeforePageDisplay( $out ) {
	$out->addModuleStyles( 'ext.wikihiero' );
	return true;
}
