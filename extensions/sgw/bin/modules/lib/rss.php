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

class lib_rss extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  return array(array("id"=>$path,"lft"=>1,"rgt"=>2,"flevel"=>0,"ftitle"=>"News",
  	"ftype"=>"sys_nodb_rss","ffcount"=>0));
}

static function count($path,$where,$vars,$mfolder) {
  $count = count(self::_parse($path));
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $datas = self::_parse($path);
  $rows = array();
  foreach ($datas as $data) {
    if (empty($data["title"])) continue;
    $row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".md5($data["link"]); break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		default:
		  $row[$field] = "";
		  if (isset($data[$field])) $row[$field] = trim(preg_replace("/<img[^>]+>/i","",$data[$field]));
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

private static function _get_link($item) {
  if (!empty($item["href"])) {
    return (string)$item["href"];
  } else return (string)$item;
}

private static function _parse($file) {
  if (($data = sys_cache_get("rss_".sha1($file)))) return $data;
  if (($message = sys_allowedpath(dirname($file)))) {
    sys_warning(sprintf("Cannot read the file %s. %s",$file,$message));
    return array();
  }
  if (!($data = @file_get_contents($file))) {
    sys_warning("The url doesn't exist. (".$file.")");
  	return array();
  }
  if (strpos(strtolower(substr($data,0,100)),"<?xml")===false) { // find RSS in website
    if (preg_match("/rss[^>]+?href=\"(.*?)\"/i",$data,$match)) {
	  $file = $match[1];
	  if (!($data = @file_get_contents($file))) {
	    sys_warning("The url doesn't exist. (".$file.")");
  	    return array();
  } } }
  try {
    $xml = @new SimpleXMLElement($data);
  }
  catch (Exception $e) {
    sys_warning("Error: ".$file." ".$e->getMessage());
    return array();
  }
  if (!is_object($xml)) return array();
  if (isset($xml->channel)) {
    $item0 = $xml->channel;
	$item0->subtitle = (string)$xml->channel->description;
	if (isset($xml->channel->item)) {
	  $items = $xml->channel->item;
	} else {
	  $items = $xml->item;
	}
  } else {
    $item0 = $xml;
	$items = $xml->entry;
  }
  $rows = array(array(
    "title"=>(string)$item0->title,
	"content"=>(string)$item0->subtitle,
	"link"=>self::_get_link($item0->link),
	"order"=>0
  ));
  $i=0;
  foreach ($items as $item) {
    if (isset($item->description)) $item->summary = (string)$item->description;
    $rows[] = array(
      "title"=>(string)$item->title,
	  "content"=>(string)$item->summary,
	  "link"=>self::_get_link($item->link),
	  "order"=>++$i
    );
  }
  sys_cache_set("rss_".sha1($file),$rows,RSS_CACHE);
  return $rows;
}
}