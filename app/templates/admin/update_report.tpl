{include file="admin/inc/header.tpl" form_action="logs_view.php"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Update report" color="Gray"}
      	<tr>
    			<td colspan="2">{$report|nl2br}</td>
    	</tr>
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="delete[]" value="{$transid}">
        <input type="hidden" name="action" value="report">
        <input type="hidden" name="actionsubmit" value="1">
	{include file="admin/inc/table_footer.tpl" button2=1 button2_name="Send this report to developers"}
{include file="admin/inc/footer.tpl"}