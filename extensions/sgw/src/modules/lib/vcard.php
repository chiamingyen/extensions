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

class lib_vcard extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $count = count(self::_parse($path));
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = self::_parse($path);
  $rows = array();
  $contact_ids = array();
  $i = 0;
  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
	    case "contactid":
		  if ($row["firstname"]!="" or $row["lastname"]!="") {
			$row[$field] = str_replace(array(" ","."),"",substr($row["firstname"],0,2).substr($row["lastname"],0,5));
			if (isset($contact_ids[$row[$field]])) $row[$field] .= $i;
			$contact_ids[$row[$field]] = "";
		  } else {
			$row[$field] = basename($path)."_".$i;
		  }
		  break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": 
		  $row[$field] = "";
		  if (isset($data["email"][0])) $row[$field] .= " ".$data["email"][0];
		  if (isset($data["fn"][0])) $row[$field] .= " ".$data["fn"][0];
		  if (isset($data["n"][0])) $row[$field] .= " ".$data["n"][0];
		  if (isset($data["n"][1])) $row[$field] .= " ".$data["n"][1];
		  break;
		case "email": $row[$field] = isset($data["email"][0])?$data["email"][0]:""; break;
		case "lastname": 
		    $row[$field] = "";
			if (isset($data["fn"][0])) $row[$field] = substr($data["fn"][0],0,strpos($data["fn"][0]," "));
			if (isset($data["n"][0])) $row[$field] = $data["n"][0];
			break;
		case "firstname":
		    $row[$field] = "";
			if (isset($data["fn"][0])) $row[$field] = substr($data["fn"][0],strpos($data["fn"][0]," ")+1);
			if (isset($data["n"][1])) $row[$field] = $data["n"][1];
			break;
		case "title": $row[$field] = isset($data["n"][3])?$data["n"][3]:""; break;
		case "company": $row[$field] = isset($data["org"][0])?$data["org"][0]:""; break;
		case "phone": $row[$field] = isset($data["tel"][0])?$data["tel"][0]:""; break;
		case "birthday": $row[$field] = isset($data["bday"][0])?strtotime($data["bday"][0]):""; break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _parse($file) {
  if (($data = sys_cache_get("vcard_".sha1($file)))) return $data;
  if (($message = sys_allowedpath(dirname($file)))) {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t} %s",$file,$message));
    return array();
  }
  if (!($data = @file_get_contents($file))) {
    sys_warning("{t}The url doesn't exist.{/t} (".$file.")");
  	return array();
  }
  if (!class_exists("Contact_Vcard_Parse",false)) require("lib/vcard/Contact_Vcard_Parse.php");

  $parse = new Contact_Vcard_Parse();

  $rows = array();
  if (($data = $parse->fromText($data))) {
	foreach ($data as $item) {
	  $row = array();
	  foreach ($item as $key=>$values) {
		$key = strtolower($key);
		$row[$key] = array();
		foreach ($values as $value) {
		  foreach ($value["value"] as $value2) if ($value2[0]) $row[$key][] = trim($value2[0]);
		}
	  }
	  $rows[] = $row;
	}
  } else {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t}",$file));
	return array();
  }
  sys_cache_set("vcard_".sha1($file),$rows,VCARD_CACHE);
  return $rows;
}
}