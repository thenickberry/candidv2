<?php

    require('Smarty/Smarty.class.php');
    $smarty = new Smarty();
    define('BASE','/usr/local/candidv2/smarty');
    $smarty->template_dir = BASE . '/templates';
    $smarty->compile_dir = BASE . '/templates_c';
    $smarty->config_dir = BASE . '/configs';
    $smarty->cache_dir = BASE . '/cache';
    $smarty->caching = false;

	$getPP = 1;
	require('../config.inc');

	$smarty->assign('http_base',$config['base_url']);

	$URI = str_replace('/browse/','',$_SERVER['REQUEST_URI']);
	$URI = str_replace('/browse','',$URI);
	$paths = explode('/',$URI);
	if (count($paths) > 1) {
		$reverse_paths = array_reverse($paths);
		$x = count($paths);
		$parentPath = array();
		foreach ($reverse_paths as $path) {
			$href = '/browse';
			for ($i=0;$i<$x;$i++) {
				$href .= '/' . $paths[$i];
			}
			$name = str_replace('+',' ',$path);
			array_unshift($parentPath, array('url'=>$href,'name'=>$name) );
			$x--;
		}
		$smarty->assign('parentPath',$parentPath);
	}

	if (isset($_GET['type']) && isset($_GET['name'])) {
		if ($_GET['type'] == 'category') {
			$last = count($paths) - 1;
			$category_name = str_replace('+',' ',$paths[$last]);
			$cat_id = getCategoryId($category_name);
		}
	} elseif (!isset($_GET['cat_id'])) {
		$cat_id = 0;
	} else {
		$cat_id = $_GET['cat_id'];
	}

	$smarty->assign('title',$category_name);


	list($userinfo['x'],$userinfo['y']) = split('x',$userinfo['init_disp']);
	$userinfo['x'] += 250;
	$userinfo['y'] += 100;
	if ($userinfo['access'] != 0) { $smarty->assign('logged_in',1); }
	$smarty->assign('user',$userinfo);
	$smarty->display('header.tpl');


	// Check to see if there are "children" categories
	$child_q = "SELECT id,name,descr,loc,category_image_id FROM category WHERE parent=${cat_id} ORDER BY name";
	$child_r = mysql_query($child_q) or db_error($child_q);

	if (mysql_num_rows($child_r) > 0) {
		while ($c = mysql_fetch_array($child_r)) {
			$la_q = "SELECT max(date_format(added,'%b. %D, %Y')) FROM image_info WHERE category_id=${c['id']}";
			$la_r = mysql_query($la_q) or db_error($la_q);
			$la   = mysql_fetch_row($la_r);

			$url = $config['base_url'] . '/browse';
			$parents = array_reverse( get_parents($c['id']) );
			foreach ($parents as $category) {
				$url .= '/' . str_replace(' ', '+', getCategoryName($category) );
			}
			$url .= '/' . str_replace(' ','+', $c['name']);

			$c_data[] = array(
					'id'	=> $c['id'],
					'url'	=> $url,
					'name'	=> $c['name'],
					'descr'	=> $c['descr'],
					'loc'	=> $c['loc'],
					'update'=> $la[0],
					'image_count' => imageCount($c['id']),
					'category_image_id' => $c['category_image_id']
				);
		}
		$smarty->assign('category_data',$c_data);
		$smarty->display('browse-category.tpl');
	}

	// Check to see if there are any images assigned
	$image_c_q = "SELECT count(id) FROM image_info WHERE category_id=${cat_id}";
	$image_c_r = mysql_query($image_c_q) or db_error($image_c_q);
	$image_c = mysql_fetch_row($image_c_r);

	if ($image_c[0] != 0) { 
		$q  = "SELECT i.id,i.descr,i.category_id,c.name as category_name,i.filename,i.access,i.owner ";
		$q .= "FROM image_info i,category c ";
		$q .= "WHERE i.category_id=c.id AND i.category_id=${cat_id}";

		$r = mysql_query($q) or db_error($q);
		while ($i = mysql_fetch_array($r)) {
			$ajax_form  = "<img style='cursor:pointer' src='/images/rotate-90.png' ";
			$ajax_form .= "onClick='Javascript:xmlhttpPost(\"${i['id']}\",\"/main.php?updateImage\",\"90\");'>";
			$ajax_form .= "&nbsp;";
			$ajax_form .= "<img style='cursor:pointer' src='/images/rotate-270.png' ";
			$ajax_form .= "onClick='Javascript:xmlhttpPost(\"${i['id']}\",\"/main.php?updateImage\",\"270\");'>";
			$i_data[] = array(
					'id' => $i['id'],
					'descr' => $i['descr'],
					'owner' => $i['owner'],
					'access' => $i['access'],
					'filename' => $i['filename'],
					'category_id' => $i['category_id'],
					'category_name' => $i['category_name'],
					'ajax_form' => $ajax_form
				);
		}
		$smarty->assign('image_data',$i_data);
		$smarty->assign('sel_cat_id',$cat_id);
		$smarty->display('browse-image.tpl');
	}



	$smarty->display('footer.tpl');

