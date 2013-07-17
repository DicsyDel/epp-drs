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
        
        {if $is_autoup_server_down}
        	{include file="admin/inc/intable_header.tpl" header="Installed updates" color="Gray"}
	        <tr>
	        	<td colspan="2" style="color:red;">Auto-update server is down for maintenance. </td>
	        </tr>
	        {include file="admin/inc/intable_footer.tpl" color="Gray"}
        {else}
	        {if $rows|@count > 0}
	        {include file="admin/inc/intable_header.tpl" header="Installed updates" color="Gray"}
	        <tr>
	        	<td colspan="2">
		        {include file="admin/inc/table_header.tpl" nofilter=1}
				<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="4" id="Webta_Items_">
				<thead>
					<tr>
						<th>Date</th>
						<th>Releases in this update </th>
						<th>Update details</th>
						<th>Status</th>
						<th>Report</th>
					</tr>
				</thead>
				<tbody>
				{section name=id loop=$rows}
				<tr id='tr_{$smarty.section.id.iteration}'>
					<td class="Item" valign="top">{$rows[id].dtupdate}</td>
					<td class="Item" valign="top">{$rows[id].releases}</td>
					<td class="Item" valign="top"><a href="updates.php?task=info&id={$rows[id].id}">View update details</a></td>
					<td class="Item" valign="top">{$rows[id].status}</td>
					<td class="Item" valign="top"><a href="update_report.php?id={$rows[id].id}">View report</a></td>
				</tr>
				{/section}
				<tr>
					<td colspan="8" align="center">&nbsp;</td>
				</tr>
				</tbody>
				</table>
		        {include file="admin/inc/table_footer.tpl" colspan=9 disable_footer_line=1}
		        </td>
	        </tr>
	        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	        {/if}
	    {/if}
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}