<?php
   include("../../config.inc");
   if ($userinfo['id'] == 0) {
    print "You do not have access";
    exit;
   }

   css_top("Add Category");
?>
<form method="post" action="<?= $config['base_url'] ?>/main.php?updateCategory">
<input type="hidden" name="add" value="y">
<table width=550><td><h1>Add Category</h1></td></table><br>
<table bgcolor="ffffff" cellpsacing=0 cellpadding=0 border=0>
<tr bgcolor="#ffffff">
 <td>&nbsp;Name</td>
 <td><input type="text" align="center" name="sql_category[name]"></td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;Location</td>
 <td><input type="text" align="center" name="sql_category[loc]"></td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;Description</td>
 <td><input type="text" align="center" name="sql_category[descr]"></td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;Category owner</td>
 <td>
  <select name="sql_category[owner]">
   <? print getPersonList($userinfo['id'],"",""); ?>
  </select>
 </td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;Parent Category</td>
 <td><select name="sql_category[parent]"><option value="0">Main
   <? echo getCategoryList("","",""); ?>
  </select></td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;Will have subcategories</td>
 <td><input type="checkbox" name="sql_category[haskids]" value="on"></td>
</tr>
<tr bgcolor="#ffffff">
 <td>&nbsp;</td>
 <td>
  <table width=100%>
    <td>
      <input type=hidden name=refer_back value="<? echo $referer; ?>">
      <input type="submit" value="Submit">
    </td>
    <td><input type="reset"></td>
  </table>
 </td>
</tr>
</table>
</form>

<?php css_end(); ?>
