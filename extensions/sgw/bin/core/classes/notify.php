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

class notify {

static function simple_tasks($id, $data) {
  if ($data["closed"]=="1" or $data["ending"] < NOW) return "";
  $subject = sprintf("Tasks: %s, %s - %s", $data["subject"], sys_date("m/d/Y", $data["begin"]), SMTP_REMINDER);
  $message = self::_message($id, $data, array("subject","begin","ending"), "tasks");
  db_notification_add("simple_tasks|".$id, $data["notification"], $subject, $message, strtotime("-2 days 17:00:00", $data["begin"]));
  return "";
}

static function simple_contactactivities($id, $data) {
  if ($data["finished"]=="1" or $data["ending"] < NOW) return "";
  $subject = sprintf("Contact activities: %s, %s - %s", $data["subject"], sys_date("m/d/Y", $data["begin"]), SMTP_REMINDER);
  $message = self::_message($id, $data, array("subject","begin","ending"), "contactactivities");
  db_notification_add("simple_contactactivities|".$id, $data["notification"], $subject, $message, strtotime("-2 days 17:00:00", $data["begin"]));
  return "";
}

static function simple_calendar($id, $data) {
  if ($data["until"] < NOW) return "";

  $recurrences = array();
  $fields = array("subject","begin","ending","allday","location","recurrence","repeatexcludes","nrecurs");
  if (!empty($data["recurrence"])) {
	$recurrences = explode("|", trim($data["recurs"], "|"));
	if ($data["repeatinterval"]!=1) $fields[] = "repeatinterval";
  }
  $subject = sprintf("Appointments: %s, %s %s - %s", $data["subject"], "%s", $data["location"], SMTP_REMINDER);
  $data["nrecurs"] = "%s";
  $message = self::_message($id, $data, $fields, "calendar");

  if ($data["reminder"]!="0") self::_simple_calendar($id, $data, $recurrences, $subject, $message, "-".$data["reminder"]." sec", "&reminder");

  // TODO add multiple events?
  // self::_simple_calendar($id, $data, $recurrences, $subject, $message, "last sunday 17:00:00", "&weekly");
  
  self::_simple_calendar($id, $data, $recurrences, $subject, $message, "-1 day 17:00:00");
  return "";
}

private static function _simple_calendar($id, $data, $recurrences, $subject, $message, $diff, $type="") {
  $deliveries_rec = self::_calc_offset($recurrences, $diff);

  $delivery = strtotime($diff, $data["begin"]);
  if (!empty($deliveries_rec)) $delivery = array_shift($deliveries_rec);
  
  $begin = $data["begin"];
  foreach ($recurrences as $recurrence) {
	if ($recurrence > $delivery) {
	  $begin = $recurrence;
	  break;
	}
  }
  $message = sprintf($message, modify::recurrences($data["recurs"],array(4, $delivery)));
  $subject = sprintf($subject, modify::shortdatetimeformat($begin));
  
  db_notification_add("simple_calendar|".$id.$type, $data["notification"], $subject, $message, $delivery, $deliveries_rec);
}

static function simple_sys_users($id, $data) {
  if ($data["activated"]=="0") return "";
	
  if (!empty($data["anniversary"])) {
	$delivery = strtotime("-2 days " . sys_date("Y-m-d",$data["anniversary"]));
	$subject = sprintf("Anniversary: %s, %s - %s", $data["lastname"], $data["firstname"], SMTP_REMINDER);
	$message = self::_message($id, $data, array("lastname","firstname","email","anniversary"), "sys_users");
	db_notification_add("simple_sys_users|".$id."&anniversary", $data["notification"], $subject, $message, $delivery, "+1 year");
  }
  if ($data["neverexp"]=="0" and $data["expires"] > NOW) {
	$subject = sprintf("Expiry: %s - %s", $data["username"], SMTP_REMINDER);
	$message = self::_message($id, $data, array("username","lastname","firstname","expires"), "sys_users");
	db_notification_add("simple_sys_users|".$id."&expiry", $data["notification"], $subject, $message, strtotime("-5 days", $data["expires"]));
  }
  if (!empty($data["birthday"])) {
	$delivery = strtotime("-2 days " . sys_date("Y-m-d",$data["birthday"]));
	$subject = sprintf("Birthday: %s, %s - %s", $data["lastname"], $data["firstname"], SMTP_REMINDER);
	$message = self::_message($id, $data, array("lastname","firstname","email","birthday"), "sys_users");
	db_notification_add("simple_sys_users|".$id."&birthday", $data["notification"], $subject, $message, $delivery, "+1 year");
  }
  return "";
}

static function simple_contacts($id, $data) {
  if (empty($data["birthday"])) return "";

  $delivery = strtotime("-2 days " . sys_date("Y-m-d",$data["birthday"]));
  $subject = sprintf("Birthday: %s, %s - %s", $data["lastname"], $data["firstname"], SMTP_REMINDER);
  $message = self::_message($id, $data, array("lastname","firstname","company","email","birthday"), "contacts");
  db_notification_add("simple_contacts|".$id."&birthday", $data["notification"], $subject, $message, $delivery, "+1 year");
  return "";
}

private static function _calc_offset($vals, $offset) {
  if (empty($vals)) return "";
  $result = array();
  foreach ($vals as $val) {
	$val = strtotime($offset, $val);
    if ($val > NOW) $result[] = $val;
  }
  return array_unique($result);
}

private static function _message($id, $data, $fields, $table) {
  $sgsml = new sgsml($data["folder"], "display", array($id), false);
  $message = $sgsml->att["MODULENAME"]."\n";
  $message .= str_repeat("-",strlen($message)-1)."\n\n";
  foreach ($data as $key=>$value) {
	if (strlen($value)==0 or !in_array($key,$fields)) continue;
	$value = trim(asset::build_history($sgsml->fields[$key]["SIMPLE_TYPE"],$value,""));
	if ($value!="") $message .= $sgsml->fields[$key]["DISPLAYNAME"].": ".$value."\n";
  }
  $url = "http".(sys_https()?"s":"")."://".$_SERVER['HTTP_HOST'].dirname($_SERVER["SCRIPT_NAME"]).
	"/index.php?view=details&find=".$table."|".$id;
  return $message . sys_remove_trans("\nDetails: ".$url);
}

}