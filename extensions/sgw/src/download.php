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

$bad_extensions = explode(",", INVALID_EXTENSIONS);
$inline_extensions = array("gif","jpg","jpeg","png");

if (empty($_REQUEST["item"]) and empty($_REQUEST["find"])) sys_error("Missing parameters.","403 Forbidden");

sys_check_auth();

if (isset($_REQUEST["item"]) and $_REQUEST["item"]=="session") {
  $path = str_replace("//","/",urldecode($_SERVER["REQUEST_URI"]));
  $filename = basename($path);
  $path = dirname($path);
  $local_file = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1($path)."--".urlencode($filename);
  if (file_exists($local_file)) _download_file($local_file,basename($local_file),"attachment");
 
  $local_file = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1($path)."--".urlencode($filename).".link";
  if (file_exists($local_file)) {
	$link = file($local_file);
	if (preg_match("|^/sgdav/(.+)/(\d+)_0__.+|",$link[0],$match)) {
	  $_REQUEST["folder"] = "/".$match[1]."/";
	  $_REQUEST["item"] = array($match[2]);
} } }

if (isset($_REQUEST["dispo"]) and $_REQUEST["dispo"]=="noinline") $dispo = "attachment"; else $dispo = "inline";
if (!empty($_REQUEST["find"])) {
  $result = folder_process_session_find((array)$_REQUEST["find"]);
  $_REQUEST = array_merge($_REQUEST, $result);
}

if (empty($_REQUEST["field"])) $field = "filedata"; else $field = ltrim($_REQUEST["field"],"_");

if (empty($_REQUEST["folder"]) and !empty($_REQUEST["folder2"])) $_REQUEST["folder"] = $_REQUEST["folder2"];
if (empty($_REQUEST["view"]) and !empty($_REQUEST["view2"])) $_REQUEST["view"] = $_REQUEST["view2"];
if (empty($_REQUEST["folder"])) {
  header("Content-Length: 0");
  exit;
}

$folder = folder_from_path($_REQUEST["folder"]);
$row_filename = ajax::file_download($folder, @$_REQUEST["view"], @$_REQUEST["item"], $field, @$_REQUEST["subitem"], false);

$filename = modify::basename($row_filename);
$ext = substr(modify::getfileext($filename),0,3);
if (in_array($ext,$bad_extensions))	{
  sys_error("{t}Access to this file has been denied.{/t} ({t}this file extension is not allowed{/t})","403 Forbidden");
}

if ($dispo=="inline" and !in_array($ext,$inline_extensions)) $dispo="attachment";
$modified = filemtime($row_filename);
$etag = '"'.md5($row_filename.$modified).'"';
header("Last-Modified: ".gmdate("D, d M Y H:i:s", $modified)." GMT");
header("ETag: $etag");
if (!empty($_SERVER["HTTP_IF_NONE_MATCH"]) and $etag == stripslashes($_SERVER["HTTP_IF_NONE_MATCH"]) and !DEBUG) {
  header("HTTP/1.0 304 Not Modified");
  exit;
}

$resize = false;
if (isset($_REQUEST["image_width"]) or isset($_REQUEST["image_height"])) $resize = true;

if (!$resize and $result = validate::checkvirus($row_filename)) {
  sys_error("Virus scanner: ".$result,"403 Forbidden");
} else {
  if ($resize) $row_filename = _download_resize($row_filename);
  _download_file($row_filename,$filename,$dispo);
}

function _download_file($row_filename,$filename,$dispo) {
  if (($fp = fopen($row_filename,"rb"))) {
    if (strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")) $filename = rawurlencode($filename);
	sys_log_stat("downloads",1);
	header("Expires: ".gmdate("D, d M Y H:i:s", NOW)." GMT");
	header("Content-Type: ".($dispo=="inline"?"image/jpg":"application/octet-stream")."; charset=utf-8");
    header("Content-Disposition: ".$dispo."; filename=\"".$filename."\"");
    header("Content-Length: ".(int)filesize($row_filename));
    header("Content-Transfer-Encoding: binary");
    while (!feof($fp)) echo fread($fp,8192);
    fclose($fp);
    exit;
  }
}

function _download_resize($row_filename) {
  $row_filename_resize = SIMPLE_CACHE."/thumbs/".sha1($row_filename)."_".filemtime($row_filename)."_".$_REQUEST["image_width"]."_".$_REQUEST["image_height"].".jpg";
  if (file_exists($row_filename_resize)) return $row_filename_resize;
  $src_files = array("gif","jpg","jpeg","png");
  $ext = modify::getfileext($row_filename);
  $new_width = "";
  $new_height = "";
  if (empty($_REQUEST["image_width"]) and empty($_REQUEST["image_height"])) {
	$new_width = 250;
	$new_height = 200;
  }
  if (isset($_REQUEST["image_width"]) and is_numeric($_REQUEST["image_width"]) and $_REQUEST["image_width"]>0) $new_width = $_REQUEST["image_width"];
  if (isset($_REQUEST["image_height"]) and is_numeric($_REQUEST["image_height"]) and $_REQUEST["image_height"]>0) $new_height = $_REQUEST["image_height"];

  if ($new_width!="" or $new_height!="") $resize = "-resize \"".$new_width."x".$new_height.">\"";
  if ($resize!="" or !in_array($ext,$src_files)) {
	$result = "";
	$src = modify::realfilename($row_filename);
	$target = modify::realfilename($row_filename_resize);
	$result = sys_exec(sys_find_bin("convert")." -quality 50 ".$resize." ".$src."[0] ".$target);
    if ($result=="") {
	  $row_filename = $row_filename_resize;
	} else sys_log_message_log("php-fail","proc_open: ".$result);
	
	if ($result!="" and in_array($ext,$src_files)) {
	  list($width, $height) = @getimagesize($row_filename);
	  if ($width!="" and $height!="") {
	    if ($width!=$new_width or $height!=$new_height) {
		  $prop = $width/$height;
		  if ($width!=$new_width and $height!=$new_height) {
		    $new_height2 = round($new_width/$prop);
			if ($new_height2 > $new_height) $new_width = round($new_height*$prop);
		  } else if ($width!=$new_width) {
		    $new_height = round($new_width/$prop);
		  } else $new_width = round($new_height*$prop);

	      $image_p = imagecreatetruecolor($new_width, $new_height);
		  imagecopyresized($image_p, imagecreatefromstring(file_get_contents($row_filename)), 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		  imagejpeg($image_p,$row_filename_resize,50);
		  $row_filename = $row_filename_resize;
  } } } }
  return $row_filename;
}