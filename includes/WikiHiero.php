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

namespace WikiHiero;

use Config;
use Html;
use MWException;
use OutputPage;
use Parser;
use RequestContext;

class WikiHiero {
	public const IMAGE_EXT = 'png';
	private const IMAGE_PREFIX = 'hiero_';

	/** Use default scale */
	private const DEFAULT_SCALE = -1;
	private const CARTOUCHE_WIDTH = 2;
	private const IMAGE_MARGIN = 1;
	private const MAX_HEIGHT = 44;

	private const TABLE_START = '<table class="mw-hiero-table">';

	private $scale = 100;
	private $config;

	/** @var string[] */
	private static $phonemes;
	/** @var string[] */
	private static $prefabs;
	/** @var int[][] */
	private static $files;

	/**
	 * @param Config|null $config
	 * @throws MWException
	 */
	public function __construct( Config $config = null ) {
		$this->config = $config ?: RequestContext::getMain()->getConfig();
		self::loadData();
	}

	/**
	 * Loads hieroglyph information
	 * @suppress PhanUndeclaredVariable,PhanTypeMismatchPropertyProbablyReal Phan doesn't understand require_once
	 */
	private static function loadData() {
		if ( self::$phonemes ) {
			return;
		}
		require_once dirname( __DIR__ ) . '/data/tables.php';
		self::$phonemes = $wh_phonemes;
		self::$prefabs = $wh_prefabs;
		self::$files = $wh_files;
	}

	/**
	 * Parser callback for <hiero> tag
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @return string
	 */
	public static function parserHook( $input, $args, $parser ) {
		$hiero = new WikiHiero();
		$parser->getOutput()->addModuleStyles( [ 'ext.wikihiero' ] );
		// Strip newlines to avoid breakage in the wiki parser block pass
		return str_replace( "\n", " ", $hiero->render( $input ) );
	}

	/**
	 * @return int Scale of overall hieroglyphic block in percents
	 */
	public function getScale() {
		return $this->scale;
	}

	/**
	 * Sets the scale of overall hieroglyphic block in percents
	 *
	 * @param int $scale
	 */
	public function setScale( $scale ) {
		$this->scale = $scale;
	}

	/**
	 * Renders a glyph
	 *
	 * @param string $glyph glyph's code to render
	 * @param int|null $height glyph size in pixels or null to omit
	 * @return string a string to add to the stream
	 */
	private function renderGlyph( $glyph, $height = null ) {
		$imageClass = null;
		if ( $this->isMirrored( $glyph ) ) {
			$imageClass = 'mw-mirrored';
		}
		$glyph = $this->extractCode( $glyph );

		if ( $glyph == '..' ) {
			// Render void block
			return $this->renderVoidBlock( self::MAX_HEIGHT );
		}
		if ( $glyph == '.' ) {
			// Render half-width void block
			return $this->renderVoidBlock( self::MAX_HEIGHT / 2 );
		}

		if ( $glyph == '<' || $glyph == '>' ) {
			// Render cartouches
			return $this->renderGlyphImage( $glyph, self::MAX_HEIGHT, null, $imageClass );
		}

		return $this->renderGlyphImage( $glyph, $height, self::IMAGE_MARGIN, $imageClass );
	}

	/**
	 * Renders a glyph into an <img> tag
	 *
	 * @param string $glyph Glyph to render
	 * @param int|null $height Image height, if null don't set explicitly
	 * @param int|null $margin Margin, if null don't set
	 * @param string|null $class Class for <img> tag
	 * @return string Rendered HTML
	 */
	private function renderGlyphImage( $glyph, $height = null, $margin = null, $class = null ) {
		if ( array_key_exists( $glyph, self::$phonemes ) ) {
			$code = self::$phonemes[$glyph];
			$fileName = $code;
			// Don't show image name for cartouches and such
			$title = preg_match( '/^[A-Za-z0-9]+$/', $glyph ) ? "{$code} [{$glyph}]" : $glyph;
		} else {
			$fileName = $title = $glyph;
		}
		if ( !array_key_exists( $fileName, self::$files ) ) {
			return htmlspecialchars( $glyph );
		}

		$style = $margin === null ? null : "margin: {$margin}px;";
		$attribs = [
			'class' => $class,
			'style' => $style,
			'src' => $this->getImageUrl( $fileName ),
			'height' => $height,
			'title' => $title,
			'alt' => $glyph,
		];
		return Html::element( 'img', $attribs );
	}

