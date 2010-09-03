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

    function getComments($image_id,$style) {
	global $config,$userinfo;
	$image_owner = getImageOwner($image_id);
	if ($style == "scroll") {
	    $scroll = "height:125px;overflow:auto;";
	} else {
	    $scroll = "";
	}
	$data = "<div style='".$scroll."width:175;'>";
	$query = "SELECT u.username,ic.comment,".
		 "date_format(ic.stamp,'%b. %d, %Y'),ic.user_id,ic.id ".
		 "FROM image_comment ic,user u ".
		 "WHERE image_id=$image_id AND ic.user_id=u.id ORDER BY ic.stamp";
	$results = mysql_query($query) or db_error($query);
	if (mysql_num_rows($results) == 0) {
	    #return "<i>none posted</i>";
	}
	while ($comment = mysql_fetch_row($results)) {
	    if ($comment[3] == $userinfo['id'] || $image_owner == $userinfo['id']) {
		$delete = "\t\t\t\t<div id=delete><a href='${config['base_url']}/comment/delete.php?image_id=$image_id".
			  "&id=$comment[4]'>x</a></div>\n";
	    } else { $delete = ""; }
	    $data .= "\t\t\t<div id='comment'>\n".
		     $delete.
		     "\t\t\t\t<b>$comment[0]</b></u> on $comment[2]\n".
		     "\t\t\t\t<div id=message>$comment[1]</div>\n".
		     "\t\t\t</div>\n";
	}
	$data .= "</div>";
	return $data;
    }

    function addComment($image_id,$comment) {
	global $config,$userinfo;
	$comment = addslashes($comment);
	$query = "INSERT INTO image_comment (id,image_id,user_id,comment,stamp) ".
		 "VALUES (null,'$image_id','$userinfo[id]','$comment',now())";
	mysql_query($query) or db_error($query);
	$commentor = getUsername($userinfo['id']);
	$image_owner = getImageOwner($image_id);
	$owner_email = getUserEmail($image_owner);
	$owner_name = getUsername($image_owner);
	$img = "<img src='${config['base_url']}/main.php?showImage&image_id=$image_id&thumb=yes'>";
	$mesg = "A comment has been added to an image (#$image_id)\n".
		"<br><br><a href='${config['base_url']}/profile/view.php?".
		"user_id=${userinfo['id']}'>$commentor</a> posted:\n<br>".
		"<table><td>$img</td><td valign=top>\"$comment\"</td></table>\n\n".
		"<a href='${config['base_url']}/image/view.php?image_id=".
		"$image_id'>Click here</a> to view\n";
	$headers = "MIME-Version: 1.0\n".
		   "Content-Type: text/html; charset=\"iso-8859-1\"\n".
		   "Content-Transfer-Encoding: 8bit\n".
		   "From: $commentor - CANDIDv2 comment <nberry@scurvy.net>\n";

	mail("$owner_name <$owner_email>","Comment added to your image",$mesg,$headers);
    }

    function countComments($image_id) {
	$query = "SELECT count(id) FROM image_comment WHERE image_id=$image_id";
	$result = mysql_query($query) or db_error($query);
	$data = mysql_fetch_row($result);
	return $data[0];
    }

    function get_last_comments($user_id,$count) {
	global $config;

	if ($user_id != 0) {
	    $main_query = "SELECT c.image_id FROM image_comment c,image_info i ".
			  "WHERE c.image_id=i.id AND i.owner=$user_id ".
			  "GROUP BY c.image_id ORDER BY stamp DESC LIMIT $count";
	} else {
	    $main_query = "SELECT c.image_id FROM image_comment c ".
			  "ORDER BY stamp DESC LIMIT $count";
	}
	$main_results = mysql_query($main_query) or db_error($main_query);
	print "<table>";
	while ($main_comment = mysql_fetch_row($main_results)) {
	    print "<tr><td><table><td>".
		  "<a href='${config['base_url']}/image/view.php?image_id=$main_comment[0]'>".
		  "<img src='${config['base_url']}/main.php?showImage&image_id=$main_comment[0]".
		  "&thumb=yes'></a></td><td>";
	    $sub_query = "SELECT c.user_id,u.username,c.comment,c.stamp ".
			 "FROM image_comment c, user u WHERE c.user_id=u.id ".
			 "AND c.image_id=$main_comment[0] ".
			 "ORDER BY stamp DESC LIMIT 5";
	    $sub_results = mysql_query($sub_query) or db_error($sub_query);
	    while ($comment = mysql_fetch_row($sub_results)) {
		print "<div style='padding:2px'><div>".
		      "<a href=/profile/view.php?user_id=$comment[0]>".
		      "$comment[1]</a> commented on $comment[3]</div>".
		      "<div style='padding-left:10px'>$comment[2]</div></div>";
	    }
	    print "</td></table></td></tr>";
	}
    }

?>
