{include file="inc/header.tpl"}
{literal}
<script language="javascript" type="text/javascript">

</script>
{/literal}
<form name="frm1" method="post" action="" id="frm1">
<input type="hidden" name="step" value="checkout">
<input type="hidden" name="backstep" value="{$backstep}">
<input type="hidden" name="direction" id="direction" value="" />
<div align="center">
	<div style="width:600px; height:90%;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
	<div style="margin:20px;" align="left">
		<span class="titlerightblue">{t}Your order:{/t}</span>
		<br><br>
		{section name=id loop=$smarty.session.wizard.cart_confirm.register}
		{assign var=domain value=$smarty.session.wizard.cart_confirm.register[id]}
		<div style="width:560px;" align="left">
			<div style="width:240px;float:left;">{$domain}</div>
			<div style="float:right;width:80px;" align="right">{$Currency} {$finalprices[$domain]|string_format:"%.2f"}</div>
			{if $discounts[$domain] > 0}<div style="float:right;padding:0px 5px 0px 5px;">- {$discounts[$domain]|string_format:"%.2f"}%</div>{/if}
			{if $discounts[$domain] > 0}<div style="float:right;padding:0px 5px 0px 5px;">{$Currency} {$prices[$domain]|string_format:"%.2f"}</div>{/if}
			<div style="float:right;width:80px;padding:0px 5px 0px 5px;" align="left">
			{if $operation == 'Register'}
				{$smarty.session.wizard.cart_confirm.period[$domain]} year{if $smarty.session.wizard.cart_confirm.period[$domain] > 1}s{else}&nbsp;{/if}
			{else}
				
			{/if}
			</div>
			<div style="clear:both;font-size:1px;height:1px;"></div>
		</div>
		{/section}
		<hr size="1" style="">
		{if $total_discount > 0}
		<div style="width:560px;" align="right">
			<div style="float:right;width:100px;" align="right">{$Currency} {$total|string_format:"%.2f"}</div>
			<div style="float:right;">{t}Total:{/t} </div>
			<div style="clear:both;font-size:1px;height:1px;"></div>
		</div>
		<div style="width:560px;" align="right">
			<div style="float:right;width:100px;" align="right">{$Currency} -{$total_discount|string_format:"%.2f"}</div>
			<div style="float:right;">{t}Total discount:{/t} </div>
			<div style="clear:both;font-size:1px;height:1px;"></div>
		</div>
		{/if}
		{if $VAT > 0 && $vat_sum > 0}
		<div style="width:560px;" align="right">
			<div style="float:right;width:100px;" align="right">{$Currency} {$vat_sum|string_format:"%.2f"}</div>
			<div style="float:right;">{t vat=$VAT}VAT (%1%):{/t} </div>
			<div style="clear:both;font-size:1px;height:1px;"></div>
		</div>
		{/if}
		<div style="width:560px;" align="right">
			<div style="float:right;width:100px;" align="right">{$Currency} {$grandtotal|string_format:"%.2f"}</div>
			<div style="float:right;">{t}Grand total:{/t} </div>
			<div style="clear:both;font-size:1px;height:1px;"></div>
		</div>
		<br>
		<span class="titlerightblue">{t}Payment method:{/t}</span>
		<div style="width:560px;" align="left">
		<table align="left" cellspacing="10" cellpadding="0" align="center" border="0">
		    {if !$hide_balance}
			<tr valign="middle">
				<td>
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


			{section name=id loop=$payment_modules}
			{assign var=mname value=$payment_modules[id]}
			<tr>
				<td>
					{if $mdisabledreason.$mname}
						<div style="float:left;margin-right:5px;margin-top:6px;vertical-align:middle;">
							<input {if $mdisabledreason.$mname}disabled{/if} name="gate" {if $payment_modules[id] == $selected_pmodule}checked{/if} type="radio" value="{$payment_modules[id]}">
						</div>
						<div style="float:left;vertical-align:middle;border:1px solid #cccccc;font-size:10px;padding:4px 4px 4px 10px;text-align:left;width:364px;max-width:364px;background-color:white;">
							{$mdisabledreason.$mname}
						</div>
					{else}
						<input {if $mdisabledreason.$mname}disabled{/if} name="gate" {if $payment_modules[id] == $selected_pmodule}checked{/if} type="radio" value="{$payment_modules[id]}">
						<img src="/images/modules/{$payment_modules[id]}_icon.png" alt="{$payment_modules[id]}" title="{$payment_modules[id]}" align="absmiddle">
					{/if}
				</td>
			</tr>
			{/section}
		</table>
		</div>
		<div style="clear:both;"></div>
		<div style="margin-top:15px;"><input type="checkbox" id="terms" name="terms" value="1" /> {t escape=no}I agree with <a target="_blank" href="terms.php">{$servicename} Terms</a>{/t}</div>
		<br><br>
		<span class="btnbox">
			<div style="display:{if $operaton == 'Register'}none{/if};width:176px;" align="left" id="next_btn">
					<br>
					<div style="float:right;"><input id="sbmt1" type="image" src="images/wiz_btn_co.gif" onclick="{literal}if ($('terms').checked == false) { {/literal}alert('{t}You must agree with Terms and Conditions to continue.{/t}{literal}'); return false; }else{ SubmitForm('next'); }{/literal}" name="sbmt1" value="{t}Next >>{/t}"></div>
					<div style="float:right;"><input id="sbmt2" type="image" src="images/wiz_btn_prev.gif" onclick="SubmitForm('back')" name="sbmt2" value="{t}<< Back{/t}"></div>
				</div>
		</span>
	</div>
	</div>
</div>
</form>
{include file="inc/footer.tpl"}