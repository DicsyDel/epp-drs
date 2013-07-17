{include file="admin/inc/header.tpl"}
	{include file="admin/inc/table_header.tpl"}
		<div style="padding:5px;">
		{if $invoice_details.notes != ""}
		  {$invoice_details.notes|nl2br}
		{else}
		  No details available on selected invoice.
		{/if}
		</div>
	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}