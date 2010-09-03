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

    function make_history($cmd,$mesg,$table_id,$table) {
	global $userinfo,$_SERVER;

	if (!isset($_SERVER['REMOTE_ADDR'])) {
		$_SERVER['REMOTE_ADDR'] = 'email';
	}

	$query = "INSERT INTO history VALUES (NULL,'${userinfo['id']}','$cmd','$mesg',NOW(),'${_SERVER['REMOTE_ADDR']}','$table_id','$table')";
	mysql_query($query) or db_error($query);
    }
  
    function showHistory($table_id,$table) {
	global $config;
	$row = 0;
	print "<table><td valign=top><img src='${config['base_url']}/main.php?showImage&image_id=$table_id&thumb=yes'></td><td valign=top'>".
	      "<table style='border:1px solid #000' cellspacing=0 cellpadding=10>\n".
	      "<tr><th>user</th><th>cmd</th><th>ip address</th><th>date</th><th width=150>extra</th></tr>\n";
	$query = "SELECT h.history_id,user_id,h.action,h.ip_addr,h.datetime,h.message FROM history h WHERE h.table_id=$table_id AND h.table_name='$table' ORDER BY h.datetime desc";
	$results = mysql_query($query) or db_error($query);
	while ($history = mysql_fetch_row($results)) {
	    if ($history[1] != 0) {
		$history[1] = getName($history[1]);
	    } else {
		$history[1] = "anonymous";
	    }
	    if ($row == 0) {
		$bgcolor = "#eeeeee"; $row = 1;
	    } else {
		$bgcolor = "#ffffff"; $row = 0;
	    }
	    print "<tr valign=top bgcolor=$bgcolor><td>$history[1]</td><td>$history[2]</td><td><a href='${config['base_url']}/history_view.php?ip_addr=$history[3]'>$history[3]</a></td><td>$history[4]</td><td width=150>$history[5]</td></tr>\n";
	}
	print "</table></td></table>";
    }

?>
