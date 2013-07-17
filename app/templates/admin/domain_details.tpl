{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Full domain information" color="Gray"}
		{foreach key=key item=item from=$info}
		<tr>
			<td width="100px" nowrap="nowrap">{$key}:&nbsp;&nbsp;&nbsp;</td>
			<td>{$item}</td>
		</tr>
		{/foreach}
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}