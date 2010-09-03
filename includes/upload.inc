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

    function uploadImage() {
		global $config,$userinfo;

		$content = "";
		$zipType = array("application/x-zip-compressed","application/zip");

		$type = $_FILES['userfile']['type'];

		if (!$userinfo['id']) { print "You're not logged in.. that's a problem"; exit; }
		$today = date("Y-m-d",time());
		$userUploadDir = $config['uploadDir']."/user_id-".$userinfo['id'];
		$destDir = $userUploadDir . "/$today";
		system("mkdir $userUploadDir; mkdir $destDir");
  	
		$filename = str_replace(" ","_",$_FILES['userfile']['name']);
		$filename = str_replace("-","_",$filename);


		if (!is_uploaded_file($_FILES['userfile']['tmp_name'])) {
			print "<!-- ";print_r($_FILES);print"-->";
	    	return "There is a problem..." . get_upload_error($_FILES['userfile']['error']);
		}

		move_uploaded_file($_FILES['userfile']['tmp_name'],"$destDir/$filename");

		if(in_array($type,$zipType)) {
	    	$content .= "<table><td><ul>Deflating $filename (${type})<ul>";
	    	system("$config[unzip] $destDir/$filename -d $destDir 1>&2");
	    	unlink("$destDir/$filename");
	    	$files = gen_thumbs($destDir);
	    	for($i=0;$i<count($files);$i++) {
				$files[$i] = str_replace($destDir."/","",$files[$i]);
				$content .= "<li>$files[$i]</li>\n";
	    	}
	    	$content .= "</ul></ul></td></table>\n";
		} elseif ($type == "application/x-tar" || $type == "application/gzip") {
	    	if (strstr($filename,".tar.gz") || strstr($filename,".tgz")) {
				$tarArgs = "zxf";
	    	} else {
				$tarArgs = "xf";
	    	}
	    	$content .= "<table><td><ul>Deflating $filename (${type})<ul>";
	    	$cmd = "tar $tarArgs $destDir/$filename -C $destDir/";
	    	system($cmd);
	    	unlink("$destDir/$filename");
	    	$files = gen_thumbs($destDir);
	    	for($i=0;$i<count($files);$i++) {
				$files[$i] = str_replace($destDir."/","",$files[$i]);
				$content .= "<li>$files[$i]</li>\n";
	    	}
	    	$content .= "</ul></ul></td></table>\n";
	    	if (strlen(str_replace("thumb-","",$filename)) > 4) {
	        	list($w,$h,$file_type,$a) = getimagesize("$destDir/$filename");
	        	thumb_create("$destDir/$filename","$destDir/thumb-$filename","","",$file_type);
	    	}
		} elseif (strstr($type,'jpeg')) {
	    	$content .= "File uploaded successfully!";
	    	thumb_create("$destDir/$filename","$destDir/thumb-$filename","","",$type);
		} else {
	    	$content .= "Problem uploading file, check php.ini and force ".
				"upload_tmp_dir to /tmp.<br>".
				$_FILES['userfile']['error'] . '<br>' . $type;
		}
		return $content;
    }

    function uploadCatImage($cat_id,$_FILES) {
		global $config,$userinfo;

		if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
	    	categoryPicUpload($cat_id,$_FILES['userfile']['tmp_name']);
		}
    }

    function uploadUserImage($user_id,$_FILES) {
		global $config,$userinfo;

		if (is_uploaded_file($_FILES['user_image']['tmp_name'])) {
	    	userPicUpload($user_id,$_FILES['user_image']['tmp_name']);
		}
    }

	function get_upload_error($code) {

		$errors = array(
					0=>"There is no error, the file uploaded with success", 
					1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini", 
					2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
					3=>"The uploaded file was only partially uploaded",
					4=>"No file was uploaded",
					6=>"Missing a temporary folder",
					7=>"Failed to write file to disk"
				);

		return $errors[$code];
	}

?>
