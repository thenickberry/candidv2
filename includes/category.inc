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

    function updateCategory() {
	global $config;

	$sql_category = $_POST['sql_category'];
	if (isset($_POST['assignCatImage'])) {
		$assignCatImage = $_POST['assignCatImage'];
	}

	$fieldNames = array(); $fieldData = array(); $setParamArray = array();

	if (empty($sql_category['parent'])) { $sql_category['parent'] = 0; }
	if (isset($sql_category['haskids'])) {
		if ($sql_category['haskids'] == "on") { $sql_category['haskids'] = 1; } else { $sql_category['haskids'] = 0; }
	}

	$query = build_query('category',$sql_category);

	if (isset($_POST['add'])) {
	    $content = "Created category <b>${sql_category['name']}</b> successfully!";
	    $result = mysql_query($query) or db_error($query);
	} elseif (isset($_POST['delete'])) {
	    $content = "Deleted category <b>${sql_category['name']}</b> successfully!";
	    $resultCheck = mysql_query("SELECT COUNT(id) FROM image_info WHERE category_id='${sql_category['id']}'");
	    $countCheck = mysql_fetch_array($resultCheck);
	    if ($countCheck[0] == 0) {
		$query = "DELETE FROM category WHERE id=${sql_category['id']}";
		mysql_query($query) or db_error($query);
	    } else { $content = "Images still exist in that category!"; }
	} elseif (isset($_POST['update'])) {
	    $content = "Updated category <b>${sql_category['name']}</b> successfully!";
	    $result = mysql_query($query) or db_error($query);
	    mysql_query("UPDATE category SET modified=NOW() WHERE id='${sql_category['id']}'");
	}
	if (!empty($assignCatImage)) {
	    $image_id = $assignCatImage;
	    $file = "/tmp/$image_id-thumb.jpg";
	    $url = "${config['base_url']}/main.php?showImage&image_id=${image_id}&thumb=yes";
	    list($w,$h,$type,$a) = getimagesize($url);
	    thumb_create($url,$file,"100","125",$type);
	    $fp = fopen ($file,'r');
	    $data = addslashes( fread( $fp, filesize($file) ) );
	    fclose($fp);
	    mysql_query("DELETE FROM category_pics WHERE category_id=${sql_category['id']}");
	    mysql_query("INSERT INTO category_pics VALUES (${sql_category['id']},\"$data\")");
	    unlink($file);
	    $content =  "Set category picture id to: $assignCatImage<br>\n";
	    $content .= "<img src=\"$url\">\n";
	}

	return $content;
    }

    function getCategoryList($pid,$spacing,$selected) {
	$output = "";
	$haskids_sql = "";
	if (empty($pid)) { $pid = 0; }
	    $query = "SELECT id,name,haskids,parent FROM category WHERE parent='$pid' $haskids_sql ORDER BY haskids,name ASC";
	$result = mysql_query($query) or db_error($query);
	while ($category = mysql_fetch_array($result)) {
	    if ($selected == $category['id']) {
		$select = "selected";
	    } else { $select = ""; }
	    if ($category['haskids'] == "1") {
		$tab = "&nbsp;&nbsp;&nbsp;&nbsp;$spacing";
		$output .= "   <option value=${category['id']} $select>$spacing${category['name']}".
			   "</option>".getCategoryList($category['id'],$tab,$selected);
	    } else {
		$output .= "    <option value=${category['id']} $select>$spacing${category['name']}</option>";
	    }         
	}
	return $output;
    }

    function getCategoryList2($pid,$branch,$pp,$url) {
	global $userinfo;
	$output = "";
	$haskids_sql = "";
	if (empty($pid)) { $pid = 0; }
	$query = "SELECT id,name,haskids,parent,owner FROM category WHERE parent='$pid' $haskids_sql ORDER BY name,haskids ASC";
	$result = mysql_query($query) or db_error($query);
	while ($category = mysql_fetch_array($result)) {
	    if (($userinfo['id'] == $category['owner']) || ($userinfo['access'] == 5)) {
		$tmpurl = $url . $category['id'];
		$nourl = 0;
	    } else {
		$tmpurl = "javascript:undefined";
		$nourl = 1;
	    }
	    if ($category['haskids'] == "1") {
		if (!empty($pp)) { $newbranch = $branch+1; } else { $newbranch = $branch; }
		if ($category['parent'] != "0") { $p = "ou${branch}"; } else { $p = "foldersTree"; }
		$output .= "ou${newbranch} = insFld($p, gFld(\"${category['name']}\", \"${tmpurl}\"))\n".
			    getCategoryList2($category['id'],$newbranch,"1",$url). "\n";
	    } else {
		if ($nourl == 1) { $tmpurl = ""; } else { $tmpurl = $tmpurl; }
		$output .= "\tinsDoc(ou${branch}, gFld(\"${category['name']}\", \"${tmpurl}\"))\n";
	    }         
	}
	return $output;
    }

   function getCategoryInfo($cat_id) {
	$query = "SELECT id,name,haskids,loc,descr,modified,added,owner,parent,public,sort_by,category_image_id FROM category WHERE id='$cat_id'";
	$result = mysql_query($query) or db_error($query);
	$data = mysql_fetch_array($result);
	if (empty($data['modified'])) {
	    $data['modified'] = "<i>never</i>";
	} else {
	    $data['modified'] = date('l, M d Y h:ia',strtotime($data['modified']));
	}
	return $data;
    }

    function browseCat($pid) {
	global $config,$userinfo;
	if (empty($pid)) {
	    $limit = "(parent='0' OR parent=null)";
	} else {
	    $limit = "parent='$pid'";
	}

	$query = "SELECT c.id,c.name,c.descr,c.owner,c.loc,c.parent,c.haskids,c.public,c.category_image_id FROM category c WHERE $limit GROUP BY id ORDER BY name";
	    //DATE_FORMAT(MAX(i.added),'%a %b %r %Y') as last_added

	$result = mysql_query($query) or db_error($query);
	$content = "\n\t\t\t<table cellpadding=10 cellspacing=0 style='border: 0px solid black' width=840>\n";
	$column = 0;
	while ($category = mysql_fetch_array($result)) {
	    if (!empty($category['category_image_id'])) {
		$cat_image = "<map name=\"${category['id']}\"><area shape=rect coords=10,10,100,100 href='${config['base_url']}/main.php?browse&cat_id=${category['id']}'><area shape=rect coords=0,0,10,10 href='${config['base_url']}/image/view.php?image_id=${category['category_image_id']}'></map><img src='${config['base_url']}/main.php?displayImage&image_id=${category['category_image_id']}&cat_thumb=yes' border=0 usemap=\"#${category['id']}\">";
	    } else {
		$cat_image = "";
	    }
	    $category['last_added'] = "N/A";
	    $url = buildURL($category['id']);

	    $catinfo = getParents($category['id'],"1");
	    list($imagecount,$updated) = $catinfo;
	    $update_date = date("M. d, Y",$updated);
	    if (isset($userinfo['update_notice'])) {
		$secs = $userinfo['update_notice'];
	    } else {
		$secs = 86400 * 7;
	    }
	    if ($updated > time()-($secs)) {
		$lastAdded = "<font style='color: red'>$update_date</font>";
	    } else {
		if ($imagecount == 0) { $update_date = "<i>never</i>"; }
		$lastAdded = $update_date;
	    }
	    if (!empty($row) && empty($column)) {
		$bgcolor = "#eee";
		$row = 0;
	    } elseif (empty($row) && empty($column)) {
		$bgcolor = "#fff";
		$row = 1;
	    } else { } 
	    if ($column == 0) { $content .= "\t\t\t<!-- start -->\n\t\t\t<tr valign=top>\n"; }
		$content .= "\t\t\t\t<td>\n".
			"\t\t\t\t\t<div id='category'>\n".
			"\t\t\t\t\t$cat_image\n".
			"\t\t\t\t\t<div style='width:420'>\n".
			"\t\t\t\t\t\t<a href=$url style='font-size:12px'>${category['name']}</a>";
		if (($userinfo['id'] == $category['owner'] || $userinfo['access'] == 5) && isset($_COOKIE['edit'])) {
		    $content .= "&nbsp; [ <a href='${config['base_url']}/category/edit.php?cat_id=${category['id']}'>edit</a> ]";
	    }
	    $content .= "\n\t\t\t\t\t<br>\n";
	    if (!empty($category['descr'])) {
		$content .= "\t\t\t\t\t${category['descr']}\n\t\t\t\t\t<br>\n";
	    }
	    if (!empty($category['loc'])) {
		$content .= "\t\t\t\t\t<b>${category['loc']}</b>\n\t\t\t\t\t<br>\n";
	    }
	    if (!empty($lastAdded)) { $id = "cat_id_${category['id']}";
		$updated = "\t\t\t\t\tUpdated $lastAdded\n\t\t\t\t<br>\n";
	    } else { $updated = ""; }
	    $content .= "\t\t\t\t\t$imagecount images\n\t\t\t\t\t<br>\n".
			"$updated".
			"\t\t\t\t</div>\n\t\t\t<!-- $column -->\n\t\t\t</td>\n";
	    $column++;
	    if ($column == "2") {
		$column = 0;
		$content .= "\t\t</tr>\n  <!-- end -->";
	    }
	}
	$length = strlen($content);
	$slen = $length - 8; $elen = $length - 5;
	$check = substr($content,$slen,$elen);
	if ($check != "end") {
	    $content .= "<td>&nbsp;</td></tr>\n<!-- end -->";
	} else { print "-$check-"; }
	$content .= "</table>";
	if (mysql_num_rows($result) == 0) { $content = ""; }
	return $content;
    }

    function getParentList() {
	$parentList = getCategoryList("","","");
	return $parentList;
    }

    function lastAdded($cat_id) {
	$array = array();
	$query = "SELECT DATE_FORMAT(MAX(added),'%m/%d/%Y') last_added FROM image_info info WHERE category_id='$cat_id'";
	$result = mysql_query($query) or db_error($query);
	$category = mysql_fetch_row($result);
	return $category[0];
    }

    function getCat($image_id) {
		if (is_array($image_id)) { $image_id = $image_id[0]; }
		if (!empty($image_id)) {
		$result = mysql_fetch_row(mysql_query("SELECT category_id FROM image_info WHERE id=$image_id"));
		return $result[0]; }
    }
    function getCategoryName($id) {
		if (!empty($id)) {
			$q = "SELECT name FROM category WHERE id=${id}";
			$r = mysql_query($q) or db_error($q);
			$d = mysql_fetch_row($r);
			return $d[0];
		} else {
			return;
		}
    }

    function listCatThumbs($cat_id,$sel_im_id) {
	$im_q = "SELECT id,filename FROM image_info WHERE category_id=$cat_id ORDER BY id";
	$im_r = mysql_query($im_q);
	while ($im = mysql_fetch_row($im_r)) {
	   echo "<option value=$im[0]";
	   if ($im[0] == $sel_im_id) {
		echo " selected";
	   }
	   echo ">$im[1] (id: $im[0])</option>\n";
	}

	$c_q = "SELECT id,name FROM category WHERE parent=$cat_id";
	$c_r = mysql_query($c_q) or db_error($c_q);
	if (mysql_num_rows($c_r) > 0) {
	    while ($c = mysql_fetch_row($c_r)) {
		$im_q = "SELECT i.id,concat('/',i.filename),c.name FROM image_info i,category c WHERE i.category_id=c.id AND i.category_id=$c[0]";
		$im_r = mysql_query($im_q) or db_error($im_q);
		while ($im  = mysql_fetch_row($im_r)) {
		    echo "<option value=$im[0]";
		    if ($im[0] == $sel_im_id) {
		        echo " selected";
		    }
		    echo ">$c[1]$im[1] (id: $im[0])</option>\n";
		}
	    }
	}
    }


?>
