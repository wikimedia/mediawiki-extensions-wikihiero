<?php

/**
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

namespace WikiHiero;

use Html;
use HTMLForm;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserOptionsLookup;
use SpecialPage;

class SpecialHieroglyphs extends SpecialPage {
	/** 1 day */
	private const CACHE_EXPIRY = 86400;

	/**
	 * @var WikiHiero
	 */
	private $hiero;
	private $syntaxHelp = [
		[ 'code' => '-', 'message' => 'wikihiero-separator', 'example' => 'A1 - B1' ],
		[ 'code' => ':', 'message' => 'wikihiero-superposition', 'example' => 'p:t' ],
		[ 'code' => '*', 'message' => 'wikihiero-juxtaposition', 'example' => 'p*t' ],
		[ 'code' => '!', 'message' => 'wikihiero-eol', 'example' => 'A1-B1 ! C1-D1' ],
		[ 'code' => '\\', 'message' => 'wikihiero-mirror', 'example' => 'A1\-A1' ],
		[ 'code' => '..', 'message' => 'wikihiero-void', 'example' => 'A1 .. B1' ],
		[ 'code' => '.', 'message' => 'wikihiero-half-void', 'example' => 'A1 . B1' ],
		[ 'code' => '<!-- -->', 'message' => 'wikihiero-comment', 'example' => 'A<!-- B1 -->1' ],
	];
	private $helpColumns = [
		'code',
		'meaning',
		'example',
		'result',
	];

	/**
	 * @var UserOptionsLookup
	 */
	private $userOptionsLookup;

	/**
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct( UserOptionsLookup $userOptionsLookup ) {
		parent::__construct( 'Hieroglyphs' );
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->addHelpLink( 'Extension:WikiHiero/Syntax' );
		$out = $this->getContext()->getOutput();
		$out->enableOOUI();
		$out->addModules( 'ext.wikihiero.special' );
		$out->addModuleStyles(
			[ 'ext.wikihiero', 'mediawiki.editfont.styles' ]
		);
		$out->addWikiMsg(
			'wikihiero-special-page-text',
			$this->msg( 'wikihiero-help-link' )->text()
		);

		$out->addHTML( '<div class="mw-hiero-form">' );
		$out->addHTML( '<div id="hiero-result">' );

		$text = trim( $this->getContext()->getRequest()->getVal( 'text', '' ) );
		if ( $text !== '' ) {
			$hiero = new WikiHiero();
			$out->addHTML( '<table class="wikitable">'
				. '<tr><th>' . $this->msg( 'wikihiero-input' )->escaped() . '</th><th>'
				. $this->msg( 'wikihiero-result' )->escaped() . '</th></tr>'
				. '<tr><td><code>&lt;hiero&gt;' . nl2br( htmlspecialchars( $text ) )
				. "&lt;/hiero&gt;</code></td><td>{$hiero->render( $text )}</td></tr></table>"
			);
		}

		// End of <div id="hiero-result">
		$out->addHTML( '</div>' );

		$formDescriptor = [
			'textarea' => [
				'type' => 'textarea',
				'name' => 'text',
				'id' => 'hiero-text',
				// The following classes are used here:
				// * mw-editfont-monospace
				// * mw-editfont-sans-serif
				// * mw-editfont-serif
				'cssclass' => 'mw-editfont-' . $this->userOptionsLookup->getOption( $this->getUser(), 'editfont' ),
				'default' => $text,
				'rows' => 3,
				'required' => true,
			]
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setMethod( 'get' )
			->setSubmitID( 'hiero-submit' )
			->setSubmitName( 'submit' )
			->setSubmitTextMsg( 'wikihiero-submit' )
			->prepareForm()
			->displayForm( false );

		$this->hiero = new WikiHiero();

		$out->addHTML( $this->getToc() );
		// class="mw-hiero-form"
		$out->addHTML( '</div>' );
		$out->addHTML( $this->listHieroglyphs() );
	}

	/**
	 * Returns a HTML list of hieroglyphs
	 * @return string
	 * @return-taint none
	 */
	private function listHieroglyphs() {
		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$langConv = $services->getLanguageConverterFactory()
			->getLanguageConverter( $this->getContext()->getLanguage() );

		return $cache->getWithSetCallback(
			$cache->makeKey( 'hiero-list',
				$langConv->getExtraHashOptions(),
				WikiHiero::getImagePath(),
				'1.2'
			),
			self::CACHE_EXPIRY,
			function () {
				$html = '';

				$html .= $this->getHeading( 'wikihiero-syntax', 'syntax' );
				$html .= '<table class="wikitable"><tr>';
				foreach ( $this->helpColumns as $col ) {
					$html .= '<th>' . $this->msg( "wikihiero-th-$col" )->escaped() . '</th>';
				}
				$html .= '</tr>';
				foreach ( $this->syntaxHelp as $e ) {
					$html .= $this->getSyntaxHelp( $e['code'], $e['message'], $e['example'] );
				}
				$html .= "</table>\n";

				$files = array_keys( $this->hiero->getFiles() );
				natsort( $files );

				foreach ( $this->getCategories() as $cat ) {
					$alnum = strlen( $cat ) == 1;
					$html .= $this->getHeading( "wikihiero-category-$cat", "cat-$cat" );
					foreach ( $files as $code ) {
						if ( strpos( $code, '&' ) !== false ) {
							// prefab
							continue;
						}
						if ( strpos( $code, $cat ) !== 0 || ( $alnum && !ctype_digit( $code[1] ) ) ) {
							// wrong category
							continue;
						}
						$html .=
							'<div class="mw-hiero-code">' .
							'<span class="mw-hiero-glyph">' . $this->hiero->render( $code ) . '</span>' .
							'<span class="mw-hiero-syntax">' . htmlspecialchars( $code ) . '</span>' .
							'</div>';
					}
				}

				return $html;
			}
		);
	}

	private function getToc() {
		$html = '<div class="toc mw-hiero-toc">';

		$syntax = $this->msg( 'wikihiero-syntax' )->text();
		$html .=
			Html::element( 'a',
				[ 'href' => "#syntax", 'title' => $syntax ],
				$syntax
			);
		$cats = $this->getCategories();
		$end = array_pop( $cats );
		foreach ( $cats as $cat ) {
			$html .=
				Html::element( 'a',
					[ 'href' => "#cat-$cat", 'title' => $this->msg( "wikihiero-category-$cat" )->text() ],
					$cat
				);
		}
		$html .=
			Html::element( 'a',
				[ 'href' => "#cat-$end", 'title' => $this->msg( "wikihiero-category-$end" )->text() ],
				$end
			);

		$html .= '</div>';
		return $html;
	}

	/**
	 * Returns an array with hieroglyph categories from Gardiner's list
	 * @return string[]
	 */
	private function getCategories() {
		$res = [];
		$ordJ = ord( 'J' );
		for ( $i = ord( 'A' ), $ordZ = ord( 'Z' ); $i <= $ordZ; $i++ ) {
			if ( $i != $ordJ ) {
				$res[] = chr( $i );
			}
		}
		$res[] = 'Aa';
		return $res;
	}

	private function getHeading( $message, $anchor ) {
		return "<h2 id=\"$anchor\">" . $this->msg( $message )->escaped() . "</h2>\n";
	}

	private function getSyntaxHelp( $code, $message, $example ) {
		return '<tr><th>' . htmlspecialchars( $code ) . '</th><td>'
			. $this->msg( $message )->escaped() . '</td><td dir="ltr">'
			. '<code>' . htmlspecialchars( "<hiero>$example</hiero>" ) . '</code></td><td>'
			. $this->hiero->render( $example )
			. "</td></tr>\n";
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}
}
