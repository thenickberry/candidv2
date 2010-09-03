<?php
    include("../config.inc");
    css_top("Search");
?>
<form action='<?= $config['base_url'] ?>/main.php'>
<input type=hidden name=browse>
<input type=hidden name=search value=yes>
<input type=hidden name=search_reload value=yes>
<h1>Search</h1><br>
<!-- #6D8EBF -->
<table class='form'>
<tr>
 <td>
  &nbsp;Newer than&nbsp;
 </td>
 <td>
  <table cellspacing=0 cellpadding=0>
    <td valign=middle width=1>
	<input type='text' name='start_date' id=f_date_c readonly=1>
    </td>
    <td valign=middle style='font-size: 8pt' align=middle>
	&nbsp;<img src='<?= $config['base_url'] ?>/images/calendar.gif' id='f_trigger_c' style='cursor: pointer; border: 1px solid red;' title='Date selector' onmouseover="this.style.background='red'" onmouseout="this.style.background=''" />
   </td>
  </table>
<script type='text/javascript'>
    Calendar.setup({
        inputField     :    "f_date_c",     // id of the input field
        ifFormat       :    "%Y-%m-%d",      // format of the input field
	weekNumbers    :    false,
        button         :    "f_trigger_c",  // trigger for the calendar (button ID)
        align          :    "Tl",           // alignment (defaults to "Bl")
        singleClick    :    true
    });
</script>
 </td>
</tr><tr valign=middle>
 <td>
  &nbsp;Older than&nbsp;
 </td>
 <td>
  <table cellspacing=0 cellpadding=0>
    <td valign=middle style='font-size: 8pt' align=middle>
	<input type='text' name='end_date' id=f_date_d readonly=1>
    </td>
    <td valign=middle style='font-size: 8pt' align=middle>
	&nbsp;<img src='<?= $config['base_url'] ?>/images/calendar.gif' id='f_trigger_d' style='cursor: pointer; border: 1px solid red;' title='Date selector' onmouseover="this.style.background='red'" onmouseout="this.style.background=''" />
    </td>
  </table>
<script type='text/javascript'>
    Calendar.setup({
        inputField     :    "f_date_d",     // id of the input field
        ifFormat       :    "%Y-%m-%d",      // format of the input field
	weekNumbers    :    false,
        button         :    "f_trigger_d",  // trigger for the calendar (button ID)
        align          :    "Tl",           // alignment (defaults to "Bl")
        singleClick    :    true
    });
</script>
 </td>
</tr><tr>
 <td>
  &nbsp;Photographed by&nbsp;
 </td><td>
  <select name='photographer'>
   <option value=''>-ANY-
   <? $personList = getPersonList("","0","");
      echo $personList; ?>
  </select>
 </td>
</tr><tr>
 <td>
  &nbsp;From this category&nbsp;
 </td><td>
  <select name='cat_id'>
   <option value=''>-ANY-
   <? $eventList = getCategoryList("","","");
      print $eventList; ?>
  </select>
 </td>
</tr><tr>
 <td>
  &nbsp;Of these people&nbsp;
 </td><td valign='center'>
  <table class='form' style='border:0px'>
   <td>
    <select name='people[]' multiple size='8'>
     <? echo $personList; ?>
    </select>
   </td>
   <td align=middle width=150>
    <font style='font-size:12px;'>
     Hold Ctrl or <img src='<?= $config['base_url'] ?>/images/apple2.gif'> to<br>select multiple people
    </font>
   </td>
  </table>
 </td>
</tr><tr>
 <td>
  &nbsp;Image ID:
 </td>
    <td valign=middle width=1><input type='text' size=6 name='search_image_id'></td>
</tr><tr>
 <td>
  &nbsp;Sort by
 </td><td>
  <select name='sort'>
   <option value=''>-ANY-
   <option value='last_view'>Recently viewed
   <option value='added'>Recently added
   <option value='views'>Frequently viewed
   <option value='RAND()'>Random
  </select>
 </td>
</tr><tr>
 <td>
  &nbsp;&nbsp;
 </td><td>
  <t1able width='100%'>
   <td align='left'>
    <input type='submit' value='Submit' class=button>
    <input type='reset' class=button>
   </td>
  </table>
 </td>
</tr>
</table>
<br>

<?php css_end(); ?>
