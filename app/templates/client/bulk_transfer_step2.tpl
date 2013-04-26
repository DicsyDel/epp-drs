{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="{$stepno}" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Domains bulk transfer - Step 2 (Enter domains to transfer)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
	<tr valign="top">
		<td width="20%">{t escape=no}Domains to transfer<br>(one domain per line):{/t}</td>
		<td>
			<textarea name="domains" class="text" cols="70" rows="10"></textarea>	
			<br>
			<br>
			<b>{t}Line format:{/t}</b><br>
			{t}Domain name (without extension){/t}
			{if $fields}
    			{section name=id loop=$fields}
                    ,{$fields[id]}
    			{/section}
			{/if}
			<br><br>
			<b>{t}Example:{/t}</b><br>
			domain1{if $fields}
    			{section name=id loop=$fields}
                    ,{$fields[id]}
    			{/section}
			{/if}<br>
			domain2{if $fields}
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
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}