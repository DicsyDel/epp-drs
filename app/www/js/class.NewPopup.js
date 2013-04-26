
var NewPopup = function(){
	this.initialize.apply(this, arguments);
};

NewPopup.prototype = {
	options: {},
	tap: null,
	content:null,
	
	initialize: function(popup) {
		var popup = this.byID(popup);
		var options = this.extend({
			target: null,
			width:	370,
			height: 150,
			popup: popup,
			selecters: null
		}, arguments[1] || {});
				
		this.options = options;
		this.setup();
	},
	
	updateTarget: function (target)
	{
		this.options.target = target;
		
		var tOffset = $(this.options.target).positionedOffset();

		targetOffset = new Array();
		
		targetOffset[0] = tOffset[0]-this.options.width-80;
		targetOffset[1] = tOffset[1]-80;
		
		this.tap.style.top = 45 + "px";
		this.tap.style.left = this.options.width+8 + "px";
					
		this.options.popup.style.left = targetOffset[0] + 'px';
		this.options.popup.style.top = targetOffset[1] + 'px';
	},
	
	positioningPopup: function(offsets) {
		var scrOffset = this.scrollOffset();
		var popup = this.options.popup;
		
		var PDims	= [this.options.width + 40, this.options.height + 130];
		
		if (this.options.target)
		{
			var tOffset = $(this.options.target).positionedOffset();

			targetOffset = new Array();
			
			targetOffset[0] = tOffset[0]-this.options.width-80;
			targetOffset[1] = tOffset[1]-80;
		}
		else
			var targetOffset = offsets;
						
		this.show(this.tap);	
				
		this.tap.style.top = 45 + "px";
		this.tap.style.left = this.options.width+8 + "px";
					
		popup.style.left = targetOffset[0] + 'px';
		popup.style.top = targetOffset[1] + 'px';
	},
	
	ieTweak: function(command)
	{
		/*
		$H(this.options.selecters).each(
			function (Item)
			{
				if ((typeof (Item[1]) == "object"))
				{
					try {
						Item[1].style.visibility = command;
					} catch (err) {}
				}
			}
		);
		*/
	},
	
	raisePopup: function(Offsets)
	{
		//this.ieTweak('hidden');
		
		this.positioningPopup(Offsets);
		//this.selecters('hidden');
		this.show(this.options.popup);
	},
	
	setup: function() {
		var options	= this.options;
		var popup	= this.options.popup;
		var content = popup.innerHTML;
		
		if (this.options.target != '')
		{
			var target	= this.byID(this.options.target);
			target.callerObj = this;
			target.onclick = function(event) {
				var event = event || window.event;
				var Offsets	= [event.clientX, event.clientY];
				this.callerObj.positioningPopup(Offsets);
				this.callerObj.selecters('hidden');
				this.callerObj.show(this.callerObj.options.popup);
				
				try
				{
					//this.ieTweak('hidden');
				}
				catch(e){}
			};
		}
		
		//alert(target.offsetTop);
//		target.style.border 	= '1px solid red';
		popup.innerHTML 		= '';
		popup.style.position	= 'absolute';
		popup.style.width 		= options.width + 70 + 'px';
		popup.style.left 		= 0 + 'px';
		popup.style.top			= 0 + 'px'; //TOffset[1] - PDims.height + 'px';
		popup.style.zIndex 		= 99999;
		
		var div1 = this.div("pop_popup pop_header");
		var div2 = this.div("pop_sizex", 1);
				
		div1.appendChild(div2);
		
		var div3 = this.div("pop_popup pop_content");
		var div4 = this.div("pop_sizex pop_sizey", 1, 1, -14);
			div4.innerHTML = content;
		div3.appendChild(div4);
		
		this.content = div4;
		
		if (!NoCloseButton)
		{
			var img  = new Image();
				img.src = "/images/popup/close.png";
				img.border = 0;
				img.id='popup_close_img';
				div1.appendChild(img);
				
				img.style.position = "absolute";
				img.style.zIndex = "9999999";
				img.style.top = "-10px";
				img.style.left = this.options.width - 8 +"px";
				
				img.style.cursor = "pointer";
				img.callerObj = this;
				img.onclick = function() {
					this.callerObj.hide(this.callerObj.options.popup);
					this.callerObj.hide(this.callerObj.tap);
					//this.callerObj.selecters('visible');
					//this.callerObj.ieTweak('visible');
				};
		}
		
		var div5 = this.div("pop_popup");
		var div7 = this.div("pop_nw pop_left");
		var div8 = this.div("pop_n pop_left pop_sizex", 1);
		var div9 = this.div("pop_ne pop_left");
		var div10 = this.div("pop_clear");
		var div11 = this.div("pop_w pop_left pop_sizey", 0, 1);
		var div12 = this.div("pop_left pop_sizey pop_sizex", 1, 1, 0, 0);
		div12.style.backgroundColor = "#F2F2F2";
		var div13 = this.div("pop_e pop_left pop_sizey", 0, 1);
		var div14 = this.div("pop_clear");
		var div15 = this.div("pop_sw pop_left");
		var div16 = this.div("pop_s pop_left pop_sizex", 1);
		var div17 = this.div("pop_se pop_left");
		var div18 = this.div("pop_clear");
		var div19 = this.div("pop_tap");
				
		this.tap  = div19;
		
		div5.appendChild(div7);
		div5.appendChild(div8);
		div5.appendChild(div9);
		div5.appendChild(div10);
		div5.appendChild(div11);
		div5.appendChild(div12);
		div5.appendChild(div13);
		div5.appendChild(div14);
		div5.appendChild(div15);
		div5.appendChild(div16);
		
		div5.appendChild(div17);
		div5.appendChild(div18);
		
		popup.appendChild(div19);
				
		
		
		popup.appendChild(div1);
		popup.appendChild(div3);
		popup.appendChild(div5);
		
		this.hide(popup);
	},
	
	div: function(className, setSizeX, setSizeY, offsetX, offsetY) 
	{
		if (!offsetX)
			offsetX = 0;
			
		if (!offsetY)
			offsetY = 0;
		
		var div = document.createElement("DIV");
		if (className) div.className = className;

		if (setSizeX)	div.style.width = this.options.width + offsetX + 'px';
		if (setSizeY)	div.style.height = this.options.height + offsetY + 'px';
		
		return div;
	},
	
	hide: function(element) {
		var element = this.byID(element);
		try {
			element.style.display = 'none';
		} catch (err) {}
		
		//this.selecters('vivible');
	},
	
	show: function(element) {
		var element = this.byID(element);

		try {
			switch (element.tagName) 
			{
				case "DIV" : element.style.display = 'block'; break;
				default: element.style.display = '';
			}
			element.style.display = '';
		} catch (err) {}
	},
	
	selecters: function(command)
	{
		/*
		if (this.options.target != '')
		{
			var frm = this.findElement(this.options.target, "form");
			if (!frm) return false;
			
			var selecters = frm.getElementsByTagName("select");
			for(var i=0; i < selecters.length; i++) {
				if ((typeof (selecters[i]) == "object"))
				{
					try {
						selecters[i].style.visibility = command;
					} catch (err) {}
				}
			}
		}
		else
			return false;
		*/
	},
		
	findElement: function(element, tagName) {
		var element = this.byID(element);
		while (element.parentNode && (!element.tagName ||
	    	(element.tagName.toUpperCase() != tagName.toUpperCase())))
	    element = element.parentNode;
		return element;
	},
	
	extend: function(destination, source) {
		for (var property in source) destination[property] = source[property];
		return destination;
	},
	
	byID: function(el) {
		if (typeof el == 'string') el = document.getElementById(el);
		return el;
	},
	
	cumulativeOffset: function(element) {
		var valueT = 0, valueL = 0;
		do {
			valueT += element.offsetTop  || 0;
			valueL += element.offsetLeft || 0;
			element = element.offsetParent;
		} while (element);
		return [valueL, valueT];
	},
	
	getDimensions: function(element) {
		element = this.byID(element);

		if (element.style.display != 'none')
			return {width: element.offsetWidth, height: element.offsetHeight};

		// All *Width and *Height properties give 0 on elements with display none,
		// so enable the element temporarily
		var els = element.style;
		var originalVisibility = els.visibility;
		var originalPosition = els.position;
			els.visibility = 'hidden';
			els.position = 'absolute';
			els.display = '';
		var originalWidth = element.clientWidth;
		var originalHeight = element.clientHeight;
			els.display = 'none';
			els.position = originalPosition;
			els.visibility = originalVisibility;
		return {width: originalWidth, height: originalHeight};
	},
	
	scrollOffset: function() {
		var deltaX =  window.pageXOffset
				|| document.documentElement.scrollLeft
				|| document.body.scrollLeft
				|| 0;
		var deltaY =  window.pageYOffset
				|| document.documentElement.scrollTop
				|| document.body.scrollTop
				|| 0;
		return [deltaX, deltaY];
	}

};
