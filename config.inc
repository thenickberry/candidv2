<?php

  $version = "2.50";
  #error_reporting(E_ALL);

  $base_dir = "/domains/scurvy/candidv2";
  chdir($base_dir);

  $config = array(
	"base_dir"	=> $base_dir,
	"base_url"	=> "http://candid.scurvy.net",
	"db_host"	=> "localhost",
	"db_name"	=> "candid2",
	"db_user"	=> "candid",
	"db_pass"	=> "didnac",
	"cookieName"	=> "candid",
	"cookiePath"	=> "/",
	"defaultRows"	=> "5",
	"defaultCols"	=> "2",
	"timeout"	=> "0",
	"default_w"	=> "480",
	"default_h"	=> "360",
	"cat_thumb_w"	=> "150",
	"cat_thumb_h"	=> "150",
	"uploadDir"	=> "$base_dir/htdocs/incoming",
	"unzip"		=> "/usr/bin/unzip",
	"default_theme"	=> "default"
	);

  include("includes/db.inc");
  mysql_select_db($config['db_name'],$db_link);

  include("includes/query.inc");
  include("includes/auth.inc");
  include("includes/template.inc");
  include("includes/image.inc");
  include("includes/upload.inc");
  include("includes/category.inc");
  include("includes/profile.inc");
  include("includes/import.inc");
  include("includes/history.inc");
  include("includes/comment.inc");
  include("includes/misc.inc");

?>
