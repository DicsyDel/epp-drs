{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
    
    	{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Checkout information"));
	    {/php}
    
        {include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
		
        {include file="inc/dynamicform.tpl"}
	    
		
		<input type="hidden" name="gate" value="{$gate}">
		<input type="hidden" name="action" value="proceed">
		<input type="hidden" name="string_invoices" value="{$string_invoices}">
        {include file="client/inc/intable_footer.tpl" color="Gray"}
        
        {php}
	    	// Do not edit PHP code below!
	    	$this->assign('button_name',_("Make payment"));
	    {/php}
        
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}