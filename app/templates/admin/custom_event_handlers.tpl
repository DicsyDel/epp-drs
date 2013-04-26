{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl" table_header_text="RAPI (Registry API) event handlers"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items_">
		<thead>
			<tr>
			    <th>Name</th>
			    <th width="200">Configure</th>
				<th width="200">Enabled</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows.registry}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$rows.registry[id].name}</td>
			<td class="Item" valign="top">{if $rows.registry[id].hasconfig}<a href="custom_event_handlers.php?action=configure&ext={$rows.registry[id].name}">Configure</a>{/if}</td>
			<td class="Item" valign="top" nowrap>
				{if $rows.registry[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif"> [<a href="custom_event_handlers.php?action=disable&ext={$rows.registry[id].name}">Disable</a>]
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif"> [<a href="custom_event_handlers.php?action=enable&ext={$rows.registry[id].name}">Enable</a>]
				{/if}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="3" align="center">No RAPI event handlers defined</td>
		</tr>
		{/section}
		<tr>
			<td colspan="3" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
	
	{include file="admin/inc/table_header.tpl" table_header_text="PAPI (Payment API) event handlers"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items_">
		<thead>
			<tr>
			    <th>Name</th>
			    <th width="200">Configure</th>
				<th width="200">Enabled</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows.payment}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$rows.payment[id].name}</td>
			<td class="Item" valign="top">{if $rows.payment[id].hasconfig}<a href="custom_event_handlers.php?action=configure&ext={$rows.payment[id].name}">Configure</a>{/if}</td>
			<td class="Item" valign="top" nowrap>
				{if $rows.payment[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif"> [<a href="custom_event_handlers.php?action=disable&ext={$rows.payment[id].name}">Disable</a>]
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif"> [<a href="custom_event_handlers.php?action=enable&ext={$rows.payment[id].name}">Enable</a>]
				{/if}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="3" align="center">No PAPI event handlers defined</td>
		</tr>
		{/section}
		<tr>
			<td colspan="3" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
	
	{include file="admin/inc/table_header.tpl" table_header_text="Billing event handlers"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items_">
		<thead>
			<tr>
			    <th>Name</th>
			    <th width="200">Configure</th>
				<th width="200">Enabled</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows.invoice}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$rows.invoice[id].name}</td>
			<td class="Item" valign="top">{if $rows.invoice[id].hasconfig}<a href="custom_event_handlers.php?action=configure&ext={$rows.invoice[id].name}">Configure</a>{/if}</td>
			<td class="Item" valign="top" nowrap>
				{if $rows.invoice[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif"> [<a href="custom_event_handlers.php?action=disable&ext={$rows.invoice[id].name}">Disable</a>]
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif"> [<a href="custom_event_handlers.php?action=enable&ext={$rows.invoice[id].name}">Enable</a>]
				{/if}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="3" align="center">No billing event handlers defined</td>
		</tr>
		{/section}
		<tr>
			<td colspan="3" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
	
	{include file="admin/inc/table_header.tpl" table_header_text="Global event handlers"}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items_">
		<thead>
			<tr>
			    <th>Name</th>
			    <th width="200">Configure</th>
				<th width="200">Enabled</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows.app}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" valign="top">{$rows.app[id].name}</td>
			<td class="Item" valign="top">{if $rows.app[id].hasconfig}<a href="custom_event_handlers.php?action=configure&ext={$rows.app[id].name}">Configure</a>{/if}</td>
			<td class="Item" valign="top" nowrap>
				{if $rows.app[id].enabled == 1}
					<img alt="{t}Enabled{/t}" src="images/true.gif"> [<a href="custom_event_handlers.php?action=disable&ext={$rows.app[id].name}">Disable</a>]
				{else}
					<img alt="{t}Disabled{/t}" src="images/false.gif"> [<a href="custom_event_handlers.php?action=enable&ext={$rows.app[id].name}">Enable</a>]
				{/if}
			</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="3" align="center">No global event handlers defined</td>
		</tr>
		{/section}
		<tr>
			<td colspan="3" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}