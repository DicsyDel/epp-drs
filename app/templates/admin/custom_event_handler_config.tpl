{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="$ext event handler settings" color="Gray"}
        	{section name=id loop=$rows}
        		{if $rows[id].type == 'text'}
        			<tr>
        				<td width="300">{$rows[id].title}:</td>
        				<td><input name="{$rows[id].key}" type="text" class="text" id="{$rows[id].key}" value="{$rows[id].value}" size="30">
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
        		{elseif $rows[id].type == 'password'}
        			<tr>
        				<td width="300">{$rows[id].title}:</td>
        				<td><input name="{$rows[id].key}" type="password" class="text" id="{$rows[id].key}" value="{$rows[id].value}" size="30">
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
        		{elseif $rows[id].type == 'checkbox'}
        			<tr>
        				<td width="300">{$rows[id].title}:</td>
        				<td><input type="checkbox" name="{$rows[id].key}" {if $rows[id].value == 1}checked{/if} value="1" />
        				{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}</td>
        			</tr>
				{elseif $rows[id].type == 'select'}
					<tr>
						<td width="300">{$rows[id].title}: </td>
						<td><select class="text" name="{$rows[id].key}">
								{foreach from=$rows[id].options key=vkey item=vfield}
									<option {if $vkey == $rows[id].value}selected{/if} value="{$vkey}">{$vfield}</option>
								{/foreach}
							</select> {if $rows[id].required}*{/if}
							
							{if $rows[id].hint}&nbsp;<span class="Webta_Ihelp">{$rows[id].hint}</span>{/if}
						</td>
					</tr>        			
        			
        		{/if}
        	{/section}
        	<input type="hidden" name="ext" value="{$ext}">
        	<input type="hidden" name="action" value="configure">
	{include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}