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
	'author'         => 'Guillaume Blanchard',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:WikiHiero',
	'descriptionmsg' => 'wikihiero-desc',
);
$wgExtensionMessagesFiles['Wikihiero'] =  dirname( __FILE__ ) . '/wikihiero.i18n.php';

$wgAutoloadClasses['WikiHiero'] = dirname( __FILE__ ) . '/wikihiero.body.php';

$wgResourceModules['ext.wikihiero'] = array(
	'styles' => 'ext.wikihiero.css',
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'wikihiero/modules',
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
