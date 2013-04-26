
{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}

	    <input type="hidden" name="stepno" value="{$stepno}" />
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk update nameservers - Step 3 (Enter nameservers)"));
	    {/php}	    
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
			<tr valign="top">
				<td width="20%">{t escape=no}Nameserver to assign:<br>(one hostname per line):{/t}</td>
				<td>
					<textarea name="hostnames" class="text" cols="70" rows="10">{$hostnames}</textarea>	
					<br>
					<br>
					<b>{t}Example:{/t}</b><br>
					ns.hostname.com{if $fields}
		    			{section name=id loop=$fields}
		                    ,{$fields[id]}
		    			{/section}
					{/if}<br>
					ns2.mydomain.com{if $fields}
		    			{section name=id loop=$fields}
		                    ,{$fields[id]}
		    			{/section}
					{/if}
				</td>
			</tr>

		{include file="client/inc/intable_footer.tpl" color="Gray"}
		
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('button_name',_("Next step"));
	    {/php}
	
	   {include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name color="Gray"}
		
		
{include file="client/inc/footer.tpl"}
