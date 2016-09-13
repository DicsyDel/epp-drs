{include file="inc/header.tpl"}
<script>

	var JS_SESSIONID = '{$smarty.session.JS_SESSIONID}'

{literal}
	function sel(event, id)
	{
		var unid = (id == 1) ? 2 : 1;
		var event = event || window.event;
		var elem = Event.element(event);
		
		var dv = $("sel"+id);
		dv.className = "box_highlighted";
		
		dv = $("sel"+unid);
		dv.className = "box_normal";
		
		var r = document.getElementsByName("reg_type");
		r[id-1].checked = true;
		
		r[unid-1].checked = false;
		
		if (elem.id != 'recover_link' && elem.id != 'rsbmt2' && elem.id != 'rsbmt1' && id == 1)
		{
			$('recover_password_dialog').style.display = 'none';
			$('login_dialog').style.display = '';
			$('btn_tr').style.display = '';
		}
	}
	
	function ShowRecoverPasswordDialog()
	{
		$('recover_password_dialog').style.display = '';
		$('login_dialog').style.display = 'none';
		$('btn_tr').style.display = 'none';
	}
	
	function HideRecoverDialog()
	{
		$('recover_password_dialog').style.display = 'none';
		$('login_dialog').style.display = '';
		$('btn_tr').style.display = '';
		$('loading_').style.display = 'none';
		return false;
	}
	
	function RecoverPassword()
	{
		$('loading_').style.display = '';
		
		new Ajax.Request('/server/misc.php?action=recover_pwd&email='+$('recover_email').value+"&JS_SESSIONID="+JS_SESSIONID, 
    	{   
    		method: 'get',   
    		onSuccess: (function(response)
    		{ 
    			if (response.responseText == 'OK')
    			{
    				alert('{/literal}{t}Please confirm password change. E-mail with confirmation link has been send to you.{/t}{literal}');
    			}
    			else
    			{
    				alert("{/literal}{t}No such email in database{/t}{literal}");
    			} 
    			
    			HideRecoverDialog();
    			
    		}).bind(this),
    		onFailure: (function(){ HideRecoverDialog(); }).bind(this)
    	});
		
		return false;
	}
{/literal}
</script>
<div align="center">
<form name="frm1" method="post" action="" id="frm1">
<input type="hidden" name="step" value="{$step}">
<input type="hidden" name="direction" id="direction" value="" />
{if $backstep}<input type="hidden" name="backstep" value="{$backstep}">{/if}
<div style="width:600px; height:500px;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
	<div style="margin:20px;">
	  <table width=510 border=0 cellspacing=20 cellpadding=5 align="center">
	    <tr>
	      <td id="sel1" name="sel1" width="510" valign=top class="{if $reg_type == 'newclient'}box_highlighted{else}box_normal{/if}">
	        <table width="100%"  border="0" cellspacing="3" cellpadding="0"  onClick="sel(event, 1);">
	          <tr>
	            <td colspan="2" valign=top>&nbsp;</td>
	          </tr>
	          <tr>
	            <td height="23" colspan="2" valign=top><span class="titlerightblue">
	
	              <input name="reg_type" type="radio" value="newclient" {if $reg_type == 'newclient'}checked{/if}> 
	              {t}New client{/t} </span></td>
	          </tr>
	          <tr>
	            <td width="9%" valign=top>&nbsp;</td>
	            <td width="91%" valign=top>{t}This is the first time I am purchasing from{/t} {$servicename}</td>
	          </tr>
	          <tr>
	
	            <td valign=top>&nbsp;</td>
	            <td valign=top>&nbsp;</td>
	          </tr>
	      </table></td>
	    </tr>
	    <tr>
	      <td id="sel2" name="sel2" valign=top class="{if $reg_type != 'newclient'}box_highlighted{else}box_normal{/if}">
	        <table width="100%"  border="0" cellspacing="3" cellpadding="0"  onClick="sel(event, 2);">
	          <tr>
	
	            <td colspan="2" valign=top>&nbsp;</td>
	          </tr>
	          <tr>
	            <td height="23" colspan="2" valign=top><span class="titlerightblue">
	              <input name="reg_type" type="radio" value="oldclient" {if $reg_type != 'newclient'}checked{/if}>
	              {t}Existing client{/t} </span> </td>
	          </tr>
	          <tr>
	
	            <td width="9%" valign=top>&nbsp;</td>
	             <td width="91%" valign=top>{t}I am existing {/t}{$servicename} {t}customer{/t}<br>
	                <br>
	                <table width="100%"  border="0" id="login_dialog" cellspacing="5" cellpadding="5">
	                  <tr>
	                    <td width="15%">{t}Login:{/t}</td>
	                    <td width="85%"><input name="login" class="text" type="text" id="login" value="{$login}"></td>
	                  </tr>
	
	                  <tr>
	                    <td>{t}Password:{/t}</td>
	                    <td><input name="password" class="text" type="password" id="password" value=""> (<a id="recover_link" onClick="ShowRecoverPasswordDialog();" href="javascript:void(0);">{t}Forgot?{/t}</a>)</td>
	                  </tr>
					</table>
					<table width="100%"  border="0" id="recover_password_dialog" style="display:none;" cellspacing="5" cellpadding="5">
	                  <tr>
						<td colspan="2">{t}Password recovery:{/t}</td>
	                  </tr>
	                  <tr>
	                    <td width="15%">{t}Email:{/t}</td>
	                    <td width="85%"><input name="tmp" class="text" type="text" id="recover_email" value=""></td>
	                  </tr>
	
	                  <tr>
	                    <td colspan="2">
	                    	<div style="float:left;"><input id="rsbmt2" type="image" src="images/wiz_btn_cancel.gif" onclick="return HideRecoverDialog()" name="sbmt2"></div>
	                    	<div style="float:left;"><input id="rsbmt1" type="image" src="images/wiz_btn_recover.gif" onclick="return RecoverPassword()" style='vertical-align:middle;' name="sbmt1"> <span style='display:none;' id='loading_' style='vertical-align:middle;'><img style='vertical-align:middle;' src='/images/load.gif'> Please wait...</span></div>
							<div style="clear:both;"></div>
	                    </td>
	                  </tr>
					</table>
				</td>
	          </tr>
	          <tr>
	            <td valign=top>&nbsp;</td>
	
	            <td valign=top>&nbsp;</td>
	          </tr>
	      </table></td>
	    </tr>
	    <tr id="btn_tr">
	      <td height=8 class="titlerightblue"><img src="/images/dot.gif" width=1 height=1 border=0><span class=btnbox></span><br>
	          <span class="btnbox">
	          <div style="display:{if $operaton == 'Register'}none{/if};width:176px;" align="left" id="next_btn">
					<br>
					<div style="float:right;"><input id="sbmt1" type="image" src="images/wiz_btn_next.gif" onclick="SubmitForm('next')" name="sbmt1" value="{t}Next >>{/t}"></div>
					<div style="float:right;"><input id="sbmt2" type="image" src="images/wiz_btn_prev.gif" onclick="SubmitForm('back')" name="sbmt2" value="{t}<< Back{/t}"></div>
				</div>
	        </span></td>
	    </tr>
	  </table>
	</div>
</div>
</form>
</div>
{include file="inc/footer.tpl"}
