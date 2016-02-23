<?php
class WikiHieroHooks {
	/**
	 * Because <hiero> tag is used rarely, we don't need to load its body on every hook call,
	 * so we keep our simple hook handlers here.
	 *
	 * @param $parser Parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setHook( 'hiero', 'WikiHiero::parserHook' );
		return true;
	}
}
