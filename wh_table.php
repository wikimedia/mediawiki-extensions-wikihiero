<?php

//////////////////////////////////////////////////////////////////////////
//
// WikiHiero - A PHP convert from text using "Manual for the encoding of 
// hieroglyphic texts for computer input" syntax to HTML entities (table and
// images).
//
// Copyright (C) 2004 Guillaume Blanchard (Aoineko)
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//////////////////////////////////////////////////////////////////////////

  include "wh_language.php";
  include "wikihiero.php";

  if(array_key_exists("table", $_GET))
    $table = $_GET["table"];
  else
    $table = "All"; 

  if(array_key_exists("lang", $_GET))
    $lang = $_GET["lang"];
  else
    $lang = "fr"; 

  function WH_Text( $index )
  {
    global $wh_language;
    global $lang;

    if(isset($wh_language[$index]))
    {
      if(isset($wh_language[$index][$lang]))
        return $wh_language[$index][$lang];
      else
        return $wh_language[$index]["en"];
    }
    return "";
  }

?>
<html lang=<?php echo $lang; ?>>
  <head>
    <title><?php echo "$table - ".WH_Text($table); ?> - WikiHiero</title>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <link rel="shortcut icon" href="/favicon.ico">
  </head>
  <body bgcolor="#DDDDDD">

    <?php

    echo "<b>$table</b> - ".WH_Text($table)."<br><br>";

    if($dh = opendir(WH_IMG_DIR)) 
    {
      while(($file = readdir($dh)) !== false) 
      {
        if($table == "All")
        {
          $code = WH_GetCode($file);
					if(in_array($code, $wh_phonemes))
            echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code [".array_search($code, $wh_phonemes)."]\">\n";
					else
            echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code\">\n";
        }
        else if($table == "Phoneme")
        {
          $code = WH_GetCode($file);
          if(in_array($code, $wh_phonemes))
            echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code [".array_search($code, $wh_phonemes)."]\">\n";
        }
        else if($table == "Aa")
        {
          $code = WH_GetCode($file);
					if((substr($code, 0, 2) == $table) && ctype_digit($code[2]))
					{
						if(in_array($code, $wh_phonemes))
							echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code [".array_search($code, $wh_phonemes)."]\">\n";
						else
							echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code\">\n";
					}
        }
        else
        {
          $code = WH_GetCode($file);
					if(($code[0] == $table) && ctype_digit($code[1]))
					{
						if(in_array($code, $wh_phonemes))
							echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code [".array_search($code, $wh_phonemes)."]\">\n";
						else
							echo "<img src=\"".WH_IMG_DIR."$file\" title=\"$code\">\n";
					}
        }
      }
      closedir($dh);
    }

    ?>

  </body>
</html>