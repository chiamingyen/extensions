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

class validate {

static function require_group($value, $params) {
  $values = array();
  $groups = explode(",", $params[0]);
  if (!empty($params[1])) $values = explode(",", $params[1]);
  if ((empty($values) or in_array($value, $values)) and !self::_has_a_group($groups)) {
	return sprintf("{t}Value '%s' needs a group membership: %s{/t}", $value, implode(" {t}or{/t} ", $groups));
  }
  return "";
}

private static function _has_a_group($groups) {
  if (sys_is_super_admin($_SESSION["username"])) return true;
  if (count(array_intersect($_SESSION["groups"], $groups))>0) return true;
  return false;
}

static function json($value) {
  if (!json_decode($value)) return "{t}Error{/t}: {t}validation failed{/t} (JSON)";
  return "";
}

static function xml($value) {
  try {
    @new SimpleXMLElement($value);
  }
  catch(Exception $e) {
    return "{t}Error{/t}: ".$e->getMessage()." ".libxml_get_last_error()->message;
  }
  return "";
}

static function fileupload($filename,$params) {
  $exts = explode(",",$params[0]);
  $filename = explode("|",$filename);
  foreach ($filename as $file) {
    $ext = modify::getfileext($file);
    if (!in_array($ext, $exts)) return sprintf("{t}Filename must have the right extension. Valid extensions are %s{/t}", $params[0]);
  }
  return "";
}

static function check_uploaded_file($files,$unused=null,$field=array()) {
  $files = explode("|",$files);
  $size = 0;
  if (!empty($field["SIMPLE_FILE_SIZE"])) {
    $size = str_replace(array("M","K"),array("000000","000"),$field["SIMPLE_FILE_SIZE"]);
  }
  $exts = explode(",", INVALID_EXTENSIONS);
  foreach ($files as $file) {
	if ($file=="") continue;
	if (!file_exists($file)) return "{t}Error{/t}: {t}file not found.{/t}";
	if ($size!=0 and filesize($file)>$size) {
	  return "{t}Error{/t}: {t}file is too big. Please upload a smaller one.{/t} (".modify::basename($file)." > ".$field["SIMPLE_FILE_SIZE"].")";
	}
    $ext = modify::getfileext($file);
    if (in_array($ext, $exts)) return sprintf("{t}this file extension is not allowed{/t} (%s)", $ext);
  }
  return "";
}

static function checkvirus($files) {
  if (VIRUS_SCANNER=="") return "";
  $files = explode("|",$files);
  foreach ($files as $file) {
	$src = modify::realfilename($file);
	$bin = modify::realfilename(VIRUS_SCANNER);
	if ($bin=="") return sprintf("checkvirus: {t}unable to find %s{/t}",VIRUS_SCANNER);
	$result = sys_exec($bin." ".VIRUS_SCANNER_PARAMS." ".$src);
	if ($result!="") {
	  if (VIRUS_SCANNER_DISPLAY and preg_match("|".preg_quote(VIRUS_SCANNER_DISPLAY)."(.*?)\n|i",$result,$match)) {
	    $result = trim($match[1]);
	  }
	  if (sys_strbegins($file,SIMPLE_CACHE."/")) @unlink($file);
	  sys_log_message_log("php-fail",$result." [".$file."]");
	  return $result;
	}
  }
  return "";
}

static function username($username) {
  if (defined("SETUP_ADMIN_USER") and sys_is_super_admin($username)) return "username: {t}Username must be different from the super administrator.{/t}";
  if (strlen($username)<129 and strlen($username)>2 and preg_match('/^[a-z0-9-_@\.]*$/', $username)) return "";
  return "{t}Name must be not null, lowercase, min 3 characters, max 128 containing [a-z0-9_-@.].{/t}";
}

static function regexp($value,$params) {
  if (empty($params[0])) return "";
  if (empty($params[1])) $params[1] = "{t}validation failed{/t}: ".$params[0];
  if (!preg_match($params[0], $value)) return $params[1];
  return "";
}

static function password($password) {
  if (strlen($password)>4) return "";
  return "{t}Password must be not null, min 5 characters.{/t}";
}

static function email($email) {
  if (strlen($email)>5 and preg_match("/^[\S]+@[\S]+\.[\S]+$/si", $email)) return "";
  return "{t}Please input a valid e-mail adress.{/t}";
}

static function url($url) {
  if (strlen($url)>6 and (preg_match("!^(http|www)[\S]+!i",$url) or sys_strbegins($url,"index.php?") or $url=="about:blank")) return "";
  return "{t}Please enter a valid url.{/t}";
}

static function numeric($var) {
  if (is_numeric($var) and $var>0) return "";
  return "{t}Variable must be of type int and greater than 0.{/t}";
}

static function integer($var) {
  if (is_numeric($var) and ceil($var)==$var) return "";
  return "{t}Variable must be of type int.{/t}";
}

static function float($var) {
  if (is_numeric($var)) return "";
  return "{t}Variable must be of type float.{/t}";
}

static function date($var) {
  if (strtotime(modify::date_translate($var))!=0) return "";
  return "{t}Variable must be of type date (mm/dd/yyyy).{/t}";
}

static function time($var) {
  if (strtotime(modify::date_translate($var))!=0) return "";
  return "{t}Variable must be of type time (hh:mm am/pm).{/t}";
}

static function datetime($var) {
  if (strtotime(modify::date_translate($var))!=0) return "";
  return "{t}Variable must be of type datetime (mm/dd/yyyy hh:mm am/pm).{/t}";
}

static function itemsexist($data,$params) {
  $checktrash = false;
  if (isset($params[2]) and $params[2]=="checktrash") $checktrash = true;
  $table = $params[0];
  $id = $data["id"];
  $data = array_intersect_key($data, array_flip(explode(",",$params[1])));
  return validate::itemexists($table,$data,$id,$checktrash);
}

static function itemexists($table,$vals,$id,$checktrash=false) {
  if (strpos($table,"sys_nodb_")) return "";
  if (!sql_query(sprintf("SELECT id,folder FROM %s LIMIT 1",$table))) return "";
  $found = false;
  $where = array("id!=@id@");
  $values = array("id"=>$id);
  $msgs = array();
  foreach ($vals as $key => $val) {
    $where[] = $key."=@".$key."@";
	$values[$key] = $val;
	$msgs[] = $table.".".$key."=".$val;
  }
  $rows = db_select($table,array("id","folder"),$where,"","",$values);
  if (is_array($rows) and count($rows)>0) {
    foreach ($rows as $row) {
	  if ($checktrash or !folder_in_trash($row["folder"])) {
	    $found = true;
	    break;
  } } }
  if (!$found) return "";
  return sprintf("{t}Variable already exists in table. (%s){/t}",implode(", ",$msgs));
}

}