//	function pP($category_id) {
//		global $parentPath;
//
//		$q = "SELECT id,name,parent FROM category WHERE id=${category_id}";
//		$r = mysql_query($q) or db_error($q);
//		$c = mysql_fetch_array($r);
//
//		array_unshift($parentPath,array('id' => $c['id'], 'name' => $c['name']));
//
//		if ($c['parent'] != 0) {
//			pP($c['parent']);
//		}
//	}

	function get_parents($child) {
        global $userinfo,$cookie;
        $list = array();
        $q = "SELECT parent FROM category WHERE id=${child}";
        $r = mysql_query($q) or db_error($q);
		while ($cat = mysql_fetch_row($r)) {
			if ($cat[0] == 0) { continue; }
			$list[] = $cat[0];
			$return = get_parents($cat[0]);
			for ($i=0;$i<count($return);$i++) {
				array_push($list,$return[$i]);
			}
		}
		return $list;
	}

	function get_children($parent,$check) {
        global $userinfo,$cookie;
        $list = array();
        $query = "SELECT id,haskids,name FROM category WHERE parent='$parent'";
        $result = mysql_query($query) or db_error($query);
        $numrows = mysql_num_rows($result);
        if (!empty($numrows)) {
            while ($cat = mysql_fetch_row($result)) {
                if ($cat[1] != "1") {
                    array_push($list,$cat[0]);
                } else { 
                    $return = get_children($cat[0],"");
                    for ($i=0;$i<count($return);$i++) {
                        array_push($list,$return[$i]);
                    }
                }
            }
        } else {
            array_push($list,$parent);
        }

		return $list;
	}

	function imageCount($category_id,$imagecount = 0) {
		$children = get_children($category_id,1);
		$q  = "SELECT count(id),max(unix_timestamp(added)) ";
		$q .= "FROM image_info WHERE (category_id='";
		$q .= implode($children,"' OR category_id='")."') AND ((owner='$userinfo[id]' ";
		$q .= "OR private!='1') AND access<='$userinfo[access]')";
		$r = mysql_query($q) or db_error($q);
		$d = mysql_fetch_row($r);
		return $d[0];
	}

//	function imageCount($category_id,$imagecount = 0) {
//		$c_child_q = "SELECT id FROM category WHERE parent=${category_id}";
//		$c_child_r = mysql_query($c_child_q) or db_error($c_child_q);
//
//		$ic_q = "SELECT count(id) FROM image_info WHERE category_id=${category_id}";
//		$ic_r = mysql_query($ic_q) or db_error($ic_q);
//		$ic = mysql_fetch_row($ic_r);
//		$imagecount += $ic[0];
//
//		if (mysql_num_rows($c_child_r) != 0) {
//			while ($c_child = mysql_fetch_array($c_child_r)) {
//				$imagecount = imageCount($c_child['id'],$imagecount);
//			}
//		}
//		return $imagecount;
//	}

	function getCategoryId($name) {
		$q = "SELECT id FROM category WHERE name=\"${name}\"";
		$r = mysql_query($q) or db_error($q);
		$d = mysql_fetch_row($r);
		return $d[0];
	}

?>
