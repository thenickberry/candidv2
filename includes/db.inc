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

    /*
		This file is used for doing all the MySQL work.

		It connects to the database, will build query
		statements based on whether an associated ID is passed
		and return it for processing, and also process any
		errors by specially formatting mysql_error() and the query
		statement (and die)
    */

    $db_link =  mysql_pconnect($config['db_host'],$config['db_user'],$config['db_pass'])
	or db_error("");

    function build_query($table,$array) {
	if (isset($array['id'])) {
	    $id = $array['id'];
	}
	$keys = array(); $values = array(); $params = array();
	foreach ($array as $key => $value) {
	    $value = addslashes($value);
	    if ($key != "id") {
		array_push($keys,$key);
		if ($key == 'pword') {
		    array_push($values,"OLD_PASSWORD('$value')");
		    array_push($params,"$key=OLD_PASSWORD('$value')");
		} else {
		    array_push($values,"\"$value\"");
		    array_push($params,"$key=\"$value\"");
		}
	    }
	}

	if (empty($id)) {
	    $query = "INSERT INTO $table (id,".implode($keys,",").") ".
			     "VALUES (NULL,".implode($values,",").")";
	} else{
	    $query = "UPDATE $table SET ".implode($params,",")." WHERE id=$id";
	}

	return $query;
    }

    function db_error($query) {
	echo "An error has occured and needs to be addressed by the admin<br><br".
	     "<table><tr><td>MySQL error:</td><td>".mysql_error()."</td></tr>".
	     "<tr><td>Query used:</td><td>$query</td></tr></table>";
	exit;
    }


?>
