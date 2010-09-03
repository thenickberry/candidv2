<?php
  include("../../config.inc");
  $bare = 1;
  css_top("Comments for $image_id");
?>
	    <div id='info'>
		<div class='title'>comments</div>
		<div class='details'>
<?= getComments($image_id,"open"); ?>
		</div>
	    </div>
<? css_end() ?>
