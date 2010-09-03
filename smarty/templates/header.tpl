<html>
	<head>
		<title> CANDIDv2 ~ {$title} </title>

		<link rel='stylesheet' href='http://candid.scurvy.net/themes/default/format.css' type='text/css' title='default'>
		<link rel='alternate stylesheet' href='http://candid.scurvy.net/themes/classic/format.css' type='text/css' title='classic'>

		<script src='{$http_base}/js/functions.js'></script>

		<!-- From http://www.treeview.net -->
		<script src='{$http_base}/js/Treeview/ua.js'></script>
		<script src='{$http_base}/js/Treeview/ftiens4.js'></script>

		<!-- From http://www.dynarch.com/projects/calendar/ -->
		<script src='{$http_base}/js/jscalendar-0.9.6/calendar.js'></script>
		<script src='{$http_base}/js/jscalendar-0.9.6/lang/calendar-en.js'></script>
		<script src='{$http_base}/js/jscalendar-0.9.6/calendar-setup.js'></script>

		<script src='{$http_base}/js/ajax.js'></script>

		

	</head>
	<body style='background:#f0eada'>
	    <div id=top>

			<div id=login>
{if $logged_in}
				<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> Welcome, {$user.fname} {$user.lname}! </li>
				<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> <a href='{$http_base}/profile/edit.php?user_id={$user.id}'>prefs</a> </li>
				<li style='display:inline;list-style:none;padding:0px 8px;'> <a href='{$http_base}/main.php?logout'>logout</a>  </li>
{else}
				<li style='display:inline;list-style:none;padding:0px 8px;border-right: 1px solid #eee'> <a href='{$http_base}/login.php'>sign in</a> </li>
				<li style='display:inline;list-style:none;padding:0px 8px;'> <a href='{$http_base}/register.php'>register</a> </li>
{/if}
			</div>

			<div id=title>
				<a href='{$http_base}/index.php'>CANDIDv2</a>
			</div>

			<div id=menu>
		    	<ul>
					<li><a href='{$http_base}/browse'>Browse</a></li>
{if $logged_in}
					<li><a href='{$http_base}/image/add.php'>Upload</a></li>
{/if}
					<li><a href='{$http_base}/search.php'>Search</a></li>
					<li><a href='{$http_base}/profile/view.php'>Users</a></li>
		    	</ul>
			</div>
{if $parentPath}
			<div id=path>
{section name=pp loop=$parentPath}
				<li><a href="{$http_base}{$parentPath[pp].url}">{$parentPath[pp].name}</a></li>
{if $smarty.section.pp.last}
				[ <a href="{$http_base}/category/edit.php?cat_id={$parentPath[pp].id}" style='padding:0'>edit</a> ]
{else}
				<img src='{$http_base}/images/arrow.gif'>
{/if}
{/section}
			</div>
{/if}
		</div>
		<h1 class='title'>{$title}</h1>
		<div id='body'><center>

