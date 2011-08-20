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
	public function __construct() {
		parent::__construct( 'Hieroglyphs', '', true );
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
					'action' => SpecialPage::getTitleFor( 'Hieroglyphs' )->getLinkUrl(),
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
			
	}
 }