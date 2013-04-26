{include file="admin/inc/header.tpl"}
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
</script>
{/literal}
    {include file="admin/inc/table_header.tpl" filter=0 paging=""}
    {include file="admin/inc/intable_header.tpl" header="Search" color="Gray"}
        <tr>
			<td nowrap="nowrap">Search string:</td>
			<td><input type="text" name="search" class="text" id="search" value="{$search}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Objects:</td>
			<td>
				<select class="text" name="obj_filter">
					<option {if !$obj_filter}selected{/if} value="">All</option>
					<option {if $obj_filter == 'DOMAIN'}selected{/if} value="DOMAIN">Domains</option>
					<option {if $obj_filter == 'CONTACT'}selected{/if} value="CONTACT">Contacts</option>
					<option {if $obj_filter == 'HOST'}selected{/if} value="HOST">Hosts</option>
				</select>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap">Operations:</td>
			<td>
				<select class="text" name="op_filter">
					<option {if !$op_filter}selected{/if} value="">All</option>
					<option {if $op_filter == 'CREATE'}selected{/if} value="CREATE">Create</option>
					<option {if $op_filter == 'UPDATE'}selected{/if} value="UPDATE">Update</option>
					<option {if $op_filter == 'DELETE'}selected{/if} value="DELETE">Delete</option>
					<option {if $op_filter == 'RENEW'}selected{/if} value="RENEW">Renew</option>
					<option {if $op_filter == 'TRADE'}selected{/if} value="TRADE">Owner change (Trade)</option>
					<option {if $op_filter == 'TRANSFER-REQUEST'}selected{/if} value="TRANSFER-REQUEST">Transfer request</option>
					<option {if $op_filter == 'TRANSFER-APPROVE'}selected{/if} value="TRANSFER-APPROVE">Transfer approve</option>
					<option {if $op_filter == 'TRANSFER-DECLINE'}selected{/if} value="TRANSFER-DECLINE">Transfer decline</option>
				</select>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap">Date:</td>
			<td>
			<input name="dt" style="vertical-align:middle;" type="text" class="text" id="dt" value="{$dt}">
			<input name="reset" style="vertical-align:middle;" type="reset" class="btn" onclick="return showCalendar('dt', 'mm/dd/y');" value=" ... ">
			</td>
		</tr>
    {include file="admin/inc/intable_footer.tpl" color="Gray"}
    {include file="admin/inc/table_footer.tpl" colspan=9 button2=1 button2_name="Search" page_data_options=false}
    <br>
   	{include file="admin/inc/table_header.tpl" filter=0}
		<table class="Webta_Items" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items">
		<thead>
			<tr>
				<th width='200'>Date</th>
				<th>Object type</th>
				<th>Object name</th>
				<th>Operation</th>
			</tr>
		</thead>
		<tbody>
		{section name=id loop=$rows}
		<tr id='tr_{$smarty.section.id.iteration}'>
			<td class="Item" nowrap="nowrap" valign="top">{$rows[id].dtadded}</td>
			<td class="Item" valign="top">{$rows[id].type}</td>
			<td class="Item" valign="top">{$rows[id].object}</td>
			<td class="Item" width="1%" nowrap valign="top">{$rows[id].operation} {if $rows[id].operation == "Update"}[<a href="history_details.php?id={$rows[id].id}">View changes</a>]{/if}</td>
		</tr>
		{sectionelse}
		<tr>
			<td colspan="6" align="center">{t}No history entries found{/t}</td>
		</tr>
		{/section}
		<tr>
			<td colspan="5" align="center">&nbsp;</td>
		</tr>
		</tbody>
		</table>
	{include file="admin/inc/table_footer.tpl" colspan=9 disable_footer_line=1}
	<br>
{include file="admin/inc/footer.tpl"}