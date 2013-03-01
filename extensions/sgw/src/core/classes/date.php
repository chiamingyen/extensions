<?php
	/**************************************************************************\
	* Simple Groupware 0.743                                                   *
	* http://www.simple-groupware.de                                           *
	* Copyright (C) 2002-2012 by Thomas Bley                                   *
	* ------------------------------------------------------------------------ *
	*  This program is free software; you can redistribute it and/or           *
	*  modify it under the terms of the GNU General Public License Version 2   *
	*  as published by the Free Software Foundation; only version 2            *
	*  of the License, no later version.                                       *
	*                                                                          *
	*  This program is distributed in the hope that it will be useful,         *
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of          *
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
	*  GNU General Public License for more details.                            *
	*                                                                          *
	*  You should have received a copy of the GNU General Public License       *
	*  Version 2 along with this program; if not, write to the Free Software   *
	*  Foundation, Inc., 59 Temple Place - Suite 330, Boston,                  *
	*  MA  02111-1307, USA.                                                    *
	\**************************************************************************/

class date {

static $today = "";
static $then = "";

private static function _build_check_hitdate($today,$today_arr,$then,$item_start,$item_end,$item_until,$item_recurrence,$item_interval,$item_exclusions) {
  if ($item_recurrence!="") { // normalize recurring events
	if ($item_start>$then or ($item_until<$today and $item_until>0)) return array();
	$norm = "";
	$start_arr = sys_getdate($item_start);
	$diff = $item_end-$item_start;
	$day_diff = ceil(($today - $item_start)/86400);
    if ($item_recurrence == "years") {
      $norm_year = $today_arr["year"] - (($today_arr["year"]-$start_arr["year"])%$item_interval);
	  if ($norm_year < $today_arr["year"] or $start_arr["yday"] < $today_arr["yday"]) $norm_year += $item_interval;

	  $start_arr["year"] = $norm_year;
	  $norm = "year";
	  
	} else if ($item_recurrence == "months") {
	  $norm_month = $today_arr["mon"] - ((12*($today_arr["year"]-$start_arr["year"])+$today_arr["mon"]-$start_arr["mon"])%$item_interval);
	  if ($norm_month*31 + $start_arr["mday"] < $today_arr["mon"]*31 + $today_arr["mday"]) $norm_month += $item_interval;

	  $start_arr["mon"] = $norm_month;
	  $start_arr["year"] = $today_arr["year"];
	  $norm = "mon";

	} else if ($item_recurrence == "weeks") {
	  $item_interval *= 7;
	  $norm_day = $today_arr["mday"] - $today_arr["wday"] + $start_arr["wday"] - ($day_diff%$item_interval);
	  if ($norm_day < $today_arr["mday"]) $norm_day += $item_interval;

	  $start_arr["mon"] = $today_arr["mon"];
	  $start_arr["mday"] = $norm_day;
	  $start_arr["year"] = $today_arr["year"];
	  
	  $norm = "mday";
	} else if ($item_recurrence == "days") {
	  $norm_day = $today_arr["mday"] - ($day_diff%$item_interval);
	  if ($norm_day < $today_arr["mday"]) $norm_day += $item_interval;

	  $start_arr["mon"] = $today_arr["mon"];
	  $start_arr["mday"] = $norm_day;
	  $start_arr["year"] = $today_arr["year"];
	  $norm = "mday";
	}
	if ($norm!="" and count($item_exclusions)>0) {
	  $item_start = mktime(0,0,0,$start_arr["mon"],$start_arr["mday"],$start_arr["year"]);
	  if (in_array($item_start,$item_exclusions)) $start_arr[$norm] += $item_interval;
	}
    $item_start = mktime($start_arr["hours"],$start_arr["minutes"],0,$start_arr["mon"],$start_arr["mday"],$start_arr["year"]);
	$item_end = $item_start + $diff;
  }
  if (!($item_end>=$today and $item_end<=$then) and !($then>=$item_start and $then<=$item_end)) {
	return array();
  }
  return array($item_start,$item_end);
}

static function process_session_request() {
  $t = $GLOBALS["t"];
  $tname = $t["title"];
  $tfolder = $t["folder"];
  if (!empty($_REQUEST["today"])) {
    if (!is_numeric($_REQUEST["today"])) $_REQUEST["today"] = modify::datetime_to_int($_REQUEST["today"]);
	$today = sys_getdate($_REQUEST["today"]);
  } else $today = sys_getdate();

  if (empty($_SESSION[$tname]["_".$tfolder]["today"]) or !is_numeric($_SESSION[$tname]["_".$tfolder]["today"]) or 
  	  !empty($_REQUEST["today"]) or $_SESSION[$tname]["_".$tfolder]["today"]<1) {
	$_SESSION[$tname]["_".$tfolder]["today"] = mktime(0,0,0,$today["mon"],$today["mday"],$today["year"]);
  }
  if (!isset($_SESSION[$tname]["_".$tfolder]["weekstart"])) $_SESSION[$tname]["_".$tfolder]["weekstart"] = WEEKSTART;
  if (isset($_REQUEST["weekstart"]) and is_numeric($_REQUEST["weekstart"]) and $_REQUEST["weekstart"]>=0 and $_REQUEST["weekstart"]<=6) {
	$_SESSION[$tname]["_".$tfolder]["weekstart"] = $_REQUEST["weekstart"];
  }
  
  if (!isset($_SESSION[$tname]["_".$tfolder]["markdate"])) {
    if (!empty($GLOBALS["current_view"]["MARKDATE"])) {
	  $_SESSION[$tname]["_".$tfolder]["markdate"] = $GLOBALS["current_view"]["MARKDATE"];
	} else {
	  $_SESSION[$tname]["_".$tfolder]["markdate"] = "week";
	}
  }
  if (isset($_REQUEST["markdate"]) and in_array($_REQUEST["markdate"], array("day","week","month","year","gantt","all"))) {
	$_SESSION[$tname]["_".$tfolder]["markdate"] = $_REQUEST["markdate"];
  }
}

static function build_views_sql($rows) {
  $t = $GLOBALS["t"];
  $tname = $t["title"];
  $tfolder = $t["folder"];
  $today = $_SESSION[$tname]["_".$tfolder]["today"];
  $type = $_SESSION[$tname]["_".$tfolder]["markdate"];
  $weekstart = $_SESSION[$tname]["_".$tfolder]["weekstart"];

  if ($type=="all" or !empty($GLOBALS["current_view"]["HIDE_CALENDAR"])) {
    if (count($t["sqllimit"])==2 and count($rows) > $t["sqllimit"][1]) {
      return array_slice($rows,$t["sqllimit"][0],$t["sqllimit"][1]);
    }
	return $rows;
  }
  // fields: 0-start,1-end,2-recurrence,3-until,4-allday,5-repeatinterval,6-repeatexcludes
  $fields = explode(",",$GLOBALS["current_view"]["ENABLE_CALENDAR"]);
  if (count($fields)<7) return $rows;

  if ($type=="month" or $type=="year" or $type=="gantt") {
    $today_arr = sys_getdate($today);
    if ($type=="month" or $type=="gantt") {
	  $start_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
      $last_day = strtotime("+1 month",$start_day);
    } else {
      $start_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
      $last_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]+1);
	}
    $num = sys_date("w",$last_day);
    if ((int)$num!=(int)$weekstart) $last_day = strtotime("+".((7+$weekstart-$num)%7)." days",$last_day);
    $num = sys_date("w",$start_day);
    if ($num!=$weekstart) $start_day = strtotime("-".((7+$num-$weekstart)%7)." days",$start_day);

