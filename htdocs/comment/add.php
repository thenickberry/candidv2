<?php
  include("../../config.inc");
  if (!empty($_POST['comment'])) {
    if (empty($userinfo['id'])) {
      $userinfo['id'] = 6;
    }
    addComment($_POST['image_id'],$_POST['comment']);
    echo "<div style='float:right'>".
	 "<a href='javascript:window.opener.location.reload();".
	 "javascript:window.close();' style='color:#313131'>Close window</a></div>".
	 "Comment added";
    exit;
  }
  $bare = 1;
  css_top("Add comment");
?>
	    <div id='info'>
		<div class='title'>Add comment</div>
		<div class='details'>
		    <form action=<?= $_SERVER['PHP_SELF'] ?> method=post>
		    <input type=hidden name=image_id value=<? echo $image_id; ?>>
		    <textarea style='width:100%;height:100;' name=comment></textarea><br>
		</div>
		<div align=right><input type=submit value=Add></form></div>
	    </div>
<? css_end() ?>
