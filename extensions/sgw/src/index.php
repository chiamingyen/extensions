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
  
  define("MAIN_SCRIPT",basename($_SERVER["PHP_SELF"]));
  define("CORE_VERSION_config","0_745");
  error_reporting(E_ALL);
  
  if (ini_get("register_globals")) pre_dropglobals();
  header("Content-Type: text/html; charset=utf-8");
  define("SIMPLE_STORE","../simple_store");
  @include(SIMPLE_STORE."/config.php");
  if (!defined("CORE_VERSION") or CORE_VERSION_config!=CORE_VERSION) {
    if (defined("CORE_VERSION")) {
	  $old = SIMPLE_STORE."/config_old.php";
	  if (file_exists($old)) rename($old,SIMPLE_STORE."/config_".time().".php");
	  rename(SIMPLE_STORE."/config.php",$old);
	  touch($old);
	  header("Location: index.php");
	} else require("core/setup.php");
	exit;
  }
  if (!defined("SETUP_DB_HOST")) exit;
  
  if (!empty($_POST)) @ignore_user_abort(1);

  if (FORCE_SSL and (!isset($_SERVER["HTTPS"]) or $_SERVER["HTTPS"]!="on")) {
    header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?".$_SERVER["QUERY_STRING"]);
    exit;
  }
  if (!empty($_SERVER["PATH_INFO"]) and $_SERVER["PATH_INFO"]!=$_SERVER["SCRIPT_NAME"] and !strpos($_SERVER["PATH_INFO"],".exe")) {
    header("Location: http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]);
	exit;
  }
  if (CHECK_DOS and !SETUP_AUTH_NTLM_SSO and !DEBUG) pre_sys_checkdos();
  
  require("core/functions.php");
  require("lib/smarty/Smarty.class.php");

  if (!defined("NOCONTENT")) ob_start();
  set_error_handler("debug_handler");
  
  if (!isset($_SERVER["SERVER_ADDR"]) or $_SERVER["SERVER_ADDR"]=="") $_SERVER["SERVER_ADDR"]="127.0.0.1";
  if (!isset($_SERVER["HTTP_USER_AGENT"])) $_SERVER["HTTP_USER_AGENT"]="mozilla/5 rv:1.4";
  if (!isset($_SERVER["SERVER_SOFTWARE"])) $_SERVER["SERVER_SOFTWARE"]="Apache";

  if (!defined("NOCONTENT") and !login_browser_detect() and !DEBUG and empty($_REQUEST["export"])) sys_die("{t}Browser not supported{/t}: ".sys::$browser["str"],login::browser_detect_toString());
  if (!DEBUG and SETUP_LANGUAGE!="{t}en{/t}") sys_die(sprintf("{t}Program is installed with language %s, please use a different url or run the setup again.{/t}",SETUP_LANGUAGE));

  sys::init();

  if (!defined("NOCONTENT")) {
    folder_process_session_request();
    folder_build_folders();
    $GLOBALS["table"] = db_get_schema($GLOBALS["schemafile"],$GLOBALS["tfolder"],$GLOBALS["tview"],true,!empty($_REQUEST["popup"]));
	$GLOBALS["tname"] = $GLOBALS["table"]["att"]["NAME"];

	if (!empty($GLOBALS["table"]["att"]["LOAD_LIBRARY"])) require($GLOBALS["table"]["att"]["LOAD_LIBRARY"]);
	sys_process_session_request();

	if (!empty($GLOBALS["current_view"]["ENABLE_CALENDAR"])) {
      date::process_session_request();
	  $session = $_SESSION[ $GLOBALS["tname"] ][ "_".$GLOBALS["tfolder"] ];
	  date::build_datebox($session["today"], $session["markdate"], $session["weekstart"]);
	}
    asset_process_session_request();

	if (!empty($GLOBALS["current_view"]["ENABLE_CALENDAR"]) and (empty($_REQUEST["iframe"]) or $_REQUEST["iframe"]=="2")) {
	  date::build_views();
	}
	$output = ob_get_contents();
	ob_end_clean();
	if (!empty(sys::$alert) or trim($output)!="") sys_message_box("{t}Error{/t}:",$output.implode("\n",sys::$alert));
	sys_process_output();
  }

  function pre_sys_checkdos() {
	if (defined("NOCONTENT") or !empty($_SERVER["HTTP_X_MOZ"])) return;
    if (isset($_SERVER["HTTP_CLIENT_IP"])) $ip = $_SERVER["HTTP_CLIENT_IP"];
      else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
      else if (isset($_SERVER["REMOTE_ADDR"])) $ip = $_SERVER["REMOTE_ADDR"];
      else $ip = "0.0.0.0";

	$delay = false;
	$ip = filter_var($ip, FILTER_VALIDATE_IP);
	if (APC) {
	  if (($val = apc_fetch("dos".$ip))===false) $val=0;
	  apc_store("dos".$ip, ++$val, 1);
	  if ($val>2) $delay = true;
	} else {
	  $ip_file = SIMPLE_CACHE."/ip/".str_replace(array(".",":"),"-",$ip);
	  if (@file_exists($ip_file) and time()-@filemtime($ip_file)<1) {
		if (file_exists($ip_file."_2") and time()-filemtime($ip_file."_2")<1) $delay = true;
		touch($ip_file."_2");
	  }
	  touch($ip_file);
	}
	if ($delay) {
	  if (empty($_SERVER["HTTP_ACCEPT_LANGUAGE"])) { // client
		header("HTTP/1.0 408 Request timeout");
	  } else {
        echo "<html><body><script>setTimeout('document.location.reload()',1500);</script>{t}Please wait ...{/t}<noscript>{t}Please hit reload.{/t}</noscript></body></html>"; 
	  }
	  exit;
	}
  }
  
  function pre_dropglobals() {
    $valid = array("GLOBALS","_REQUEST", "_FILES","_SERVER","_COOKIE","_GET","_POST","browser");
    foreach (array_keys($GLOBALS) as $key) if (!in_array($key,$valid)) unset($GLOBALS[$key]);
  }