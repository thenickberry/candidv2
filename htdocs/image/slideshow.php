<?php
  include("../../config.inc");
  $query = $userinfo['last_query'];
  #if (!isset($_GET['offset'])) { $offset = 1; } else { $offset = $_GET['offset']; }
  #$maxoffset = $offset + 15;
  $offset = 0; $maxoffset=100;
  $query .= " LIMIT $offset,$maxoffset";
  $results = mysql_query($query) or db_error($query);
  $x = 0;

  error_reporting(0);

  // set the loading slide first
  $slides = "s = new slide();\n".
	    "s.src=\"${config['base_url']}/images/loading.gif\";\n".
	    "s.text = \"\";\n".
	    "SLIDES.add_slide(s);\n\n";

  while ($image = mysql_fetch_array($results)) {
	$img_disp_url = "${config['base_url']}/main.php?displayImage&image_id=${image['id']}";
	$img_descr = addslashes($image['descr']);
	if ($x == 0) {
	    $first_img = $img_disp_url;
	}
	$image_info = get_image_info($image['id']);
	$show_info = show_image_info($image_info);
	$slides .= "s = new slide();\n".
		   "s.src = \"$img_disp_url\";\n".
		   "s.text = \"$show_info\";\n".
		   "SLIDES.add_slide(s);\n\n";
	$x++;
  }
  #$html_title = "Slideshow for ${cat_info['name']}";
  $html_title = '';
  $bare = 1;
  css_top($html_title);
?>

	    <div id=body>

		<!-- <A HREF=<?= $_SERVER['PHP_SELF'] ?>?offset=<?= $offset + 15 ?>> Next 15 </A> -->
		<br>

		    <TABLE BORDER=0>
		      <TR>
			<TD ALIGN=middle>
			<FORM>
			<INPUT TYPE="button" VALUE="start" onclick="SLIDES.next();SLIDES.play()">
			<INPUT TYPE="button" VALUE="stop" onclick="SLIDES.pause()">
			</FORM>
			</TD><TD>&nbsp;</TD>
		      </TR>
		      <TR>
			<TD ALIGN=middle>
			<FORM>
			Delay:

			<INPUT TYPE=radio NAME=speed CHECKED onclick="SLIDES.timeout=4000"> 4 sec
			<INPUT TYPE=radio NAME=speed onclick="SLIDES.timeout=10000"> 10 sec
			</FORM>

			</TD><TD>&nbsp;</TD>
		      </TR>
		      <TR VALIGN=top>
			<TD ALIGN=middle>

			<img src='<?= $first_img ?>' name='SLIDESIMG'>
			</TD><TD>
			<DIV ID="SLIDESTEXT" STYLE="position: relative;">
			    If you can see this, then your browser cannot display the slideshow text.
			</DIV>

			<BR>

			<SCRIPT type="text/javascript">
			<!--
			if (document.images) {
			  SLIDES.image = document.images.SLIDESIMG;
			  SLIDES.textid = "SLIDESTEXT";
			  SLIDES.update();
			  SLIDES.play();
			}
			//-->
			</SCRIPT>
			</TD>
		      </TR>
		    </TABLE>


	    </div>

<?= css_end() ?>

<?php

    function show_image_info($image) {

	$info .= "<div id=info> <div class='title'>information</div> <div class='details'>";
        if (!empty($image['date_taken'])) {
	    $info .= "<div class='descr'>Date taken:</div>";
	    $info .= "<div class='value'>${image['date_taken_nice']}</div>";
        }
        if (!empty($image['photographer_name'])) {
	    $info .= "<div class='descr'>Photographer:</div>";
	    $info .= "<div class='value'>${image['photographer_name']}</div>";
        }

	$info .= "<div class='descr'>Resolution:</div>";
	$info .= "<div class='value'>${image['resolution']}</div>";

	$info .= "<div class='descr'>Camera used:</div>";
	$info .= "<div class='value'>${image['camera']}</div>";

        if (!empty($image['people'])) {
	    $info .= "<div class='descr'>People in image:</div>";
	    $info .= $image['people'];
        }

        if ($image['views'] == 1) {
            $image['viewed'] = "once";
        } else {
            $image['viewed'] = $image['views']." times";
        }

	$info .= "<div class='descr'>Viewed ${image['viewed']}</div>";
	$info .= "<div style='padding:3'></div>";

	$info .= "</div>";

	return $info;
    }

?>
