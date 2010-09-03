<?php
    include("../config.inc");
    if (!isset($_GET['image_id'])) {
	exit;
    }
    $image_id = $_GET['image_id'];

    $code = "<link rel='stylesheet' href='http://candid.scurvy.net/themes/default/format.css' type='text/css' title='default'>";

    print $code;

    $code .= "<center><table>";

    print "<center><table>\n";
    foreach ($image_id as $id) {
	$info = get_image_info($id);
	if ($info['access'] <= $userinfo['access']) {
	    $disp = "<tr><td align=middle><a href=${config['base_url']}/image/view.php?image_id=${id} target=_new>${info['descr']}</a><div class=imgholder><a href=${config['base_url']}/image/view.php?image_id=${id} target=_new><img src=${config['base_url']}/main.php?showImage&image_id=${id}&thumb=yes></a></div><br></td>";
	    $vars = split('&', str_replace('/blog_it.php?','', str_replace("image_id[]=${id}",'',$_SERVER['REQUEST_URI']) ) );
	    
	    print $disp;
	    print "<td><a href=${_SERVER['PHP_SELF']}?image_id[]=${id}&".implode('&',$vars).">top</a> | <a href=${_SERVER['PHP_SELF']}?".implode('&',$vars)."&image_id[]=${id}>bottom</a></td></tr>";
	    $code .= $disp . "</tr>";;
	} else {
	    print "skipped ${id} because of access level";
	}
    }
    $code .= "</table>";
    print "</table><br><br>";
    print "<textarea cols=75 rows=10>\n";
    print htmlentities($code);
    print "\n\n</textarea>\n";

?>
