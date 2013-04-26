var Tooltip = Class.create();
Tooltip.prototype = {
  initialize: function(element, tool_tip) {
    var options = Object.extend({
      default_css: false,
      margin: "0px",
	  padding: "5px",
	  backgroundColor: "#d6d6fc",
	  delta_x: 5,
	  delta_y: 5,
      zindex: 1000
    }, arguments[1] || {});
    
    this.element      = $(element);
    this.tool_tip     = $(tool_tip);

    this.options      = options;

    // hide the tool-tip by default
    this.tool_tip.hide();

    this.eventMouseOver = this.showTooltip.bindAsEventListener(this);
    this.eventMouseOut   = this.hideTooltip.bindAsEventListener(this);

    this.registerEvents();
  },

  destroy: function() {
    Event.stopObserving(this.element, "mouseover", this.eventMouseOver);
    Event.stopObserving(this.element, "mouseout", this.eventMouseOut);
  },

  registerEvents: function() {
    Event.observe(this.element, "mouseover", this.eventMouseOver);
    Event.observe(this.element, "mouseout", this.eventMouseOut);
  },

  showTooltip: function(event){
	Event.stop(event);
	// get Mouse position
    var mouse_x = Event.pointerX(event);
	var mouse_y = Event.pointerY(event);
	
	
	// decide if wee need to switch sides for the tooltip
	var dimensions = Element.getDimensions( this.tool_tip );
	var element_width = dimensions.width;
	var element_height = dimensions.height;
	
	if ( (element_width + mouse_x) >= ( this.getWindowWidth() - this.options.delta_x) ){ // too big for X
		mouse_x = mouse_x - element_width;
		// apply delta to make sure that the mouse is not on the tool-tip
		mouse_x = mouse_x - this.options.delta_x;
	} else {
		mouse_x = mouse_x + this.options.delta_x;
	}
	
	if ( (element_height + mouse_y) >= ( this.getWindowHeight() - this.options.delta_y) ){ // too big for Y
		mouse_y = mouse_y - element_height;
	    // apply delta to make sure that the mouse is not on the tool-tip
		mouse_y = mouse_y - this.options.delta_y;
	} else {
		mouse_y = mouse_y + this.options.delta_y;
	} 
	
	// now set the right styles
	this.setStyles(mouse_x, mouse_y);
	
		
	// finally show the Tooltip
	//new Effect.Appear(this.tool_tip);
	new Element.show(this.tool_tip);

  },
  
  setStyles: function(x, y){
    // set the right styles to position the tool tip
	Element.setStyle(this.tool_tip, { position:'absolute',
	 								  top:y + "px",
	 								  left:x + "px",
									  zindex:this.options.zindex
	 								});
	
	// apply default theme if wanted
	if (this.options.default_css){
	  	Element.setStyle(this.tool_tip, { margin:this.options.margin,
		 								  padding:this.options.padding,
		                                  backgroundColor:this.options.backgroundColor,
										  zindex:this.options.zindex
		 								});	
	}	
  },

  hideTooltip: function(event){
	//new Effect.Fade(this.tool_tip);
	new Element.hide(this.tool_tip);
  },

  getWindowHeight: function(){
    var innerHeight;
	if (navigator.appVersion.indexOf('MSIE')>0) {
		innerHeight = document.body.clientHeight;
    } else {
		innerHeight = window.innerHeight;
    }
    return innerHeight;	
  },
 
  getWindowWidth: function(){
    var innerWidth;
	if (navigator.appVersion.indexOf('MSIE')>0) {
		innerWidth = document.body.clientWidth;
    } else {
		innerWidth = window.innerWidth;
    }
    return innerWidth;	
  }

}