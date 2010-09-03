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

    include("../config.inc");
    $q = "SELECT id,content_type,thumb_width,thumb_height FROM image_info";
    $r = mysql_query($q);
    while ($i = mysql_fetch_row($r)) {
		if ($i[2] && $i[3]) { continue; }
		$ext = str_replace('image/','',$i[1]);
		$d = mysql_fetch_row(mysql_query("SELECT data FROM image_thumb WHERE image_id=$i[0]"));
    	$f = "thumb-$i[0].${ext}";
    	$h = fopen($f,'a');
		fwrite($h,$d[0]);
		fclose($h);
		if (file_exists($f)) {
			$a = @getimagesize($f);
			if (!$a) { die("Failed to getimagesize on $f"); }
			mysql_query("UPDATE image_info SET thumb_width=$a[0],thumb_height=$a[1] WHERE id=$i[0]");
			unlink($f);
		} else {
			die("$f not found!");
		}
    }
?>
