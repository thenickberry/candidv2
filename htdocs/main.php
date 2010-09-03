<?php
$time1 = time();

include("../config.inc");

if ($cmd == "browse") {
  $content = '';
  if (isset($_GET['offset'])) {
  	$offset = $_GET['offset'];
  }
  if (!isset($offset)) {
    $offset = 0;
  }
  if (!isset($_GET['cat_id'])) {
    $_GET['cat_id'] = 0;
  }
  if (empty($user)) {
    $url = buildURL($_GET['cat_id']);
    $imagequery = buildImageQuery("browse","people") . " " . buildImageQRestrict($_GET['cat_id']);
  }
  if ($userinfo['id'] != 0) {
    $last_query = addslashes($imagequery);
  }
  if (isset($importPath)) { $imagequery = ""; }
  if ($cat_id != 0 || !empty($_GET['search'])) {
    $browseImageResults = browseImage($imagequery,$url);
  } else {
    $browseImageResults = array('','');
  }

  $owned = $browseImageResults[1];
  if (isset($_GET['importPath'])) { $owned = "yes"; }

  if ((!empty($browseImageResults[2]) && empty($_GET['search']))) { 
    $content .= browseCat($_GET['cat_id']);
  } else {
    if (empty($_GET['search'])) {
	if (empty($_GET['offset'])) {
	    $content .= browseCat($cat_id,"");
	}
	$content .= $browseImageResults[0];
    } else {
      $content .= "<div style='display:block;float:right;padding:10'>$search_restr</div>\n".$browseImageResults[0];
    }
  }
  if (empty($search)) {
	$title = "Search for images";
	if (isset($_GET['people'])) {
		$title .= " with ";
		$names = array();
		foreach ($_GET['people'] as $p_id) {
			$names[] = getName($p_id,true);
		}
		$title .= implode(', ',$names);
	} elseif (isset($_GET['photographer'])) {
		$title .= ' taken by ' . getName($_GET['photographer'],true);
	} elseif (isset($_GET['sort'])) {
		$title .= ' ~ order by ' . strtolower(str_replace('_',' ',$_GET['sort']));
	} else { }
  }
  if (!empty($browseImageResults[3])) {
    $title .= " - $browseImageResults[3]";
  }
  $content .= "</form>";

  if (!empty($cat_id)) {
    $category = getCategoryInfo($cat_id);
    $title = 'Category: '.$category['name'];
  }
  css_top($title);
  echo $content;
  css_end(); }
elseif ($cmd == "updateImage") {
  $image_id = $_POST['image_id'];
  $count = count($image_id);
  if (isset($image_id[0])) {
    if ($count == 0) {
		update_image($image_id[0],'');
    } else {
		if ($count == 1) { $mass = 0; } else { $mass = 1; }
		for ($i=0;$i<$count;$i++) {
	    	update_image($image_id[$i],$mass);
		}
    }
  } else {
    $content = "Weird.. no image ID's found in image_id[].  You must have done something you shouldn't have.";
  }
  header("Location: ${_POST['refer_back']}");
}
elseif ($cmd == "updateProfile") {
  $refer_back = $_POST['refer_back'];
  $content = updateProfile();
  header("Location: $refer_back"); }
elseif ($cmd == "updateCategory") {
	$cat_id = $_POST['cat_id'];
	$goback = $_POST['goback'];
  $owner = getCatOwner($cat_id);
  if (empty($cat_id)) { $owner = $userinfo['id']; }
  if ($userinfo['id'] != $owner && $userinfo['access'] < 5) {
    $content = "You cannot modify this category, either you do not own it or you do not have enough access";
  } else {
    $content = updateCategory($cat_id);
  }
  if (isset($goback))  { 
      $cat_id = mysql_insert_id();
      $refer_back = $goback . "&sql_image[category_id]=$cat_id";
  } else {
	$refer_back = $_POST['refer_back'];
  }
  header("Location: $refer_back"); }
elseif ($cmd == "logout") {
  $userid = ""; $userinfo['id'] = "";
  expireSession($cookie);
  if (strstr($referer,"login")) { $referer = "/"; }
  header("Location: $referer"); }
elseif ($cmd == "process") {
  if ($auth_status != "ok") {
    $refer_back = "/main.php?login&type=$auth_status";
  }

  if (strstr($_POST['refer_back'],"login")) {
    $refer_back= $config['base_url']."/";
  } else {
    $refer_back = $_POST['refer_back'];
  }

  header("Location: $refer_back"); }
elseif ($cmd == "uploadImage") {
  $content = "<a style='font-size: 12pt' href=?addImage>Click here</a> to start importing images<BR><BR>\n";
  $content .= uploadImage($HTTP_POST_FILES);
  css_top($title);
  echo $content;
  css_end(); }
elseif ($cmd == "importImage") {
  $importFile = $_POST['importFile'];
  $destDir = $_POST['destDir'];
  $count = count($importFile);
  if ($count > 0) {
    for($i=0;$i<$count;$i++) {
	$type = exif_imagetype($importFile[$i]);
	if ($type == 2 || $type == 3) {
	    $content .= importImage($importFile[$i]);
	} else {
	    $content .= "Skipped $importFile[$i]<br>";
	}
    }
    system("rm -rf $destDir");
  } else {
    $content = "Man, what's up? no importFile(s) specified";
  }
  $sql_image = $_POST['sql_image'];
  $refer_back = "main.php?browse&cat_id=${sql_image['category_id']}";
  header("Location: $refer_back");
}
elseif ($cmd == "showImage") {
  showImage($_GET['image_id']); }
elseif ($cmd == "thumbView") {
  thumb_create($importFile); }
elseif ($cmd == "rotate") {
  rotateJpeg($image_id,$rotate); }
elseif ($cmd == "showRotatedImage") {
  showRotatedJpeg($image_id,$rotate); }
elseif ($cmd == "displayImage") {
  displayImage($_GET['image_id']); }
elseif ($cmd == "displayCatImage") {
  displayCatImage($cat_id); }
elseif ($cmd == "uploadCatImage") {
  uploadCatImage($cat_id,$HTTP_POST_FILES); }
elseif ($cmd == "uploadUserImage") {
  uploadUserImage($user_id,$HTTP_POST_FILES); }
elseif ($cmd == "showHistory") {
  showHistory($image_id,'image_info'); }
elseif ($cmd == "addComment") {
  addComment($image_id,$comment); }
elseif ($cmd == "displayUserImage") {
  displayUserImage($user_id); }
else {
  if (!isset($pid)) { $pid = 0; }
  css_top('');
  echo browseCat($pid);
  css_end(); }

if (@mysql_ping()) {
	mysql_close();
}

?>