	/**
	 * Returns HTML for a void block
	 * @param int $width
	 * @return string
	 */
	private function renderVoidBlock( $width ) {
		$width = intval( $width );
		return Html::rawElement(
			'table',
			[
				'class' => 'mw-hiero-table',
				'style' => "width: {$width}px;",
			],
			'<tr><td>&#160;</td></tr>'
		);
	}

	private function getImageUrl( $fileName ) {
		$url = self::getImagePath() . self::IMAGE_PREFIX . $fileName . '.' . self::IMAGE_EXT;
		return OutputPage::transformResourcePath( $this->config, $url );
	}

	private function isMirrored( $glyph ) {
		return substr( $glyph, -1 ) == '\\';
	}

	/**
	 * Extracts hieroglyph code from glyph, e.g. A1\ --> A1
	 *
	 * @param string $glyph
	 * @return string
	 */
	private function extractCode( $glyph ) {
		return preg_replace( '/\\\\.*$/', '', $glyph );
	}

	/**
	 * Resize a glyph
	 *
	 * @param string $item glyph code
	 * @param bool $is_cartouche true if glyph is inside a cartouche
	 * @param int $total total size of a group for multi-glyph block
	 * @return int size
	 */
	private function resizeGlyph( $item, $is_cartouche = false, $total = 0 ) {
		$item = $this->extractCode( $item );
		if ( array_key_exists( $item, self::$phonemes ) ) {
			$glyph = self::$phonemes[$item];
		} else {
			$glyph = $item;
		}

		$margin = 2 * self::IMAGE_MARGIN;
		if ( $is_cartouche ) {
			$margin += 2 * self::CARTOUCHE_WIDTH;
		}

		if ( array_key_exists( $glyph, self::$files ) ) {
			$height = $margin + self::$files[$glyph][1];
			if ( $total ) {
				if ( $total > self::MAX_HEIGHT ) {
					return intval( $height * self::MAX_HEIGHT / $total ) - $margin;
				} else {
					return $height - $margin;
				}
			} else {
				if ( $height > self::MAX_HEIGHT ) {
					return intval( self::MAX_HEIGHT * self::MAX_HEIGHT / $height ) - $margin;
				} else {
					return $height - $margin;
				}
			}
		}

		return self::MAX_HEIGHT - $margin;
	}

