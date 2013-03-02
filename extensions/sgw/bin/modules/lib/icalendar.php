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

class lib_icalendar extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $count = count(self::_parse($path));
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = self::_parse($path);
  $rows = array();
  $i = 0;
  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
	    case "folder": $row[$field] = $path; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
	  	case "lastmodifiedby": $row[$field] = ""; break;
		default:
		  if (isset($data[$field])) {
			$row[$field] = $data[$field];
		  } else $row[$field] = "";
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  // fix for missing sql filtering
  $limit = array(0,count($rows));
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _array_flat($arr) {
  if (count($arr)>0) return "|".implode("|",$arr)."|"; else return "";
}

private static function _parse($file) {
  if (($data = sys_cache_get("icalendar_".sha1($file)))) return $data;
  if (($message = sys_allowedpath(dirname($file)))) {
    sys_warning(sprintf("Cannot read the file %s. %s",$file,$message));
    return array();
  }

  $rows = array();
  if (!$handle = fopen($file, "rb")) {
    sys_warning(sprintf("Cannot read the file %s.",$file));
	return array();
  }
  $i = 0;
  $evopen = false;
  $lines = array();
  
  while (!feof($handle)) {
	$line = fgets($handle, 10000);
	$line = str_replace(array("\r","\n","\\n"),array("","","\n"),$line);
	if ($line=="") continue;
	if ($line[0]==" ") {
	  $lines[count($lines)-1] .= substr($line,1);
	} else $lines[] = $line;
  }
  fclose($handle);

  foreach ($lines as $line) {
	$line = trim($line);
	switch($line) {
	  case "BEGIN:VEVENT":
		$i++;
	  	$evopen = true;
		$rows[$i] = array(
		  "subject"=>"","begin"=>0,"ending"=>0,"created"=>0,"duration"=>0,"lastmodified"=>0,"allday"=>"0","location"=>"","description"=>"",
		  "organizer"=>array(),"participants_ext"=>array(),"category"=>"","priority"=>"3",
		  "recurrence"=>"","repeatinterval"=>1,"repeatcount"=>"0","repeatuntil"=>0,"repeatexcludes"=>array()
		);
		break;
	  case "BEGIN:VALARM":
	    $evopen = false;
		break;
	  case "END:VEVENT":
	    $evopen = false;
		$begin = sys_getdate($rows[$i]["begin"]);
		$end = sys_getdate($rows[$i]["ending"]);
		if ($rows[$i]["lastmodified"]==0) $rows[$i]["lastmodified"] = $rows[$i]["created"];
		if ($begin["hours"]==0 and $end["hours"]==0 and $begin["minutes"]==0 and $end["minutes"]==0) {
		  if ($rows[$i]["begin"]==$rows[$i]["ending"]) {
			$rows[$i]["ending"] += 86399;
		  } else $rows[$i]["ending"]--;
		  $rows[$i]["allday"] = "1";
		}
		foreach ($rows[$i] as $key=>$item) {
		  if (is_array($item)) $rows[$i][$key] = self::_array_flat($item);
		}
		if ($rows[$i]["ending"]!="" and $rows[$i]["begin"]!="") {
		  $rows[$i]["duration"] = $rows[$i]["ending"] - $rows[$i]["begin"];
		}
		$rows[$i] = array_merge($rows[$i],trigger::calcappointment("",$rows[$i],null,""));
		// $rows[$i]["begin_str"] = date("D d-m-y H:i:s",$rows[$i]["begin"]);
		// $rows[$i]["end_str"] = date("D d-m-y H:i:s",$rows[$i]["ending"]);
		break;
	  default:
		if (!$evopen) break;
		$pos = strpos($line,":");
		$first = substr($line,0,$pos);
		$value_str = str_replace(array("\\,","\\n","\\N"),array(",","\n","\n"),substr($line,$pos+1));
		if (($pos2 = strpos($first,";"))) $kval = substr($first,0,$pos2); else $kval = $first;

		switch($kval) {
		  case "SUMMARY":
			$rows[$i]["subject"] = $value_str; break;
		  case "DESCRIPTION":
			$rows[$i]["description"] = $value_str; break;
		  case "LOCATION":
			$rows[$i]["location"] = $value_str; break;
		  case "CLASS":
			if ($rows[$i]["category"]!="" and $value_str!="") $rows[$i]["category"] .= ",";
			$rows[$i]["category"] .= ucfirst(strtolower($value_str)); break;		
		  case "CATEGORIES":
			if ($rows[$i]["category"]!="") $rows[$i]["category"] .= ",";
			$rows[$i]["category"] .= $value_str; break;		
		  case "UID":
			$rows[$i]["id"] = $value_str; break;
		  case "SEQUENCE":
			$rows[$i]["sequence"] = $value_str; break;
		  case "DTSTART":
			$rows[$i]["begin"] = modify::ical_datetime_to_int($value_str); break;
		  case "DTEND":
			$rows[$i]["ending"] = modify::ical_datetime_to_int($value_str); break;
		  case "LAST-MODIFIED":
			$rows[$i]["lastmodified"] = modify::ical_datetime_to_int($value_str); break;
		  case "DTSTAMP":
			$rows[$i]["created"] = modify::ical_datetime_to_int($value_str); break;
		  case "DURATION":
			if (!preg_match("/PT?([0-9]{1,2}W)?([0-9]{1,2}D)?([0-9]{1,2}H)?([0-9]{1,2}M)?/",$value_str,$match)) break;
			$match = array_merge($match,array(0,0,0,0));
			$rows[$i]["ending"] = $rows[$i]["begin"] + str_replace("W","",$match[1])*604800 + str_replace("D","",$match[2])*86400 + str_replace("H","",$match[3])*3600 + str_replace("M","",$match[4])*60;
			break;
		  case "RRULE":
			$value = explode(";",$value_str);
			foreach ($value as $val) {
			  $val = explode("=",$val);
			  switch($val[0]) {
				case "FREQ":
				  switch ($val[1]) {
					case "YEARLY": $rows[$i]["recurrence"] = "years"; break;
					case "MONTHLY": $rows[$i]["recurrence"] = "months"; break;
					case "WEEKLY": $rows[$i]["recurrence"] = "weeks"; break;
					case "DAILY": $rows[$i]["recurrence"] = "days"; break;
				  }
				  break;
				case "INTERVAL":
				  $rows[$i]["repeatinterval"] = $val[1]; break;
				case "COUNT":
				  $rows[$i]["repeatcount"] = $val[1]; break;
				case "UNTIL":
				  $rows[$i]["repeatuntil"] = modify::ical_datetime_to_int($val[1]); break;
			  }
			}
			break;
		  case "EXDATE":
			$rows[$i]["repeatexcludes"][] = modify::ical_datetime_to_int($value_str); break;
		  case "ORGANIZER":
		  	$value = explode(";",$value_str);
			$key = explode(";",$first);
			if (isset($value[1])) $value[0] = $value[1];
			if (isset($key[1])) $value[0] = str_replace(array("CN=","\""),"",$key[1])." (".str_replace(array("MAILTO:","mailto:"),"",$value[0]).")";
			$rows[$i]["organizer"][] = $value[0];
			break;
		  case "ATTENDEE":
	  		$value = explode(";",$value_str);
			$key = explode(";",$first);
			if (isset($value[1])) $value[0] = $value[1];
			$value[0] = str_replace(array("MAILTO:","mailto:"),"",$value[0]);
			if (isset($key[1]) and strpos($key[1],"CN=")!==false) $value[0] = str_replace(array("CN=","\""),"",$key[1])." (".$value[0].")";
			$rows[$i]["participants_ext"][] = $value[0];
			break;
		  default:
			// echo $line."<br>\n";
			break;
	  }
	  break;
	}
  }
  sys_cache_set("icalendar_".sha1($file),$rows,ICALENDAR_CACHE);
  return $rows;
}
}