{include file="admin/inc/header.tpl"}
{include file="admin/inc/table_header.tpl" filter=""}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items">
    <thead>
	<tr>
		<th nowrap>Module name</th>
		<th nowrap>Description</th>
		<th nowrap>Provides extensions</th>
		<th nowrap>Status</th>
		<th nowrap>Settings</th>
		<th nowrap>Test</th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$modules item=module key=key}
	 <tr id='tr_{$smarty.section.id.iteration}'>
		  <td class="Item" valign="top">{$module.name}</td>
		  <td class="Item" valign="top" nowrap>{$module.description}</td>
		  <td class="Item" valign="top">{$module.expTLDs}</td>
		  <td class="Item" valign="top" nowrap>
		  	{if $module.status == 1}
				<img border="0" align="absmiddle" alt="{t}Enabled{/t}" src="/admin/images/true.gif"> [<a alt="Disable module" title="Disable module" href="?action=disable&module={$module.nameNormal}">Disable</a>]
			{else}
				{if $module.status != 3}
					<img border="0" align="absmiddle" alt="{t}Disabled{/t}" src="/admin/images/false.gif"> [<a alt="Enable module" title="Enable module" href="?action=enable&module={$module.nameNormal}">Enable</a>]
				{else} 
					- 
				{/if}
			{/if}
			</td> 
		  <td class="Item" valign="top">{if $module.status == 1}<a href="module_config.php?module={$module.nameNormal}">Configure</a>{/if}</td>
		  <td nowrap class="Item" valign="top">
		  	{if $module.status == 1}<a href="modules_view.php?module={$module.nameNormal}&action=test">Test module</a>
		  	{if $module.run_test == 1}<br><a href="module_certtest.php?module={$module.nameNormal}">Run certification test</a>{/if}
		  	{/if}
		  </td>
	</tr>
	{/foreach}
	</tbody>
	<tr>
		<td colspan="12">&nbsp;</td>
	</tr>
</table>
{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}