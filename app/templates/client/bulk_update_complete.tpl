
{include file="client/inc/header.tpl"}
    {include file="client/inc/table_header.tpl"}
    <div style="width:95%" align="center">
        <div style="padding:10px;" align="center">{t}Operations have been queued. They will be processed with the next cronjob run.{/t} <br />
        </div>
    </div>
{include file="client/inc/table_footer.tpl" disable_footer_line=1}
{include file="client/inc/footer.tpl"}