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

class lib_ldif_contacts extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  return count(self::_parse($path));
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = self::_parse($path);
  $rows = array();
  $ids = array();
  $mapping = array(
	"c"=>"country",	"cn"=>"contactid", "company"=>"company", "department"=>"department", "dn"=>"id",
	"description"=>"description", "facsimiletelephonenumber"=>"fax", "fax" => "fax",
	"sn"=>"lastname", "homephone"=>"phoneprivate", "ipphone"=>"skype", "l"=>"city",
	"mail"=>"email", "mobile"=>"mobile", "mozillasecondemail"=>"emailprivate",
	"mozillanickname"=>"nickname", "pager"=>"pager", "postalcode"=>"zipcode", "givenname"=>"firstname",
	"st"=>"state", "streetaddress"=>"street", "street"=>"street", "telephonenumber"=>"phone",
	"title" => "title"
  );
  foreach ($datas as $key=>$data) {
    $id = $key;
	if (!empty($data["id"])) $id = md5($data["id"]);
    $row = array();
	foreach ($fields as $field) {
	  $row[$field] = "";
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$id; break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		case "contactid": 
		  if (empty($data["cn"])) {
			if (!empty($data["sn"])) $row[$field] = $data["sn"];
			if (!empty($data["givenname"])) $row[$field] .= " ".$data["givenname"];
			if ($row[$field]=="" and !empty($data["mail"])) $row[$field] = $data["mail"];
		  } else $row[$field] = $data["cn"];
			
		  $row[$field] = str_replace(array(" ",".",",","@","\"","'"),array("_","_","","_","",""),$row[$field]);
		  $row[$field] = substr(trim($row[$field]," _-."),0,15);
		  while (isset($ids[$row[$field]])) $row[$field] .= "_2";
		  $ids[$row[$field]] = "";
		  break;
		case "lastname":
		  if (!empty($data["sn"])) $row[$field] = $data["sn"];
		  if ($row[$field]=="" and !empty($data["mail"])) {
		    preg_match("/[.-_]?([^.-_@]+)@/i",$data["mail"],$match);
			if (!empty($match[1])) $row[$field] = ucfirst(strtolower($match[1]));
		  }
		  $row[$field] = trim($row[$field]," ,");
		  break;
		case "firstname":
		  if (!empty($data["givenname"])) $row[$field] = $data["givenname"];
		  if ($row[$field]=="" and !empty($data["mail"])) {
		    preg_match("/([^._@]+)[._][^._@]*@/i",$data["mail"],$match);
			if (!empty($match[1])) $row[$field] = ucfirst(strtolower($match[1]));
		  }
		  $row[$field] = trim($row[$field]," ,");
		  break;
		default:
		  if ($field_key = array_search($field,$mapping) and !empty($data[$field_key])) {
		    $row[$field] = str_replace(array("\"","'"),"",$data[$field_key]);
		  }
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _parse($file) {
  if (($data = sys_cache_get("ldif_".sha1($file)))) return $data;
  if (($message = sys_allowedpath(dirname($file)))) {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t} %s",$file,$message));
    return array();
  }
  $rows = array();
  $i = 0;
  if (($handle = fopen($file, "r"))) {
    while (!feof($handle)) {
	  $data = utf8_encode(trim(fgets($handle, 8192)));
	  if ($data!="" and $pos = strpos($data,":")) {
	    $data_key = strtolower(substr($data,0,$pos));
		$data_val = substr($data,$pos+1);
		if ($data_val!="" and $data_val[0]==":") {
		  $data_val = base64_decode(trim(substr($data_val,1)));
		}
		if ($data_key=="" or $data_val=="") continue;
		$rows[$i][$data_key] = trim($data_val);
	  } else if ($data=="") $i++;
	}
    fclose($handle);
  } else {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t}",$file));
	return array();
  }
  sys_cache_set("ldif_".sha1($file),$rows,LDIF_CACHE);
  return $rows;
}
}