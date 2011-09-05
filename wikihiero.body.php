<?php

/**
 * WikiHiero - A PHP convert from text using "Manual for the encoding of
 * hieroglyphic texts for computer input" syntax to HTML entities (table and
 * images).
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

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point' );
}

// D E F I N E S
define( "WH_TABLE_S",      '<table class="mw-hiero-table">' );
define( "WH_TABLE_E",      '</table>' );
define( "WH_TD_S",         '<td>' );
define( "WH_TD_E",         '</td>' );

define( "WH_MODE_DEFAULT", -1 );    // use default mode
define( "WH_MODE_TEXT",     0 );    // text only
define( "WH_MODE_HTML",     1 );    // HTML without CSS
define( "WH_MODE_STYLE",    2 );    // HTML and CSS // not supported
define( "WH_MODE_IMAGE",    3 );    // picture (PNG) // not supported
define( "WH_MODE_RAW",      4 );    // MdC test as it

define( "WH_TYPE_NONE",     0 );
define( "WH_TYPE_GLYPH",    1 );    // rendered items
define( "WH_TYPE_CODE",     2 );    // single code as ':', '*', '!', '(' or ')'
define( "WH_TYPE_SPECIAL",  3 );    // advanced code (more than 1 caracter)
define( "WH_TYPE_END",      4 );    // end of line '!'

define( "WH_SCALE_DEFAULT", -1 );   // use default scale
define( "WH_HEIGHT",        44 );
define( "WH_IMG_MARGIN",    1 );    // default value
define( "WH_CARTOUCHE_WIDTH", 2 );  // default value

global $wgExtensionAssetsPath;
define( "WH_IMG_DIR",       $wgExtensionAssetsPath . '/wikihiero/img/' );
define( "WH_IMG_PRE",       "hiero_" );

define( "WH_DEBUG_MODE",    false );

class WikiHiero {
	const IMG_EXT = 'png';

	private $scale = 100;

	private static $phonemes, $prefabs, $files, $textConv;

	public function __construct( $scale = WH_SCALE_DEFAULT ) {
		self::loadData();
	}

	/**
	 * Loads hieroglyph information
	 */
	private static function loadData() {
		if ( self::$phonemes ) {
			return;
		}
		if ( MWInit::isHipHop() ) {
			require_once( MWInit::extCompiledPath( 'wikihiero/data/tables.php' ) );
			self::$phonemes = $wh_phonemes;
			self::$prefabs = $wh_prefabs;
			self::$files = $wh_files;
			self::$textConv = $wh_text_conv;
		} else {
			$fileName = dirname( __FILE__ ) . '/data/tables.ser';
			$stream = file_get_contents( $fileName );
			if ( !$stream ) {
				throw new MWException( "Cannot open serialized hieroglyph data file $fileName!" );
			}
			$data = unserialize( $stream );
			self::$phonemes = $data['wh_phonemes'];
			self::$prefabs = $data['wh_prefabs'];
			self::$files = $data['wh_files'];
			self::$textConv = $data['wh_text_conv'];
		}
	}

	/**
	 * Render hieroglyph text
	 *
	 * @param $text string: text to convert
	 * @param $mode string: conversion mode [DEFAULT|TEXT|HTML|STYLE|IMAGE] (def=HTML)
	 * @param $scale string: global scale in percentage (def=100%)
	 * @param $line string: use line [true|false] (def=false)
	 * @return string: converted code
	 */
	public static function render( $text, $mode = WH_MODE_DEFAULT, $scale = WH_SCALE_DEFAULT, $line = false ) {
		if ( $mode == WH_MODE_DEFAULT ) {
			$mode = WH_MODE_HTML;
		}

		$hiero = new WikiHiero( $scale );

		switch( $mode ) {
			case WH_MODE_TEXT:  return $hiero->renderText( $text, $line );
			case WH_MODE_HTML:  return $hiero->renderHtml( $text, $scale, $line );
			case WH_MODE_STYLE: die( "ERROR: CSS version not yet implemented" );
			case WH_MODE_IMAGE: die( "ERROR: Image version not yet implemented" );
		}
		die( "ERROR: Unknown mode!" );
	}

	/**
	 *
	 */
	public static function parserHook( $input ) {
		// Strip newlines to avoid breakage in the wiki parser block pass
		return str_replace( "\n", " ", self::render( $input, WH_MODE_HTML ) );
	}

	public function getScale() {
		return $this->scale;
	}

	public function setScale( $scale ) {
		$this->scale = $scale;
	}

	/**
	 * Renders a glyph
	 *
	 * @param $glyph string: glyph's code to render
	 * @param $option string: option to add into <img> tag (use for height)
	 * @return string: a string to add to the stream
	 */
	private function renderGlyph( $glyph, $option = '' ) {
		if ( $glyph == ".." ) { // Render void block
		  $width = WH_HEIGHT;
		  return "<table class=\"mw-hiero-table\" style=\"width: {$width}px;\"><tr><td>&#160;</td></tr></table>";
		}
		elseif ( $glyph == "." ) // Render half-width void block
		{
		  $width = WH_HEIGHT / 2;
		  return "<table class=\"mw-hiero-table\" style=\"width: {$width}px;\"><tr><td>&#160;</td></tr></table>";
		}
		elseif ( $glyph == '<' ) // Render open cartouche
		{
		  $height = intval( WH_HEIGHT * $this->scale / 100 );
		  $code = self::$phonemes[$glyph];
		  return "<img src='" . htmlspecialchars( WH_IMG_DIR . WH_IMG_PRE . "{$code}." . self::IMG_EXT ) . "' height='{$height}' title='" . htmlspecialchars( $glyph ) . "' alt='" . htmlspecialchars( $glyph ) . "' />";
		}
		elseif ( $glyph == '>' ) // Render close cartouche
		{
		  $height = intval( WH_HEIGHT * $this->scale / 100 );
		  $code = self::$phonemes[$glyph];
		  return "<img src='" . htmlspecialchars( WH_IMG_DIR . WH_IMG_PRE . "{$code}." . self::IMG_EXT ) . "' height='{$height}' title='" . htmlspecialchars( $glyph ) . "' alt='" . htmlspecialchars( $glyph ) . "' />";
		}

		if ( array_key_exists( $glyph, self::$phonemes ) )
		{
		  $code = self::$phonemes[$glyph];
		  if ( array_key_exists( $code, self::$files ) )
			return "<img style='margin:" . WH_IMG_MARGIN . "px;' $option src='" . htmlspecialchars( WH_IMG_DIR . WH_IMG_PRE . "{$code}." . self::IMG_EXT ) . "' title='" . htmlspecialchars( "{$code} [{$glyph}]" ) . "' alt='" . htmlspecialchars( $glyph ) . "' />";
		  else
			return "<font title='" . htmlspecialchars( $code ) . "'>" . htmlspecialchars( $glyph ) . "</font>";
		}
		elseif ( array_key_exists( $glyph, self::$files ) )
		  return "<img style='margin:" . WH_IMG_MARGIN . "px;' $option src='" . htmlspecialchars( WH_IMG_DIR . WH_IMG_PRE . "{$glyph}." . self::IMG_EXT ) . "' title='" . htmlspecialchars( $glyph ) . "' alt='" . htmlspecialchars( $glyph ) . "' />";
		else
		  return htmlspecialchars( $glyph );
	}

	/**
	 * Resize a glyph
	 *
	 * @param $item string: glyph code
	 * @param $is_cartouche bool: true if glyph is inside a cartouche
	 * @param $total int: total size of a group for multi-glyph block
	 * @return size
	 */
	private function resizeGlyph( $item, $is_cartouche = false, $total = 0 ) {
		if ( array_key_exists( $item, self::$phonemes ) ) {
			$glyph = self::$phonemes[$item];
		} else {
		  $glyph = $item;
		}

		$margin = 2 * WH_IMG_MARGIN;
		if ( $is_cartouche ) {
			$margin += 2 * intval( WH_CARTOUCHE_WIDTH * $this->scale / 100 );
		}

		if ( array_key_exists( $glyph, self::$files ) ) {
			$height = $margin + self::$files[$glyph][1];
			if ( $total ) {
				if ( $total > WH_HEIGHT ) {
					return ( intval( $height * WH_HEIGHT / $total ) - $margin ) * $this->scale / 100;
				} else {
					return ( $height - $margin ) * $this->scale / 100;
				}
			} else {
				if ( $height > WH_HEIGHT ) {
					return ( intval( WH_HEIGHT * WH_HEIGHT / $height ) - $margin ) * $this->scale / 100;
				} else {
					return ( $height - $margin ) * $this->scale / 100;
				}
			}
		}

		return ( WH_HEIGHT - $margin ) * $this->scale / 100;
	}

	/**
	 * Render hieroglyph text in text mode
	 *
	 * @param $hiero string: text to convert
	 * @param $line bool: use line (default = false)
	 * @return string: converted code
	 */
	public function renderText( $hiero, $line = false ) {
		$html = "";

		if ( $line )
		  $html .= "<hr />\n";

		for ( $char = 0; $char < strlen( $hiero ); $char++ )
		{
		  if ( array_key_exists( $hiero[$char], self::$textConv ) )
		  {
			$html .= self::$textConv[$hiero[$char]];
			if ( $hiero[$char] == '!' )
			  if ( $line )
				$html .= "<hr />\n";
		  }
		  else
			$html .= $hiero[$char];
		}

		return $html;
	  }

	/**
	 * Render hieroglyph text
	 *
	 * @param $hiero string: text to convert
	 * @param $scale int: global scale in percentage (default = 100%)
	 * @param $line bool: use line (default = false)
	 * @return string: converted code
	*/
	public function renderHtml( $hiero, $scale = WH_SCALE_DEFAULT, $line = false ) {
		if ( $scale != WH_SCALE_DEFAULT )
		  $this->setScale( $scale );

		$html = "";

		if ( $line ) {
		  $html .= "<hr />\n";
		}

		// ------------------------------------------------------------------------
		// Split text into block, then split block into item
		$block = array();
		$block[0] = array();
		$block[0][0] = "";
		$block_id = 0;
		$item_id = 0;
		$parenthesis = 0;
		$type = WH_TYPE_NONE;
		$is_cartouche = false;

		for ( $char = 0; $char < strlen( $hiero ); $char++ ) {

			if ( $hiero[$char] == '(' ) {
				$parenthesis++;
			} elseif ( $hiero[$char] == ')' ) {
				$parenthesis--;
			}

			if ( $parenthesis == 0 ) {
				if ( $hiero[$char] == '-' || $hiero[$char] == ' ' ) {
					if ( $type != WH_TYPE_NONE ) {
						$block_id++;
						$block[$block_id] = array();
						$item_id = 0;
						$block[$block_id][$item_id] = "";
						$type = WH_TYPE_NONE;
					}
				}
			} else {// don't slit block if inside parenthesis
				if ( $hiero[$char] == '-' ) {
					$item_id++;
					$block[$block_id][$item_id] = '-';
					$type = WH_TYPE_CODE;
				}
			}

			if ( $hiero[$char] == '!' ) {
				if ( $item_id > 0 ) {
					$block_id++;
					$block[$block_id] = array();
					$item_id = 0;
				}
				$block[$block_id][$item_id] = $hiero[$char];
				$type = WH_TYPE_END;

			} elseif ( preg_match( "/[*:()]/", $hiero[$char] ) ) {

				if ( $type == WH_TYPE_GLYPH || $type == WH_TYPE_CODE ) {
					$item_id++;
					$block[$block_id][$item_id] = "";
				}
			$block[$block_id][$item_id] = $hiero[$char];
			$type = WH_TYPE_CODE;

			} elseif ( ctype_alnum( $hiero[$char] ) || $hiero[$char] == '.' || $hiero[$char] == '<' || $hiero[$char] == '>' ) {
				if ( $type == WH_TYPE_END ) {
					$block_id++;
					$block[$block_id] = array();
					$item_id = 0;
					$block[$block_id][$item_id] = "";
				} elseif ( $type == WH_TYPE_CODE ) {
					$item_id++;
					$block[$block_id][$item_id] = "";
				}
				$block[$block_id][$item_id] .= $hiero[$char];
				$type = WH_TYPE_GLYPH;
			}
		}

		// DEBUG: See the block split table
		if ( WH_DEBUG_MODE ) {

			foreach ( $block as $code ) {
				echo "| ";
				foreach ( $code as $item ) {
					echo "$item | ";
				}
				echo "<br />\n";
			}
		}

		$contentHtml = $tableHtml = $tableContentHtml = "";
		// $html .= WH_TABLE_S."<tr>\n";

		// ------------------------------------------------------------------------
		// Loop into all blocks
		foreach ( $block as $code ) {

			// simplest case, the block contain only 1 code -> render
			if ( count( $code ) == 1 )
			{
				if ( $code[0] == "!" ) { // end of line
					$tableHtml = "</tr>" . WH_TABLE_E . WH_TABLE_S . "<tr>\n";
					if ( $line ) {
						$contentHtml .= "<hr />\n";
					}

				} elseif ( strchr( $code[0], '<' ) ) { // start cartouche
					$contentHtml .= WH_TD_S . self::renderGlyph( $code[0] ) . WH_TD_E;
					$is_cartouche = true;
					$contentHtml .= "<td>" . WH_TABLE_S . "<tr><td height='" . intval( WH_CARTOUCHE_WIDTH * $this->scale / 100 ) . "px' bgcolor='black'></td></tr><tr><td>" . WH_TABLE_S . "<tr>";

				} elseif ( strchr( $code[0], '>' ) ) { // end cartouche
					$contentHtml .= "</tr>" . WH_TABLE_E . "</td></tr><tr><td height='" . intval( WH_CARTOUCHE_WIDTH * $this->scale / 100 ) . "px' bgcolor='black'></td></tr>" . WH_TABLE_E . "</td>";
					$is_cartouche = false;
					$contentHtml .= WH_TD_S . self::renderGlyph( $code[0] ) . WH_TD_E;

				} elseif ( $code[0] != "" ) { // assum is glyph or '..' or '.'
					$option = "height='" . $this->resizeGlyph( $code[0], $is_cartouche ) . "'";

					$contentHtml .= WH_TD_S . self::renderGlyph( $code[0], $option ) . WH_TD_E;
				}

			// block contains more than 1 glyph
			} else {

				// convert all codes into '&' to test prefabs glyph
				$temp = "";
				foreach ( $code as $t ) {
					if ( preg_match( "/[*:!()]/", $t[0] ) ) {
						$temp .= "&";
					} else {
						$temp .= $t;
					}
				}

			// test if block exists in the prefabs list
			if ( in_array( $temp, self::$prefabs ) ) {
				$option = "height='" . $this->resizeGlyph( $temp, $is_cartouche ) . "'";

				$contentHtml .= WH_TD_S . self::renderGlyph( $temp, $option ) . WH_TD_E;

			// block must be manually computed
			} else {
				// get block total height
				$line_max = 0;
				$total    = 0;
				$height   = 0;

				foreach ( $code as $t ) {
					if ( $t == ":" ) {
						if ( $height > $line_max ) {
							$line_max = $height;
						}
						$total += $line_max;
						$line_max = 0;

					} elseif ( $t == "*" ) {
						if ( $height > $line_max ) {
							$line_max = $height;
						}
					} else {
						if ( array_key_exists( $t, self::$phonemes ) ) {
							$glyph = self::$phonemes[$t];
						} else {
							$glyph = $t;
						}
						if ( array_key_exists( $glyph, self::$files ) ) {
							$height = 2 + self::$files[$glyph][1];
						}
					}
				} // end foreach

				if ( $height > $line_max ) {
					$line_max = $height;
				}

				$total += $line_max;

				// render all glyph into the block
				$temp = "";
				foreach ( $code as $t ) {

					if ( $t == ":" ) {
						$temp .= "<br />";

					} elseif ( $t == "*" ) {
						$temp .= " ";

					} else {
						// resize the glyph according to the block total height
						$option = "height='" . $this->resizeGlyph( $t, $is_cartouche, $total ) . "'";
						$temp .= self::renderGlyph( $t, $option );
					}
				} // end foreach

				$contentHtml .= WH_TD_S . $temp . WH_TD_E;
			}
			$contentHtml .= "\n";
			}

			if ( strlen( $contentHtml ) > 0 ) {
				$tableContentHtml .= $tableHtml . $contentHtml;
				$contentHtml = $tableHtml = "";
			}
		}

		if ( strlen( $tableContentHtml ) > 0 ) {
			$html .= WH_TABLE_S . "<tr>\n" . $tableContentHtml . "</tr>" . WH_TABLE_E;
		}

		return "<table class='mw-hiero-table mw-hiero-outer' dir='ltr'><tr><td>\n$html\n</td></tr></table>";
	}

	/**
	 * Returns a list of image files used by this extension
	 *
	 * @return array: list of files in format 'file' => array( width, height )
	 */
	public function getFiles() {
		return self::$files;
	}

	/**
	 * @return string: URL of images directory
	 */
	public static function getImagePath() {
		return WH_IMG_DIR;
	}

	/**
	 * Get glyph code from file name
	 *
	 * @param $file string: file name
	 * @return string: converted code
	 */
	public static function getCode( $file ) {
		return substr( $file, strlen( WH_IMG_PRE ), -( 1 + strlen( self::IMG_EXT ) ) );
	}
}
