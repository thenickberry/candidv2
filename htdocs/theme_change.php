<?php

	include("../config.inc");
	if ($userinfo['id'] != "0") {
		mysql_query("UPDATE user SET theme='${_GET['theme']}' WHERE id='${userinfo['id']}'");
	}
	setcookie('theme',$_GET['theme'],time()+(999*999));
	header("Location: ${_SERVER['HTTP_REFERER']}");
?>
