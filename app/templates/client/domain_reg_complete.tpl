{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
    <div style="width:95%" align="center">
        <div style="padding:10px;" align="center">{t}Your domain registration is currently awaiting payment.{/t} <br />
        {t escape=no}Invoice has been created for this purchase and available  <a href="inv_view.php">here</a>.{/t}<br />
        <br />
        {t}Domain will be registered immediately after we receive a confirmation that invoice has been paid.{/t}<br />
        <br />
        </div>
    </div>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}