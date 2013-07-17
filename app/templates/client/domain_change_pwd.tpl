{include file="client/inc/header.tpl"}
	{include file="client/inc/table_header.tpl"}
		
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Domain auth code"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    		<tr>
    			<td>
    			     New auth code:
    			</td>
    			<td>
    			     <input type="text" class="text" name="authCode" value="">
    			     
<script type="text/javascript">
{literal}
function generateAuthCode (input) {
	input.value = generatePwd(10);
}

function generatePwd (length) {
	var vowels = 'aeuyAEUY';
	var consonants = 'bdghjmnpqrstvzBDGHJLMNPQRSTVWXZ';
	var numbers = '23456789';
	var others = '@#$%';
	
	var password = '';
		
	var numberIndex = Math.ceil(Math.random()*(length-1));
	var otherIndex = Math.ceil(Math.random()*(length-1));
    var alt = Boolean((new Date()).getTime() % 2);

    for (i = 0; i < length; i++) {
    	if (i == numberIndex) {
    		password += numbers[Math.ceil(Math.random()*(numbers.length-1))];
    	} else if (i == otherIndex) {
    		password += others[Math.ceil(Math.random()*(others.length-1))];
    	} else if (alt) {
    		password += consonants[Math.ceil(Math.random()*(consonants.length-1))];
    		alt = false;
    	} else {
    		password += vowels[Math.ceil(Math.random()*(vowels.length-1))];
    		alt = true;
    	}
    }
    
    return password;

}


{/literal}
</script>
    			     <input type="button" class="btn" value="{t}Generate{/t}" onclick="window.generateAuthCode(this.form.authCode);">
    			</td>
    		</tr>
	    {include file="client/inc/intable_footer.tpl" color="Gray"}
	{include file="client/inc/table_footer.tpl" edit_page=1}
{include file="client/inc/footer.tpl"}