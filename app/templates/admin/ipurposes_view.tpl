{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
			    <th>Name</th>
				<th>Description</th>
				<th>System</th>
				<th width="1%">Edit</th>
				<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="checkall()"></td>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}'>
		    <td class="Item" valign="top">{$rows[id].name}</td>
			<td class="Item" valign="top">{$rows[id].description}</td>
			<td class="Item" valign="top">{if $rows[id].issystem == 1}<img alt="{t}System{/t}" src="images/true.gif">{else}<img alt="{t}Custom{/t}" src="images/false.gif">{/if}</td>
			<td class="ItemEdit" valign="top" align="center">{if $rows[id].issystem != 1}<a href="ipurposes_add.php?id={$rows[id].id}">Edit</a>{/if}</td>
			<td class="ItemDelete" valign="top" align="center">
				<span>
					<input type="checkbox" {if $rows[id].issystem == 1}disabled{/if} id="delete[]" name="delete[]" value="{$rows[id].id}">
				</span>
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="7" align="center">No fields found</td>
		</tr>
		{/section}
		<tr>
			<td colspan="3" align="center">&nbsp;</td>
			<td class="ItemEdit" valign="top">&nbsp;</td>
			<td class="ItemDelete" valign="top">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" colspan=9}
{include file="admin/inc/footer.tpl"}