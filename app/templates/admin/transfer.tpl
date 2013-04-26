{include file="admin/inc/header.tpl"}
{section name=id loop=$domains}
	<input type="hidden" name="domains[]" value="{$domains[id].id}" />
{/section}
{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Transfer domains, contacts and invoices to another client" color="Gray"}
<input type="hidden" name="action" value="transfer" />
  	<tr>
		<td>Domains to transfer:</td>
		<td>
		{section name=id loop=$domains}
			{if $smarty.section.id.last}
				{$domains[id].name}
			{else}
				{$domains[id].name}, 
			{/if}
		{/section}	
		</td>
	</tr>
  	<tr>
		<td width="20%">New client:</td>
		<td>
			<select name="n_userid" class="text">
			{section name=id loop=$users}
				<option value="{$users[id].id}">{$users[id].login}</option>
			{/section}
			</select>
		</td>
	</tr>
	{include file="admin/inc/intable_footer.tpl" color="Gray"}
{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}