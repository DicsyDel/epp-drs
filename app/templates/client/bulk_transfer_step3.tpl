
{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="{$stepno}" />
	   
		<script src="/js/tooltip-v0.1.js" type="text/javascript"></script>	
		<script type="text/javascript" src="/js/DomainChecker.js"></script>	   
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk transfer &mdash; Step 3 (Checking transfer possibility)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	<tr valign="top">
		<td width="20%">{t}Domains to check:{/t}</td>
		<td>
			<div id="domain_checker_wrapper" style="width: 60%;"></div>
			<script type="text/javascript">
    	    // {literal}
			document.observe("dom:loaded", function () {
				var nextBtn = $$("input[name=cbtn_2]")[0];
				nextBtn.disabled = true;
			
				var dc = new DomainChecker({
    	   		// {/literal}
	    	   		renderTo: 		"domain_checker_wrapper",
	    	   		queue: 			{$domains},
	    	   		baseUrl: 		"server/check.php?action=transfer",
	    	   		availMessage: 	"{t}Available for transfer{/t}",
	    	   		unavailMessage: "{t}Transfer not available{/t}",
	    	   		failedMessage:	"{t}Cannot check transfer availability. Make sure that you spelled domain name correctly{/t}",
	    	   		onComplete: 	onCompleteCheck
    	   		// {literal} 
				});

				nextBtn.observe("click", onSubmitForm);
				dc.start();
				
				// Event handlers
				function onCompleteCheck () {
					nextBtn.disabled = false;
				}
				function onSubmitForm () {
					dc.stop();
				}
			});
    	   // {/literal}
    	   </script>
    	   			
		</td>
	</tr>
  {include file="client/inc/intable_footer.tpl" color="Gray"}
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}  	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}