{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
    <div style="width:95%" align="center">
        <div style="padding:10px;" align="center">
        {t escape=no}New invoice has been created for this purchase and available  <a href="inv_view.php">here</a>.{/t}<br />
        <br />
        {t}Please pay the invoice to proceed further.{/t}<br />
        <br />
        </div>
    </div>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}