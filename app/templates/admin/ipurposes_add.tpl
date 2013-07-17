{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
        <div id="hidden_container" style="display:none;"></div>
		{include file="admin/inc/intable_header.tpl" header="General" color="Gray"}
		<tr>
			<td nowrap="nowrap">Name:</td>
			<td><input type="text" name="name" class="text" id="name" value="{$name}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Description:</td>
			<td><input type="text" name="description" class="text" id="name" value="{$description}" size="20" /></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}		
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}