	$all_begin = $start_day;
	$all_end = $last_day-1;
  } else if ($type=="week") {
    $num = sys_date("w",$today);
    if ($num!=$weekstart) $today = strtotime("-".((7+$num-$weekstart)%7)." days",$today);
	$all_begin = $today;
	$all_end = strtotime("+1 week -1 second",$today);
  } else if ($type=="day") {
	$all_begin = $today;
	$all_end = $today+86399;
  }
  $removed = 0;
  $all_begin_arr = sys_getdate($all_begin);
  foreach ($rows as $key=>$item) {
	$item_start = $item[$fields[0]];
    if ($item_start=="") continue;
	$item_end = $item[$fields[1]];
	$item_until = $item[$fields[3]];
	$item_recurrence = $item[$fields[2]];
	$item_interval = $item[$fields[5]];
	if ($item[$fields[6]]=="") {
	  $item_exclusions = array();
	} else {
	  $item_exclusions = explode("|",trim($item[$fields[6]],"|"));
	}
	$normalized = self::_build_check_hitdate($all_begin,$all_begin_arr,$all_end,$item_start,$item_end,$item_until,$item_recurrence,$item_interval,$item_exclusions);

	if (count($normalized)==0) {
	  unset($rows[$key]);
	  $removed++;
	}
  }
  if ($removed>0) {
	_asset_process_pages($t["maxdatasets"]-$removed);
  }
  
  if (count($t["sqllimit"])==2 and count($rows) > $t["sqllimit"][1]) {
    $rows = array_slice($rows,$t["sqllimit"][0],$t["sqllimit"][1]);
  }
  return $rows;
}

