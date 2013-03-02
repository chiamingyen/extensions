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

class lib_fs extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  $tree = array();
  self::_get_dirs($path, 1, 0, $parent, $recursive, $tree);
  return $tree;
}

static function count($path,$where,$vars,$mfolder) {
  if (sys_allowedpath($path)!="") return 0;
  $files = 0;
  if (($handle=@opendir($path))) {
    while (false !== ($file = readdir($handle))) {
      if($file=='.' or $file=='..' or is_dir($path.$file) or modify::getfileext($file)=="meta") continue;
	  $files++;
    }
    closedir($handle);
  } else {
    sys_warning("Access denied.");
  }
  return $files;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  if (sys_allowedpath($path)!="") return array();
  $file_array = array();
  if (!$handle = @opendir($path)) return array();
  while (false !== ($file = readdir($handle))) {
    if ($file=='.' or $file=='..' or is_dir($path.$file)) continue;
	$file_array[]=$file;
  }
  closedir($handle);

  if ($fields==array("*")) $fields = array("id", "folder");
  $rows = array();
  foreach ($file_array as $filename) {
	$ext = modify::getfileext($filename);
	if ($ext=="meta") continue;
    $data = stat($path.$filename);
	$row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "filedata":
	    case "id": $row[$field] = $path.$filename; break;
	    case "folder": $row[$field] = $path; break;
	    case "filename":
		case "searchcontent": $row[$field] = $filename; break;
		case "fileext": $row[$field] = $ext; break;
		case "fileatime": $row[$field] = $data["atime"]; break;
		case "created": $row[$field] = $data["ctime"]; break;
		case "lastmodified": $row[$field] = $data["mtime"]; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "fileperms": $row[$field] = $data["mode"]; break;
		case "filesize": $row[$field] = $data["size"]; break;
		default: $row[$field] = ""; break;
	  }
	}
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  if (count($rows)>0) {
    foreach ($rows as $key=>$row) {
	  $meta = self::_get_meta($row["id"]);
	  foreach ($meta as $mkey=>$mval) $rows[$key][$mkey] = $mval;
	}
  }
  return $rows;
}

static function rename_folder($title,$path,$mfolder) {
  $newpath = dirname($path)."/".$title;
  if (sys_allowedpath($path)!="" or @is_dir($newpath) or file_exists($newpath)) return "";
  if (!@rename($path,$newpath)) exit("Access denied.");
  if (is_dir($newpath)) return "ok"; else return "";
}

static function create_folder($title,$parent,$mfolder) {
  $newpath = $parent.$title."/";
  if (sys_allowedpath($parent)!="" or is_dir($newpath) or file_exists($newpath)) return "";
  if (!sys_mkdir($newpath)) exit("Access denied.");
  if (is_dir($newpath)) return "ok"; else return "";
}

static function delete_folder($path,$mfolder) {
  if (sys_allowedpath($path)!="" or !is_dir($path)) return "";
  dirs_delete_all($path);
  if (file_exists($path)) exit("Access denied.");
  if (!file_exists($path)) return "ok"; else return "";
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"]) or sys_allowedpath(dirname($vars["id"]))!="" or !file_exists($vars["id"])) return "error";
  if (!@unlink($vars["id"])) exit("Access denied.");
  if (file_exists($vars["id"].".meta")) @unlink($vars["id"].".meta");
  if (!file_exists($vars["id"])) return ""; else return "error";
}

static function update($path,$data,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  if (sys_allowedpath($path)!="" or !is_dir($path)) return "";
  if (sys_allowedpath(dirname($vars["id"]))!="" or is_dir($vars["id"])) return "";

  $target = $path.basename($vars["id"]);
  if (isset($data["filedata"])) $source = $data["filedata"]; else $source = $vars["id"];

  if ($source!="" and $source!=$target and !is_dir($source) and file_exists($source)) {
    if (file_exists($target)) @unlink($target);
	if (file_exists($source.".meta")) @rename($source.".meta", $target.".meta");
    if (!@rename($source, $target)) return "Access denied.";
  }
  self::_set_meta($data,$target);
  return "";
}

static function insert($path,$data,$mfolder) {
  if (sys_allowedpath($path)!="" or !is_dir($path)) return "";
  $sources = explode("|",trim($data["filedata"],"|"));
  foreach ($sources as $source) {
    $target = $path.modify::basename($source);
    if (is_dir($source) or !file_exists($source)) continue;
    if (file_exists($target)) return "[1] Access denied.";
	if ($source!=$target and !rename($source, $target)) return "[2] Access denied.";
	self::_set_meta($data,$target);
  }
  return "";
}

private static function _has_subfolder($path) {
  if (($handle=@opendir($path))) {
    while (false !== ($file = readdir($handle))) {
      if ($file=='.' or $file=='..' or !is_dir($path.$file)) continue;
	  return true;
    }
    closedir($handle);
  }
  return false;
}

private static function _get_dirs($path, $left, $level, $parent, $recursive, &$tree) {
  if (sys_allowedpath($path)!="") return $left;
  $right = $left+1;

  if ($recursive and sys_is_folderstate_open($path,"fs",$parent)) {
    $subfolders = 0;
	$folders = array();
    if (($handle=@opendir($path))) {
      while (false !== ($file = readdir($handle))) {
        if ($file!='.' and $file!='..' and is_dir($path.$file)) {
		  $folders[] = $path.$file."/";
		  $subfolders = 1;
	} } }
	natcasesort($folders);
	foreach ($folders as $folder) {
      $right = self::_get_dirs($folder, $right, $level+1, $parent, true, $tree);
	}
  } else {
    $right = $right+2;
	$subfolders = (int)self::_has_subfolder($path);
  }
  $icon = "";
  if ($level==0) $icon = "sys_nodb_fs.png";
  $tree[$left] = array("id"=>$path,"lft"=>$left,"rgt"=>$right,"flevel"=>$level,"ftitle"=>basename($path),
  	"ftype"=>"sys_nodb_fs","icon"=>$icon, "ffcount"=>$subfolders);
  return $right+1;
}

private static function _get_meta($id) {
  $filename = $id.".meta";
  if (!file_exists($filename)) return array();
  return sys_build_meta(file_get_contents($filename),array());
}

private static function _set_meta($data,$id) {
  $sourcefile = $id.".meta";
  if (file_exists($sourcefile)) {
    $data = sys_build_meta(file_get_contents($sourcefile),$data);
  }
  $drop = array("filedata", "folder", "created", "lastmodified", "handler", "mfolder", "dsize", "id");
  $data = sys_build_meta_str($data, array_diff(array_keys($data), $drop));
  if ($data=="") {
    @unlink($sourcefile);
  } else {
    file_put_contents($sourcefile, $data, LOCK_EX);
  }
}
}