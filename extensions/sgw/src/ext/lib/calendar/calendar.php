<?PHP
  header('Content-Type: text/html; charset=utf-8');
?>
<!--
Title: Tigra Calendar
URL: http://www.softcomplex.com/products/tigra_calendar/
Version: 3.2
Date: 10/14/2002 (mm/dd/yyyy)
Note: Permission given to use this script in ANY kind of applications if
   header lines are left unchanged.
-->
<html>
<head>
<style>
	body {
	  margin:0px;
	  padding:0px;
	  border:0px;
	  border-spacing:0px;
	  overflow-y:hidden;
	  overflow:hidden;
	}
	td,input {font-size: 11px; font-family: Arial, Helvetica, Verdana, sans-serif;}
	a {text-decoration: none;}
	#footer {text-align: center; color: #FFFFFF;}
	#footer a, #title a {color: #FFFFFF; font-weight: bold;}
</style>
<script language="JavaScript">
var date_format = remove_trans("{t}m/d/Y{/t}");
var time_format = remove_trans("{t}g:i a{/t}");
var RE_NUM = /^\-?\d+$/;

function gen_date(dt_datetime) {
	var date_str = date_format;
	date_str = date_str.replace("d",(dt_datetime.getDate() < 10 ? '0' : '') + dt_datetime.getDate());
	date_str = date_str.replace("m",(dt_datetime.getMonth() < 9 ? '0' : '') + (dt_datetime.getMonth() + 1));
	date_str = date_str.replace("Y",dt_datetime.getFullYear());
	return date_str;
}
function gen_time(dt_datetime) {
	var time_str = time_format;
	time_str = time_str.replace("H",(dt_datetime.getHours() < 10 ? '0' : '') + dt_datetime.getHours());
	time_str = time_str.replace("G",dt_datetime.getHours());
	time_str = time_str.replace("i",(dt_datetime.getMinutes() < 10 ? '0' : '') + dt_datetime.getMinutes());
	time_str = time_str.replace("a",(dt_datetime.getHours() < 12 ? 'am' : 'pm'));
	var hours = dt_datetime.getHours();
	if (hours==0) hours = 12;
	if (hours>12) hours = hours - 12;
	time_str = time_str.replace("g",hours);
	time_str = time_str.replace("h",(hours < 10 ? '0' : '') + hours);
	return time_str;
}
function gen_tsmp(dt_datetime) {
	return(gen_date(dt_datetime) + ' ' + gen_time(dt_datetime));
}
function prs_tsmp(str_datetime) {
	if (!str_datetime) return (new Date());
	if (RE_NUM.exec(str_datetime)) return new Date(new Number(str_datetime));
	str_datetime = str_datetime.replace("%20"," ").replace("%20"," ");
	var date = str_datetime.substr(0,(str_datetime+' ').indexOf(' ',8));
	var time = str_datetime.substr(str_datetime.indexOf(' ',8)+1);
	return prs_time(time, prs_date(date));
}
function prs_date(str_date) {
	var reg = new RegExp(date_format.replace("d","(\\d{1,2})").replace("m","\\d{1,2}").replace("Y","\\d{4}"));
	reg.exec(str_date);
	var day = RegExp.$1;
	var reg = new RegExp(date_format.replace("d","\\d{1,2}").replace("m","(\\d{1,2})").replace("Y","\\d{4}"));
	reg.exec(str_date);
	var month = RegExp.$1;
	var reg = new RegExp(date_format.replace("d","\\d{1,2}").replace("m","\\d{1,2}").replace("Y","(\\d{4})"));
	reg.exec(str_date);
	var year = RegExp.$1;
	if (!RE_NUM.exec(day)) day = 1;
	if (!RE_NUM.exec(month)) month = 1;
	if (!RE_NUM.exec(year)) year = 2006;
	var dt_date = new Date();
	dt_date.setDate(1);
	if (month < 1 || month > 12) month = 1;
	dt_date.setMonth(month-1);
	if (year < 100) year = Number(year) + (year < 30 ? 2000 : 1900);
	dt_date.setFullYear(year);
	dt_date.setDate(day);
	return (dt_date)
}
function prs_time(str_time, dt_date) {
	if (!dt_date) return null;
	if (!str_time) return dt_date;
	var reg = new RegExp(time_format.replace(/G|H|g|i/g,"(\\d{1,2})").replace("a","(am|pm)"));
	reg.exec(str_time);
	var hours = RegExp.$1;
	var minutes = RegExp.$2;
	var ampm = RegExp.$3;
	if (ampm) {
  	  if (hours == 12) hours = 0;
	  if (ampm=="pm") hours = Number(hours) + 12;
	}
	dt_date.setHours(hours);
	dt_date.setMinutes(minutes);
	dt_date.setSeconds(0);
	dt_date.setMilliseconds(0);
	return dt_date;
}
function remove_trans(str) {
  return str.replace(new RegExp("{t"+"}|{/t"+"}","g"), "");
}
var ARR_MONTHS = ["{t}January{/t}", "{t}February{/t}", "{t}March{/t}", "{t}April{/t}", "{t}May{/t}", "{t}June{/t}", "{t}July{/t}", "{t}August{/t}", "{t}September{/t}", "{t}October{/t}", "{t}November{/t}", "{t}December{/t}"];
var ARR_WEEKDAYS = ["{t}Su{/t}", "{t}Mo{/t}", "{t}Tu{/t}", "{t}We{/t}", "{t}Th{/t}", "{t}Fr{/t}", "{t}Sa{/t}"];
for (var i=0;i<ARR_MONTHS.length;i++) ARR_MONTHS[i] = remove_trans(ARR_MONTHS[i]);
for (var i=0;i<ARR_WEEKDAYS.length;i++) ARR_WEEKDAYS[i] = remove_trans(ARR_WEEKDAYS[i]);
var re_url = new RegExp('datetime=(\\-?.+?)&');
var dt_current = (re_url.exec(String(window.location))
	? prs_tsmp(RegExp.$1) : new Date());
