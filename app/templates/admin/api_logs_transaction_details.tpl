{include file="admin/inc/header.tpl"}
   	{include file="admin/inc/table_header.tpl" filter=0}


		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
				<th width="25%">Key</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">ID:</td>
			<td class="Item" valign="top">{$row.id}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">Transaction-ID:</td>
			<td class="Item" valign="top">{$row.transaction_id}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">User:</td>
			<td class="Item" valign="top">
				{if $row.user_id != -1}
					<a href="users_view.php?userid={$row.user_id}">{$row.user}</a>
				{else}
					Admin
				{/if}
			</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">IP:</td>
			<td class="Item" valign="top">{$row.ipaddress}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">Action:</td>
			<td class="Item" valign="top">{$row.action}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">Request:</td>
			<td class="Item" valign="top">{$row.request}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">Response:</td>
			<td class="Item" valign="top">{$row.response|nl2br}</td>
		</tr>
		
		<tr>
			<td class="ItemEdit" nowrap="nowrap" valign="top">Error stacktrace:</td>
			<td class="Item" valign="top">{$row.error_trace|nl2br}</td>
		</tr>

		
		<tr>
			<td colspan="2" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>


	{include file="admin/inc/table_footer.tpl" colspan=9 allow_delete=0 disable_footer_line=1 add_new=0}
{include file="admin/inc/footer.tpl"}
