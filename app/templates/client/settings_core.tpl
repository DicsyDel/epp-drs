{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Interface settings"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		<tr>
			<td colspan="2"><input name="inline_help" {if $inline_help}checked{/if} type="checkbox" id="inline_help" value="1"> {t}Display inline help:{/t}</td>
		</tr>
		<tr>
			<td colspan="2"><input name="prefill_contact" {if $prefill_contact}checked{/if} type="checkbox" value="1"> {t}Prefill new contacts during creation with client profile data{/t}</td>
		</tr>
		<tr>
			<td colspan="2"><input name="auto_pay" onclick="document.getElementById('auto_pay_no_renew').disabled = !this.checked"  {if $auto_pay}checked{/if} type="checkbox" id="auto_pay" value="1"> {t}Always deduct funds from my account balance (if there is a sufficient amount){/t}</td>
		</tr>
		<tr>
			<td colspan="2"><input name="auto_pay_no_renew" {if !$auto_pay}disabled{/if} {if $auto_pay_no_renew}checked{/if} type="checkbox" id="auto_pay_no_renew" value="1"> {t}Dont automaticaly pay renew invoices from balance{/t}</td>
		</tr>

		<tr>
			<td colspan="2"><input onclick="document.getElementById('low_balance_value').disabled = !this.checked" name="low_balance_notify" {if $low_balance_notify}checked{/if} type="checkbox" value="1"> {t}Send me email when balance is lower than{/t} {$Currency}<input type="text" size="4" name="low_balance_value" value="{$low_balance_value}" id="low_balance_value" class="text" {if !$low_balance_notify}disabled{/if}/> </td>
		</tr>
		<tr>
			<td colspan="2">
			{php}
				// Do not edit PHP code below!
				$input = '<input name="expire_notify_start_days" class="text" size="2" type="text" value="'.$this->get_template_vars("expire_notify_start_days").'">';
				$this->assign("expire_notify_start_days_input", $input);
			{/php}
			{t escape=no input=$expire_notify_start_days_input}Start sending domain expiration notices in %1 days before domain is being to be expired.{/t}
			</td>
		</tr>
	
		
		
		{include file="client/inc/intable_footer.tpl" color="Gray"}
		
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Default nameservers"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		<tr>
			<td>{t}Nameserver{/t} 1: </td>
			<td><input type="text" name="ns1" class="text" value="{$ns1}"></td>
		</tr>
		<tr>
			<td>{t}Nameserver{/t} 2: </td>
			<td><input type="text" name="ns2" class="text" value="{$ns2}"></td>
		</tr>
		
		{include file="client/inc/intable_footer.tpl" color="Gray"}
		
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}
