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

class lib_xml extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  if (sys_allowedpath(dirname($path))!="") return array();
  $data = self::_get_data($path);
  $right = 2;
  $tree = array();
  if ($recursive and count($data)>0 and sys_is_folderstate_open($path,"xml",$parent) and file_exists($path)) {
	foreach (array_keys($data) as $folder) {
	  $tree[$right] = array("id"=>$path."/".$folder,"lft"=>$right,"rgt"=>$right+1,"flevel"=>1,
		"ftitle"=>$folder,"ftype"=>"nodb_".basename($path)."_".$folder,"ffcount"=>0);
	  $right = $right+2;
	}
  }
  $icon = "sys_nodb_xml.png";
  $tree[1] = array("id"=>$path,"lft"=>1,"rgt"=>$right,"flevel"=>0,"ftitle"=>basename($path),"ftype"=>"sys_nodb_xml","icon"=>$icon);
  return $tree;
}

static function count($path,$where,$vars,$mfolder) {
  $folder = substr($path,strrpos($path,"/")+1);
  $path = substr($path,0,strrpos($path,"/"));
  if (is_dir($path) or !file_exists($path) or sys_allowedpath(dirname($path))!="") return 0;
  $data = self::_get_data($path);
  if (isset($data[$folder])) return count($data[$folder]);
  return 0;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = rtrim($path,"/");
  $folder = substr($path,strrpos($path,"/")+1);
  $file_path = substr($path,0,strrpos($path,"/"));
  if (is_dir($file_path) or !file_exists($file_path) or sys_allowedpath(dirname($file_path))!="") return array();
  $datas = self::_get_data($file_path);
  if (!isset($datas[$folder]) or count($datas[$folder])==0) return array();
  $datas = $datas[$folder];
  $rows = array();
  
  $i = 0;
  foreach ($datas as $data) {
	$i++;
    $row = array();
	foreach ($fields as $field) {
	  $row[$field] = "";
	  switch ($field) {
	    case "id": $row[$field] = $path."/?".$i; break;
	    case "folder": $row[$field] = $path; break;
		case "created": $row[$field] = 0; break;
		case "lastmodified": $row[$field] = 0; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "searchcontent": $row[$field] = implode(" ",$data); break;
		default:
		  if (empty($row[$field]) and isset($data->$field)) $row[$field] = (string)$data->$field;
		  break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  return sys_select($rows,$order,$limit,$fields);
}

private static function _get_data($file) {
  static $cache = array();
  if (isset($cache[$file])) return $cache[$file];
  if (sys_allowedpath(dirname($file))!="") return array();
  if (is_dir($file) or !file_exists($file)) return array();
  $data = get_object_vars(sys_get_xml($file));
  foreach ($data as $key=>$val) {
	if (is_array($val)) $data[$key] = $val; else $data[$key] = array($val);
  }
  $cache[$file] = $data;
  return $cache[$file];
}
}