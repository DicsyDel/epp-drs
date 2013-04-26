{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Change contact policy"));
	    {/php}	
	    {include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	    <tr>
	    	<td colspan="2">
	    	{$policy_text}
	    	</td>
	    </tr>
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	    
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('button_js_name',_("Next"));
	    {/php}	
    {include file="client/inc/table_footer.tpl" button_js=1 button_js_action=$button_js_action button_js_name=$button_js_name}
	
{include file="client/inc/footer.tpl"}	