<?
  include("../../config.inc");
  if (!isset($refer_back)) { $refer_back = $referer; }
  if (empty($cat_id)) {
    css_top("Select Category");
    print "<table width=550><td><h1>Select category</h1></td></table><br>\n".
	  "<form action=${_SERVER['PHP_SELF']} method=post>\n".
	  "<select name='cat_id'>\n".
    	  getCategoryList("","","").
	  "</select>\n".
	  "<input type=hidden name=refer_back value=\"$refer_back\">\n".
	  "<input type=submit value=Submit class=button>\n";
    css_end();
    exit;
  } else {
    $category = getCategoryInfo($cat_id);
    $check = checkCatPic($cat_id);
  }

  if ($category['public'] == 'y') { 
    $publicParent = "<tr>\n".
		    " <td>\n".
		    "  &nbsp;Parent Category\n".
		    " </td><td>\n".
		    "  <select name=sql_category[parent]>".
		    "    <option value='0'>Main</option>".
		    getCategoryList("","",$category['parent']).
		    "  </select>\n".
		    " </td>\n".
		    "</tr>\n";
  }
  if (!empty($category['category_image_id'])) {
  $cat_thumb  = "${config['base_url']}/main.php?showImage&image_id=${category['category_image_id']}&thumb=yes";
  } else {
  	$cat_thumb = "${config['base_url']}/images/cat_thumb-none.gif";
  }
  css_top("Edit Category");
?>
<IMG STYLE='float:right;padding:10' SRC=<?= $cat_thumb ?> NAME="cat_thumbnail">
<h1>Edit Category</h1><br>
<form action='<?= $config['base_url'] ?>/main.php?updateCategory' method='post'>
<input type="hidden" name="update" value="y">
<input type="hidden" name="sql_category[id]" value="<? echo $cat_id; ?>">
<table class='form'>
<tr>
 <td>
  &nbsp;Category ID
 </td>
 <td>
  <? echo $cat_id; ?>
 </td>
</tr>
<!-- <tr>
 <td>
  &nbsp;Date Added
 </td>
 <td>
  <? echo $category['added']; ?>
 </td>
</tr> -->
<tr>
 <td>
  &nbsp;Last Modified
 </td>
 <td>
  <?= $category['modified']; ?>
 </td>
</tr>
<tr>
 <td>
  &nbsp;Name
 </td>
 <td>
  <input type="text" align="center" name="sql_category[name]" value="<? echo $category['name'] ?>">
 </td>
</tr>
<tr>
 <td>
  &nbsp;Location
 </td>
 <td>
  <input type="text" align="center" name="sql_category[loc]" value="<? echo $category['loc']; ?>">
 </td>
</tr>
<tr>
 <td>
  &nbsp;Description
 </td>
 <td>
  <textarea name='sql_category[descr]' cols=45 rows=5><?= $category['descr']; ?></textarea>
 </td>
</tr>
<tr>
 <td>
  &nbsp;Owner
 </td>
 <td>
  <select name="sql_category[owner]" >
    <? print getPersonList($category['owner'],'',''); ?>
  </select>
 </td>
</tr>
<? if (isset($publicParent)) { echo $publicParent; } ?>
<tr>
 <td>
  &nbsp;Sub-categories?
 </td>
 <td>
  <? if ($category['haskids'] == "1") { $checked = "checked"; } else { $checked = ""; } ?>
  <input type="checkbox" name="sql_category[haskids]" <? echo $checked; ?>>
 </td>
</tr>
<tr>
  <td>
&nbsp;Sort by:
  </td>
  <td>
    <select name=sql_category[sort_by]>
	<option value=timestamp>Date Taken
	<option value=added>Date Added
	<option value=filename>Filename
    </select>
  </td>
</tr>
<tr>
  <td>Category thumbnail:</td>
  <td>
    <SCRIPT LANGUAGE="JavaScript"><!--
    function change(what) {
        value = what.options[what.selectedIndex].value;
        if (value != '')
            if (document.images)
                document.images['cat_thumbnail'].src = '<?= $config['base_url'] ?>/main.php?showImage&image_id=' + value + '&thumb=yes';
    }
    //--></SCRIPT>
   <SELECT NAME='sql_category[category_image_id]' onChange="change(this)">
   <OPTION VALUE=''>--</OPTION>
    <?php listCatThumbs($cat_id,$category['category_image_id']); ?>
   </SELECT>
 </td>
</tr>
<tr>
 <td colspan=2>
    <table width=100%>
      <tr valign="center">
	<td align="center">
	  <input type="hidden" name="refer_back" value="<? echo $referer; ?>">
	  <input type="submit" value="Update" class=button>
	</td><td align="center">
	  <input type="reset" value="Reset" class=button></form>
	</td><td align="center">
	  <form action='<?= $config['base_url'] ?>/main.php?updateCategory' method='post'>
	    <input type='hidden' name='sql_category[id]' value='<? echo $cat_id; ?>'>
	    <input type='hidden' name='refer_back' value="<? echo $referer; ?>">
	    <input type='hidden' name='delete' value='y'>
	    <input type='submit' value='Delete' class='button'> </form>
	</td>
     </tr>
   </table>
 </td>
</tr>
<BR>
</center>

<?php css_end(); ?>

