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
error_reporting(E_ALL);

if (ini_get("register_globals")) {
  foreach (array_keys($GLOBALS) as $key) if (!in_array($key,array("GLOBALS","_REQUEST","_SERVER"))) unset($GLOBALS[$key]);
}
define("SIMPLE_STORE","../simple_store");
@include(SIMPLE_STORE."/config.php");
if (!defined("SETUP_DB_HOST")) exit;
@ignore_user_abort(0);

$file_conf = sys_custom("templates/core_css.conf");
$file_css = sys_custom("templates/core.css");

if (!empty($_REQUEST["css_style"])) {
  if (empty($_REQUEST["browser"])) $_REQUEST["browser"] = "firefox";
  predownload($file_conf,filemtime($file_css).$_REQUEST["css_style"]);
  require("lib/smarty/Smarty.class.php");
  $smarty = new Smarty();
  $smarty->compile_dir = SIMPLE_CACHE."/smarty";
  $smarty->template_dir = dirname($file_css);
  $smarty->config_dir = dirname($file_conf);
  $smarty->security = true;
  $smarty->left_delimiter = "<";
  $smarty->right_delimiter = ">";
  $smarty->assign("style",basename($_REQUEST["css_style"]));
  $smarty->assign("browser",$_REQUEST["browser"]);
  $output = $smarty->fetch("core.css");
  
  if ($_REQUEST["browser"]=="safari") {
	$from = array(
	  "/-moz-linear-gradient\(top,([^,]+),([^\)]+)\);/i",
	);
	$to = array(
	  "-webkit-gradient(linear, left top, left bottom, from(\\1), to(\\2));",
	);
	$output = preg_replace($from,$to,$output);
  }
  if ($_REQUEST["browser"]=="opera" or $_REQUEST["browser"]=="msie") {
	$output = preg_replace("/^.*(-moz-)/m","",$output);
  }
  if ($_REQUEST["browser"]=="msie") {
	$output = preg_replace("/max-height:([^;]+)px;/","height:expression(this.scrollHeight>\\1?'\\1px':'auto');",$output);
	$from = array(
	  "/linear-gradient\(top,\s?([^,]+),\s?([^\)]+)\);/i",
	);
	$to = array(
	  "filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='\\1',endColorstr='\\2');",
	);
	$output = preg_replace($from,$to,$output);
  }
  download($file_conf,"text/css",$output,true);
}

