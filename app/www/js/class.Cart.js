	
	var Cart = Class.create(
	{   
		domain_template : '<div style="width:390px;height:24px;">'+
						  '<div style="float:left;margin-top:3px;"><img src="/images/wait.gif" style="vertical-align:middle;margin-right:10px;margin-left:5px;" id="avail_image_#id#"> <span style="vertical-align:middle;">#dname_optimized#.#TLD#</span></div>'+
						  '<div style="float:right;margin-top:3px;">'+
							 '<a id="remove_link_#id#" style="margin:2px;display:none;vertical-align:middle;" href="javascript:void(0);"><img border="0" style="vertical-align:middle;margin:2px 3px 0px 0px" align="middle" src="/images/trash.gif"><span style="vertical-align:middle;">Remove</span></a>'+
							 '<a id="whois_link_#id#" style="margin:2px;margin-left:5px;display:none;vertical-align:middle;" href="javascript:void(0);"><img border="0" style="vertical-align:middle;margin:2px 3px 0px 5px;" src="/images/whois.gif"><span style="vertical-align:middle;">Whois</span></a>'+
						  '</div><div slyle="clear:both;font-size:1px;height:1px; border-bottom: 1px dotted;"></div>'+
						  '<input type="hidden" id="domain_name_res_#id#" name="domains[]" value="#dname#" style="padding:0px;margin:0px;">'+
						  '<input type="hidden" id="domain_TLD_res_#id#" name="TLDs[]" value="#TLD#" style="padding:0px;margin:0px;">'+
						  '<input type="hidden" id="domain_avail_res_#id#" name="avail[]" value="" style="padding:0px;margin:0px;">'+
						  '<div style="clear:both;font-size:1px;height:1px;"></div></div>',
		domainHashes: {"register":[],"transfer":[]},
		checkResults: {"register":[],"transfer":[]},
		okDomains: {"register":0,"transfer":0},
		totalDomains: {"register":0,"transfer":0},
		RequestTimeOut:20,
		isTransfer:false,
		WhoisPopup:false,
		DomainTableContainerId:'canvas_register_results',
		AjaxQueue:new Array(),
		
		initialize: function() 
		{
			
		},
		
		RunQueue:function()
		{
			if (Ajax.activeRequestCount < 2)
			{
				for (var key in this.AjaxQueue)
				{
					if (this.AjaxQueue[key] != false && typeof(this.AjaxQueue[key]) != 'function')
					{
						var QueueObj = this.AjaxQueue[key];
						this.AjaxQueue[key] = false;
						break;
					}
				}
				
				if (!this.isTransfer)
			    {
			    	var id = this.domainHashes.register[QueueObj.domainname+"."+QueueObj.tld];
			    }
			    else
			    {
			    	var id = this.domainHashes.transfer[QueueObj.domainname+"."+QueueObj.tld];
			    }
				
				if (id)
				{
					$('avail_image_'+id).src = "/images/load.gif";
					
					new Ajax.Request(QueueObj.url, 
			    	{   
			    		method: 'get',   
			    		onSuccess: this.onCheckDomainComplete.bind(this),
			    		onFailure: this.onCheckDomainFailure.bind(this),
			    		domainName: QueueObj.domainname,
			    		TLD: QueueObj.tld
			    	});
			    }
		    }
		},
		
		AddToQueue:function(url, domainname, tld)
		{
			this.AjaxQueue[domainname+tld] = {'url':url, 'domainname':domainname, 'tld':tld};
		},
		
		SetAvailTLDs:function()
		{
			var domainname = $('domainname').value.toLowerCase();
			
			var tlds = document.getElementsByName('checkTLDs[]');
			
			var hashes = (!this.isTransfer) ? this.domainHashes.register : this.domainHashes.transfer;
			
			for (index = 0; index < tlds.length; index++)
			{
				var TLD	= tlds[index].value;
					    	
		    	if (hashes[domainname+"."+TLD])
		    	{
		    		tlds[index].disabled = true;
		    		tlds[index].checked = true;
		    	}
		    	else
		    	{
		    		tlds[index].disabled = false;
		    	}
			}
				
			return true;
		},
		
		SetDomainDomainRowAvail: function(id, disabled)
		{
			$('domain_name_res_'+id).disabled = disabled;
			$('domain_TLD_res_'+id).disabled = disabled;
			$('domain_avail_res_'+id).disabled = disabled;
		},
		
		onWhoisComplete: function(response)
		{
			eval("response = "+response.responseText+";");
	    	
	    	if (response.status == true)
	    	{
				$('whois_content').innerHTML = response.data;
	    		$('whois_loader').style.display = "none";
				$('whois_content').style.display = "";
				$('whois_content').parentNode.scrollTop = 0+"px";
				$('whois_content').parentNode.parentNode.scrollTop = 0+"px";
	    	}
	    	else
	    	{
	    		$('whois_content').innerHTML = "<div align='center'>"+ERR_NO_WHOIS_INFO+"</div><br><div align='center'><input type='button' name='btn' onClick='$(\"popup_close_img\").onclick(event);' value='Close'></div>";
	    		$('whois_loader').style.display = "none";
				$('whois_content').style.display = "";
				$('whois_content').parentNode.scrollTop = 0+"px";
				$('whois_content').parentNode.parentNode.scrollTop = 0+"px";
	    		
				//this.WhoisPopup.hide(this.WhoisPopup.options.popup);
	    		//alert(ERR_NO_WHOIS_INFO);
	    	}
		},
		
		onWhoisFailure: function(response, json, options)
		{
			$('whois_content').innerHTML = "<div align='center'>"+ERR_NO_WHOIS_INFO+"</div><br><div align='center'><input type='button' name='btn' onClick='$(\"popup_close_img\").onclick(event);' value='Close'></div>";
    		$('whois_loader').style.display = "none";
			$('whois_content').style.display = "";
			
			//this.WhoisPopup.hide(this.WhoisPopup.options.popup);
			//alert(ERR_NO_WHOIS_INFO);
		},
		
		Whois: function(domainname, TLD, id, event)
		{
			if (!event) var event = window.event;
			
			if (!$('whois_container'))
			{
				tmp = document.createElement("DIV");
				tmp.id = "whois_container";
				
				var content = $('whois_container_tpl').innerHTML.replace("whois_l", "whois_loader");
				content = content.replace("whois_c", "whois_content");			
				
				tmp.innerHTML = content;
				document.body.appendChild(tmp);
			}
			
			if (!this.WhoisPopup)
				this.WhoisPopup = new NewPopup('whois_container', {target: 'whois_link_'+id, width: 490, height: 260, selecters: new Array()});
			else
				this.WhoisPopup.updateTarget('whois_link_'+id);
			
			$('whois_loader').style.display = "";
			$('whois_content').style.display = "none";
				
			this.WhoisPopup.raisePopup();
			
			var params = 'name=' + encodeURIComponent(domainname) + "&TLD="+TLD+"&JS_SESSIONID="+JS_SESSIONID;
				
			var url = '/server/whois.php?'+params
	    	
			new Ajax.Request(url, 
	    	{   
	    		method: 'get',   
	    		onSuccess: this.onWhoisComplete.bind(this),
	    		onFailure: this.onWhoisFailure.bind(this)
	    	});
		},
		
		CheckDomain: function()
	    {
	    	var domainname = $('domainname').value.toLowerCase();
	    	//var TLD = $('TLDs').value;
    		
	    	var hashes = (!this.isTransfer) ? this.domainHashes.register : this.domainHashes.transfer;
	    	
	    	if (domainname.length < 2)
	    	{
	    		alert(ERR_DMN_LENGTH);
	    		return false;
	    	}
	    	
	    	// 
	    	// Add domain validation here!!!
	    	//
	    	/*
	    	var valid_regExp = new RegExp(/^[A-Za-z0-9-\u0386-\u1FFC]+$/gi);
	    	var invalid_regExp = new RegExp(/[\u0387\u038B\u038D\u03A2\u03CF\u1F16\u1F17\u1F1E\u1F1F\u1F46\u1F47\u1F4E\u1F4F\u1F58\u1F5A\u1F5C\u1F5E\u1F7E\u1F7F\u1FBD-\u1FC1\u1FC5\u1FCD-\u1FCF\u1FD4\u1FD5\u1FDC-\u1FDF\u1FED-\u1FF1\u1FF5]+/gi);
	    	
	    	check_result = !valid_regExp.test(domainname) || invalid_regExp.test(domainname) || (domainname.charAt(0) == "-") || (domainname.charAt(domainname.length-1) == "-");
	    	
	    	if (check_result)
	    	{
	    		alert(ERR_DMN_BAD_CHR);
	    		return false;
	    	}
	    	*/
	    	
	    	var tlds = document.getElementsByName('checkTLDs[]');
			for (var index = 0; index < tlds.length; index++)
			{
				if (tlds[index].checked == true)
				{
					if (this.AddDomainRowToContainer(domainname, tlds[index].value))
					{												
						var params = 'name=' + encodeURIComponent(domainname) + "&TLD=" + tlds[index].value + "&isTransfer="+this.isTransfer.toString()+"&JS_SESSIONID="+JS_SESSIONID;
						
						var url = '/server/domain.php?'+params;
						
						this.AddToQueue(url, domainname, tlds[index].value);
						this.RunQueue();
					}
				}
			}
	    },
	    
	    onCheckDomainFailure: function(response, json, options)
	    {
    		id = (!this.isTransfer) ? this.domainHashes.register[options['domainName']+"."+options['TLD']] : this.domainHashes.transfer[options['domainName']+"."+options['TLD']];
	    		
	    	if (!id)
		    	return false;
	    	
	    	$('avail_image_'+id).src = "images/fail.gif";
			
	    	var tooltips_suffix = (!this.isTransfer) ? "register" : "transfer";
	    	
			new Tooltip('avail_image_'+id, 'tooltip_chk_error_'+tooltips_suffix);
			
			$('domain_avail_res_'+id).value = "0";
			
			if (!this.isTransfer)
				this.checkResults.register[$('domain_row_'+id).dHash] = 0;
			else
				this.checkResults.transfer[$('domain_row_'+id).dHash] = 0;
			
			this.OnDomainCheckEnd();
	    },
	    
	    OnDomainCheckError: function(id, tooltips_suffix)
	    {
	    	$('avail_image_'+id).src = "/images/fail.gif";
			
			new Tooltip('avail_image_'+id, 'tooltip_chk_error_'+tooltips_suffix);
			
			$('domain_avail_res_'+id).value = "0";
			
			if (!this.isTransfer)
				this.checkResults.register[$('domain_row_'+id).dHash] = 0;
			else
				this.checkResults.transfer[$('domain_row_'+id).dHash] = 0;
	    },

	    onCheckDomainComplete: function(response, no_eval)
	    {
	    	try
	    	{
	    		if (!no_eval)
	    			eval("response = "+response.responseText+";");
	    	}
	    	catch(err)
	    	{
	    		id = (!this.isTransfer) ? this.domainHashes.register[response.request.options.domainName+"."+response.request.options.TLD] : this.domainHashes.transfer[response.request.options.domainName+"."+response.request.options.TLD];
	    		
	    		var tooltips_suffix = (!this.isTransfer) ? "register" : "transfer";
	    		if (!id)
		    		return false;
		    		
		    	this.OnDomainCheckError(id, tooltips_suffix);
	    	}
	    	
	    	if (response.status == true)
	    	{
	    		id = (!this.isTransfer) ? this.domainHashes.register[response.data.domain+"."+response.data.TLD] : this.domainHashes.transfer[response.data.domain+"."+response.data.TLD];
	    		var tooltips_suffix = (!this.isTransfer) ? "register" : "transfer";
	    		if (!id)
		    		return false;
		    			
	    		try
	    		{ 				
	    			if (response.data.res == 'AVAIL')
		    		{
		    			$('avail_image_'+id).src = "/images/avail.gif";
		    			
		    			new Tooltip('avail_image_'+id, 'tooltip_avail_'+tooltips_suffix);
		    			
		    			if (!this.isTransfer)
		    			{
		    				this.checkResults.register[$('domain_row_'+id).dHash] = 1;
		    				this.okDomains.register++;
		    			}
		    			else
		    			{
		    				this.checkResults.transfer[$('domain_row_'+id).dHash] = 1;
		    				this.okDomains.transfer++;
		    			}
		    			
		    			$('domain_avail_res_'+id).value = "1";
		    			
		    			if (this.isTransfer)
	    				{
	    					$('whois_link_'+id).style.display = '';
	    					Event.observe($('whois_link_'+id), 'click', this.Whois.bind(this, response.data.domain, response.data.TLD, id));
	    				}
		    		}
		    		else
		    		{
		    			if (response.data.res == 'NOT_AVAIL')
		    			{
		    				$('avail_image_'+id).src = "/images/unavail.gif";
		    				
		    				if (!this.isTransfer)
		    				{
		    					$('whois_link_'+id).style.display = '';
		    					Event.observe($('whois_link_'+id), 'click', this.Whois.bind(this, response.data.domain, response.data.TLD, id));
		    				}
		    				
		    				new Tooltip('avail_image_'+id, 'tooltip_no_avail_'+tooltips_suffix);
		    			}
		    			else
		    			{
		    				$('avail_image_'+id).src = "/images/fail.gif";
		    				
		    				new Tooltip('avail_image_'+id, 'tooltip_chk_error_'+tooltips_suffix);
		    			}
		    			
		    			$('domain_avail_res_'+id).value = "0";
		    			
		    			if (!this.isTransfer)
		    				this.checkResults.register[$('domain_row_'+id).dHash] = 0;
		    			else
		    				this.checkResults.transfer[$('domain_row_'+id).dHash] = 0;
		    		}
	    		}
	    		catch(e)
	    		{
	    			this.OnDomainCheckError(id, tooltips_suffix);
	    		}
	    	}
	    	
	    	this.OnDomainCheckEnd();
	    },
	    
	    OnRowDelete: function(id)
	    {
	    	var cont = $('domain_row_'+id);
	    	
	    	var mySlide = new fx.Height('domain_row_'+id);
		    mySlide.toggle(26, 1, function()
		    { 
		    	try
		    	{
			    	$(this.el.id).remove(); 
			    	
			    	var total = (!this.isTransfer) ? DomainCart.totalDomains.register : DomainCart.totalDomains.transfer;
			    	
			    	try
			    	{
			    		if (total == 0)
			    		{
		    				$(this.DomainTableContainerId).style.display = "none";
		    				$('top_container').style.display = "none";
		    				$('text_container').style.display = "";
			    		}
			    	}
			    	catch(e){}
		    	}
		    	catch(e){}
		    });
		    
		    if (!this.isTransfer)
		    {
			    delete(this.domainHashes.register[cont.dHash]);
			    
			    if (this.checkResults.register[cont.dHash] == 1)
			    {
			    	this.okDomains.register--;
				    delete(this.checkResults.register[cont.dHash]);
			    	this.totalDomains.register--;
			    }
		    }
		    else
		    {
		    	delete(this.domainHashes.transfer[cont.dHash]);
			    
			    if (this.checkResults.transfer[cont.dHash] == 1)
			    {
			    	this.okDomains.transfer--;
			   	 	delete(this.checkResults.transfer[cont.dHash]);			    
			    	this.totalDomains.transfer--;
		    	}
		    }
		    
		    this.SetAvailTLDs();
		    
		    this.CheckDomainsNum();
	    },
	    
	    OnDomainCheckEnd: function()
	    {
	    	if (Ajax.activeRequestCount > 0)
	    	{
	    		if (this.isTransfer)
	    		{
	    			$('tab_register').removeClassName("tab_disabled");
			    	$('tab_transfer').removeClassName("tab_disabled");
			    	
			    	$('tab_register').addClassName('tab_disabled');
			    	
			    	Event.stopObserving($('tab_register'), 'click', register_tab_click);
	    		}
	    		else
	    		{
	    			$('tab_register').removeClassName("tab_disabled");
			    	$('tab_transfer').removeClassName("tab_disabled");
			    	
			    	$('tab_transfer').addClassName('tab_disabled');
			    	
			    	Event.stopObserving($('tab_transfer'), 'click', transfer_tab_click);
	    		}
	    	}
	    	else
	    	{
	    		$('tab_register').removeClassName("tab_disabled");
			    $('tab_transfer').removeClassName("tab_disabled");
			    
			    Event.observe($('tab_register'), 'click', register_tab_click);
			    Event.observe($('tab_transfer'), 'click', transfer_tab_click);
	    	}
	    	
	    	this.CheckDomainsNum();
	    },
	    
	    CheckDomainsNum:function()
	    {
	    	var okDomains = (!this.isTransfer) ? this.okDomains.register : this.okDomains.transfer;
	    	
	    	if (okDomains > 0)
	    	{
	    		$('next_btn').style.display = "";
	    	}
	    	else
	    	{
	    		$('next_btn').style.display = "none";
	    	}
	    	
	    	var totalDomains = (!this.isTransfer) ? this.totalDomains.register : this.totalDomains.transfer;
		    
	    	//if (totalDomains > 2)
	    	//	document = 1;
	    	
		    if (totalDomains > 0)
	    	{
	    		$('top_container').style.display = "";
	    		$('text_container').style.display = "none";
	    	}
	    	else
	    	{
	    		$('top_container').style.display = "none";
	    		$('text_container').style.display = "";
	    	}
	    },
	    
	    AddDomainRowToContainer: function(domainname, TLD)
	    {	    	
	    	var hashes = (!this.isTransfer) ? this.domainHashes.register : this.domainHashes.transfer;
	    	
	    	if (hashes[domainname+"."+TLD])
	    	{
	    		return false;
	    	}
    		
	    	var id = parseInt(Math.random()*100000);
    		var domain_template = this.domain_template.replace(/\#id\#/gi, id);
    		domain_template = domain_template.replace(/\#dname\#/gi, domainname);
    		
    		if (domainname.length > 14)
    			dname_concated = domainname.substring(0, 7)+"&hellip;"+domainname.substring(domainname.length-7, domainname.length);
    		else
    			dname_concated = domainname;
    			
    		domain_template = domain_template.replace(/\#dname_optimized\#/gi, dname_concated);
    		
    		domain_template = domain_template.replace(/\#TLD\#/gi, TLD);
    		cont = document.createElement('DIV');
		    		
    		cont.id = 'domain_row_'+id
    		cont.innerHTML = domain_template;
    		cont.className = "domain_row_container";    		
    				    			
    		$(this.DomainTableContainerId).appendChild(cont);
    		cont.style.overflow = "hidden";
    		cont.style.height = "1px";
    		cont.dHash = domainname+"."+TLD;
    		
    		
    		$(this.DomainTableContainerId).style.display = "";
    		$('top_container').style.display = "";
    		$('text_container').style.display = "none";
    		
    		var mySlide = new fx.Height(cont.id);
		    mySlide.toggle(1, 26);
		    
		    		    
		    Event.observe($('remove_link_'+id), 'click', this.OnRowDelete.bind(this, id));
		    
		    $('remove_link_'+id).style.display = '';
		    
		    if (!this.isTransfer)
		    {
		    	this.domainHashes.register[cont.dHash] = id;
		    	this.totalDomains.register++;
		    }
		    else
		    {
		    	this.domainHashes.transfer[cont.dHash] = id;
		    	this.totalDomains.transfer++;
		    }
		    		    
		    try
		    {
		    	this.SetAvailTLDs();
		    }
		    catch(e){}
		    
		    return true;
	    },
	    
	    ShowRegisterStep: function()
	    {
	    	$('tab_register').removeClassName("tab_active");
	    	$('tab_transfer').removeClassName("tab_active");
	    	
	    	$('t_tab_l').src = 'images/wiz_tab_inactive_l.gif';
	    	$('t_tab_m').style.background = 'url(\'images/wiz_tab_inactive_m.gif\')';
	    	$('t_tab_r').src = 'images/wiz_tab_inactive_r.gif';
	    	
	    	$('tab_register').addClassName('tab_active');
	    	
	    	this.DomainTableContainerId = 'canvas_register_results';
	    	
	    	$('canvas_register_results').style.display = "";
	    	$('canvas_transfer_results').style.display = "none";
	    	
	    	$('domainname').value = "";
	    	
	    	this.isTransfer = false;
	    	
	    	$('operation').value = "Register";
	    	
	    	for (var k in this.Options)
	    	{
	    		if (typeof(this.Options[k]) != 'function' && $(this.Options[k]))
	    		{
	    			$(this.Options[k]).style.display = "";
	    		}
	    	}
	    	
	    	var opts = $('TLDs_container').childNodes;
	    	for (var i = 0; i < opts.length; i++)
	    	{
	    		if (opts[i].id && opts[i].tagName == "DIV")
	    		{
	    			var elems = $(opts[i]).getElementsByTagName("INPUT");    		
	    			elems[0].checked = false;
	    		}
	    	}
	    	
	    	for(var key in this.domainHashes.register)
	    	{
	    		if (typeof(this.domainHashes.register[key]) != 'function')
	    			this.SetDomainDomainRowAvail(this.domainHashes.register[key], false);
	    	}
	    	
	    	for(var key in this.domainHashes.transfer)
	    	{
	    		if (typeof(this.domainHashes.transfer[key]) != 'function')
	    			this.SetDomainDomainRowAvail(this.domainHashes.transfer[key], true);
	    	}
	    	
	    	this.SetAvailTLDs();
	    	
	    	this.OnDomainCheckEnd();
	    },
	    
	    ShowTransferStep: function()
	    {
	    	$('tab_register').removeClassName("tab_active");
	    	$('tab_transfer').removeClassName("tab_active");
	    	$('t_tab_l').src = '/images/wiz_tab_active_l.gif';
	    	$('t_tab_m').style.background = 'url(\'images/wiz_tab_active_m.gif\')';
	    	$('t_tab_r').src = '/images/wiz_tab_active_r.gif';
	    	
	    	
	    	this.DomainTableContainerId = 'canvas_transfer_results';
	    	
	    	$('domainname').value = "";
	    	
	    	$('canvas_transfer_results').style.display = "";
	    	$('canvas_register_results').style.display = "none";
	    	
	    	$('operation').value = "Transfer";
	    	
	    	
	    	this.Options = new Array();
	    	var opts = $('TLDs_container').childNodes;
	    	
	    	for (var i = 0; i < opts.length; i++)
	    	{
	    		if (opts[i].id && opts[i].tagName == "DIV")
	    		{
	    			var transfer_avail = opts[i].id.replace(/[^0-9]/g, "");
	    			
	    			if (transfer_avail == 0)
	    			{
	    				this.Options[this.Options.length] = opts[i].id;
	    				opts[i].style.display = "none";
	    			}
	    			
	    			var elems = $(opts[i]).getElementsByTagName("INPUT");    		
	    			elems[0].checked = false;
	    		}
	    	}
	    	
	    	this.isTransfer = true;
	    	
	    	for(var key in this.domainHashes.register)
	    	{
	    		if (typeof(this.domainHashes.register[key]) != 'function')
	    			this.SetDomainDomainRowAvail(this.domainHashes.register[key], true);
	    	}
	    	
	    	for(var key in this.domainHashes.transfer)
	    	{
	    		if (typeof(this.domainHashes.transfer[key]) != 'function')
	    			this.SetDomainDomainRowAvail(this.domainHashes.transfer[key], false);
	    	}
	    	
	    	this.SetAvailTLDs();
	    	
	    	this.OnDomainCheckEnd();
	    }
	}); 