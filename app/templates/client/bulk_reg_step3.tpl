
{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="stepno" value="{$stepno}" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk registration &mdash; Step 3 (Select registration period)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
			
		{foreach from=$period_forms item=form}
			<tr valign="top">
				<td width="20%">{$form.title}:</td>
				<td><select name="periods[{$form.tld}]" class="text">
    			  {section name=id loop=$form.periods}
    		      <option value="{$form.periods[id].period}">
    		      {$form.periods[id].period} year{if $form.periods[id].period > 1}s{/if} ({$Currency} {$form.periods[id].price|string_format:"%.2f"})
    		      </option>
				  {/section}
				</select>
				</td>
			</tr>
		{/foreach}
			
		{include file="client/inc/intable_footer.tpl" color="Gray"}

	   
  
  
	{php}
    	// Do not edit PHP code below!
    	$this->assign('button_name',_("Next step"));
    {/php}  	
	{include file="client/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="client/inc/footer.tpl"}