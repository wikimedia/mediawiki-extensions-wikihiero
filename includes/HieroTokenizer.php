<?php
/**
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

namespace WikiHiero;

/**
 * Hieroglyphs tokenizer
 */
class HieroTokenizer {

	/** @var array|false */
	private static $delimiters = false;
	/** @var array */
	private static $tokenDelimiters;
	/** @var array */
	private static $singleChars;

	/** @var string */
	private $text;
	/** @var string[][]|false */
	private $blocks = false;
	/** @var string[] */
	private $currentBlock;
	/** @var string */
	private $token;

	/**
	 * Constructor
	 *
	 * @param string $text
	 */
	public function __construct( $text ) {
		$this->text = $text;
		self::initStatic();
	}

	private static function initStatic() {
		if ( self::$delimiters ) {
			return;
		}

		self::$delimiters = array_flip( [ ' ', '-', "\t", "\n", "\r" ] );
		self::$tokenDelimiters = array_flip( [ '*', ':', '(', ')' ] );
		self::$singleChars = array_flip( [ '!' ] );
	}

	/**
	 * Split text into blocks, then split blocks into items
	 *
	 * @return string[][] tokenized text
	 */
	public function tokenize() {
		if ( $this->blocks !== false ) {
			return $this->blocks;
		}

		$this->blocks = [];
		$this->currentBlock = [];
		$this->token = '';

		// remove HTML comments
		$text = preg_replace( '/\\<!--.*?--\\>/s', '', $this->text );

		for ( $i = 0, $len = strlen( $text ); $i < $len; $i++ ) {
			$char = $text[$i];

			if ( isset( self::$delimiters[$char] ) ) {
				$this->newBlock();
			} elseif ( isset( self::$singleChars[$char] ) ) {
				$this->singleCharBlock( $char );
			} elseif ( $char == '.' ) {
				$this->dot();
			} elseif ( isset( self::$tokenDelimiters[$char] ) ) {
				$this->newToken( $char );
			} else {
				$this->char( $char );
			}
		}

		// flush stuff being processed
		$this->newBlock();

		return $this->blocks;
	}

	/**
	 * Handles a block delimiter
	 */
	private function newBlock() {
		$this->newToken();
		if ( $this->currentBlock ) {
			$this->blocks[] = $this->currentBlock;
			$this->currentBlock = [];
		}
	}

	/**
	 * Flushes current token, optionally adds another one
	 *
	 * @param string|bool $token token to add or false
	 */
	private function newToken( $token = false ) {
		if ( $this->token !== '' ) {
			$this->currentBlock[] = $this->token;
			$this->token = '';
		}
		if ( $token !== false ) {
			$this->currentBlock[] = $token;
		}
	}

	/**
	 * Adds a block consisting of one character
	 *
	 * @param string $char block character
	 */
	private function singleCharBlock( $char ) {
		$this->newBlock();
		$this->blocks[] = [ $char ];
	}

	/**
	 * Handles void blocks represented by dots
	 */
	private function dot() {
		if ( $this->token == '.' ) {
			$this->token = '..';
			$this->newBlock();
		} else {
			$this->newBlock();
			$this->token = '.';
		}
	}

	/**
	 * Adds a miscellaneous character to current token
	 *
	 * @param string $char character to add
	 */
	private function char( $char ) {
		if ( $this->token == '.' ) {
			$this->newBlock();
			$this->token = $char;
		} else {
			$this->token .= $char;
		}
	}
}
