{include file="admin/inc/header.tpl"}
{include file="admin/inc/table_header.tpl" filter=""}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
    <tr>
		<th nowrap>Module name</th>
		<th nowrap>Payment system</th>
		<th nowrap>Status</th>
		<th nowrap>Settings</th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$modules}
	 <tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$modules[id].mname}</td>
		  <td class="Item" valign="top">{$modules[id].name}</td>
		  <td class="Item" valign="top">
		  	{if $modules[id].status == 1}
				<img border="0" align="absmiddle" alt="{t}Enabled{/t}" src="/admin/images/true.gif"> [<a alt="Disable module" title="Disable module" href="?action=disable&module={$modules[id].nameNormal}">Disable</a>]
			{else}
				{if $modules[id].status != 3}
					<img border="0" align="absmiddle" alt="{t}Disabled{/t}" src="/admin/images/false.gif"> [<a alt="Enable module" title="Enable module" href="?action=enable&module={$modules[id].nameNormal}">Enable</a>]
				{else} 
					Corrupt package!
				{/if}
			{/if}
			</td> 
		  <td class="Item" valign="top">{if $modules[id].status == 1}<a href="pmodule_config.php?module={$modules[id].nameNormal}">Configure</a>{/if}</td>
	</tr>
	{/section}
	</tbody>
	<tr>
		<td colspan="12">&nbsp;</td>
	</tr>
</table>
{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}