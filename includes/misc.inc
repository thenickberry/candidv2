<?php
    /*
	CANDIDv2 - Candid A New Digital Image Database
	Copyright (C) 2005 Nicholas Berry <nberry@scurvy.net>

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
    */


    if (!empty($_SERVER['QUERY_STRING'])) {
	if(strstr($_SERVER['QUERY_STRING'],"&")) {
	    list($cmd, $alt) = split("&", $_SERVER['QUERY_STRING'], 2);
	    $cmd = str_replace("=","",$cmd);
	} else { $cmd = $_SERVER['QUERY_STRING']; }
    } else { $cmd = ""; }

  $camera = array(
	"FinePix4700 ZOOM"    => "FujiFilm 4700",
	"Canon PowerShot S10" => "Canon PowerShot S10",
	"DMC-LC20"	    => "Panasonic DMC-LC20",
	"CYBERSHOT"	   => "Sony Cybershot",
	"C830L,D340R"	 => "Olympus D340",
	"E990"		=> "Nikon E990",
	"Kodak CLAS Digital Film Scanner / HR200" => "Kodak Film Scanner",
	"FinePix1300"	 => "FujiFilm 1300",
	"MX-1700ZOOM"	 => "FujiFilm 1700",
	"KODAK EASYSHARE CX6230 ZOOM DIGITAL CAMERA" => "Kodak CX6230",
	"KODAK EASYSHARE CX6200 DIGITAL CAMERA" => "Kodak CX6200",
	"EX-Z3"	       => "Casio EX-Z3"
	);

    if (isset($_SERVER['HTTP_REFERER'])) {
	$HTTP_REFERER = $_SERVER['HTTP_REFERER'];
    } else {
	$HTTP_REFERER = '';
    }


  foreach ($_POST as $key => $value) {
    if ($value == '' && $value != '0') { unset($$key); }
  }

  if (!empty($_GET['cat_id'])) {
	$cat_id = $_GET['cat_id'];
  } else {
	$cat_id = 0;
  }

  $content = '';
  $imagequery = '';
  $pid = '';
  $url = '';
  $title = '';
  $add_comment = '';
  $people = array();
  $user = '';
  $userid = '';
  $search_restr = '';
  $slides = '';

  $today = getdate();
  $referer = $HTTP_REFERER;

  if (isset($_GET['image_id'])) {
  	$image_id = $_GET['image_id'];
  }
  if (!isset($getPP)) {
  	$parentPath = "<li><a href='${config['base_url']}/main.php'>Main</a></li>";
  	if (isset($cat_id) && !isset($image_id)) {
    	$parentPath .= parentPath($cat_id,$cat_id);
  	} elseif (isset($_GET['image_id'])) {
    	$cat_id = getCat($image_id);
    	$parentPath .= parentPath($cat_id,$cat_id);
  	} else { $cat_id = 0; }
  }


    function get_vars($vars,$method) {
    	$strings = array();
        foreach ($vars as $var) {
	    if ($method == 'GET') {
		if (isset($_GET[$var])) {
		    $form_var = $_GET[$var];
		}
	    } elseif ($method == 'POST') {
		if (isset($_POST[$var])) {
		    $form_var = $_POST[$var];
		}
	    } else {
	    }

            if (isset($form_var)) {
                $$var = $form_var;
            } else {
                if (!isset($$var)) { $$var = ''; }
            }
	    #print "$var = ".$$var."<br>";
	    unset($form_var);
	    if (!empty($$var)) { 
		$strings[$var] = $$var;
	    }
        }
	return $strings;
    }


?>
