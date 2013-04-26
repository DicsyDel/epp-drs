{include file="admin/inc/header.tpl"}
    {include file="admin/inc/table_header.tpl"}
    <table class="Webta_Items" width="100%" rules="groups" frame="box" cellpadding="4" id="Webta_Items">
	<thead>
		<tr>
			<th>Domain extension</th>
			<th>Module</th>
		</tr>
	</thead>
	<tbody>
	{foreach from=$conflicts key=TLD item=item}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top" width="1%" nowrap>{$TLD}</td>
	    <td class="Item" valign="top" nowrap>
	    {if !$item.disabled}
	    <select name="tld_modules[{$TLD}]" {if $item.disabled}disabled{/if} class="text" style="vertical-align:middle;width:200px;">
	    {foreach from=$item.modules key=key item=imodule}
	    	{if $imodule == $item.selected}
	    	<option value="{$imodule}">{$imodule} {if $imodule == $item.selected}(curent){/if}</option>
	    	{/if}
	    {/foreach}
	    {foreach from=$item.modules key=key item=imodule}
	    	{if $imodule != $item.selected}
	    	<option value="{$imodule}">{$imodule} {if $imodule == $item.selected}(curent){/if}</option>
	    	{/if}
	    {/foreach}
	    </select>
	    {else}
	    	Not possible to change because there are domains or contacts on {$item.selected}.
	    {/if}
	    </td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="10" align="center">No extension conflicts detected.</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="8" align="center">&nbsp;</td>
	</tr>
	</tbody>
	</table>
	<input type="hidden" name="task" value="{$task}">
	<input type="hidden" name="module" value="{$module}">
	{include file="admin/inc/table_footer.tpl" colspan=9 edit_page=1}	
{include file="admin/inc/footer.tpl"}