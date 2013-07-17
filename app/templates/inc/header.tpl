<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<title>{t}Domain Registration Service by {$servicename}{/t}</title>
<link href="/css/style.css" media="screen" rel="stylesheet" type="text/css" />
<script language="javascript" type="text/javascript" src="/js/prototype.js"></script>
<script src="/js/moo/moo.fx.js" type="text/javascript"></script>
<script src="/js/moo/moo.fx.pack.js" type="text/javascript"></script>
<script src="/js/tooltip-v0.1.js" type="text/javascript"></script>
<script language="Javascript" src="/js/common.js"></script>
<script language="Javascript" src="/js/phone.js"></script>
</head>
<body>
<div align="right">
    {if $languages_num > 1}
       {section name=id loop=$languages}
	       <a href="?lang={$languages[id].name}" alt="{$languages[id].language}" title="{$languages[id].language}"><img src="/images/lang/{$languages[id].name}.gif" border="0"></a>
	   {/section}
	{/if}
</div>
<div align="center">
{include file="inc/errors.tpl"}