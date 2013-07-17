
var FitoTab = Class.create();

FitoTab.prototype = {
	element:	null,
	options:	{},
	trs:		[],
	bgcolors:	[],
	choosed_colors: [],
	allchecked:	false,
	
	initialize: function(element) {
		var element = $(element);
		
		try
		{
			var options = {
				element:		element,
				hoverColor:		'#FFFFCC',
				selectedColor: 	'#FFFFCC',
				trIDPrefix:		'tr'
			};
			
			if (arguments[1])
				Object.extend(options, arguments[1] || {});
		
		this.options = options;
		}
		catch(err){ };
		
		if (element && element.tagName == "TABLE") {
			this.trs = element.getElementsByTagName("tr");
		}
	},
	
	create: function() {
		if (!this.options.element) return false;
		
		callerObj = this;
		for (var i=0; i < this.trs.length; i++)
		{
			var tr = this.trs[i];
			if (tr.id.indexOf(this.options.trIDPrefix) == 0)
			{
				var id = parseInt(tr.id.replace(this.options.trIDPrefix + '_', ''));
				this.bgcolors[id] = tr.style.backgroundColor;
				
				//
				// Over table row
				//
				tr.onmouseover = function(e)
				{
					var id = parseInt(this.id.replace(callerObj.options.trIDPrefix + '_', ''));
					if (!callerObj.choosed_colors[id])
					{
						this.style.backgroundColor = callerObj.options.hoverColor;
					}
				};
				
				//
				// Out table row
				//
				tr.onmouseout = function(e)
				{
					var id = parseInt(this.id.replace(callerObj.options.trIDPrefix + '_', ''));
					if (!callerObj.choosed_colors[id])
					{
						this.style.backgroundColor = callerObj.bgcolors[id];
					}
				};
				
				//
				// Click check box
				//
				tr.onclick = function(e)
				{
					var event = (e || window.event);
					var element = (event.originalTarget || event.srcElement);
					
					if (element.tagName == "INPUT" && element.type == "checkbox")
					{
						var id = parseInt(this.id.replace(callerObj.options.trIDPrefix + '_', ''));
						
						this.style.backgroundColor = (element.checked) ? callerObj.options.selectedColor : callerObj.bgcolors[id];
						callerObj.choosed_colors[id] = (element.checked) ? callerObj.options.selectedColor : null;
					}
				};
			}
		}
	},
	
	checkall: function() {
		if (!this.options.element) return false;
		
		var checked = (this.allchecked) ? false : true;
		this.allchecked = checked;
		
		for (var i=0; i < this.trs.length; i++)
		{
			var tr = this.trs[i];
			if (tr.id.indexOf(this.options.trIDPrefix) == 0)
			{
				var inputs = tr.getElementsByTagName('input');
				
				if (inputs[0].type == "checkbox" && inputs[0].disabled == true)
					continue;
				
				var id = parseInt(tr.id.replace(this.options.trIDPrefix + '_', ''));
				tr.style.backgroundColor = (checked) ? this.options.selectedColor : this.bgcolors[id];
				this.choosed_colors[id] = (checked) ? this.options.selectedColor : null;
			}
		}
	},
	
	SetColor: function(id) {
		try {
			$(this.trIDPrefix + '_' + id).style.backgroundColor = this.bgcolors[id];
		} catch (err) { }
	},
	
	setup: function() {
		this.options.prototype = {
			alternative:	'_Webta_Settings'
		};
		
		if (arguments[0])
			Object.extend(this.options, arguments[0] || {});	
		
		if (!this.options.element) {
			this.options.element = $(this.options.alternative);
			var settings = true;
		}

		if (!this.options.element) return false;
		
		try {
			
			var tab = this.options.element;
			var rowHeight = 20;
			var Head = tab.getElementsByTagName("thead")[0];
			var Body = tab.getElementsByTagName("tbody")[0];
			
			var _tempPos = Position.cumulativeOffset(tab);

			var tabOffsetLeft = _tempPos[0];
			var tabOffsetTop = _tempPos[1];
			
			var tabHeight = this.GetWindowHeight() - tabOffsetTop - 55;
			
			//
			// set cells height
			//
			var cmlt = 0;
			for (var i = 0; i < tab.rows.length - 1; i++) {
				if (!i) {
					tab.rows[0].style.height = "10px";
					tab.rows[0].style.padding = 0;
				} else {
					tab.rows[i].style.height = rowHeight + "px";
				}
				cmlt += tab.rows[i].offsetHeight;
			}
			
			try {
				tab.rows[tab.rows.length - 1].style.height = tabHeight - cmlt + "px";
			} catch (err2) {
				
			} finally {

				var wHead = (!settings) ? Head.offsetHeight : tab.rows[0].offsetHeight;
				var lastRow = tab.rows[tab.rows.length - 2];
				var firstRow = tab.rows[1];
				var w = 0; 
				var lastNum = lastRow.cells.length - 1;
				
				for (var i = 0; i < firstRow.cells.length; i++) {
					firstRow.cells[i].style.borderTop = '1px solid #A2BBDD';
				}
				
				for (var i = 1; i < tab.rows.length; i++) {
					if ((i % 2 == 0) && !settings && tab.rows[i].style.backgroundColor == "") {
						tab.rows[i].style.backgroundColor = '#F9FAFF';
					}
					if (tab.rows[i].cells[0].className.indexOf("Part") == -1)
					tab.rows[i].cells[0].style.borderLeft = '1px solid #A2BBDD';
				}
				
				if (settings) return false;
				
				for (var i = 0; i < lastRow.cells.length; i++) {
					w += lastRow.cells[i].offsetWidth;
				
					if ((lastNum > i && lastRow.cells[i+1].className == "Item") || (settings && !i))	
					{
						try {
							if ($("separator_" + i)) {
								$("separator_" + i).parentNode.removeChild($("separator_" + i));
							}
						} catch (err) {} 
						var div = document.createElement("div");
						div.id	=	"separator_" + i;
						div.className 		= "vrule gutter";
						div.style.left 		= tabOffsetLeft + w - 3 + 'px';
						div.style.top 		= tabOffsetTop + wHead + 'px';
						div.style.height 	= tab.offsetHeight - wHead - 1 + 'px';
						document.body.appendChild(div);
					}
				}
			}
		} catch (err) {}
	},
	
	GetWindowHeight: function() {
		return (window.innerHeight != undefined) ? window.innerHeight : document.documentElement.clientHeight;
	}
	
	
}