var re_id = new RegExp('id=(\\d+)');
var num_id = (re_id.exec(String(window.location))
	? new Number(RegExp.$1) : 0);
var obj_caller = top.calendars[num_id];
if (obj_caller && obj_caller.year_scroll) {
	var dt_prev_year = new Date(dt_current);
	dt_prev_year.setFullYear(dt_prev_year.getFullYear() - 1);
	if (dt_prev_year.getDate() != dt_current.getDate())
		dt_prev_year.setDate(0);
	var dt_next_year = new Date(dt_current);
	dt_next_year.setFullYear(dt_next_year.getFullYear() + 1);
	if (dt_next_year.getDate() != dt_current.getDate())
		dt_next_year.setDate(0);
}
var dt_prev_month = new Date(dt_current);
dt_prev_month.setMonth(dt_prev_month.getMonth() - 1);
if (dt_prev_month.getDate() != dt_current.getDate())
	dt_prev_month.setDate(0);
var dt_next_month = new Date(dt_current);
dt_next_month.setMonth(dt_next_month.getMonth() + 1);
if (dt_next_month.getDate() != dt_current.getDate())
	dt_next_month.setDate(0);
var dt_firstday = new Date(dt_current);
dt_firstday.setDate(1);
dt_firstday.setDate(1 - (7 + dt_firstday.getDay() - obj_caller.NUM_WEEKSTART) % 7);
function set_datetime(n_datetime, b_close) {
	if (!obj_caller) return;
	
	var dt_datetime = prs_time(
		(document.cal ? document.cal.time.value : ''),
		new Date(n_datetime)
	);
	if (!dt_datetime) return;
	if (b_close) {
		top.document.getElementById("calendar").style.display="none";
		top.document.getElementById("calendar_iframe").src="about:blank";
		if (n_datetime) {
		  obj_caller.target.value = (document.cal ? gen_tsmp(dt_datetime) : gen_date(dt_datetime));
		  if (obj_caller.callback) top.calendar_changed();
		}
	} else obj_caller.popup_open(dt_datetime.valueOf());
}
</script>
</head>
<body marginheight="0" marginwidth="0" topmargin="0" leftmargin="0" rightmargin="0">
<table id="table" class="clsOTable" cellspacing="0" border="0" width="100%">
<tr><td bgcolor="#4682B4" valign="top">
<table cellspacing="1" cellpadding="3" border="0" width="100%">
<tr><td colspan="7"><table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr id="title">
<script language="JavaScript">
document.write(
'<td>'+(obj_caller&&obj_caller.year_scroll?'<a href="javascript:set_datetime('+dt_prev_year.valueOf()+')">&lt;</a>&nbsp;':'')+'<a href="javascript:set_datetime('+dt_prev_month.valueOf()+')">&lt;&lt;</a></td>'+
'<td align="center" width="100%"><b><font color="#ffffff">'+ARR_MONTHS[dt_current.getMonth()]+' '+dt_current.getFullYear() + '</font></b></td>'+
'<td><a href="javascript:set_datetime('+dt_next_month.valueOf()+')">&gt;&gt;</a>'+(obj_caller && obj_caller.year_scroll?'&nbsp;<a href="javascript:set_datetime('+dt_next_year.valueOf()+')">&gt;</a>':'')+'</td>'
);
</script>
</tr>
</table></td></tr>
<tr>
<script language="JavaScript">
for (var n=0; n<7; n++)
	document.write('<td bgcolor="#87cefa" align="center"><font color="#ffffff">'+ARR_WEEKDAYS[(obj_caller.NUM_WEEKSTART+n)%7]+'</font></td>');
