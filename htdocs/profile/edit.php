<?
  include("../../config.inc");
  css_top("Edit Profile");
  if (isset($_GET['user_id'])) { $user_id = $_GET['user_id']; }
  if (!isset($refer_back)) { $refer_back = $referer; }
  if (empty($user_id)) {
    $personList = getPersonList("","","0");
    print "<h1>Select person</h1><br>\n".
	  "<form action='${_SERVER['PHP_SELF']}' method='post'>\n".
	  "<select name='user_id'>\n".
	  "$personList\n</select>\n".
	  "<input type='hidden' name='refer_back' value='${referer}'>".
	  "<input type='submit' value='Submit'>\n";
    exit;
  } else {
    $person = getPersonInfo($user_id);
    $lname_disp = '';
    $fname_disp = '';
    if ($person['name_disp'] == "fname") {
      $fname_disp = "selected";
    } else {
      $lname_disp = "selected";
    }
    $d = ""; $w = ""; $m = "";
    $$person['update_notice'] = "selected";
    $disp480 = ""; $disp640 = ""; $disp800 = "";
    list($user_w,$user_h) = split("x",$userinfo['init_disp']);
    $init_disp = "disp" . $user_w;
    $$init_disp = "selected";
    $expirename = "expire".$person['expire'];
    $expiresoon = ""; $expirenever = "";
    $$expirename = "selected";
  } ?>
<h1>Edit Profile</h1><br>
<form method='post' action='<?= $config['base_url'] ?>/main.php?updateProfile'>
<table cellspacing=0 cellpadding=5 class='form'>
<tr>
 <td>&nbsp;User ID</td>
 <td>&nbsp;<?= $user_id ?>
  <input type='hidden' name='sql_profile[id]' value='<?= $user_id ?>'>
  <input type='hidden' name='user_id' value='<?= $user_id ?>'>
 </td>
</tr>
<tr>
 <td>&nbsp;Member since:</td>
 <td>&nbsp;<?= $person['created'] ?></td>
</tr>
<tr>
 <td>&nbsp;Prefs updated:</td>
 <td>&nbsp;<?= $person['modified'] ?></td>
</tr>
<tr>
 <td>&nbsp;First Name</td>
 <td>
  <input type='text' name='sql_profile[fname]' value='<?= $person['fname'] ?>'>
 </td>
</tr>
<tr>
 <td>&nbsp;Last Name</td>
 <td>
  <input type='text' name='sql_profile[lname]' value='<?= $person['lname'] ?>'>
 </td>
</tr>
<tr>
 <td>&nbsp;Email</td>
 <td>
  <input type='text' name='sql_profile[email]' value='<?= $person['email'] ?>'>
 </td>
</tr>
<tr>
 <td>&nbsp;Username</td>
 <td>
  <input type='text' name='sql_profile[username]' value='<?= $person['username'] ?>'>
 </td>
</tr>
<tr>
 <td>&nbsp;Password (reset)</td>
 <td>
  <input type='text' name='sql_profile[pword]'>
 </td>
</tr>
<tr>
 <td>&nbsp;Access Level</td>
 <td>
  <? if ($userinfo['access'] == 5) { 
       print "<select name='sql_profile[access]'>" . listAccess($person['access']) . "</select>";
     } else {
       print $person['access']; } ?>
 </td>
</tr>
<tr>
 <td>&nbsp;Number of columns</td>
  <td><input type=text name='sql_profile[numcols]' value='<?= $person['numcols'] ?>' size=1 style='text-align:center'></td>
</tr>
<tr>
  <td>&nbsp;Number of rows</td>
  <td><input type='text' name='sql_profile[numrows]' value='<?= $person['numrows'] ?>' size=1 style='text-align:center'></td>
</tr>
<tr>
  <td>&nbsp;Sort person list by</td>
  <td>
    <select name='sql_profile[name_disp]'>
	<option value='fname' <?= $fname_disp ?>>First Last
	<option value='lname' <?= $lname_disp ?>>Last, First
    </select>
  </td>
</tr>
<tr>
  <td>&nbsp;Update notice</td>
  <td>
    <select name='sql_profile[update_notice]'>
	<option value='d' <?= $d ?>>Day
	<option value='w' <?= $w ?>>Week
	<option value='m' <?= $m ?>>Month
    </select>
  </td>
</tr>
<tr>
  <td>&nbsp;Image display</td>
  <td>
    <select name='sql_profile[init_disp]'>
      <option value='480x360' <?= $disp480 ?>>480x360</option>
      <option value='640x480' <?= $disp640 ?>>640x480</option>
      <option value='800x600' <?= $disp800 ?>>800x600</option>
    </select>
  </td>
</tr>
<tr>
  <td>&nbsp;Cookie expiration</td>
  <td>
    <select name='sql_profile[expire]'>
      <option value='never' <?= $expirenever ?>>never</option>
      <option value='soon' <?= $expiresoon ?>>when browser closes</option>
    </select>
  </td>
</tr>
<tr>
  <td>&nbsp;Theme</td>
  <td>
    <select name='sql_profile[theme]'>
	<option value='default'>Default
	<option value='classic'>Classic
    </select>
  </td>
</tr>
<tr>
 <td align=middle colspan=2>
    <input type='hidden' name='refer_back' value='<?= $refer_back ?>'>
    <input type='submit' value='Update'> <input type='reset'></form>
    <? if ($userinfo['access'] == 5) { ?>
         <form action='<?= $config['base_url'] ?>/main.php?updateProfile' method='post'>
	 <input type='hidden' name='delete' value='on'>
	 <input type='hidden' name='user_id' value='<?= $user_id ?>'>
	 <input type='submit' value='Delete'>
	 </form>
    <? } ?>
 </td>
</tr>
</table>
<!-- 
	<form enctype='multipart/form-data' action='<?= $config['base_url'] ?>/main.php?uploadUserImage' method='post'>
	    <input type='hidden' name='user_id' value='<?= $user_id ?>'>
	    <input type='file' name='user_image'>
	    <input type='submit' value='Upload'>
	</form>
-->
<a href='<?= $config['base_url'] ?>/profile/mms.php?user_id=<?= $person['id'] ?>' target=myPopUp onclick="javascript:window.open('', this.target, 'width=480,height=225,scrollbars=no');return true;">mms prefs</a>
<? css_end() ?>
