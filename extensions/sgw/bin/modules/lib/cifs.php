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

if (!function_exists("java_get_base")) require("lib/java/java.php");

class lib_cifs extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  static $cache = array();
  if (!isset($cache[$path])) {
    $tree = array();
    self::_get_dirs($path, 1, 0, $parent, $recursive, $tree);
    $cache[$path] = $tree;
  }
  return $cache[$path];
}

static function count($path,$where,$vars,$mfolder) {
  $count = 0;
  if (substr_count($path,"/") < 2) return 0;
  try {
    $ntlm = self::_get_ntlm($mfolder);
    $w = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
    if (($files = $w->listFiles())) {
	  foreach ($files as $file) {
	    if ($file->isFile() and modify::getfileext($file->getName())!="meta") $count++;
	  }
    }
  } catch (Exception $e) {
    if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
    sys_warning("Access denied. [count] ".$msg." ".$path);
  }
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $file_array = array();
  try {
    $ntlm = self::_get_ntlm($mfolder);
    $w = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
    if (($files = $w->listFiles())) {
	  foreach ($files as $file) if ($file->isFile()) $file_array[] = $file;
    }
  } catch (Exception $e) {
    if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
    sys_warning("Access denied. [select] ".$msg." ".$path);
  }
  if ($fields==array("*")) $fields = array("id", "folder");
  $rows = array();
  foreach ($file_array as $file) {
	$ext = modify::getfileext($file->getName());
	if ($ext=="meta") continue;
	$row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "filedata":
	    case "id": $row[$field] = $path.$file->getName(); break;
	    case "folder": $row[$field] = $path; break;
	    case "filedata_show":
	    case "filename":
		case "searchcontent": $row[$field] = (string)$file->getName(); break;
		case "fileext": $row[$field] = $ext; break;
		case "fileatime": $row[$field] = $file->getLastAccess(); break;
		case "created": $row[$field] = $file->createTime()/1000; break;
		case "lastmodified": $row[$field] = $file->getLastModified()/1000; break;
		case "lastmodifiedby": $row[$field] = ""; break;
		case "filesize": $row[$field] = $file->length(); break;
		default: $row[$field] = ""; break;
	  }
	}
	$row["_lastmodified"] = $file->getLastModified()/1000;
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  $rows = sys_select($rows,$order,$limit,$fields);
  if (count($rows)>0) {
    if (in_array("filedata",$fields)) {
      foreach ($rows as $key=>$row) {
		$filename = sys_cache_get_file("cifs", $row["id"].$row["_lastmodified"], "--".modify::basename($row["id"]), true);
		if (!file_exists($filename) and (!isset($row["filesize"]) or $row["filesize"]<CIFS_PREVIEW_LIMIT)) {
		  $w = new Java("jcifs.smb.SmbFile","smb://".$row["id"],$ntlm);
		  $out = new Java("java.io.FileOutputStream",modify::realfilename($filename,false));
		  $w->store($out);
		}
		$rows[$key]["filedata"] = $filename;
	  }
	}
    foreach ($rows as $key=>$row) {
	  $meta = array();
	  try {
		$meta = self::_get_meta($row["id"],$mfolder,$ntlm);
	  } catch (Exception $e) {
	    if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	    sys_warning("Access denied. [get_meta] ".$msg." ".$path);
	  }
	  foreach ($meta as $mkey=>$mval) $rows[$key][$mkey] = $mval;
	}
  }
  return $rows;
}

static function rename_folder($title,$path,$mfolder) {
  try {
	$ntlm = self::_get_ntlm($mfolder);
	$source = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
	$dest = new Java("jcifs.smb.SmbFile","smb://".dirname($path)."/".$title."/",$ntlm);
	$source->renameTo($dest);
	if (!$dest->isDirectory()) exit("Access denied.");
  } catch (Exception $e) {
	if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	exit("Access denied. [rename_folder] ".$msg." ".$path);
  }
  return "ok";
}

static function create_folder($title,$parent,$mfolder) {
  try {
	$ntlm = self::_get_ntlm($mfolder);
	$w = new Java("jcifs.smb.SmbFile","smb://".$parent.$title."/",$ntlm);
	$w->mkdir();
	if (!$w->isDirectory()) exit("Access denied.");
  } catch (Exception $e) {
	if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	exit("Access denied. [create_folder] ".$msg." ".$parent);
  }
  return "ok";
}