if (!empty($_REQUEST["image"])) {
  $image = basename($_REQUEST["image"]);
  if (!empty($_REQUEST["color"])) $newcolor = $_REQUEST["color"]; else $newcolor = "";

  predownload($file_conf,$image.$newcolor);

  if (in_array($image,array("folder1","folder2"))) {
    if ($newcolor=="") {
      header("HTTP/1.1 303 See Other");
	  header("Location: ext/".$image.".gif");
    } else if ($newcolor!="") {
	  image_newcolor("ext/icons/".$image.".gif",$file_conf,$newcolor);
	}
  } else {
	$image_file = "ext/icons/folder".(strpos($image,"1")?"1":"2").".gif";
	if ($newcolor!="") image_newcolor($image_file,$file_conf,$newcolor);
    header("HTTP/1.1 303 See Other");
	header("Location: ".$image_file);
  }
}
if (isset($_REQUEST["search"])) {
  if (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"]=="on") $proto = "https"; else $proto = "http";
  if (FORCE_SSL) $proto = "https";
  $url = $proto."://".$_SERVER["HTTP_HOST"].str_replace("images.php","",$_SERVER["SCRIPT_NAME"]);
  echo '<?xml version="1.0"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>'.htmlspecialchars(APP_TITLE,ENT_QUOTES).'</ShortName>
<Description>'.htmlspecialchars(APP_TITLE,ENT_QUOTES).'</Description>
<Image height="16" width="16" type="image/x-icon">'.$url.'ext/images/favicon.ico</Image>
<Url type="text/html" method="get" template="'.$url.'index.php?folder=1&amp;view=search&amp;search={searchTerms}"/>
<!--<Url type="application/x-suggestions+json" method="GET" template=""/>-->
</OpenSearchDescription>';
  exit;
}

/*
if (isset($_REQUEST["process"])) {
  $dir = "ext/";
  if (($dh = opendir($dir))) {
    while (($file = readdir($dh)) !== false) {
	  if (!is_dir($dir.$file) and $file!="." and $file!="..") {
	    if (filesize($dir.$file)<10*1024) {
	      echo "\n\"".$file."\",\"".((base64_encode(file_get_contents($dir.$file))))."\",\n\n";
    } } }
    closedir($dh);
  }  
  exit("\n\n @\n\n");
}
*/

function sys_custom($file) {
  if (file_exists(SIMPLE_CUSTOM.$file)) return SIMPLE_CUSTOM.$file;
  return $file;
}

function image_newcolor($image_file,$file_conf,$newcolor) {
  $rgb = array('r' => hexdec(substr($newcolor,0,2)), 'g' => hexdec(substr($newcolor,2,2)), 'b' => hexdec(substr($newcolor,4,2)));
  $img = greyscale(imagecreatefromgif(sys_custom($image_file)));
  $img = imageselectivesolor($img,floor(($rgb["r"]+30)/2.55),floor(($rgb["g"]+30)/2.55),floor(($rgb["b"]+30)/2.55));
  download($file_conf,"image/png","",false); 
  imagepng($img);
  exit;
}

function greyscale($img) {
  for ($i=0; $i < imagecolorstotal($img); $i++) {
 	$c = ImageColorsForIndex($img, $i);
 	$t = ($c["red"]+$c["green"]+$c["blue"])/3;
    imagecolorset($img, $i, $t, $t, $t);   
  }
  return $img;
}

function imageselectivesolor($img,$red,$green,$blue) {
  for($i=0;$i<imagecolorstotal($img);$i++) {
    $col=ImageColorsForIndex($img,$i);
    $red_set=$red/100*$col['red'];
    $green_set=$green/100*$col['green'];
    $blue_set=$blue/100*$col['blue'];
    if($red_set>255)$red_set=255;
    if($green_set>255)$green_set=255;
    if($blue_set>255)$blue_set=255;
    imagecolorset($img,$i,$red_set,$green_set,$blue_set);
  }
  return $img;
}

function predownload($filename,$id) {
  if (DEBUG) return;
  $modified = filemtime($filename);
  $etag = '"'.md5($filename.$id.$modified.CORE_VERSION).'"';
  header("Last-Modified: ".gmdate("D, d M Y H:i:s", $modified)." GMT");
  header("ETag: $etag");
  if (!empty($_SERVER["HTTP_IF_NONE_MATCH"]) and $etag == stripslashes($_SERVER["HTTP_IF_NONE_MATCH"])) {
    header("HTTP/1.1 304 Not Modified");
	exit;
  }
}

function download($filename,$mimetype,$output,$show) {
  $until = 3600*24*30;
  if (DEBUG) $until = 1;
  header("Content-Type: ".$mimetype."; charset=utf-8");
  header("Cache-Control: public, max-age=".$until.", must-revalidate");
  header("Pragma: public");
  header("Expires: ".gmdate("D, d M Y H:i:s", NOW+$until)." GMT");
  if (!$show) return;
  if ($mimetype=="text/css" and CORE_COMPRESS_OUTPUT and isset($_SERVER["HTTP_ACCEPT_ENCODING"]) and
	  strpos($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")!==false and !@ini_get("zlib.output_compression")) {
	if ($output=="") $hash = "SGS".strlen($filename).crc32($filename).filemtime($filename); else $hash = "SGS".strlen($output).crc32($output);
	header("Content-Encoding: gzip");
    $cache_file = SIMPLE_CACHE."/output/".$hash.".cache";
	if (!file_exists($cache_file) or filesize($cache_file)==0) {
      if ($output=="") $output = file_get_contents($filename);
	  $output = gzencode($output);
	  file_put_contents($cache_file, $output, LOCK_EX);
	} else $output = file_get_contents($cache_file);
	exit($output);
  } else {
    if ($output=="") $output = file_get_contents($filename);
	exit($output);
} }