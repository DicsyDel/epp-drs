{include file="inc/header.tpl"}
<div style="width:600px; height:500px;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
	<br/>
	<span class="title_shine">{t}Your payment for the order failed.{/t}</span>
		<div style="padding: 30px; text-align: left;">{t}The reason of failure is:{/t} {$reason}
			<br>
			{t}You can pay the corresponding invoice(s) via your{/t} <a href="/client">{t}Registrant Control Panel{/t}</a>
		</div>
</div>
{include file="inc/footer.tpl"}