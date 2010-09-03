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
    $q = "SELECT id FROM image_info ORDER BY id";
    $r = mysql_query($q);
    while ($i = mysql_fetch_row($r)) {
		list($id) = $i;
		$d = mysql_fetch_row(mysql_query("SELECT data FROM image_file WHERE image_id=${id}"));
    	$f = "${id}.jpg";
    	$h = fopen($f,'a');
		fwrite($h,$d[0]);
		fclose($h);
		#$a = getimagesize($f);
		$md5 = md5_file($f);
		mysql_query("UPDATE image_info SET md5_sum='${md5}' WHERE id=${id}");
		unlink($f);
    }
?>
