{include file="admin/inc/header.tpl"}
    {include file="admin/inc/table_header.tpl"}
    <table class="Webta_Items" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Name</th>
			<th>Min domains</th>
			<th>Min balance</th>
			<th width="1%">Users</th>			
			<th width="1%">Edit</th>
			<td width="1%" nowrap><input type="checkbox" name="checkbox" value="checkbox" onClick="webtacp.checkall()"></td>
		</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id].name}</td>
		<td class="Item" valign="top">{$rows[id].min_domains}</td>
		<td class="Item" valign="top">{if $rows[id].min_balance}{$Currency}{$rows[id].min_balance}{/if}</td>
		<td class="Item" valign="top" nowrap>{$rows[id].num_users} [<a href="users_view.php?packageid={$rows[id].id}">View</a>]</td>
		<td class="ItemEdit" valign="top"><a href="packages_add.php?id={$rows[id].id}">Edit</a></td>
		<td class="ItemDelete" valign="top">
			<span>
				<input type="checkbox" id="delete[]" name="delete[]" value="{$rows[id].id}">
			</span>
		</td>
	</tr>
	{sectionelse}
	<tr>
		<td colspan="10" align="center">No packages found!</td>
	</tr>
	{/section}
	<tr>
		<td colspan="4" align="center">&nbsp;</td>
		<td class="ItemEdit" valign="top">&nbsp;</td>
		<td class="ItemDelete" valign="top">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	{include file="admin/inc/table_footer.tpl" colspan=9}	
{include file="admin/inc/footer.tpl"}