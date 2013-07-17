{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	   <input type="hidden" name="stepno" value="{$stepno}" />
	   
	    {php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Bulk registration &mdash; Step 5 (Additional information)"));
	    {/php}
	   
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
			
		{foreach from=$extra_forms item=form}
		<tr>
			<td colspan="2" valign="top">
			{include file="client/inc/intable_header.tpl" header=$form.title color="Gray"}


	    	{foreach from=$form.fields key=key item=field}
				{assign var="tld" value=$form.tld}	    	
				{assign var="fname" value=$field.name}
				{if $attr.extra.$tld.$fname}
					{assign var=fvalue value=$attr.extra.$tld.$fname}
				{else}
					{assign var=fvalue value=$field.value}
				{/if}
				
				{if $field.type == 'text'}
				<tr>
					<td width="250">{$key}: {if $field.isrequired}*{/if}</td>
					<td><input style="height:18px;" type="text" class="text" name="extra[{$form.tld}][{$field.name}]" value="{$fvalue}"/>
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'checkbox'}
				<tr valign="top">
					<td width="250">{$key}: {if $field.isrequired}*{/if}</td>
					<td style="vertical-align:top;"><input type="checkbox" name="extra[{$form.tld}][{$field.name}]" value="{$field.value}" {if $attr.extra.$tld.$fname}checked{/if} />
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'memo'}
				<tr valign="top">
					<td width="250">{$key}: {if $field.isrequired}*{/if}</td>
					<td style="vertical-align:top;"><textarea cols="40" class="text" rows="5" name="extra[{$form.tld}][{$field.name}]">{$fvalue}</textarea>
					{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{elseif $field.type == 'select'}
				<tr>
					<td width="250">{$key}: {if $field.isrequired}*{/if}</td>
					<td><select name="extra[{$form.tld}][{$field.name}]">
							{foreach from=$field.values key=vkey item=vfield}
								<option value="{$vkey}" {if $vkey == $fvalue}selected{/if}>{$vfield}</option>
							{/foreach}
						</select>
						{if $field.hint}&nbsp;<span class="Webta_Ihelp">{$field.hint}</span>{/if}
					</td>
				</tr>
				{/if}
			{/foreach}
		
		
			{include file="client/inc/intable_footer.tpl" color="Gray"}
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