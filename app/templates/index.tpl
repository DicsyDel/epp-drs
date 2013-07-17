{include file="inc/header.tpl"}
<script language="Javascript" src="/js/class.Cart.js"></script>
<script language="Javascript" src="/js/class.NewPopup.js"></script>
<link href="/css/popup.css" media="screen" rel="stylesheet" type="text/css" />
<script language="javascript">	

	var NoCloseButton = false;
	var DomainCart = new Cart();	
	
	var register_tab_click = DomainCart.ShowRegisterStep.bindAsEventListener(DomainCart);
	var transfer_tab_click = DomainCart.ShowTransferStep.bindAsEventListener(DomainCart);
	
	var JS_SESSIONID = "{$JS_SESSIONID}";
		
	var ERR_NO_WHOIS_INFO = "{t}Cannot retrieve whois information for this domain now.{/t}";
	var ERR_CHKLIST_EXISTS = "{t}Domain name #domain# already exists in check list.{/t}";
	var ERR_DMN_LENGTH = "{t}Please enter a valid domain name composed of two or more characters.{/t}";
	var ERR_DMN_BAD_CHR = "{t}Domain name contains non-supported characters.{/t}";
		
	{literal}
	Event.observe(window, 'load', function()
	{
		Event.observe($('checkButton'), 'click', DomainCart.CheckDomain.bind(DomainCart));
		
		Event.observe($('tab_register'), 'click', register_tab_click);
		Event.observe($('tab_transfer'), 'click', transfer_tab_click);
		
		Event.observe($('domainname'), 'keydown', function(event){ if (event.keyCode == 13){ DomainCart.CheckDomain.bind(DomainCart) } });
		//Event.observe($('TLDs'), 'keydown', function(event){ if (event.keyCode == 13){ DomainCart.CheckDomain.bind(DomainCart) } });
		
		Event.observe($('domainname'), 'change', DomainCart.SetAvailTLDs.bind(DomainCart));
		Event.observe($('domainname'), 'keyup', DomainCart.SetAvailTLDs.bind(DomainCart));
		Event.observe($('domainname'), 'keydown', DomainCart.SetAvailTLDs.bind(DomainCart));
		Event.observe($('domainname'), 'mouseup', DomainCart.SetAvailTLDs.bind(DomainCart));
		Event.observe($('domainname'), 'mousedown', DomainCart.SetAvailTLDs.bind(DomainCart));
		
		var images = new Array('/images/wiz_tab_inactive_l.gif',
								'/images/wiz_tab_inactive_m.gif',
								'/images/wiz_tab_inactive_r.gif',
								'/images/wiz_tab_active_l.gif',
								'/images/wiz_tab_active_m.gif',
								'/images/wiz_tab_active_r.gif', 
								'/images/popup/close.png', 
								'/images/wiz-wiz_btn_next_dis.gif', 
								'/images/popup/tl.png', 
								'/images/popup/tr.png', 
								'/images/popup/bl.png', 
								'/images/popup/br.png', 
								'/images/popup/t.png', 
								'/images/popup/b.png', 
								'/images/popup/l.png', 
								'/images/popup/r.png', 
								'/images/popup/tail.png',
								'/images/fail.gif',
								'/images/wait.gif'
							   );
		
		for (var i = 0; i < images.length; i++)
		{
			var img = new Image();
			img.src = images[i];
		}
		
	});
			
	function callInProgress (xmlhttp) 
	{
		switch (xmlhttp.readyState) 
		{
			case 1: case 2: case 3:
				return true;
			break;
			// Case 4 and 0
			default:
				return false;
			break;
		}
	}
	
	function showFailureMessage() 
	{
		//
	}
	
	Ajax.activeConnections = new Array();
	
	// Register global responders that will occur on all AJAX requests
	Ajax.Responders.register({
	onCreate: function(request) 
	{
		DomainCart.OnDomainCheckEnd();
		
		request.uniqId = parseInt(Math.random()*10000000);
		
		Ajax.activeConnections[request.uniqId] = request.transport;
		
		request['timeoutId'] = window.setTimeout(
		function() 
		{
		// If we have hit the timeout and the AJAX request is active, abort it and let the user know
			if (callInProgress(request.transport)) 
			{
				request.transport.abort();
				showFailureMessage();
				// Run the onFailure method if we set one up when creating the AJAX object
				if (request.options['onFailure']) 
				{
					request.options['onFailure'](request.transport, request.json, request.options);
				}
			}
		},
		DomainCart.RequestTimeOut*1000
		);
	},
	onComplete: function(request) 
	{		
		DomainCart.OnDomainCheckEnd();
		DomainCart.RunQueue();
		delete(Ajax.activeConnections[request.uniqId]);
		// Clear the timeout, the request completed ok
		window.clearTimeout(request['timeoutId']);
	}
	});	
	
	function ClearRequests()
	{
		for (var k in Ajax.activeConnections)
		{
			if (Ajax.activeConnections[k].readyState)
				Ajax.activeConnections[k].abort();
		}
		
		$('sbmt1').src = '/images/wiz-wiz_btn_next_dis.gif';
		$('sbmt1').disabled = true;
		
		$('direction').value = 'Next';
		$('frm1').submit();
		
		return false;
	}
