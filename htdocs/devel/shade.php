<?php

	include('../../config.inc');

	$i_q = "SELECT data FROM image_file WHERE image_id=${_GET['id']}";
	$i_r = mysql_query($i_q) or db_error($i_q);
	$i = mysql_fetch_row($i_r);
	$im_main = imagecreatefromstring($i[0]);
	$im_main_w = imagesx($im_main);
	$im_main_h = imagesx($im_main);


	$x_inst = 30;
	$small_h = 30;
	$im_big_w = floor($im_main_w / 2).
	$im_big_h = floor($im_main_h / 2);

	$q = "SELECT i.id FROM image_info i WHERE i.width > i.height AND i.height > ${small_h} ORDER BY RAND() LIMIT 1000";
	$r = mysql_query($q) or db_error($q);

	$layout = array();

	$x = 0;
	$y = 0;

	$max_im_y = floor($im_big_h / $small_h);

	while ($d = mysql_fetch_row($r)) {
		if ($y > $max_im_y) { next; }
		$layout[$y][$x] = $d[0];
		if ($x < $x_inst) {
			$x++;
		} else {
			$y++; $x = 0;
		}
	}


	// Trim off stragglers
	foreach ($layout as $row => $row_array) {
		#print "$row -> $x_inst = " . count($row_array) . "<br />";
		if (count($row_array) != ($x_inst + 1)) {
			unset($layout[$row]);
		}
	}
	#print"<pre>";print_r($layout);print"</pre>";

	$im_big = imagecreatetruecolor($im_big_w, $im_big_h);


	$x_mark = 0; $y_mark = 0;
	foreach ($layout as $row => $row_array) {
		foreach ($row_array as $row_pos => $image_id) {
			$q = "SELECT data FROM image_thumb WHERE image_id=${image_id}";
			$r = mysql_query($q) or db_error($q);
			$i = mysql_fetch_row($r);
			$im = @imagecreatefromstring($i[0]) or die("Error with ${image_id}");
			if (!$im) {
				next;
			}
			$im_w = imagesx($im);
			$im_h = imagesy($im);

			$ratio = floor($im_h / $small_h);
			$im_small_h = $small_h;
			$im_small_w = floor($im_w / $ratio);

			#print "$im_small_w x $im_small_h ~ $x_mark/$y_mark ($small_h)<br />";

			$im_small = imagecreatetruecolor($im_small_w,$im_small_h);
			imagecopyresampled($im_small, $im, 0, 0, 0, 0, $im_small_w, $im_small_h, $im_w, $im_h);
			imagedestroy($im);
			imagecopymerge($im_big, $im_small, $x_mark, $y_mark, 0, 0, $im_small_w, $im_small_h, 100);
			imagedestroy($im_small);
			$x_mark += $im_small_w;
		}
		$y_mark += $small_h;
		$x_mark = 0;
	}
	#print $y_mark;

	$im_main_s = imagecreatetruecolor($im_big_w,$im_big_h);

	$im_main_s_w = $im_big_w;
	$im_main_s_h = $im_big_h;

	imagecopyresampled($im_main_s, $im_main, 0, 0, 0, 0, $im_main_s_w, $im_main_s_h, $im_main_w, $im_main_h);
	imagedestroy($im_main);
	imagecopymerge($im_big, $im_main_s, 0, 0, 0, 0, $im_big_w, $im_big_h, 80);
	imagedestroy($im_main_s);

	header('Content-type: image/jpeg');
	imagejpeg($im_big);
	imagedestroy($im_big);


?>