static function build_views() {
  $t = &$GLOBALS["t"];
  $tname = $t["title"];
  $tfolder = $t["folder"];
  $today = $_SESSION[$tname]["_".$tfolder]["today"];
  $type = $_SESSION[$tname]["_".$tfolder]["markdate"];
  $weekstart = $_SESSION[$tname]["_".$tfolder]["weekstart"];

  $weekdays2 = array("{t}Sun{/t}","{t}Mon{/t}","{t}Tue{/t}","{t}Wed{/t}","{t}Thu{/t}","{t}Fri{/t}","{t}Sat{/t}");

  if ($type=="all" or !empty($GLOBALS["current_view"]["HIDE_CALENDAR"])) {
    return;
  }
  $fields = explode(",",$GLOBALS["current_view"]["ENABLE_CALENDAR"]); // 0-start,1-end,2-recurrence,3-until,4-allday,5-repeatinterval,6-repeatexcludes
  if (count($fields)<2) return;
  $days = array();
  $weeknum = 0;
  $today_arr = sys_getdate($today);
  if ($type=="month" or $type=="year" or $type=="gantt") {
    if ($type=="month" or $type=="gantt") {
	  $start_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
      $last_day = strtotime("+1 month",$start_day);
    } else {
      $start_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
      $last_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]+1);
	}
    $num = sys_date("w",$last_day);
    if ((int)$num!=(int)$weekstart) $last_day = strtotime("+".((7+$weekstart-$num)%7)." days",$last_day);
    $num = sys_date("w",$start_day);
    if ($num!=$weekstart) $start_day = strtotime("-".((7+$num-$weekstart)%7)." days",$start_day);

	if ($type=="month" or $type=="gantt") $limit = 42; else $limit = 377;
	for ($i=0;$i<$limit;$i++) {
	  $days[] = $start_day;
      $start_day = strtotime("+1 day",$start_day);
	  if ($start_day==$last_day) break;
	}
  } else if ($type=="week") {
    $num = sys_date("w",$today);
    if ($num!=$weekstart) $today = strtotime("-".((7+$num-$weekstart)%7)." days",$today);
    $start_day = $today;
	for ($i=0;$i<7;$i++) {
	  $days[] = $start_day;
      $start_day = strtotime("+1 day",$start_day);
	}
	$title = array($today,$start_day-1);
	$weeknum = sys_date("W",$today+86400);
  } else if ($type=="day") {
	$title = array($today);
	$days[] = $today;
  }
  
  if ($type=="day" or $type=="week") {
    $day_begin = $_SESSION["day_begin"];
    $day_end = $_SESSION["day_end"];
    $times = array();

    for ($i=$day_begin+$today;$i<$day_end+$today;$i=$i+3600) $times[count($times)*4] = $i;
	$times[0] += 3600;
    $maxslices = ($day_end-$day_begin)/900;

    $cols = 0;
	$offset = 0;
	$daylabels = array();
	$table = array();
    foreach ($days as $today) {
      $then = strtotime("+1 day -1 second",$today);
      $today_arr = sys_getdate($today);
      $daylabels[] = array("timestamp"=>$today,"day"=>$weekdays2[$today_arr["wday"]],"daynum"=>$today_arr["mday"],"span"=>1);

      $day = array();
      $begin = $today + $day_begin;
      $end = $today + $day_end;
  	  foreach ($t["data"] as $item) {
	    $item_start = $item[$fields[0]]["data"][0];
	    $item_end = $item[$fields[1]]["data"][0];
		if (!isset($fields[3])) {
		  $item_until = 0;
	      $item_allday = 0;
	      $item_recurrence = "";
	      $item_interval = 1;
		  $item_exclusions = "";
		} else {
		  $item_until = $item[$fields[3]]["data"][0];
	      $item_allday = $item[$fields[4]]["data"][0];
	      $item_recurrence = $item[$fields[2]]["data"][0];
	      $item_interval = $item[$fields[5]]["data"][0];
	      $item_exclusions = $item[$fields[6]]["data"];
		}
		$normalized = self::_build_check_hitdate($today,$today_arr,$then,$item_start,$item_end,$item_until,$item_recurrence,$item_interval,$item_exclusions);
		if (count($normalized)==0) continue;
		$item_start = $normalized[0];
		$item_end = $normalized[1];
	    if ($item_start <= $begin and $item_end >= $end) $item_allday = 1;
	    if ($item_allday) {
	      $item_start = $begin;
	      $item_end = $begin + 3600;
	    }
	    $row_span = ceil($item_end/900) - floor($item_start/900);

	    if ($item_start < $begin) {
	      $row_span -= ($begin-$item_start+3600)/900;
		  $start_span = 4;
	    } else $start_span = floor(($item_start-$begin)/900);
	  
	    if ($start_span >= $maxslices) { $start_span = $maxslices - 1; $row_span = 1; }
	    if ($row_span > $maxslices) $row_span = $maxslices - $start_span;

        if (!$item_allday and $start_span < 4) {
	      $row_span -= 4 - $start_span;
		  $start_span = 4;
	    }
	    if ($row_span < 1) $row_span = 1;
		
	    $end_span = $maxslices - $start_span - $row_span;
	    $inserted = false;
	    foreach ($day as $day_key=>$day_item) {
	      if ($day_item[0][2] >= ($row_span + $start_span)) {
		    $day[$day_key][0][2] -= $row_span + $start_span;
		    $day[$day_key][0][1] = $row_span + $start_span + 1;
  		    $day[$day_key] = array_merge(array(array("free",1,$start_span,""), array("id",$start_span+1,$row_span,$item["id"]["data"][0])), $day[$day_key]);
		    $inserted = true;
		    break;
		  }
	      if ($day_item[count($day_item)-1][2] >= ($row_span + $end_span)) {
		    $day[$day_key][count($day_item)-1][2] -= $row_span + $end_span;
		    $index = $day[$day_key][count($day_item)-1][1] + $day[$day_key][count($day_item)-1][2];
  		    $day[$day_key] = array_merge($day[$day_key], array(array("id",$index,$row_span,$item["id"]["data"][0]), array("free",$index+$row_span,$end_span,"")));
		    $inserted = true;
		    break;
		  }
	    }
	    if (!$inserted) {
		  $day[] = array( array("free",1,$start_span,""), array("id",$start_span+1,$row_span,$item["id"]["data"][0]), array("free",$start_span+$row_span+1,$end_span,""));
	    }
	  }
	  foreach ($day as $day_key=>$day_item) { // split spannings into slices with maxspan 4
	    foreach ($day_item as $day_item_item) {
	      if ($day_item_item[2]!=0) {
		    $day_item_item[1]--;
		    if ($day_item_item[0]=="free") {
		      $pos = $day_item_item[1];
			  $spans = $day_item_item[2];
		      while ($spans > 0) {
			    $span = 4;
			    if ($pos == $day_item_item[1]) $span = 4-$pos%4;
			    if ($spans < 4) $span = $spans;
			    $table[$pos][$day_key+$offset] = array($day_item_item[0],$span,$day_item_item[3]);
			    if ($pos == $day_item_item[1]) $pos -= $pos%4;
			    $spans -= $span;
			    $pos += 4;
			  }
		    } else {
              $table[$day_item_item[1]][$day_key+$offset] = array($day_item_item[0],$day_item_item[2],$day_item_item[3]);
		    }
		  }
	    }
	  }
	  if (count($day)==0) {
	    for ($i=0;$i<=$maxslices;$i+=4) $table[$i][$offset] = array('free', 4,'');
	    $day[] = "";
	  }
	  $offset += count($day);
      $cols += count($day);
      $daylabels[count($daylabels)-1]["span"] = count($day)*2;
    }
    $t["data_day"] = array("type"=>$type,"weeknum"=>$weeknum,"title"=>$title,"rows"=>$maxslices, "cols"=>$cols, "times"=>$times, "daylabels"=>$daylabels,"table"=>$table);
  }
  
  if ($type=="month" or $type=="year" or $type=="gantt") {
    $daylabels = array();
    foreach ($days as $today) {
      $then = $today+86399;
      $today_arr = sys_getdate($today);
      foreach ($t["data"] as $item) {
	    $item_start = $item[$fields[0]]["data"][0];
	    $item_end = $item[$fields[1]]["data"][0];
		if (!isset($fields[3])) {
		  $item_until = 0;
	      $item_allday = 1;
	      $item_recurrence = "";
	      $item_interval = 1;
		  $item_exclusions = "";
		} else {
		  $item_until = $item[$fields[3]]["data"][0];
	      $item_allday = $item[$fields[4]]["data"][0];
	      $item_recurrence = $item[$fields[2]]["data"][0];
	      $item_interval = $item[$fields[5]]["data"][0];
		  $item_exclusions = $item[$fields[6]]["data"];
		}
		$normalized = self::_build_check_hitdate($today,$today_arr,$then,$item_start,$item_end,$item_until,$item_recurrence,$item_interval,$item_exclusions);
		if (count($normalized)==0) continue;

		$daylabels[$today][] = $item["id"]["data"][0];
	  }		
	}
    $t["data_month"] = array("type"=>$type,"table"=>$daylabels);
  }
}

