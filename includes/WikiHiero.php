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

use MediaWiki\Config\Config;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;

class WikiHiero {
	public const string IMAGE_EXT = 'png';
	private const string IMAGE_PREFIX = 'hiero_';

	private const int CARTOUCHE_WIDTH = 2;
	private const int IMAGE_MARGIN = 1;
	private const int MAX_HEIGHT = 44;

	private const string TABLE_START = '<table class="mw-hiero-table">';

	private Config $config;

	/** @var string[] */
	private static array $phonemes;
	/** @var string[] */
	private static array $prefabs;
	/** @var array<string, array{0:int,1:int}> */
	private static array $files;

	public function __construct( ?Config $config = null ) {
		$this->config = $config ?: MediaWikiServices::getInstance()->getMainConfig();
		self::loadData();
	}

	/**
	 * Loads hieroglyph information
	 * @suppress PhanUndeclaredVariable,PhanTypeMismatchPropertyReal Phan doesn't understand require_once
	 */
	private static function loadData(): void {
		// @phan-suppress-next-line PhanRedundantCondition Property may not be initialized yet
		if ( isset( self::$phonemes ) ) {
			return;
		}
		require_once dirname( __DIR__ ) . '/data/tables.php';
		self::$phonemes = $wh_phonemes;
		self::$prefabs = $wh_prefabs;
		self::$files = $wh_files;
	}

	/**
	 * Parser callback for <hiero> tag
	 */
	public static function parserHook( ?string $input, array $args, Parser $parser ): string {
		// T388339 - self closed <hiero/> is a no-op, and $input is null
		if ( $input === null ) {
			return '';
		}

		$hiero = new WikiHiero();
		$parser->getOutput()->addModuleStyles( [ 'ext.wikihiero' ] );
		$parser->addTrackingCategory( 'wikihiero-usage-tracking-category' );
		// Strip newlines to avoid breakage in the wiki parser block pass
		return str_replace( "\n", ' ', $hiero->render( $input ) );
	}

	/**
	 * Renders a glyph
	 *
	 * @param string $glyph glyph's code to render
	 * @param int|null $height glyph size in pixels or null to omit
	 * @return string a string to add to the stream
	 */
	private function renderGlyph( string $glyph, $height = null ): string {
		// Support skins with night theme.
		$imageClass = 'skin-invert';
		if ( $this->isMirrored( $glyph ) ) {
			$imageClass .= ' mw-mirrored';
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
	 */
	private function renderVoidBlock( int $width ): string {
		return Html::rawElement(
			'table',
			[
				'class' => 'mw-hiero-table',
				'style' => "width: {$width}px;",
			],
			'<tr><td>&#160;</td></tr>'
		);
	}

	private function getImageUrl( string $fileName ): string {
		$url = self::getImagePath() . self::IMAGE_PREFIX . $fileName . '.' . self::IMAGE_EXT;
		return OutputPage::transformResourcePath( $this->config, $url );
	}

	private function isMirrored( string $glyph ): bool {
		return str_ends_with( $glyph, '\\' );
	}

	/**
	 * Extracts hieroglyph code from glyph, e.g. A1\ --> A1
	 */
	private function extractCode( string $glyph ): string {
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
	 * @return string converted code
	 */
	public function render( string $hiero ): string {
		$html = '';

		$tokenizer = new HieroTokenizer( $hiero );
		$blocks = $tokenizer->tokenize();
		$contentHtml = $tableHtml = $tableContentHtml = '';
		$is_cartouche = false;

		// ------------------------------------------------------------------------
		// Loop into all blocks
		foreach ( $blocks as $code ) {
			// simplest case, the block contain only 1 code -> render
			if ( count( $code ) == 1 ) {
				if ( $code[0] == '!' ) {
					// end of line
					$tableHtml = '</tr></table>' . self::TABLE_START . "<tr>\n";
				} elseif ( str_contains( $code[0], '<' ) ) {
					// start cartouche
					$contentHtml .= '<td>' . $this->renderGlyph( $code[0] ) . '</td>';
					$is_cartouche = true;
					$contentHtml .= '<td>' .
						self::TABLE_START . "<tr><td class=\"mw-hiero-box\" style=\"height: " .
						self::CARTOUCHE_WIDTH . "px;\"></td></tr><tr><td>" . self::TABLE_START .
						"<tr>";

				} elseif ( str_contains( $code[0], '>' ) ) {
					// end cartouche
					$contentHtml .= "</tr></table></td></tr><tr><td class=\"mw-hiero-box\" " .
						"style=\"height: " . self::CARTOUCHE_WIDTH .
						'px;"></td></tr></table></td>';
					$is_cartouche = false;
					$contentHtml .= '<td>' . $this->renderGlyph( $code[0] ) . '</td>';

				} elseif ( $code[0] != '' ) {
					// assume it's a glyph or '..' or '.'
					$contentHtml .= '<td>' . $this->renderGlyph(
						$code[0],
						$this->resizeGlyph( $code[0], $is_cartouche )
					) . '</td>';
				}

			// block contains more than 1 glyph
			} else {
				// convert all codes into '&' to test prefabs glyph
				$prefabs = '';
				foreach ( $code as $t ) {
					if ( preg_match( '/[*:!()]/', $t[0] ) ) {
						$prefabs .= '&';
					} else {
						$prefabs .= $t;
					}
				}

				// test if block exists in the prefabs list
				if ( in_array( $prefabs, self::$prefabs, true ) ) {
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
						if ( $t == ':' ) {
							if ( $height > $line_max ) {
								$line_max = $height;
							}
							$total += $line_max;
							$line_max = 0;

						} elseif ( $t == '*' ) {
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
					$block = '';
					foreach ( $code as $t ) {
						if ( $t == ':' ) {
							$block .= '<br />';

						} elseif ( $t == '*' ) {
							$block .= ' ';

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
				$contentHtml = $tableHtml = '';
			}
		}

		if ( strlen( $tableContentHtml ) > 0 ) {
			$html .= self::TABLE_START . "<tr>\n" . $tableContentHtml . '</tr></table>';
		}

		return Html::rawElement(
			'table',
			[
				'class' => 'mw-hiero-table mw-hiero-outer',
				'dir' => 'ltr',
			],
			"<tr><td>\n$html\n</td></tr>"
		);
	}

	/**
	 * Returns a list of image files used by this extension
	 *
	 * @return array<string, array{0:int,1:int}> list of files in format 'file' => [ width, height ]
	 */
	public function getFiles(): array {
		return self::$files;
	}

	/**
	 * @return string URL of images directory
	 */
	public static function getImagePath(): string {
		global $wgExtensionAssetsPath;
		return "$wgExtensionAssetsPath/wikihiero/img/";
	}

	/**
	 * Get glyph code from file name
	 *
	 * @return string converted code
	 */
	public static function getCode( string $file ): string {
		return substr( $file, strlen( self::IMAGE_PREFIX ), -( 1 + strlen( self::IMAGE_EXT ) ) );
	}
}
