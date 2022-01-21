<?php

namespace WikiHiero;

use Parser;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class Hooks extends ExtensionTagHandler implements ExtensionModule {
	/**
	 * Because <hiero> tag is used rarely, we don't need to load its body on every hook call,
	 * so we keep our simple hook handlers here.
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'hiero',  WikiHiero::class . '::parserHook' );
		return true;
	}

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'WikiHiero',
			'tags' => [
				[
					'name' => 'hiero',
					'handler' => self::class
				]
			]
		];
	}

	/** @inheritDoc */
	public function sourceToDom( ParsoidExtensionAPI $extApi, string $src, array $extArgs ) {
		$hiero = new WikiHiero();
		$extApi->addModuleStyles( [ 'ext.wikihiero' ] );
		$html = str_replace( "\n", " ", $hiero->render( $src ) );
		return $extApi->htmlToDom( $html );
	}
}
