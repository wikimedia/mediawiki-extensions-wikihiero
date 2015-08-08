<?php

/**
 * WikiHiero - adds Ancient Egyptian hieroglyphs support to MediaWiki
 *
 * Copyright (C) 2004 Guillaume Blanchard (Aoineko)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

define( 'WIKIHIERO_VERSION', '1.1' );

$wgHooks['ParserFirstCallInit'][] = 'wfRegisterWikiHiero';
$wgHooks['RejectParserCacheValue'][] = 'WikiHiero::onRejectParserCacheValue';

// Register MediaWiki extension
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'WikiHiero',
	'version'        => WIKIHIERO_VERSION,
	'author'         => array( 'Guillaume Blanchard', 'Max Semenik' ),
	'url'            => '//www.mediawiki.org/wiki/Extension:WikiHiero',
	'descriptionmsg' => 'wikihiero-desc',
	'license-name'   => 'GPL-2.0+',
);

$dir = __DIR__;

$wgMessagesDirs['Wikihiero'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Wikihiero'] = "$dir/wikihiero.i18n.php";
$wgExtensionMessagesFiles['HieroglyphsAlias'] = "$dir/wikihiero.alias.php";

$wgAutoloadClasses['WikiHiero'] = "$dir/wikihiero.body.php";
$wgAutoloadClasses['SpecialHieroglyphs'] = "$dir/SpecialHieroglyphs.php";
$wgAutoloadClasses['HieroTokenizer'] = "$dir/HieroTokenizer.php";

$wgParserTestFiles[] = "$dir/tests.txt";

$wgSpecialPages['Hieroglyphs'] = 'SpecialHieroglyphs';

$moduleTemplate = array(
    'localBasePath' => __DIR__ . '/modules',
    'remoteExtPath' => 'wikihiero/modules',
);

$wgResourceModules['ext.wikihiero'] = array(
	'position' => 'top',
	'styles' => 'ext.wikihiero.css',
) + $moduleTemplate;

$wgResourceModules['ext.wikihiero.Special'] = array(
	'position' => 'top',
	'scripts' => 'ext.wikihiero.Special.js',
	'styles' => 'ext.wikihiero.Special.css',
	'dependencies' => array( 'jquery.spinner' ),
	'messages' => array(
		'wikihiero-input',
		'wikihiero-result',
		'wikihiero-load-error',
	),
) + $moduleTemplate;

$wgResourceModules['ext.wikihiero.visualEditor'] = array(
	'scripts' => array(
		'VisualEditor/ve.dm.MWHieroNode.js',
		'VisualEditor/ve.ce.MWHieroNode.js',
		'VisualEditor/ve.ui.MWHieroInspector.js',
		'VisualEditor/ve.ui.MWHieroInspectorTool.js',
	),
	'styles' => array(
		'VisualEditor/ve.ui.MWHieroIcons.css',
	),
	'dependencies' => array(
		'ext.visualEditor.mwcore',
	),
	'messages' => array(
		'wikihiero-visualeditor-mwhieroinspector-title',
	),
	'targets' => array( 'desktop', 'mobile' ),
) + $moduleTemplate;

$wgVisualEditorPluginModules[] = 'ext.wikihiero.visualEditor';

/**
 * Because <hiero> tag is used rarely, we don't need to load its body on every hook call,
 * so we keep our simple hook handlers here.
 *
 * @param $parser Parser
 * @return bool
 */
function wfRegisterWikiHiero( &$parser ) {
	$parser->setHook( 'hiero', 'WikiHiero::parserHook' );
	return true;
}
