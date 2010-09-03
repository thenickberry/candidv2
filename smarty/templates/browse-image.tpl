<style type='text/css'>
	@import('shadow.css');
</style>
<table>
	<tr valign=center>
{section name=image loop=$image_data}
{if $smarty.section.image.iteration % $user.numcols == 1}
	</tr>
	<tr valign=center>
{/if}
		<td align=center>
			<table height=100%>
				<tr valign='top'><td>
					<div class='shadow'>
						<a	href='{$http_base}/image/view.php?image_id={$image_data[image].id}'
							target=myPopup onclick="javascript:window.open('',this.target,'width={$user.x},height={$user.y},scrollbars=yes');return true;"><img src='{$http_base}/main.php?showImage&image_id={$image_data[image].id}&thumb=yes' border='0' alt='' title='' id='image_{$image_data[image].id}'></a>
						<div class="topleft"></div>
						<div class="topright"></div>
						<div class="bottomleft"></div>
						<div class="bottomright"></div>
					</div>
				</td></tr><tr><td>
{if $image_data[image].owner == $user.id || $user.access >= $image_data[image].access}
					<span style='float:right'>
						{$image_data[image].ajax_form}
					</span>
{/if}
					<span style='width:225px;text-align:left;padding:0px'>
{if $image_data[image].filename}
						<b>{$image_data[image].filename}</b><br />
{/if}
{if $sel_cat_id != $image_data[image].category_id}
						<a href='{$http_base}/browse.php?cat_id={$image_data[image].category_id}'>{$image_data[image].category_name}</a>
						<br />
{/if}
					</span>
				</td></tr>
			</table>
		</td>
{/section}
		</td>
	</tr>
</table>
