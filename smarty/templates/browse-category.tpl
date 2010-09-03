
<table cellspacing=0 cellpadding=0>
	<tr valign='bottom' style='background:url(/images/table-shade.png) repeat-x bottom'>
{section name=cat loop=$category_data}
{if $smarty.section.cat.iteration % $user.numcols == 1}
		</tr><tr valign='bottom'>
{/if}
		<td align='center'>
			<table class='category' style='padding:20px'>
{if $category_data[cat].category_image_id}
				<tr><td align='center'>
				<!-- <img src='{$http_base}/main.php?displayImage&image_id={$category_data[cat].category_image_id}&cat_thumb=yes' border=0> -->
				<img src='{$http_base}/main.php?displayImage&image_id={$category_data[cat].category_image_id}&cat_thumb=yes' border=0>
				</td><tr>
{/if}
				<tr><td>
					<a href="{$category_data[cat].url}" style='font-size:12px'>{$category_data[cat].name}</a>
					<br />
					{$category_data[cat].image_count} images
					<br />
					Updated {$category_data[cat].update}
					<br />
				</td></tr>
			</table>
		</td>
{/section}
	</tr>
</table>
