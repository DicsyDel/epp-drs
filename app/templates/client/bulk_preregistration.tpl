{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="{$stepno}" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Domain preregistration dropcatching"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	<tr valign="top">
		<td width="20%">{t escape=no}Domains to pre-register<br>(one domain per line):{/t}</td>
		<td>
			<textarea name="domains" class="text" cols="70" rows="10"></textarea>	
			<br>
			<br>
			<b>{t}Line format:{/t}</b><br>
			{t}Domain name,expiration date (yyyy-mm-dd){/t}
			<br><br>
			<b>{t}Example:{/t}</b><br>
			domain1.com,2008-04-15<br>
			domain2.net,2008-04-11<br>
			domain3.com,2008-05-12
		</td>
	</tr>
  {include file="client/inc/intable_footer.tpl" color="Gray"}
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next"));
    {/php}  	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}