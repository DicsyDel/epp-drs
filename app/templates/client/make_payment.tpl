{include file="client/inc/header.tpl" form_action="checkout.php" onsubmit="return submitMakePaymentForm(this)"}
{include file="client/inc/table_header.tpl"}
{literal}
<script type="text/javascript">
function submitMakePaymentForm(form) {
	try {
		var data = $(form).serialize(true);
		if (data.gate == 'OfflineBank') {
			form.target = '_blank';
			setTimeout(function () {
				window.location.href = 'offlinebank_complete.php';
			}, 10);
			return true;
		}
	}
	catch (e) {}
}
</script>
{/literal}
    <table class="Webta_Items" rules="groups" width="100%" frame="box" cellpadding="4" id="Webta_Items_">
    <thead>
    <tr>
		<th nowrap>{t}Summary{/t}</th>
		<th width="1%" nowrap>{t}Amount{/t}</th>
	</tr>
	</thead>
	<tbody>
	{section name=id loop=$rows}
	<tr id='tr_{$smarty.section.id.iteration}'>
		<td class="Item" valign="top">{$rows[id]->Description}</td>
		<td width="1%" nowrap class="ItemEdit" valign="top">{$CurrencyHTML} {$rows[id]->GetTotal()|string_format:"%.2f"} <input type="hidden" name="invoices[]" value="{$rows[id]->ID}" style="margin:0px;padding:0px;"></td>
	</tr>
	{/section}
	<tr>
		<td>&nbsp;</td>
		<td class="ItemEdit" valign="top"></td>
	</tr>
	<tr id='tr_{$smarty.section.id.iteration}' style="font-weight:bold;">
		<td class="Item" valign="top" align="right">{t}Total:{/t} {if $vat > 0}{t vat=$vat}(Incl. VAT %1%){/t}{/if}</td>
		<td width="1%" nowrap class="ItemEdit" valign="top">{$CurrencyHTML} {$total|string_format:"%.2f"}</td>
	</tr>
	</tbody>
</table>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}

{include file="client/inc/table_header.tpl"}

	{php}
    	// Do not edit PHP code below!
    	$this->assign('intable_header',_("Select payment method"));
    {/php}

    {include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    
    {if !$hide_balance}
	<tr valign="middle">
		<td width="700" colspan="2">
			{if $balance_disabled}
				<input name="gate" disabled="disabled" type="radio" value="Balance">
				<img src="/images/modules/balance_icon.png" alt="{t}Payment from balance{/t}" title="{t}Payment from balance{/t}" align="absmiddle">
				{$balance_disabled_reason|nl2br}
			{else}
				<input name="gate" type="radio" value="Balance">
				<img src="/images/modules/balance_icon.png" alt="{t}Payment from balance{/t}" title="{t}Payment from balance{/t}" align="absmiddle">
			{/if}
		</td>
	</tr>
	{/if}
    
    {if $payment_modules|@count > 0}
	{section name=id loop=$payment_modules}
	{assign var=mname value=$payment_modules[id]}
	<tr valign="middle">
		<td width="700" colspan="2">
			{if $mdisabledreason.$mname}
				<div style="float:left;margin-right:5px;margin-top:6px;vertical-align:middle;">
					<input {if $mdisabledreason.$mname}disabled{/if} name="gate" {if $payment_modules[id] == $selected_pmodule}checked{/if} type="radio" value="{$payment_modules[id]}">
				</div>
				<div style="float:left;vertical-align:middle;border:1px solid #cccccc;font-size:10px;padding:4px 4px 4px 10px;text-align:left;width:348px;max-width:356px;background-color:white;">
					{$mdisabledreason.$mname}
				</div>
			{else}
				<input {if $mdisabledreason.$mname}disabled{/if} name="gate" {if $payment_modules[id] == $selected_pmodule}checked{/if} type="radio" value="{$payment_modules[id]}">
				<img src="/images/modules/{$payment_modules[id]}_icon.png" alt="{$payment_modules[id]}" title="{$payment_modules[id]}" align="absmiddle">
			{/if}
		</td>
	</tr>
	{/section}
    {/if}	
	
    {include file="client/inc/intable_footer.tpl" color="Gray"}

    
    {php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Checkout"));
    {/php}
    
{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}

{include file="client/inc/footer.tpl"}
