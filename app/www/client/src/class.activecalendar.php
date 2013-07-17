<?php
/*
* @class: activeCalendar
* @project: Active Calendar Class
* @version: 1.0.4 (stable);
* @author: Giorgos Tsiledakis;
* @date: 2005-3-2;
* @copyright: Giorgos Tsiledakis;
* @license: GNU LESSER GENERAL PUBLIC LICENSE;
* Support, feature requests and bug reports please at : http://www.micronetwork.de/activecalendar/
* Special thanks to Corissia S.A (http://www.corissia.com) for the permission to publish the source code
* Thanks to Maik Lindner (http://nifox.com) for his help developing this class

* -------- You may remove all comments below to reduce file size -------- *

* This class generates calendars as a html table (XHTML Valid)
* Supported views: month and year view
* Supported dates:
* 1. Using PHP native date functions (default): 1902-2037 (UNIX) or 1971-2037 (Windows)
* 2. Using ADOdb Date Library : 100-3000 and later [limited by the computation time of adodb_mktime()].
* You can find the ADOdb Date Library at http://phplens.com/phpeverywhere/adodb_date_library
* To use the ADOdb Date Library just include it in your main script. The Active Calendar class will use the library functions automatically.
* Supported features:
* 1. Static calendar without any links
* 2. Calendar with month's or year's view navigation controls
* 3. Calendar with linkable days (url or javascript)
* 4. Calendar with a date picker (year ot month mode)
* 5. Calendar with event days (css configutation) and event links
* 6. Calendar with optionally linkable event contents
* The layout of can be configured using css, as the class generates various html classes
* Please read the readme.html first and check the examples included in this package
*/
class activeCalendar{
/*
----------------------
@START CONFIGURATION
----------------------
*/
/*
********************************************************************************
You can change below the month and day names, according to your language
This is just the default configuration. You may set the month and day names by calling setMonthNames() and setDayNames()
********************************************************************************
*/
var $jan="Jan";
var $feb="Feb";
var $mar="Mar";
var $apr="Apr";
var $may="May";
var $jun="Jun";
var $jul="Jul";
var $aug="Aug";
var $sep="Sep";
var $oct="Oct";
var $nov="Nov";
var $dec="Dec";
var $sun="Sun";
var $mon="Mon";
var $tue="Tue";
var $wed="Wed";
var $thu="Thu";
var $fri="Fri";
var $sat="Sat";
/*
********************************************************************************
You can change below the default year's and month's view navigation controls
********************************************************************************
*/
var $yearNavBack=" &lt;&lt; "; // Previous year, this could be an image link
var $yearNavForw=" &gt;&gt; "; // Next year, this could be an image link
var $monthNavBack="&lt;&lt;"; // Previous month, this could be an image link
var $monthNavForw="<div align=\"right\">&gt;&gt;</div>"; // Next month, this could be an image link
var $selBtn="Go"; // value of the date picker button (if enabled)
var $monthYearDivider=" "; // the divider between month and year in the month`s title
/*
********************************************************************************
$startOnSun = false: first day of week is Monday
$startOnSun = true: first day of week is Sunday
********************************************************************************
*/
var $startOnSun=false;
/*
********************************************************************************
$rowCount : defines the number of months in a row in yearview ( can be also set by the method showYear() )
********************************************************************************
*/
var $rowCount=4;
/*
********************************************************************************
Names of the generated html classes. You may change them to avoid any conflicts with your existing CSS
********************************************************************************
*/
var $cssYearTable="th"; // table tag: calendar year
var $cssYearTitle="th"; // td tag: calendar year title
var $cssYearNav="th"; // td tag: calendar year navigation
var $cssMonthTable=""; // table tag: calendar month
var $cssMonthTitle="th"; // td tag: calendar month title
var $cssMonthNav="th"; // td tag: calendar month navigation
var $cssWeekDay="textHighlights"; // tr tag: calendar weekdays
var $cssPicker="calendar"; // td tag: date picker
var $cssPickerForm="inp"; // form tag: date picker form
var $cssPickerMonth="inp"; // select tag: month picker
var $cssPickerYear="inp"; // select tag: year picker
var $cssPickerButton="btn"; // input (submit) tag: date picker button
var $cssMonthDay="calendarBg"; // td tag: days, that belong to the current month
var $cssNoMonthDay=""; // td tag: days, that do not belong to the current month
var $cssToday="calendarBgToday"; // td tag: the current day
var $cssSelecDay="calendarBgSelected"; // td tag: the selected day
var $cssSunday="calendarBg"; // td tag: all Sundays (can be disabled, see below)
var $cssSaturday="calendarBg"; // td tag: all Saturdays (can be disabled, see below)
var $cssEvent=""; // td tag: event day set by setEvent(). Multiple class names can be generated
var $cssPrefixSelecEvent=""; // prefix for the event class name if the event is selected
var $cssPrefixTodayEvent=""; //  prefix for the event class name if the event is the current day
var $cssEventContent=""; // table tag: calendar event content. Multiple class names can be generated
var $crSunClass=true; // true: creates a td class on every Sunday (set above)
var $crSatClass=true; // true: creates a td class on every Saturday (set above)
/*
********************************************************************************
You can change below the GET VARS NAMES (navigation + day links)
You should modify the private method mkUrl(), if you want to change the structure of the generated links
********************************************************************************
*/
var $yearID="yearID";
var $monthID="monthID";
var $dayID="dayID";
/*
********************************************************************************
Default start and end year for the date picker (can be changed, if using the ADOdb Date Library)
********************************************************************************
*/
var $startYear=1971;
var $endYear=2037;
/*
----------------------
@START PUBLIC METHODS
----------------------
*/
var $announcements = array();
/*
********************************************************************************
PUBLIC activeCalendar() -> class constructor, does the initial date calculation
$GMTDiff: GMT Zone for current day calculation, do not set to use local server time
********************************************************************************
*/
function activeCalendar($year=false,$month=false,$day=false,$GMTDiff="none", $mysql, $serverid){


// WEBTA ADDON
if ($serverid)
	$where = " AND (serverid='".$serverid."' OR serverid='0')";

$anno = $mysql->fetch("SELECT * FROM announcements WHERE TO_DAYS(dtadded)>=TO_DAYS(FROM_UNIXTIME(".mktime(0,0,0,$month,1,$year).")) AND TO_DAYS(dtadded)<=TO_DAYS(FROM_UNIXTIME(".mktime(0,0,0,$month+1,0,$year).")) {$where}");
foreach ($anno as $ann)
{
	$tm = strtotime($ann['dtadded']);
	$m = date("m", $tm);
	$d = date("d", $tm);
	$y = date("y", $tm);
	$this->announcements[mktime(0,0,0,$m,$d,$y)] = $ann;
}
// WEBTA ADDON

$this->timetoday=time();
$this->selectedday=-2;
$this->selectedyear=$year;
$this->selectedmonth=$month;
	if (!$month) $month=1;
	if (!$day) $day=1;
	else $this->selectedday=$day;
$h=$this->mkActiveGMDate("H");
$m=$this->mkActiveGMDate("i");
$s=$this->mkActiveGMDate("s");
$d=$this->mkActiveGMDate("j");
$mo=$this->mkActiveGMDate("n");
$y=$this->mkActiveGMDate("Y");
$is_dst=$this->mkActiveDate("I");
	if ($GMTDiff!="none") $this->timetoday=$this->mkActiveTime($h,$m,$s,$mo,$d,$y)+(3600*($GMTDiff+$is_dst));
$this->unixtime=$this->mkActiveTime($h,$m,$s,$month,$day,$year);
	if ($this->unixtime==-1 || !$year) $this->unixtime=$this->timetoday;
$this->daytoday=$this->mkActiveDate("j");
$this->monthtoday=$this->mkActiveDate("n");
$this->yeartoday=$this->mkActiveDate("Y");
	if (!$day) $this->actday=$this->daytoday;
	else $this->actday=$this->mkActiveDate("j",$this->unixtime);
	if (!$month) $this->actmonth=$this->monthtoday;
	else $this->actmonth=$this->mkActiveDate("n",$this->unixtime);
	if (!$year) $this->actyear=$this->yeartoday;
	else $this->actyear=$this->mkActiveDate("Y",$this->unixtime);
$this->has31days=checkdate($this->actmonth,31,$this->actyear);
$this->isSchalt=checkdate(2,29,$this->actyear);
	if ($this->isSchalt==1 && $this->actmonth==2) $this->maxdays=29;
	elseif ($this->isSchalt!=1 && $this->actmonth==2) $this->maxdays=28;
	elseif ($this->has31days==1) $this->maxdays=31;
	else $this->maxdays=30;
$this->firstday=$this->mkActiveDate("w", $this->mkActiveTime(0,0,1,$this->actmonth,1,$this->actyear));
$this->GMTDiff=$GMTDiff;
}
/*
********************************************************************************
PUBLIC enableYearNav() -> enables the year's navigation controls
********************************************************************************
*/
function enableYearNav($link=false,$arrowBack=false,$arrowForw=false){
	if ($link) $this->urlNav=$link;
	else $this->urlNav=$_SERVER['PHP_SELF'];
	if ($arrowBack) $this->yearNavBack=$arrowBack;
	if ($arrowForw) $this->yearNavForw=$arrowForw;
$this->yearNav=true;
}
/*
********************************************************************************
PUBLIC enableMonthNav() -> enables the month's navigation controls
********************************************************************************
*/
function enableMonthNav($link=false,$arrowBack=false,$arrowForw=false){
	if ($link) $this->urlNav=$link;
	else $this->urlNav=$_SERVER['PHP_SELF'];
	if ($arrowBack) $this->monthNavBack=$arrowBack;
	if ($arrowForw) $this->monthNavForw=$arrowForw;
$this->monthNav=true;
}
/*
********************************************************************************
PUBLIC enableDayLinks() -> enables the day links
param javaScript: sets a Javascript function on each day link
********************************************************************************
*/
function enableDayLinks($link=false,$javaScript=false){
	if ($link) $this->url=$link;
	else $this->url=$_SERVER['PHP_SELF'];
	if ($javaScript) $this->javaScriptDay=$javaScript;
$this->dayLinks=true;
}
/*
********************************************************************************
PUBLIC enableDatePicker() -> enables the day picker control
********************************************************************************
*/
function enableDatePicker($startYear=false,$endYear=false,$link=false,$button=false){
	if ($link) $this->urlPicker=$link;
	else $this->urlPicker=$_SERVER['PHP_SELF'];
	if ($startYear && $endYear){
		if ($startYear>=$this->startYear && $startYear<$this->endYear) $this->startYear=$startYear;
		if ($endYear>$this->startYear && $endYear<=$this->endYear) $this->endYear=$endYear;
	}
	if ($button) $this->selBtn=$button;
$this->datePicker=true;
}
/*
********************************************************************************
PUBLIC setEvent() -> sets a calendar event, $id: the HTML class (css layout)
********************************************************************************
*/
function setEvent($year,$month,$day,$id=false,$url=false){
$eventTime=$this->mkActiveTime(0,0,1,$month,$day,$year);
	if (!$id) $id=$this->cssEvent;
$this->calEvents[$eventTime]=$id;
$this->calEventsUrl[$eventTime]=$url;
}
/*
********************************************************************************
PUBLIC setEventContent() -> sets a calendar event content,
$content: can be a string or an array, $id: the HTML class (css layout)
********************************************************************************
*/
function setEventContent($year,$month,$day,$content,$url=false,$id=false){
$eventTime=$this->mkActiveTime(0,0,1,$month,$day,$year);
$eventContent[$eventTime]=$content;
$this->calEventContent[]=$eventContent;
	if (!$id) $id=$this->cssEventContent;
$this->calEventContentId[]=$id;
	if ($url) $this->calEventContentUrl[]=$url;
	else $this->calEventContentUrl[]=$this->calInit++;
}
/*
********************************************************************************
PUBLIC setMonthNames() -> sets the month names, $namesArray must be an array of 12 months starting with January
********************************************************************************
*/
function setMonthNames($namesArray){
	if (!is_array($namesArray) || count($namesArray)!=12) return false;
	else $this->monthNames=$namesArray;
}
/*
********************************************************************************
PUBLIC setDayNames() -> sets the week day names, $namesArray must be an array of 7 days starting with Sunday
********************************************************************************
*/
function setDayNames($namesArray){
	if (!is_array($namesArray) || count($namesArray)!=7) return false;
	else $this->dayNames=$namesArray;
}
/*
********************************************************************************
PUBLIC showYear() -> returns the year's view as html table string
Each private method returns a tr tag of the table as a string.
You can change the calendar structure by simply calling these private methods in another order
********************************************************************************
*/
function showYear($rowCount=false){
	if ($rowCount) $this->rowCount=$rowCount;
$this->monthNav=false; // disables month navigation in yearview
$out=$this->mkYearHead(); // this should remain first: opens table tag
$out.=$this->mkYearTitle(); // tr tag: year title and navigation
$out.=$this->mkDatePicker("yearonly"); // tr tag: year date picker (only year selection)
$this->datePicker=false; // disables month date picker in yearview
$out.=$this->mkYearBody(); // tr tag(s): year month (html tables)
$out.=$this->mkYearFoot(); // this should remain last: closes table tag
return $out;
}
/*
********************************************************************************
PUBLIC showMonth() -> returns the month's view as html table string
Each private method returns a tr tag of the table as a string.
You can change the calendar structure by simply calling these private methods in another order
********************************************************************************
*/
function showMonth(){
$out=$this->mkMonthHead(); // this should remain first: opens table tag
$out.=$this->mkMonthTitle(); // tr tag: month title and navigation
$out.=$this->mkDatePicker(); // tr tag: month date picker (month and year selection)
$out.=$this->mkWeekDays(); // tr tag: the weekday names
$out.=$this->mkMonthBody(); // tr tags: the days of the month
$out.=$this->mkMonthFoot(); // this should remain last: closes table tag
return $out;
}
/*
----------------------
@START PRIVATE METHODS
----------------------
*/
/*
********************************************************************************
THE FOLLOWING METHODS AND VARIABLES ARE PRIVATE. PLEASE DO NOT CALL OR MODIFY THEM
********************************************************************************
*/
var $timezone=false;
var $yearNav=false;
var $monthNav=false;
var $dayLinks=false;
var $datePicker=false;
var $url=false;
var $urlNav=false;
var $urlPicker=false;
var $calEvents=false;
var $calEventsUrl=false;
var $javaScriptDay=false;
var $monthNames=false;
var $dayNames=false;
var $calEventContent=false;
var $calEventContentUrl=false;
var $calEventContentId=false;
var $calInit=0;
/*
********************************************************************************
PRIVATE mkYearHead() -> creates the year table tag
********************************************************************************
*/
function mkYearHead(){
return "<table width=\"110\" class=\"".$this->cssYearTable."\">\n";
}
/*
********************************************************************************
PRIVATE mkYearTitle() -> creates the tile and navigation tr tag of the year table
********************************************************************************
*/
function mkYearTitle(){
	if ($this->rowCount<1 || $this->rowCount>12) $this->rowCount=4;
	if (!$this->yearNav){
		$out="<tr><td colspan=\"".$this->rowCount."\" class=\"".$this->cssYearTitle."\">";
		$out.=$this->actyear;
		$out.="</td></tr>\n";
	}
	else{
		$out="<tr><td colspan=\"".$this->rowCount."\" align=\"center\">";
		$out.="<table><tr><td class=\"".$this->cssYearNav."\">";
		$out.=$this->mkUrl($this->actyear-1);
		$out.=$this->yearNavBack."</a></td>";
		$out.="<td class=\"".$this->cssYearTitle."\">".$this->actyear."</td>";
		$out.="<td class=\"".$this->cssYearNav."\">";
		$out.=$this->mkUrl($this->actyear+1);
		$out.=$this->yearNavForw."</a></td></tr></table></td></tr>\n";
	}
return $out;
}
/*
********************************************************************************
PRIVATE mkYearBody() -> creates the tr tags of the year table
********************************************************************************
*/
function mkYearBody(){
$TrMaker = $this->rowCount;
$out="<tr>\n";
	for ($x=1; $x<=12; $x++) {
		$this->activeCalendar($this->actyear,$x,false,$this->GMTDiff);
		$out.="<td valign=\"top\">\n".$this->showMonth()."</td>\n";
		if ($x == $TrMaker && $x < 12) {
			$out.="</tr><tr>";
			$TrMaker = ($TrMaker+$this->rowCount);
		}
	}
$out.="</tr>\n";
return $out;
}
/*
********************************************************************************
PRIVATE mkYearFoot() -> closes the year table tag
********************************************************************************
*/
function mkYearFoot(){
return "</table>\n";
}
/*
********************************************************************************
PRIVATE mkMonthHead() -> creates the month table tag
********************************************************************************
*/
function mkMonthHead(){
return "<table width=\"150\" class=\"".$this->cssMonthTable."\">\n";
}
/*
********************************************************************************
PRIVATE mkMonthTitle() -> creates the tile and navigation tr tag of the month table
********************************************************************************
*/
function mkMonthTitle(){
	if (!$this->monthNav){
		$out="<tr><td class=\"".$this->cssMonthTitle."\" colspan=\"7\">";
		$out.=$this->getMonthName().$this->monthYearDivider.$this->actyear;
		$out.="</td></tr>\n";
	}
	else{
		$out="<tr><td class=\"".$this->cssMonthNav."\" colspan=\"2\">";
		if ($this->actmonth==1) $out.=$this->mkUrl($this->actyear-1,"12");
		else $out.=$this->mkUrl($this->actyear,$this->actmonth-1);
		$out.=$this->monthNavBack."</a></td>";
		$out.="<td class=\"".$this->cssMonthTitle."\" colspan=\"3\">";
		$out.=$this->getMonthName().$this->monthYearDivider.$this->actyear."</td>";
		$out.="<td class=\"".$this->cssMonthNav."\" colspan=\"2\">";
		if ($this->actmonth==12) $out.=$this->mkUrl($this->actyear+1,"1");
		else $out.=$this->mkUrl($this->actyear,$this->actmonth+1);
		$out.=$this->monthNavForw."</a></td></tr>\n";
	}
return $out;
}
/*
********************************************************************************
PRIVATE mkDatePicker() -> creates the tr tag for the date picker
********************************************************************************
*/
function mkDatePicker($yearpicker=false){
	if ($yearpicker) $pickerSpan=$this->rowCount;
	else $pickerSpan=7;
	if ($this->datePicker){
		$out="<tr><td align=\"center\" class=\"".$this->cssPicker."\" colspan=\"".$pickerSpan."\">\n";
		$out.="<form name=\"".$this->cssPickerForm."\" class=\"".$this->cssPickerForm."\" action=\"".$this->urlPicker."\" method=\"get\">\n";
		if (!$yearpicker){
			$out.="<select name=\"".$this->monthID."\" class=\"".$this->cssPickerMonth."\">\n";
			for ($z=1;$z<=12;$z++){
				if ($z==$this->actmonth) $out.="<option value=\"".$z."\" selected=\"selected\">".$this->getMonthName($z)."</option>\n";
				else $out.="<option value=\"".$z."\">".$this->getMonthName($z)."</option>\n";
			}
			$out.="</select>\n";
		}
		$out.="<select name=\"".$this->yearID."\" class=\"".$this->cssPickerYear."\">\n";
		for ($z=$this->startYear;$z<=$this->endYear;$z++){
			if ($z==$this->actyear) $out.="<option value=\"".$z."\" selected=\"selected\">".$z."</option>\n";
			else $out.="<option value=\"".$z."\">".$z."</option>\n";
		}
		$out.="</select>\n";
		$out.="<input type=\"submit\" value=\"".$this->selBtn."\" class=\"".$this->cssPickerButton."\"></input>\n";
		$out.="</form>\n";
		$out.="</td></tr>\n";
	}
	else $out="";
return $out;
}
/*
********************************************************************************
PRIVATE mkWeekDays() -> creates the tr tag of the month table for the weekdays
********************************************************************************
*/
function mkWeekDays(){
	if ($this->startOnSun){
		$out="<tr class=\"".$this->cssWeekDay."\"><td>".$this->getDayName(0)."</td>";
		$out.="<td>".$this->getDayName(1)."</td>";
		$out.="<td>".$this->getDayName(2)."</td>";
		$out.="<td>".$this->getDayName(3)."</td>";
		$out.="<td>".$this->getDayName(4)."</td>";
		$out.="<td>".$this->getDayName(5)."</td>";
		$out.="<td>".$this->getDayName(6)."</td></tr>\n";
	}
	else{
		$out="<tr class=\"".$this->cssWeekDay."\"><td>".$this->getDayName(1)."</td>";
		$out.="<td>".$this->getDayName(2)."</td>";
		$out.="<td>".$this->getDayName(3)."</td>";
		$out.="<td>".$this->getDayName(4)."</td>";
		$out.="<td>".$this->getDayName(5)."</td>";
		$out.="<td>".$this->getDayName(6)."</td>";
		$out.="<td>".$this->getDayName(0)."</td></tr>\n";
		$this->firstday=$this->firstday-1;
		if ($this->firstday<0) $this->firstday=6;
	}
return $out;
}
/*
********************************************************************************
PRIVATE mkMonthBody() -> creates the tr tags of the month table
********************************************************************************
*/
function mkMonthBody(){
$out="<tr>";
$monthday=0;
	for ($x=0; $x<=6; $x++){
		if ($x>=$this->firstday){
		$monthday++;
		$out.=$this->mkDay($monthday);
		}
		else $out.="<td class=\"".$this->cssNoMonthDay."\"></td>";
	}
$out.="</tr>\n";
$goon=$monthday+1;
$stop=0;
	for ($x=0; $x<=6; $x++){
		if ($goon>$this->maxdays) break;
		if ($stop==1) break;
		$out.="<tr>";
			for ($i=$goon; $i<=$goon+6; $i++){
				if ($i>$this->maxdays){
					$out.="<td class=\"".$this->cssNoMonthDay."\"></td>";
					$stop=1;
				}
				else $out.=$this->mkDay($i);
			}
		$goon=$goon+7;
		$out.="</tr>\n";
	}
$this->selectedday="-2";
return $out;
}
/*
********************************************************************************
PRIVATE mkDay() -> creates each td tag of the month body
********************************************************************************
*/
function mkDay($var){
$eventContent=$this->mkEventContent($var);
$linkstr=$this->mkUrl($this->actyear,$this->actmonth,$var);
$linkstrT=$this->mkUrl($this->actyear,$this->actmonth,$var,1);
	if ($this->javaScriptDay) $linkstr="<a href=\"javascript:".$this->javaScriptDay."(".$this->actyear.",".$this->actmonth.",".$var.")\">".$var."</a>";
	if ($this->isEvent($var)){
		if ($this->eventUrl){
			$out="<td class=\"".$this->eventID."\"><a href=\"".$this->eventUrl."\">".$var."</a>".$eventContent."</td>";
			$this->eventUrl=false;
		}
		elseif (!$this->dayLinks) $out="<td class=\"".$this->eventID."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->eventID."\">".$linkstr.$eventContent."</td>";
	}
	elseif ($var==$this->selectedday && $this->actmonth==$this->selectedmonth && $this->actyear==$this->selectedyear){
		if (!$this->dayLinks) $out="<td class=\"".$this->cssSelecDay."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->cssSelecDay."\">".$linkstrT.$eventContent."</td>";
	}
	elseif ($var==$this->daytoday && $this->actmonth==$this->monthtoday && $this->actyear==$this->yeartoday){
		if (!$this->dayLinks) $out="<td class=\"".$this->cssToday."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->cssToday."\">".$linkstr.$eventContent."</td>";
	}
	elseif ($this->getWeekday($var)==0 && $this->crSunClass){
		if (!$this->dayLinks) $out="<td class=\"".$this->cssSunday."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->cssSunday."\">".$linkstr.$eventContent."</td>";
	}
	elseif ($this->getWeekday($var)==6 && $this->crSatClass){
		if (!$this->dayLinks) $out="<td class=\"".$this->cssSaturday."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->cssSaturday."\">".$linkstr.$eventContent."</td>";
	}
	else{
		if (!$this->dayLinks) $out="<td class=\"".$this->cssMonthDay."\">".$var.$eventContent."</td>";
		else $out="<td class=\"".$this->cssMonthDay."\">".$linkstr.$eventContent."</td>";
	}
return $out;
}
/*
********************************************************************************
PRIVATE mkMonthFoot() -> closes the month table
********************************************************************************
*/
function mkMonthFoot(){
return "</table>\n";
}
/*
********************************************************************************
PRIVATE mkUrl() -> creates the day and navigation link structure
********************************************************************************
*/
function mkUrl($year,$month=false,$day=false, $isToday=0){
	
	$css = ($isToday == 1) ? "calendarRed" : "";

	if (strpos($this->url,"?")) $glue="&amp;";
	else $glue="?";
	if (strpos($this->urlNav,"?")) $glueNav="&amp;";
	else $glueNav="?";
$yearNavLink="<a style=\"text-decoration:none;\" class=\"\" href=\"".$this->urlNav.$glueNav.$this->yearID."=".$year."\">";
$monthNavLink="<a style=\"text-decoration:none;\" class=\"\" href=\"".$this->urlNav.$glueNav.$this->yearID."=".$year."&amp;".$this->monthID."=".$month."\">";
if ($this->announcements[mktime(0,0,0,$month,$day,$year)])
{
	$dayLink="<a style=\"text-decoration:none;\" class=\"".$css."\" href=\"".$this->url.$glue.$this->yearID."=".$year."&amp;".$this->monthID."=".$month."&amp;".$this->dayID."=".$day."\">".$day."</a>";
}
else
{
	$dayLink=$day;
}
	if ($year && $month && $day) return $dayLink;
	if ($year && !$month && !$day) return $yearNavLink;
	if ($year && $month && !$day) return $monthNavLink;
}
/*
********************************************************************************
PRIVATE mkEventContent() -> creates the table for the event content
********************************************************************************
*/
function mkEventContent($var){
$hasContent=$this->hasEventContent($var);
$out="";
	if ($hasContent){
		for ($x=0;$x<count($hasContent);$x++){
			foreach($hasContent[$x] as $eventContentid => $eventContentData){
				foreach($eventContentData as $eventContentUrl => $eventContent){
					$out.="<table class=\"".$eventContentid."\">";
					if (is_string($eventContent)){
						if (is_int($eventContentUrl)) $out.="<tr><td>".$eventContent."</td></tr></table>";
						else $out.="<tr><td><a href=\"".$eventContentUrl."\">".$eventContent."</a></td></tr></table>";
					}
					elseif (is_array($eventContent)){
						foreach($eventContent as $arrayContent){
							if (is_int($eventContentUrl)) $out.="<tr><td>".$arrayContent."</td></tr>";
							else $out.="<tr><td><a href=\"".$eventContentUrl."\">".$arrayContent."</a></td></tr>";
						}
					$out.="</table>";
					}
					else $out="";
				}
			}
		}
	}
return $out;
}
/*
********************************************************************************
PRIVATE getMonthName() -> returns the month's name, according to the configuration
********************************************************************************
*/
function getMonthName($var=false){
	if (!$var) $var=@$this->actmonth;
	if ($this->monthNames) return $this->monthNames[$var-1];
	switch($var){
		case 1: return $this->jan;
		case 2: return $this->feb;
		case 3: return $this->mar;
		case 4: return $this->apr;
		case 5: return $this->may;
		case 6: return $this->jun;
		case 7: return $this->jul;
		case 8: return $this->aug;
		case 9: return $this->sep;
		case 10: return $this->oct;
		case 11: return $this->nov;
		case 12: return $this->dec;
	}
}
/*
********************************************************************************
PRIVATE getDayName() -> returns the day's name, according to the configuration
********************************************************************************
*/
function getDayName($var=false){
	if ($this->dayNames) return $this->dayNames[$var];
	switch($var){
		case 0: return $this->sun;
		case 1: return $this->mon;
		case 2: return $this->tue;
		case 3: return $this->wed;
		case 4: return $this->thu;
		case 5: return $this->fri;
		case 6: return $this->sat;
	}
}
/*
********************************************************************************
PRIVATE getWeekday() -> returns the weekday's number, 0 = Sunday ... 6 = Saturday
********************************************************************************
*/
function getWeekday($var){
return $this->mkActiveDate("w", $this->mkActiveTime(0,0,1,$this->actmonth,$var,$this->actyear));
}
/*
********************************************************************************
PRIVATE isEvent() -> checks if a date was set as an event and creates the eventID (css layout) and eventUrl
********************************************************************************
*/
function isEvent($var){
	if ($this->calEvents){
		$checkTime=$this->mkActiveTime(0,0,1,$this->actmonth,$var,$this->actyear);
		$selectedTime=$this->mkActiveTime(0,0,1,$this->selectedmonth,$this->selectedday,$this->selectedyear);
		$todayTime=$this->mkActiveTime(0,0,1,$this->monthtoday,$this->daytoday,$this->yeartoday);
		foreach($this->calEvents as $eventTime => $eventID){
			if ($eventTime==$checkTime){
				if ($eventTime==$selectedTime) $this->eventID=$this->cssPrefixSelecEvent.$eventID;
				elseif ($eventTime==$todayTime) $this->eventID=$this->cssPrefixTodayEvent.$eventID;
				else $this->eventID=$eventID;
				if ($this->calEventsUrl[$eventTime]) $this->eventUrl=$this->calEventsUrl[$eventTime];
				return true;
			}
		}
	return false;
	}
}
/*
********************************************************************************
PRIVATE hasEventContent() -> checks if an event content was set
********************************************************************************
*/
function hasEventContent($var){
$hasContent=false;
	if ($this->calEventContent){
		$checkTime=$this->mkActiveTime(0,0,1,$this->actmonth,$var,$this->actyear);
		for ($x=0;$x<count($this->calEventContent);$x++){
			$eventContent=$this->calEventContent[$x];
			$eventContentUrl=$this->calEventContentUrl[$x];
			$eventContentId=$this->calEventContentId[$x];
			foreach($eventContent as $eventTime => $eventContent){
				if ($eventTime==$checkTime) $hasContent[][$eventContentId][$eventContentUrl]=$eventContent;
			}
		}
	}
return $hasContent;
}
/*
********************************************************************************
PRIVATE mkActiveDate() -> checks if ADOdb Date Library is loaded and calls the date function
********************************************************************************
*/
function mkActiveDate($param,$acttime=false){
	if (!$acttime) $acttime=$this->timetoday;
	if (function_exists("adodb_date")) return adodb_date($param,$acttime);
	else return date($param,$acttime);
}
/*
********************************************************************************
PRIVATE mkActiveGMDate() -> checks if ADOdb Date Library is loaded and calls the gmdate function
********************************************************************************
*/
function mkActiveGMDate($param,$acttime=false){
	if (!$acttime) $acttime=time();
	if (function_exists("adodb_gmdate")) return adodb_gmdate($param,$acttime);
	else return gmdate($param,$acttime);
}
/*
********************************************************************************
PRIVATE mkActiveTime() -> checks if ADOdb Date Library is loaded and calls the mktime function
********************************************************************************
*/
function mkActiveTime($hr,$min,$sec,$month=false,$day=false,$year=false){
	if (function_exists("adodb_mktime")) return adodb_mktime($hr,$min,$sec,$month,$day,$year);
	else return mktime($hr,$min,$sec,$month,$day,$year);
}
}
?>
