{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		<div style="padding:5px;">
		{if $whois != ""}
		  {$whois}
		{else}
		{t}No WHOIS information found for selected domain name.{/t}
		{/if}
		</div>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}