static function build_datebox($today, $mark, $weekstart) {
  $today_arr = sys_getdate($today);
  $current_month = $today_arr["mon"];
  
  $weekdays = array("{t}Su{/t}","{t}Mo{/t}","{t}Tu{/t}","{t}We{/t}","{t}Th{/t}","{t}Fr{/t}","{t}Sa{/t}");
  $months = array("","{t}January{/t}","{t}February{/t}","{t}March{/t}","{t}April{/t}","{t}May{/t}","{t}June{/t}",
  				  "{t}July{/t}","{t}August{/t}","{t}September{/t}","{t}October{/t}","{t}November{/t}","{t}December{/t}");

  if ($mark=="year") {
	$dates_months = range($today_arr["mon"],$today_arr["mon"]+11);
  } else {
	$dates_months = array($today_arr["mon"]);
  }
  $dow = array();
  for ($i=0;$i<7;$i++) {
    $index = ($i+$weekstart)%7;
    $dow[] = array("date_d"=>$weekdays[$index],"date_w"=>$index);
  }
  
  $dates = array();
  $fstinmonths = array();
  foreach ($dates_months as $mon) {
    $year = $today_arr["year"];
    if ($mon>12) {
	  $mon -= 12;
	  $year++;
	}
    $start_day = mktime(0,0,0,$mon,1,$year);
    $last_day = mktime(0,0,0,$mon+1,1,$year);
	
    $fstinmonths[$mon] = $start_day;

    $num = sys_date("w",$start_day);
    if ($num!=$weekstart) $start_day = strtotime("-".((7+$num-$weekstart)%7)." days",$start_day);
    $num = sys_date("w",$last_day);
    if ((int)$num!=(int)$weekstart) $last_day = strtotime("+".((7+$weekstart-$num)%7)." days",$last_day);
	
	$date_n = 12;
    if ($mon!=1) $date_n = $mon-1;
	
    $start_week = sys_date("W",$start_day+86400);

    for ($i=0;$i<42;$i++) {
      $date_j = sys_date("j",$start_day);
	  if ($date_j==1) $date_n++;
	  if ($date_n==13) $date_n = 1;
	  $week_key = floor($i/7)+$start_week;
	  if ($week_key>52) $week_key = $week_key % 52;
      $dates[$mon][$week_key][] = array("timestamp"=>$start_day,"j"=>$date_j,"n"=>$date_n);

      $start_day = strtotime("+1 day",$start_day);
	  if ($start_day==$last_day) break;
    }
  }

  $next_date = strtotime("+1 month",$today);
  $prev_date = strtotime("-1 month",$today);
  
  if ($mark=="year") {
    $next_date_year = strtotime("+1 year",$today);
    $prev_date_year = strtotime("-1 year",$today);
  } else {
    $next_date_year = $next_date;
	$prev_date_year = $prev_date;
  }

  if ($mark=="month" or $mark=="year" or $mark=="gantt") {
	$start_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
    if ($mark=="month" or $mark=="gantt") {
      $last_day = strtotime("+1 month",$start_day);
    } else {
      $last_day = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]+1);
	}
    $num = sys_date("w",$last_day);
    if ((int)$num!=(int)$weekstart) $last_day = strtotime("+".((7+$weekstart-$num)%7)." days",$last_day);
    $num = sys_date("w",$start_day);
    if ($num!=$weekstart) $start_day = strtotime("-".((7+$num-$weekstart)%7)." days",$start_day);
	$today = $start_day;
	$tomorrow = $last_day-1;
  } else if ($mark=="week") {
    $num = sys_date("w",$today);
    if ($num!=$weekstart) $today = strtotime("-".((7+$num-$weekstart)%7)." days",$today);
	$tomorrow = strtotime("+1 week -1 second",$today);
  } else if ($mark=="day") {
	$tomorrow = $today+86399;
  } else {
	$today = 0;
	$tomorrow = 0;
  }

  sys::$smarty->assign("datebox",array("dow"=>$dow,"dates"=>$dates,"realtoday"=>strtotime("00:00:00"),"today"=>$today,"week"=>sys_date("W",$today+86400),"month"=>$current_month,"year"=>sys_date("Y",$fstinmonths[$current_month]),
  	"months"=>$months,"mark"=>$mark,"next_date"=>$next_date,"prev_date"=>$prev_date,"next_date"=>$next_date,"prev_date"=>$prev_date,"next_date_year"=>$next_date_year,"prev_date_year"=>$prev_date_year,"fstinmonths"=>$fstinmonths));

  if ($mark!="all") {
	$today_arr = sys_getdate($today);
	$tomorrow_arr = sys_getdate($tomorrow);
	self::_build_datebox_sql($today,$today_arr,$tomorrow,$tomorrow_arr,$mark);
  }
}

