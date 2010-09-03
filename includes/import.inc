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

    function listUserUploadDir() {
	global $config,$userinfo;
	$path = $config['uploadDir'] . "/user_id-" . $userinfo['id'];
	$content = "<table><td width=50>&nbsp;</td><td>";
	if ($dir = @opendir($path)) {
	    while (($file = readdir($dir)) !== false) {
		if(!strstr($file,".")) {
		    $list = array();
		    if ($udir = @opendir("$path/$file")) {
			while (($ufile = readdir($udir)) !== false) {
			    if (strstr($ufile,".JPG") || strstr($ufile,".jpg")) {
				if (!strstr($ufile,"thumb-")) { array_push($list,$ufile); }
			    }
			}
		    }
		    $filecount = count($list);
		    if ($filecount > 1) { $s = "s"; }
		    if ($filecount == 0) { system("rm -rf $path/$file"); }
		    $content .= "<br><a href='?browseImport&importPath=user_id-$userinfo[id]/$file'>".
				"$file</a> <font class='9pt'>($filecount image$s)</font>";
		}
	    }
	    closedir($dir);
	}
	$content .= "</td></table>";
	return $content;
    }

    function listUserUploadDirContents($path) {
	global $config,$userinfo;
	$filelist = array();
	if ($dir = @opendir($path)) {
	    while (($file = readdir($dir)) !== false) {
		if(!strstr($file,".")) { array_push($filelist,$file); }
	    }
	    closedir($dir);
	}
	sort($filelist);
	return $filelist;
    }

    function importImage($importFile) {
		global $config,$userinfo;

		$sql_image = $_POST['sql_image'];
		$use_exif = $_POST['use_exif'];
		$addPeople = $_POST['addPeople'];
		$delete = $_POST['delete'];
		$confirm = $_POST['confirm'];
		$refer_back = $_POST['refer_back'];

		$filename_seg = split("/",$importFile);
		$seg_count = count($filename_seg);
		$last_seg = $seg_count - 1;
		$filename = $filename_seg[$last_seg];
		$fieldName = array(); $fieldData = array();
		if (empty($importFile)) { return "Nice try bub"; }
		if (isset($sql_image)) {
	     	error_reporting(0);
	    	$exif = exif_read_data($importFile, 0, true);
	    	error_reporting(1);
	    	if (isset($use_exif)) {
				list($exif_date,$exif_time) = split(" ",$exif['EXIF']['DateTimeOriginal']);
				if (!empty($exif_date)) {
		    		$exif_date = str_replace(":","-",$exif_date);
		    		$sql_image['date_taken'] = $exif_date;
		    		if (!empty($exif_time)) {
						$sql_image['timestamp'] = "$exif_date $exif_time";
		    		}
				}
	   		}
	   	if (!empty($exif['IFD0']['Model'])) {
			$sql_image['camera'] = $exif['IFD0']['Model'];
			$test = str_replace(' ','',$sql_image['camera']);
			if (empty($test)) { $sql_image['camera'] = "unknown"; }
	   	} else { $sql_image['camera'] = "unknown"; }
	    	foreach ($sql_image as $key => $value) {
				array_push($fieldName,$key);
				array_push($fieldData,$value);
	    	}
		}
		$countAdd = count($addPeople);	  ## Count number of people to add
		if ($private == "on") { $private = 1; } ## Reassign $private to 1 if checked
		$file = $importFile;
		$pieces = explode("/",$file);
		$count = count($pieces) - 1;
		$pieces[$count] = "thumb-".$pieces[$count];
		$filethumb = implode("/",$pieces);
		list($filethumb_x,$filethumb_y,$filethumb_type,$filethumb_attr) = getimagesize($filethumb);
		$fp = fopen ($file,'r');
		$image_file = addslashes( fread( $fp, filesize($file) ) );
		fclose($fp);
		list($file_x,$file_y,$type,$file_attr) = getimagesize($file);
		if ($type == 2) { $file_type = "image/jpeg"; }
		if ($type == 3) { $file_type = "image/png"; }
	
		$fp = fopen ($filethumb,'r');
		$image_thumb = addslashes( fread( $fp, filesize($filethumb) ) );
		fclose($fp);
		
		$query = "INSERT INTO image_info (id,".implode($fieldName,",").",added,modified,width,".
		 	"height,thumb_height,thumb_width,content_type,filename) ".
		 	"VALUES (NULL,\"".implode($fieldData,"\",\"")."\",".
		 	"NOW(),NOW(),'${file_x}','${file_y}','${$filethumb_x}','${filethumb_y}','${file_type}','${filename}')";
		mysql_query($query) or db_error($query);
		$image_id = mysql_insert_id() or db_error("getting image_id");
		$queryFile = "INSERT INTO image_file VALUES(\"".
		     	"$image_id\",\"".$image_file."\")";
		mysql_query($queryFile) or db_error($queryFile);
		$queryThumb = "INSERT INTO image_thumb VALUES(\"".
		      	"$image_id\",\"".$image_thumb."\")";
		mysql_query($queryThumb) or db_error($queryThumb);
		if ($countAdd > 0) {			## Check to see if anyone was selected
	    	for($i=0;$i<$countAdd;$i++) {		#    to be added
				$aQuery = "INSERT INTO image_people(id,user_id,image_id)\n".
			  		"VALUES(NULL,\"${addPeople[$i]}\",$image_id)";
				mysql_query($aQuery) or db_error($aQuery);
	    	}
		}
		$content .= "Successfully imported: $filename<br>\n";
		make_history("add",$filename,$image_id,"image_info");
		return $content;
    }

    function categoryPicUpload($cat_id,$catPicFile) {
		global $config,$userinfo;
		$file = "/tmp/$cat_id-tmp.jpg";
		$type = exif_imagetype($file);
		thumb_create($catPicFile,$file,"100","150",$type);
		$fp = fopen ($file,'r');
		$cat_pic_data = addslashes( fread( $fp, filesize($file) ) );
		fclose($fp);
		mysql_query("DELETE FROM category_pics WHERE category_id=$cat_id");
		mysql_query("INSERT INTO category_pics VALUES ($cat_id,\"$cat_pic_data\")");
		unlink($file);
    }

    function gen_thumbs($dir) {
	global $config;
	$data = array();
	$file_output = shell_exec("find $dir -print");
	$file_list = split("\n",$file_output);
	for($i=0;$i<count($file_list);$i++) {
	    if (!empty($file_list[$i])) {
		$file = str_replace("./","",$file_list[$i]);
		if (!strstr($file,"thumb-")) {
		    $new_file = str_replace("[ -]","_",$file);
		    rename($file,$new_file);
		    if (is_file($new_file)) {
			$pieces = explode("/",$new_file);
			$count = count($pieces) - 1;
			$pieces[$count] = "thumb-".$pieces[$count];
			$thumb_name = implode("/",$pieces);
			$type = exif_imagetype($new_file);
			if ($type == 2 || $type == 3) {
			    thumb_create($new_file,$thumb_name,"","");
			    array_push($data,$new_file);
			}
		    }
		}
	    }
	}
	return $data;
    }

?>
