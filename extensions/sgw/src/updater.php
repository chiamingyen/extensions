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

require("index.php");
@set_time_limit(1800);

if (!sys_is_super_admin($_SESSION["username"])) sys_die("{t}Not allowed. Please log in as super administrator.{/t}");

setup::out('
<html>
<head>
<title>Simple Groupware</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
  body, h2, img, div, table.data, a {
	background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif;
  }
  a,input { color: #0000FF; }
  input {
	font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px;
	vertical-align: middle; padding-left: 5px; padding-right: 5px; border-radius: 10px;
  }
  .checkbox { border: 0px; background-color: transparent; }
  .submit { color: #0000FF; background-color: #FFFFFF; width: 125px; font-weight: bold; }
  .border {
    border-bottom: 1px solid black;
  }
  .headline {
	letter-spacing: 2px;
	font-size: 18px;
	font-weight: bold;
  }
</style>
<script>
function change_links(nobackup) {
  var objs = document.getElementsByTagName("a");
  for (var i=0; i<objs.length; i++) {
    objs[i].href = objs[i].href.replace("updater.php?nobackup=1&","updater.php?");
	if (nobackup) {
      objs[i].href = objs[i].href.replace("updater.php?","updater.php?nobackup=1&");
} } }
</script>
</head>
<body>
<div class="border headline">Simple Groupware '.CORE_VERSION_STRING.'</div>
');
setup::out("<a href='index.php'>{t}Back{/t}</a><br>");

$mirrors = array(
  "sourceforge" => array(
	"name" => "Sourceforge.net",
	"url" => "http://sourceforge.net/export/rss2_projnews.php?group_id=96330",
	"pattern" => "!<title>simple groupware ([^ ]+) released.*?</title>.*?<pubdate>([^<]+)!msi",
	"source" => "http://sourceforge.net/projects/simplgroup/files/simplegroupware/%s/SimpleGroupware_%s.tar.gz",
  ),
  "google" => array(
	"name" => "Google Code",
	"url" => "http://code.google.com/feeds/p/simplegroupware/downloads/basic",
	"pattern" => "!simplegroupware_(.+?)\.tar\.gz.+?<updated>([^<]+)!msi",
	"source" => "http://simplegroupware.googlecode.com/files/SimpleGroupware_%s.tar.gz",
  ),
);

$mirror_id = "sourceforge";
if (!empty($_REQUEST["mirror"]) and in_array($_REQUEST["mirror"],array_keys($mirrors))) $mirror_id = $_REQUEST["mirror"];
$mirror = $mirrors[$mirror_id];

$folders = array("../","../old/","../docs/","../lang/","../import/","../src/","../bin/");
foreach ($folders as $folder) {
  if (!is_writable($folder)) setup::out_exit(sprintf("[1] {t}Please give write access to %s{/t}",$folder));
}
if ((empty($_REQUEST["release"]) and empty($_REQUEST["cfile"])) or !sys_validate_token()) {
  setup::out("
	<div style='color:#ff0000;'>
	<b>{t}Warning{/t}</b>:<br>
	- Please make a complete backup of your database (e.g. using phpMyAdmin)<br>
	- Please make a complete backup of your sgs folder (e.g. /var/www/htdocs/sgs/)<br>
	- Make sure both backups are complete!
    </div>
  ");

  setup::out("{t}Downloading update list{/t} ...<br>");

  $ctx = stream_context_create(array("http" => array("timeout" => 5))); 
  $data = @file_get_contents($mirror["url"],0,$ctx);
  preg_match_all($mirror["pattern"], $data, $match);

  if (!empty($match[1]) and $data!="") {
  	$found = false;
    foreach ($match[1] as $key=>$item) {
	  if ($key > 4) break;
	  if (strpos("@".$item, CORE_VERSION_STRING) and !DEBUG) break;
	  if (!empty($match[3][$key]) and strtotime($match[3][$key])+3600 > time()) continue;
	  $found = true;
	  $check = true;
	  
	  if (!empty($match[2][$key])) {
		preg_match("/php (\d+\.\d+\.\d+)/i", $match[2][$key], $match_version);
		if (!empty($match_version[1]) and version_compare(PHP_VERSION, $match_version[1], "<")) {
	      setup::out(sprintf("{t}Setup needs php with at least version %s !{/t}", $match_version[1]));
		  $check = false;
		}
		preg_match("/".SETUP_DB_TYPE." (\d+\.\d+\.\d+)/i", $match[2][$key], $match_version);
		if (!empty($match_version[1])) {
		  $db_version = str_replace(".","",$match_version[1]);
		  $curr_version = sgsml_parser::sql_version();
		  if ($curr_version < $db_version) {
		    setup::out(sprintf("{t}Wrong database-version (%s). Please use at least %s !{/t}", $curr_version, $match_version[1]));
			$check = false;
	  } } }
	  if ($check) {
	    setup::out("<a href='updater.php?mirror=".$mirror_id."&token=".modify::get_form_token()."&release=".$item."'>{t}I n s t a l l{/t}</a>&nbsp; Simple Groupware ", false);
	    setup::out($item." (<a target='_blank' href='http://www.simple-groupware.de/cms/Release-".str_replace(".","-",$item)."'>Changelog</a>)<br>");
	  }
	}
	if (!$found) setup::out("{t}Already running latest release.{/t}<br>");
  } else {
    setup::out(sprintf("{t}Connection error: %s [%s]{/t}", $mirror["url"], "HTTP")."<br>".strip_tags($data,"<br><p><h1><center>"));
  }

  setup::out("{t}Server{/t}: <b>".$mirror["name"]."</b>, {t}use mirror from{/t}: ", false);
  foreach ($mirrors as $key => $sel_mirror) {
	if ($key==$mirror_id) continue;
	setup::out("<a href='updater.php?mirror=".$key."'>".$sel_mirror["name"]."</a> ");
  }

  setup::out("<br/>{t}Package from local file system (.tar.gz){/t}:<br/>{t}current path{/t}: ".str_replace("\\","/",getcwd())."/<br/>");

  $dir = opendir("./");
  while (($file=readdir($dir))) {
    if ($file!="." and $file!=".." and preg_match("|^SimpleGroupware\_.*?.tar\.gz\$|i",$file)) {
	  setup::out("<a href='updater.php?token=".modify::get_form_token()."&cfile=".$file."'>{t}I n s t a l l{/t}</a>&nbsp; ".$file."<br/>");
	}
  }
  closedir($dir);

  setup::out("<form method='POST'><input type='hidden' name='token' value='".modify::get_form_token()."'><input type='text' name='cfile' value='/tmp/SimpleGroupware_0.xyz.tar.gz' style='width:300px;'>&nbsp;<input type='submit' class='submit' value='{t}I n s t a l l{/t}'><br>");

  setup::out("<input type='checkbox' name='nobackup' value='1' onchange='change_links(this.checked);'/> {t}Don't move old files to 'old/'{/t}</form>");
  setup::out_exit('<div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div></body></html>');
} else if (!empty($_REQUEST["cfile"])) {
  $source = $_REQUEST["cfile"];
  if (!file_exists($source) or filesize($source) < 3*1024*1024) sys_die("{t}Error{/t}: file-check [0] ".$source);
} else {
  $release = "latest";
  if ($release=="latest" or !is_numeric($_REQUEST["release"])) {
    $data = @file_get_contents($mirror["url"]);
    if ($data!="") preg_match($mirror["pattern"], $data, $match);
    if (!empty($match[1])) $release = $match[1];
  }
  $source = sprintf($mirror["source"], $release, $release);
}

$temp_folder = SIMPLE_CACHE."/updater/";
sys_mkdir($temp_folder);
$target = $temp_folder.substr(basename($source),0,-3);

setup::out("{t}Download{/t}: ".$source." ...");
if ($fz = gzopen($source,"r") and $fp = fopen($target,"w")) {
  $i = 0;
  while (!gzeof($fz)) {
    $i++;
    setup::out(".",false);
	if ($i%160==0) setup::out();
    fwrite($fp,gzread($fz, 16384));
  }
  gzclose($fz);
  fclose($fp);
} else sys_die("{t}Error{/t}: gzopen [1] ".$source);

setup::out();
if (!file_exists($target) or filesize($target) < 5*1024*1024) sys_die("{t}Error{/t}: file-check [2] Filesize: ".filesize($target)." ".$target);

setup::out(sprintf("{t}Processing %s ...{/t}",basename($target)));
require("lib/tar/Tar.php");

$tar_object = new Archive_Tar($target);
$tar_object->setErrorHandling(PEAR_ERROR_PRINT);
$tar_object->extract($temp_folder);

$file_list = $tar_object->ListContent();
if (!is_array($file_list) or !isset($file_list[0]["filename"]) or !is_dir($temp_folder.$file_list[0]["filename"])) {
  sys_die("{t}Error{/t}: tar [3] ".$target);
}
foreach ($file_list as $file) sys_chmod($temp_folder.$file["filename"]);
@unlink($target);

chdir("../old/");

$base = "../";
setup::out(sprintf("{t}Processing %s ...{/t}","{t}Folders{/t}"));
$folders = array("src","bin","lang","import","docs");
foreach ($folders as $folder) {
  if (file_exists($base.$folder."/") and !file_exists($base."old/".$folder."_".CORE_VERSION."/")) {
    if (!empty($_REQUEST["nobackup"])) {
	  dirs_delete_all($base.$folder."/");
	} else {
	  rename($base.$folder."/",$base."old/".$folder."_".CORE_VERSION."/");
} } }
if (is_dir($base."src/") or is_dir($base."bin/")) sys_die("{t}Error{/t}: rename [4]");

$source_folder = $temp_folder.$file_list[0]["filename"];
foreach ($folders as $folder) {
  if (is_dir($source_folder.$folder."/") and !is_dir($base.$folder."/")) {
    rename($source_folder.$folder."/",$base.$folder."/");
  }
}
if (!is_dir($base."src/") or !is_dir($base."bin/")) sys_die("{t}Error{/t}: rename [5]");

dirs_delete_all($source_folder);

setup::out(sprintf("{t}Processing %s ...{/t}","config.php"));

$old = SIMPLE_STORE."/config_old.php";
if (file_exists($old)) rename($old,SIMPLE_STORE."/config_".time().".php");
rename(SIMPLE_STORE."/config.php",$old);
touch($old);

setup::out(sprintf("{t}Processing %s ...{/t}","{t}translations{/t}"));
setup::build_trans(SETUP_LANGUAGE,"../src/","../bin/");

chdir("../bin/");
setup::out(sprintf("{t}Processing %s ...{/t}","{t}customizations{/t}"));
setup::build_customizing(SIMPLE_CUSTOM."customize.php");

$dir = opendir(SIMPLE_EXT);
while (($file=readdir($dir))) {
  if ($file!="." and $file!=".." and file_exists(SIMPLE_EXT.$file."/update.php")) {
    setup::out(sprintf("{t}Processing %s ...{/t}",SIMPLE_EXT.$file."/update.php"));
	require(SIMPLE_EXT.$file."/update.php");
  }
}
setup::out("<br><a href='index.php'>{t}C O N T I N U E{/t}</a><finished>");
setup::out('<br><div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div></body></html>');