{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Change contact owner"));
	    {/php}
	
		{include file="admin/inc/intable_header.tpl" header=$intable_header color="Gray"}
        	<tr valign="top">
        		<td width="20%">{t}Current contact owner:{/t}</td>
        		<td>
        		  {$userinfo.login}({$userinfo.email})
        		</td>
        	</tr>
        	<tr><td colspan="2">&nbsp;</td></tr>
    		<tr valign="top">
        		<td width="20%">{t}New owner:{/t}</td>
        		<td>
        		  <select name="newuserid" class="text" id="dropdown" class="text">
        		  {section name=id loop=$clients}
        		      <option value="{$clients[id].id}">{$clients[id].login} ({$clients[id].email})</option>
        		  {/section}
        		  	  
        		  </select>
        		</td>
        	</tr>
        	{include file="admin/inc/intable_footer.tpl" color="Gray"}
        	
        	{php}
		    	// Do not edit PHP code below!
		    	$this->assign('button_name',_("Change"));
		    {/php}
        	<input type='hidden' name='id' value='{$id}'>
    	    {include file="admin/inc/table_footer.tpl" button2=1 button2_name=$button_name}
{include file="admin/inc/footer.tpl"}