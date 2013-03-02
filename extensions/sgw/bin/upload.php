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

define("NOCONTENT",true);
define("NOSESSION",true);
require("index.php");

if (empty($_REQUEST["item"]) and empty($_REQUEST["filename"])) sys_error("Missing parameters.","403 Forbidden");
sys_check_auth();

$ext = modify::getfileext(urldecode($_SERVER["REQUEST_URI"]));
if (in_array($ext, explode(",", INVALID_EXTENSIONS))) {
  sys_error(sprintf("this file extension is not allowed (%s)", $ext),"403 Forbidden");
}

$content_length = sys_get_header("Content-Length");
if ($content_length==0 and strtolower($_REQUEST["action"])!="move") {
  _upload_success();
}

if (strtolower($_REQUEST["action"])=="move" and !empty($_SERVER["HTTP_DESTINATION"])) {
  $_SERVER["REQUEST_URI"] = substr($_SERVER["HTTP_DESTINATION"],strpos($_SERVER["HTTP_DESTINATION"],"/sgdav/"));
}

if ($_REQUEST["item"]=="session") {
  $path = str_replace("//","/",urldecode($_SERVER["REQUEST_URI"]));
  $filename = basename($path);
  $path = dirname($path);
  if (sys_strbegins($filename,"~") or sys_strbegins($filename,".") or modify::getfileext($filename)=="tmp") {
    $target = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1($path)."--".urlencode($filename);
    if ($fp = fopen("php://input","r") and $ft = fopen($target,"wb")) {
	  while (!feof($fp)) fwrite($ft,fread($fp,8192));
	  fclose($fp);
	  fclose($ft);
	  _upload_success();
	} else {
	  sys_error("cant write","403 Forbidden");
	}
  } else {
	$target_lnk = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1($path)."--".urlencode($filename).".link";
	if (file_exists($target_lnk)) {
	  $link = file($target_lnk);
	  if (preg_match("|^/sgdav/(.+)/(\d+)_0__.+|",$link[0],$match)) {
	    $_REQUEST["folder"] = "/".$match[1]."/";
	    $_REQUEST["item"] = array($match[2]);
	  }
	} else {
	  $db_path = substr($path,strlen("/sgdav"));
	  _upload_create_file($db_path, $target_lnk, $path, $filename);
} } }

// TODO use sgsml class

folder_process_session_request();
folder_build_folders();
$GLOBALS["table"] = db_get_schema($GLOBALS["schemafile"],$GLOBALS["tfolder"],$GLOBALS["tview"]);
$GLOBALS["tname"] = $GLOBALS["table"]["att"]["NAME"];

sys_process_session_request();

if (empty($_REQUEST["field"])) $field = "filedata"; else $field = ltrim($_REQUEST["field"],"_");
$field = sql_fieldname($field);

if ($content_length > _upload_get_limit($field)) {
  sys_error("Upload failed: file is too big. Please upload a smaller one. (insufficient folder rights)","409 Conflict");
}

$t = &$GLOBALS["t"];
$t["sqlvars"]["item"] = $_REQUEST["item"];
$t["sqlvarsnoquote"]["permission_sql_read_nq"] = $_SESSION["permission_sql_write"];
$t["sqlvarsnoquote"]["permission_sql_write_nq"] = $_SESSION["permission_sql_write"];

$row = db_select_first($GLOBALS["tname"],array_unique(array($field,"folder","id","dsize")),$t["sqlwhere"],"",$t["sqlvars"],array("sqlvarsnoquote"=>$t["sqlvarsnoquote"]));
if (empty($row["folder"])) sys_error("file not found in database.");

if (!db_get_right($row["folder"],"write")) {
  sys_error("Access to this file has been denied. (insufficient folder rights)","403 Forbidden");
}

if (empty($row[$field])) $row[$field] = "";
$row_filename = $row[$field];

if ($row_filename!="") {
  $file = explode("|",trim($row[$field],"|"));
  if (empty($_REQUEST["subitem"])) $_REQUEST["subitem"] = 0;
  if (!empty($file[$_REQUEST["subitem"]])) $row_filename = $file[$_REQUEST["subitem"]]; else $row_filename = "";
}

if ($row_filename=="") {
  $filename = urldecode(basename($_REQUEST["filename"]));
  list($target,$filename) = sys_build_filename($filename,"simple_files");
  dirs_checkdir($target);
  $target .= sys_get_pathnum($row["folder"])."/";
  dirs_checkdir($target);
  $target .= md5($row["folder"]).$filename;
  $newfilename = $target;
} else {
  if (file_exists($row_filename.".lck") and !sys_can_unlock($row_filename,$_SESSION["username"])) {
    sys_error("Access to this file has been denied.","409 Conflict");
  } else {
	$i = 1;
    $newfilename = preg_replace("|_rev\d+|","",$row_filename);
	$base = basename($newfilename);
	$dir = dirname($newfilename);
	while (file_exists($newfilename)) {
	  if (($pos = strrpos($base,"."))) $name = substr($base,0,$pos)."_rev".($i++).substr($base,$pos); else $name = $base."_rev".($i++);
	  $newfilename = $dir."/".$name;
    }
    if (!rename($row_filename,$newfilename)) {
	  sys_error("Error moving file","409 Conflict");
	}
	$target = $row_filename;

	if (strtolower($_REQUEST["action"])=="move" and !empty($_REQUEST["filename"])) {
	  $path = str_replace("//","/",urldecode($_REQUEST["filename"]));
	  $tmpfile = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1(dirname($path))."--".urlencode(basename($path));
	  if (file_exists($tmpfile)) rename($tmpfile,$target);
} } }

