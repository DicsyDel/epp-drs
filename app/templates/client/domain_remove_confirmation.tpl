{include file="client/inc/header.tpl"}
{literal}
<link rel="stylesheet" type="text/css" media="all" href="/css/calendar.css"  />
<script type="text/javascript" src="/js/calendar/calendar.js"></script>
<script type="text/javascript" src="/js/calendar/calendar-en.js"></script>
<script type="text/javascript">
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
function showCalendar(id, format) {
  var el = document.getElementById(id);
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

var MINUTE = 60 * 1000;
var HOUR = 60 * MINUTE;
var DAY = 24 * HOUR;
var WEEK = 7 * DAY;

function isDisabled(date) {
  var today = new Date();
  return (Math.abs(date.getTime() - today.getTime()) / DAY) > 10;
}

function toggleSchduledDelete (ev) {
	var target = ev.target || ev.srcElement;
	$('sd_date_reset').disabled = !target.checked;
	$('sd_date').disabled = !target.checked;
	$('sd_label').className = !target.checked ? "disabled" : "";
}
</script>

{/literal}


	{include file="client/inc/table_header.tpl"}
		{php}
	    	// Do not edit PHP code below!
	    	$this->assign('intable_header',_("Confirmation required"));
	    {/php}
	
		{include file="client/inc/intable_header.tpl" header=$intable_header color="Gray"}
    	<tr>
    		<td colspan="2">
    			{t}Are you sure want delete domain name {$Domain->Name}.{$Domain->Extension}?{/t}
    		</td>
    	</tr>
    	{if $ability.scheduled_delete}
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td colspan="2">
    			<label><input type="checkbox" name="scheduled_delete" value="1" onClick="toggleSchduledDelete(event)">
    			{t}Schedule a domain for deletion at a given date.{/t}
    			</label>
    		</td>
    	</tr>
    	<tr>
			<td id="sd_label" nowrap="nowrap" style="padding-left:30px;" class="disabled">Date:</td>
			<td>
			<input disabled name="sd_date" style="vertical-align:middle;" type="text" class="text" id="sd_date" value="">
			<input disabled name="reset" style="vertical-align:middle;" type="reset" class="btn" id="sd_date_reset" onclick="return showCalendar('sd_date', 'mm/dd/y');" value=" ... ">
			</td>
    	</tr>
    	{/if}
        {include file="client/inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="task" value="delete" />
        <input type="hidden" name="domainid" value="{$Domain->ID}" />
        <input type="hidden" name="confirmed" value="1" />
        {php}
	    	// Do not edit PHP code below!
	    	$this->assign('button2_name',_("Yes, I want to completely delete this domain from registry. I will no longer own it and it will become available for public registration."));
	    {/php}
        
	{include file="client/inc/table_footer.tpl" button2=1}
{include file="client/inc/footer.tpl"}