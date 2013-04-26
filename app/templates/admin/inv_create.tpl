{include file="admin/inc/header.tpl"}
<script language="Javascript">
var VATs = new Array();
{section name=id loop=$users}
	VATs[{$users[id].id}] = '{$users[id].client_vat}';
{/section}
{literal}
	function Calculate()
	{
		var userid = $('userid').value;
		var vt = (VATs[userid]) ? VATs[userid] : '0';
		$('user_vat').innerHTML = vt;
		
		$('inv_total').innerHTML = (parseFloat($('total').value)+parseFloat($('total').value)/100*parseFloat(vt)).toFixed(2);
	}
{/literal}
</script>
	{include file="admin/inc/table_header.tpl"}
        <div id="hidden_container" style="display:none;"></div>
		{include file="admin/inc/intable_header.tpl" header="General" color="Gray"}
		<tr>
			<td nowrap="nowrap">Client:</td>
			<td>
				<select name="userid" id="userid" onchange="Calculate();">
					{section name=id loop=$users}
						<option {if $selected_user == $users[id].id}selected{/if} value="{$users[id].id}">{$users[id].login}, {$users[id].email}</option>
					{/section}
				</select>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap">Invoice for:</td>
			<td><input type="text" name="description" class="text" id="name" value="" size="60" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Amount:</td>
			<td>{$CurrencyHTML} <input onchange="Calculate();" onkeydown="Calculate();" onkeyup="Calculate();" type="text" name="total" class="text" id="total" value="0" size="5" /></td>
		</tr>
		<tr>
			<td colspan="2"><hr size="1" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">VAT:</td>
			<td><span id='user_vat'>{$VAT}</span>%</td>
		</tr>
		<tr>
			<td nowrap="nowrap">Total:</td>
			<td>{$CurrencyHTML} <span id='inv_total'>0</span></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}		
	{include file="admin/inc/table_footer.tpl" button2=1 button2_name='Create'}
	<script language="Javascript">
	Calculate();
	</script>
{include file="admin/inc/footer.tpl"}