	/**
	 * Render hieroglyph text
	 *
	 * @param string $hiero text to convert
	 * @param int $scale global scale in percentage (default = 100%)
	 * @param bool $line use line (default = false)
	 * @return string converted code
	 */
	public function render( $hiero, $scale = self::DEFAULT_SCALE, $line = false ) {
		if ( $scale != self::DEFAULT_SCALE ) {
			$this->setScale( $scale );
		}

		$html = "";

		if ( $line ) {
			$html .= "<hr />\n";
		}

		$tokenizer = new HieroTokenizer( $hiero );
		$blocks = $tokenizer->tokenize();
		$contentHtml = $tableHtml = $tableContentHtml = "";
		$is_cartouche = false;

		// ------------------------------------------------------------------------
		// Loop into all blocks
		foreach ( $blocks as $code ) {
			// simplest case, the block contain only 1 code -> render
			if ( count( $code ) == 1 ) {
				if ( $code[0] == '!' ) {
					// end of line
					$tableHtml = '</tr></table>' . self::TABLE_START . "<tr>\n";
					if ( $line ) {
						$contentHtml .= "<hr />\n";
					}

				} elseif ( strstr( $code[0], '<' ) ) {
					// start cartouche
					$contentHtml .= '<td>' . $this->renderGlyph( $code[0] ) . '</td>';
					$is_cartouche = true;
					$contentHtml .= '<td>' .
						self::TABLE_START . "<tr><td class=\"mw-hiero-box\" style=\"height: " .
						self::CARTOUCHE_WIDTH . "px;\"></td></tr><tr><td>" . self::TABLE_START .
						"<tr>";

				} elseif ( strstr( $code[0], '>' ) ) {
					// end cartouche
					$contentHtml .= "</tr></table></td></tr><tr><td class=\"mw-hiero-box\" " .
						"style=\"height: " . self::CARTOUCHE_WIDTH .
						'px;"></td></tr></table></td>';
					$is_cartouche = false;
					$contentHtml .= '<td>' . $this->renderGlyph( $code[0] ) . '</td>';

				} elseif ( $code[0] != "" ) {
					// assume it's a glyph or '..' or '.'
					$contentHtml .= '<td>' . $this->renderGlyph(
						$code[0],
						$this->resizeGlyph( $code[0], $is_cartouche )
					) . '</td>';
				}

			// block contains more than 1 glyph
			} else {
				// convert all codes into '&' to test prefabs glyph
				$prefabs = "";
				foreach ( $code as $t ) {
					if ( preg_match( "/[*:!()]/", $t[0] ) ) {
						$prefabs .= "&";
					} else {
						$prefabs .= $t;
					}
				}

				// test if block exists in the prefabs list
				if ( in_array( $prefabs, self::$prefabs ) ) {
					$contentHtml .= '<td>' . $this->renderGlyph(
						$prefabs,
						$this->resizeGlyph( $prefabs, $is_cartouche )
					) . '</td>';

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
					}

					if ( $height > $line_max ) {
						$line_max = $height;
					}

					$total += $line_max;

					// render all glyph into the block
					$block = "";
					foreach ( $code as $t ) {
						if ( $t == ":" ) {
							$block .= "<br />";

						} elseif ( $t == "*" ) {
							$block .= " ";

						} else {
							// resize the glyph according to the block total height
							$block .= $this->renderGlyph(
								$t,
								$this->resizeGlyph( $t, $is_cartouche, $total )
							);
						}
					}

					$contentHtml .= '<td>' . $block . '</td>';
				}
				$contentHtml .= "\n";
			}

			if ( strlen( $contentHtml ) > 0 ) {
				$tableContentHtml .= $tableHtml . $contentHtml;
				$contentHtml = $tableHtml = "";
			}
		}

		if ( strlen( $tableContentHtml ) > 0 ) {
			$html .= self::TABLE_START . "<tr>\n" . $tableContentHtml . '</tr></table>';
		}

		$style = null;
		if ( $this->scale != 100 ) {
			$ratio = floatval( $this->scale ) / 100;
			$style = "-ms-transform: scale($ratio,$ratio); -webkit-transform: scale($ratio,$ratio);"
				. " -o-transform: scale($ratio,$ratio); transform: scale($ratio,$ratio);";
		}

		return Html::rawElement(
			'table',
			[
				'class' => 'mw-hiero-table mw-hiero-outer',
				'dir' => 'ltr',
				'style' => $style,
			],
			"<tr><td>\n$html\n</td></tr>"
		);
	}

	/**
	 * Returns a list of image files used by this extension
	 *
	 * @return array list of files in format 'file' => [ width, height ]
	 */
	public function getFiles() {
		return self::$files;
	}

	/**
	 * @return string URL of images directory
	 */
	public static function getImagePath() {
		global $wgExtensionAssetsPath;
		// @phan-suppress-next-line PhanPossiblyUndeclaredVariable Caused by taint-check
		return "$wgExtensionAssetsPath/wikihiero/img/";
	}

	/**
	 * Get glyph code from file name
	 *
	 * @param string $file file name
	 * @return string converted code
	 */
	public static function getCode( $file ) {
		return substr( $file, strlen( self::IMAGE_PREFIX ), -( 1 + strlen( self::IMAGE_EXT ) ) );
	}
}
