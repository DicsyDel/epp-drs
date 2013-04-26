{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="2" />
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Choose domain name"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	<tr valign="top">
		<td>
			<div style="width:500px;">
				<div style="display:inline;">{t}Domain name:{/t}</div> 
			    <div style="display:inline;"><input type="text" class="text" name="domain" value="{$domain}"> </div>
			    <div style="display:inline; vertical-align:middle;"><select name="TLD" class="text">
		    			{section name=id loop=$TLDs}
		    			<option {if $TLD == $TLDs[id]}selected="selected"{/if} value="{$TLDs[id]}">{$TLDs[id]}</option>
		    			{/section}
					</select>	
				</div>
			</div>
		</td>
	</tr>
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}