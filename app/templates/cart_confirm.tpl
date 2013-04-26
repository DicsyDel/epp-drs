{include file="inc/header.tpl"}
{literal}
<script language="Javascript">
	var prices = new Array();

	Event.observe(window, 'load', function()
	{
		{/literal}
		{if $operation == 'Register'}Calculate();{/if}
		{literal}
	});
	
	function Calculate()
	{
		var total = 0;
		var elements = $('frm1').getInputs('checkbox');
		var checked_elements = 0;
		for (var i =0; i < elements.length; i++)
		{
			var elem = elements[i];
			if (elem.name == 'register[]')
			{
				if (elem.checked == true)
				{
					var periodInp = document.getElementsByName('period['+elem.value+']');
					var period = periodInp[0].value;					
					
					if (parseFloat(prices[elem.value][period]).toString() != "NaN")
						total = total+parseFloat(prices[elem.value][period]);
						
					checked_elements++;
				}
			}
		}
		
		if (total.toString() != 'NaN')
			$('total_cost').innerHTML = parseFloat(total).toFixed(2);
		
		if (checked_elements == 0)
			$('next_btn').style.display = "none";
		else
			$('next_btn').style.display = "";
	}
	
</script>
{/literal}
<div align="center">
	<form name="frm1" action="" id="frm1" method="POST">
		<input type="hidden" name="step" value="check_user">
		<input type="hidden" name="direction" id="direction" value="" />
		{if $backstep}<input type="hidden" name="backstep" value="{$backstep}">{/if}
		<div style="width:600px; height:500px;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
			<div style="margin:20px;" align="left">
			
				<div align="left" style="margin-bottom: 30px;">
					<div class="title">{t}Sum of your order{/t}</div>
					<div>{t}Please revise contents of your cart. You can remove items by unchecking them.{/t}</div>
				</div>
				{section name=id loop=$domains}
				{assign var=dname value=$domains[id].dname}
				<div style="width:560px;">
					<div style="float:left;width:30px;" align="left"><input {if $domains[id].checked}checked{/if} onclick="Calculate();" type="checkbox" name="register[]" value="{$domains[id].name}.{$domains[id].TLD}"></div>
					<div style="float:left;width:180px;" align="left">{$domains[id].name}.{$domains[id].TLD}</div>
					<div style="float:right;">
						{if $operation == 'Register'}
						<select name="period[{$domains[id].name}.{$domains[id].TLD}]" class="selectbox" onchange="Calculate();">
							{section name=pid loop=$domains[id].periods}
								<option {if $domains[id].periods[pid].period == $data.period[$dname]}selected{/if} value="{$domains[id].periods[pid].period}">{$domains[id].periods[pid].period} year{if $domains[id].periods[pid] != 1}s{/if} ({$Currency} {$domains[id].periods[pid].price|string_format:"%.2f"})</option>
							{/section}
						</select>
						{else}
						{$Currency} {$domains[id].price|string_format:"%.2f"}
						{/if}
					</div>
					<div style="clear:both; font-size:1px; height:1px;"></div>
				</div>
				<script language="javascript">
						prices['{$domains[id].name}.{$domains[id].TLD}'] = new Array();
					{section name=pid loop=$domains[id].periods}
						prices['{$domains[id].name}.{$domains[id].TLD}'][{$domains[id].periods[pid].period}] = parseFloat('{$domains[id].periods[pid].price}');
					{/section}
				</script>
				{/section}
				<div style="border-bottom:1px dotted #666666;margin-top:10px;margin-bottom:4px;"></div>
				<div style="float:right;">
				{t}Total:{/t} {$Currency} <span id="total_cost">{$total|string_format:"%.2f"}</span>
				</div>
				<div style="display:{if $operaton == 'Register'}none{/if};width:176px;" align="left" id="next_btn">
					<br>
					<div style="float:right;"><input id="sbmt1" type="image" src="images/wiz_btn_next.gif" onclick="SubmitForm('next')" name="sbmt1" value="{t}Next >>{/t}"></div>
					<div style="float:right;"><input id="sbmt2" type="image" src="images/wiz_btn_prev.gif" onclick="SubmitForm('back')" name="sbmt2" value="{t}<< Back{/t}"></div>
				</div>
			</div>
		</div>
	</form>
</div>

{include file="inc/footer.tpl"}
