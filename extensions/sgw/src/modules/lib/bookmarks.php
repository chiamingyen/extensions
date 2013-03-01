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

class lib_bookmarks extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $count = count(self::_get_data($path));
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = self::_get_data($path);
  $rows = array();
  $i = 0;
  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
		case "sort": $row[$field] = $i; break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		default:
		  if (isset($data[$field])) {
		    $row[$field] = $data[$field];
		  } else $row[$field] = "";
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
//  $order = str_replace("category","",$order);
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _get_data($file) {
  if (($data = sys_cache_get("bookmarks_".sha1($file)))) return $data;
  if (($message = sys_allowedpath(dirname($file)))) {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t} %s",$file,$message));
    return array();
  }
  if (!($data = @file_get_contents($file))) {
    sys_warning("{t}The url doesn't exist.{/t} (".$file.")");
  	return array();
  }
  preg_match_all("!(?:<h3.*?>(.*?)</h3>|<dd>(.*?)\n|<a href=\"(.*?)\".*?(?:add_date=\"(.*?)\".*?>|>)(.*?)</a>)!msi",$data,$matches);
  $category = "";
  $rows = array();
  if (is_array($matches) and count($matches)==6) {
    for ($i=0; $i<count($matches[0]); $i++) {
	  $url = "";
	  $name = "";
	  $created = 0;
	  $desc = "";
	  if (!empty($matches[1][$i])) $category = modify::htmlunquote($matches[1][$i]);
	  if (!empty($matches[2][$i+1])) $desc = modify::htmlunquote($matches[2][$i+1]);
	  if (!empty($matches[3][$i])) $url = $matches[3][$i];
	  if (!empty($matches[4][$i])) $created = $matches[4][$i];
	  if (!empty($matches[5][$i])) $name = modify::htmlunquote($matches[5][$i]);
	  if ($name!="" or $url!="") {
	    $rows[] = array("category"=>$category, "bookmarkname"=>$name, "description"=>$desc, "url"=>$url, "created"=>$created);
  } } }
  sys_cache_set("bookmarks_".sha1($file),$rows,BOOKMARKS_CACHE);
  return $rows;
}
}