static function delete_folder($path,$mfolder) {
  try {
	$ntlm = self::_get_ntlm($mfolder);
	$w = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
	$w->delete();
	if ($w->isDirectory()) exit("Access denied.");
  } catch (Exception $e) {
	if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	exit("Access denied. [delete_folder] ".$msg." ".$path);
  }
  return "ok";
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  try {
	$ntlm = self::_get_ntlm($mfolder);
	$w = new Java("jcifs.smb.SmbFile","smb://".$vars["id"],$ntlm);
	$w->delete();
	if ($w->exists()) exit("Access denied.");
	$w = new Java("jcifs.smb.SmbFile","smb://".$vars["id"].".meta",$ntlm);
	if ($w->exists()) $w->delete();
  } catch (Exception $e) {
	if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	exit("Access denied. [delete] ".$msg." ".$vars["id"]);
  }
  return "";
}

static function update($path,$data,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  $target = $path.basename($vars["id"]);
  try {
	$ntlm = self::_get_ntlm($mfolder);
	$source = !empty($data["filedata"]) ? $data["filedata"] : $vars["id"];
	if (empty($data["filedata"])) {
	  $in = new Java("jcifs.smb.SmbFile", "smb://".$source.".meta", $ntlm);
	  $dest = new Java("jcifs.smb.SmbFile", "smb://".$target.".meta", $ntlm);
	  if ($in->exists()) $in->renameTo($dest);
	  $in = new Java("jcifs.smb.SmbFile", "smb://".$source, $ntlm);
	  $dest = new Java("jcifs.smb.SmbFile", "smb://".$target, $ntlm);
	  $in->renameTo($dest);
	} else if (file_exists($source) and sys_strbegins($source, SIMPLE_CACHE."/upload/")) {
	  $in = new Java("java.io.FileInputStream", modify::realfilename($source,false));
	  $dest = new Java("jcifs.smb.SmbFile", "smb://".$target, $ntlm);
	  $dest->load($in);
	}
	self::_set_meta($data,$target,$mfolder,$ntlm);
  } catch (Exception $e) {
	if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	return "Access denied. [update] ".$msg." ".$source." ".$target;
  }
  return "";
}

static function insert($path,$data,$mfolder) {
  $source = $data["filedata"];
  if (!is_dir($source) and file_exists($source)) {
    $target = $path.modify::basename($data["filedata"]);
	try {
	  $ntlm = self::_get_ntlm($mfolder);
      $in = new Java("java.io.FileInputStream",modify::realfilename($source,false));
      $w = new Java("jcifs.smb.SmbFile","smb://".$target,$ntlm);
	  $w->load($in);
      self::_set_meta($data,$target,$mfolder,$ntlm);
	} catch (Exception $e) {
	  if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	  return "Access denied. [insert] ".$msg." ".$target;
	}
  }
  return "";
}

private static function _has_subfolder($path,$ntlm) {
  try {
    $w = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
	if (($files = $w->listFiles())) {
	  foreach ($files as $file) {
	    if ($file->isDirectory() and $file->getType()!=32 and (!in_array($file->getType(),array(8,16)) or !$file->isHidden())) {
		  return true;
		}
	  }
	}
  } catch (Exception $unused) {}
  return false;
}

