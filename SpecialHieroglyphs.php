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

class SpecialHieroglyphs extends SpecialPage {
	const HIEROGLYPHS_PER_ROW = 10;
	const CACHE_EXPIRY = 86400; // 1 day

	public function __construct() {
		parent::__construct( 'Hieroglyphs' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$out = $this->getContext()->getOutput();
		$out->addModules( 'ext.wikihiero.Special' );
		$out->addWikiMsg( 'wikihiero-special-page-text' );
		
		$out->addHTML( '<div id="hiero-result">' );

		$text = trim( $this->getContext()->getRequest()->getVal( 'text', '' ) );
		if ( $text !== '' ) {
			$hiero = new WikiHiero();
			$out->addHTML( '<table class="wikitable">'
				. '<tr><th>' . wfMsg( 'wikihiero-input' ) . '</th><th>' 
				. wfMsg( 'wikihiero-result' ) . '</th></tr>'
				. '<tr><td><code>&lt;hiero&gt;' . htmlspecialchars( $text ) . '&lt;/hiero&gt;</code></td>'
				. "<td>{$hiero->renderHtml( $text )}</td></tr></table>"
			);
		}

		$out->addHTML( '</div>' ); // id="hiero-result"

		$out->addHTML(
			Html::openElement( 'form',
				array(
					'method' => 'get',
					'action' => $this->getTitle()->getLinkUrl(),
				)
			)
			. Html::element( 'textarea', array( 'id' => 'hiero-text', 'name' => 'text' ), $text )
			. Html::element( 'input', array(
				'type' => 'submit',
				'id' => 'hiero-submit',
				'name' => 'submit',
			) )
			. Html::closeElement( 'form' )
		);

		$out->addHTML( '<div class="mw-hiero-list">' );
		$out->addHTML( $this->listHieroglyphs() );
		$out->addHTML( '</div>' );
	}

	/**
	 * Returns a HTML list of hieroglyphs
	 */
	private function listHieroglyphs() {
		global $wgMemc;

		$key = wfMemcKey( 'hiero-list',
			$this->getContext()->getLang()->getCode(),
			WikiHiero::getImagePath(),
			WIKIHIERO_VERSION
		);
		$html = $wgMemc->get( $key );
		if ( $html ) {
			return $html;
		}
		$html = '';
		$hiero = new WikiHiero();
		$files = array_keys( $hiero->getFiles() );
		natsort( $files );

		foreach ( $this->getCategories() as $cat ) {
			$alnum = strlen( $cat ) == 1;
			$html .= "<h2 id=\"cat-$cat\">" . wfMessage( "wikihiero-category-$cat" )->escaped() . "</h2>
<table class=\"wikitable\">
";
			$upperRow = $lowerRow = '';
			$columns = 0;
			$rows = 0;
			foreach ( $files as $code ) {
				if ( strpos( $code, '&' ) !== false ) {
					continue; // prefab
				}
				if ( strpos( $code, $cat ) !== 0 || ( $alnum && !ctype_digit( $code[1] ) ) ) {
					continue; // wrong category
				}
				$upperRow .= '<td>' . $hiero->renderHtml( $code ) . '</td>';
				$lowerRow .= '<th>' . htmlspecialchars( $code ) . '</th>';
				$columns++;
				if ( $columns == self::HIEROGLYPHS_PER_ROW ) {
					$html .= "<tr>$upperRow</tr>\n<tr>$lowerRow</tr>\n";
					$upperRow = $lowerRow = '';
					$columns = 0;
					$rows++;
				}
			}
			if ( $columns ) {
				$html .= "<tr>$upperRow"
					. ( $columns && $rows ? '<td colspan="' . ( self::HIEROGLYPHS_PER_ROW - $columns ) . '">&#160;</td>' : '' ) . "</tr>\n";
				$html .= "<tr>$lowerRow"
					. ( $columns && $rows ? '<th colspan="' . ( self::HIEROGLYPHS_PER_ROW - $columns ) . '">&#160;</th>' : '' ) . "</tr>\n";
			}
			$html .= "</table>\n";
		}
		$wgMemc->set( $key, $html, self::CACHE_EXPIRY );
		return $html;
	}

	/**
	 * Returns an array with hieroglyph categories from Gardiner's list
	 */
	private function getCategories() {
		$res = array();
		for ( $i = ord( 'A' ); $i <= ord( 'Z' ); $i++ ) {
			if ( $i != ord( 'J' ) ) {
				$res[] = chr( $i );
			}
		}
		$res[] = 'Aa';
		return $res;
	}
 }