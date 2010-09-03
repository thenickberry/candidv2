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

	$smarty->display('header.tpl');


	if (count($_POST) == 0) {
		$photographer_q = "SELECT u.id,concat(u.fname,' ',u.lname) full_name,count(i.id) FROM user u,image_info i WHERE i.photographer=u.id GROUP BY u.id ORDER BY u.fname";
		$photographer_r = mysql_query($photographer_q) or db_error($photographer_q);
		while ($photographer = mysql_fetch_row($photographer_r)) {
			$photographers[] = array('id'=>$photographer[0],'full_name'=>$photographer[1]);
		}
		$smarty->assign('photographers',$photographers);

		$people_q = "SELECT u.id,concat(u.fname,' ',u.lname) full_name,count(p.id) FROM user u,image_people p WHERE p.user_id=u.id GROUP BY u.id ORDER BY u.fname";
		$people_r = mysql_query($people_q) or db_error($people_q);
		while ($people = mysql_fetch_row($people_r)) {
			$peoples[] = array('id'=>$people[0],'full_name'=>$people[1]);
		}
		$smarty->assign('peoples',$peoples);

		$category_q = "SELECT id,name,parent FROM category";
		$category_r = mysql_query($category_q) or db_error($category_q);
		while ($category = mysql_fetch_row($category_r)) {
			list($id,$name,$parent) = $category;
			if (empty($parent)) { $parent = 0; }
			$categories[] = array('id'=>$id,'name'=>$name,'parent'=>$parent,'children'=>array());
			if (!isset($parents[$parent])) { $parents[$parent] = array(); }
			$parents[$parent][] = $id;
		}

		#foreach ($parents as $parent => $kids) {
			#foreach ($kids as $kid) {
				#unset($categories[$kid]);
			#}
			#$categories[$parent]['children'][] = $kids;
		#}

		$smarty->assign('categories',$categories);
		$smarty->display('search.tpl');
		exit;
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
