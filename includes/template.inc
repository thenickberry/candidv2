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

    function css_top($title) {
	global $config,$userinfo,$parentPath,$slides,$bare,$_COOKIE;
	$parent_path = $parentPath;
	$themes = array();
	if ($dir = @opendir('htdocs/themes')) {
	    while (($file = readdir($dir)) !== false) {
		if(!strstr($file,".")) {
		    if (empty($userinfo['theme'])) {
			if ($file == $config['default_theme']) {
			    $rel = "stylesheet";
			} else {
			    $rel = "alternate stylesheet";
			}
		    } else {
			if ($file == $userinfo['theme']) {
			    $rel = "stylesheet";
			} else {
			    $rel = "alternate stylesheet";
			}
		    }
		    $tag = "<link rel='$rel' href='${config['base_url']}/themes/$file/format.css' type='text/css' title='$file'>";

		    if ($rel == 'stylesheet') {
			array_unshift($themes,$tag);
		    } else {
			array_push($themes,$tag);
		    }
		}
	    }
	}
	$css_themes = implode($themes,"\n\t\t") . "\n";

	if (strstr($_SERVER['REQUEST_URI'],'slideshow.php')) {
	    $head_add = "<!-- From http://slideshow.barelyfitz.com/ -->\n".
			"\t\t<SCRIPT type='text/javascript' src='${config['base_url']}/js/slideshow.js'></SCRIPT>\n".
			"\t\t<SCRIPT type='text/javascript'>\n".
			"<!--\n".
			"SLIDES = new slideshow(\"SLIDES\");\n".
			"SLIDES.prefetch = 2;\n".
			"SLIDES.repeat = false;\n\n".
			$slides.
			"//-->\n</SCRIPT>\n";
	    $body_add = "onLoad=\"SLIDES.update()\"";
	} else {
	    $head_add = "";
	    $body_add = "";
	}

	$edit_mesg = '';
	$prefs_mesg = '';

	if ($userinfo['access'] != '0') {
	    $login_mesg_l = "Welcome, ${userinfo['fname']} ${userinfo['lname']}!";
	    $prefs_mesg  = "<a href='${config['base_url']}";
	    $prefs_mesg .= "/profile/edit.php?user_id=${userinfo['id']}'>prefs</a>";
	    if (empty($_COOKIE['edit'])) {
	    	$edit_mesg  = "<a href='javascript:void()' ";
			$edit_mesg .= "onclick='setcookie(\"edit\",\"on\",\"1\");";
			$edit_mesg .= "window.location.reload()'>edit</a>";
	    } else {
	    	$edit_mesg  = "<a href='javascript:void()' ";
			$edit_mesg .= "onclick='setcookie(\"edit\",\"on\",\"-1\");";
			$edit_mesg .= "window.location.reload()'>no edit</a>";
	    }
	    $login_mesg_r = "<a href='${config['base_url']}/main.php?logout'>logout</a> ";
	    $add_images = "<li><a href='${config['base_url']}/image/add.php'>Upload</a></li>";
	} else {
	    $login_mesg_l = "<a href='${config['base_url']}/login.php'>sign in</a>";
	    $login_mesg_r = "<a href='${config['base_url']}/register.php'>register</a>";
	    $add_images = "";
	}
	print <<<END
<html>
	<head>
		<title> $title </title>

		$css_themes

		<script src="/mint/?js" type="text/javascript"></script>

		<script src='${config['base_url']}/js/functions.js'></script>

		<!-- From http://www.treeview.net -->
		<script src='${config['base_url']}/js/Treeview/ua.js'></script>
		<script src='${config['base_url']}/js/Treeview/ftiens4.js'></script>

		<!-- From http://www.dynarch.com/projects/calendar/ -->
		<script src='${config['base_url']}/js/jscalendar-0.9.6/calendar.js'></script>
		<script src='${config['base_url']}/js/jscalendar-0.9.6/lang/calendar-en.js'></script>
		<script src='${config['base_url']}/js/jscalendar-0.9.6/calendar-setup.js'></script>

		$head_add

	</head>
	<body $body_add>
END;

    if ($bare != 1) {
	print <<<END

	    <div id=top>

		<div id=login>
			<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> $login_mesg_l </li>
			<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> $edit_mesg </li>
			<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> $prefs_mesg </li>
			<li style='display:inline;list-style:none;padding:0px 8px;'> $login_mesg_r </li>
		</div>

		<div id=title>
			<a href='${config['base_url']}/index.php'>CANDIDv2</a>
		</div>

		<div id=menu>
		    <ul>
			<li><a href='${config['base_url']}/main.php?browse'>Browse</a></li>
			$add_images
			<li><a href='${config['base_url']}/search.php'>Search</a></li>
			<li><a href='${config['base_url']}/profile/view.php'>Users</a></li>
		    </ul>
		</div>

		<div id=path>
			$parent_path
		</div>

	    </div>
END;
    }

	print "<div id=body><center>";
     }

    function css_end() {
	global $config;
	$path = $config['base_dir'] . "/htdocs/themes";
	#print "\t\t<div style='width:200px;text-align:right;position:absolute;bottom:5px;right:5px;font-size:12px'>themes: ";
        if ($dir = @opendir($path)) {
            while (($file = readdir($dir)) !== false) {
		$stylesheet = $path . '/' . $file . '/format.css';
		if (file_exists($stylesheet)) {
			#print "\t\t\t<a href=${config['base_url']}/change_theme.php?theme=${file}>${file}</a>&nbsp;\n";
		}
	    }
	    closedir($dir);
	}
	print "\t\t</div>\n";
	print "\t</body>\n</html>\n";
    }

?>
