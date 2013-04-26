{include file="admin/inc/login_header.tpl"}
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
				{if $errmsg != ''}
				    <span class="error">{$errmsg}</span>
				{/if}
				<div id="loginform_inner">
				  <table align="center" cellpadding="5" cellspacing="0">
				    <tr>	
				    	<td colspan="2">&nbsp;</td>
				    </tr>
				    <tr>
					    <td align="right">Login:</td>
				    	<td align="left"><input name="login" type="text" class="text" id="login" value="{$login}" size="15" /></td>
				    </tr>
				    <tr>
				    	<td align="right">Password:</td>
						<td align="left"><input name="pass" type="password" class="text" id="pass" size="15" /></td>
				    </tr>
				    <tr>
				    	<td><input name="s2" type="hidden" id="s2" value="{$s}" /></td>
				    	<td align="left"><input name="Submit2" type="submit" class="btn" value="Login" /></td>
				    </tr>
				  </table>
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
{include file="admin/inc/login_footer.tpl"}