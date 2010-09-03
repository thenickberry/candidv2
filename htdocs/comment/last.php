<?php
    include("../../config.inc");
    css_top("Last comments");
    if (!isset($_GET['user_id'])) {
		$user_id = $userinfo['id'];
?>
<a href=${config['base_url']}/comment_last.php?user_id=0>Click here</a> for last 5 images (regardless of the owner)<br>
<?php
    } else { $user_id = $_GET['user_id']; }
    get_last_comments($user_id,'5');
    css_end();
?>
