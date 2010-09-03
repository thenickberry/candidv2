<?php
    include("../../config.inc");

    $c_query = "SELECT ic.user_id FROM image_comment WHERE id=$id";
    $check = mysql_fetch_row(mysql_query($c_query));
    $image_owner = getImageOwner($image_id);

    if ($check[0] == $userinfo['id'] ||
	$image_owner == $userinfo['id'] ||
	$userinfo['access'] == 5) {

	$query = "DELETE FROM image_comment WHERE id=$id";
	mysql_query($query) or error($query);

    } else {
	echo "err, yeah.. sorry dude";
	exit;
    }

    header("Location: $HTTP_REFERER");
?>
