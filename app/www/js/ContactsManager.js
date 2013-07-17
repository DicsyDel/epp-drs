	var ContactChilds = new Array();
	
	function SetContact()
	{
		
		$('Webta_ErrMsg_contact').style.display = "none";
		$('another_contact_options').style.display = "none";
		$('dropdown_'+type).disabled = true;
					
		var params = 'task=set_contact'+
					 '&type=' + type + '&TLD='+TLD+'&groupname='+groupname+
					 '&domainid='+domainid+'&newContact='+$('dropdown_'+type).value;
		
		var url = 'server/misc.php?'+params
		
		$('loader_'+type).style.display = "";
		
		var nm = 0;
		for(var ind = 0; ind < $('dropdown_'+type).childNodes.length; ind++)
		{
			if ($('dropdown_'+type).childNodes[ind] && $('dropdown_'+type).childNodes[ind].tagName == 'OPTION')
				nm++;
		}

		if (nm == 1)
		{
			$('dropdown_'+type).style.display = "none";
		}
		
		new Ajax.Request(url,
    	{   
    		method: 'get',   
    		onSuccess: OnSetContactSuccess.bind(this, TLD, type),
    		onFailure: OnSetContactFailure.bind(this, TLD, type),
    		TLD: TLD,
    		CType: type
    	});
	}
	
	function ShowError(data)
	{
		if ($('Webta_ErrMsg_contact'))
		{
			$('Webta_ErrMsg_contact').style.display = "";
			$('Webta_ErrMsg_contact').innerHTML = data;
			new Effect.Pulsate($('Webta_ErrMsg_contact'));
		}
	}
	
	function OnSetContactSuccess(TLD, type, response)
	{
		try
		{
			eval('var response = '+response.responseText);
			if (response.result == true)
			{
				if (response.data.checkout_redir == true)
				{
					if (response.data.invoice_status == 1)
						document.location='order_info.php?invoiceid='+response.data.invoiceid;
					else
						document.location='checkout.php?string_invoices='+response.data.invoiceid;
				}
				else
				{
					document.location='domains_view.php';
				}
			}
			else
			{
				ShowError(response.data);
			}
						
			$('another_contact_options').style.display = "";
			$('loader_'+type).style.display = "none";
			$('dropdown_'+type).disabled = false;
		}
		catch(e){}
	}
	
	function OnSetContactFailure(TLD, type, response)
	{
		$('another_contact_options').style.display = "";
		$('loader_'+type).style.display = "none";
		$('dropdown_'+type).disabled = false;
	}
	
	function SaveContact(type, TLD, domainid)
	{
		var cnt_params = $('frm_'+type).serialize();
		cnt_params += "&TLD="+TLD+"&type="+type+"&task=edit&domainid="+domainid;
		
		var url = 'server/misc.php?'+cnt_params
		
		$('contact_edit_button_'+type).disabled = true;
		$('contact_cancel_button_'+type).disabled = true;
		
		$('contact_create_loader_'+type).style.display = "";
		$('error_'+type).style.display = "none";
		
		new Ajax.Request(url, 
    	{   
    		method: 'get',   
    		onSuccess: OnSaveContactSuccess.bind(this, TLD, type),
    		onFailure: OnSaveContactFailure.bind(this, TLD, type),
    		TLD: TLD,
    		CType: type
    	});
	}
	
	function OnSaveContactSuccess(TLD, type, response)
	{
		try
		{
			eval('var response = '+response.responseText);
			if (response.result == true)
			{
				document.location='domains_view.php';
			}
			else
			{
				$('error_'+type).style.display = "";
				$('error_text_'+type).innerHTML = response.data;
			}
			
			$('contact_edit_button_'+type).disabled = false;
			$('contact_cancel_button_'+type).disabled = false;
		
			$('contact_create_loader_'+type).style.display = "none";
		}
		catch(e){}
	}
	
	function OnSaveContactFailure(TLD, type, response)
	{
		$('contact_edit_button_'+type).disabled = false;
		$('contact_cancel_button_'+type).disabled = false;
		
		$('contact_create_loader_'+type).style.display = "none";
	}
	
	function ContactCancelSaving(type, TLD)
	{
		$(type+'_new_contact_cont').style.display = "none";
		$('loader_'+type).style.display = "none";
		$('current_contact_options').style.display = "";
	}
	
	function EditContact()
	{
		$('current_contact_options').style.display = "none";
					
		var params = 'task=get_edit_contact_form'+
					 '&type=' + type + "&TLD="+TLD+"&groupname="+groupname+"&domainid="+domainid;
		
		var url = 'server/misc.php?'+params
		
		$('loader_'+type).style.display = "";
		
		var nm = 0;
		for(var ind = 0; ind < $('dropdown_'+type).childNodes.length; ind++)
		{
			if ($('dropdown_'+type).childNodes[ind] && $('dropdown_'+type).childNodes[ind].tagName == 'OPTION')
				nm++;
		}

		if (nm == 1)
		{
			$('dropdown_'+type).style.display = "none";
		}
		
		new Ajax.Request(url, 
    	{   
    		method: 'get',   
    		onSuccess: OnEditContactGetFormSuccess.bind(this, TLD, type),
    		onFailure: OnEditContactGetFormFailure.bind(this, TLD, type),
    		TLD: TLD,
    		CType: type
    	});		
	}
	
	function OnEditContactGetFormSuccess(TLD, type, response)
	{
		$('loader_'+type).style.display = "none";
	
		if (response.responseText)
		{
			try
			{
				$(type+'_new_contact_value').innerHTML = response.responseText;
				$(type+'_new_contact_value').innerHTML.evalScripts();
				if ($('js_'+type))
				{
					//$('js_'+type).innerHTML.evalScripts();
				}
			}
			catch(err)
			{
				//alert(err);
			}
			
			$(type+'_new_contact_cont').style.display = "";
		}
	}
	
	function OnEditContactGetFormFailure(TLD, type, response)
	{
		$('loader_'+type).style.display = "none";
		$('current_contact_options').style.display = "";
	}
	
	function ClearSelectBox(select_object)
	{
	    for (i=0;i<select_object.options.length;)
	    {
	        select_object.options.remove(0);
	    }
	}
	
	function CheckContact(type, value, TLD, groupname)
	{
		if (value == '[NEW]')
		{
			var params = 'task=get_contact_form'+
						 '&type=' + type + "&TLD="+TLD+"&groupname="+groupname;
			
			var url = 'server/misc.php?'+params
	    	
	    	if ($('cbtn_2'))
				$('cbtn_2').disabled = true;
			
			$('loader_'+type).show();
			
			var optgroup = $('dropdown_'+type).down('optgroup'); 
			if (optgroup.hasClassName('action-bar1')) {
				$('dropdown_'+type).style.display = "none";			
			}
			
			new Ajax.Request(url, 
	    	{   
	    		method: 'get',   
	    		onSuccess: OnCheckContactSuccess.bind(this, TLD, type),
	    		onFailure: OnCheckContactFailure.bind(this, TLD, type),
	    		TLD: TLD,
	    		CType: type
	    	});
	    	
	    	if (ContactChilds[type])
			{
				$('select_parent_'+ContactChilds[type]).style.display = "";
				$('dropdown_'+ContactChilds[type]).style.display = "none";
			}
		}
		else if (value == '[CHOSE]')
		{
			var params = 'task=get_contact_list'+
						 '&type=' + type + "&TLD="+TLD+"&groupname="+groupname;
			
			var url = 'server/misc.php?'+params
	    	
	    	if ($('cbtn_2'))
				$('cbtn_2').disabled = true;
			
			$('loader_'+type).show();
			
			new Ajax.Request(url, 
	    	{   
	    		method: 'get',   
	    		onSuccess: OnLoadContactSuccess.bind(this, TLD, type),
	    		onFailure: OnCheckContactFailure.bind(this, TLD, type),
	    		TLD: TLD,
	    		CType: type
	    	});
		}
		else
		{
			$('loader_'+type).hide();			
			
			if ($("hidd_"+type))
				$("hidd_"+type).value = value;
				
			if ($("hidd2_"+type))
				$("hidd2_"+type).value = value;

				
			$(type+'_new_contact_cont').style.display = "none";
			$(type+'_select_contact_cont').style.display = "none";
			
			if ($('cbtn_2'))
				$('cbtn_2').disabled = false;
			
			if (value != '[SEP]' && value != '')
			{
				if (ContactChilds[type])
				{
					$('select_parent_'+ContactChilds[type]).style.display = "none";
					$('dropdown_'+ContactChilds[type]).style.display = "";
					GetChildContacts(value, ContactChilds[type], TLD);
				}
			}
		}
	}

	function GetChildContacts(parentID, type, TLD)
	{
		$('loader_'+type).style.display = "";
		$('dropdown_'+type).style.display = "none";
		
		var params = 'task=get_childs_list'+
				     '&type=' + type + "&TLD="+TLD+"&parentID="+parentID;
			
		var url = 'server/misc.php?'+params
		
		new Ajax.Request(url, 
    	{   
    		method: 'get',   
    		onSuccess: OnGetChildContactsSuccess.bind(this, TLD, type, parentID),
    		onFailure: OnGetChildContactsFailure.bind(this, TLD, type, parentID),
    		TLD: TLD,
    		CType: type,
    		ParentID: parentID
    	});
	}

	function OnGetChildContactsFailure(TLD, type, parentID, response)
	{
		$('loader_'+type).style.display = "none";
	}

	function OnGetChildContactsSuccess(TLD, type, parentID, response)
	{
		$('loader_'+type).style.display = "none";
		try
		{
			eval('var response = '+response.responseText);
			
			if (response.result == true)
			{				
				var dropdown = $('dropdown_'+type);
				ClearSelectBox(dropdown);
				
				if (response.data.length > 0)
				{
					for (var i = 0; i < response.data.length;i++)
					{
						dropdown.options[dropdown.options.length] = new Option(response.data[i].name, response.data[i].clid);
					}
					
					dropdown.options[dropdown.options.length-1].selected = true;
					dropdown.value = dropdown.options[dropdown.options.length-1].value; 
				}
				
				dropdown.options[dropdown.options.length] = new Option("Create new contact...", 0);
				dropdown.disabled = false;
				dropdown.style.display = "";
				
				CheckContact(type, $('dropdown_'+type).value, TLD);
			}
		}
		catch(e){}
	}
	
	function OnCheckContactSuccess(TLD, type, response)
	{
		$('loader_'+type).style.display = "none";
		
		if (response.responseText)
		{
			try
			{
				$(type+'_new_contact_value').innerHTML = response.responseText;
				$(type+'_new_contact_value').innerHTML.evalScripts();
			}
			catch(err)
			{
				//alert(err);
			}
			
			$(type+'_new_contact_cont').style.display = "";
			$(type+'_select_contact_cont').hide();
		}
	}
	
	function OnCheckContactFailure(response, json, options)
	{
		$('loader_'+type).style.display = "none";
	}
	
	function OnLoadContactSuccess (TLD, type, response)
	{
		$('loader_'+type).style.display = "none";
		
		if (response.responseText)
		{
			try
			{
				$(type+'_select_contact_value').innerHTML = response.responseText;
				$(type+'_select_contact_value').innerHTML.evalScripts();
			}
			catch(err)
			{
				//alert(err);
			}
			
			$(type+'_select_contact_cont').style.display = "";
			$(type+'_new_contact_cont').hide();
			
			$('form[name="contactchoise-'+type+'"]').down('.loader').hide();
		}
	}
	
	function CreateContact(type, TLD)
	{
		var cnt_params = $('frm_'+type).serialize();
		cnt_params += "&TLD="+TLD+"&type="+type+"&task=create";
		
		var child_type = "";
		
		for(key in ContactChilds)
		{
			if (typeof(key) == "string" && typeof(ContactChilds[key]) != 'function')
			{
				if (ContactChilds[key] == type)
				{
					child_type = key;
				}
			}
		}
		
		if (child_type != "")
		{
			cnt_params+="&parentID="+$('dropdown_'+child_type).value;
		}

		var url = 'server/misc.php?'+cnt_params
		
		$('contact_create_button_'+type).disabled = true;
		$('contact_create_loader_'+type).style.display = "";
		$('error_'+type).style.display = "none";
		
		new Ajax.Request(url, 
    	{   
    		method: 'get',   
    		onSuccess: OnCreateContactSuccess.bind(this, TLD, type),
    		onFailure: OnCreateContactFailure.bind(this, TLD, type),
    		TLD: TLD,
    		CType: type
    	});
	}
	
	function OnCreateContactSuccess(TLD, type, response)
	{
		try
		{
			if (!response.responseText || response.responseText == "")
			{
				$('error_'+type).style.display = "";
				$('error_text_'+type).innerHTML = "Request timeout. Please try again.";
				
				$('contact_create_button_'+type).disabled = false;
				$('contact_create_loader_'+type).style.display = "none";
				return;
			}
			
			eval('var response = '+response.responseText);
			if (response.result == true)
			{
				var data = response.data;
				var title = data.title;
				
				SelectContact(data.id, title, data.type, data.groupname);
				
				if ($("hidd_"+type))
					$("hidd_"+type).value = response.data.id;
					
				$(type+'_new_contact_cont').style.display = "none";
				
				if ($('cbtn_2'))				
					$('cbtn_2').disabled = false;
				
			}
			else
			{
				$('error_'+type).style.display = "";
				$('error_text_'+type).innerHTML = response.data;
			}
			
			$('contact_create_button_'+type).disabled = false;
			$('contact_create_loader_'+type).style.display = "none";
		}
		catch(e){}
	}
	
	function OnCreateContactFailure()
	{
		$('contact_create_button_'+type).disabled = false;
		$('contact_create_loader_'+type).style.display = "none";
	}
	
	
	function SelectContact (clid, title, type, groupname)
	{
		$$('select[groupname="'+groupname+'"]').each(function (el) {
			var optgroup = $(el).down('optgroup');
			if (optgroup.hasClassName('actions')) {
				var optgroup2 = document.createElement('optgroup');
				optgroup.insert({before: optgroup2});
				optgroup = optgroup2;
			}
			
			var opt = document.createElement('option');
			opt.value = clid;
			opt.innerHTML = title;
			optgroup.appendChild(opt);
			opt.selected = el.value == '[NEW]' || el.value == '[CHOSE]';
			if (opt.selected) {
				el.show();
			}
			if (el.visible()) {
				el.onchange();
			}
		});
	}
	
	function ContactList_OnSelect (btn)
	{
		var form = btn.form;

		var itemTd, clid;		
		var choises = form.elements['clid'];
		var selected = false;
		
		if (choises.length) {
			// Iterate over radio button collection to find checked one
			for (var i=0; i<choises.length; i++) {
				var choise = choises.item(i);
				if (choise.checked) {
					selected = true;
					itemTd = $(choise).up('td');
					clid = choise;
					break;
				}
			}
		}
		else {
			// When radio button list constains only one button
			// instead of collection, browser returns to us this button
			var choise = choises;
			if (choise.checked) {
				selected = true;
				itemTd = $(choise).up('td');
				clid = choise;
			}
		}
		
		if (!selected) {
			// No item was selected
			return;
		}
		
		var title = (itemTd.innerText || itemTd.textContent || '').replace(/^\s+/, '').replace(/\s+$/, '');
		
		SelectContact(
			clid.value, 
			title, 
			form.elements['type'].value, 
			form.elements['groupname'].value
		);
	}
	
	function ContactList_OnClickItem (htmlEl)
	{
		var container = $(htmlEl).up('.choise');
		$(container).getElementsBySelector('td').each(function (el) {
			$(el).removeClassName('Selected');
		});
		$(htmlEl).addClassName('Selected');
		$(htmlEl).down('input[type=radio]').checked = true;
	}
	
	function ContactList_ShowPage (type, pageNum)
	{
		var f = document.forms['contactchoise-' + type];
		ContactList_Update(f, {
			pn: pageNum,
			pf: f.filter_q.value
		});
	}
	
	function ContactList_ApplyFilter (btn)
	{
		ContactList_Update(btn.form, {
			applyFilter: true
		});
	}
	
	/**
	 * Contact list update method. 
	 */
	function ContactList_Update (form, params)
	{
		params = params || {};
		Object.extend(params, {
			task: 'get_contact_list',
			TLD: form.TLD.value,
			groupname: form.groupname.value,
			type: form.elements['type'].value
		});
		
		if (params.applyFilter) {
			Object.extend(params, {
				act: form.act.value,
				filter_q: form.filter_q.value
			});
			delete params.applyFilter;
		}
		
		new Ajax.Request('server/misc.php', {   
    		method: 'get',
    		parameters: params,
    		onSuccess: OnLoadContactSuccess.bind(this, params.TLD, params.type),
    		onFailure: OnCheckContactFailure.bind(this, params.TLD, params.type),
    		TLD: params.TLD,
    		CType: params.type
    	});
    	
    	// Show loading indicator
 		form.down('.loader').show();
	}
