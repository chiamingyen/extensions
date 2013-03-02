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

class lib_stats extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $t = $GLOBALS["t"];
  $stats = array();
  if (in_array("id in (@item@)",$t["sqlwhere"])) unset($t["sqlwhere"][array_search("id in (@item@)",$t["sqlwhere"])]);
  $stats_db = db_select("simple_sys_stats","action",$t["sqlwhere"],"","",$t["sqlvars"],array("groupby"=>"action"));
  foreach ($stats_db as $stat) if (isset($stat["action"])) $stats[] = $stat["action"];
  return count($stats);
}

static function delete($path,$where,$vars,$mfolder) {
  db_delete("simple_sys_stats",array(),array());
}

static function select($folder,$fields,$where,$order,$limit,$vars,$mfolder) {
  $tname = "simple_sys_stats";
  $today = $_SESSION[$tname]["_".$folder]["today"];
  $today_arr = sys_getdate($today);
  
  $weekdays = array("Su","Mo","Tu","We","Th","Fr","Sa");
  $weekstart = $_SESSION[$tname]["_".$folder]["weekstart"];
  $t_today = date::$today;
  $t_then = date::$then;
  $s_today = $t_today;
  $s_then = $t_then;

  if ($_SESSION[$tname]["_".$folder]["markdate"]=="day") {
    $dstrings = array(sys_date("Y_M_d",$today),24,"loghour");
	$labels = range(1, 24);
  } else if ($_SESSION[$tname]["_".$folder]["markdate"]=="week") {
    $dstrings = array(sys_date("Y_W_",$today).$_SESSION[$tname]["_".$folder]["weekstart"],28,"logweekpart");
	$labels = array($weekdays[$weekstart],"","","",$weekdays[($weekstart+1)%7],"","","",$weekdays[($weekstart+2)%7],"","","",$weekdays[($weekstart+3)%7],"","","",$weekdays[($weekstart+4)%7],"","","",$weekdays[($weekstart+5)%7],"","","",$weekdays[($weekstart+6)%7],"","","");
  } else if ($_SESSION[$tname]["_".$folder]["markdate"]=="month") {
    $s_today = mktime(0,0,0,$today_arr["mon"],1,$today_arr["year"]);
	$s_then = strtotime("+1 month -1 day",$s_today);
    $dstrings = array(sys_date("Y_M",$today),31,"logday");
	$labels = range(1,31);
  } else {
    $s_today = mktime(0,0,0,1,1,$today_arr["year"]);
	$s_then = mktime(0,0,0,12,31,$today_arr["year"]);
    $dstrings = array(sys_date("Y",$today),52,"logweek");
	$labels = range(1,52);
  }
  if ($t_today!="" and $t_today != $s_today) {
	foreach ($where as $key=>$val) {
	  if (strpos($val,(string)$t_today)) {
	    $where[$key] = str_replace(array($t_today, $t_then),array($s_today, $s_then),$val);
  } } }
  
  $rows = array();
  if (in_array("id in (@item@)",$where)) unset($where[array_search("id in (@item@)",$where)]);
  $rows2 = db_select("simple_sys_stats",array("action", $dstrings[2]." as id","sum(weight) as weight"),$where,"","",$vars,array("groupby"=>"action, ".$dstrings[2]));

  $data_all = array("logins"=>array(),"pages"=>array(),"downloads"=>array());
  if (is_array($rows2) and count($rows2)>0)
  foreach ($rows2 as $row) {
	if (!isset($data_all[$row["action"]]) or count($data_all[$row["action"]])==0) $data_all[$row["action"]] = array_fill(0,$dstrings[1],"");
    $data_all[$row["action"]][$row["id"]-1] = $row["weight"];
  }
  if (count($data_all)>0) {
	foreach ($data_all as $stat=>$data) {
	  if (count($data)==0) continue;
      $id = md5($_SESSION["style"].$stat.$dstrings[0]);
	  $stat = str_replace("_"," ",ucfirst($stat));
      $filename = "preview.php?type=bar&stat=".$stat."&style=".$_SESSION["style"]."&width=550&height=175&data=".implode(",",array_slice($data,0,$dstrings[1]))."&labels=".implode(",",array_slice($labels,0,$dstrings[1]));
	  $row = array();
	  foreach ($fields as $field) {
		switch ($field) {
		  case "id": $row[$field] = $id; break;
		  case "created": $row[$field] = 0; break;
		  case "lastmodified": $row[$field] = 0; break;
		  case "lastmodifiedby": $row[$field] = ""; break;
		  case "image": $row[$field] = "<img src='".$filename."'/>"; break;
		  case "title": $row[$field] = $stat; break;
		  case "searchcontent": $row[$field] = $stat; break;
		}
	  }
	  if (sys_select_where($row,$where,$vars)) $rows[] = $row;
	}
    $rows = sys_select($rows,$order,$limit,$fields);
  }
  return $rows;
}
}