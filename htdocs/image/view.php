<?php
  include("../../config.inc");
  if (!isset($_GET['image_id'])) {
	echo "No image_id defined.";
	exit;
  } else {
	$image_id = $_GET['image_id'];
  }
  $image = get_image_info($image_id);
  $img_disp_url = "${config['base_url']}/main.php?displayImage&image_id=$image_id";
  $img_main_url = "${config['base_url']}/main.php?showImage&image_id=$image_id";
  $nav_links = getNextLastLinks($image_id);
  if (!empty($userinfo['access'])) {
    $add_comment = "<a href='${config['base_url']}/comment/add.php?image_id=$image_id' target=myPopUp2 onclick=\"javascript:window.open('', this.target, 'width=320,height=250');return true;\">add comment</a> |";
  }
  if (empty($image['descr'])) {
    $html_title = "Image ID: $image_id";
  } else {
    $html_title = "\"${image['descr']}\"";
  }
  if ($image['owner'] == $userinfo['id'] || $userinfo['access'] == '5') {
	$edit = "&nbsp;&nbsp;&nbsp;&nbsp;<a href=edit.php?image_id[]=$image_id&bare=1>edit</a>&nbsp; &nbsp; ";
  } else { $edit = ''; }
  $bare = 1;
  css_top($html_title);
  if (!empty($nav_links['prev']) || !empty($nav_links['next'])) {
	$nav =	"\t\t\t<div class=link>\n".
		"\t\t\t\t".$nav_links['prev'].
		"&nbsp; &nbsp; &nbsp; &nbsp ".
		$nav_links['next']."\n".
		"\t\t\t</div>\n";
  } else { $nav = ''; }
?>
	    <div id=top style='text-align:left'>
		<div id=path><?= $parentPath ?></div>
	    </div>
	    <div id=body>

	        <div id=sidebar>
			<?= $nav ?>
			<br>

			<div class=link>
				<?= $edit ?>
				<a href=slideshow.php>slideshow</a>
			</div>
			<br>

			<div id=info>

				<div class='title'>information</div>
				<div class='details'>
				    <? if (!empty($image['date_taken'])) { ?>
					<div class='descr'>Date taken:</div>
					<div class='value'><?= $image['date_taken_nice'] ?></div>
				    <? } ?>

				    <? if (!empty($image['photographer_name'])) { ?>
					<div class='descr'>Photographer:</div>
					<div class='value'><?= $image['photographer_name'] ?></div>
				    <? } ?>

					<div class='descr'>Resolution:</div>
					<div class='value'><?= $image['resolution'] ?></div>

					<div class='descr'>Camera used:</div>
					<div class='value'><?= $image['camera'] ?></div>

				    <? if (!empty($image['people'])) { ?>
					<div class='descr'>People in image:</div>
					<?= $image['people'] ?>
				    <? } ?>

					<div class='descr'>Viewed <?= $image['viewed'] ?></div>

					<div style='padding:3'></div>

				</div>
				<div class='end'> [ <a href='<?= $config['base_url'] ?>/image/view-detail.php?image_id=<?= $image['id']; ?>' target=myPopUp2 onclick="javascript:window.open('', this.target, 'width=350,height=350,scrollbars=yes');return true;">more details</a> ] </div>

			</div>
			<br>
			<div id=info>

				<div class='title'>comments</div>
				<div class='details'>
					<?= getComments($image_id,"scroll"); ?>
				</div>
				<div class='end'> [ <?= $add_comment ?> <a href='<?= $config['base_url'] ?>/comment/view.php?image_id=<?= $image_id; ?>' target=myPopUp2 onclick="javascript:window.open('', this.target, 'width=225,height=350,scrollbars=yes');return true;"'>view more</a> ] </div>
			</div>

			
			<br>
		</div>

		<div id=main>
			<div style='padding:10;text-align:center'>
				<?= $image['descr'] ?>
				[ <a href='<?= $img_main_url ?>'>view full size</a> ]
				<br>
				<br>
				<a href='<?= $img_main_url ?>'><img src='<?= $img_disp_url ?>'></a>
			</div>
		</div>
		<br><br>

	    </div>

<?= css_end() ?>
