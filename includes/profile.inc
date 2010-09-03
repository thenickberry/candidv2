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

    function getName($user_id,$plain=false) {
	global $config;
	if ($user_id != 0) {
	    $query = "SELECT CONCAT(fname,' ',lname) FROM user WHERE id=$user_id";
	    $info = mysql_fetch_row(mysql_query($query));
		if ($plain) {
			$data = $info[0];
		} else {
	    	$data = "<a href='${config['base_url']}/profile/view.php?user_id=$user_id'>$info[0]</a>";
		}
	} else {
	    $data = "<i>unassigned</i>";
	}
	return $data;
    }

    function getUsername($user_id) {
	$result = mysql_query("SELECT username FROM user WHERE id=$user_id");
	$data = mysql_fetch_row($result);
	return $data[0];
    }

    function getUserEmail($user_id) {
	$result = mysql_query("SELECT email FROM user WHERE id=$user_id");
	$data = mysql_fetch_row($result);
	return $data[0];
    }


    function listUsers() {
	global $config;
	$query = "SELECT id,email,concat(fname,' ',lname) full_name FROM user ORDER BY fname";
	$result = mysql_query($query) or db_error($query);
	$data = "<table>\n";
	while ($user = mysql_fetch_array($result)) {
	     $data .= "<tr><td>${user['full_name']}</td><td>[<a href='${config['base_url']}/profile/view.php?user_id=${user['id']}'>".
			"view</a> / <a href=mailto:${user['email']}>email</a>]</tr>\n";
	}
	$data .= "</table>";
	return $data;
    }

    function updateProfile() {
	global $config,$userinfo,$action;

	$sql_profile = $_POST['sql_profile'];
	if (isset($_POST['delete'])) {
	    $delete = $_POST['delete'];
	}

	if (empty($sql_profile)) { return "No data provided"; }

	if (empty($sql_profile['pword'])) {
	    unset($sql_profile['pword']);
	}

	if ($userinfo['access'] != '5') {
	    unset($sql_profile['access']);
	    unset($sql_profile['username']);
	}

	$content = "Successfully added ${sql_profile['fname']} ${sql_profile['lname']}";
	if (isset($delete)) {
	    if ($userinfo['access'] == 5) {
		$query = "DELETE FROM user WHERE id=${sql_profile['id']}";
		$result = mysql_query($query) or db_error($query);
		$content = "Deleted person successfully!";
	    } else {
		$content = "You do not have access to delete users";
	    }
	} else {
	    $query = build_query('user',$sql_profile);
	    mysql_query($query) or db_error($query);
	    $content = "Updated profile for <b>${sql_profile['fname']} ${sql_profile['lname']}</b> successfully!";
	}
	return $content;
    }

    function getPersonList($owner,$unass,$valid) {
	global $config,$userinfo;
	$data = "";
	$exclude = "onlist!='n'";
	if (isset($userinfo['name_disp']) && $userinfo['name_disp'] == "fname") {
	    $list = "CONCAT(fname,' ',lname)";
	} else {
	    $list = "CONCAT(lname,', ',fname)";
	}
	if (!empty($userinfo['name_disp'])) {
	    $order = $userinfo['name_disp'];
	    if ($userinfo['name_disp'] == 'fname') { $order .= ',lname'; }
	} else {
	    $order = 'lname,fname';
	}
	$query = "SELECT id, fname, lname, $list FROM user WHERE $exclude ORDER BY $order";
	$result = mysql_query($query) or db_error($query);

  
	$ownerSelect = '';
	while ($user = mysql_fetch_row($result)) {
	    if ($user[1] && !$user[2]) {
		$name = $user[1];
	    } else {
		$name = $user[3];
	    }
	    if ($owner == $user[0]) {
 		$ownerSelect = "  <option value=\"$user[0]\" selected>$name</option>";
	    } else {
		$data .= "  <option value=\"$user[0]\">$name</option>";
	    }
	}
	$data = $ownerSelect . $data . "\n";
	return $data;
    }

    function getPersonInfo($user_id) {
	global $config;
	$query = "SELECT id, fname, lname, concat(fname,' ',lname) full_name, username, pword, email, date_format(created,'%b %d, %Y') created, date_format(modified,'%b %d, %Y') modified, access, numrows, numcols, name_disp, update_notice, expire, user_image image FROM user WHERE id=$user_id";
	$result = mysql_query($query) or db_error($query);
	if (mysql_num_rows($result) == 0) {
		print "<br><br>User ID not found: ${user_id}";
		exit;
	}
	$data = mysql_fetch_array($result);
	return $data;
    }

    function getUserMMS($user_id) {
    	$query = "SELECT * FROM user_mms WHERE user_id=$user_id";
	$result = mysql_query($query) or db_error($query);
	$data = mysql_fetch_array($result);
	return $data;
    }

    function setup_person($fname,$lname) {
	$fname = ucwords($fname);
	$lname = ucwords($lname);
	$q_check = "SELECT fname,lname FROM user";
	$r_check = mysql_query($q_check) or db_error($q_check);
	while ($check = mysql_fetch_row($r_check)) {
	    list($c_fname,$c_lname) = $check;
	    $c_fname = strtolower($c_fname);
	    $c_lname = strtolower($c_lname);
	    $i_fname = strtolower($fname);
	    $i_lname = strtolower($lname);
	    if (($i_fname == $c_fname) && ($i_lname == $c_lname)) {
		if (!isset($confirm)) { $confirm = 'no'; }
	    }
	}
	if ($confirm != 'no') {
	    $query = "INSERT INTO user (id,fname,lname) ".
		     "VALUES (NULL,'$fname','$lname')";
	    mysql_query($query) or db_error($query);
	    echo "$fname $lname added successfully";
	} else {
	    echo 'Identical FIRST and LAST name already exists.  Please '.
		 'change it to make them unique.<br>';
	}
	exit;
    }

?>
