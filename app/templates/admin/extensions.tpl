{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
			    <th>Name</th>
				<th>Description</th>
				<th>Allowed by license</th>
				<th>Enabled</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$rows[id].name}</td>
			<td class="Item" valign="top">{$rows[id].description}</td>
			<td class="Item" valign="top">{if $rows[id].licensed == 1}<img alt="{t}Allowed{/t}" src="images/true.gif">{else}<img alt="{t}Disallowed{/t}" src="images/false.gif">{/if}</td>
			<td class="Item" valign="top">
			{if $rows[id].licensed == 1}
				{if $rows[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif"> [<a href="extensions.php?action=disable&ext={$rows[id].key}">Disable</a>]
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif"> [<a href="extensions.php?action=enable&ext={$rows[id].key}">Enable</a>]
				{/if}
			{else}
				{if $rows[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif">
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif">
				{/if}
			{/if}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="7" align="center">No features found</td>
		</tr>
		{/section}
		<tr>
			<td colspan="5" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}