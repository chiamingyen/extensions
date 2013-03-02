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

class lib_csv_calendar extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $count = count(sys_parse_csv($path));
  if ($count>0) $count--;
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = sys_parse_csv($path);
  $rows = array();
  $index = array_shift($datas);
  $i = 0;
  $map = array(
    "subject" => "Subject",
    "description" => "Description",
    "location" => "Location",
	"category" => "Categories",
	"organizer" => "Meeting Organizer",
  );
  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "createdby": $row[$field] = ""; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		case "begin": 
		  $key1 = array_search("Start Date", $index);
		  $key2 = array_search("Start Time", $index);
		  $row[$field] = modify::datetime_to_int($data[$key1]." ".$data[$key2]);
		  break;
		case "ending":
		  $key1 = array_search("End Date", $index);
		  $key2 = array_search("End Time", $index);
		  $row[$field] = modify::datetime_to_int($data[$key1]." ".$data[$key2]);
		  $row["until"] = $row[$field];
		  break;
		case "duration": $row[$field] = $row["ending"] - $row["begin"]; break;

		case "priority": 
		  $key = array_search("Priority", $index);
		  $row[$field] = strtolower($data[$key]);
		  break;
		case "allday": 
		  $key = array_search("All day event", $index);
		  $row[$field] = ($data[$key]=="False"?0:1);
		  break;
		case "participants_ext":
		  $key = array_search("Required Attendees", $index);
		  $row[$field] = $data[$key];
		  $key = array_search("Optional Attendees", $index);
		  if ($row[$field]!="" and $data[$key]!="") $row[$field] .= ", ";
		  $row[$field] .= $data[$key];
		  break;
		default:
		  $row[$field] = "";
		  if (!isset($row[$field]) and in_array($field,$index)) {
			$key = array_search($field, $index);
			$row[$field] = $data[$key];
		  } else if (isset($map[$field])) {
			$key = array_search($map[$field], $index);
			$row[$field] = $data[$key];
		  }
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}
}