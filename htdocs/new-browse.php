<?php
	include('../config.inc');
	css_top('test');
?>

<style type='text/css'>
	#category {
		text-align:left;
		border: 1px solid #ccc;
		background: #fff url(/images/table-shade.png) repeat-x bottom left; }
	#category td {
		padding: 10px;
		border: 1px solid #555; }
	#category .loc {
		float: right;
		font-size: 13px; }
	#category .name {
		font-weight: bold;
		font-size: 14px; }
	#category .descr {
		font-size: 12px; }
	#category .imgs {
		padding: 10px; }
</style>

<br>

<?php
	$category_q  = "SELECT c.id,c.name,c.descr,c.loc,max(i.added) ";
	$category_q .= "FROM category c, image_info i ";
	$category_q .= "WHERE c.id=i.category_id AND c.parent='15' ";
	$category_q .= "GROUP BY i.category_id ORDER BY c.name";
	$category_r  = mysql_query($category_q) or db_error($category_q);
	print "\t<table cellspacing=0>\n";
	$row = 0;
	while ($category = mysql_fetch_array($category_r)) {
		$category['descr'] = wordwrap($category['descr'], 150, "<br />");
		print "\t\t<tr id='category'><td>\n";
		print "\t\t\t\t<div class='loc'>${category['loc']}</div>\n";
		print "\t\t\t\t<div class='name'><a href=/main.php?browse&cat_id=${category['id']}>${category['name']}</a></div>";
		print "\t\t\t\t<div class='descr'>${category['descr']}</div>\n";
		print "\t\t\t\t<div class='imgs'>\n";
		$image_q  = "SELECT id,descr FROM image_info ";
		$image_q .= "WHERE category_id=${category['id']} ORDER BY RAND() ";
		$image_q .= "LIMIT 4";
		$image_r = mysql_query($image_q) or db_error($image_q);
		while ($image = mysql_fetch_array($image_r)) {
			print "\t\t\t\t\t\t<a href=/image/view.php?image_id=${image['id']}>";
			print "<img src=/main.php?showImage&image_id=${image['id']}&thumb=yes height=150 border=0></a>\n";
		}
		print "\t\t\t\t</div>\n";
		print "\t\t</td><td style='background: url(/images/shade-right.png) repeat-y top left;border:0px'>&nbsp;</td></tr>\n";
		print "\t\t<tr height=30 valign=top><td style='background: url(/images/shade-bottom.png) no-repeat top left'>&nbsp;</td><td style='background: url(/images/shade-corner.png) no-repeat top left'>&nbsp;</td></tr>\n";
	}
	print "\t</table>\n";

	css_end();
		
?>
