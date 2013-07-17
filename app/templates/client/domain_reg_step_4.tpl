{include file="client/inc/header.tpl"}
	<script language="Javascript" type="text/javascript">
	
	var ns1 = '{$ns1}';
	var ns2 = '{$ns2}';
	
	{literal}
	function SetManagedDNS(enable)
	{
		if (enable)
		{
			$('ns1').value = ns1;
			$('ns1').disabled = true;
			
			$('ns2').value = ns2;
			$('ns2').disabled = true;
		}
		else
		{
			$('ns1').disabled = false;
			$('ns2').disabled = false;
		}
	}
	{/literal}
	</script>
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="5" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("DNS settings"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" intable_first_column_width=200 header=$intable_header color="Gray"}
    	<tr>
    	   <td>{t}Nameserver 1: *{/t}</td>
    	   <td><input type="text" class="text" name="ns1" id="ns1" value="{$ns1}"></td>
    	</tr>
    	<tr>
    	   <td>{t}Nameserver 2: *{/t}</td>
    	   <td><input type="text" class="text" name="ns2" id="ns2" value="{$ns2}"></td>
    	</tr>
    	{if $enable_managed_dns}
    	<tr valign="top">
    	   <td>{t}Enable managed DNS:{/t}</td>
    	   <td>
    	   		<input style="vertical-align:middle;" type="checkbox" name="enable_managed_dns" onclick="SetManagedDNS(this.checked)" value="1">
    	   		<span style="font-size:10px;">{t}If Managed DNS is enabled, {$servicename} nameservers will be used for this domain. You will be able to control your domain DNS zone right in your registrant Control Panel.{/t}</span>
    	   </td>
    	</tr>
    	{/if}
    	{include file="client/inc/intable_footer.tpl" color="Gray"}
    	{if $add_fields && $add_fields|@count > 0}
    	{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Additional information"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	    	{foreach from=$add_fields key=key item=field}
				{assign var="fname" value=$field.name}
				{if $field.type == 'text'}
				<tr>
					<td width="250">{$key}: {if $field.required}*{/if}</td>
					<td><input style="height:18px;" type="text" class="text" name="add_fields[{$field.name}]" value="{$add_fields_post.$fname}"/>
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'checkbox'}
				<tr valign="top">
					<td width="250">{$key}: {if $field.required}*{/if}</td>
					<td style="vertical-align:top;"><input type="checkbox" name="add_fields[{$field.name}]" value="{$field.value}" {if $add_fields_post.$fname}checked{/if} />
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'memo'}
				<tr valign="top">
					<td width="250">{$key}: {if $field.required}*{/if}</td>
					<td style="vertical-align:top;"><textarea cols="40" class="text" rows="5" name="add_fields[{$field.name}]">{if $add_fields_post.$fname}{$add_fields_post.$fname}{else}{$field.value}{/if}</textarea>
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'select'}
				<tr>
					<td width="250">{$key}: {if $field.required}*{/if}</td>
					<td><select name="add_fields[{$field.name}]">
							{foreach from=$field.values key=vkey item=vfield}
								<option value="{$vkey}" {if $vkey == $add_fields_post.$fname}selected{/if}>{$vfield}</option>
							{/foreach}
						</select>
						{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{/if}
			{/foreach}
		{include file="client/inc/intable_footer.tpl" color="Gray"}
		{/if}
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}