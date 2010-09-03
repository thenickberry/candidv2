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

    function get_image_info($image_id) { 
	$da = array(); $ca = array(); $pha = array(); $pa = array();
	if (!is_array($image_id)) {
	    $image_id = array($image_id);
	}
	$count = count($image_id);
	for($i=0;$i<$count;$i++) {
	    $query = "SELECT id, descr, ISNULL(descr) nodescr,date_taken,".
		     "date_format(date_taken,'%M %d, %Y') date_taken_nice,".
		     "category_id cat_id,photographer,owner,".
		     "date_format(modified,'%b %d, %Y') modified,".
		     "date_format(added,'%b %d, %Y') added,camera,".
		     "access, views,private,last_view,width,height,".
		     "concat(width,' x ',height) resolution,content_type,filename ".
		     "FROM image_info info WHERE id='${image_id[$i]}'";
	    $result = mysql_query($query) or db_error($query);
	    $image = mysql_fetch_array($result);

		#$imageSize_q = "SELECT data FROM image_file WHERE image_id=${image_id}";
		#$imageSize_r = mysql_query($imageSize_q) or db_error($imageSize_q);
		#$imageSize = mysql_field_len($imageSize_r, 0);

        if (empty($image['camera'])) {
            $image['camera'] = "<i>unknown</i>";
        } else {
            $image['camera'] = getCameraName($image['camera']);
        }

	     if ($image['views'] == 1) {
		$image['viewed'] = "once";
	    } else {
		$image['viewed'] = $image['views']." times";
	    }

	     if (empty($da)) {
		array_push($da,$image['descr']);
	     } elseif (in_array($image['descr'],$da)) {
		array_push($da,$image['descr']);
	     } else { }

	    if (empty($ca)) {
		array_push($ca,$image['cat_id']);
	    } elseif (in_array($image['cat_id'],$ca)) {
		array_push($ca,$image['cat_id']);
	    } else { }

	    if (empty($pha)) {
		array_push($pha,$image['photographer']);
	    } elseif (in_array($image['photographer'],$pha)) {
		array_push($pha,$image['photographer']);
	    } else { }

	    $image['people'] = ''; $image['people_list'] = '';

	    $pq = "SELECT user_id FROM image_people WHERE image_id='${image_id[$i]}'";
	    $pr = mysql_query($pq) or db_error($pq);
	    while ($pcount = mysql_fetch_row($pr)) {
		$uid = $pcount[0];
		if (!isset($countPid[$uid])) { $countPid[$uid] = 0; }
		$countPid[$uid]++;
		if (!in_array($uid,$pa)) {
		    array_push($pa,$uid);
		}
	    }
	}

	if (count($da) == $count) { $image['descr'] = $da[0];	      } else { $image['descr'] = ""; }
	if (count($ca) == $count) { $image['category_id'] = $ca[0];   } else { $image['category_id'] = ""; }
	if (count($pha) == $count){ $image['photographer'] = $pha[0]; } else { $image['photographer'] = ""; }

	for($i=0;$i<count($pa);$i++) {
	    $uid = $pa[$i];
	    $thecount = $countPid[$uid];
	    if ($thecount == $count) {
		$image['people_list'] .= "<option value=$uid>".getName($uid)."</option>";
		$image['people'] .= "<div class='value'>".getName($uid)."</div>";
	    } else { }
	}

	$image['photographer_name'] = getName($image['photographer']);

	return $image;
    }

    function get_image_people($image_id) {
	global $userinfo;
	if ($userinfo['name_disp'] == 'fname') {
	    $list = "CONCAT(u.fname,' ',u.lname) name";
	} else {
	    $list = "CONCAT(u.lname,', ',u.fname) name";
	}
	$query = "SELECT p.id id, p.user_id, $list FROM image_people p, user u WHERE image_id='${image_id}' AND u.id=p.user_id";

	$result = mysql_query($query) or db_error($query);
	return $result;
    }

    function update_image($image_id,$mass) {
	global $config;

	if (isset($_POST['sql_image'])) { $sql_image = $_POST['sql_image']; }
	if (isset($_POST['addPeople'])) { $addPeople  = $_POST['addPeople']; }
	if (isset($_POST['removePeople'])) { $removePeople= $_POST['removePeople']; }
	if (isset($_POST['rotate'])) { $rotate = $_POST['rotate']; }
	if (isset($_POST['delete'])) { $delete = $_POST['delete']; }
	if (isset($_POST['confirm'])) { $confirm = $_POST['confirm']; }
	if (isset($_POST['refer_back'])) { $refer_back = $_POST['refer_back']; }
	if (isset($_POST['assignCatImage'])) { $assignCatImage = $_POST['assignCatImage']; }

	if (isset($sql_image['private'])) {
	    if ($sql_image['private'] == "on") {
	    	$sql_image['private'] = 1;
	    } else {
	        $sql_image['private'] = 0;
	    }
	}

	if (isset($delete)) {
	    $query = "DELETE FROM image_info WHERE id='${image_id}'";
	    mysql_query($query) or db_error($query);
	    $queryFile = "DELETE FROM image_file WHERE image_id='${image_id}'";
	    mysql_query($queryFile) or db_error($queryFile);
	    $queryThumb = "DELETE FROM image_thumb WHERE image_id='${image_id}'";
	    mysql_query($queryThumb) or db_error($queryThumb);
	    $pQuery = "DELETE FROM image_people WHERE image_id='${image_id}'";
	    mysql_query($pQuery) or db_error($pQuery);
	    $hQuery = "DELETE FROM history WHERE table_id='${image_id}' AND table_name='image_info'";
	    mysql_query($hQuery) or db_error($hQuery);
	    return;
	}

	$updateParams = array();
	$changes = array();
	$image = array();


	if (!empty($mass)) {
	    foreach ($sql_image as $key => $value) {
		if (strstr($key,"_do") && $value == 'on') {
			$good_key = str_replace('_do','',$key);
			$image[$good_key] = $sql_image[$good_key];
			array_push($changes,$good_key);
			unset($good_key);
		}
	    }
	} else {
		$check = get_image_info($image_id);
		if (isset($sql_image)) {
			foreach ($sql_image as $key => $value) {
				if ($sql_image[$key] != $check[$key]) {
					$image[$key] = $value;
					array_push($changes,$key);
				}
			}
		}
	}


	$content = "";

	if (count($image) > 0) {
		#$image = $sql_image;
		$image['id'] = $image_id;
		$query = build_query('image_info',$image);
	}


	if (!empty($rotate)) {
	    $image = rotateJpeg($image_id,$rotate);
	    $queryFile = "update image_file set data=\"${image['file']}\" where image_id=${image_id}";
	    mysql_query($queryFile) or db_error($queryFile);
	    $queryThumb = "update image_thumb set data=\"${image['thumb']}\" where image_id=${image_id}";
	    mysql_query($queryThumb) or db_error($queryThumb);
	    if ($rotate == "90" || $rotate == "270") {
		list($dst_y,$dst_x) = GetImageDimensions($image_id);
		mysql_query("UPDATE image_info SET width=$dst_x,height=$dst_y WHERE id='${image_id}'");
	    }
	}
	if (!empty($query)) {
	    mysql_query($query) or db_error($query);
	}

	if (isset($addPeople)) {    add_people($image_id,$addPeople); }
	if (isset($removePeople)) { del_people($image_id,$removePeople); }


	if (empty($mass) && $assignCatImage == "y") {
	    $file = "/tmp/${image_id}-thumb.jpg";
	    $url = "${config['base_url']}/main.php?showImage&image_id=${image_id}&thumb=yes";
	    list($w,$h,$type,$a) = getimagesize($url);
	    thumb_create($url,$file,"100","125",$type);
	    $fp = fopen ($file,'r');
	    $image_thumb = addslashes( fread( $fp, filesize($file) ) );
	    fclose($fp);
	    mysql_query("DELETE FROM category_pics WHERE category_id=${sql_image['category_id']}");
	    mysql_query("INSERT INTO category_pics VALUES (${sql_image['category_id']},\"$image_thumb\")");
	    unlink($file);
	}
	$mesg = implode($changes,", ");
	make_history("update",$mesg,$image_id,"image_info");
	return $content;
    }

    function browseImage($query,$url) {
	global $config,$userinfo,$cookie,$title;

	$content = '<center>';
	$end = '';
	$owned = 0;

	if (isset($_GET['offset'])) {
	    $offset = $_GET['offset'];
	} else {
	    $offset = 0;
	}
	if (isset($_GET['cat_id'])) {
	    $cat_id = $_GET['cat_id'];
	} else {
	    $cat_id = 0;
	}
	if (isset($_GET['sort']))   {
	    $sort  = 'info.' . $_GET['sort'];
	    $order = "DESC";
	} else {
	    $sort  = '';
	    $order = "ASC";
	}
	if (isset($_GET['search'])) {
	    $search = $_GET['search'];
	}

	if (isset($_GET['people'])) {
	    $people = $_GET['people'];
	} else {
	    $people = array();
	}
    
	$limit = $userinfo['numcols'] * $userinfo['numrows'];
	$countPeople = count($people);
	if (!strstr($query,"GROUP BY")) {
	    $end = " GROUP BY id ORDER BY id";
	}

	$countQuery = mysql_query($query . $end) or db_error($query . $end);

	if (empty($countPeople)) {
	    $imagecount = mysql_num_rows($countQuery);
	    if (!empty($cat_id)) {
		$cat_sort_query = "SELECT sort_by FROM category WHERE id=$cat_id";
		$cat_sort_results = mysql_query($cat_sort_query) or db_error($cat_sort_query);
		$cat_sort = mysql_fetch_row($cat_sort_results);
		if (!empty($cat_sort[0])) {
			$cat_sort[0] = 'c.'.$cat_sort[0];
			$limitQuery = buildImageQuerySort($cat_sort[0]);
		} else {
			$limitQuery = '';
		}
	    } elseif (isset($_GET['sort'])) {
	        $limitQuery = buildImageQuerySort($_GET['sort']);
	    } else {
		$limitQuery = '';
	    }
	} else {
	    $imagecount = 0;
	    while ($countResult = mysql_fetch_array($countQuery)) {
		if ($countResult['c'] == $countPeople) { $imagecount++; }
	    }
	    $order = "DESC";
	    if (empty($sort)) { $sort = "id"; }
	    $limitQuery = " ORDER BY $sort";
	}

	if (!strstr($query,"GROUP BY")) { $query .= " GROUP BY id "; }

	$query .= "$limitQuery $order";

	setcookie('last_query',$query);

	$query .= " LIMIT $offset,$limit";
	$results = mysql_query($query) or db_error($query);
	if (mysql_num_rows($results) == 0) {
		return array("\n\t\t<br><br>\n\t\tNo image results\n","","");
	}

	$navigate = build_jump_points($imagecount,$offset,$limit,$url);


	if (empty($image['owner'])) { $image['owner'] = -1; }
	$content .= "<table cellpadding='10' cellspacing='0' width=1>\n".
		    "<tr valign=top bgcolor=white><td colspan=".
		    $userinfo['numrows']++." align=middle>$navigate</td></tr>\n";
	$column = 0;
	$row = 0;
	while ($image = mysql_fetch_array($results)) {
	    $data = "";

	    if (!empty($image['filename'])) {
		if (strlen($image['filename']) > 24) {
		    $image['filename'] = substr($image['filename'],0,24) . "...jpg";
		}
		$data .= "<b>${image['filename']}</b><br>";
	    }

	    if (!empty($image['descr'])) {
		if (strlen($image['descr']) > 24) {
		    $image['short_descr'] = substr($image['descr'],0,38) . "...";
		} else {
		    $image['short_descr'] = $image['descr'];
		}
		$data .= "<i>${image['short_descr']}</i><br>";
	    }
	    if (isset($_COOKIE['edit'])) {
	    	$data .= "Views: ${image['views']}<br>";
	    }
	    if (!empty($_GET['search'])) {
		$data .= "<a href='${config['base_url']}/main.php?browse&".
			 "cat_id=${image['cat_id']}' alt='${image['cat_name']}' style='font-size:11px'>".
			 "${image['cat_name']}</a>&nbsp;<br>";
	    }

	    $comments = countComments($image['id']);
	    if (!empty($comments)) {
		$data .= "Comments: $comments<br>";
	    }

	    $image['descr'] = str_replace('"','&quot;',$image['descr']);

	    /* Checks to make sure if we're looking for two people that it will
		only display if two people are found */
	    if (!empty($people)) { if ($countPeople != $image['c']) { continue; } }

	    if (($userinfo['id'] == $image['owner'] || $userinfo['access'] == "5") && isset($_COOKIE['edit'])) {
		$owned++;
		$checkbox = "<input type=checkbox name='image_id[]' ".
			    "value=${image['id']} onclick='Toggle(this)'>";
		$mass_edit = "yes";
		if ($image['nodescr'] == 1) {
		    $needs_edit = "*";
		} else { $needs_edit = ""; }
		$edit = "<a href='${config['base_url']}/main.php?showHistory&image_id=$image[id]'>history</a> | ".
			"<a href='${config['base_url']}/image/edit.php?image_id[]=${image['id']}'>edit</a>$needs_edit";
		unset($needs_edit);
	    } else {
	    	$edit = ''; $checkbox = '';
	    }

	    if (empty($column)) { $content .= "<tr valign=top><a name='${row}'></a>"; $row++; }
	    $popup_w = $userinfo['default_w'] + 240;
	    $popup_h = $userinfo['default_h'] + 120;
	    $content .= "<td align='center'><table width=100%><tr><td>".
			"<div class=imgholder><a href='${config['base_url']}/image/view.php?image_id=${image['id']}' target=myPopUp onclick=\"javascript:window.open('', this.target, 'width=${popup_w},height=${popup_h},scrollbars=yes');return true;\">".
			"<img src=${config['base_url']}/main.php?showImage&image_id=${image['id']}&thumb=yes ".
			"border=0 alt=\"${image['descr']}\" title=\"${image['descr']}\">".
			"</a></div></td></tr><tr><td>".
			"<span style='width:225px;text-align:left;padding:0px 0px'><div style='float:right;text-align:right'>$edit</div>$data</span>$checkbox</td></tr></table></td>";
	    unset($edit,$checkbox);
	    $column = 1 + $column;
	    if ($column == $userinfo['numcols']) {
		$column = 0;
		$content .= "</tr>\n";
 	    }
	}
	$content .= "<tr bgcolor=white><td align=middle colspan=".
		    "${userinfo['numrows']}>$navigate</td></tr></table>";
	if (!empty($owned)) {
	    $content = "<form action='${config['base_url']}/image/edit.php' method=post>".
			"<input type=hidden name=cat_id value='$cat_id'>\n".
			$content;
	    $owned = "yes";
	    $button_value = "Mass Edit";
	    $button_name = "edit";
	    $content .=  "   <table>\n".
			"     <tr valign=bottom height=75>\n".
			"       <td align=right><a href='javascript:void(0);' ".
			"onClick='".$button_name."MarkAll();'>Check all</a></td>".
			"       <td><a href='javascript:void(0);' onClick='".
			$button_name."UnmarkAll();'>Uncheck all</a></td>".
			"     </tr>\n".
			"     <tr valign=center>\n".
			"       <td align=center colspan=2><form action='${config['base_url']}/image/edit.php' ".
			"method=post><input type=submit value='$button_value' class=button></td>\n".
			"     </tr>\n".
			"   </table>\n";
	} else {
	    $owned = "no";
	}
	$noimages = "";
	$content = "<!-- start of browseImage(); -->\n" . $content;
	$content .= "</table>\n<!-- end of browseImage(); -->";
	return array($content,$owned,$noimages,$title);
    }

     function getParents($top,$check) {
	global $userinfo,$cookie;
	$list = array();
	$query = "SELECT id,haskids,name FROM category WHERE parent='$top'";
	$result = mysql_query($query) or db_error($query);
	$numrows = mysql_num_rows($result);
	if (!empty($numrows)) {
	    while ($cat = mysql_fetch_row($result)) {
		if ($cat[1] != "1") {
		    array_push($list,$cat[0]);
		} else { 
		    $return = getParents($cat[0],"");
		    for ($i=0;$i<count($return);$i++) {
			array_push($list,$return[$i]);
		    }
		}
	    }
	} else {
	    array_push($list,$top);
	}

	if ($check == 1) {
	    $query = "SELECT count(id),max(unix_timestamp(added)) ".
		     "FROM image_info WHERE (category_id='".
		     implode($list,"' OR category_id='")."') AND ((owner='$userinfo[id]' ".
		     "OR private!='1') AND access<='$userinfo[access]')";
	    $list = array();
	    $result = mysql_query($query) or db_error($query);
	    $countresult = mysql_fetch_row($result);
	    $count = $countresult;
	}

	if (!empty($count)) { return $count; } else { return $list;  }

     }

     function countImage($cat_id,$haskids,$reqCount) {
	global $restr,$imagecount;

	$cat_id = $_GET['cat_id'];
	$people = $_GET['people'];
	$sort   = $_GET['sort'];
	$search = $_GET['search'];

	if (count($people) > 0) { $test = "people"; }
	if (empty($imagecount)) { $imagecount = 0; }
	$queryRestr = "";
	if (isset($restr)) {
	    foreach ($restr as $key => $value) {
		$queryRestr .= "AND $key='$value' ";
	    }
	}

	if ($haskids == 1) {
	    $query = "SELECT id,haskids FROM category WHERE parent='$cat_id'";
	    $result = mysql_query($query) or db_error($query);
	    while ($category = mysql_fetch_row($result)) {
		if ($category[1] != 1) {
		    $iquery = "select count(id) from image_info where category_id=".$category[0];
		    $count = mysql_fetch_row(mysql_query($iquery));
		    $imagecount += $count[0];
		} else {
		    $imagecount += countImage($category[0],"1","");
		}
	    }
	} else {
	    $query = "select count(id) from image_info where category_id='$cat_id' $queryRestr";
	    $count = mysql_fetch_row(mysql_query($query));
	    $imagecount += $count[0];
	}

	if (empty($imagecount)) { $imagecount = 0; }

	return $imagecount;
    }

    function showImage($image_id) {
		global $userinfo,$_SERVER;
		$thumb = $_GET['thumb'];
		if (isset($_GET['resize'])) {
			$resize = $_GET['resize'];
		} else { $resize = 0; }
		if (empty($resize)) {
	    	$access_limit = "AND access<=$userinfo[access]";
		}

	   	if ($thumb == "yes") {
	   		$table  = 'image_thumb';
			$prefix = 'thumb_';
	   	} else {
			$table  = 'image_file';
			$prefix = '';
			if (isset($_SERVER['HTTP_REFERER'])) {
	    		if (strstr('image/view.php',$_SERVER['HTTP_REFERER'])) {
	        		make_history("view","full",$image_id,"image_info");
	    		}
			}
	   	}

    	$query = "SELECT d.data, i.content_type, i.filename FROM $table d, image_info i WHERE d.image_id=$image_id $access_limit AND i.id=d.image_id";
    	$image = mysql_fetch_row(mysql_query($query)) or db_error($query);


		list($j,$ext) = split("/",$image[1]);
		$ext = strtolower($ext);
    	if (empty($image[2])) {
			$image[2] = $image_id . ".$ext";
    	} 


    	$filename = $prefix . $image[2];

    	header('Content-type: '.$image[1]);
    	header('Content-disposition: inline; name='.$filename);
		$im = @imagecreatefromstring($image[0]);
		if (!$im) {
			$im = imagecreatetruecolor('200','150');
			$im_error = imagecreatefrompng($config['base_dir'].'htdocs/images/error.png');
			$im_error_w = imagesx($im_error);
			$im_error_h = imagesy($im_error);
			imagecopymerge($im,$im_error,0,0,0,0,$im_error_w,$im_error_h,100);
			#imagestring($im, 5, 10, 50, 'Unable to load image', imagecolorallocate($im,255,0,0));
			imagestring($im, 5, 120, 130, 'id: '.$image_id, imagecolorallocate($im,255,0,0));
		}

		if ($ext == 'jpg' || $ext == 'jpeg') {
			ImageJpeg($im,'',100);
		} elseif ($ext == 'png') {
			ImagePNG($im);
		} else { }
		ImageDestroy($im);
    }

    function rotateJpeg($image_id,$rotate) {
		global $config;
		$data = mysql_fetch_array(mysql_query("SELECT data file FROM image_file WHERE image_id='$image_id'"));
		$src_file = imagecreatefromstring($data['file']);
		$file_width = imagesx($src_file);
		$file_height = imagesy($src_file);
		$dst_file = imagecreatetruecolor($file_width,$file_height);
		imagecopyresampled($dst_file,$src_file,0,0,0,0,$file_width,$file_height,$file_width,$file_height);
		$rotated_file = imagerotate($dst_file,$rotate,0);
		ob_start();
		ImageJpeg($rotated_file);
		$file_buffer = ob_get_contents();
		ob_end_clean();
		$image_file = addslashes($file_buffer);

		$thumb_buffer = thumb_create($rotated_file,'','','','JPEG');
		$image_thumb = addslashes($thumb_buffer);

		ImageDestroy($rotated_file);

		return array("file" => $image_file, "thumb" => $image_thumb);
    }

    function totalImages() {
		global $userinfo;
		$query = "SELECT COUNT(id) FROM image_info WHERE (owner='$userinfo[id]' OR private!='1')";
		$result = mysql_query($query) or $count = 0;
		if (empty($count)) {
	    	$info = mysql_fetch_row($result);
	    	$count = $info[0];
		}
		return $count;
    }

    function getNextImage($image_id,$last_query) {
		global $userinfo,$cat_id;
		$imageList = array();
		if (!empty($last_query)) {
	    	$query = $last_query;
		} else {
	    	$query = "SELECT id FROM image_info WHERE category_id=$cat_id ".
		     	"AND id>$image_id AND access<=${userinfo['access']}";
	}
		if (!strstr($query,"ORDER BY")) { $query .= " ORDER BY id"; }
		$result = mysql_query($query) or db_error($query);
		while ($image_info = mysql_fetch_row($result)) {
	    	array_push($imageList,$image_info[0]);
		}
		for($i=0;$i<count($imageList);$i++) {
	    	if ($imageList[$i] == $image_id) {
	      		$last = $i + 1;
	      		if ($last < count($imageList)) {
					$nextImage = $imageList[$last];
	      		}
	    	}
		}
		if (empty($nextImage)) { $nextImage = ""; }
		return $nextImage;
    }

    function getLastImage($image_id,$last_query) {
		global $userinfo,$sort;
		$cat_id = getCat($image_id);
		$imageList = array();
		if (!empty($last_query)) {
	    	$query = $last_query;
		} else { 
	    	$query = "SELECT id FROM image_info WHERE category_id=$cat_id ".
		     	"AND id<$image_id AND access<=$userinfo[access]";
		}
		if (!strstr($query,"ORDER BY")) { $query .= " ORDER BY id"; }
		if (!empty($sort)) { $query .= " $sort"; }
		$result = mysql_query($query) or db_error($query);
		while ($image_info = mysql_fetch_row($result)) {
	    	array_push($imageList,$image_info[0]);
		}
		for($i=0;$i<count($imageList);$i++) {
	
	    	if ($imageList[$i] == $image_id) {
	
			if ($i > 0) {
		    	$last = $i - 1;
			} else {
		    	$last = 0;
			}

			$lastImage = $imageList[$last];

	    	}
		}
		if (empty($lastImage)) { $lastImage = ""; }

		return $lastImage;
    }

    function displayImage($image_id) {
		global $config,$userinfo,$_SERVER;
		if (isset($_GET['cat_thumb'])) {
			$cat_thumb = $_GET['cat_thumb'];
		}

		if (isset($_GET['w']) && isset($_GET['h'])) {
			$default_w = $_GET['w'];
			$default_h = $_GET['h'];
		} else {
			$default_w = $userinfo['default_w'];
			$default_h = $userinfo['default_h'];
		}


		$img_stats = GetImageDimensions($image_id);
		if (isset($cat_thumb)) {
			$default_w = $config['cat_thumb_w'];
			$default_h = $config['cat_thumb_h'];
			$default_h = 150;
			$table = "image_thumb";
			$src_w = $img_stats[2];
			$src_h = $img_stats[3];
		} else {
			$table = "image_file";
			$src_w = $img_stats[0];
			$src_h = $img_stats[1];
		}
	
		#if (!isset($default_w) || !isset($default_h)) {
	    	#$default_w = $config['default_w'];
	    	#$default_h = $config['default_h'];
		#}
		if (isset($_SERVER['HTTP_REFERER'])) {
	 		if (strstr($_SERVER['HTTP_REFERER'],"image/view.php")) {
	    		make_history("view","",$image_id,"image_info");
	    		mysql_query("UPDATE image_info SET views=views+1,last_view=NOW() WHERE id='$image_id'");
	 		}
		}
		$dst_h = $default_h;
		$dst_w = ($dst_h / $src_h) * $src_w;
		#if ($dst_w > $src_w) {
	     	#$dst_w = $src_w;
	     	#if ($dst_h > $src_h) {
				#$dst_h = $src_h;
		 	#}
		#}
		#if ($dst_h > $default_h) {
	    	#$dst_h = $default_h;
	    	#$dst_w = ($dst_h / $src_h) * $src_w;
		#}
		$query = "SELECT d.data,i.content_type,i.filename,i.width,i.height FROM $table d, image_info i where d.image_id='${image_id}' and i.id=d.image_id";
		$result = mysql_query($query) or db_error($query);
		$data = mysql_fetch_row($result);
		$src_img = @imagecreatefromstring($data[0]);
		if (!$src_img) {
			$src_img = imagecreatetruecolor($data[3], $data[4]);
		}
		$dst_img = imagecreatetruecolor($dst_w,$dst_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$dst_w,$dst_h,$src_w,$src_h);
		if (empty($data[2])) {
	    	list($j,$ext) = split("/",$data[1]);
	    	$data[2] = $image_id . ".$ext";
		}
		if (isset($cat_thumb)) {
	    	#$arrow = imagecreatefrompng("${config['base_dir']}/htdocs/images/arrow-test.png");
	    	#$arrow_x = imagesx($arrow);
	    	#$arrow_y = imagesy($arrow);
	    	#imagecolortransparent($arrow, imagecolorallocate($arrow, 255, 0 , 255) );
	    	#imagecopymerge($dst_img,$arrow,0,0,0,0,$arrow_x,$arrow_y,100);
		}
		header('Content-type: '.$data[1]);
		header('Content-disposition: inline; name=small_'.$data[2]);
		ImageJpeg($dst_img);
		ImageDestroy($dst_img);
    }

    function displayCatImage($cat_id) {
		$result = mysql_fetch_row(mysql_query("SELECT data FROM category_pics WHERE category_id=$cat_id"));
		header('Content-type: image/jpeg');
		header('Content-Disposition: inline; filename=cat_thumb_'.$cat_id.'.jpg');
		echo $result[0];
    }

    function GetImageDimensions($image_id) {
		$query = "SELECT width,height,thumb_width,thumb_height FROM image_info WHERE id='${image_id}'";
		$result = mysql_query($query) or db_error($query);
		$data = mysql_fetch_row($result);
		return $data;
    }

    function getImageDetails($image,$detail) {
		global $config;

		$image['photographer_name'] = getName($image['photographer']);
		$imgURL = "${config['base_url']}/main.php?showImage&image_id=${image['id']}";
		if (empty($image['date_taken'])) {
	    	$image['date_taken'] = "<i>unknown date</i>";
		}
	
		if (!isset($image['dimensions'])) {
	    	$image['dimensions'] = $image['width'] . ' x ' . $image['height'];
		}
	
		$people = get_image_people($image['id']);
		$count = mysql_num_rows($people);
		$peopleInImage = "";
		if ($count > 0) {
	    	while ($inImage = mysql_fetch_row($people)) {
			$peopleInImage .= "\n\t\t\t\t&nbsp;&nbsp;<a href='${config['base_url']}/profile/view.php?user_id=$inImage[1]'>$inImage[2]</a><br>";
	    	}
		}
	
		$data = "\t\t  <table cellspacing=0 cellpadding=0><td width=5>&nbsp;</td><td>\n".
			"\t\t    <table cellspacing=3 cellpadding=0 class=title2>\n".
			"\t\t\t<tr><td><u>Taken on:</td></tr>\n".
			"\t\t\t<tr><td>&nbsp;&nbsp;${image['date_taken']}<div style='height:5px'></div></td></tr>\n".
			"\t\t\t<tr><td><u>Resolution:</td></tr>\n".
			"\t\t\t<tr><td>&nbsp;&nbsp;${image['dimensions']}<div style='height:5px'></div></td></tr>\n".
			"\t\t\t<tr><td><u>Times viewed:</td></tr>\n".
			"\t\t\t<tr><td>&nbsp;&nbsp;${image['views']} times<div style='height:5px'></div></td></tr>\n".
			"\t\t\t<tr><td><u>Photographer:</td></tr>\n".
			"\t\t\t<tr><td>&nbsp;&nbsp;${image['photographer_name']}<div style='height:5px'></div></td></tr>\n";
		if (!empty($peopleInImage)) {
	    	$data .= "\t\t\t<tr><td><u>People in image:</td></tr>\n".
		     	"\t\t\t<tr><td>$peopleInImage<div style='height:5px'></div></td></tr>\n";	
		}
		$data .= "\t\t\t<tr><td><u>Camera used:</td></tr>\n".
		 	"\t\t\t<tr><td>&nbsp;&nbsp;${image['camera']}<div style='height:5px'></div></td></tr>\n";
		if ($detail == "full") {
	    	$data .= "\t\t\t<tr><td><u>EXIF data:</td></tr>\n".
		     	"\t\t\t<tr><td style='font-size:11px'>";
	    	$exif = array();
	    	if (count($exif) > 1) {
			foreach($exif as $key=>$section) {
		    	foreach($section as $name=>$val) {
	
					if ($name != "UserComment") {
			    		if (is_array($val)) {
							$newval = "<table>";
							foreach ($val as $newkey => $newvalue) {
				    			$newval .= "<tr><td>$newkey</td><td>$newvalue</td></tr>";
							}
							$newval .= "</table>";
			    			} else {
								$newval = $val;
			    			}
			
			    			$data .= "$key.$name: $newval<br />\n";
						}
		    		}
				}
	    	} else {
				$data .= "&nbsp;&nbsp;<i>none</i>";
	    	}
	    	$data .= "<div style='height:5px'></div></td></tr>\n";
		}
		$data .= "\t\t    </table>\n\t\t  </td></table>\n";
		return $data;
    }

    function getNextLastLinks($image_id) {
		global $config,$userinfo,$_COOKIE;
		if (empty($_COOKIE['last_query'])) { return; }
	
		$last_image_id = getLastImage($image_id,$_COOKIE['last_query']);
		$next_image_id = getNextImage($image_id,$_COOKIE['last_query']);
	
		if (!empty($last_image_id) && $last_image_id != $image_id) {
	    	$lastImageUrl = "<a href='${config['base_url']}/image/view.php?image_id=${last_image_id}'>< Prev</a>";
		} else {
	    	$lastImageUrl = "<font class=nolink>< Prev</font>";
		}
	
		if (!empty($next_image_id)) {
	     	$nextImageUrl = "<a href='${config['base_url']}/image/view.php?image_id=${next_image_id}'>Next ></a>";
		} else {
	     	$nextImageUrl = "<font class=nolink>Next ></font>";
		}
	
		return array("prev" => $lastImageUrl,"next" => $nextImageUrl);
    }

    function lastAddedImage() {
		global $userinfo;
		$max = mysql_fetch_row(mysql_query("select max(id) from image_info where (private!='1' or owner=$userinfo[id]) AND access<=$userinfo[access]"));
		$query = "select id,descr from image_info where id=$max[0]";
		$result = mysql_fetch_array(mysql_query($query));
		$result['virgin_descr'] = $result['descr'];
		$result['descr'] = htmlentities($result['descr'],ENT_QUOTES);
		$result['descr'] = wordwrap($result['descr'],36,"<br>\n");
		$result['virgin_descr'] = htmlentities($result['virgin_descr'],ENT_QUOTES);
		return $result;
    }

    function lastViewedImage() {
		global $userinfo;
		$last_view = mysql_fetch_row(mysql_query("select id from image_info where (private!='1' or owner=$userinfo[id]) AND access<=$userinfo[access] order by last_view desc limit 1"));
		$query = "select id,descr from image_info where id='$last_view[0]' ";
		$result = mysql_fetch_array(mysql_query($query));
		$result['virgin_descr'] = $result['descr'];
		$result['descr'] = htmlentities($result['descr'],ENT_QUOTES);
		$result['descr'] = wordwrap($result['descr'],36,"<br>\n");
		$result['virgin_descr'] = htmlentities($result['virgin_descr'],ENT_QUOTES);
		return $result;
    }

    function randomImage() {
		global $userinfo;
		$rand_id = mysql_fetch_row(mysql_query("select id from image_info where (private!='1' or owner=$userinfo[id]) AND access<=$userinfo[access] order by rand() limit 1"));
		$query = "select id,descr from image_info where id='$rand_id[0]' ";
		$result = mysql_fetch_array(mysql_query($query));
		$result['virgin_descr'] = $result['descr'];
		$result['descr'] = htmlentities($result['descr'],ENT_QUOTES);
		$result['descr'] = wordwrap($result['descr'],36,"<br>\n");
		$result['virgin_descr'] = htmlentities($result['virgin_descr'],ENT_QUOTES);
		return $result;
    }

    function displayUserImage($user_id) {
		$query = "SELECT user_image,username FROM user WHERE id=$user_id";
		$result = mysql_query($query) or db_error($query);
		$data = mysql_fetch_row($result);
		header('Content-type: image/jpeg');
		header('Content-Disposition: inline; filename=user_'.$data[1].'.jpg');
		echo $data[0];
    }

    function userPicUpload($user_id,$userPicFile) {
		$file = "/tmp/$user_id-tmp.jpg";
		$type = exif_imagetype($userPicFile);
		thumb_create($userPicFile,$file,"100","150");
		$fp = fopen ($file,'r');
		$user_pic_data = addslashes( fread( $fp, filesize($file) ) );
		fclose($fp);
		mysql_query("UPDATE user SET user_image=\"$user_pic_data\" WHERE id=$user_id");
		unlink($file);
    }

    function thumb_create($image,$outputFile = '',$dX = '',$dY = '',$type = '') {
		if (empty($dX) && empty($dY)) { $dX = "200"; $dY = "250"; }
		$srcX = @imagesx($image);
		$srcY = @imagesy($image);
		// checks to see if $image is already an image resource
		if (!$srcX && !$srcY) {
			list($srcX,$srcY,$type_id,$srcAttr) = GetImageSize($image);
			$types = array("","GIF","JPEG","PNG");
			$type = $types[$type_id];
			$createFunc = "ImageCreateFrom$type";
			$imageFunc = "Image$type";
			$src_img = $createFunc($image);
		} else {
			$src_img = $image;
			$imageFunc = "Image$type";
		}
		$dstX = $dX;
		$dstY = ($dstX / $srcX) * $srcY;
		if ($dstY > $dY) {
	    	$dstY = $dY;
	    	$dstX = ($dstY / $srcY) * $srcX;
		}
		if ($dstX > $srcX) {
	    	$dstX = $srcX;
	    	if ($dstY > $srcY) {
			$dstY = $srcY;
	    	}
		}
		$dst_img = ImageCreateTrueColor($dstX,$dstY);
		ImageCopyResampled($dst_img,$src_img,0,0,0,0,$dstX,$dstY,$srcX,$srcY);
		if (empty($outputFile)) {
			ob_start();
			$imageFunc($dst_img);     
			$buffer = ob_get_contents();
			ob_end_clean();
			ImageDestroy($dst_img);
			return $buffer;
		} else {
			$imageFunc($dst_img,$outputFile);
		}
    }

    function add_people($image_id,$people) {
		if (!is_array($people)) { return; }
		$count = count($people);
		if ($count > 0) {			## Check to see if anyone was selected
	    	for($i=0;$i<$count;$i++) {		#    to be added
	
				$check_res = mysql_query("SELECT id FROM image_people WHERE image_id='${image_id}' AND user_id='${people[$i]}'");
				$check = mysql_fetch_row($check_res);
		
				if (empty($check)) {
		    		$query = "INSERT INTO image_people (id,user_id,image_id) VALUES (NULL,'${people[$i]}','${image_id}')";
		    		mysql_query($query) or db_error($query);
				}
	    	}
		}
    }

    function del_people($image_id,$people) {
		if (!is_array($people)) { return; }
		$count = count($people);
		if ($count > 0) {			## Check to see if anyone was selected
	    	for($i=0;$i<$count;$i++) {		#   to be removed
				$query = "DELETE FROM image_people WHERE user_id='${people[$i]}' AND image_id='${image_id}'";
				mysql_query($query) or db_error($query);
	    	}
		}
    }

    function build_jump_points($imagecount,$offset,$limit,$url) {
		$jumps = array();
		$data = '';
		$next = '';
		$prev = '';
		$n = 1;
	
		$jumpBwCheck = $offset - ($limit * 4);
	
		if ($jumpBwCheck < 0) {
	    	$bwAdjust = (($jumpBwCheck + $offset) / -($limit));
		}

		if (isset($bwAdjust)) {
	    	if ($bwAdjust <= 0) {
			$bwAdjust = 0;
	    	}
		} else {
	    	$bwAdjust = 0;
		}

		$jumpFwCheck = $offset + ($limit * 6);

		if ($jumpFwCheck > $imagecount) {
	    	$fwAdjust = (($jumpFwCheck - $offset) / ($limit));
		} else {
	    	$fwAdjust = 0;
		}
		$jumpBwRestrict = $offset - ($limit * (4 + $fwAdjust));
		$jumpFwRestrict = $offset + ($limit * (6 + $bwAdjust));
		for ($x=0;$x<$imagecount;$x+=$limit) {
	    	if ($x == $offset) {
				$title = "Page $n";
				$jump = "$n";
				$nextJump = $limit * ($n);
				$prevJump = $limit * ($n - 2);
				if ($nextJump < $imagecount) {
		    		$next = "<a href='${url}&offset=${nextJump}'>Next >></a>";
				}
				if ($prevJump >= 0) {
		    		$prev = "<a href='${url}&offset=${prevJump}'><< Prev</a>";
				}
				array_push($jumps,"$jump");
	    	} elseif ($x >= $jumpBwRestrict && $x <= $jumpFwRestrict) {
				$jump = "<a href='${url}&offset=${x}'>$n</a>";
				array_push($jumps,"$jump");
	    	} else { } 
	    	$n++;
	    	unset($jump);
		}
		if (!isset($prev)) { $prev = ""; }
		if (count($jumps) > 1) { 
	    	if ($imagecount > 1) {
				$imagecount = "($imagecount images)";
	    	} elseif ($imagecount == 1) {
				$imagecount = "($imagecount image)";
	    	} else {
				$imagecount = "";
	    	}
	    	$jumpPoints = implode($jumps,"&nbsp;&nbsp;");
	    	$data = "<b>Result pages $imagecount:</b><br>$prev&nbsp;&nbsp;$jumpPoints&nbsp;&nbsp;$next<br>";
		}
		return $data;
    }
?>