$result = _upload_append_file($row,$field,$target,$newfilename);
if (!$result) {
  @rename($newfilename,$row_filename);
  sys_error("Error writing file","409 Conflict");
}

function _upload_append_file($row,$field,$target,$newfilename) {
  $t = $GLOBALS["t"];
  if (!file_exists($target) and $fp=fopen("php://input","r") and $ft=fopen($target,"wb")) {
    while (!feof($fp)) fwrite($ft,fread($fp,8192));
    fclose($fp);
    fclose($ft);
  }
  if (!file_exists($target)) return false;
  if ($row[$field]!="") $files = explode("|",trim($row[$field],"|")); else $files = array();
  $files[] = $newfilename;
  $size = filesize($newfilename) + $row["dsize"];
  $history = sprintf("Item edited (%s) by %s at %s",$field,$_SESSION["username"],sys_date("m/d/y g:i:s a"))."\nFile: + ".modify::basename($newfilename)."\n\n";
  $error_sql = db_update($GLOBALS["tname"],array($field=>"|".implode("|",$files)."|","dsize"=>$size,"history"=>$history),$t["sqlwhere"],$t["sqlvars"],array("sqlvarsnoquote"=>$t["sqlvarsnoquote"]));
  if ($error_sql=="") {
    db_update_treesize($GLOBALS["tname"],$row["folder"]);
    db_search_update($GLOBALS["tname"],$t["sqlvars"]["item"],$GLOBALS["table"]["fields"]);
	_upload_success("204 No Content");
  }
  return false;
}

function _upload_get_limit($field_name) {
  $size = 0;
  $fields = $GLOBALS["table"]["fields"];
  if (isset($fields[$field_name]["SIMPLE_FILE_SIZE"])) {
    $size = $fields[$field_name]["SIMPLE_FILE_SIZE"];
    $size = str_replace(array("M","K"),array("000000","000"),$size);
  }
  return $size;
}

function _upload_process_folder_string($folder) {
  $parent = 0;
  $parent_last = 0;
  $nodes = explode("/",$folder);
  $left = count($nodes);
  foreach ($nodes as $node) {
    $left--;
    if ($node=="") continue;
	$where = array("ftitle=@title@", "parent=@parent@", $_SESSION["permission_sql_read"]);
	$vars = array("title"=>$node,"parent"=>$parent);
    $row_id = db_select_value("simple_sys_tree","id",$where,$vars);
    if (!empty($row_id)) {
	  $parent_last = $parent;
	  $parent = $row_id;
	} else {
	  return array(0,$left,$parent);
	}
  }
  return array($parent,$left,$parent_last);
}

function _upload_create_file($db_path, $target_lnk, $path, $filename) {
  list($id,$left,$unused) = _upload_process_folder_string($db_path."/");
  if ($left!=0 or $id==0) sys_error("path not found","409 Conflict");
  
  $ftype = db_select_value("simple_sys_tree","ftype","id=@id@",array("id"=>$id));
  if (db_get_right($id, "write") and !empty($ftype) and $ftype=="files") {
  
	list($target,$a_filename) = sys_build_filename($filename,"simple_files");
	dirs_checkdir($target);
	$target .= sys_get_pathnum($id)."/";
	dirs_checkdir($target);
	$target .= md5($id).$a_filename;

	if ($fp = fopen("php://input","r") and $ft = fopen($target,"wb")) {
	  while (!feof($fp)) fwrite($ft,fread($fp,8192));
	  fclose($fp);
	  fclose($ft);
	  $a_id = sql_genID("simple_files")*100+$_SESSION["serverid"];
	  $data = array(
		"id"=>$a_id, "folder"=>$id, "dsize"=>filesize($target),
		"filedata"=>"|".$target."|", "filename"=>$filename,
		"rread_users"=>"|anonymous|", "rwrite_users"=>"|anonymous|",
		"history"=>sprintf("Item created by %s at %s\n",$_SESSION["username"],sys_date("m/d/y g:i:s a"))
	  );
 	  $error_sql = db_insert("simple_files",$data);
	  if ($error_sql=="") {
		db_update_treesize("simple_files",$id);
		$fields = array("filename"=>"text", "filedata"=>"files", "folder"=>"id", "id"=>"id");
		db_search_update("simple_files",$a_id,array(),$fields);
	    sys_log_stat("new_records",1);

		file_put_contents($target_lnk, $path."/".$a_id."_0__".$filename."\n".$target, LOCK_EX);
		_upload_success();
  } } }
  sys_error("cant write new","403 Forbidden");
}

function _upload_success($string="201 Created") {
  header("HTTP/1.1 ".$string);
  exit;
}