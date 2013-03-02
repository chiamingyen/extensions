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

class setup {

static $config_old = "";

static $errors = array();

static function build_trans($language, $source, $target) {
  $lang_strings = self::_get_lang_strings($language);
  if (file_exists($target."index.php") and !unlink($target."index.php")) die(sprintf("Unable to write to %s",$target."index.php"));
  db_lock_tree(true);
  $queue = array($source);
  while (count($queue)>0) {
    $src = array_shift($queue);
    if (is_dir($src) and $dh=opendir($src)) {
      while (($file = readdir($dh)) !== false) {
   	    if (!in_array($file,array("..","."))) {
	      if (is_dir($src.$file)) {
		    if (!file_exists($src.$file."/_.exclude") and !file_exists($src.$file."/_.exclude_bin")) {
		      $queue[] = $src.$file."/";
		      $newdir = str_replace($source,$target,$src.$file."/");
		      sys_mkdir($newdir);
		    }
	      } else {
			$newfile = str_replace($source,$target,$src.$file);
		    self::_translate_file($src.$file, $newfile, $lang_strings);
	  } } }
	  closedir($dh);
    }
  }
  db_lock_tree(false);
}

static function _translate_file($source, $target, $lang_strings) {
  $file = basename($source);
  if (preg_match("!tools/|bin/lib/|tinymce/!",$target) or preg_match("/(gif|jpg|png|tar)\$/",$file)) {
	copy($source,$target);
	return;
  }
  $data = file_get_contents($source);
  $ext = strtolower(substr($file,-strpos(strrev($file),'.')));
  if (!file_exists($target)) {
	if ($ext!="lang" and preg_match_all("|\{t\}(.*?)\{/t\}|i",$data,$matches,PREG_SET_ORDER)) {
	  foreach ($matches as $match) {
		if (count($match)==2) {
		  if (isset($lang_strings[$match[1]])) {
			$data = str_replace($match[0],$lang_strings[$match[1]],$data);
		  } else $data = str_replace($match[0],$match[1],$data);
	} } }
	$data = str_replace("define(\"DEBUG\",true);","define(\"DEBUG\",false);",$data);
	file_put_contents($target, $data, LOCK_EX);
  } else echo "Cannot write ".$target.", file already exists.<br>\n";
}

static function build_customizing($file) {
  if (!file_exists($file)) return;
  self::out("Building customizations:");
  self::out("Execute ".$file);
  require($file);
}

static function customize_replace($file,$code_remove,$code_new) {
  echo $file.":<br/>Replace:<br/>".nl2br(modify::htmlquote($code_remove))."<br/><br/>with:<br/>".nl2br(modify::htmlquote($code_new))."<br/><br/>\n";
  $data = file_get_contents("../bin/".$file);
  if (strpos($data,$code_remove)===false) {
	throw new Exception("code not found in: ".$file." Code: ".$code_remove);
  }
  $data = str_replace($code_remove,$code_new,$data);
  file_put_contents("../bin/".$file,$data);
}

static function out($str="",$nl=true,$exit=false) {
  echo sys_remove_trans($str);
  // if (DEBUG) echo " ".memory_get_usage(true);
  if ($nl) echo "<br>\n";
  if ($exit) exit;
  flush();
  @ob_flush();
}

static function out_exit($str) {
  self::out($str,false,true);
}

static function get_config_old($key, $full=false, $default="") {
  $config_old = self::$config_old;
  if (($pos = strpos($config_old,"define('".$key."',"))) {
	$pos = $pos+strlen($key)+10;
	$end = strpos($config_old,"\n",$pos)-$pos-2;
	$result = substr($config_old,$pos,$end);
	if (!$full) $result = trim($result,"'\"");
	if ($key=="INVALID_EXTENSIONS") $result = str_replace(",url,", ",", $result);
	return $result;
  }
  return $default;
}

private static function _get_lang_strings($language) {
  $lang_file = "lang/".basename($language).".lang";
  $data = @file_get_contents($lang_file);
  $data .= @file_get_contents("../".$lang_file);
  $data .= @file_get_contents(SIMPLE_EXT.$lang_file);
  
  $unicode = false;
  $lang_strings = array();
  if ($data!="") {
    $data = explode("{t"."}",$data);
    if (ord($data[0])==239) $unicode = true; // BOM
    foreach ($data as $elem) {
      if ($elem!="") {
  	    $elem = explode("{/t"."}",$elem);
	    $elem[0] = trim($elem[0]);
	    if (isset($elem[1])) $elem[1] = trim($elem[1]);
	    if (!empty($elem[1])) {
	      if (!$unicode) $elem[1] = utf8_encode($elem[1]);
	      $lang_strings[$elem[0]] = htmlspecialchars($elem[1],ENT_QUOTES,"UTF-8");
  } } } }
  return $lang_strings;
}

static function dirs_create_htaccess($dirname) {
  if (!file_exists($dirname.".htaccess")) {
    if (!@file_put_contents($dirname.".htaccess", "Order deny,allow\nDeny from all\n", LOCK_EX)) {
	  setup::error(sprintf("[4] Please give write access to %s",$dirname),25);
    }
  }
  dirs_create_index_htm($dirname);
}

static function dirs_create_dir($dirname) {
  if (!is_dir($dirname)) sys_mkdir($dirname);
  dirs_create_index_htm($dirname."/");
}

static function error($msg,$id=0) {
  self::$errors[] = array($msg,$id);
}

static function display_errors($exit) {
  $err = "";
  $msg = "";
  foreach (self::$errors as $message) {
    $msg .= str_replace("\n","<br>",modify::htmlquote($message[0]))."<br>";
	$err .= $message[1]."_";
  }
  $output = '
    <br>
    <center>
	<img src="http://www.simple-groupware.de/cms/logos.php?v='.CORE_VERSION.'&d='.PHP_VERSION.'_'.PHP_OS.'&e='.$err.'" start="width:1px; height:1px;">
    <div style="border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">Simple Groupware Setup</div>
	<br>Error:<br>
	<error>'.$msg.'</error>
	<br><br>
	<a href="index.php">Relaunch Setup</a><br><br>
	<hr>
	<a href="http://www.simple-groupware.de/cms/Main/Installation" target="_blank">Installation manual</a> / 
	<a href="http://www.simple-groupware.de/cms/Main/Update" target="_blank">Update manual</a><hr>
	<a href="http://www.simple-groupware.de/cms/Main/Documentation" target="_blank">Documentation</a> / 
	<a href="http://www.simple-groupware.de/cms/Main/FAQ" target="_blank">FAQ</a><hr>
	<br>
	</center>
  ';
  echo sys_remove_trans($output);
  if ($exit) exit();
  phpinfo();
}

}