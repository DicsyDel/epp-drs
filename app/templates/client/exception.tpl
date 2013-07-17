{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
	
    
    <div>
        <div style="float:left;padding:10px;"><img src="images/exception1.gif" /></div>	
        
        <div style="float:left;">
          <div style="font-size:24px;padding:10px 10px 0px 10px; color:red;">Unrecoverable error</div>	
            <div style="padding:5px 10px 10px 10px;"><div>{$message}</div>
            <div style="padding-top:25px;"><span style="text-decoration:underline;">Call stack</span></div>
            <div>{$backtrace}{$post_serialized}</div>
      </div>
    </div>
{if $post_serialized != ''}
    	{include file="client/inc/table_footer.tpl" button2=1 button2_name='Retry' backbtn=1}
    {else}
		{include file="client/inc/table_footer.tpl" retry_btn=1 backbtn=1}
	{/if}
{include file="client/inc/footer.tpl"}