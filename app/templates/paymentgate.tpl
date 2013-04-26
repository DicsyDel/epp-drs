{include file="inc/header.tpl"}

<style>
{literal}
#paymentform td {
	min-width:150px !important;
}
{/literal}
</style>

<div align="center">
	<div style="width:600px; height:90%;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x; overflow:visible;">
	<div style="margin:20px;" align="left">
		<br/><br/>
			<form action="" method="POST" name="frm1" id="frm1">
			<div style="margin:20px;">
			<input type="hidden" name="step" value="process_payment">
			<input type="hidden" name="backstep" value="placeorder">
			<input type="hidden" name="direction" id="direction" value="" />
			
			<table id="paymentform" width="600" align="center"  border="0" cellspacing="3" cellpadding="0">
			{include file="inc/dynamicform.tpl"}
			</table>
			
			<div style="clear:both;margin-top:20px"></div>
			<span class="btnbox">
				<div style="width:176px;" align="left" id="next_btn">
					<br>
					<div style="float:right;"><input id="sbmt1" type="image" src="images/wiz_btn_co.gif" onclick="SubmitForm('next')" name="sbmt1" value="{t}Process{/t}"></div>
					<div style="float:right;"><input id="sbmt2" type="image" src="images/wiz_btn_prev.gif" onclick="SubmitForm('back')" name="sbmt2" value="{t}&lt;&lt; Back{/t}"></div>
				</div>
			</span>
			
			
			</form>
	</div>	
</div>
</div>
{include file="inc/footer.tpl"}
