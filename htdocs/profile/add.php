<?php
    include("../../config.inc");
    access_check($userinfo['access'],'1');
    if (isset($_POST['fname'],$_POST['lname'])) {
		$fname = $_POST['fname'];
		$lname = $_POST['lname'];
		setup_person($fname,$lname);
    }
?>

<form method='post' action='<?= $_SERVER['PHP_SELF'] ?>'>
<table bgcolor='#7b849c' style='color:white'>
<tr>
 <td>&nbsp;First name&nbsp;</td>
 <td> <input type='text' name='fname'> </td>
</tr>
<tr>
 <td>&nbsp;Last name&nbsp;</td>
 <td> <input type='text' name='lname'> </td>
</tr>
<tr>
 <td>&nbsp;Email&nbsp;</td>
 <td> <input type='text' name='email'> </td>
</tr>
<tr>
 <td>
  &nbsp;
 </td>
 <td align=center>
   <table width=100%>
     <td align=center>
       <input type='submit' value='Submit'>
     </td>
     <td align=center>
       <input type='reset'>
     </td>
   </table>
  </td>
</tr>
</table>
</center>
<input type='hidden' name='action' value='add'>
</form>
