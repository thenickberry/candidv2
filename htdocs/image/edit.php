<?php
  include("../../config.inc");
  if (isset($_GET['image_id'])) {
  	$image_id = $_GET['image_id'];
  } elseif (isset($_POST['image_id'])) {
  	$image_id = $_POST['image_id'];
  } else {
    echo "No image_id specified!";
    exit;
  }
  $count = count($image_id);
  $imageId = ""; $delete_id = "";
  if ($count > 1) {
    $massThumbs = "";
    for ($i=0;$i<$count;$i++) {
      if ($i == 0) { $referer .= "#".$image_id[0]; }
      $imageId .= "<input type='hidden' name='image_id[]' value='$image_id[$i]'>";
      $delete_id .= "<input type='hidden' name='image_id[]' value='$image_id[$i]'>";
      $massThumbs .= "<img src='${config['base_url']}/main.php?showImage&image_id=$image_id[$i]&thumb=yes'><br><br>";
    }
    $massMsg = "<b><u>check the boxes where you want to make changes</u></b><br><br>";
  } else {
    $massMsg = '';
    $thumbnail = "<img src='${config['base_url']}/main.php?showImage&image_id=$image_id[0]&thumb=yes'>";
    $imageId = "<input type='hidden' name='image_id[]' value='$image_id[0]'>";
    $delete_id.= "<input type='hidden' name='image_id[]' value='$image_id[0]'>";
    $singleEdit= $thumbnail; $massThumbs = '';
  }
  $image = get_image_info($image_id);
  if ($image['private'] == 1) { $private = " checked"; } else { $private = ""; }
  $image['descr'] = str_replace("\"","''",$image['descr']);
  $action = "updateImage";
  if (isset($_GET['bare'])) { $bare = 1; }
  $title = 'Edit Image ~ ';
  if (count($image_id) > 1) {
	$title .= 'mass edit';
  } else {
	$title .= 'id: '.$image_id[0];
  }
  css_top($title);
?>
<h1>Edit Image</h1>
<form action="<?= $config['base_url'] ?>/main.php?<?= $action ?>" method="post">
<?= $imageId ?>
<br>
<?= $massMsg ?>
<table><td valign=top>
<table border=0 cellspacing=5 class='form'>
  <tr>
    <td>&nbsp;Description</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type=checkbox name=sql_image[descr_do] style='margin:0px' onclick=\"Toggle(parentNode.this);\">";
         } ?>
    </td>
    <td name=descr>
      <input type="text" name="sql_image[descr]" value="<?= $image['descr'] ?>" size="40">
    </td>
  </tr>
  <tr>
    <td>&nbsp;Date taken</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type=checkbox name=sql_image[date_taken_do] style='margin:0px' onclick='Toggle(this);'>";
         } ?>
    </td>
    <td name=descr>
      <input type="text" name="sql_image[date_taken]" value="<?= $image['date_taken'] ?>" size="10">
    </td>
  </tr>
  <tr>
    <td>&nbsp;Photographer</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type='checkbox' name='sql_image[photographer_do]' style='margin:0px' onclick='Toggle(this);'>";
         } ?>
    </td>
    <td>
      <select name="sql_image[photographer]">
	<option value=''>unassigned</option>
	<? print getPersonList($image['photographer'],"clean","0") ?>
    </td>
  </tr>
  <tr>
    <td>&nbsp;Category</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type=checkbox name=sql_image[category_id_do] style='margin:0px' onclick=\"Toggle(this);\">";
         } ?>
    </td>
    <td>
      <select name="sql_image[category_id]">
	<? print getCategoryList("","",$image['cat_id']) ?>
    </td>
  </tr>
  <tr>
    <td colspan=2>&nbsp;People</td>
    <td>
      <table width=100% cellpadding=0 cellspacing=0><tr valign="top">
        <td align="left">Assign people<br>
	  <select name="addPeople[]" multiple size="8">
	    <?= getPersonList("","clean","0") ?>
	  </select>
	</td><td align="left">&nbsp;<?php if (!empty($image['people_list'])) { ?>Remove people<br>
	  <select name="removePeople[]" multiple size="8">
	    <?= $image['people_list'] ?>
	  </select>
	<?php } ?>
	</td>
      </tr><tr>
        <td colspan=2 align=center><a href='<?= $config['base_url'] ?>/profile/add.php' target=myPopUp onclick="javascript:window.open('', this.target, 'width=350,height=200,scrollbars=yes');return true;">add person</a></td>
      </tr></table>
    </td>
  </tr>
  <tr>
    <td>&nbsp;Access</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type=checkbox name=sql_image[access_do] style='margin:0px' onclick=\"Toggle(this);\">";
         } ?>
    </td>
    <td>
      <?= "<select name=sql_image[access]>" .
	      listAccess($image['access']) .
	      "</select>";
      ?>
    </td>
  </tr>
  <tr>
    <td>&nbsp;Private</td>
    <td align=right>
      <? if ($count > 1) {
           echo "<input type=checkbox name=sql_image[private_do] style='margin:0px' onclick=\"Toggle(this);\">";
         } ?>
    </td>
    <td>
      <input type="checkbox" name="sql_image[private]" <?= $private ?>>
    </td>
  </tr>
  <tr>
    <td colspan=2>&nbsp;Rotate image</td>
    <td align=center><table width=80% border=0 height=75 cellspacing=0 cellpadding=0 style='color:#fff'>
      <td align=left valign=bottom>90 <input type=radio style='margin:0px;margin-bottom:-2px;' name=rotate value=90><br><br></td>
      <td align=center valign=top>180<br><input type=radio style='margin:0px' name=rotate value=180></td>
      <td align=right valign=bottom><input type=radio style='margin:0px;margin-bottom:-2px' name=rotate value=270> 270<br><br></td>
    </table></td>
  </tr>
  <? if ($count == 1) { ?>
  <tr>
    <td>&nbsp;Assign category pic?</td>
    <td><input type=checkbox name=assignCatImage value=y></td>
  </tr>
  <? } ?>
  <tr>
    <td colspan=3>
      <table width=100% cellspacing=0 cellpadding=0>
       <tr valign="center">
        <td align="center">
	  <table cellspacing=0 cellpadding=0><td>
	  <input type="hidden" name="refer_back" value="<?= $referer ?>">
          <input type="submit" value="Update" class=button>&nbsp;&nbsp;&nbsp;
          <input type="reset" value="Reset" class=button>&nbsp;&nbsp;&nbsp;</form></td><td>
	  <form action='<?= $config['base_url'] ?>/main.php?<?= $action ?>' method='post'>
	    <input type='hidden' name='refer_back' value='<?= $referer ?>'>
	    <input type='hidden' name='delete' value='y'>
            <?  echo $delete_id ?>
	    <input type='submit' value='Delete' class='button'></form>
	  </td></table>
	</td>
       </tr>
      </table>
    </td>
  </tr>
</table>
</td>
<td width=10>&nbsp;</td><td valign=top><? if ($count > 0) { echo $massThumbs; } ?></td></table>

<?php css_end() ?>
