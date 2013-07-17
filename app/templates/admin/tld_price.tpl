{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Registration and renewal pricing" color="Gray"}
			<tr>
				<td nowrap="nowrap"></td>
				<td>
					&nbsp;<b>Registration</b>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<b>Renew</b>
					{if $preregistration_enabled}
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<b>Pre-registration</b>
					{/if}
				</td>
			</tr>
			{section name=id loop=$periods}
			{assign var=period value=$periods[id]}
			<tr>
				<td nowrap="nowrap">{$period} year{if $period > 1}s{/if}:</td>
				<td>
					{$CurrencyHTML}<input type="text" name="register[{$period}]" class="text" id="name" value="{$price_register.$period}" size="5" />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					{$CurrencyHTML}<input type="text" name="renew[{$period}]" class="text" id="name" value="{$price_renew.$period}" size="5" />
					{if $preregistration_enabled}
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					{$CurrencyHTML}<input type="text" name="preregister[{$period}]" class="text" id="name" value="{$price_preregister.$period}" size="5" />
					{/if}
				</td>
			</tr>
			{/section}
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Transfer pricing" color="Gray"}
		<tr>
			<td nowrap="nowrap">Price:</td>
			<td>{$CurrencyHTML}<input type="text" name="transfer" class="text" id="name" value="{$price_transfer}" size="5" /></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{if $trade_enabled}
		{include file="admin/inc/intable_header.tpl" header="Trade pricing" color="Gray"}
		<tr>
			<td nowrap="nowrap">Price:</td>
			<td>{$CurrencyHTML}<input type="text" name="trade" class="text" id="name" value="{$price_trade}" size="5" /></td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{/if}
		
		{if $discount_packages|@count > 0}
			{include file="admin/inc/intable_header.tpl" header="Discounts" color="Gray"}
			<tr>
				<td colspan="2">
					<table border="0" style="font-weight:bold;">
						<tr>
							<td width="150">Package</td>
							<td width="150">Registration</td>
							<td width="150">Renewal</td>
							<td width="150">Transfer</td>
							{if $trade_enabled}
							<td width="150">Trade</td>
							{/if}
							{if $preregistration_enabled}
							<td width="150">Preregistration</td>
							{/if}
						</tr>
					</table>
				</td>
			</tr>
			{section name=id loop=$discount_packages}
			<tr>
				<td colspan="2">
					<table border="0">
						<tr>
							<td width="150">{$discount_packages[id].name}</td>
							<td width="150"><input type="text" name="discounts[{$discount_packages[id].id}][register]" class="text" id="" value="{$discount_packages[id].register}" size="1" />%</td>
							<td width="150"><input type="text" name="discounts[{$discount_packages[id].id}][renew]" class="text" id="" value="{$discount_packages[id].renew}" size="1" />%</td>
							<td width="150"><input type="text" name="discounts[{$discount_packages[id].id}][transfer]" class="text" id="" value="{$discount_packages[id].transfer}" size="1" />%</td>
							{if $trade_enabled}
							<td width="150"><input type="text" name="discounts[{$discount_packages[id].id}][trade]" class="text" id="" value="{$discount_packages[id].trade}" size="1" />%</td>
							{/if}
							{if $preregistration_enabled}
							<td width="150"><input type="text" name="discounts[{$discount_packages[id].id}][preregister]" class="text" id="" value="{$discount_packages[id].preregister}" size="1" />%</td>
							{/if}
						</tr>
					</table>
				</td>
			</tr>
			{/section}
			{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{/if}
		<input type="hidden" name="pn" value="{$pn}">
		<input type="hidden" name="pt" value="{$pt}">
		<input type="hidden" name="pf" value="{$pf}">
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}
