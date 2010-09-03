
<h1>Search</h1>
<form action='{$http_base}/search' method='post'>
<br />
<table class='form'>
	<tr>
		<td>&nbsp;Newer than&nbsp;</td>
		<td valign='middle'>
			<input type='text' name='start_date' id='f_date_c'>
			&nbsp;&nbsp;<img src='{$http_base}/images/calendar.gif' id='f_trigger_c' style='cursort:pointer; border:1px solid red' title='Date selector' onmouseover='this.style.background="red"' onmouseout='this.style.background=""'>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td>&nbsp;Older than&nbsp;</td>
		<td valign='middle'>
			<input type='text' name='start_date' id='f_date_d'>
			&nbsp;&nbsp;<img src='{$http_base}/images/calendar.gif' id='f_trigger_d' style='cursort:pointer; border:1px solid red' title='Date selector' onmouseover='this.style.background="red"' onmouseout='this.style.background=""'>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td>&nbsp;Photographed by&nbsp;</td>
		<td>
			<select name='photographer'>
				<option value=''>-ANY-</option>
{section name=ph loop=$photographers}
				<option value='{$photographers[ph].id}'>{$photographers[ph].full_name}</option>
{/section}
			</select>
		</td>
	</tr>
	<tr>
		<td>&nbsp;From this category:&nbsp;</td>
		<td>
			<select name='category_id'>
				<option value=''>-ANY-</option>
{section name=c loop=$categories}
				<option value='{$categories[c].id}'>{$categories[c].name}</option>
{/section}
			</select>
		</td>
	</tr>
	<tr>
		<td>&nbsp;Of these people&nbsp;</td>
		<td>
			<select name='people[]' multiple size='8'>
{section name=pe loop=$peoples}
				<option value='{$peoples[pe].id}'>{$peoples[pe].full_name}</option>
{/section}
			</select>
		</td>
	</tr>
	<tr>
		<td>&nbsp;Sort by&nbsp;</td>
		<td>
			<select name='sort'>
				<option value=''>-ANY-
				<option value='last_view'>Recently viewed
				<option value='added'>Recently added
				<option value='views'>Frequently viewed
				<option value='random'>Random
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<input type='submit' value='Submit' class='button'>
			<input type='reset' class='button'>
		</td>
	</tr>
</table>

