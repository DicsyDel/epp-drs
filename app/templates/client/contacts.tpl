{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	    {assign var="classname" value="text"}
	    {assign var="padding" value="0"}

	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Contact details"));
	    {/php}
	    
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	{include file="inc/contact_dynamic_fields.tpl"}
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	    {if $disclose}
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Whois disclose options"));
	    {/php}
	    
	    {include file="client/inc/intable_header.tpl" header=$intable_header disable_collapse="1" color="Gray"}
	        <tr>
	           <td colspan="2">{t}Allow following details to appear in domain whois response:{/t}<br/><br/></td>
	        </tr>
    	    {foreach key=dname item=dvalue from=$disclose}
    	    <tr>
    	        <td width="1%"><input type="checkbox" {if $dvalue.value == 1}checked{/if} name="disclose[{$dvalue.name}]" value="1" {if $disable_change}disabled{/if} /></td>
        		<td>{$dname}</td>
        	</tr>
    	    {/foreach}
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	    {/if}
	    {if $dsb}
			{include file="client/inc/table_footer.tpl" disable_footer_line=1}
		{else}
			{include file="client/inc/table_footer.tpl" edit_page=1}
		{/if}
{include file="client/inc/footer.tpl"}