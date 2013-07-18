{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Product information" color="Gray"}
		<tr>
			<td>Product name:</td>
			<td>EPP-DRS</td>
		</tr>
		<tr>
			<td>Product version:</td>
			<td>{$version}</td>
		</tr>
		<tr>
			<td>Host ID:</td>
			<td>{$hostid}</td>
		</tr>
		<tr>
			<td>Expire Date:</td>
			<td>{$expire_date}</td>
		</tr>
		
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{if $lic_info && $lic_info|@count > 0}
		{include file="admin/inc/intable_header.tpl" header="License information" color="Gray"}
		{foreach from=$lic_info item=value key=name}
		<tr>
			<td>{$name}:</td>
			<td>{$value}</td>
		</tr>
		{/foreach}
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
        {/if}
        
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}
