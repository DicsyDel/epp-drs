{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Display currency" color="Gray"}
		<tr>
			<td width="18%">Currency (HTML):</td>
			<td width="82%"><input name="currency" type="text" class="text" value="{$currency}" size="30"></td>
		</tr>
		<tr>
			<td>Currency (ISO):</td>
			<td><input name="currencyISO" type="text" class="text" value="{$currencyISO}" size="30"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}

		{include file="admin/inc/intable_header.tpl" header="Billing currency" color="Gray"}
		<tr>
			<td>Currency (ISO):</td>
			<td><input name="billing_currencyISO" type="text" class="text" value="{$billing_currencyISO}" size="30"></td>
		</tr>
		<tr>	
			<td width="18%">Exchange rate (Display/Billing):</td>
			<td width="82%"><input name="currency_rate" type="text" class="text" value="{$currency_rate}" size="30"></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}

	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}
