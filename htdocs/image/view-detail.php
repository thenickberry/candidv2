<?php
  include("../../config.inc");
  $image = get_image_info($image_id);
  $img_disp_url = "http://candid.scurvy.net/main.php?displayImage&image_id=$image_id";
  $img_main_url = "http://candid.scurvy.net/main.php?showImage&image_id=$image_id";
  $bare = 1;
  css_top("Details for ${image_id}");
?>
	    <div id='info'> 
		<div class='title'>information</div>
		<div class='details'>
		    <? echo getImageDetails($image,"full"); ?>
		</div>
		<div class='end'><a href=javascript:window.close();>close</a></div>
	    </div>