private static function _get_dirs($path, $left, $level, $parent, $recursive, &$tree) {
  $right = $left+1;
  $subfolders = 0;
  if ($recursive and sys_is_folderstate_open($path,"cifs",$parent)) {
    try {
	  $ntlm = self::_get_ntlm($parent);
	  $w = new Java("jcifs.smb.SmbFile","smb://".$path,$ntlm);
	  if (($files = $w->listFiles())) {
	    $dirs = array();
	    foreach ($files as $file) {
		  $type = $file->getType();
	      if ($file->isDirectory() and $type!=32 and (!in_array($type,array(8,16)) or !$file->isHidden())) {
		    $dirs[] = $path.str_replace(chr(0),"",$file->getName());
		    $subfolders = 1;
		  }
	    }
	    natcasesort($dirs);
	    foreach ($dirs as $dir) {
	      $right = self::_get_dirs($dir, $right, $level+1, $parent, true, $tree);
 	    }
	  }
	} catch (Exception $e) {
	  if (DEBUG_JAVA) $msg = java_cast($e, "string"); else $msg = $e->getMessage();
	  sys_warning("Access denied. [get_dirs] ".$msg." " .$path);
	}
  } else {
    $right = $right+2;
	$subfolders = 1;
	if ($level>1) $subfolders = (int)self::_has_subfolder($path, self::_get_ntlm($parent));
  }
  $icon = "";
  if ($level==0) $icon = "sys_nodb_cifs.png";
  $tree[$left] = array("id"=>$path,"lft"=>$left,"rgt"=>$right,"flevel"=>$level,"ftitle"=>basename($path),
    "ftype"=>"sys_nodb_cifs","icon"=>$icon,"ffcount"=>$subfolders);
  return $right+1;
}

private static function _get_meta($id,$mfolder,$ntlm) {
  $w = new Java("jcifs.smb.SmbFile","smb://".$id,$ntlm);
  $lastmodified = $w->getLastModified()/1000;
  $filename = sys_cache_get_file("cifs", $id.$lastmodified, "--".modify::basename($id.".meta"), true);
  if (!file_exists($filename)) {
	$w = new Java("jcifs.smb.SmbFile","smb://".$id.".meta",$ntlm);
	if (!$w->exists()) return array();
	$out = new Java("java.io.FileOutputStream",modify::realfilename($filename,false));
	$w->store($out);
  }
  return sys_build_meta(file_get_contents($filename),array());
}

private static function _set_meta($data,$id,$mfolder,$ntlm) {
  $w = new Java("jcifs.smb.SmbFile","smb://".$id,$ntlm);
  $lastmodified = $w->getLastModified()/1000;
  $sourcefile = sys_cache_get_file("cifs", $id.$lastmodified, "--".modify::basename($id.".meta"), true);
  if (file_exists($sourcefile)) {
	$data = sys_build_meta(file_get_contents($sourcefile),$data);
  }
  $drop = array("filedata", "folder", "created", "lastmodified", "handler", "mfolder", "dsize", "id");
  $data = sys_build_meta_str($data, array_diff(array_keys($data), $drop));
  if ($data=="") {
	$w = new Java("jcifs.smb.SmbFile","smb://".$id.".meta",$ntlm);
	if ($w->exists()) $w->delete();
  } else {
	file_put_contents($sourcefile, $data, LOCK_EX);
	$in = new Java("java.io.FileInputStream",modify::realfilename($sourcefile,false));
	$w = new Java("jcifs.smb.SmbFile","smb://".$id.".meta",$ntlm);
	$w->load($in);
  }
}

private static function _get_ntlm($mfolder) {
  static $cache = array();
  if (empty($cache[$mfolder])) {
	if (!function_exists("java_require")) {
	  if (!isset($cache[$mfolder])) {
	    sys_warning(sprintf("%s is not compiled / loaded into PHP.","PHP/Java Bridge"));
	  }
	  $cache[$mfolder] = false;
	} else {
	  java_require("jcifs-1.3.8_tb.jar");
	  $conf = new JavaClass("jcifs.Config");
	  $conf->setProperty("jcifs.smb.client.responseTimeout","5000");
      $conf->setProperty("jcifs.resolveOrder","LMHOSTS,DNS");
	  $conf->setProperty("jcifs.smb.client.soTimeout","120000");

	  // TODO2 option for hidden shares
	  $creds = sys_credentials($mfolder);
	  $creds["domain"] = "";
	  if ($creds["options"]!="") {
		$options = explode(",",$creds["options"]);
		foreach ($options as $option) {
		  $option = trim($option);
		  if (sys_strbegins($option,"domain=")) $creds["domain"] = substr($option,7);
		}
      }
	  $cache[$mfolder] = new Java("jcifs.smb.NtlmPasswordAuthentication",$creds["domain"],$creds["username"],$creds["password"]);
	}
  }
  return $cache[$mfolder];
}
}