private static function _build_datebox_sql($today,$today_arr,$then,$then_arr,$mark) {
  $t = &$GLOBALS["t"];

  $fields = explode(",",$GLOBALS["current_view"]["ENABLE_CALENDAR"]);
  if (count($fields)==1) {
    self::$today = $today;
	self::$then = $then;
    $sql = "(".$fields[0]." between ".$today." and ".$then.")";
  } else if (count($fields)==2) {
    $sql = "((".$fields[1]." between ".$today." and ".$then.") or (".$fields[0]." between ".$today." and ".$then."))";
  } else { // 0-start,1-end,2-recurrence,3-until,4-allday,5-repeatinterval,6-repeatexclusions,7-repeatstart,8-repeatend	
	$start = $fields[0];
	$end = $fields[1];
	$rec = $fields[2];
	$until = $fields[3];
	$rstart = $fields[7];
	$rend = $fields[8];

	$wtoday = $today_arr["wday"];
	$wthen = $then_arr["wday"];

	$mtoday = $today_arr["mday"];
	$mthen = $then_arr["mday"];

	$ytoday = $today_arr["yday"];
	$ythen = $then_arr["yday"];
	  
    $sql = "(($end between $today and $then) or ($then between $start and $end)";
  	$sql .= " or ($start < $then and ($until=0 or $until > $today) and ";
	$sql .= "($rec='days'";
	  
	if ($mark=="day") {
	  $sql .= " or ($rec='weeks' and (($rend between $wtoday and $wthen) or ($rend>=$rstart and $wthen between $rstart and $rend) or ($rend<$rstart and $wthen not between $rend+1 and $rstart-1)) )";
	} else $sql .= " or $rec='weeks'";
	  
	if ($mark=="day" or $mark=="week") {
	  $sql .= " or ($rec='months' and (($rend between $mtoday and $mthen) or ($rend>=$rstart and $mthen between $rstart and $rend) or ($rend<$rstart and $mthen not between $rend+1 and $rstart-1)) )";
	} else $sql .= " or $rec='months'";

	if ($mark=="day" or $mark=="week" or $mark=="month" or $mark=="gantt") {
	  $sql .= " or ($rec='years' and (($rend between $ytoday and $ythen) or ($rend>=$rstart and $ythen between $rstart and $rend) or ($rend<$rstart and $ythen not between $rend+1 and $rstart-1)) )";
	} else $sql .= " or $rec='years'";
	
	$sql .= ")))";
  }
  $t["sqlwhere"][] = $sql;
  $t["sqlwhere_default"][] = $sql;
}

}