<?php
  include("../../config.inc");
  restrict_access("3",$user_id);
  if (isset($sql_user_mms)) {
    $sql_user_mms['user_id'] = $user_id;
    $query = build_query("user_mms",$sql_user_mms);
    mysql_query($query) or db_error($query);
  }
  $user_mms = getUserMMS($user_id);
?>
<form action=<?= $_SERVER['PHP_SELF'] ?> method=post>
<input type=hidden name=sql_user_mms[id] value=<?= $user_mms['id'] ?>>
<input type=hidden name=user_id value=<?= $user_id ?>>
<table height=200 width=100% cellspacing=0 cellpadding=0 style='border:1px solid black'>
  <tr bgcolor=#015296 height=20>
    <td align=middle colspan=2 class=title1 style='border-bottom:1px solid black'><font style='color:white'>information</td>
  </tr>
  <tr bgcolor=white valign=top>
    <td align=left>
      &nbsp;Mobile email address:
    </td>
    <td>
      <input type=text name=sql_user_mms[mobile_addr] value='<?= $user_mms['mobile_addr'] ?>' size=35>
    </td>
  </tr>
  <tr bgcolor=white valign=top>
    <td align=left>
      &nbsp;Default Category:
    </td>
    <td>
      <select name=sql_user_mms[default_category_id]><?= getCategoryList("","",$user_mms['default_category_id']) ?>'></select>
    </td>
  </tr>
  <tr bgcolor=white valign=top>
    <td align=left>
      &nbsp;Default to Private?
    </td>
    <td>
      <input type=hidden name=sql_user_mms[private] value="">
      <input type=checkbox name=sql_user_mms[private] value=checked <?= $user_mms['private'] ?>
    </td>
  </tr>
  <tr bgcolor=white valign=top>
    <td align=middle colspan=2>
      <input type=submit value=Update>
    </td>
  </tr>
  <tr bgcolor=#EFEFEF height=25>
    <td align=middle colspan=2 class=title1 style='border-top:1px solid black;'><a href=javascript:window.close();>close window</a></td>
  </tr>
</table>
</form>