document.write('</tr>');
var dt_current_day = new Date(dt_firstday),
	dt_selected = obj_caller.dt_selected;
if (!dt_selected) dt_selected = dt_current;
while (dt_current_day.getMonth() == dt_current.getMonth() ||
	dt_current_day.getMonth() == dt_firstday.getMonth()) {
	document.write('<tr>');
	for (var n_current_wday=0; n_current_wday<7; n_current_wday++) {
		if (dt_current_day.getDate() == dt_selected.getDate() &&
			dt_current_day.getMonth() == dt_selected.getMonth() &&
			dt_current_day.getFullYear() == dt_selected.getFullYear())
			document.write('<td bgcolor="#ffb6c1" align="center" width="14%">');
		else if (dt_current_day.getDay() == 0 || dt_current_day.getDay() == 6)
			document.write('<td bgcolor="#dbeaf5" align="center" width="14%">');
		else
			document.write('<td bgcolor="#ffffff" align="center" width="14%">');
		if (dt_current_day.getMonth() == this.dt_current.getMonth()) color = "#000000"; else color = "#606060";
		document.write('<a href="javascript:set_datetime('+dt_current_day.valueOf() +', true);" style="color:'+color+';">');
		document.write(dt_current_day.getDate()+'</a></td>');
		dt_current_day.setDate(dt_current_day.getDate()+1);
	}
	document.write('</tr>');
}
if (obj_caller && obj_caller.time_comp)
	document.write('<form onsubmit="javascript:set_datetime('+dt_current.valueOf()+', true)" name="cal"><tr><td colspan="7" align="center" bgcolor="#87CEFA"><font color="White" face="tahoma, verdana" size="2"><input type="text" name="time" value="'+gen_time(this.dt_current)+'" size="8" maxlength="8"></font></td></tr></form>');
</script>
</table>
<script>
var str_today = remove_trans("{t}Today{/t}");
var str_close = remove_trans("{t}Close{/t}");
document.write('<div id="footer"><a href="javascript:set_datetime(new Date().valueOf(),true);">'+str_today+'</a> - <a href="javascript:set_datetime(\'\',true);">'+str_close+'</a></div>');
</script>
</td></tr></table>
<script>
top.document.getElementById("calendar").style.height = document.getElementById("table").clientHeight+"px";
</script>
</body>
</html>