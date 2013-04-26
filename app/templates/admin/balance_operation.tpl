{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{include file="admin/inc/intable_header.tpl" header="Add/deduct funds to balance" color="Gray"}
        <tr>
        	<td colspan="2"></td>
        </tr>
        <tr>
        	<td width="30%">Client:</td>
        	<td>
        		<select name="userid" class="text">
        			{section name=id loop=$users}
   					<option value="{$users[id].id}" {if $users[id].id == $attr.userid}selected{/if}>{$users[id].login} ({$users[id].email})</option>
        			{/section}
        		</select>
        	</td>
        </tr>
        <tr valign="top">
        	<td>Operation:</td>
        	<td>
        		<label><input type="radio" name="type" value="Deposit" checked="checked"> Deposit</label>
        		<label><input type="radio" name="type" value="Withdraw"> Withdraw</label>
        	</td>
        </tr>
        <tr valign="top">
        	<td>Amount:</td>
        	<td>
        		<span>{$Currency}</span>
        		<input name="amount" size="10" maxlength="10" value="{$attr.amount}"> 
        	</td>
        </tr>
        <tr valign="top">
        	<td>Description (max 255 characters):</td>
        	<td>
        		<textarea rows="4" cols="70" name="description">{$attr.description}</textarea>
        	</td>
        </tr>

        
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}