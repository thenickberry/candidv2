<?php
   if (!file_exists("../config.inc")) { header("Location: install.php"); }

   include("../config.inc");
   if (isset($_SERVER['QUERY_STRING'])) {
   	if (strstr($_SERVER['QUERY_STRING'],'showImage&image_id=2564')) {
		$forbob = '/main.php?' . $_SERVER['QUERY_STRING'];
		header("Location: $forbob");
   	}
   }
   css_top("Main page");
   $check = mysql_fetch_row(mysql_query("SELECT count(id) FROM image_info"));

   if (!empty($check[0])) {
	$lastAddedInfo  = lastAddedImage();
	$lastViewedInfo = lastViewedImage();
	$randomInfo 	= randomImage();
   }

   $popup_w = $userinfo['default_w'] + 240;
   $popup_h = $userinfo['default_h'] + 120;

?>
<table cellspacing=10 cellpadding=10>
  <tr valign=top>
    <td align=center>
	<table>
	  <tr><td align=middle>
	<a style='font-weight: bold; line-height: 25px;' href=<?= $config['base_url'] ?>/main.php?browse&search=yes&sort=info.id>Last added</a>
	  </td></tr>
	  <tr><td>
	<div class=imgholder>
	    <a href='<?= $config['base_url'] ?>/image/view.php?image_id=<?= $lastAddedInfo['id']; ?>' alt="<?= $lastAddedInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;">
	    <img src='<?= $config['base_url'] ?>/main.php?showImage&image_id=<?= $lastAddedInfo['id']; ?>&thumb=yes' alt="<?= $lastAddedInfo['descr']; ?>">
	    </a>
	</div>
	  </td></tr>
	  <tr><td align=middle style='font-weight:bold'>
	<a href=<?= $config['base_url'] ?>/image/view.php?image_id=<?= $lastAddedInfo['id']; ?>' alt="<?= $lastAddedInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;"><?= $lastAddedInfo['descr']; ?></a>
	  </td></tr>
	</table>
    </td>
    <td align=center>
	<table>
	  <tr><td align=middle>
	<a style='font-weight: bold; line-height: 25px;' href='<?= $config['base_url']?>/main.php?browse&search=yes&sort=last_view'>Last viewed</a><br>
	  </td></tr>
	  <tr><td>
	<div class=imgholder>
	    <a href='<?= $config['base_url'] ?>/image/view.php?image_id=<?= $lastViewedInfo['id']; ?>' alt="<?= $lastViewedInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;"><img src='<?= $config['base_url'] ?>/main.php?showImage&thumb=yes&image_id=<?= $lastViewedInfo['id']; ?>' alt="<?= $lastViewedInfo['descr']; ?>"></a>
	</div>
	  </td></tr>
	  <tr><td align=middle style='font-weight:bold'>
	<a href='<? $config['base_url'] ?>/image/view.php?image_id=<?= $lastViewedInfo['id']; ?>' alt="<?= $lastViewedInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;"><?= $lastViewedInfo['descr']; ?></a>
	  </td></tr>
	</table>
    </td>
    <td align=center>
	<table>
	  <tr><td align=middle>
	<a style='font-weight: bold; line-height: 25px;' href='<?= $config['base_url'] ?>/main.php?browse&search=yes&sort=RAND()'>Random</a><br>
	  </td></tr>
	  <tr><td>
	<div class=imgholder>
	    <a href='<?= $config['base_url'] ?>/image/view.php?image_id=<?= $randomInfo['id']; ?>' alt="<?= $randomInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;">
	    <img src='<?= $config['base_url'] ?>/main.php?showImage&thumb=yes&image_id=<?= $randomInfo['id']; ?>' title="<?= $randomInfo['descr']; ?>" alt="<?= $randomInfo['descr']; ?>">
	    </a>
	</div>
	  </td></tr>
	  <tr><td align=middle style='font-weight:bold'>
	<a href='<?= $config['base_url'] ?>/image/view.php?image_id=<?= $randomInfo['id']; ?>' alt="<?= $randomInfo['descr']; ?>" target=myPopUp onclick="javascript:window.open('', this.target, 'width=<?= $popup_w ?>,height=<?= $popup_h ?>,scrollbars=yes');return true;"><?= $randomInfo['descr']; ?></a>
	  </td></tr>
	</table>
    </td>
  </tr>
</table><br><br>
<center><font style='font-weight:bold;font-size:12px'> version <?= $version ?></font></center>
<div style='position:absolute;bottom:5px;left:5px;font-size:12px'>
    <A HREF="http://slideshow.barelyfitz.com/">JavaScript Slideshow</A> provided by
    <A HREF="http://www.barelyfitz.com/">BarelyFitz Designs</A>
<!--	||
    <A HREF="http://www.dynarch.com/projects/calendar/">JavaScript Calendar</A> provided by
    <A HREF="http://www.dynarch.com">Dynarch</A>
-->
</div>
<? css_end(); ?>
