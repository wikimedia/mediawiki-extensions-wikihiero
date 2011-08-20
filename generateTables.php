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

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class GenerateWikiHieroTables extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate tables with hieroglyph information';
		// if ( !MWInit::classExists( 'WikiHiero' ) ) {
			// $this->error( "Please install WikiHiero first!\n", true );
		// }
	}
	
	public function execute() {
		$wh_prefabs = "\$wh_prefabs = array(\n";
		$wh_files   = "\$wh_files   = array(\n";

		$imgDir = dirname( __FILE__ ) . '/img/';

		if ( is_dir( $imgDir ) ) {
			$dh = opendir( $imgDir );
			if ( $dh ) {
				while ( ( $file = readdir( $dh ) ) !== false ) {
					if ( stristr( $file, WikiHiero::IMG_EXT ) ) {
						list( $width, $height, $type, $attr ) = getimagesize( $imgDir . $file );
						$wh_files .= "  \"" . WikiHiero::getCode( $file ) . "\" => array( $width, $height ),\n";
						if ( strchr( $file, '&' ) ) {
							$wh_prefabs .= "  \"" . WikiHiero::getCode( $file ) . "\",\n";
						}
					}
				}
				closedir( $dh );
			}
		} else {
			$this->error( "Images directory $imgDir not found!\n", true );
		}

		$wh_prefabs .= ");";
		$wh_files .= ");";

		$file = fopen( 'wh_list.php', 'w+' );
		fwrite( $file, "<?php\n\n" );
		fwrite( $file, '// File created by generateTables.php version ' . WikiHiero::VERSION . "\n" );
		fwrite( $file, '// ' . date( 'Y/m/d \a\t H:i' ) . "\n\n" );
		fwrite( $file, "global \$wh_prefabs, \$wh_files;\n\n" );
		fwrite( $file, "$wh_prefabs\n\n" );
		fwrite( $file, "$wh_files\n\n" );
		fclose( $file );
	}
}

$maintClass = "GenerateWikiHieroTables";
require_once( RUN_MAINTENANCE_IF_MAIN );
