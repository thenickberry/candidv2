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

    if (isset($_COOKIE['candid'])) {
	$cookie = $_COOKIE['candid'];
    }
    if (isset($_COOKIE['theme'])) {
	$theme  = $_COOKIE['theme'];
    }
    if (isset($_COOKIE['last_query'])) {
	$last_query = $_COOKIE['last_query'];
    }
    
    function test() {
	global $cookie;
	if (!isset($cookie)) {
	    if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="CANDIDv2 auth"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Text to send if user hits Cancel button';
	    } else {
		$auth_status = setSession($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
		header("Location: /");
	    }
	} else {
		echo $cookie;
	}
    }
    
    if (!isset($cookie)) {
	if (isset($_POST['username'],$_POST['password'])) {
	    $auth_status = setSession($_POST['username'],$_POST['password']);
	}
    } else {
	if (isset($_POST['username'],$_POST['password'])) {
	    setcookie($config['cookieName'], '', time()-3600, $config['cookiePath']);
	    $auth_status = setSession($_POST['username'],$_POST['password']);
	} else {
	    $userinfo = getUserInfoSession($cookie);
	}
    }

    if (empty($userinfo['id'])) {
	$userinfo = array('access'  => '0',
			  'id'      => '0',
			  'numcols' => $config['defaultCols'],
			  'numrows' => $config['defaultRows']);
    }

    if (isset($theme)) {
	$userinfo['theme'] = $theme;
    }

    if (isset($last_query)) {
	$userinfo['last_query'] = $last_query;
    }

    if (isset($userinfo['init_disp'])) {
	list($userinfo['default_w'],$userinfo['default_h']) = split('x',$userinfo['init_disp']);
    } else {
	$userinfo['default_w'] = $config['default_w'];
	$userinfo['default_h'] = $config['default_h'];
    }
    
    
    function getCookie($_COOKIE) {
	$cookie = $_COOKIE['candid'];
	return $cookie;
    }
    
    function getUserInfoSession($sessionid) {
	global $config;
	$query = "SELECT s.user_id id, s.last_query last_query, u.fname, u.lname, u.username, u.access, u.numcols, u.numrows, u.name_disp, u.update_notice, u.theme, u.init_disp FROM user u, session s WHERE s.session='$sessionid' AND s.user_id=u.id";
	$result = mysql_query($query) or db_error($query);
	if (mysql_num_rows($result) == 0) { return; }
	$userinfo = mysql_fetch_array($result);

	$lastQuery = mysql_fetch_row(mysql_query("SELECT last_query FROM session WHERE session=$sessionid"));
	$userinfo['last_query'] = $lastQuery[0];

	if (empty($userinfo['numcols'])) { $userinfo['numcols'] = $config['defaultCols']; }
	if (empty($userinfo['numrows'])) { $userinfo['numrows'] = $config['defaultRows']; }
	$w = "7"; $d = "1"; $m = "30";
	$userinfo['update_notice'] = $$userinfo['update_notice'] * 86400;

	return $userinfo;
    }
    
    function generateSessionID() {
	srand ((double) microtime() * 1000000); // for session id generation
	$sid = rand(1000000000, 9999999999);
	return $sid;
    }
    
    function setSession($username,$password) {
	global $config,$_SERVER;
	if (empty($username) || empty($password)) {
	    header("Location: ${config['base_url']}/login.php?msg=err2");
	}
	
	$query = "SELECT id,username,pword,expire FROM user WHERE username='$username' AND pword=OLD_PASSWORD('$password')";
	$result = mysql_query($query);
	$user = mysql_fetch_array($result);
	if (empty($user['pword'])) {
	    header("Location: ${config['base_url']}/login.php?msg=err1");
	}

	$sessionid = generateSessionID();
	setcookie($config['cookieName'], "", time()-3600, $config['cookiePath']);
	if ($user['expire'] == "never") {
	    $expire = time()+99999999;
	    setcookie($config['cookieName'], $sessionid, $expire, $config['cookiePath'])
		or die("could not set cookie");
	} else {
	    setcookie($config['cookieName'], $sessionid, 0, $config['cookiePath'])
		or die("could not set cookie1");
	}
	if (isset($expire)) {
	    $expiredate = date("Y-m-d H:i:s", $expire);
	} else {
	    $expiredate = "0000-00-00 00:00:00";
	}
	$query = "INSERT INTO session (session, user_id, expire, ip) VALUES ($sessionid,$user[id],'$expiredate','${_SERVER['REMOTE_ADDR']}')";
	mysql_query($query) or db_error($query);
	return "ok";
    }  
    
    function expireSession($session) {
	global $config;
	$query = "DELETE FROM session WHERE session='$session'";
	mysql_query($query) or db_error($query);
	setcookie($config['cookieName'],"",time()-3600);
    }
    
    function access_check($user_access,$min_access) {
    	if ($user_access < $min_access) {
    	    echo "You've ventured beyond your level of access.";
    	    exit;
    	}
    }
?>
