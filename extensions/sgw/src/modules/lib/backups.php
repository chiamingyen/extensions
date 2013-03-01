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

class lib_backups extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $path = SIMPLE_STORE."/backup/";
  if (sys_allowedpath($path)!="") return 0;
  $files = 0;
  if (($handle=@opendir($path))) {
    while (false !== ($file = readdir($handle))) {
      if ($file=='.' or $file=='..' or is_dir($path.$file)) continue;
	  if (modify::getfileext($file)!="tar") continue;
	  $files++;
    }
    closedir($handle);
  }
  return $files;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $path = SIMPLE_STORE."/backup/";
  if (sys_allowedpath($path)!="") return array();
  $file_array = array();
  if (!$handle=@opendir($path)) return array();
  while (false !== ($file = readdir($handle))) {
    if ($file=='.' or $file=='..' or is_dir($path.$file)) continue;
	if (modify::getfileext($file)!="tar") continue;
	$file_array[]=$file;
  }
  closedir($handle);

  $rows = array();
  foreach ($file_array as $filename) {
    $data = stat($path.$filename);
	$row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "filedata":
	    case "id": $row[$field] = $path.$filename; break;
	    case "folder": $row[$field] = $vars["folder"]; break;
		case "category": 
		  $row[$field] = str_replace(array("__"),array("/"),substr(modify::basename($filename),0,strpos(modify::basename($filename),"--")));
		  break;
		case "filename":
		  $row[$field] = basename(str_replace(array("__"),array("/"),modify::basename($filename)));
		  $row[$field] = substr($row[$field],0,strpos($row[$field],"--"));
		  if ($row[$field]=="") $row[$field] = $filename;
		  break;
		case "searchcontent": $row[$field] = $filename; break;
		case "createdby":
		case "lastmodifiedby": $row[$field] = ""; break;
		case "created": $row[$field] = $data["ctime"]; break;
		case "lastmodified": $row[$field] = $data["mtime"]; break;
		case "filesize": $row[$field] = $data["size"]; break;
		default: $row[$field] = ""; break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  return $rows;
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"]) or sys_allowedpath(dirname($vars["id"]))!="" or !file_exists($vars["id"])) return "error";
  unlink($vars["id"]);
  if (!file_exists($vars["id"])) return ""; else return "error";
}

static function insert($path,$data,$mfolder) {
  $path = SIMPLE_STORE."/backup/";
  if (sys_allowedpath($path)!="") return;
  $source = $data["filedata"];
  $target = $path.$data["filename"];
  if (!is_dir($source) and file_exists($source)) {
    if (file_exists($target)) unlink($target);
	rename($source,$target);
  }
}

}