<?php 
	include('../config.inc');
	$cat_id = 578;
	$INFO = array();
	$q = "SELECT id,md5_sum FROM image_info WHERE category_id=${cat_id}";
	$r = mysql_query($q);
	while ($i = mysql_fetch_row($r)) {
		list($id,$md5) = $i;
		if (!isset($INFO[$md5])) { $INFO[$md5] = array(); }
		array_push($INFO[$md5],$id);
	}

	foreach ($INFO as $md5 => $array) {
		if (count($array) == 1) { next; }
		array_shift($array);
		$q = array();
		foreach ($array as $id) {
			print $id . "\n";
			$q[] = "DELETE FROM image_info WHERE id=$id";
			$q[] = "DELETE FROM image_thumb WHERE id=$id";
			$q[] = "DELETE FROM image_file WHERE id=$id";
		}
		for($i=0;$i<count($q);$i++) {
			mysql_query($q[$i]);
		}
	}
?>
