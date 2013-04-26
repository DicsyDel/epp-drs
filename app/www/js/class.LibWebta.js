
	// +--------------------------------------------------------------------------+
	// | LibWebta scripts.					                                          |
	// +--------------------------------------------------------------------------+
	// | Copyright (c) 2003-2006 Webta Inc, http://webta.net/copyright.html       |
	// +--------------------------------------------------------------------------+
	// | This program is protected by international copyright laws. Any           |
	// | use of this program is subject to the terms of the license               |
	// | agreement included as part of this distribution archive.                 |
	// | Any other uses are strictly prohibited without the written permission    |
	// | of "Webta" and all other rights are reserved.                            |
	// | This notice may not be removed from this source code file.               |
	// | This source file is subject to version 1.1 of the license,               |
	// | that is bundled with this package in the file LICENSE.                   |
	// | If the backage does not contain LICENSE file, this source file is        |
	// | subject to general license, available at http://webta.net/license.html   |
	// +--------------------------------------------------------------------------+
	// | Authors:	Alex Kovalyov <ak@webta.net> 								  |
	// | 			Sergey Koksharov <sergey@webta.net> 						  |
	// +--------------------------------------------------------------------------+
	

var LibWebta = Class.create();

LibWebta.prototype = {
	allchecked:		false,
	tweaker:		null,
	options:		{},
	
	initialize: function() {
		var options = Object.extend({
			load_calendar:	0,
			load_treemenu:	0,
			
			topmenu:		'TopMenu',
			tabitems:		'Webta_Items',
			xmlserver:		'xmlserver.php',
			
			menucss:		'../js/dmenu/dmenu.css',
			menujs:			'dmenu/dmenu.js',
			
			calendar_css:	'../js/calendar/calendar.css',
			calendar_js:	'calendar/calendar.js',
			calendar_en:	'calendar/calendar-en.js',
			calendar_inc:	'calendar.inc.js',
			
			treemenu_js:	'treemenu/treemenu.js',
			treemenu_css:	'../js/treemenu/tree.css'
		}, arguments[0] || {});
		
		this.options = options;
	},
	
	load: function(library) {
		if (!library) return false;
		
		if (library.indexOf(".js") != -1) {
			// load js library
			document.writeln("<script type='text/javascript' src='/js/"+ library +"'></script>");
		}
		
		if (library.indexOf(".css") != -1) {
			// load css style sheet
			document.writeln("<link href='/css/"+ library +"' rel='stylesheet' type='text/css'>");
		}
		
		return true;
	},
	
	preloadImages: function() {
		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
			if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	},
	
	
	checkall: function()
	{
		var frm = $("frm");
		for (var i=0; i<frm.elements.length; i++) 
		{
			var e = frm.elements[i]
			if ((e.name == "delete[]") && (e.type=='checkbox') && !e.disabled) {
				e.checked = !this.allchecked;
			}
		}
		
		this.allchecked = !this.allchecked;
		if (this.tweaker)
			this.tweaker.checkall();
	},

	generatePass: function(element, passwordlength, isdigits) {
	    var pwchars = isdigits ? "0123456789" : "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ";
	    var passwordlength = (passwordlength > 0) ? passwordlength : 16;
	    var passwd = '';
	
	    for (var i = 0; i < passwordlength; i++ ) {
	        passwd += pwchars.charAt( Math.floor( Math.random() * pwchars.length ) );
	    }
	    
	    var element = document.getElementById(element);
	  		element.value = passwd;
	},
	
	setupTweaker: function() {
		if (this.tweaker)
			this.tweaker.setup();
	},
	
	createTweaker: function() {
		if (this.tweaker) {
			this.tweaker.setup();
			this.tweaker.create();
		}
	},
	
	showTopMenu: function() {
		var element = $(this.options.topmenu);
		if (element) {
			element.style.visibility = 'visible';
			DynarchMenu.setup(this.options.topmenu);
		}
		
		this.tweaker = new FitoTab(this.options.tabitems);
	},
	
	afterload: function() {
		this.showTopMenu();
		this.createTweaker();
	},
	
	openurl: function(url) {
		window.location.href = url;
		return true;
	},
	
	loadDefautls: function() {
		if (this.options.menucss)
			this.load(this.options.menucss);
		
		if (this.options.menujs)
			this.load(this.options.menujs);
			
		if (this.options.load_calendar) {
			if (this.options.calendar_css)
				this.load(this.options.calendar_css);
				
			if (this.options.calendar_inc)
				this.load(this.options.calendar_inc);
				
			if (this.options.calendar_js)
				this.load(this.options.calendar_js);
				
			if (this.options.calendar_en)
				this.load(this.options.calendar_en);
		}
		
		if (this.options.load_treemenu) {
			if (this.options.treemenu_js)
				this.load(this.options.treemenu_js);
				
			if (this.options.treemenu_css)
				this.load(this.options.treemenu_css);
		}
		
	},
	
	search: function() {
		string = $('search_string').value;
	
		if (self.location.href.indexOf("index.php") == -1)
		{
			document.location = 'index.php?searchpage='+string;
			return true;
		}

		$("search_button").disabled = true;
		$("search_image").style.display = "";
		$("title_td").innerHTML = "Search results for '"+string+"'";
		
		var ajax = new Ajax.Request(this.options.xmlserver, {
			onSuccess : function(response){
				$("index_menu_div").innerHTML = response.responseText;
				$("search_button").disabled = false;
				$("search_image").style.display = "none";
			},
			
			method: 'get',
			parameters: "_cmd=search&search_string="+string,
			onFailure: function() { 
				$("search_button").disabled = false;
				$("search_image").style.display = "none";
			}
		});
	},
	
	collapseSettings: function(id, obj) {
		var element = $(id);
			element.style.display = (element.style.display == 'none') ? 'block' : 'none';
	
		var img = obj.getElementsByTagName("img");
		if (img)
			img[0].src = (img[0].src.indexOf("sorta") != -1) ? img[0].src.replace('sorta', 'sortd') : img[0].src.replace('sortd', 'sorta');
	}
	
};
