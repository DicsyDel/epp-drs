{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="$module Settings" color="Gray"}
		    <input type="hidden" name="module" value="{$module}" />
        	{foreach from=$fields item=f}
        		{assign var="name" value=$f->Name}
        		{if $f->FieldType == 'text'}
    			<tr>
    				<td width="18%">{$f->Title}:</td>
    				<td width="82%"><input name="{$f->Name}" type="text" class="text" id="{$f->Name}" value="{$values.$name}" size="30">
    				{if $f->Hint}&nbsp;<span class="Webta_Ihelp">{$f->Hint}</span>{/if}</td>
    			</tr>
    			{elseif $f->FieldType == 'password'}
    			<tr>
    				<td width="18%">{$f->Title}:</td>
    				<td width="82%"><input name="{$f->Name}" type="password" class="text" id="{$f->Name}" value="{$values.$name}" size="30">
    				{if $f->Hint}&nbsp;<span class="Webta_Ihelp">{$f->Hint}</span>{/if}</td>
    			</tr>
        		{elseif $f->FieldType == 'checkbox'}
    			<tr>
    				<td width="18%">{$f->Title}:</td>
    				<td width="82%"><input type="checkbox" name="{$f->Name}" {if $values.$name == 1}checked{/if} value="1" />
    				{if $f->Hint}&nbsp;<span class="Webta_Ihelp">{$f->Hint}</span>{/if}</td>
    			</tr>
    			{elseif $f->FieldType == 'select'}
    			<tr>
    				<td width="18%">{$f->Title}:</td>
    				<td width="82%">
    					<select name="{$f->Name}">
						{foreach from=$f->Options item=vv key=kk}
								<option {if $values.$name == $kk}selected{/if} value="{$kk}">{$vv}</option>
						{/foreach}
						</select>{if $f->Hint}&nbsp;<span class="Webta_Ihelp">{$f->Hint}</span>{/if}</td>
    			</tr>
        		{/if}
        	{foreachelse}
        	<tr>
				<td colspan="2">No configuration options for this module</td>
			</tr>
        	{/foreach}
	       {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}