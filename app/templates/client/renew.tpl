{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
    
    	{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Renew domain"));
	    {/php}
    
        {include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
        <tr>
            <td colspan="2">
    	{if $canrenew}
			<table width="100%"  border="0" cellspacing="0" cellpadding="4">
			{if $check_inv}
				<tr>
					<td colspan="2"><div align="left">{t escape=no}<b style="color:red;">ATTENTION!</b> You already create invoice for renewal this domain name. <a href="inv_view.php">Click here</a> to view your invoices.{/t}</div></td>
				</tr>
				 <tr>
    				<td colspan="2">&nbsp;</td>
    			  </tr>
			{/if}
			  <tr>
				<td width="10%">{t}Period:{/t}</td>
				<td><select name="period" class="text">
					{section name=id loop=$periods}
							<option value="{$periods[id]}">{$periods[id]}</option>
					{/section}
					</select> {t}years{/t}
				</td>
			  </tr>
			   <tr>
				<td colspan="2">&nbsp;</td>
			  </tr>
			   <tr>
				<td><input name="Submit" type="submit" class="btn" value="{t}Renew{/t}"></td>
				<td>&nbsp;</td>
			  </tr>
			 </table>
    	{else}
    		<div align="left">{t}This domain name cannot be renewed.{/t}<br />
    		{t minDays=$minDays needDays=$needDays escape=no}This domain cannot be renewed now because a minimum amount of time before expiration date (<b style="font-size:11px;">%1</b> days) not reached. 
    		You will be able to renew this domain in <b style="font-size:11px;">%2</b> days.{/t}</div>
    	{/if}
    	</td>
    	</tr>
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}