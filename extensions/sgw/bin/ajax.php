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
define("NOCONTENT",true);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

define("SIMPLE_STORE","../simple_store");
@include(SIMPLE_STORE."/config.php");
if (!defined("SETUP_DB_HOST")) exit;

require("core/functions.php");

set_error_handler("debug_handler");
if (ini_get("magic_quotes_gpc")!==false and get_magic_quotes_gpc()) modify::stripslashes($_REQUEST);
if (ini_get("register_globals")) modify::dropglobals();
@ignore_user_abort(1);

if (!sql_connect(SETUP_DB_HOST, SETUP_DB_USER, sys_decrypt(SETUP_DB_PW,sha1(SETUP_ADMIN_USER)), SETUP_DB_NAME)) {
  $err = sprintf("Cannot connect to database %s on %s.\n",SETUP_DB_NAME,SETUP_DB_HOST).sql_error();
  trigger_error($err,E_USER_ERROR);
  exit($err);
}

$save_session = false;
if (ini_get("suhosin.session.encrypt")) $save_session = true; // workaround for broken session_encode()
login_handle_login($save_session);

$class = "ajax";
if (!empty($_REQUEST["class"]) and strpos($_REQUEST["class"],"_ajax")) $class = $_REQUEST["class"];

if (empty($_REQUEST["function"]) and empty($_SERVER["HTTP_SOAPACTION"])) {
  $reflect = new ReflectionClass($class); 
  $output = "";
  foreach($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectmethod) {
	$output .= $reflectmethod->getDocComment()."\n";
    $output .= "{$reflectmethod->getName()}(";
    foreach($reflectmethod->getParameters() as $num => $param) {
		if ($param->isArray()) $output .= " array";
		$output .= " \$".$param->getName();
		if ($param->isDefaultValueAvailable()) $output .= "=".str_replace("\n","",var_export($param->getDefaultValue(),true));
        if ($reflectmethod->getNumberOfParameters() != $num+1) $output .= ",";
    }
	$output .= " )\n\n";
  }
  sys_die("Simple Groupware Soap/Ajax Functions","<pre>".$output."</pre>");
}

if (!empty($_SERVER["HTTP_SOAPACTION"])) {
  if (!extension_loaded("soap")) sys_die(sprintf("%s is not compiled / loaded into PHP.","Soap"));
  $soap = new SoapServer(null, array('uri'=>'sgs'));
  $soap->setClass($class);
  $soap->handle();

} else if ($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest") {
  $func = $_REQUEST["function"];
  if ($func=="type_pmwikiarea::ajax_render_preview") require("lib/pmwiki/pmwiki.php");
  if (!function_exists("json_encode")) require("lib/json/JSON.php");

  if ((strpos($func,"_ajax::") or strpos($func,"::ajax_")) and substr_count($func,"::")==1) {
    list($class,$func) = explode("::",$func);
  }
  ajax::require_method($func, $class);
  
  if (!empty($_REQUEST["params"])) {
    $params = json_decode($_REQUEST["params"], true);
  } else {
    $params = json_decode(file_get_contents("php://input"),true);
  }
  echo json_encode(call_user_func_array(array($class, $func), $params));

  if (!empty($_SESSION["notification"]) or !empty($_SESSION["warning"])) ajax::session_save();
}