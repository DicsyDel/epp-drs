<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title>Registrar Control Panel{if $title && $title != 'Control Panel'}: {$title|strip_tags}{/if}</title>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="none" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

	{if $load_extjs}
	{if !$load_extjs_nocss}
	<link type="text/css" rel="stylesheet" href="/css/ext-all.css" />
	{/if}
	<link type="text/css" rel="stylesheet" href="/css/ext-ux.css" />
	{/if}	

	<link href="css/main.css" rel="stylesheet" type="text/css" />
	<link href="css/style.css" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">
		var load_calendar = {$load_calendar|default:"0"};
		var load_treemenu = {$load_treemenu|default:"0"};
		var get_url = '{$get_url}';
	</script>
	<script type="text/javascript" src="/js/prototype.js"></script>
	<script type="text/javascript" src="/js/class.Tweaker.js"></script>
	<script type="text/javascript" src="/js/class.LibWebta.js"></script>
	<script type="text/javascript" src="/js/common.inc.js"></script>
	<script type="text/javascript" src="/js/phone.js"></script>	
	<script type="text/javascript" src="/js/src/scriptaculous.js?effects"></script>
	
	{if $load_extjs}
	<script type="text/javascript" src="/js/extjs/ext-prototype-adapter.js"></script>
	<script type="text/javascript" src="/js/extjs/ext-all.js"></script>
	<script type="text/javascript">Ext.BLANK_IMAGE_URL = "/images/s.gif";</script>	
	<script type="text/javascript" src="/js/extjs/ext-ux.js"></script>
	<script type="text/javascript" src="/js/extjs/ext-ux-lang.js.php"></script>
	{/if}
	
	
	<script language="Javascript" type="text/javascript">
	{literal}
	 var allchecked = false;
     function checkall(nm, frm)
     {
     	if (!frm)
     		var frm = $("frm");
     		
    	for (var i=0;i<frm.elements.length;i++)
    	{
    		var e = frm.elements[i]
    		if ((e.name == "delete[]") && (e.type=='checkbox') && !e.disabled) {
    			e.checked = !allchecked;
    		}
    	}
    	allchecked = !allchecked;
     }
 {/literal}
	</script>
</head>

<body onload="webtacp.afterload()" onresize="webtacp.setupTweaker()">
<table border="0" cellpadding="0" cellspacing="0" class="Webta_Table" width="100%">
<tr>
	<td width="7"><div class="TableHeaderLeft"></div></td>
	<td><div class="TableHeaderCenter"></div></td>
	<td width="7"><div class="TableHeaderRight"></div></td>
</tr>
<tr>
	<td width="7" class="TableHeaderCenter"></td>
	<td><table border="0" width="100%" cellpadding="0" cellspacing="0" class="Header">
		<tr>
			<td width="90">
				<a href="http://epp-drs.com" target="_blank"><img style="margin-left:3px; margin-top:2px;" src="images/logo_header.gif" hspace="0" vspace="4" align="absmiddle"></a>
			</td>
			<td width="200" nowrap valign="middle">
				<form action="index.php" method="post" name="serach_form" onsubmit="webtacp.search(); return false;">
				<div style="float:left; vertical-align:middle;"><input name="search" id="search_string" type="text" class="text_smaller" size="11" /><img id="search_image" style="margin-left: -18px; vertical-align:middle; display:none;" src="images/loading.gif"></div>
				<input id="search_button" type="submit"  value="Search" class="btn" style="margin-top:2px; margin-left: 3px;" />
				</form>
			</td>
			<td style="overflow: hidden;">
				{$dmenu}
			</td>
			<td align="right"><input type="button" value="Logout" onClick="document.location='login.php?logout=1'" class="btn" /></td>
		</tr>
		</table></td>
	<td width="7" class="TableHeaderCenter"></td>
</tr>
<tr>
	<td width="7"><div class="TableFooterLeft"></div></td>
	<td><div class="TableFooterCenter"></div></td>
	<td width="7"><div class="TableFooterRight"></div></td>
</tr>
</table>
	

<br>
<table width="100%" height="100%" cellpadding="5" cellspacing="0">

  <tr>
	<td width="76%" valign="top"><table width="100%" cellspacing="0" cellpadding="0">
	  <tr>
		<td height="17" class="mg" id="title_td">{$title}</td>
	  </tr>
	  <tr>
        <td>
        	{if $license_err}
        	<div class="Webta_ErrMsg">{$license_err}</div>
        	{/if}
        	{if $license_info}
        	<div class="Webta_InfoMsg">{$license_info}</div>
        	{/if}
        
			{if $mess != ''}
				<div class="Webta_Message">{$mess}</div>
			{elseif $errmsg != ''}
				<div id="Webta_ErrMsg" class="Webta_ErrMsg">
				    {$errmsg}
				    {if $err}
				    <table style="margin-top:0px;" width="100%" cellpadding="5" cellspacing="1" bgcolor="">
    					<tr>
    						<td bgcolor="">
    							<span style="color: #CB3216">
    							{foreach from=$err key=id item=field}
    								&bull;&nbsp;&nbsp;{$field}<br>
    							{/foreach}
    							</span>
    						</td>
    					</tr>
          			</table>
          			{/if}
				</div>
				{literal}
				<script language="Javascript" type="text/javascript">
					Event.observe(window, 'load', function(){new Effect.Pulsate($('Webta_ErrMsg'));}); 
				</script>
				{/literal}
			{elseif $okmsg != ''}
				<div class="Webta_OkMsg">{$okmsg}</div>
			{/if}
        	
			{if $warn}
				<div class="Webta_WarnMsg">{$warn}</div>
			{/if} 
			  
			{if !$noheader}
				<form name="frm" id="frm" action="{$form_action}" method="post" {if $upload_files}enctype="multipart/form-data"{/if} {if $onsubmit}onsubmit="{$onsubmit}"{/if}>
			{/if}
			<a name="top"></a>
			{if $help && $smarty.const.CF_INLINE_HELP == 1}
				{literal}
				<script language="Javascript">
				
				function CloseHelp()
				{
					new Effect.Fade($('Webta_InfoMsg'));
					
					var params = 'task=disablehelp';
				
					var url = '/admin/server/misc.php?'+params
			    	
					new Ajax.Request(url, 
			    	{   
			    		method: 'get',   
			    		onSuccess: function(){ alert("Inline help is now disabled for all pages. You can enable it back in Settings."); },
			    		onFailure: function(){}
			    	});
				}
				
				</script>
				{/literal}
			    <div class="Webta_InfoMsg" id="Webta_InfoMsg">
			    	<div>
				    	<div style="float:left; margin-right: 20px; width: 97%">{$help}</div>
				    	<div style="float:right;"><a href="javascript:CloseHelp();"><img alt="Hide inline help" src="images/help_close.gif"></img></a></div>
				    	<div style="clear:both;font-size:1px;height:1px;"></div>
			    	</div>
			    </div>
			{/if}
