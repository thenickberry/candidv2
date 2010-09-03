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

    function buildImageQuery($cmd,$table2) {
		global $userinfo;

		$tableA = '';

		if (isset($_GET['people'])) {
	    	$people = $_GET['people'];
		} else {
	    	$people = array();
		}

		$countPeople = count($people);
		if ($countPeople > 0) { $tableA = ", image_people p"; }

		if ($cmd == "browse") {
	  	# Changed 20040424 to support multiple categories -bweir
	    	$query = "SELECT info.id id, info.views views, info.descr descr, ISNULL(info.descr) nodescr, info.category_id cat_id, info.owner owner, info.filename filename, c.name cat_name, COUNT(*) c FROM (image_info info, category c) LEFT JOIN image_category cat ON info.id=cat.image_id $tableA WHERE access<=${userinfo['access']} AND (info.private=0 || info.owner=${userinfo['id']}) AND info.category_id=c.id";
		} elseif ($cmd == "count") {
	    	$query = "SELECT COUNT(info.id) AS count FROM image_info info $tableA WHERE access<='${userinfo['access']}'";
		} elseif ($cmd == "countPeople") {
	    	$query = "SELECT COUNT(*) AS countPeople, info.id id FROM image_info info";
		} else { }

		return $query;
    }

    function buildImageQRestrict($cat_id) {

	if (isset($_GET['start_date']))	  { $start_date   = $_GET['start_date']; }
	if (isset($_GET['end_date']))	  { $end_date     = $_GET['end_date']; }
	if (isset($_GET['photographer'])) { $photographer = $_GET['photographer']; }
	if (isset($_GET['people']))	  { $people	  = $_GET['people']; }
	if (isset($_GET['owner']))	  { $owner	  = $_GET['owner']; }
	if (isset($_GET['search_image_id'])) { $search_image_id = $_GET['search_image_id']; }
	if (isset($_GET['descr']))	  { $descr	  = $_GET['descr']; }
	if (isset($_GET['search']))	  { $search	  = $_GET['search']; }

	$addQuery = array();

	# Changed 20040424 to support multiple categories -bweir
	if (!empty($cat_id)) { array_push($addQuery,"(info.category_id=${cat_id} OR cat.category_id=${cat_id})"); }

	if (!empty($start_date)) { array_push($addQuery,"date_taken>='${start_date}'"); }
	if (!empty($end_date)) { array_push($addQuery,"date_taken<='${end_date}'"); }
	if (!empty($photographer)) { array_push($addQuery,"photographer=${photographer}"); }
	if (!empty($owner)) { array_push($addQuery,"owner=${owner}"); }

	# added 20040501 to allow searching for a specific image number
	if (!empty($search_image_id)) { array_push($addQuery,"info.id=${search_image_id}"); };

	if (!empty($people)) { $pAdd = array(); $people_count = count($people);
		for($i=0;$i<$people_count;$i++) {
		    array_push($pAdd,"p.user_id=$people[$i]");
		} $pLimit = implode($pAdd," OR ");
		array_push($addQuery,"p.image_id=info.id AND ($pLimit)"); }
	if (!empty($descr)) {
	    $keywords = split(" ",$descr);
	    for($i=0;$i<count($keywords);$i++) {
		array_push($addQuery,"descr like '%$keywords[$i]%'");
	    }
	}
	$query = implode($addQuery, " AND ");
	if (!empty($query)) { $query = "AND $query"; }
	return $query;
    }

    function buildImageQuerySort($sort) {
	if (empty($sort)) {
        $sort = "info.id";
    } else if (strpos($sort, "info.") == 0) {
        # do nothing
    } else if ($sort != "RAND()") {
        $sort = "info.${sort}";
    } else if ($sort == "info.added") {
        $sort = "info.id";
    }
	$orderBy = "ORDER BY ${sort}";
	return $orderBy;
    }

    function buildURL($cat_id) {
	global $config,$search_restr;

	
	$urlArray = array();
	$status = array();

	$url = $config['base_url']."/main.php?browse";

	if (!empty($_GET['search'])) { $url .= "&search=yes"; }

	if (!empty($cat_id)) {
	    $url .= "&cat_id=$cat_id";
	    $catInfo = getCategoryInfo($cat_id);
	    array_push($status,"Category:<br>&nbsp;&nbsp;&nbsp;${catInfo['name']}");
	}
	if (!empty($_GET['start_date'])) {
	    $url .= "&start_date=${_GET['start_date']}";
	    array_push($status,"<div class='descr'>Start date:</div><div class='value'>${_GET['start_date']}</div>");
	}
	if (!empty($_GET['end_date'])) {
	    $url .= "&end_date=${_GET['end_date']}";
	    array_push($status,"<div class='descr'>End date:</div><div class='value'>${_GET['end_date']}</div>");
	}
	if (!empty($_GET['photographer'])) {
	    $url .= "&photographer=${_GET['photographer']}";
	    $photoguy = getPersonInfo($_GET['photographer']);
	    array_push($status,"<div class='descr'>Photographer:</div><div class='value'>${photoguy['full_name']}</div>");
	}
	if (!empty($_GET['owner'])) {
	    $url .= "&owner=${_GET['owner']}";
	    $owner = getPersonInfo($_GET['owner']);
	    array_push($status,"<div class='descr'>Owner:</div><div class='value'>${owner['full_name']}</div>");
	}
	if (!empty($_GET['search_image_id'])) {
	    $url .= "&search_image_id=${_GET['search_image_id']}";
	    array_push($status,"<div class='descr'>Image ID:</div><div class='value'>$search_image_id</div>");
	}

	if (!empty($_GET['people'])) { 
	    $list = array();
	    $countPeople = count($_GET['people']);
	    for ($i=0;$i<$countPeople;$i++) {
		$url .= "&people[]=".$_GET['people'][$i];
		$person = getPersonInfo($_GET['people'][$i]);
		array_push($list,$person['full_name']);
	    }
	    array_push($status,"<div class='descr'>People:</div><div class='value'>".
	    implode($list,"<br>")."</div>");
	}
	if (!empty($_GET['sort'])) {
	    $url .= "&sort=${_GET['sort']}";
	    array_push($status,"<div class='descr'>Sort by:</div><div class='value'>${_GET['sort']}</div>");
	}
	if (count($status) < 1) { $status = array ("None"); }
	$descr = "<div id=info style='width:120'>".
		 "<div class='title'>Search criteria</div><div class='details'>".
		implode($status,"").
		"</div></div>";
	if (isset($_GET['search_reload'])) { header("Location: ${url}"); }
	$search_restr = $descr;
	return $url;
    }

    function parentPath($sel_category,$orig_sel) {
	global $config,$userinfo,$_SERVER;

	$array = array();
	$query = "SELECT id, name, parent, haskids, owner FROM category WHERE id='$sel_category'";
	$result = mysql_query($query) or db_error($query);
	$category = mysql_fetch_array($result);

	if (isset($_SERVER['HTTP_REFERER'])) { $referer = $_SERVER['HTTP_REFERER']; } else { $referer = ''; }
	if (isset($_SERVER['REQUEST_URI']) && (strstr($_SERVER['REQUEST_URI'],'view.php') && strstr($referer,'main.php'))) {
	    $link = "<a href=javascript:null(0); onclick='javascript:window.opener.location.href=\"${config['base_url']}/main.php?browse&cat_id=${category['id']}\";'>${category['name']}</a>";
	} else {
	    $link = "<a href='${config['base_url']}/main.php?browse&cat_id=${category['id']}'>${category['name']}</a>";
	}
	if ($category['id'] == $orig_sel && $category['id'] != 0 && ($userinfo['id'] == $category['owner'] || $userinfo['access'] == 5) ) {
	    $link .= "[ <a class=nopad href='${config['base_url']}/category/edit.php?cat_id=${category['id']}'>edit</a> ]";
	}
	array_push($array,$link);

	if ($category['parent'] != 0) {
	    $path = parentPath($category['parent'],$orig_sel);
	} else { $path = ""; $arrow = ""; }
	if (!empty($sel_category)) {
	    $arrow = "	<img src='${config['base_url']}/images/arrow.gif'>\n";
	} else { $arrow = ""; }

	$path .= "$arrow\n	  <li>".  implode($array,"</li>\n	<li>"). "</li>";

	return $path;
    }

    function listAccess($selected) {
	$options = array();
	for ($i=0;$i<=5;$i++) {
	    if ($i == $selected) {
		$select = "<option value='${i}' selected>${i}</option>";
	    } else {
		array_push($options,"<option value=${i}>${i}</option>");
	    }
	}
	$output = $select . implode($options) . "\n";
	return $output;
    }

    function getImageOwner($image_id) {
	$owners = array();
	if (!is_array($image_id)) {
	    $image_id = array($image_id);
	}
	for ($i=0;$i<count($image_id);$i++) {
	    $query = "SELECT owner FROM image_info WHERE id=${image_id[$i]}";
	    $result = mysql_query($query) or db_error($query);
	    $owner = mysql_fetch_row($result);
	    array_push($owners,$owner[0]);
	}
	if (count($image_id) == count($owners)) {
	    return $owners[0];
	  } else {
	    return "";
      }
}

    function getCatOwner($cat_id) {
	$query = "SELECT owner FROM category WHERE id='${cat_id}'";
	$owner = mysql_fetch_array(mysql_query($query));
	return $owner[0];
    }

  $configvars = array('post_max_size', 'memory_limit', 'upload_max_filesize');
  foreach($configvars as $var) {           
    /* skip 0 or empty values */              
    if( $size = getByteSize(ini_get($var)) ) {
      $sizes[] = $size;        
    }
  }

  if(count($sizes) > 0) {
    $maxsize = "<font class='10pt'>(max.&nbsp;" . show_readable_size( min( $sizes ) ) . ")</font>";
  } else {
    $maxsize = '.';
  }

    function getByteSize($ini_size) {
	if(!$ini_size) return FALSE;
	$ini_size = trim($ini_size);
	switch(strtoupper(substr($ini_size, -1))) {
	    case 'G':
		$bytesize = 1073741824;
		break;
	    case 'M':
		$bytesize = 1048576;
		break;
	    case 'K':
		$bytesize = 1024;
		break;
	    default:
		$bytesize = 1;
	}
	$bytesize *= (int)substr($ini_size, 0, -1);
	return $bytesize;
    }

    function show_readable_size($bytes) {
	$bytes /= 1024;
	$type = 'KB';
	if ($bytes / 1024 > 1) {
	    $bytes /= 1024;
	    $type = 'MB';
	}
	if ($bytes < 10) {
	    $bytes *= 10;
	    settype($bytes, 'integer');
	    $bytes /= 10;
	} else {
	    settype($bytes, 'integer');
	}
	return $bytes . '&nbsp;' . $type;
    }

    function getCameraName($name) {
	global $camera;
	if (isset($camera[$name])) {
	    return $camera[$name];
	} else {
	    return $name;
	}
    }

    function checkCatPic($cat_id) {
	$result = mysql_query("SELECT category_id FROM category_pics WHERE category_id='${cat_id}'");
	$info = mysql_fetch_row($result);
	return $info[0];
    }

    function restrict_access($access,$id) {
	global $userinfo;
	if (($userinfo['access'] < $access) && ($userinfo['id'] != $id)) {
	    echo "You have insufficient privileges!";
	    exit;
	}
    }


?>
