{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
        </form>
        <form action="" method="post" style="margin:0px; padding:0px;">
        <div id="hidden_container" style="display:none;"></div>
		{include file="admin/inc/intable_header.tpl" header="Send report to developers" color="Gray"}
		<tr>
			<td nowrap="nowrap" colspan="2">
				This form will send report  email to developers for further investigation.<br>
				Please use this feature only if you were asked to submit a report.<br>
				<br>
				The following info will be included in message:<br>
				<ul>
					<li> Selected log entries </li>
					<li> phpinfo </li>
					<li> License details </li>
				</ul>
			</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td colspan="2">Comments:<br><i style="font-size:10px;">Include as much information as possible.</i><br>
				<textarea class="text" name="comments" cols="70" rows="7"></textarea>
			</td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		<input type="hidden" name="action" value="send" />
		<input type="hidden" name="log_entries" value="{$log_entries}" />
		<input type="hidden" name="actionsubmit" value="1" />
	{include file="admin/inc/table_footer.tpl" button2=1 button2_name="Send"}
{include file="admin/inc/footer.tpl"}