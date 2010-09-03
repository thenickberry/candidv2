#!/usr/local/bin/php
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

include("/usr/local/candidv2/config.inc");

$error = 0;

include('Mail/mimeDecode.php');
$fd = fopen("php://stdin", "r");
$input = "";
while (!feof($fd)) {
    $input .= fread($fd, 1024);
}
fclose($fd);
$params['include_bodies'] = true;
$params['decode_bodies'] = true;
$params['decode_headers'] = true;
$params['input'] = $input;

$structure = Mail_mimeDecode::decode($params);

$from = $structure->headers['from'];
if (strstr($from,"<") && strstr($from,">")) {
    $frags = split(" ",$from);
    foreach ($frags as $frag) {
        if (strstr($frag,"@")) {
	    $frag = str_replace("<","",$frag);
	    $frag = str_replace(">","",$frag);
	    $from = $frag;
	}
    }
}
$date = $structure->headers['date'];
$unixtime = strtotime($date);
$date = date("Y-m-d",$unixtime);
$files = array();

foreach ($structure->parts as $part) {
    // only save if an attachment
    if (isset($part->disposition) and
        ($part->disposition=='attachment')) {
        // open file
	$file = $part->ctype_parameters['name'];
        $fp = fopen("/tmp/$file", 'w');
        #foreach($part->ctype_parameters as $key => $value) {
        #        echo "$key => $value\n";
        #}
        // write body
        fwrite($fp, $part->body);
        // close file
        fclose($fp);
	chmod("/tmp/$file",0755);
        array_push($files,$file);
    }
}

$mms_query = "select * from user_mms where mobile_addr='$from'";
$mms_results = mysql_query($mms_query) or die($mms_query);

if (mysql_num_rows($mms_results) == 0) {
	$error = 1;
}

while ($user_mms = mysql_fetch_array($mms_results)) {
    if ($user_mms['private'] == "checked") {
	$private = 1;
    } else {
	$private = 0;
    }
    foreach ($files as $file) {
        $filename = "/tmp/$file";
	$thumbname = "/tmp/thumb-$file";

        list($srcX,$srcY,$srcType,$srcAttr) = GetImageSize($filename);
	$dX = "200";
	$dY = "250";
	$types = array("","","Jpeg","Png");
	$type = $types[$srcType];
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
	$src_img = ImageCreateFromJpeg($filename);
	$dst_img = ImageCreateTrueColor($dstX,$dstY);
	ImageCopyResampled($dst_img,$src_img,0,0,0,0,$dstX,$dstY,$srcX,$srcY);
	ImageJpeg($dst_img,$thumbname) or $error = 1;

        $fp = fopen($filename,'r');
        $image = addslashes( fread( $fp, filesize($filename) ) );
        fclose($fp);
        $fp = fopen($thumbname,'r');
        $thumb = addslashes( fread( $fp, filesize($thumbname) ) );
        fclose($fp);
        $info_query = "insert into image_info (id,filename,category_id,owner,photographer,added,date_taken,width,height,content_type,private) values (null,'$file','${user_mms['default_category_id']}','${user_mms['user_id']}','${user_mms['user_id']}',NOW(),'$date',$srcX,$srcY,'image/jpeg','$private');";
        mysql_query($info_query) or die("failed on query! $info_query");
        $image_id = mysql_insert_id();
        $file_query = "insert into image_file values ('$image_id',\"$image\")";
        mysql_query($file_query) or die("file_query failed");
        $thumb_query = "insert into image_thumb values ('$image_id',\"$thumb\")";
        mysql_query($thumb_query) or die("thumb_query failed");
	$userinfo['id'] = $user_mms['user_id'];
	make_history("add","via mms",$image_id,"image_info");
    }
}


if ($error == 1) {
	$reply_subject = 'upload error';
	$reply_mesg    = 'There was a problem uploading your photo to CANDIDv2.  ';
	$reply_mesg   .= 'Please check that your mobile address is correct in your MMS prefs';
	$reply_mesg   .= '(' . $from .')';
} else {
	foreach ($files as $file) {
    		#unlink("/tmp/$file");
    		#unlink("/tmp/thumb-$file");
	}

	$reply_subject = 'upload success!';
	$reply_mesg    = 'Your MMS photo has been uploaded to CANDIDv2!';
}

mail($from,$reply_subject,$reply_mesg);

return 0;

?>
