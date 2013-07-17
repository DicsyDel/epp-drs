{include file="client/inc/login_header.tpl"}
	<center>
	<div class="middle">	
	
		<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table">
		<tr>
			<td width="7"><div class="TableHeaderLeft"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td width="7"><div class="TableHeaderRight"></div></td>
		</tr>
		<tr>
			<td width="7" class="TableHeaderCenter"></td>
			<td align="center"><div id="loginform">
				{if $mess != ''}
				    <span class="error">{$mess}</span>
				{/if}
				{if !$action}
				<div id="loginform_inner">
				  <table align="center" cellpadding="5" cellspacing="0">
				    <tr>	
				    	<td colspan="2">&nbsp;</td>
				    </tr>
				    <tr>
					    <td align="right">{t}Login:{/t}</td>
				    	<td align="left"><input name="login" type="text" class="text" id="login" value="{$login}" size="15" /></td>
				    </tr>
				    <tr>
				    	<td align="right">{t}Password:{/t}</td>
						<td align="left"><input name="pass" type="password" class="text" id="pass" size="15" /></td>
				    </tr>
				    <tr>
				    	<td><input name="s2" type="hidden" id="s2" value="{$s}" /></td>
				    	<td align="left"><input name="Submit2" type="submit" class="btn" value="{t}Login{/t}" />&nbsp;<input name="Submit1" type="button" onClick="document.location='?action=lostpwd';" class="btn" value="{t}Forgot password?{/t}" /></td>
				    </tr>
				  </table>
				  {else}
				  </form>
				  <form name="form1" method="post" action="?action=lostpwd" style="padding:0px;margin:0px;">
				  <table align="center" cellpadding="5" cellspacing="0">
				    <tr>	
				    	<td colspan="2">&nbsp;</td>
				    </tr>
				    <tr>
					    <td align="right">{t}E-mail:{/t}</td>
				    	<td align="left"><input name="email" type="text" class="text" id="login" value="" size="15" /></td>
				    </tr>
				    <tr>
				    	<td><input name="s2" type="hidden" id="s2" value="{$s}" /></td>
				    	<td align="left"><input name="Submit2" type="submit" class="btn" value="{t}Send{/t}" /></td>
				    </tr>
				  </table>
				  {/if}
				  </div>
				  </div></td>
			<td width="7" class="TableHeaderCenter"></td>
		</tr>
		<tr>
			<td width="7"><div class="TableFooterLeft"></div></td>
			<td><div class="TableFooterCenter"></div></td>
			<td width="7"><div class="TableFooterRight"></div></td>
		</tr>
		</table>
			  
	
		<div id="webta_logo_footer"><a href="http://webta.net"><img src="images/webtalogo_footer.gif" /></a></div>
	</div>
	</center>
{include file="client/inc/login_footer.tpl"}