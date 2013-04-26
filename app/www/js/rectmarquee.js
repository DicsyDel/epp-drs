/**
*	RECTANGULAR MARQUEE TOOL 
*	allow you select rectangles and operate with selected space.
*	
*	Requirements: Prototype Library version 1.5.0 and higher (http://prototype.conio.net/)
*	Author:	Koksharov Sergey (web2.0.dev@gmail.com)
*	Version: 1.1.0
*
*	RECTANGULAR MARQUEE TOOL is freely distributable 
*	under the terms of an MIT-style license.
*	For details, see the Web 2.0 Dev web site: http://web2dev.net/
*/


var Marquee = Class.create();
Marquee.prototype = {
	options:	{},
	active:		false,
	moving:		false,
	dims:		[0,0],
	noedges:	false,
	stopped:	false,
	startingCoords:	{leftTop: [0,0], rightBottom: [0,0], dims: [0,0]},
	coords:		{leftTop: [0,0], rightBottom: [0,0], dims: [0,0]},
	_focused:	null,
	
	// element types
	BASE:		1,
	MARQUEE:	2,
	OPACITY:	4,
	SHROUD:		8,
	EDGE:		16,
	
	initialize: function(element) {
		var element = $(element);
		if (!element) return;
		
		if (element.tagName == 'DIV') {
			Element.setStyle(element, {
				position: 'relative'
			});
			
		} else if (element.tagName == 'IMG') {
			var elClone = document.createElement('DIV');
			Element.setStyle(elClone, {
				width: element.offsetWidth + 'px',
				height: element.offsetHeight + 'px',
				background: 'url('+ element.src +') no-repeat',
				position: 'relative'
			});
			element.parentNode.appendChild(elClone);
			element.parentNode.removeChild(element);
			
			element = elClone;
		} else {
			alert('Allowed tags for marqueetool are only DIV and IMG tags.');
			return;
		}
		
		var options = Object.extend({
			element:		element,
			start:			true,
			marqueeClass:	'marquee',
			opacityClass:	'marquee-opacity',
			opacityFalse:	'marquee-empty',
			opacityColor:	'#00f',
			shroudClass:	'marquee-full',
			marqueeType:	'rectangle',			// window or rectangle
			edgeClass:		'marquee-edge',
			tooltipClass:	'marquee-tooltip',
			minEdgeWidth:	5,
			minEdgeHeight:	5,
			showTooltip:	true,
			disableHotKeys: false,
			onlyMove:		false,
			disabled: 		false
		}, arguments[1] || {});
		
		var marquee = document.createElement('DIV');
			marquee.className = options.marqueeClass;
			marquee.mtype = this.MARQUEE;
		this.marquee = marquee;

		element.appendChild(marquee);
		var insertable = element;
		insertable.mtype = this.BASE;
		
		this.options = options; 
				
		this.opacityDiv1 = marquee.cloneNode(true);
		this.opacityDiv1.className = options.opacityClass;
		this.opacityDiv1.style.display = 'none';
		this.opacityDiv2 = this.opacityDiv1.cloneNode(true);
		this.opacityDiv3 = this.opacityDiv1.cloneNode(true);
		this.opacityDiv4 = this.opacityDiv1.cloneNode(true);
		this.opacityDiv1.mtype = this.opacityDiv2.mtype = 
		this.opacityDiv3.mtype = this.opacityDiv4.mtype = this.OPACITY;
		insertable.appendChild(this.opacityDiv1);
		insertable.appendChild(this.opacityDiv2);
		insertable.appendChild(this.opacityDiv3);
		insertable.appendChild(this.opacityDiv4);
		
		this.dims = Element.getDimensions(options.element);
		this._offset = Position.cumulativeOffset(options.element);

		this.shroud = marquee.cloneNode(true);
		this.shroud.className = options.shroudClass;
		this.shroud.mtype = this.SHROUD;
		Element.setStyle(this.shroud, {
			width: this.dims.width + 'px', 
			height: this.dims.height + 'px', 
			display: 'block', 
			zIndex: 10
		});
		
		insertable.appendChild(this.shroud);
		this.createEdges(insertable);
		
		if (options.showTooltip) {
			this.tooltip = document.createElement('DIV');
			this.tooltip.className = options.tooltipClass;
			insertable.appendChild(this.tooltip);
		}
		
		if (options.start) 
			this.startListening();
	},
	
	setShroudColor: function(color) {
		this.options.opacityColor = color;
		this.endDrag();
	},
	
	startListening: function() {
		if (!this.options.element) return;
		
		// for dragging marquee
		Event.observe(document, "mousedown",	this.initDrag.bindAsEventListener(this));
		Event.observe(document, "mouseup", 		this.endDrag.bindAsEventListener(this));
		Event.observe(document, "mousemove",	this.updateDrag.bindAsEventListener(this));
		Event.observe(this.options.element, "selectstart", this.bibbFunction.bindAsEventListener(this));
		Event.observe(this.marquee, "selectstart", this.bibbFunction.bindAsEventListener(this));
		if (!this.options.disableHotKeys)
			Event.observe(document, "keydown",		this.keyPress.bindAsEventListener(this));
		
		// for moving marquee
		Event.observe(this.marquee, "mousedown",this.initMove.bindAsEventListener(this));
		Event.observe(document, "mouseup",		this.endMove.bindAsEventListener(this));
		Event.observe(this.options.element, "mousemove",	this.move.bindAsEventListener(this));
		
		// for showing info tooltip
		Event.observe(this.marquee, "mousemove",this.onMarqueeOver.bindAsEventListener(this));
		Event.observe(this.marquee, "mouseout",this.onMarqueeOut.bindAsEventListener(this));
		
		// focused element handler
		Event.observe(document, "focus", this._addFocusedElement.bindAsEventListener(this));
	},
	
	bibbFunction: function(event) {
		Event.stop(event);
		return false;
	},
	
	initDrag: function(event) {
		if (this.options.onlyMove || this.options.disabled) return;
		var src = Event.element(event);
		if (src.tagName && src.tagName != 'DIV' || !(src.mtype & (this.BASE | this.OPACITY | this.SHROUD))) return;
		if (!this._beforeUpdate(event)) return;
		
		if (!(src.mtype & this.EDGE)) this.hideEdges();

		var pointer = [Event.pointerX(event), Event.pointerY(event)];

		var pos     = Position.positionedOffset(this.options.element);
		this.offset = [0,1].map( function(i) { return (pointer[i] - pos[i]) });

		this.active = true;

		this.marquee.className =  this.options.marqueeClass;
		Element.setStyle(this.marquee, {backgroundColor: '', cursor: '', display: 'block'});
		
		this._setOpacities();
		Event.stop(event);
	},
	
	_setOpacities: function() {
		Element.setStyle(this.opacityDiv1, {
			left:	0,
			top:	0,
			width:	this.dims.width + 'px',
			height:	0,
			display: 'none'
		});

		Element.setStyle(this.opacityDiv2, {
			left:	0,
			top:	0,
			width:	0,
			height:	0,
			display: 'none'
		});

		Element.setStyle(this.opacityDiv3, {
			top:	0,
			right:	0,
			width:	0,
			height:	0,
			display: 'none'
		});

		Element.setStyle(this.opacityDiv4, {
			left: 	0,
			top:	0,
			width:	this.dims.width + 'px',
			height:	0,
			display: 'none'
		});
	},
	
	endDrag: function(event) {
		var opacityDivs = [this.opacityDiv1, this.opacityDiv2, this.opacityDiv3, this.opacityDiv4];
		
		switch (this.options.marqueeType) {
			case 'rectangle':
				this.marquee.className =  this.options.marqueeClass +' '+ this.options.opacityClass;
				this.marquee.style.backgroundColor = this.options.opacityColor;
				this.marquee.style.cursor = 'move';
				opacityDivs.each((function (Item){
					Item.className = this.options.opacityFalse;
				}).bind(this));
				break;
			case 'window':
				this.marquee.style.backgroundColor = '';
				this.marquee.className =  this.options.marqueeClass;
				this.marquee.style.cursor = '';
				opacityDivs.each((function (Item){
					Item.className = this.options.opacityClass;
					Item.style.backgroundColor = this.options.opacityColor;
				}).bind(this));
				break;
		}
		
		opacityDivs.each((function (Item){
			Item.style.display = 'block';
		}).bind(this));
		this.updateMarquee();
		this.showEdges();
		if (this.active || this.moving || this.dragedge)
			this._finishUpdates(event);
		this.active = false;
	},
	
	updateDrag: function(event) {
		if (!this.active || this.stopped) return;
		
	    var pointer = [Event.pointerX(event) - this.options.element.offsetLeft, 
						Event.pointerY(event) - this.options.element.offsetTop];

		if (pointer[0] > this.dims.width)  pointer[0] = this.dims.width;
		if (pointer[1] > this.dims.height) pointer[1] = this.dims.height;
		if (pointer[0] < 0)  pointer[0] = 0;
		if (pointer[1] < 0)  pointer[1] = 0;
		
		var width	= this.offset[0] - pointer[0];
		var height	= this.offset[1] - pointer[1];
		
		if (event.shiftKey) {
			width = height = Math.min(width, height);
		}
		
		this.setCoords(
			(width < 0)  ? this.offset[0] : pointer[0],
			(height < 0) ? this.offset[1] : pointer[1],
			width, 
			height
		);
		this.updateMarquee();
	},
	
	setCoords: function(x, y, w, h) {
		var coords = {
			leftTop:	[x, y],
			rightBottom:[x + Math.abs(w), y + Math.abs(h)],
			dims:		[Math.abs(w), Math.abs(h)]
		};

		this.coords = coords;
	},
	
	getCoords: function() {
		return this.coords;
	},
	
	setMarqueeCoords: function(coords) {
		if (!coords) coords = this.coords;
		
		Element.setStyle(this.marquee, {
			left: 	coords.leftTop[0] + 'px',
			top:	coords.leftTop[1] + 'px',
			width:	coords.dims[0] + 'px',
			height:	coords.dims[1] + 'px'
		});
	},
	
	updateMarquee: function(coords) {
		if (!coords) coords = this.coords;
		
		var top = coords.leftTop[1];
		var left = coords.leftTop[0];
		var right = this.dims.width - coords.rightBottom[0] - 2;
		var bottom = this.dims.height - coords.rightBottom[1] - 2;
		if (right < 0) right = 0;
		if (bottom < 0) bottom = 0;
		
		this.setMarqueeCoords();
		this.updateTootip(left, top, coords.dims[0], coords.dims[1]);
		
		/* Opacity Div's */
		if (this.options.marqueeType == 'rectangle') return;

		this.opacityDiv1.style.height = top + 'px';

		Element.setStyle(this.opacityDiv2, {
			top:	top + 'px',
			width:	left + 'px',
			height:	coords.dims[1] + 2 + 'px'
		});

		Element.setStyle(this.opacityDiv3, {
			top:	top + 'px',
			width:	right + 'px',
			height:	coords.dims[1] + 2 + 'px'
		});

		Element.setStyle(this.opacityDiv4, {
			top:	top + coords.dims[1] + 2 + 'px',
			height:	bottom + 'px'
		});

		/* end of opacity div's */
	},
	
	keyPress: function(event) {
		if (this.options.disableHotKeys || this.options.disabled) return;
		var key = event.keyCode || event.which || event.button;

		switch (key) {
			case 73:
				// i - inverse opacity type (selection)
				if (event.shiftKey)
					this.inverse();
				break;
			case 37:
				// left arrow
				this.moveLeft(event.shiftKey ? 10 : 1, event.ctrlKey);
				break;
			case 38:
				// top arrow
				this.moveTop(event.shiftKey ? 10 : 1, event.ctrlKey);
				break;
			case 39:
				// right arrow
				this.moveRight(event.shiftKey ? 10 : 1, event.ctrlKey);
				break;
			case 40:
				// down arrow
				this.moveBottom(event.shiftKey ? 10 : 1, event.ctrlKey);
				break;
			case 27:
				// escape
				this.unselectAll();
				break;
			case 49: case 50: case 51:
			case 52: case 53: case 54:
			case 55: case 56: case 57: case 48:
				// numbers
				var colors = ['00f', '000', 'ffc', 'cfc', '0f0', '0ff', 'f00', 'f0f', 'ff0', 'fff'];
				
				if (event.shiftKey) {
					this.options.opacityColor = '#' + colors[key - 48];
					this.endDrag();
				}
				break;
			case 82:
				var numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
				// r - random
				if (event.shiftKey) {
					var r1 = Math.round(Math.random()*15);
					var r2 = Math.round(Math.random()*15);
					var r3 = Math.round(Math.random()*15);
					this.options.opacityColor = '#' + numbers[r1] + numbers[r2] + numbers[r3];
					this.endDrag();
				}
				break;
			case 65:
				// a - select all
				if (event.shiftKey)
					this.selectAll();
				break;
			case 78:
				// n - new, select none
				if (event.shiftKey) this.unselectAll();
				break;
			
			case 69:
				// e - edges switching
				if (event.shiftKey)
					if (this.noedges) {
						this.noedges = false;
						this.showEdges();
					} else {
						this.noedges = true;
						this.hideEdges();
					}
				break;
			
			case 90:
				// z - zoom
				if (event.shiftKey) 
					this.zoom(1, event.ctrlKey);
				break;
		}

	},
	
	inverse: function() {
		this.options.marqueeType = this.options.marqueeType != 'window' ? 'window' : 'rectangle';
		this.endDrag();
	},
	
	moveLeft: function(amount, ctrlKey) {
		if (!this.coords.leftTop) return;
		
		if (!ctrlKey) {
			this.coords.leftTop[0] -= amount;
			this.coords.rightBottom[0] -= amount;
			
			if (this.coords.leftTop[0] < 0) {
				this.coords.rightBottom[0] = this.coords.dims[0];
				this.coords.leftTop[0] = 0;
			}
		} else {
			this.coords.rightBottom[0] -= amount;
			this.coords.dims[0] -= amount;
			this.validateCoords();
		}
		
		this.updateMarquee();
		this.showEdges();
	},
	
	moveTop: function(amount, ctrlKey) {
		if (!this.coords.leftTop) return;
		
		if (!ctrlKey) {
			this.coords.leftTop[1] -= amount;
			this.coords.rightBottom[1] -= amount;
			
			if (this.coords.leftTop[1] < 0) {
				this.coords.rightBottom[1] = this.coords.dims[1];
				this.coords.leftTop[1] = 0;
			}
		} else {
			this.coords.dims[1] -= amount;
			this.coords.rightBottom[1] -= amount;
			this.validateCoords();
		}

		this.updateMarquee();
		this.showEdges();
	},
	
	moveRight: function(amount, ctrlKey) {
		if (!this.coords.leftTop) return;
		
		if (!ctrlKey) {
			this.coords.leftTop[0] += amount;
			this.coords.rightBottom[0] += amount;
			
			if (this.coords.rightBottom[0] > this.dims.width) {
				this.coords.rightBottom[0] = this.dims.width;
				this.coords.leftTop[0] = this.dims.width - this.coords.dims[0];
			}
		} else {
			this.coords.dims[0] += amount;
			this.coords.rightBottom[0] += amount;
			this.validateCoords();
		}

		this.updateMarquee();
		this.showEdges();
	},
	
	moveBottom: function(amount, ctrlKey) {
		if (!this.coords.leftTop) return;
		
		if (!ctrlKey) {
			this.coords.leftTop[1] += amount;
			this.coords.rightBottom[1] += amount;
			
			if (this.coords.rightBottom[1] > this.dims.height) {
				this.coords.rightBottom[1] = this.dims.height;
				this.coords.leftTop[1] = this.dims.height - this.coords.dims[1];
			}
		} else {
			this.coords.dims[1] += amount;
			this.coords.rightBottom[1] += amount;
			this.validateCoords();
		}

		this.updateMarquee();
		this.showEdges();
	},
	
	zoom: function(amount, ctrlKey) {
		if (!this.coords.leftTop) return;
		if (ctrlKey) amount = - amount;
		
		this.coords.dims[0] += 2 * amount;
		this.coords.dims[1] += 2 * amount;
		if ((this.coords.dims[0] < this.options.minEdgeWidth) || 
			(this.coords.dims[1] < this.options.minEdgeHeight)) {
			this.coords.dims[0] -= 2 * amount;
			this.coords.dims[1] -= 2 * amount;
			return;
		}
		
		this.coords.leftTop[0] -= amount;
		this.coords.leftTop[1] -= amount;
		this.coords.rightBottom[0] += amount;
		this.coords.rightBottom[1] += amount;
		 
		this.validateCoords();
		this.updateMarquee();
		this.showEdges();
	},
	
	selectAll: function() {
		this.setCoords(0,0, this.dims.width, this.dims.height);
		this.updateMarquee();
		Element.setStyle(this.marquee, {backgroundColor: '', cursor: '', display: 'block'});
		
		this.endDrag();
	},
	
	unselectAll: function() {
		this.setCoords(0,0, 0,0);
		this.updateMarquee();
		Element.setStyle(this.marquee, {backgroundColor: '', cursor: '', display: 'none'});
		
		var opacityDivs = [this.opacityDiv1, this.opacityDiv2, this.opacityDiv3, this.opacityDiv4];
		opacityDivs.each((function (Item){
			Item.style.display = 'none';
		}).bind(this));
		
		this.hideEdges();
	},
	
	initMove: function(event) {
		if (this.options.disabled) return;
		var src = Event.element(event);
		if (src.tagName && src.tagName != 'DIV' || !(src.mtype & this.MARQUEE)) return;
		if (!this._beforeUpdate(event)) return;
		
		this.moving = true;
		this.startMovingPoint = [Event.pointerX(event), Event.pointerY(event)];
		this.indent = [this.startMovingPoint[0] - this.coords.leftTop[0], this.startMovingPoint[1] - this.coords.leftTop[1]];
		this.hideEdges();
	},
	
	endMove: function(event) {
		this.moving = false;
	},
	
	move: function(event) {
		if (!this.moving) return;

		// fix for IE - onmousemove very frequent listening
		this._currentTime = new Date();
		if ((this._currentTime - this._lastUpdate) < 32)
			return;
		this._lastUpdate = new Date();
		
	    var pointer = [Event.pointerX(event), Event.pointerY(event)];
		if (pointer[0] - this.indent[0] < 0)  pointer[0] = this.indent[0];
		if (pointer[1] - this.indent[1] < 0)  pointer[1] = this.indent[1];

		var shifting = [pointer[0] - this.startMovingPoint[0], pointer[1] - this.startMovingPoint[1]];
		var points	 = [Math.abs(shifting[0]), Math.abs(shifting[1])];
		
		
		this.coords.leftTop = [shifting[0] < 0 ? this.coords.leftTop[0] - points[0] : this.coords.leftTop[0] + points[0],
						shifting[1] < 0 ? this.coords.leftTop[1] - points[1] : this.coords.leftTop[1] + points[1]];
		this.coords.rightBottom = [this.coords.leftTop[0] + this.coords.dims[0], this.coords.leftTop[1] + this.coords.dims[1]];
		
		if (this.coords.leftTop[0] < 0) {
			this.coords.rightBottom[0] = this.coords.dims[0];
			this.coords.leftTop[0] = 0;
		}

		if (this.coords.leftTop[1] < 0) {
			this.coords.rightBottom[1] = this.coords.dims[1];
			this.coords.leftTop[1] = 0;
		}

		if (this.coords.rightBottom[0] > this.dims.width) {
			this.coords.rightBottom[0] = this.dims.width;
			this.coords.leftTop[0] = this.dims.width - this.coords.dims[0];
		}

		if (this.coords.rightBottom[1] > this.dims.height) {
			this.coords.rightBottom[1] = this.dims.height;
			this.coords.leftTop[1] = this.dims.height - this.coords.dims[1];
		}

		this.startMovingPoint = pointer;
		this.updateMarquee();
	},
	
	hideEdges: function() {
		if (this.edge) this.edge.style.display = 'none';
	},
	
	showEdges: function() {
		if (this.options.onlyMove || this.options.disabled || !this.edge || this.noedges) return;
		
		this.edge.style.display = 'block';
		this.edge1.style.left = this.coords.leftTop[0] + 'px';
		this.edge1.style.top = this.coords.leftTop[1] + 'px';
		
		this.edge2.style.left = this.coords.rightBottom[0] + 'px';
		this.edge2.style.top = this.coords.leftTop[1] + 'px';
		
		this.edge3.style.left = this.coords.leftTop[0] + 'px';
		this.edge3.style.top = this.coords.rightBottom[1] + 'px';
		
		this.edge4.style.left = this.coords.rightBottom[0] + 'px';
		this.edge4.style.top = this.coords.rightBottom[1] + 'px';
	},
	
	createEdges: function(insertable) {
		var edge = document.createElement('DIV');
			edge.className = this.options.edgeClass;
			edge.mtype = this.EDGE;
		
		this.edge1 = edge.cloneNode(true);
		this.edge1.style.cursor = 'nw-resize';
		this.edge1.num = 1;
		this.edge1.onmousedown = this.startDragEdge.bindAsEventListener(this);
		
		this.edge2 = edge.cloneNode(true);
		this.edge2.style.cursor = 'ne-resize';
		this.edge2.num = 2;
		this.edge2.onmousedown = this.startDragEdge.bindAsEventListener(this);
		
		this.edge3 = edge.cloneNode(true);
		this.edge3.style.cursor = 'ne-resize';
		this.edge3.num = 3;
		this.edge3.onmousedown = this.startDragEdge.bindAsEventListener(this);
		
		this.edge4 = edge.cloneNode(true);
		this.edge4.style.cursor = 'nw-resize';
		this.edge4.num = 4;
		this.edge4.onmousedown = this.startDragEdge.bindAsEventListener(this);

		Event.observe(document, "mousemove",	this.dragEdge.bindAsEventListener(this));
		Event.observe(document, "mouseup",	this.endDragEdge.bindAsEventListener(this));
		
			edge.className = '';
			edge.style.display = 'none';
			this.edge = edge;
			
		edge.appendChild(this.edge1);
		edge.appendChild(this.edge2);
		edge.appendChild(this.edge3);
		edge.appendChild(this.edge4);
		
		this.edge1.mtype = this.EDGE;
		this.edge2.mtype = this.EDGE;
		this.edge3.mtype = this.EDGE;
		this.edge4.mtype = this.EDGE;
		insertable.appendChild(edge);
	},
	
	startDragEdge: function(event) {
		if (this.options.onlyMove || this.options.disabled) return;
		var element = Event.element(event);
		if (element.mtype != this.EDGE) return;
		if (!this._beforeUpdate(event)) return;
		
		this.startEdgePoint = [Event.pointerX(event), Event.pointerY(event)];
		this.storedCoords = Object.extend({}, this.coords);
		this.dragedge = true;
		this.activeEdge = element.num;
	},
	
	dragEdge: function(event) {
		if (!this.dragedge) return;
		
		// fix for IE - onmousemove very frequent listening
		this._currentTime = new Date();
		if ((this._currentTime - this._lastUpdate) < 32)
			return;
		this._lastUpdate = new Date();
		
		var pointer = [Event.pointerX(event), Event.pointerY(event)];
		var shifting = [pointer[0] - this.startEdgePoint[0], pointer[1] - this.startEdgePoint[1]];
		
		switch (this.activeEdge) {
			case 4:
				var width = (this.storedCoords.dims[0]+shifting[0] >= this.options.minEdgeWidth) 
							? this.storedCoords.dims[0]+shifting[0] : this.options.minEdgeWidth;
				var height = (this.storedCoords.dims[1]+shifting[1] >= this.options.minEdgeHeight) 
							? this.storedCoords.dims[1]+shifting[1] : this.options.minEdgeHeight;
				
				if (event.shiftKey)
					width = height = Math.min(width, height);
		
				this.coords.dims = [width, height];
				this.coords.rightBottom = [this.coords.leftTop[0] + this.coords.dims[0], 
											this.coords.leftTop[1] + this.coords.dims[1]];
				break;
				
			case 3:
				var width = (this.storedCoords.dims[0]-shifting[0] >= this.options.minEdgeWidth) 
							? this.storedCoords.dims[0]-shifting[0] : this.options.minEdgeWidth;
				var height = (this.storedCoords.dims[1]+shifting[1] >= this.options.minEdgeHeight) 
							? this.storedCoords.dims[1]+shifting[1] : this.options.minEdgeHeight;
				
				if (event.shiftKey)
					width = height = Math.min(width, height);
		
				this.coords.dims = [width, height];
				this.coords.leftTop[0] = this.coords.rightBottom[0] - this.coords.dims[0];
				this.coords.rightBottom[1] = this.coords.leftTop[1] + this.coords.dims[1];
				break;
			
			case 2:
				var width = (this.storedCoords.dims[0]+shifting[0] >= this.options.minEdgeWidth) 
							? this.storedCoords.dims[0]+shifting[0] : this.options.minEdgeWidth;
				var height = (this.storedCoords.dims[1]-shifting[1] >= this.options.minEdgeHeight) 
							? this.storedCoords.dims[1]-shifting[1] : this.options.minEdgeHeight;
				
				if (event.shiftKey)
					width = height = Math.min(width, height);
		
				this.coords.dims = [width, height];
				this.coords.leftTop[1] = this.coords.rightBottom[1] - this.coords.dims[1];
				this.coords.rightBottom[0] = this.coords.leftTop[0] + this.coords.dims[0];
				break;
				
			case 1:
				var width = (this.storedCoords.dims[0]-shifting[0] >= this.options.minEdgeWidth) 
							? this.storedCoords.dims[0]-shifting[0] : this.options.minEdgeWidth;
				var height = (this.storedCoords.dims[1]-shifting[1] >= this.options.minEdgeHeight) 
							? this.storedCoords.dims[1]-shifting[1] : this.options.minEdgeHeight;
				
				if (event.shiftKey)
					width = height = Math.min(width, height);
		
				this.coords.dims = [width, height];
				this.coords.leftTop = [this.coords.rightBottom[0] - this.coords.dims[0],
									   this.coords.rightBottom[1] - this.coords.dims[1]];
				break;
		}
		
		this.validateCoords();
		this.updateMarquee();
		this.showEdges();
	},
	
	validateCoords: function() {
		if (this.coords.dims[0] < this.options.minEdgeWidth) {
			this.coords.dims[0] = this.options.minEdgeWidth;
			this.coords.rightBottom[0] = this.coords.leftTop[0] + this.options.minEdgeWidth;
		}
		
		if (this.coords.dims[1] < this.options.minEdgeHeight) {
			this.coords.dims[1] = this.options.minEdgeHeight;
			this.coords.rightBottom[1] = this.coords.leftTop[1] + this.options.minEdgeHeight;
		}
		
		if (this.coords.leftTop[0] + this.coords.dims[0] > this.dims.width) {
			this.coords.rightBottom[0] = this.dims.width;
			this.coords.dims[0] = this.dims.width - this.coords.leftTop[0];
		}

		if (this.coords.leftTop[1] + this.coords.dims[1] > this.dims.height) {
			this.coords.rightBottom[1] = this.dims.height;
			this.coords.dims[1] = this.dims.height - this.coords.leftTop[1];
		}

		if (this.coords.leftTop[0] < 0) {
			this.coords.leftTop[0] = 0;
			this.coords.dims[0] = this.coords.rightBottom[0];
		}

		if (this.coords.leftTop[1] < 0) {
			this.coords.leftTop[1] = 0;
			this.coords.dims[1] = this.coords.rightBottom[1];
		}
	},
	
	endDragEdge: function(event) {
		this.dragedge = false;
		this.activeEdge = 0;
		Event.stop(event);
	},
	
	onMarqueeOver: function(event) {
		if (!this.tooltip) return;
		if (this.active || this.moving || this.dragedge) {
			this.tooltip.style.display = 'none';
			return;
		}
		
		Element.setStyle(this.tooltip, {
			display: 'block',
			left: Event.pointerX(event) - this._offset[0] + 4 + 'px',
			top: Event.pointerY(event) - this._offset[1] + 14 + 'px'
		});
	},
	
	onMarqueeOut: function(event) {
		if (!this.tooltip) return;
		if (this._cron_tt)
			clearTimeout(this._cron_tt);
		
		this._cron_tt = setTimeout((function(){
			this.tooltip.style.display = 'none';
		}).bind(this), 150);
	},
	
	updateTootip: function(x, y, w, h) {
		if (!this.tooltip) return;
		this.tooltip.innerHTML = x + ',' + y + ' ' + w + 'x' + h;
	},
	
	showMarquee: function(x, y, w, h) {
		this._setOpacities();
		
		if (x != undefined && y != undefined && w != undefined && h != undefined)
			this.setCoords(x, y, w, h);
		this.updateMarquee();
		Element.setStyle(this.marquee, {backgroundColor: '', cursor: '', display: 'block'});
		
		this.endDrag();
	},
	
	setOnFinishDragCallback: function(callback) {
		this.onFinishDragCallback = callback;
	},
	
	_finishUpdates: function(event) {
		if (!event) return;
		
		if (typeof(this.onFinishDragCallback) == 'function')
			this.onFinishDragCallback(
				this.coords.leftTop[0], this.coords.leftTop[1], 
				this.coords.dims[0], this.coords.dims[1]
			);
	},
	
	setOnBeforeDragCallback: function(callback) {
		this.onBeforeDragCallback = callback;
	},
	
	_beforeUpdate: function(event) {
		if (!event) return;
		var src = Event.element(event);
		
		if (typeof(this.onBeforeDragCallback) == 'function')
			return this.onBeforeDragCallback(src.mtype, 
					this.coords.leftTop[0], this.coords.leftTop[1], 
					this.coords.dims[0], this.coords.dims[1]
			);
		return true;
	},
	
	accessResize: function() {
		this.options.onlyMove = !arguments[0];
	},
	
	accessHotKeys: function() {
		this.options.disableHotKeys = !arguments[0];
	},
	
	accessMarquee: function() {
		this.options.disabled = true;
	},
	
	_addFocusedElement: function(event) {
		this._focused = Event.element(event);
	},
	
	_getFocusedElement: function() {
		return this._focused;
	}
	
};
