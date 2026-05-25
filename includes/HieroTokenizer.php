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

	private const string DELIMITERS = " -\t\n\r";
	private const string TOKEN_DELIMITERS = '*:()';
	private const string SINGLE_CHAR_DELIMITER = '!';

	/** @var string[][]|false */
	private array|false $blocks = false;
	/** @var string[] */
	private array $currentBlock = [];
	private string $token = '';

	public function __construct(
		private readonly string $text
	) {
	}

	/**
	 * Split text into blocks, then split blocks into items
	 *
	 * @return string[][] tokenized text
	 *
	 * @suppress PhanParamSuspiciousOrder
	 */
	public function tokenize(): array {
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

			if ( str_contains( self::DELIMITERS, $char ) ) {
				$this->newBlock();
			} elseif ( $char === self::SINGLE_CHAR_DELIMITER ) {
				$this->singleCharBlock( $char );
			} elseif ( $char == '.' ) {
				$this->dot();
			} elseif ( str_contains( self::TOKEN_DELIMITERS, $char ) ) {
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
	private function newBlock(): void {
		$this->newToken();
		if ( $this->currentBlock ) {
			$this->blocks[] = $this->currentBlock;
			$this->currentBlock = [];
		}
	}

	/**
	 * Flushes current token, optionally adds another one
	 *
	 * @param string|false $token token to add or false
	 */
	private function newToken( string|false $token = false ): void {
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
	private function singleCharBlock( string $char ): void {
		$this->newBlock();
		$this->blocks[] = [ $char ];
	}

	/**
	 * Handles void blocks represented by dots
	 */
	private function dot(): void {
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
	 */
	private function char( string $char ): void {
		if ( $this->token == '.' ) {
			$this->newBlock();
			$this->token = $char;
		} else {
			$this->token .= $char;
		}
	}
}