</script>
{/literal}
<div align="center" style="height:auto;">

	<div id="whois_container_tpl" align="center" style="display:none;">
		<div id="whois_l" style="display:none;" align="center">
			<img src="images/load.gif" style="vertical-align:middle;" />&nbsp;{t}Retrieving whois information{/t}&hellip;
		</div>
		<div id="whois_c" align="left" style="display:none;padding:15 15 0 15;"></div>
	</div>
		
	<!-- <Tooltips> -->
	<div id="tooltip_avail_register" style="display:none;" class="ok_hint">{t}This domain is available for registration{/t}</div>
	<div id="tooltip_no_avail_register" style="display:none;" class="error_hint">{t}This domain is taken{/t}</div>
	<div id="tooltip_chk_error_register" style="display:none;" class="error_hint">{t}Cannot check domain availability. Make sure that you spelled domain name correctly{/t}</div>
	
	<div id="tooltip_avail_transfer" style="display:none;" class="ok_hint">{t}This domain is available for transfer{/t}</div>
	<div id="tooltip_no_avail_transfer" style="display:none;" class="error_hint">{t}This domain is not available for transfer{/t}</div>
	<div id="tooltip_chk_error_transfer" style="display:none;" class="error_hint">{t}Cannot check transfer availability. Make sure that you spelled domain name correctly{/t}</div>
	<!-- </ToolTips> -->
	
	<img src="images/avail.gif" style="display:none;">
	<img src="images/unavail.gif" style="display:none;">
	
	<div  style="width:750px;padding-left:10px;">
		<div id="tabs_container" align="left">
			<div id="tab_register" class="tab tab_active"><span>{t}Register domains{/t}</span></div>
			<div style="float:left;width:5px;font-size:1px;">&nbsp;</div>
			<div id="tab_transfer" style="float:left;cursor:pointer;">
				<div style="float:left;width:11px;"><img id="t_tab_l" src="images/wiz_tab_inactive_l.gif"></div>
				<div id="t_tab_m" style="color:white;float:left;line-height:27px;height:27px;background-image: url('images/wiz_tab_inactive_m.gif');background-repeat: repeat-x;">{t}Transfer domains to {/t}{$servicename}</div>
				<div style="float:left;width:11px;"><img id="t_tab_r" src="images/wiz_tab_inactive_r.gif"></div>
			</div>
			<div style="clear:both;"></div>
		</div>
	</div>	
		
	<div style="width:750px; height:500px;border-top:3px solid #6D9632; background-image: url('/images/wiz-main-grad.jpg'); background-repeat: repeat-x;">
		<div style="margin:20px;">
			<div id="main_canvas" align="left" style="padding:0px; margin:0px;">
				<form name="frm1" action="" id="frm1" method="POST" style="padding:0px; margin:0px;">
				<input type="hidden" name="step" value="cart_confirm" />
				<input type="hidden" name="operation" id="operation" value="Register" />
				<input type="hidden" name="direction" id="direction" value="" />
				<div style="width:280px;float:left;">
					<div align="left" style="float:left;padding-left:0px;margin:0px;font-size:1px;">
						<span>
							<input type="text" name="domainname" id="domainname" value="" class="text" style="width:180px;">
						</span>
					</div>					
					<div style="float:right;width:95px;">
						<span style="float:left;padding:0px;margin:0px;"><input id="checkButton" style="cursor:pointer;margin:0px;padding:0px;" onclick="return false;" type="image" src="/images/wiz_btn_check.gif"></span>
					</div>
					
					<div style="width:280;float:left;margin-top:20px;" id="TLDs_container">
					{section name=id loop=$TLDs}
						<div style="width:92px;float:left;" id="TLD_{$TLDs[id].name}_{$TLDs[id].allowtransfer}">
							<input type="checkbox" name="checkTLDs[]" {if $TLD == $TLDs[id].name}checked{/if} value="{$TLDs[id].name}"> {$TLDs[id].name}
						</div>
					{/section}
					</div>
										
					<div style="clear:both;height:10px;font-size:1px;"></div>
				</div>
				<div id="text_container" style="float:right;width:420px;border-left:1px dotted #CCCCCC;padding-bottom:5px;display:{if $data.transferdomains|@count != 0 || $data.domains|@count !=0 }none{else}{/if};">
					<div style="margin-left:20px;">
						{include file="inc/index_welcome.tpl"}
					</div>
				</div>
				<div id="top_container" style="float:right;width:420px;border-left:1px dotted #CCCCCC;padding-bottom:5px;display:{if $data.transferdomains|@count != 0 || $data.domains|@count !=0 }{else}none{/if};">
					<div style="margin-left:20px;">
						<div class="title" style="margin-bottom:10px;">Selected domains</div>
						<div style="width:360px;float:none;font-size:1px;height:auto;display:{if !$data || $data.transferdomains|@count ==0}none{/if};" id="canvas_transfer_results">
					
						</div>
						<div style="width:360px;float:none;font-size:1px;height:auto;display:{if !$data || $data.domains|@count ==0}none{/if};" id="canvas_register_results">
							
						</div>
					</div>
				</div>
				<div style="width:100%;clear:both;">
					<div style="display:none;float:right;" id="next_btn">
						<br>
						<input id="sbmt1" type="image" src="images/wiz_btn_next.gif" onclick="return ClearRequests()" name="sbmt1">
					</div>
				</div>
				</form>
			</div>
			
			<script language="Javascript">
			{if $smarty.session.wizard.whois.operation == 'Transfer'}DomainCart.ShowTransferStep();{/if}
			
			{section name=id loop=$data.domains}
					DomainCart.AddDomainRowToContainer('{$data.domains[id]}', '{$data.TLDs[id]}');
					{literal}
					DomainCart.onCheckDomainComplete(
					{
						'status':true, 
						'data':
							{
								{/literal}
								'domain':'{$data.domains[id]}', 
								'TLD':'{$data.TLDs[id]}', 
								'res':'{if $data.avail[id] == 1}AVAIL{else}NOT_AVAIL_CANNOT_CHECK{/if}'
								{literal}
							}
					}, true);
					
					{/literal}
			{/section}
			</script>
			<script language="Javascript">
				DomainCart.CheckDomainsNum();		
			</script>
		</div>
	</div>
</div>
<div style="clear:both;"></div>
{include file="inc/footer.tpl"}