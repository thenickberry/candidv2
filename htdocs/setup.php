<html>
	<head>
		<style type='text/css'>
			.pass { color: green; padding-left: 20px; background:url(/images/passed.png) no-repeat center left; }
			.fail { color: red;   }
		</style>
	</head>
	<body>
<div style='margin:0px auto;width:400px'>
<h1>CANDIDv2</h1>
<?php
    if (file_exists("../config.inc1")) {
		print "config.inc already exists... the file must be removed in order to restart CANDIDv2 install";
		exit;
    } 

	$results = array();
	$passed = true;
	if (!isset($_GET['step'])) { $step = '1'; } else { $step = $_GET['step']; }

	$required_versions = array(
			'php'	=> '4.2',
			'mysql'	=> '4.0',
			'gd'	=> '2.0'
		);

	switch ($step) {
		case 1:
			$extensions = get_loaded_extensions();

			$installed_versions['php'] = PHP_VERSION;

			## Check for MySQL support and compare version requirement
			if (!in_array('mysql',$extensions)) {
				$failed[] = 'MySQL support is not included in PHP';
			} else {
				$installed_versions['mysql'] = mysql_get_client_info();
			}

			## Check for GD support and compare version requirement
			if (!in_array('gd',$extensions)) {
				$failed[] = '<span class=error>GD support is not included in PHP</span>';
			} else {
				$GDArray = gd_info();
				$installed_versions['gd'] = ereg_replace('[[:alpha:][:space:]()]+', '', $GDArray['GD Version']);
				$gd_functions = get_extension_funcs('gd');
				if (in_array('imagejpeg',$gd_functions)) {
					$results[] = '&nbsp;&nbsp;&nbsp;+ JPEG support'; }
				if (in_array('imagepng',$gd_functions)) {
					$results[] = '&nbsp;&nbsp;&nbsp;+ PNG support'; }
				if (in_array('imagegif',$gd_functions)) {
					$results[] = '&nbsp;&nbsp;&nbsp;+ GIF support'; }
			}
			foreach ($required_versions as $package => $version) {
				if (isset($installed_versions[$package])) {
					$installed_version = $installed_versions[$package];
					if (!version_compare($installed_version,$version,'ge')) {
						$class = 'error';
						$operand = '<';
					} else {
						$class = 'pass';
						$operand = '>=';
					}
					print "<span class='${class}'>${package} ${installed_version}</span>";
				} else {
					print "<span class='error'>${package} not available</span>";
				}
				print "<br />\n";
			}
			break;
		case 2:
			get_env();
			break;
		case 3:
			build_env();
			break;
		case 4:
			check_perms();
			break;
	}

	foreach ($results as $result) {
		print $result . "<br />\n";
	}
	exit;

    function get_env() {
	
?>
	<form action='<?= $_SERVER['PHP_SELF'] ?>?step=2' method='post'>
	<table>
		<tr><td colspan=3 bgcolor=#efefef><b>User info</td></tr>
		<tr><td width=20>&nbsp;</td><td>Username:</td><td><input type=text name=user_name></td></tr>
		<tr><td width=20>&nbsp;</td><td>Password:</td><td><input type=text name=user_pass></td></tr>
		<tr><td width=20>&nbsp;</td><td>First Name:</td><td><input type=text name=user_fname></td></tr>
		<tr><td width=20>&nbsp;</td><td>Last Name:</td><td><input type=text name=user_lname></td></tr>
	</table>
	<br />
	<table>
		<tr><td colspan=3 bgcolor=#efefef><b>Database</b></td></tr>
		<tr><td width=20>&nbsp;</td><td>Hostname:</td><td><input type=text name=db_host value=localhost></td></tr>
		<tr><td width=20>&nbsp;</td><td>Database:</td><td><input type=text name=db_name></td></tr>
		<tr><td width=20>&nbsp;</td><td>Username:</td><td><input type=text name=db_user></td></tr>
		<tr><td width=20>&nbsp;</td><td>Password:</td><td><input type=text name=db_pass></td></tr>
	</table>
	<br />
	<input type=submit value=Continue>

<?php
    }
	    
    function build_env() {
	error_reporting(0);
	$fields = array('user_name','user_pass','user_fname','user_lname',
		 	'db_host','db_user','db_pass','db_name');

	foreach ($fields as $field) {
		$$field = $_POST[$field];
		if (empty($$field)) {
			echo "\tMissing field: <font color=red>$field</font><br />\n";
			$error = 1;
		}

		if ($error == 1) {
			echo "<br />Go back and try again";
			exit;
		}

	}


	mysql_connect($db_host,$db_user,$db_pass) or $db_auth_fail = 1;
	mysql_select_db($db_name) or $db_select_fail = 1;

	if ($db_auth_fail == 1) {
	    $data .= "<table><td>Authentication failed. <b>${db_user}</b>/<b>${db_pass}</b> was ".
		     "unable to connect to ${db_host}<br /><br />".
		     "Make sure the account has been created in MySQL... ".
		     "from a mysql prompt:<br /><i>grant all privileges on ${db_name}.* to ${db_user}@'${db_host}' identified by '${db_pass}'</i></td></table><br /><br />";
	    $error = 1;
	}

	if ($db_select_fail == 1) {
	    $data .= "<table><td>Database name, <b>${db_name}</b>, does not exist<br /><br />".
		     "Make sure the database has been created<br />".
		     "i.e. from a shell prompt: <i>mysqladmin -u root -p create ${db_name}</i></td></table><br /><br />";
	    $error = 1;
	}

	if ($error == 1) {
	    return $data;
	}

	$queries[] = "CREATE TABLE category ( id int(11) NOT NULL auto_increment, descr varchar(255) default NULL, loc varchar(80) default NULL, name varchar(40) default NULL, parent int(11) default NULL, haskids int(4) default NULL, added datetime default NULL, modified datetime default NULL, owner int(11) NOT NULL default '0', public enum('y','n') default 'y', sort_by varchar(32) default NULL, PRIMARY KEY  (id)) TYPE=ISAM PACK_KEYS=1";
	$queries[] = "CREATE TABLE category_pics ( category_id int(11) NOT NULL default '0', data blob, PRIMARY KEY  (category_id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE history ( history_id int(11) NOT NULL auto_increment, user_id int(11) default NULL, action varchar(255) default NULL, message varchar(255) default NULL, datetime datetime default NULL, ip_addr varchar(15) default NULL, table_id int(11) default NULL, table_name varchar(16) default NULL, PRIMARY KEY  (history_id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE image_category ( id int(11) NOT NULL auto_increment, image_id int(11) NOT NULL default '0', pri enum('y','n') NOT NULL default 'n', category_id int(11) NOT NULL default '0', PRIMARY KEY  (id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE image_comment ( id int(11) NOT NULL auto_increment, image_id int(11) NOT NULL default '0', user_id int(11) NOT NULL default '0', comment text NOT NULL, stamp datetime default '0000-00-00 00:00:00', PRIMARY KEY  (id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE image_file ( image_id int(11) NOT NULL default '0', data mediumblob, PRIMARY KEY  (image_id)) TYPE=MyISAM MAX_ROWS=10000000";
	$queries[] = "CREATE TABLE image_info ( id int(11) NOT NULL auto_increment, category_id int(11) NOT NULL default '0', descr varchar(255) default NULL, added datetime default NULL, modified datetime default NULL, owner int(11) NOT NULL default '0', photographer int(11) default NULL, date_taken date default NULL, access int(1) default '0', views int(11) default '0', last_view datetime default NULL, private int(1) default '0', width varchar(4) default NULL, height varchar(4) default NULL, content_type varchar(32) default NULL, filename varchar(64) default NULL, timestamp datetime default NULL, camera varchar(64) default NULL, PRIMARY KEY  (id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE image_thumb ( image_id int(11) NOT NULL default '0', data blob, PRIMARY KEY  (image_id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE people ( id int(11) NOT NULL auto_increment, user_id int(11) NOT NULL default '0', image_id int(11) default NULL, PRIMARY KEY  (id), KEY user_id (user_id)) TYPE=ISAM PACK_KEYS=1";
	$queries[] = "CREATE TABLE session ( session int(16) NOT NULL default '0', user_id int(11) NOT NULL default '0', expire datetime default NULL, ip varchar(32) default NULL, last_query text) TYPE=MyISAM";
	$queries[] = "CREATE TABLE user ( id int(11) NOT NULL auto_increment, username varchar(16) default NULL, pword varchar(32) default NULL, access int(2) default NULL, fname varchar(16) default NULL, lname varchar(16) default NULL, email varchar(32) default NULL, numrows int(1) default '5', numcols int(1) default '2', debug int(1) default NULL, created datetime default NULL, modified datetime default NULL, name_disp varchar(5) default 'fname', update_notice char(1) default 'w', init_disp enum('480x360','640x480','800x600') default '480x360', expire varchar(5) default NULL, onlist enum('y','n') default 'y', user_image blob, theme varchar(32) default NULL, PRIMARY KEY  (id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE user_mms ( id int(11) NOT NULL auto_increment, user_id int(11) default NULL, mobile_addr varchar(64) default NULL, default_category_id int(11) default NULL, private enum('','checked') default '', PRIMARY KEY  (id)) TYPE=MyISAM";
	$queries[] = "CREATE TABLE user_pics ( user_id int(11) NOT NULL default '0', image_data blob, PRIMARY KEY  (user_id)) TYPE=MyISAM";
	$queries[] = "INSERT INTO user (id,username,pword,access,created,fname,lname) VALUES (null,'$user_name',PASSWORD('$user_pass'),'5',NOW(),'$user_fname','$user_lname')";

	foreach ($queries as $query) {
		mysql_query($query) or db_error($query);
	}

	mysql_close();

	if ($error != 1) {
		echo "Success!  CANDIDv2's database has been created and privileges have been set!";
		$base_dir = str_replace('/htdocs','',$_SERVER['DOCUMENT_ROOT']);
		$base_url = "http://${_SERVER['SERVER_NAME']}" . str_replace('/install.php','',$_SERVER['PHP_SELF']);
		if (! $fp = fopen('/tmp/config.inc','a') ) { echo "Cannot write /tmp/config.inc!"; exit; }
		$config = '<?
  $version = "2.41";

  $base_dir = "'.$base_dir.'";
  chdir($base_dir);

  $config = array(
        "db_host"       => "'.$db_host.'",
        "db_name"       => "'.$db_name.'",
        "db_user"       => "'.$db_user.'",
        "db_pass"       => "'.$db_pass.'",
	"base_url"	=> "'.$base_url.'",
        "cookieName"    => "candid",
        "cookiePath"    => "/",
        "defaultRows"   => "5",
        "defaultCols"   => "2",
        "timeout"       => "0",
        "defaultW"      => "480",
        "defaultH"      => "360",
        "uploadDir"     => "$base_dir/htdocs/incoming",
        "unzip"         => "/usr/bin/unzip",
        "default_theme" => "default"
        );

  include("includes/db.inc");
  mysql_select_db($config[\'db_name\'],$db_link);

  include("includes/misc.inc");
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

?>';
		fwrite($fp,$config);
		fclose($fp);

		return;
	}
    }

    function check_perms() {

	$cwd = getcwd();
	$incoming_uid = fileowner($cwd . "/incoming");
	$apache_uid = fileowner("/tmp/config.inc");

	if ($incoming_uid != $apache_uid) {
		$apache_dude = posix_getpwuid($apache_uid);
		$data = "<table style='border:1px solid #000'><tr><td bgcolor=#efefef>Permissions check</td></tr>".
			 "<tr><td>${cwd}/incoming not owned by the Apache user<br />Chown ${cwd}/incoming to <b>${apache_dude['name']}</b> (${incoming_uid})</td></tr>".
			 "</table>";
		return $data;
	} else {
		return;
	}

    }

    function build_candid_tables() {
    }

    function db_error($query) {
		echo mysql_error() . "<font color=red>$query</font>";
		exit;
    }

    if (empty($output)) {
	$base_dir = str_replace('/htdocs','',$_SERVER['DOCUMENT_ROOT']);
	echo "Move <b>/tmp/config.inc</b> to <b>$base_dir/config.inc</b> and you're done";
	echo "<br /><br /><a href=index.php>click here to start using CANDIDv2</a>";
    } else {
	echo $output;
    }

?>
