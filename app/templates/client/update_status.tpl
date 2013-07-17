{include file="client/inc/header.tpl"}
{if !$err}
<div align="center">{t}Registrar transfer requested for this domain name.
 To confirm transfer, please click "Approve" button. Your domain will be transferred to requesting registrar.
 To decline transfer, push "Decline" button.{/t}</div><br />
<form name="" action="" method="post">
<input type="hidden" name="did" value="{$id}" />
<table align="center" cellpadding="2" cellspacing="2" border="0">
	<tr>
		<td><input class="btn" type="submit" name="submit1" onClick="return window.confirm('{t}Are you sure you want to approve this domain transfer?{/t}');" value="{t}Approve{/t}" /></td>
		<td><input class="btn" type="submit" name="submit2" onClick="return window.confirm('{t}Are you sure you want to reject this domain transfer?{/t}');" value="{t}Reject{/t}" /></td>
	</tr>
</table>
</form>
{/if}
{include file="client/inc/footer.tpl"}