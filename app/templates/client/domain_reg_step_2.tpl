{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="step" value="3" />
	   
	   {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Select registration period"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" intable_first_column_width=100 header=$intable_header color="Gray"}
    	<tr>
    	   <td width="100">{t}Period:{/t}</td>
    	   <td><select name="period" class="text">
    		  {section name=id loop=$periods}
    		      <option value="{$periods[id].period}">
    		      {$periods[id].period} year{if $periods[id].period > 1}s{/if} ({$Currency} {$periods[id].price|string_format:"%.2f"})
    		      </option>
    		  {/section}
    		  </select></td>
    	</tr>
	{include file="client/inc/intable_footer.tpl" color="Gray"}
	
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}
	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}