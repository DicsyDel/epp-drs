
	// This function gets called when the end-user clicks on some date.
	function selected(cal, date) {
	  cal.sel.value = date; // just update the date in the input field.
	  if (cal.sel.id == "sel1" || cal.sel.id == "sel3")
		// if we add this call we close the calendar on single-click.
		// just to exemplify both cases, we are using this only for the 1st
		// and the 3rd field, while 2nd and 4th will still require double-click.
		cal.callCloseHandler();
	}
	
	function closeHandler(cal) {
	  cal.hide();                        // hide the calendar
	}

	var MINUTE = 60 * 1000;
	var HOUR = 60 * MINUTE;
	var DAY = 24 * HOUR;
	var WEEK = 7 * DAY;
	
	function isDisabled(date) {
	  var today = new Date();
	  return (Math.abs(date.getTime() - today.getTime()) / DAY) > 10;
	}

	function showCalendar(id, format) {
	  var el = $(id);
	  if (calendar != null) {
		// we already have some calendar created
		calendar.hide();                 // so we hide it first.
	  } else {
		// first-time call, create the calendar.
		var cal = new Calendar(false, null, selected, closeHandler);
		// uncomment the following line to hide the week numbers
		// cal.weekNumbers = false;
		calendar = cal;                  // remember it in the global var
		cal.setRange(1900, 2070);        // min/max year allowed.
		cal.create();
	  }
	  calendar.setDateFormat(format);    // set the specified date format
	  calendar.parseDate(el.value);      // try to parse the text in field
	  calendar.sel = el;                 // inform it what input field we use
	  calendar.showAtElement(el);        // show the calendar below it
	
	  return false;
	}
