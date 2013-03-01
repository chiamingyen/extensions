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

if (!defined("MAIN_SCRIPT")) exit;

class sys {

  static $db = null; // database link
  static $db_error = null; // sqlite
  static $db_queries = array(); // all queries
 
  static $time_start = 0; // script start
  static $time_end = 0; // script end

  // browser infos
  static $browser = array( "name"=>"", "ver"=>0, "str"=>"unknown", "is_mobile"=>false, "plattform"=>"", 
	"comp"=>array("htmledit"=>true, "codeedit"=>false, "javascript"=>true), "no_scrollbar"=>false );

  static $alert = array(); // force error message output
  
  static $notification = array(); // show notification messages
  
  static $warning = array(); // show warning messages

  static $smarty = null; // smarty reference

  static $urladdon = ""; // auto-append string to URL

  static $cache = array(); // cache data
  
  static function init() {
    self::$time_start = sys_get_microtime();

	// clean request vars
	if (ini_get("magic_quotes_gpc")!==false and get_magic_quotes_gpc()) modify::stripslashes($_REQUEST);
	foreach ($_REQUEST as $key=>$val) {
	  if (is_array($val) and count($val)>0) {
		$_REQUEST[$key] = array();
		foreach ($val as $val2) {
		  if (!is_array($val2)) $_REQUEST[$key][$val2] = $val2;
	} } }

	// refresh smarty cache?
	if (DEBUG) debug_check_tpl();
	
	// set up smarty
	self::$smarty = new Smarty;
	self::$smarty->register_prefilter(array("modify","urladdon_quote"));
	if (isset($_REQUEST["print"])) self::$smarty->register_outputfilter(array("modify","striplinksforms"));
	if (isset($_REQUEST["print"])) self::$smarty->assign("print",$_REQUEST["print"]);
	self::$smarty->compile_dir = SIMPLE_CACHE."/smarty";
	self::$smarty->template_dir = "templates";
	self::$smarty->config_dir = "templates";
	self::$smarty->compile_check = false;

	// set up database
	if (!sql_connect(SETUP_DB_HOST, SETUP_DB_USER, sys_decrypt(SETUP_DB_PW,sha1(SETUP_ADMIN_USER)), SETUP_DB_NAME)) {
	  $err = sprintf("{t}Cannot connect to database %s on %s.{/t}\n",SETUP_DB_NAME,SETUP_DB_HOST).sql_error();
	  trigger_error($err,E_USER_ERROR);
	  sys_die($err);
	}

	// verify credentials
	login_handle_login();
  }

  static function shutdown() {
    // check execution time
    self::$time_end = number_format(sys_get_microtime()-self::$time_start,2);
	if (self::$time_end > SYSTEM_SLOW) {
	  sys_log_message_log("system-slow",sprintf("{t}%s secs{/t}",self::$time_end)." ".basename(_sys_request_uri()),_sys_request_uri());
	}

	// process error.txt
	$size = @filesize(SIMPLE_CACHE."/debug/error.txt");
	if ($size>0 and $size<=2097152 and $msgs = @file_get_contents(SIMPLE_CACHE."/debug/error.txt")) { // 2M
	  @unlink(SIMPLE_CACHE."/debug/error.txt");
	  $msgs = array_reverse(explode("\n",$msgs));
	  foreach ($msgs as $msg) {
		if ($msg=="") continue;
		$vars = unserialize($msg);
		sys_log_message($vars[0],$vars[1],$vars[2],$vars[3],$vars[4],true,$vars[5]);
	  }
	} else if ($size>0) {
	  sys_die("Can't process the error logfile, too large: ".SIMPLE_CACHE."/debug/error.txt");
	}

	// logging
	sys_log_stat("pages",1);
  }
}

function __autoload($class) {
  if ($class=="Net_IMAP") {
    require("lib/mail/IMAP.php");
  } else if ($class=="Net_SMTP") {
    require("lib/mail/SMTP.php");
  } else if ($class=="PEAR" or $class=="PEAR_Error") {
    require("lib/pear/PEAR.php");
  } else if (sys_strbegins($class,"lib_")) {
    require("modules/lib/".basename(substr($class,4)).".php");
  } else if (sys_strbegins($class,"type_")) {
    require(sys_custom("core/types/".basename(substr($class,5)).".php"));
  } else {
    require(sys_custom("core/classes/".basename($class).".php"));
  }
}

function ______A_S_S_E_T______() {}

function _asset_get_rows() {
  $t = &$GLOBALS["t"];
  $tname = $t["title"];
  $tview = $t["view"];
  $mode = $t["views"][$tview]["SCHEMA_MODE"];
  $vars = array("handler"=>$t["handler"],"sqlvarsnoquote"=>$t["sqlvarsnoquote"],"default_sql"=>$t["default_sql"],"custom_name"=>$t["custom_name"]);
  $rows = db_select($tname,$t["fields_query"],$t["sqlwhere"],$t["sqlorder"],$t["sqllimit"],$t["sqlvars"],$vars);
  if (!empty($GLOBALS["current_view"]["ENABLE_CALENDAR"]) and is_array($rows) and count($rows)>0) {
    $rows = date::build_views_sql($rows);
  }
  $i = 0;
  $total_row = array();
  $has_total_row = false;
  
  if ($mode=="edit" and is_array($rows) and !empty($t["sqlvars"]["item"]) and count($rows) < count($t["sqlvars"]["item"])) {
	sys_warning("{t}Item(s) not found or access denied.{/t}");
  }
  if (is_array($rows) and count($rows)>1 and empty($_REQUEST["preview"]) and empty($_REQUEST["iframe"])) {
	$total_row = array("_bgstyle"=>"", "_fgstyle"=>"", "issum"=>1);
	$first_row = array_slice($rows,0,1);
    foreach ($first_row[0] as $field=>$value) {
	  if (isset($t["fields"][$field]["SUM"]) or isset($t["fields"][$field]["AVG"])) {
	    if (empty($_REQUEST["iframe"]) or isset($t["fields"][$field]["AVG"])) $has_total_row = true;
		$total_row[$field] = array("data"=>array(0),"filter"=>array(0));
	  } else if (isset($t["fields"][$field])) {
		$total_row[$field] = array("data"=>array(""),"filter"=>array(""));
  } } }
  if (is_array($rows)) foreach ($rows as $row) {
    if (empty($row["id"])) $row["id"] = $i++;
	while (isset($t["data"][$row["id"]])) $row["id"] .= " ";
	$row["_id"] = $row["id"];
	$row["_folder"] = isset($row["folder"])?$row["folder"]:"";
	$row["_table"] = $tname;
	$row["_bgstyle"] = "";
	$row["_fgstyle"] = "";
	if (!isset($t["fields"]["id"])) unset($row["id"]);

	if (isset($t["views"][$tview]["CHANGESEEN"]) and empty($row["seen"])) {
	  $vars = $t["sqlvars"];
	  $vars["id"] = $row["_id"];
	  db_update($tname,array("seen"=>1),array("id=@id@"),$vars,array("handler"=>$t["handler"]));
	}
	foreach ($row as $field=>$value) {
      if (isset($t["fields"][$field])) {
	    $value = $row[$field];
	    $row[$field] = array();
	    $row["_bgstyle"] = "";
	    $row["_fgstyle"] = "";
		if ($has_total_row and is_numeric($value)) {
		  if (isset($t["fields"][$field]["SUM"])) {
		    $total_row[$field]["filter"][0] += $value;
		  } else if (isset($t["fields"][$field]["AVG"])) {
			$total_row[$field]["filter"][0] += round($value/count($rows),4);
		  }
		}
		if ($value!="") {
		  switch ($t["fields"][$field]["SIMPLE_TYPE"]) {
		    case "dateselect":
		    case "select":
		      $value = explode("|",trim($value,"|"));
		      break;
		    case "files":
		      $value = explode("|",trim($value,"|"));
			  foreach ($value as $key=>$file) {
			    $locked = file_exists($file.".lck");
			    $row[$field]["locked"][$key] = $locked;
				if ($GLOBALS["sel_folder"]["rights"]["write"] and $locked) {
				  $row[$field]["can_unlock"][$key] = sys_can_unlock($file,$_SESSION["username"]);
				} else if ($GLOBALS["sel_folder"]["rights"]["write"]) {
				  $row[$field]["can_lock"][$key] = sys_can_lock($file);
				}
			  }
		      break;
		    case "multitext":
			  $value = _asset_explode($value, ", ");
		      break;
			case "text":
			  if (!empty($t["fields"][$field]["SEPARATOR"])) {
				$value = _asset_explode($value, $t["fields"][$field]["SEPARATOR"]);
			  }
			  break;
		  }
		}
		if (!is_array($value)) $value = array($value);
	    $row[$field]["data"] = $value;
	    $row[$field]["filter"] = $value;
	  }
	}
	$t["data"][$row["_id"]] = $row;
  }
  $t["datasets"] = count($t["data"]);
  if ($mode=="" and $has_total_row) $t["data"][] = $total_row;

  if ($mode!="" and $mode!="static") {
    if ($mode=="edit") _asset_lock_rows();
	if ($mode!="new") _asset_restore_rows($mode=="edit_as_new");
  } else _asset_filter_rows();
}

function _asset_explode($value, $separator) {
  static $func = false;
  if (!$func) $func = create_function("\$match", "return str_replace(', ', ' ', \$match[1]);");
  return explode($separator, preg_replace_callback('!(".*?")!', $func, $value));
}

function _asset_lock_rows() {
  $t = &$GLOBALS["t"];
  if (is_array($t["data"])) {
    foreach (array_keys($t["data"]) as $row_key) {
      $lckfile = SIMPLE_STORE."/locking/".$t["title"]."_".md5($row_key).".lck";
	  $lckuser = "";
	  if (file_exists($lckfile)) {
	    $lcktime = filemtime($lckfile);
	    if (($lcktime+LOCKING) > time()) {
	      $lckuser = file_get_contents($lckfile);
	      if ($lckuser!=$_SESSION["username"]) {
	        $t["data"][$row_key]["_lck"] = sprintf("{t}%s started editing this asset at %s{/t}",$lckuser,sys_date("{t}g:i a{/t}",$lcktime));
	  } } }
	  if ($lckuser!=$_SESSION["username"]) {
	    file_put_contents($lckfile, $_SESSION["username"], LOCK_EX);
} } } }

function _asset_filter_rows() {
  $t = &$GLOBALS["t"];
  if (!is_array($t["data"])) return;

  foreach ($t["data"] as $row_key=>$row) {
	foreach (array_keys($row) as $field) {
	
      if (!isset($t["fields"][$field])) continue;
	  $type = $t["fields"][$field]["SIMPLE_TYPE"];
		
	  if (is_call_type($type)) {
		$t["filters"][$field][$type] = array("FUNCTION"=>"type_".$type."::render_page");
	  }
	  if (empty($t["filters"][$field])) continue;
	  foreach ($t["filters"][$field] as $filter) {
		list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
		foreach (array_keys($row[$field]["filter"]) as $filter_key) {
		  if (!empty($filter["TYPE"])) {
			$t["data"][$row_key][$field][$filter["TYPE"]] = call_user_func(array($class, $function),$t["data"][$row_key][$field]["filter"][$filter_key],$params,$row);
		  } else {
			$t["data"][$row_key][$field]["filter"][$filter_key] = call_user_func(array($class, $function),$t["data"][$row_key][$field]["filter"][$filter_key],$params,$row);
	} } } }

	if (count($t["rowfilters"])==0) continue;
	foreach ($t["rowfilters"] as $filter) {
  	  list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
	  if (!isset($filter["TYPE"])) continue;
	  $t["data"][$row_key][$filter["TYPE"]] .= call_user_func(array($class, $function), $row, $params);
} } }

function _asset_restore_rows($restore_files) {
  $t = &$GLOBALS["t"];
  if (empty($t["data"])) return;
  foreach ($t["data"] as $row) {
	foreach (array_keys($row) as $fieldname) {
	  if (!isset($t["fields"][$fieldname])) continue;
	  $value_edit = $row[$fieldname]["data"];
	  $restore_filters = array();
	  if (isset($t["restore"][$fieldname])) $restore_filters = $t["restore"][$fieldname];
	  if ($restore_files and isset($t["fields"][$fieldname]["SIMPLE_TYPE"]) and $t["fields"][$fieldname]["SIMPLE_TYPE"]=="files") {
		$restore_filters[] = array("FUNCTION"=>"copyfiles_totemp");
	  }
	  foreach ($restore_filters as $filter) {
		list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
		foreach (array_keys($value_edit) as $key) {
		  $value_edit[$key] = call_user_func(array($class, $function), $value_edit[$key], $params, $row);
	  } }
	  $val = implode("|",$value_edit);
	  $t["fields"][$fieldname]["SIMPLE_DEFAULTS"]["form_".md5($row["_id"])] = $val;
	  if (!$restore_files or $val!="") $t["fields"][$fieldname]["SIMPLE_DEFAULT"] = "";
} } }

function asset_process_trigger($trigger,$id,$data,$table="") {
  $error = "";
  foreach ($data as $key=>$val) {
    if (is_array($val)) $data[$key] = implode("|",$val);
  }
  foreach (explode("|",$trigger) as $job) {
	$params = explode(":",str_replace("runfunc:","",$job));
	if (count($params)>2 and $params[1]=="") {
	  $func = $params[0]."::".$params[2];
	  $params = array_slice($params, 3);
	} else {
	  $func = array_shift($params);
	}
	list($class, $function, $unused) = sys_find_callback("trigger", $func);
	$result = call_user_func(array($class, $function), $id, $data, $params, $table);
	if ($result!="") $error .= " {$class}::{$function} {$result} ";
  }
  return $error;
}

function _asset_process_syncml_requests() {
  $anchor = $GLOBALS["sel_folder"]["anchor"];
  if (!($pos = strpos($anchor,"_"))) return;

  $tfolder = $GLOBALS["t"]["folder"];
  $fields = $GLOBALS["table"]["fields"];
  $lastsync = $GLOBALS["sel_folder"]["lastsync"];

  $username = substr($anchor,$pos+1);
  $module = substr($anchor,0,$pos);

  db_update("simple_sys_tree",array("lastsync"=>NOW),array("id=@id@"),array("id"=>$tfolder));
  if (in_array($module,array("calendar","tasks","contacts","notes"))) {
    sync4j::import_createedit($tfolder, $module, $username, $lastsync, $fields);
  }
}

function _asset_process_pages($maxdatasets) {
  $t = &$GLOBALS["t"];
  $tname = $t["title"];
  $tview = $t["view"];
  $tfolder = $t["folder"];
  $t["maxdatasets"] = $maxdatasets;

  if (isset($_REQUEST["limit"])) $_SESSION[$tname][$tview]["limit"] = $_REQUEST["limit"];
  if (isset($_SESSION[$tname][$tview]["limit"]) and is_numeric($_SESSION[$tname][$tview]["limit"]) and $_SESSION[$tname][$tview]["limit"]>0) {
    $t["limit"] = $_SESSION[$tname][$tview]["limit"];
  }
  if ($t["limit"]>ASSET_PAGE_LIMIT and empty($_REQUEST["export"])) $t["limit"] = ASSET_PAGE_LIMIT;

  $t["limits"] = isset($_REQUEST["form_fields"]) ? $_REQUEST["form_fields"] : range(0,$t["limit"]-1);
  $t["lastpage"] = ceil($t["maxdatasets"]/$t["limit"]);
  if ($t["lastpage"]==0 or isset($_REQUEST["print_all"])) $t["lastpage"] = 1;

  if (isset($_REQUEST["page"])) $_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"] = $_REQUEST["page"];
  if (isset($_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"]) and $_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"] > $t["lastpage"]) $_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"] = $t["lastpage"];
  if (isset($_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"]) and is_numeric($_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"]) and $_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"]>0) {
    $t["page"] = $_SESSION[$tname]["_".$tfolder]["_".$t["limit"]]["page"];
  }
  if (!empty($_SESSION["_".$tfolder][$tview]["item"])) {
	$t["lastpage"] = 1;
	$t["page"] = 1;
  }
  $t["prevpage"] = ($t["page"]<=1?1:$t["page"]-1);
  $t["nextpage"] = ($t["page"]==$t["lastpage"]?$t["lastpage"]:$t["page"]+1);
}

function asset_process_session_request() {
  $t = &$GLOBALS["t"];
  $tname = $t["title"];
  $tview = $t["view"];
  $tfolder = $t["folder"];

  if (SYNC4J and !empty($GLOBALS["sel_folder"]["anchor"])) _asset_process_syncml_requests();

  if ($tview=="search") {
    if (empty($_SESSION["_".$tfolder]["request"]["search"])) {
	  $_SESSION["_".$tfolder]["request"]["search"] = array("query"=>"*","module"=>"","subfolders"=>1);
	}
    if (isset($_REQUEST["search"])) {
	  if (empty($_REQUEST["module"])) $_REQUEST["module"] = "";
	  if (empty($_REQUEST["similar"])) $_REQUEST["similar"] = "";
	  if (!isset($_REQUEST["subfolders"])) $_REQUEST["subfolders"] = 1;
	  $_SESSION["_".$tfolder]["request"]["search"] = array(
		"query"=>$_REQUEST["search"],"module"=>$_REQUEST["module"],"similar"=>$_REQUEST["similar"],
		"subfolders"=>$_REQUEST["subfolders"]);
	}
	$t["search"] = $_SESSION["_".$tfolder]["request"]["search"];
	$value = modify::searchindex($t["search"]["query"]);

	$search_snd = "";
	if (!empty($t["search"]["similar"])) {
	  $search_arr = explode(" ",$value);
	  foreach ($search_arr as $key=>$value) {
		$val = soundex($value);
		if ($val!="0000") $search_arr[$key] = $val; else unset($search_arr[$key]);
	  }
	  $search_snd = implode(" ",$search_arr);
	}
	if ($search_snd!="") $search_snd = "%".$search_snd."%";
	if ($value!="") $term_two = "%".$value."%"; else $term_two = "";

	$search_rights = str_replace("r@right@_","t.rread_",$_SESSION["permission_sql"])." and ".str_replace("r@right@_","s.rread_",$_SESSION["permission_sql"]);
	if (!isset($GLOBALS["sel_folder"]["lft"])) $folder = array("lft"=>0,"rgt"=>0); else $folder = $GLOBALS["sel_folder"];

	$vars = array("lft"=>$folder["lft"],"rgt"=>$folder["rgt"],"search"=>$value,
	  "search2"=>$term_two,"search_snd"=>$search_snd,"module"=>$t["search"]["module"],"subfolders"=>$t["search"]["subfolders"]);
	$t["sqlvars"] = array_merge($t["sqlvars"],$vars);
	$t["sqlvarsnoquote"] = array_merge($t["sqlvarsnoquote"],array("search_rights"=>$search_rights));
  }

  if ($t["default_sql"]!="" and $t["default_sql"]!="no_select") {
  	$matches = "";
	if (preg_match_all("|@(.*?)@|i",$t["default_sql"],$matches,PREG_SET_ORDER)) {
	  foreach ($matches as $match) {
		if (count($match)!=2) continue;
		$req_key = $match[1];
		if (isset($_SESSION[$req_key])) {
		  $t["default_sql"] = str_replace("@".$req_key."@",sys_correct_quote($_SESSION[$req_key]),$t["default_sql"]);
  } } } }

  if (isset($_REQUEST["session_remove_request"])) {
    $_SESSION["_".$tfolder]["request"] = array();
    $_SESSION["_".$tfolder][$tview] = array();
  }

  if (isset($_REQUEST["subitem"])) $_SESSION["_".$tfolder][$tview]["subitem"] = $_REQUEST["subitem"];
  if (isset($_SESSION["_".$tfolder][$tview]["subitem"])) $t["subitem"] = $_SESSION["_".$tfolder][$tview]["subitem"];

  if (isset($_REQUEST["filters"])) $_SESSION["_".$tfolder][$tview]["filters"] = $_REQUEST["filters"];
  if (isset($_SESSION["_".$tfolder][$tview]["filters"])) $t["filter"] = $_SESSION["_".$tfolder][$tview]["filters"];

  if ($t["filter"]!="") {
	$t["sqlfilters"] = array();
	$ops = array("eq","neq","lt","gt","like","nlike","starts","oneof");
	foreach (explode("||",$t["filter"]) as $key=>$filter) {
	  $filter = explode("|",$filter);
	  if (count($filter)==3 and isset($t["fields"][$filter[0]]) and in_array($filter[1],$ops)) {
		if (trim($filter[2])=="") continue;
		if (!empty($t["search"])) {
		  if ($filter[0]=="lastmodifiedby") $t["search"]["user"] = $filter[2];
		  if ($filter[0]=="lastmodified") $t["search"]["modified"] = array("type"=>$filter[1],"value"=>$filter[2]);
		}
        $t["sqlfilters"][] = array("field"=>$filter[0],"type"=>$filter[1],"value"=>$filter[2]);

		if ($t["fields"][$filter[0]]["SIMPLE_TYPE"]=="checkbox") {
		  $filter[2] = $filter[2]==sys_remove_trans("{t}yes{/t}")?"1":"0";
		}
		if ($filter[1]=="oneof") $filter[2] = explode(",",$filter[2]);
		if (in_array($t["fields"][$filter[0]]["SIMPLE_TYPE"],array("date","dateselect","time","datetime"))) {
		  $filter[2] = modify::datetime_to_int($filter[2]);
		}
	    $op = "=";
		$key_var = "@filter_value_".$key."@";
	    switch ($filter[1]) {
		  case "neq": $op = "!="; break;
		  case "oneof": $op = "in"; $key_var = "(".$key_var.")"; break;
		  case "lt": $op = "<"; break;
		  case "gt": $op = ">"; break;
		  case "like": $op = "like"; $filter[2] = "%".$filter[2]."%"; break;
		  case "nlike": $op = "not like"; $filter[2] = "%".$filter[2]."%"; break;
		  case "starts": $op = "like"; $filter[2] = $filter[2]."%"; break;
		}
		$t["sqlvars"]["filter_value_".$key] = $filter[2];
		$t["sqlwhere"][] = $filter[0]." ".$op." ".$key_var;
		$t["sqlwhere_default"][] = $filter[0]." ".$op." ".$key_var;
  } } }

  if (!empty($t["att"]["ENABLE_ASSET_RIGHTS"])) {
    $t["sqlvarsnoquote"]["permission_sql_read_nq"] = $_SESSION["permission_sql_read"];
    $t["sqlvarsnoquote"]["permission_sql_write_nq"] = $_SESSION["permission_sql_write"];
  }

  foreach ($t["sqlwhere_default"] as $key=>$value) {
	if (!preg_match_all("|@(.*?)@|i",$value,$matches,PREG_SET_ORDER)) continue;
	foreach ($matches as $match) {
	  if (count($match)!=2) continue;
	  $req_key = $match[1];
	  if (isset($_SESSION[$req_key])) $t["sqlvars"][$req_key] = $_SESSION[$req_key];
	}
  }

  if ($t["default_sql"]=="" or $t["default_sql"]!="no_select") {
	$vars = array("handler"=>$t["handler"],"sqlvarsnoquote"=>$t["sqlvarsnoquote"],"default_sql"=>$t["default_sql"],"custom_name"=>$t["custom_name"]);
	$fcount = $GLOBALS["sel_folder"]["fcount"];

	if (!empty($vars["handler"]) or !empty($vars["default_sql"]) or count($t["sqlvars"]["folders"])>1 or !empty($t["custom_name"])) {
	  $t["maxdatasets"] = db_count($tname,$t["sqlwhere_default"],$t["sqlvars"],$vars);
	} else if (!sys_strbegins($tname, "simple_sys_") and ($t["sqlwhere_default"]==array("folder in (@folders@)") or $fcount==0)) {
	  $t["maxdatasets"] = $fcount;
	} else if ($fcount > 100) {
	  $cid = "dbcount_".sha1(serialize(array($t["sqlvars"], $t["sqlwhere_default"], $fcount)));
	  $t["maxdatasets"] = sys_cache_get($cid);
	  if ($t["maxdatasets"]===false) {
		$t["maxdatasets"] = db_count($tname,$t["sqlwhere_default"],$t["sqlvars"],$vars);
		sys_cache_set($cid, $t["maxdatasets"], OUTPUT_CACHE);
	  }
	} else {
	  $t["maxdatasets"] = db_count($tname,$t["sqlwhere_default"],$t["sqlvars"],$vars);
	}
  } else {
    $t["maxdatasets"] = 0;
  }

  if (isset($_REQUEST["orderby"]) and isset($_REQUEST["order"])) {
    $_SESSION[$tname][$tview]["orderby"] = $_REQUEST["orderby"];
    $_SESSION[$tname][$tview]["order"] = $_REQUEST["order"];
  }

  $t["hidden_fields"] = array();
  if (isset($_REQUEST["hide_fields"])) $_SESSION[$tname][$tview]["hidden"] = explode(",", $_REQUEST["hide_fields"]);
  if (!empty($_SESSION[$tname][$tview]["hidden"]) and empty($t["views"][$tview]["SCHEMA_MODE"])) {
	$t["hidden_fields"] = $_SESSION[$tname][$tview]["hidden"];
	foreach ($t["hidden_fields"] as $field) unset($t["fields"][$field]);
  }

  if (isset($_SESSION[$tname][$tview]["order"]) and isset($_SESSION[$tname][$tview]["orderby"]) and
      in_array($_SESSION[$tname][$tview]["order"],array("asc","desc")) and isset($t["fields"][$_SESSION[$tname][$tview]["orderby"]])) {
    $t["orderby"] = $_SESSION[$tname][$tview]["orderby"];
    $t["order"] = $_SESSION[$tname][$tview]["order"];
  }

  foreach ($t["sqlwhere"] as $key=>$value) {
	if (!preg_match_all("|@(.*?)@|i",$value,$matches,PREG_SET_ORDER)) continue;
	foreach ($matches as $match) {
	  if (count($match)!=2) continue;
	  $req_key = $match[1];
	  if ($req_key=="folders") continue;
	  if ($req_key=="item") $skey = $tview; else $skey = "request";
	  if (isset($_SESSION["_".$tfolder][$skey][$req_key])) $t["sqlvars"][$req_key] = $_SESSION["_".$tfolder][$skey][$req_key];
	  if (isset($_SESSION[$req_key])) $t["sqlvars"][$req_key] = $_SESSION[$req_key];
	  if (isset($_REQUEST[$req_key])) {
		$t["sqlvars"][$req_key] = $_REQUEST[$req_key];
		$_SESSION["_".$tfolder][$skey][$req_key] = $_REQUEST[$req_key];
	  }
	  if (!isset($t["sqlvars"][$req_key]) and empty($t["sqlvarsnoquote"][$req_key])) $t["sqlwhere"][$key] = "1=1";
	}
  }

  if (!isset($_SESSION[$tname][$tview]["group"])) $_SESSION[$tname][$tview]["group"] = $t["group"];
  if (!isset($_SESSION[$tname][$tview]["groupby"])) $_SESSION[$tname][$tview]["groupby"] = $t["groupby"];

  if (isset($_REQUEST["group"])) {
    if ($_SESSION[$tname][$tview]["groupby"]=="") {
      $_SESSION[$tname][$tview]["groupby"] = $t["orderby"];
      $_SESSION[$tname][$tview]["group"] = $t["order"];
	} else {
      $_SESSION[$tname][$tview]["groupby"] = "";
      $_SESSION[$tname][$tview]["group"] = "";
	}
  }
  if (!isset($_REQUEST["plain"])) {
    $t["groupby"] = $_SESSION[$tname][$tview]["groupby"];
    $t["group"] = $_SESSION[$tname][$tview]["group"];
  } else {
    $t["groupby"] = "";
	$t["group"] = "";
  }
  if ($t["groupby"]!="" and isset($t["fields"][$t["groupby"]])) {
    $field = $t["fields"][$t["groupby"]];
	$field["WIDTH"] = 0;
	unset($t["fields"][$t["groupby"]]);
	unset($t["fields_query"][$t["groupby"]]);
	$t["fields"] = array_merge(array($t["groupby"]=>$field),$t["fields"]);
	$t["fields_query"] = array_unique(array_merge(array($t["groupby"]),$t["fields_query"]));
  }

  _asset_process_pages($t["maxdatasets"]);

  if (!isset($t["views"][$tview]["NOSQLORDER"])) $t["sqlorder"] = ($t["groupby"]!=""?$t["groupby"]." ".$t["group"].",":"").$t["orderby"]." ".$t["order"];
  if (!isset($t["views"][$tview]["NOSQLLIMIT"]) and empty($_REQUEST["print_all"]) and (empty($_REQUEST["export"]) or !empty($_REQUEST["limit"]))) {
    $t["sqllimit"] = array(($t["page"]-1)*$t["limit"],$t["limit"]);
  }
  if (!empty($_REQUEST["iframe"]) and isset($_REQUEST["session_remove_request"])) $t["sqllimit"] = array(); // offline sync

  if (!empty($t["sqlvars"]["item"]) and $t["views"][$tview]["SCHEMA_MODE"]=="edit" and $t["maxdatasets"]==0) {
	sys_warning("{t}Item(s) not found or access denied.{/t}");
  }
  if ($t["maxdatasets"]!=0) _asset_get_rows();

  if ((!empty($_REQUEST["form_submit_create"]) or !empty($_REQUEST["form_submit_edit"])) and $t["rights"]["write"] and $t["schema_mode"]!="") {
	$mode = ($t["schema_mode"]=="edit" ? "edit" : "create");
    list($t["errors"], $defaults, $form_ids, $saved_ids) = asset::create_edit($tfolder, $tview, $mode);

	foreach ($t["limits"] as $key=>$val) {
	  if (in_array($val, $form_ids)) unset($t["limits"][$key]);
	}
	if (count($t["errors"])==0) {
	  $t["limits"] = range(0,$t["limit"]-1);

	  if (!empty($_REQUEST["form_submit_return"])) {
		$arr = array_pop(array_slice($_SESSION["history"],-2,1));
		if (empty($arr[2])) $arr[2] = "default";
		sys_redirect("index.php?view=".$arr[2]."&".sys::$urladdon);
	  }
	  if (!empty($_REQUEST["form_submit_go_edit"])) {
		$items = "";
		foreach ($saved_ids as $id) $items .= "&item[]=".rawurlencode($id);
		sys_redirect("index.php?view=edit".$items."&".sys::$urladdon);
	  }
	}
	foreach ($defaults as $id=>$field ) {
	  foreach ($field as $field_name=>$value) {
	    $t["fields"][$field_name]["SIMPLE_DEFAULTS"][$id] = $value;
		$t["fields"][$field_name]["SIMPLE_DEFAULT"] = "";
  } } }

  sys::$smarty->assign_by_ref("t",$t);
}

function ______D_B______() {}

function db_lock_tree($lock) {
  static $locked = false;
  $lock_file = SIMPLE_STORE."/lock_tree";
  $stack = explode("\n",sys_backtrace());

  if ($lock and !$locked) {
    $i = 0;
    while (file_exists($lock_file) and filemtime($lock_file)+30 > time() and $i<60) {
	  sleep(1);
	  $i++;
	}
    if (!file_exists($lock_file)) touch($lock_file);
    $locked = $stack[2]; // caller
  } else if (!$lock and $locked and $locked==$stack[2]) {
    @unlink($lock_file);
    $locked = false;
  }
}

function db_optimize_tables() {
  if (!sql_table_optimize()) sys_log_message_log("db-fail","optimize ".sql_error());
}

/*
 * Quotas:
 * selected folder or first parent matches the quota rule
 * quota of 0 means unlimited
 */
function db_get_quota($sel_folder) {
  if (!is_numeric($sel_folder["id"])) return 0;
  $row = db_select_first("simple_sys_tree",array("fquota","fsizecount","fchsizecount"),array("@left@ between lft and rgt","fquota>0"),"lft desc",array("left"=>$sel_folder["lft"]));
  if (!empty($row["fquota"])) {
	$row["fquota"] *= 1048576;
	return array("self"=>$row["fquota"],"quota"=>$row["fquota"],"remain"=>$row["fquota"]-$row["fsizecount"]-$row["fchsizecount"]);
  }
  $sel_folder["fquota"] *= 1048576;
  if (!empty($sel_folder["fquota"])) $used = $sel_folder["fsizecount"]+$sel_folder["fchsizecount"]; else $used = 0;
  return array("self"=>$sel_folder["fquota"],"quota"=>$sel_folder["fquota"],"remain"=>$sel_folder["fquota"]-$used);
}

function db_check_quota($tfolder) {
  $sel_folder = db_select_first("simple_sys_tree",array("id","lft","rgt","fquota","fsizecount", "fchsizecount"),"id=@id@","",array("id"=>$tfolder));
  $quota = db_get_quota($sel_folder);
  if ($quota["remain"] < 0) {
	sys_log_message_log("quota",sprintf("{t}Quota limitation exceeded: %s{/t}",modify::filesize($quota["remain"])." id: ".$tfolder));
	sys_warning(sprintf("{t}Quota limitation exceeded: %s{/t}",modify::filesize($quota["remain"])));
  }
}

function db_get_parents($sel_folder) {
  if (empty($sel_folder["id"])) return array();
  if (!is_numeric($sel_folder["id"])) {
	$url = sys_parse_folder($sel_folder["id"]);
	$mfolder = $url["mfolder"];
	if ($mfolder=="") return array();
	$m_sel_folder = folder_build_selfolder($mfolder,"");
	$parents = array();
	$mountpoint = $url["mountpoint"];
	$url = sys_parse_folder($m_sel_folder["fmountpoint"]);
	if ($url["handler"]=="") return array();
	$i=0;
	while($url["path"]!=$mountpoint and $mountpoint!="../" and $mountpoint!="./") {
	  $mountpoint = dirname($mountpoint)."/";
	  $parents[] = array("id"=>$url["handler"].":".$mfolder."/".$mountpoint,"ftitle"=>basename($mountpoint));
	  if ($i++ > 20) break;
	}
	if (is_numeric($m_sel_folder["id"])) {
      $parents = array_merge($parents,db_select("simple_sys_tree",array("id","ftitle"),array("@left@ between lft and rgt",$_SESSION["permission_sql_read"]),"lft desc","",array("left"=>$m_sel_folder["lft"])));
	}
	$parents = array_reverse($parents);
  } else {
    $parents = db_select("simple_sys_tree",array("id","ftitle"),array("lft<@left@","rgt>@left@",$_SESSION["permission_sql_read"]),"lft asc","",array("left"=>$sel_folder["lft"]));
  }
  return $parents;
}

function db_get_children($sel_folder) {
  if (empty($sel_folder["id"])) return array();
  $children = array();
  if (!is_numeric($sel_folder["id"])) {
	$url = sys_parse_folder($sel_folder["id"]);
	$mfolder = (int)$url["mfolder"];
    if ($sel_folder["fmountpoint"]!="" and $url["handler"]!="") {
	  sys_credentials($mfolder, $sel_folder["fmountpoint"]);
	  $children = folder_get_mount_dirs($url["mountpoint"], $url["handler"], $mfolder, true);
	  if (count($children)>0) array_shift($children);
	}
  } else {
    if ($sel_folder["fmountpoint"]!="") {
	  $url = sys_credentials($sel_folder["id"], $sel_folder["fmountpoint"]);
      if (!empty($url["path"]) and !empty($url["handler"])) {
	    $children = folder_get_mount_dirs($url["path"], $url["handler"], $sel_folder["id"], false);
	  }
    }
    $children = array_merge($children, db_select("simple_sys_tree",array("id","ftitle","fcount","ftype","ffcount","icon","fdescription","length(coalesce(fmountpoint,'')) as mp","anchor"),array("parent=@parent@", $_SESSION["permission_sql_read"]),"lft asc","",array("parent"=>$sel_folder["id"])));
  }
  return $children;
}

function db_get_rights($folder,$view="") {
  $rights_list = array("read","write","admin");
  $rights = array("read"=>false,"write"=>false,"admin"=>false,"read_folder"=>false,"write_folder"=>false,"admin_folder"=>false);
  if (!is_numeric($folder)) {
    $mfolder = sys_parse_folder($folder,"mfolder");
    if (!is_numeric($mfolder)) {
	  $rights["read"] = $rights["read_folder"] = true;
	} else return db_get_rights($mfolder,$view);
  } else {
    foreach ($rights_list as $right) {
	  static $cache = array();
	  if (!isset($cache[$folder.$right])) {
		$cache[$folder.$right] = false;
		$row_id = db_select_value("simple_sys_tree","id",array("id=@id@",str_replace("@right@",$right,$_SESSION["permission_sql"])),array("id"=>$folder));
		if (!empty($row_id)) $cache[$folder.$right] = true;
	  }
	  $rights[$right."_folder"] = $rights[$right] = $cache[$folder.$right];

	  // view exception, e.g. freebusy:read:anonymous, details:no_read:anonymous => invert permission
	  if ($view!="" and $right!="admin") {
	    if (db_get_view_right($folder,$view,$rights[$right] ? "no_".$right : $right)) $rights[$right] = !$rights[$right];
  } } }
  return $rights;
}

function db_get_right($folder,$right,$view="") {
  $rights = db_get_rights($folder,$view);
  return $rights[$right];
}

// rexception-syntax: view[,view2]:right:username[,username2] or view[,view2]:right:groupname[,groupname2]
function _db_get_view_right_eval($rexception,$find) {
  if (empty($rexception) or !is_array($find) or count($find)==0) return array();
  $exceptions = array();
  $exceptions_arr = explode("|",trim($rexception,"|"));
  foreach ($exceptions_arr as $val) {
	$val = explode(":",$val);
	if (empty($val[2])) continue;
	foreach (explode(",",$val[2]) as $user_group) {
	  if (in_array($user_group,$find)) {
	    if (!isset($exceptions[$val[1]])) $exceptions[$val[1]] = array();
	    $exceptions[$val[1]] = array_merge($exceptions[$val[1]], explode(",",$val[0]));
  } } }
  return $exceptions;
}

function db_get_view_right($folder,$view,$right) {
  if (!is_numeric($folder)) return false;
  static $cache = array();
  if (!isset($cache[$folder])) {
    $cache[$folder] = array();
    $row = db_select_first("simple_sys_tree",array("rexception_users","rexception_groups"),array("id=@id@"),"",array("id"=>$folder));
	if (is_array($row) and count($row)>0) {
	  $cache[$folder] = array_merge_recursive(
	  	_db_get_view_right_eval($row["rexception_users"],array("anonymous",$_SESSION["username"])), // anonymous=all users
	  	_db_get_view_right_eval($row["rexception_groups"],$_SESSION["groups"])
	  );
	}
  }
  if (isset($cache[$folder][$right]) and in_array($view,$cache[$folder][$right])) return true;
  return false;
}

function db_notification_delete($table, $id) {
  if (empty($id) or empty($table)) return;
  db_delete("simple_sys_notifications", array("reference=@ref@ or reference like @ref2@"), array("ref"=>$table."|".$id, "ref2"=>$table."|".$id."&%"));
}

// reference: simple_xy|id, to: xy@za.bla, delivery: unix-timestamp, recurrence: +1 month or array(timestamps)
function db_notification_add($reference, $to, $subject, $message, $delivery, $recurrence="") {
  if (empty($reference) or empty($to) or empty($subject)) return;
  $counter = 0;

  while ($delivery < NOW and !empty($recurrence) and $counter<365) {
	$delivery = is_array($recurrence) ? array_shift($recurrence) : strtotime($recurrence, $delivery);
	$counter++;
  }
  $id = sql_genID("simple_sys_notifications")*100+$_SESSION["serverid"];
  $data = array(
    "id"=>$id, "eto"=>$to, "reference"=>$reference, "subject"=>$subject, "message"=>$message, "delivery"=>(int)$delivery,
	"recurrence"=>is_array($recurrence) ? "|".implode("|", $recurrence)."|" : $recurrence, "category"=>"email", "sent"=>0
  );
  db_insert("simple_sys_notifications", $data);
}

function db_search_delete($table,$id,$folder) {
  if (strpos($table,"_nodb_")) return;
  db_delete("simple_sys_search",array("id=@id@","folder=@folder@"),array("id"=>$id,"folder"=>$folder));
}

function db_search_delete_folder($folder) {
  db_delete("simple_sys_search",array("folder=@folder@"),array("folder"=>$folder));
}

function db_search_update($table,$id,$fields,$field_arr=array()) {
  if (strpos($table,"_nodb_")) return;
  $row = db_select_first($table,"*","id=@id@","",array("id"=>$id));
  if (empty($row["id"])) return;
  if ($table=="simple_sys_tree") $id = 0; else $id = $row["id"];

  if ($table=="simple_sys_events") {
    $folder = db_select_value("simple_sys_tree","id","ftype=@ftype@",array("ftype"=>"sys_events"));
	if (empty($folder)) return;
  } else $folder = $row["folder"];

  $rread_users = "|anonymous|";
  $rread_groups = "";
  $searchindex = "";
  $searchindex_snd = "";
  $searchcontent = "";
  foreach ($row as $data_key=>$data) {
	if ($data_key=="rread_users") $rread_users = $data;
	if ($data_key=="rread_groups") $rread_groups = $data;

	if (count($field_arr)>0 and isset($field_arr[$data_key])) {
	  $fields[$data_key] = array("SIMPLE_TYPE"=>$field_arr[$data_key]);
	}
    if (isset($fields[$data_key]) and $data!="" and (!is_numeric($data) or $data!=0) and $data!="null") {
	  $field = $fields[$data_key];
	  if (isset($field["NOTINALL"]) or isset($field["NO_SEARCH_INDEX"])) continue;
	  $data = trim($data,"|");
      $data2 = $data;
	  switch ($field["SIMPLE_TYPE"]) {
		case "pid":
		case "password": $data = ""; $data2 = ""; break;
		case "folder":
		case "id": $data2 = ""; break;
	    case "checkbox": if ($data) $data = $data_key; else $data = ""; $data2 = $data; break;
		case "time": $data = sys_date("{t}g:i a{/t}",$data); $data2 = $data; break;
	    case "date": $data = sys_date("{t}m/d/Y{/t}",$data); $data2 = $data; break;
		case "dateselect": $data2 = ""; foreach (explode("|",$data) as $date) $data2 .= " ".sys_date("{t}m/d/Y g:i a{/t}",$date); $data = $data2; break;
	    case "datetime": $data = sys_date("{t}m/d/Y g:i a{/t}",$data); $data2 = $data; break;
		case "files":
		  $data2 = "";
		  foreach (explode("|",$data) as $file) {
		    $text = modify::displayfile($table,$file,true);
		    $data2 .= " ".strip_tags($text)." ".modify::basename($file);
		  }
		  $data = $data2;
		  break;
		case "select": $data = str_replace("|"," ",$data); $data2 = $data; break;
		case "multitext": $data = str_replace(","," ",$data); $data2 = $data; break;
	  }
	  if ($data!="") $searchindex .= " ".preg_replace("/[ ]+/i"," ",modify::searchindex(trim($data)));
	  if ($data2!="") $searchcontent .= " ".trim(preg_replace("/[ ]+/i"," ",$data2));
	}
  }
  $searchcontent = trim($searchcontent);
  $searchindex = trim($searchindex);

  if (strlen($searchindex)>INDEX_LIMIT) {
    $pos = strpos($searchindex," ",INDEX_LIMIT);
	if ($pos>0) $searchindex = substr($searchindex,0,$pos);
  }

  $search_arr = array_unique(explode(" ",$searchindex));
  foreach ($search_arr as $key=>$value) {
    $val = soundex($value);
    if ($val!="0000") $search_arr[$key] = $val; else unset($search_arr[$key]);
  }
  $searchindex_snd = implode(" ",$search_arr);

  if (strlen($searchindex_snd)>8192) {
    $pos = strpos($searchindex_snd," ",8192);
	if ($pos>0) $searchindex_snd = substr($searchindex_snd,0,$pos);
  }
  $data = array(
    "sindex"=>$searchindex, "sindex_snd"=>$searchindex_snd, "searchcontent"=>$searchcontent,
	"lastmodifiedby"=>!empty($row["lastmodifiedby"])?$row["lastmodifiedby"]:"anonymous",
	"lastmodified"=>!empty($row["lastmodified"])?$row["lastmodified"]:0,
	"rread_users"=>$rread_users,"rread_groups"=>$rread_groups,
  );

  $count = db_count("simple_sys_search",array("id=@id@","folder=@folder@"),array("id"=>$id,"folder"=>$folder));
  if ($count>0) {
	db_update("simple_sys_search",$data,array("id=@id@","folder=@folder@"),array("id"=>$id,"folder"=>$folder));
  } else {
    $data = array_merge($data,array("id"=>$id,"folder"=>$folder, "history"=>""));
    db_insert("simple_sys_search",$data,array("delay"=>true));
  }
}

function db_get_schema($schema_file, $folder="", $tview="", $cache=true, $popup=false) {
  static $data = array();
  if (!$cache) $data = array();

  $cid = $schema_file.$folder;
  if (!empty($data[$cid])) {
	if ($tview=="") return $data[$cid];
	if (!isset($data[$cid][$tview])) $tview = sys_array_shift(array_keys($data[$cid]));
	return $data[$cid][$tview];
  }
  if (!file_exists($schema_file)) {
    if (basename($schema_file)=="nodb_.xml") {
	  sys_warning(sprintf("{t}Folder not found.{/t} (%s)", $folder));
	} else {
	  sys_log_message_alert("php-fail", sprintf("{t}Schemafile not found. (%s){/t}", $schema_file." ".$folder));
	}
    $schema_file = "modules/schema/blank.xml";
  }
  $schema = basename(substr($schema_file,0,-4));
  $cache_file = SIMPLE_CACHE."/schema/".CORE_SGSML_VERSION."_".$schema.".ser";

  $custom_schema = "";
  if ($folder!="") {
	if (file_exists(sys_custom($schema_file.".".$folder))) {
	  $schema_file = sys_custom($schema_file.".".$folder);
	  $cache_file .= ".".$folder;
    }
    $custom_schema = db_select_value("simple_sys_tree","custom_schema","id=@id@",array("id"=>$folder));
	
	// TODO optimize
	$rows = db_select("simple_sys_custom_fields",array("custom_schema"),array("module=@schema@", "(ffolder='' or ffolder like @folder@)", "activated=1"),"id asc","",array("schema"=>$schema, "folder"=>"%|".$folder."|%"));
	if (is_array($rows) and count($rows)>0) {
	  $custom_schema = str_replace("</table>", "", $custom_schema);
	  if (!strpos($custom_schema, "<table")) $custom_schema = "<table>";
	  foreach ($rows as $row) $custom_schema .= $row["custom_schema"]."\n";
	  $custom_schema .= "</table>";
	}
    if ($custom_schema!="") $cache_file .= ".".sha1($custom_schema);
  }
  $custom_dir = sys_custom_dir(substr($schema_file,0,-4));
  if (is_dir($custom_dir)) $cache_file .= ".".filemtime($custom_dir);
  $schema_mtime = filemtime($schema_file);

  if (APC) {
	$data[$cid] = apc_fetch("sgsml".basename($cache_file).$schema_mtime);
  } else if (file_exists($cache_file) and filemtime($cache_file)==$schema_mtime) {
	$data[$cid] = unserialize(file_get_contents($cache_file));
  }
  if (empty($data[$cid])) {
    if (DEBUG and empty($_REQUEST["iframe"])) echo "reload schema";
	$schema_content = sgsml_parser::file_get_contents($schema_file,$schema,$custom_schema);
  	$data[$cid] = sgsml_parser::parse_schema($schema_content,$schema,$schema_mtime,$cache_file);

    if (defined("SETUP_DB_HOST")) {
	  sys_log_message_log("info",sprintf("{t}Updating schema %s from %s.{/t} {t}Folder{/t}: %s",$schema,$schema_file,$folder));
	}
  }
  if ($tview=="") return $data[$cid];

  if ($folder!="") {
	$write = true;
	if ($popup) {
	  $ftype = str_replace("simple_","",$data[$cid]["att"]["NAME"]);
	  if (!in_array($ftype,explode("\n",file_get_contents(sys_custom("modules/core/popup_write.txt"))))) $write = false;
	}
	$superadmin = sys_is_super_admin($_SESSION["username"]);
	foreach (array_keys($data[$cid]["views"]) as $view) {
	  if (isset($data[$cid]["views"][$view]["RIGHT"])) $right = $data[$cid]["views"][$view]["RIGHT"]; else $right = "read";
	  if (($write or $right!="write") and ($superadmin or db_get_right($folder,$right,$view))) continue;
	  unset($data[$cid][$view]);
	  unset($data[$cid]["views"][$view]);
	}
  }
  if (isset($data[$cid][$tview])) {
    return $data[$cid][$tview];
  } else {
	if ($tview!="none") sys_warning("{t}Item(s) not found or access denied.{/t} (view={$tview})");
	$GLOBALS["tview"] = sys_array_shift(array_keys($data[$cid]["views"]));
	if (empty($GLOBALS["tview"])) return db_get_schema("modules/schema/blank.xml","","display");
	return $data[$cid][$GLOBALS["tview"]];
  }
}

function db_update_treesize($tname,$tfolder) {
  if (is_numeric($tfolder) and !strpos($tname,"_nodb_") and $tname!="simple_sys_tree") {
    db_lock_tree(true);
	$row = db_select_first("simple_sys_tree",array("lft","fcount","fsizecount"),"id=@id@","",array("id"=>$tfolder));
	if (isset($row["lft"])) {
	  $count = (int)db_select_value($tname,"count(*) as count","folder=@id@",array("id"=>$tfolder));
	  $sum = (int)db_select_value($tname,"coalesce(sum(dsize),0) as count","folder=@id@",array("id"=>$tfolder));

	  db_update("simple_sys_tree",array("fcount"=>$count),array("id=@id@"),array("id"=>$tfolder));
	  db_update("simple_sys_tree",array("fsizecount"=>$sum),array("id=@id@"),array("id"=>$tfolder));

	  $diff_count = $count - $row["fcount"];
	  $diff_sum = $sum - $row["fsizecount"];

  	  if ($diff_count!=0 or $diff_sum!=0) {
	    if ($diff_count>=0) $diff_count = "+".$diff_count;
	    if ($diff_sum>=0) $diff_sum = "+".$diff_sum;
	    db_update("simple_sys_tree",array("fchsizecount"=>"fchsizecount".$diff_sum,"fchcount"=>"fchcount".$diff_count),array("lft<@lft@","rgt>@lft@"),array("lft"=>$row["lft"]),array("quote"=>false, "no_defaults"=>true));
      }
	}
    db_lock_tree(false);
	db_check_quota($tfolder);
  }
}

function db_update_subfolder_count($tfolder) {
  if (!is_numeric($tfolder)) return;
  $count = db_count("simple_sys_tree",array("parent=@id@"),array("id"=>$tfolder));
  db_update("simple_sys_tree",array("ffcount"=>$count),array("id=@id@"),array("id"=>$tfolder));
}

function db_count($table,$sql_where,$vars,$optional=array()) {
  if (!empty($optional["handler"])) {
    $handler = "lib_".$optional["handler"];
	$vars = sys_remove_handler($vars);
	return call_user_func(array($handler,"count"),$vars["folder"],$GLOBALS["current_view"]["WHERE"],$vars,$vars["mfolder"]);
  } else {
	if (!empty($optional["default_sql"]) and $optional["default_sql"]!="no_select") {
	  // TODO optimize
      return count(db_select($table,"count(*) as count",$sql_where,"","",$vars,$optional));
	} else {
      return (int)db_select_value($table,"count(*) as count",$sql_where,$vars,$optional);
} } }

function db_query($sql, $vars=array()) {
  if (is_array($sql)) {
    foreach ($sql as $cmd) if (($msg=db_query($cmd,$vars))) return $msg;
	return "";
  }
  if (is_array($vars) and count($vars)>0) {
    foreach (array_keys($vars) as $key) {
	  $vars[$key] = sys_correct_quote($vars[$key]);
      $sql = str_replace("@".$key."@",$vars[$key],$sql);
    }
  }
  $time_start = sys_get_microtime();
  if (sql_query($sql) === false) {
	$msg = sql_error();
	if (DEBUG) debug_sql("ERROR ".$sql,$msg);
	sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
	return "{t}Error{/t}: ".$msg;
  }
  $time = number_format(sys_get_microtime()-$time_start,4);
  if ($time > DB_SLOW) {
	$slow_log = var_export(sql_explain($sql),true);
	sys_log_message_log("db-slow",sprintf("{t}%s secs{/t}",$time)." ".$sql,sys_backtrace()." ".$slow_log);
  }
  sys::$db_queries[] = array($sql, $time);
  return "";
}

function db_fetch($sql, $vars=array()) {
  if (is_array($vars) and count($vars)>0) {
    foreach (array_keys($vars) as $key) {
	  $vars[$key] = sys_correct_quote($vars[$key]);
      $sql = str_replace("@".$key."@",$vars[$key],$sql);
    }
  }
  $time_start = sys_get_microtime();
  if (($rows = sql_fetch($sql)) === false) {
	$msg = sql_error();
	if (DEBUG) debug_sql("ERROR ".$sql,$msg);
	sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
	return "error";
  }
  $time = number_format(sys_get_microtime()-$time_start,4);
  if ($time > DB_SLOW) {
	$slow_log = var_export(sql_explain($sql),true);
	sys_log_message_log("db-slow",sprintf("{t}%s secs{/t}",$time)." ".$sql,sys_backtrace()." ".$slow_log);
  }
  sys::$db_queries[] = array($sql, $time);
  return $rows;
}

function db_select_first($table,$fields,$sql_where,$order,$vars=array(),$optional=array()) {
  $rows = db_select($table,$fields,$sql_where,$order,1,$vars,$optional);
  if (!is_array($rows) or !isset($rows[0])) return false;
  return $rows[0];
}

function db_select_json($table,$field,$sql_where,$vars) {
  return json_decode(db_select_value($table,$field,$sql_where,$vars),true);
}

function db_select_value($table,$field,$sql_where,$vars=array(),$optional=array()) {
  $rows = db_select($table,$field,$sql_where,"",1,$vars,$optional);
  if (!isset($rows[0]) or !is_array($rows)) return false;
  return implode("", $rows[0]);
}

function db_select($table,$fields,$sql_where,$order,$limit,$vars=array(),$optional=array()) {
  $fields = (array)$fields;
  $sql_where = (array)$sql_where;

  if (!is_array($limit)) {
  	if ($limit!="") $limit = array($limit); else $limit = array();
  }
  $rows = array();
  if (!empty($optional["handler"])) {
	$handler = "lib_".$optional["handler"];
	$folder = $vars["folder"];
	$vars = sys_remove_handler($vars);
	$rows = call_user_func(array($handler,"select"),$vars["folder"],$fields,$sql_where,$order,$limit,$vars,$vars["mfolder"]);
	if (count($rows)>0 and in_array("id",$fields)) foreach (array_keys($rows) as $key) {
	  $rows[$key]["folder"] = $folder;
	  $rows[$key]["id"] = $handler.":".$vars["mfolder"]."/".$rows[$key]["id"];
	}
	return $rows;
  }
  $groupby="";
  $where = "";
  if (count($sql_where)>0) {
    $where = str_replace("and 1=1",""," where ".implode(" and ",$sql_where));
  }
  if ($order!="") $order = " order by ".$order;
  if (!empty($optional["groupby"])) $groupby = " group by ".$optional["groupby"];
  if (!empty($optional["custom_name"])) $table = sql_translate(sql_concat($optional["custom_name"]));

  $sql = "select ".implode(",",$fields)." from ".$table.$where.sql_fieldname($order.$groupby,true);

  if (!empty($optional["default_sql"]) and $optional["default_sql"]!="no_select") {
    $sql = str_replace("@table@", $table, sql_translate($optional["default_sql"]));
  }

  if (is_array($vars) and count($vars)>0) {
    foreach (array_keys($vars) as $key) {
      $sql = str_replace("@".$key."@",sys_correct_quote($vars[$key]),$sql);
    }
  }

  if (!empty($optional["sqlvarsnoquote"]) and count($optional["sqlvarsnoquote"])>0) {
    foreach ($optional["sqlvarsnoquote"] as $key=>$val) {
      $sql = str_replace("@".$key."@",$val,$sql);
	}
  }

  $sql = str_replace("1=1 and ","",$sql);
  if (count($limit)>0) $sql = sql_limit($sql,isset($limit[1])?$limit[0]:0,isset($limit[1])?$limit[1]:$limit[0]);

  if ($sql != "none") {
    $time_start = sys_get_microtime();
    if (($rows = sql_fetch($sql)) === false) {
	  $msg = sql_error();
	  if (DEBUG) debug_sql("ERROR ".$sql,$msg);
	  sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
	  return "error";
	}
    $time = number_format(sys_get_microtime()-$time_start,4);
    if ($time > DB_SLOW) {
	  $slow_log = var_export(sql_explain($sql),true);
	  sys_log_message_log("db-slow",sprintf("{t}%s secs{/t}",$time)." ".$sql,sys_backtrace()." ".$slow_log);
	}
	sys::$db_queries[] = array($sql, $time);
    if (in_array("pid",$fields) and count($rows)>0) $rows = modify::threadsort($rows);
  }

  if (!empty($optional["default_sql"]) and $optional["default_sql"]!="no_select") {
    foreach ($rows as $key=>$row) {
	  if (!sys_select_where($row,$sql_where,$vars)) unset($rows[$key]);
    }
	return sys_select($rows,substr($order,10),$limit,$fields);
  }
  if ($limit==array("1") and count($rows)>1) $rows = array(array("count"=>count($rows)));
  if (count($limit)>0) {
    if (count($limit)==2 and count($rows) > $limit[1]) $rows = array_slice($rows,$limit[0],$limit[1]);
    if (count($limit)==1 and count($rows) > $limit[0]) $rows = array_slice($rows,0,$limit[0]);
  }
  return $rows;
}

function db_delete($table,$sql_where,$vars,$optional=array()) {
  if (!empty($optional["handler"])) {
    $handler = "lib_".$optional["handler"];
	$vars = sys_remove_handler($vars);
	return call_user_func(array($handler,"delete"),$vars["folder"],$sql_where,$vars,$vars["mfolder"]);
  }
  $where = "";
  if (count($sql_where)>0) $where = " where ".implode(" and ",$sql_where);

  if (is_array($vars) and count($vars)>0) {
    foreach (array_keys($vars) as $key) {
	  $vars[$key] = sys_correct_quote($vars[$key]);
      $where = str_replace("@".$key."@",$vars[$key],$where);
    }
  }
  $sql = "delete from ".sql_fieldname($table).$where;
  sys::$db_queries[] = $sql;

  if (sql_query($sql)===false) {
    $msg = sql_error();
    if (DEBUG) debug_sql("ERROR ".$sql,$msg);
	sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
    return "error";
  }
  return "";
}

function db_insert($table,$data,$optional=array()) {
  if (empty($optional["no_defaults"])) {
    if (empty($data["created"])) $data["created"] = NOW;
    if (empty($data["createdby"])) $data["createdby"] = isset($_SESSION["username"])?$_SESSION["username"]:"";
    if (empty($data["lastmodified"])) $data["lastmodified"] = NOW;
    if (empty($data["lastmodifiedby"])) $data["lastmodifiedby"] = isset($_SESSION["username"])?$_SESSION["username"]:"";
  }
  if (!empty($optional["handler"])) {
    $handler = "lib_".$optional["handler"];
	$data = sys_remove_handler($data);
	return call_user_func(array($handler,"insert"),$data["folder"],$data,$data["mfolder"]);
  }
  foreach (array_keys($data) as $key) {
    $data[$key] = sys_correct_quote($data[$key], !empty($optional["no_defaults"]));
  }
  if (SETUP_DB_TYPE=="mysql" and isset($optional["delay"])) $delay = "delayed"; else $delay = "";

  $sql = "insert ".$delay." into ".$table." (".implode(",",array_keys($data)).") values (".implode(",",$data).")";
  sys::$db_queries[] = $sql;

  if (sql_query($sql)===false) {
    $msg = sql_error();
   	if (DEBUG) debug_sql("ERROR ".$sql,$msg);
	if ($table!="simple_sys_events") sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
    return "error (".$msg.")";
  }
  return "";
}

function db_update($table,$data,$sql_where,$vars,$optional=array()) {
  if (empty($optional["no_defaults"])) {
	$data["lastmodified"] = NOW;
	$data["lastmodifiedby"] = isset($_SESSION["username"])?$_SESSION["username"]:"";
    if (isset($optional["quote"]) and !$optional["quote"]) $data["lastmodifiedby"] = sys_correct_quote($data["lastmodifiedby"]);
  }
  if (!empty($optional["handler"])) {
    $handler = "lib_".$optional["handler"];
	$data = sys_remove_handler($data);
	$vars = sys_remove_handler($vars);
	return call_user_func(array($handler,"update"),$vars["folder"],$data,$sql_where,$vars,$vars["mfolder"]);
  }
  $where = "";
  if (count($sql_where)>0) $where = " where ".implode(" and ",$sql_where);
  $set = "";
  foreach ($data as $key=>$value) {
    if ($set!="") $set .= ",";
	if (!isset($optional["quote"]) or $optional["quote"]) $value = sys_correct_quote($value, !empty($optional["no_defaults"]));
	if (isset($optional[$key."_append"]) or $key=="history") $value = sql_concat("concat(".$value.";".$key.")");
    $set .= $key."=".$value;
  }
  if (is_array($vars) and count($vars)>0) {
    foreach (array_keys($vars) as $key) {
	  $vars[$key] = sys_correct_quote($vars[$key]);
      $where = str_replace("@".$key."@",$vars[$key],$where);
    }
  }
  if (!empty($optional["sqlvarsnoquote"]) and count($optional["sqlvarsnoquote"])>0) {
    foreach ($optional["sqlvarsnoquote"] as $key=>$val) {
      $where = str_replace("@".$key."@",$val,$where);
	}
  }
  $sql = "update ".sql_fieldname($table)." set ".$set.$where;
  sys::$db_queries[] = $sql;

  if (sql_query($sql)===false) {
    $msg = sql_error();
	if (DEBUG) debug_sql("ERROR ".$sql,$msg);
    sys_log_message_log("db-fail",$sql." ".$msg,sys_backtrace());
    return "error (".$msg.")";
  }
  return "";
}

function ______D_E_B_U_G______() {}

function debug_handler($errno, $errstr, $errfile, $errline) {
  if ((error_reporting()!=E_ALL and $errno!=E_USER_ERROR) or $errno==E_STRICT) return; // avoid timezone error, smarty warnings

  $errortype = array (
    E_ERROR => "Error", E_WARNING => "Warning", E_PARSE => "Parsing Error", E_NOTICE => "Notice",
    E_CORE_ERROR => "Core Error", E_CORE_WARNING => "Core Warning", E_COMPILE_ERROR => "Compile Error",
    E_COMPILE_WARNING => "Compile Warning", E_USER_ERROR => "User Error",
    E_USER_WARNING => "User Warning", E_USER_NOTICE => "User Notice"
  );
  $err = "Php ".(isset($errortype[$errno])?$errortype[$errno]:$errno).": ".sys_date("{t}m/d/y g:i:s a{/t}")." ".$errstr." ".$errfile." ".$errline;

  if (isset($_SESSION["username"])) $username = $_SESSION["username"]; else $username = "anonymous";
  if (isset($_SESSION["serverid"])) $serverid = $_SESSION["serverid"]; else $serverid = "1";

  if ($errno!=E_USER_ERROR or error_reporting()!=E_ALL) {
    echo nl2br($err)."\n";
    if (DEBUG) echo sys_backtrace()."\n";
  }
  sys_log_message("php-fail",$err,sys_backtrace()."\n\$_REQUEST: ".print_r($_REQUEST,true),$username,$serverid,false);
}

function debug_file($var,$file="debug.txt") {
  $data = str_replace("\n","\r\n","\n\n-------------------------\n\n".var_export($var,true));
  if (!sys_file_append(SIMPLE_CACHE."/debug/".$file, $data)) {
    echo var_export($var,true);
  }
}

/**
 * in debug mode, refresh template cache automatically on change
 */
function debug_check_tpl() {
  $lastmod = filemtime(SIMPLE_CACHE."/smarty");
  $folders = array("templates/","templates/helper/",SIMPLE_CUSTOM."templates/",SIMPLE_CUSTOM."templates/helper/");
  foreach ($folders as $folder) {
    if (!is_dir($folder)) continue;
    foreach (scandir($folder) as $file) {
      if ($file[0]==".") continue;
	  if (filemtime($folder.$file)>$lastmod or filemtime($folder)>$lastmod) {
	    dirs_create_empty_dir(SIMPLE_CACHE."/smarty");
	    break;
} } } }

function debug_sql($var,$errmsg) {
  if (DEBUG) echo "<error>DEBUG ".$errmsg." ".$var."</error><br>";
  $trace = "# ".sys_backtrace()." ".sys_date("{t}m/d/y g:i:s a{/t}")."\n".preg_replace("/(\s+)/"," ",str_replace(array("\n","\r"),"",$var)).";".($errmsg!=""?"\n".$errmsg:"")."\r\n\r\n";
  sys_file_append(SIMPLE_CACHE."/debug/sql.log", $trace);
}

function debug_html($var) {
  exit("<pre>".print_r($var,true)."</pre>");
}

function debug_queries() {
  exit("<pre>".print_r(sys::$db_queries,true)."</pre>");
}

function ______D_I_R_S______() {}

function dirs_create_index_htm($path) {
  foreach (array("index.htm","index.html") as $file) {
    if (!file_exists($path.$file)) {
	  if (!@file_put_contents($path.$file, "{t}You are not allowed to view this folder.{/t}", LOCK_EX)) {
	    sys_die(sprintf("{t}Unable to write to %s{/t}",$path.$file));
} } } }

function dirs_delete_all($path,$olderthan=0,$remove=true) {
  $my_dir = opendir($path);
  while (($file=readdir($my_dir))) {
    if ($file!="." and $file!="..") {
	  if (is_dir($path."/".$file)) {
		dirs_delete_all($path."/".$file,$olderthan);
	  } else {
	    if (file_exists($path."/".$file) and filectime($path."/".$file)+$olderthan < time()) @unlink($path."/".$file);
  } } }
  closedir($my_dir);
  if ($remove) @rmdir($path);
}

function dirs_create_empty_dir($path) {
  if (is_dir($path."/")) dirs_delete_all($path,0,false);
  sys_mkdir($path);
  dirs_create_index_htm($path."/");
}

function dirs_checkdir($path) {
  if (!is_dir($path) and !sys_mkdir($path)) sys_die(sprintf("{t}Failed to create directory: %s{/t}",$path));
  if (!is_writable($path)) sys_die(sprintf("{t}Failed to write into %s{/t}",$path));
  dirs_create_index_htm($path);
}

function ______F_O_L_D_E_R______() {}

function _folder_searchtypes() {
  return array("ftitle"=>"text", "anchor"=>"text", "ftype"=>"text", "folder"=>"id", "parent"=>"id");
}

function _folder_process_folders($rows,&$tree,$level) {
  foreach($rows as $row) {
    if (!empty($_SESSION["folder_states"][$row["id"]])) $row["state"] = "minus"; else $row["state"] = "plus";
	$icon = "";
	$tree_icon = "";
	if (!empty($row["icon"])) $icon = $row["icon"];
	if (!empty($row["anchor"]) and !strpos($row["anchor"],"_")) $tree_icon = "anchor_".$row["anchor"].".png";
	  else $tree_icon = $row["ftype"].".png";

	if ($row["fmountpoint"]!="" and $_SESSION["treetype"]=="folders" and empty($_REQUEST["popup"]) and empty($_REQUEST["onecategory"])) {
	  $url = sys_credentials($row["id"], $row["fmountpoint"]);
	} else $url = array();
	if ($row["fmountpoint"]=="" and $row["ffcount"]==0) $row["state"] = "line";
	$index = $row["id"];
	if (isset($tree[$row["id"]])) $index .= "_2";
	$tree[$index] = array("id"=>$row["id"],"flevel"=>$row["flevel"]+$level,"plus"=>$row["state"],"icon"=>$icon,
	  "tree_icon"=>$tree_icon,"title"=>$row["ftitle"],"count"=>$row["fcount"]>0?$row["fcount"]:"","ftype"=>$row["ftype"],
	  "fmountpoint"=>$row["fmountpoint"],"lft"=>$row["lft"],"rgt"=>$row["rgt"],"fdescription"=>$row["fdescription"]);

	if ($row["state"]!="plus" and !empty($url["path"])) {
	  $next_rows = folder_get_mount_dirs($url["path"], $url["handler"], $row["id"], true);
	  if (is_array($next_rows) and count($next_rows)>0) _folder_process_folders(array_values($next_rows),$tree,$row["flevel"]+1);
	}
  }
}

function folder_get_mount_dirs($mountpoint,$handler,$parent,$recursive) {
  if ($handler=="") return array();
  $lib_handler = "lib_".$handler;
  if (!method_exists($lib_handler,"get_dirs")) {
    $next_rows = array(array("id"=>$mountpoint,"lft"=>1,"rgt"=>2,"flevel"=>0,"ftitle"=>basename($mountpoint),
	  "ftype"=>"sys_nodb_".$handler,"ffcount"=>0));
  } else {
	$next_rows = call_user_func(array($lib_handler,"get_dirs"),$mountpoint,$parent,$recursive);
  }
  if (count($next_rows)>0) {
	ksort($next_rows);
	foreach (array_keys($next_rows) as $nkey) {
	  $next_rows[$nkey] = array_merge(array("fcount"=>"","ffcount"=>1,"fdescription"=>"","mp"=>0),$next_rows[$nkey], array("fmountpoint"=>""));
	  $next_rows[$nkey]["id"] = $handler.":".$parent."/".$next_rows[$nkey]["id"];
	}
  }
  return $next_rows;
}

function folder_in_trash($folder) {
  static $cache = array();
  if (!is_numeric($folder)) return false;
  if (isset($cache[$folder])) return $cache[$folder];
  $cache[$folder] = false;
  $left = db_select_value("simple_sys_tree","lft","id=@id@",array("id"=>$folder));
  if (!empty($left)) {
    $count = db_select_value("simple_sys_tree","count(*) as count",array("anchor=@anchor@","lft<@lft@","rgt>@lft@"),array("anchor"=>"trash","lft"=>$left));
    if (!empty($count)) $cache[$folder] = true;
  }
  return $cache[$folder];
}

// /Workspace/<node>/<node>/...
// node: folder name (first), ~ftype (all), !ftype (in path), ^anchor (first), * (all)
function folder_from_path($path) {
  return sys_array_shift(folders_from_path($path));
}

function folders_from_path($path) {
  if (!in_array($path[0],array("/","^","!","~"))) return array($path);
  $parents = array(0);
  $nodes = explode("/",$path);
  foreach ($nodes as $key=>$node) {
    if ($node=="") continue;
	$limit = "1";
	if ($node[0]=="^") {
	  $where = array("anchor=@node2@",$_SESSION["permission_sql_read"]);
	} else if ($node[0]=="!") {
	  $where = array("ftype=@node2@","parent in (@parents@)",$_SESSION["permission_sql_read"]);
	  $limit = "";
	} else if ($node[0]=="~") {
	  $where = array("ftype=@node2@",$_SESSION["permission_sql_read"]);
	} else if ($node=="*") {
	  $where = array("parent in (@parents@)",$_SESSION["permission_sql_read"]);
	  $limit = "";
	} else {
	  $where = array("ftitle=@node@","parent in (@parents@)",$_SESSION["permission_sql_read"]);
	}
	$vars = array("node"=>$node,"node2"=>substr($node,1),"parents"=>$parents);
    $rows = db_select("simple_sys_tree",array("id","fmountpoint"),$where,"",$limit,$vars);
    if (!is_array($rows) or count($rows)==0) return array(0);
	  
	$parents = array();
	foreach ($rows as $row) {
	  $parents[] = $row["id"];
	  if ($row["fmountpoint"]=="") continue;
	  $mpt = sys_parse_folder($row["fmountpoint"]);
	  if (!empty($nodes[$key+1]) and $nodes[$key+1]==basename($mpt["path"])) {
		return array($mpt["handler"].":".$row["id"]."/".$mpt["path"] . implode("/", array_slice($nodes, $key+2)));
  } } }
  return $parents;
}

function folder_process_session_find($finds) {
  /*
	find short syntax: find[]=table|field=value[,field2=value] => field and field2
	find first asset: find[]=asset|table|limit|field=value[|field2=value2] => field and field2
	find first folder: find[]=folder|simple_tree|limit|field=value
	find folders: find[]=folders|simple_tree|limit|field=value
	
	or: find[]=asset|table|limit|field=value&find[]=asset|table|limit|field=value
	union: find[]=assets|table|limit|field=value&find[]=assets|table|limit|field=value
	limit can be left blank
  */
  if (!is_array($finds) or count($finds)==0) return array();
  if (!empty($_REQUEST["folder"])) $finds[] = "assets|||folder=".$_REQUEST["folder"];

  $table = "";
  $result = array();
  foreach ($finds as $find) {
    if (!strpos($find,"|")) $delim = "¦"; else $delim = "|";
    $find = explode($delim, $find);
	if (count($find)==1 and isset($_SESSION["ftype"])) $find = array($_SESSION["ftype"],$find[0]);
	if (count($find)==2) $find = explode("|","asset|".$find[0]."||".str_replace(",","|",$find[1]));
	if (count($find)<4) return array();
	$mode = $find[0];
	$limit = $find[2];
	if (empty($limit) or $limit > ASSET_PAGE_LIMIT) $limit = ASSET_PAGE_LIMIT;
	if ($find[1]!="") $table = sql_fieldname($find[1]);
	if ($table=="") continue;
	if (!sys_strbegins($table,"simple_")) $table = "simple_".$table;
	$values = array();
	$where = array();
	$find = array_slice($find,3);
	foreach ($find as $val) {
	  $val = str_replace(array(utf8_encode("¦"),"¦"),"|",$val);
	  if (($pos = strpos($val,"="))) {
		$field = sql_fieldname(substr($val,0,$pos));
		$values[$field] = explode(",",substr($val,$pos+1));
		if (count($values[$field])==1) {
		  $where[] = $field."=@".$field."@";
		  if ($field=="folder") $values[$field] = folders_from_path($values[$field][0]);
		} else $where[] = $field." in (@".$field."@)";
	  } else if (($pos = strpos($val,"~"))) {
		$field = sql_fieldname(substr($val,0,$pos));
		$values[$field] = "%".substr($val,$pos+1)."%";
		$where[] = $field." like @".$field."@";
	  } else {
		$values["id"] = explode(",",$val);
		$where[] = "id in (@id@)";
	  }
	}
	$rows = db_select($table,array("folder","id"),$where,"",(is_numeric($limit)?$limit:""),$values);
	if (!empty($rows) and is_array($rows) and count($rows)>0) {
	  if (($mode=="asset" or $mode=="folder") and count($rows)==1) {
		$result["folder"] = $rows[0]["folder"];
		if ($mode=="asset") $result["item"] = $rows[0]["id"];
	  } else {
		foreach ($rows as $row) {
		  $result["folders"][] = $row["folder"];
		  if (!in_array($mode,array("folder", "folders"))) $result["item"][] = $row["id"];
		}
		if (empty($result["folder"])) {
		  foreach ($rows as $row) {
		    if (!db_get_right($row["folder"],"read")) continue;
			$result["folder"] = $row["folder"];
			break;
	  } } }
	  if ($mode!="assets") break; // OR asset|folder
  } }
  return $result;
}

function folder_process_session_request() {
  if (!empty($_REQUEST["find"])) {
	unset($_REQUEST["item"]);
	unset($_REQUEST["folders"]);
    $result = folder_process_session_find((array)$_REQUEST["find"]);
	$_REQUEST = array_merge($_REQUEST, $result);
	
	if (empty($result) and empty($_REQUEST["iframe"])) {
	  $params = implode(", ", (array)$_REQUEST["find"]);
	  $params = str_replace(array("|", utf8_encode("¦"), "¦", "~"),array(", ", "", "", " like "), $params);
	  sys_warning("{t}Item not found.{/t} {t}Parameters{/t}: ".$params, true);
  } }

  if (isset($_REQUEST["fschema"])) {
    $row = db_select_first("simple_sys_tree","id",array("ftype=@ftype@",$_SESSION["permission_sql_read"]),"lft asc",array("ftype"=>str_replace("simple_","",$_REQUEST["fschema"])));
	if (!isset($row["id"])) {
	  sys_warning("{t}Item not found.{/t} (".$_REQUEST["fschema"].")");
	} else $_REQUEST["folder"] = $row["id"];
  }
  if (!empty($_REQUEST["folder2"]) and !empty($_REQUEST["view2"]) and empty($_REQUEST["folder"])) {
    if (!isset($_REQUEST["folder"])) $_REQUEST["folder"] = $_REQUEST["folder2"];
    if (!isset($_REQUEST["view"])) $_REQUEST["view"] = $_REQUEST["view2"];
  }
  if (!isset($_SESSION["treetype"])) $_SESSION["treetype"] = "folders";
  if (!empty($_REQUEST["treetype"])) {
    if (isset($_REQUEST["onecategory"])) unset($_REQUEST["onecategory"]);
	$_SESSION["treetype"] = $_REQUEST["treetype"];
  }

  if (!isset($_SESSION["treevisible"])) $_SESSION["treevisible"] = true;
  if (!isset($_SESSION["hidedata"])) $_SESSION["hidedata"] = false;

  if (isset($_REQUEST["tree"])) {
    if ($_REQUEST["tree"]=="minimize") $_SESSION["treevisible"] = false;
    if ($_REQUEST["tree"]=="maximize") $_SESSION["treevisible"] = true;
  }
  if (isset($_REQUEST["hidedata"])) $_SESSION["hidedata"]=!$_SESSION["hidedata"];

  if (!empty($_REQUEST["folder"])) {
	$folders = folders_from_path($_REQUEST["folder"]);
	$_SESSION["folder"] = $folders[0];
	if (count($folders)>1) $_REQUEST["folders"] = $folders;
  }

  if (isset($_REQUEST["tree"]) and $_REQUEST["tree"]=="closeall") $_SESSION["folder_states"] = array();
  if (!empty($_REQUEST["view"]) and !empty($_SESSION["folder"])) $_SESSION["view"]["_".$_SESSION["folder"]] = $_REQUEST["view"];

  if (!empty($_REQUEST["item"]) and count($_REQUEST["item"])==1 and isset($_REQUEST["item"][0]) and $_REQUEST["item"][0]==0) unset($_REQUEST["item"]);
}

function folder_build_selfolder($tfolder,$tview) {
  if (!is_numeric($tfolder)) {
    $url = sys_parse_folder($tfolder);
	$handler = $url["handler"];
	$mfolder = $url["mfolder"];
	$level = substr_count($url["path"], "/") - 1;
	$sel_folder = db_select_first("simple_sys_tree",array("fmountpoint","flevel"),array("id=@id@",$_SESSION["permission_sql_read"]),"",array("id"=>(int)$mfolder));
	if (!empty($sel_folder["flevel"])) {
	  $level += $sel_folder["flevel"] - substr_count($sel_folder["fmountpoint"], "/") + 1;
	  $mp = $sel_folder["fmountpoint"];
	} else $mp = "";
    $sel_folder = array("id"=>$tfolder, "ftitle"=>basename($tfolder), "fdescription"=>"", "children"=>array(),
	  "lft"=>1, "rgt"=>2, "ftype"=>"sys_nodb_".$handler, "fcount"=>0, "fsizecount"=>0, "fchcount"=>0, "fchsizecount"=>0,
	  "ffcount"=>0, "flevel"=>$level, "quota"=>0, "anchor"=>"", "folders"=>"", "icon"=>"", "notification"=>"",
	  "fmountpoint"=>$mp, "rights"=>db_get_rights($mfolder,$tview));
  } else {
    if (db_get_right($tfolder,"read")) {
      $sel_folder = db_select_first("simple_sys_tree","*",array("id=@id@"),"",array("id"=>$tfolder));
	} else {
	  $sel_folder = db_select_first("simple_sys_tree","*",$_SESSION["permission_sql_read"],"lft asc");
      if (DEBUG and empty($sel_folder)) {
	    folders::create_default_folders("modules/core/folders.xml",0,true);
		sys_die("tree created.");
	  }
	  if (!empty($_REQUEST["iframe"]) and $tfolder!=1) sys_die("{t}Access denied.{/t}");
    }
	if (!is_array($sel_folder)) sys_die("ERROR ".sql_error());
	if (count($sel_folder)==0) sys_die("{t}Access denied.{/t} <a href='index.php?logout'>{t}Login/-out{/t}</a>");
    $sel_folder["quota"] = db_get_quota($sel_folder);
    $sel_folder["rights"] = db_get_rights($sel_folder["id"],$tview);
  }
  return $sel_folder;
}

function folder_get_default_values($folder) {
  if (!is_numeric($folder)) {
	$vars = sys_parse_folder($folder);
	$handler = "lib_".$vars["handler"];
	return call_user_func(array($handler,"default_values"),$vars["mountpoint"],$vars["mfolder"]);
  }
  $row = db_select_first("simple_sys_tree",array("lft","rgt"),array("id=@id@"),"",array("id"=>$folder));
  if (empty($row["lft"])) return array();
  $row = db_select_first("simple_sys_tree", "default_values", array("lft<=@lft@", "rgt>=@rgt@", "default_values!=''"), "lft desc", $row);
  if (empty($row["default_values"])) return array();
  $values = array();
  foreach (explode("\n",$row["default_values"]) as $line) {
	$line = explode("=",trim($line),2);
	if (count($line)!=2) continue;
	$values[$line[0]] = $line[1];
  }
  return $values;
}

function folder_build_folders() {
  if (empty($_SESSION["folder"])) $tfolder = 1; else $tfolder = $_SESSION["folder"];
  if (empty($_SESSION["view"]["_".$tfolder])) $tview = "none"; else $tview = $_SESSION["view"]["_".$tfolder];

  $sel_folder = folder_build_selfolder($tfolder,$tview);
  $sel_folder["parents"] = db_get_parents($sel_folder);
  if (count($sel_folder["parents"])>0 and !isset($_REQUEST["onecategory"]) and $_SESSION["treetype"]=="folders") {
	foreach ($sel_folder["parents"] as $parent) {
	  $id = $parent["id"];
	  if (!isset($_SESSION["folder_states"][$id]) or !in_array($sel_folder["id"],$_SESSION["folder_states"][$id])) {
	    $_SESSION["folder_states"][$id][] = $sel_folder["id"];
	  }
	}
	if (!isset($_SESSION["folder_states"][$sel_folder["id"]])) {
      $_SESSION["folder_states"][$sel_folder["id"]] = array(1);
	}
  }

  if (!empty($_REQUEST["folder"]) and empty($_REQUEST["iframe"]) and empty($_REQUEST["preview"])) {
	if ($tview=="none") $v = ""; else $v = $tview;
	if (isset($_SESSION["history"][$tfolder.$v])) unset($_SESSION["history"][$tfolder.$v]);
	$last_parent = end($sel_folder["parents"]);
	if (!empty($last_parent["ftitle"])) $last_parent = $last_parent["ftitle"]." / ";
	$_SESSION["history"][$tfolder.$v] = array($last_parent.$sel_folder["ftitle"], $tfolder, $v);
	if (count($_SESSION["history"])>15) array_shift($_SESSION["history"]);
  }

  $sel_folder["children"] = db_get_children($sel_folder);
  $ftype = $sel_folder["ftype"];
  $tfolder = $sel_folder["id"];
  
  if (isset($_SESSION["disabled_modules"][$ftype])) {
	sys_warning("{t}Module disabled.{/t}");
	$ftype = "blank";
  }
  $GLOBALS["schemafile"] = sys_find_module($ftype);
  $GLOBALS["tname"] = $ftype;
  $GLOBALS["tquota"] = $sel_folder["quota"];
  $GLOBALS["tfolder"] = $tfolder;

  $tfolders = array($tfolder);
  if (!empty($_REQUEST["folders"])) {
    $tfolders = (array)$_REQUEST["folders"];
  } else if ($sel_folder["folders"]!="") {
	$tfolders = explode("|",trim($sel_folder["folders"],"|"))	;
  }
  $tfolders = _build_merge_folders(array_values($tfolders), $tfolder, $tview);

  $GLOBALS["tfolders"] = $tfolders;
  $GLOBALS["sel_folder"] = $sel_folder;
  $GLOBALS["tview"] = $tview;

  $_SESSION["folder"] = $tfolder;
  $_SESSION["ftype"] = $ftype;

  if (isset($_REQUEST["popup"])) {
	folder_build_tree(false);
  } else if (defined("NOCONTENT") or !$_SESSION["treevisible"] or isset($_REQUEST["iframe"]) or isset($_REQUEST["preview"]) or isset($_REQUEST["export"])) {
    sys::$smarty->assign("tree",array("visible"=>false));
  } else folder_build_tree($_SESSION["treevisible"]);

  sys::$smarty->assign("folder",array("id"=>$GLOBALS["tfolder"],"name"=>$sel_folder["ftitle"],"description"=>$sel_folder["fdescription"],
  	"mountpoint"=>$sel_folder["fmountpoint"],"type"=>$sel_folder["ftype"],"parents"=>$sel_folder["parents"],"children"=>$sel_folder["children"]));
	
  sys::$smarty->assign("sys",array(
    "app_title"=>APP_TITLE,
	"version"=>CORE_VERSION,
	"version_str"=>CORE_VERSION_STRING,
	"session_time"=>LOGIN_TIMEOUT,
	"folder_refresh"=>FOLDER_REFRESH,
	"menu_autohide"=>(int)MENU_AUTOHIDE,
	"tree_autohide"=>(int)TREE_AUTOHIDE,
	"fixed_footer"=>(int)FIXED_FOOTER,
	"fdesc_in_content"=>(int)FDESC_IN_CONTENT,
	"mountpoint_admin"=>(int)MOUNTPOINT_REQUIRE_ADMIN,
	"is_superadmin"=>(int)sys_is_super_admin($_SESSION["username"]),
	"username"=>$_SESSION["username"],
	"home"=>$_SESSION["home_folder"],
	"history"=>$_SESSION["history"],
	"disabled_modules"=>$_SESSION["disabled_modules"],
	"browser"=>sys::$browser,
  ));
}

function folder_build_tree($visible) {
  $vars = array();
  $sel_folder = $GLOBALS["sel_folder"];
  $schemas = select::modules();
  if ($_SESSION["treetype"]=="folders" and empty($_REQUEST["popup"]) and empty($_REQUEST["onecategory"])) {
    $where = array("(flevel<2 or parent in (@states@))",$_SESSION["permission_sql_read"]);
    $vars["states"] = array_filter(array_keys($_SESSION["folder_states"]),"is_numeric");
	if (count($vars["states"])==0) $vars["states"] = 0;
  } else {
    $where = array("ftype=@type@",$_SESSION["permission_sql_read"]);
	if (isset($schemas[$sel_folder["ftype"]])) {
      $vars["type"] = $sel_folder["ftype"];
	} else $vars["type"] = "blank";
  }
  $treelimit = 100;
  $treepage = 1;
  $tree_count = db_count("simple_sys_tree",$where,$vars);
  $last_treepage = ceil($tree_count/$treelimit);
  if ($last_treepage<1) $last_treepage = 1;

  if (isset($_REQUEST["treepage"])) $_SESSION["treepage"] = $_REQUEST["treepage"];
  if (isset($_SESSION["treepage"]) and $_SESSION["treepage"] > $last_treepage) $_SESSION["treepage"] = $last_treepage;
  if (isset($_SESSION["treepage"]) and is_numeric($_SESSION["treepage"]) and $_SESSION["treepage"]>0) {
    $treepage = $_SESSION["treepage"];
  }

  $tree = array();
  $limit = array(($treepage-1)*$treelimit,$treelimit);
  $rows = db_select("simple_sys_tree",array("*"),$where,"lft asc",$limit,$vars);

  if (is_array($rows) and count($rows)>0) {
    _folder_process_folders($rows,$tree,0);
  }
  $prev_treepage = ($treepage<=1?1:$treepage-1);
  $next_treepage = ($treepage==$last_treepage?$last_treepage:$treepage+1);

  sys::$smarty->assign("sys_schemas",$schemas);
  sys::$smarty->assign("tree",array(
  	"tree"=>$tree, "type"=>$_SESSION["treetype"], "visible"=>$visible,
	"page"=>$treepage, "lastpage"=>$last_treepage,"prevpage"=>$prev_treepage, "nextpage"=>$next_treepage,
  ));
}

function _build_merge_folders($tfolders, $tfolder, $view, $write=false) {
  $folders = array();
  $tfolders[] = $tfolder;
  $tfolders = array_values(array_unique($tfolders));

  if (count($tfolders)>1 or $tfolders[0]!=$tfolder) {
    $colors = array("#DDDDFF","#CCFFCC","#FFDDFF","#FFDDAA","#FFCCCC","#CCFFFF","#FFFFAA","#CCCCCC","#FFFFFF","#AAAAFF",
  				  "#99FF99","#FF99FF","#FFAA33","#FFAAAA","#6699FF","#CCCC00","#999999","#00CC66","#CC9933","#CC6600");

    foreach ($tfolders as $key=>$folder) {
	  if (empty($folder) or !db_get_right($folder, $write ? "write" : "read", $view)) continue;
	  if (!isset($colors[$key])) $colors[$key] = "";
	  $folders[$folder] = array($folder, $colors[$key]);
 	}
  } else $folders[$tfolder] = array($tfolder,"");

  return $folders;
}

function ______L_O_G_I_N______() {}

function _login_get_serverid() {
  $serverid = db_select_value("simple_sys_hosts","serverid","hostip=@hostip@",array("hostip"=>$_SERVER["SERVER_ADDR"]));
  if (empty($serverid)) {
    $serverid = sql_genID("simple_sys_hosts");
    db_insert("simple_sys_hosts",array("serverid"=>$serverid, "hostip"=>$_SERVER["SERVER_ADDR"]));
  }
  return $serverid;
}

function _login_get_remoteaddr() {
  if (isset($_SERVER["HTTP_CLIENT_IP"])) $ip = $_SERVER["HTTP_CLIENT_IP"];
    else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    else if (isset($_SERVER["REMOTE_ADDR"])) $ip = $_SERVER["REMOTE_ADDR"];
    else $ip = "0.0.0.0";

  return filter_var($ip, FILTER_VALIDATE_IP);
}

function _login_session_none() {
  return true;
}

function _login_session_read($id) {
  if (empty($id)) return "";
  if (APC_SESSION) return apc_fetch("sess".$id);
  $data = db_select_value("simple_sys_session","data",array("id=@id@","expiry>=@now@"),array("id"=>$id,"now"=>NOW));
  if (empty($data)) return "";
  return rawurldecode($data);
}

function _login_session_write($id,$val) {
  if (empty($_SESSION["username"]) or defined("NOSESSION")) return true;
  if (APC_SESSION) return apc_store("sess".$id, $val, LOGIN_TIMEOUT);
  $data = array("username"=>$_SESSION["username"], "expiry"=>NOW+LOGIN_TIMEOUT, "data"=>rawurlencode($val));
  db_update("simple_sys_session",$data,array("id=@id@"),array("id"=>$id));
  return true;
}

function _login_session_destroy($id) {
  if (APC_SESSION) return apc_delete("sess".$id);
  if ($_SESSION["username"]=="anonymous" or isset($_REQUEST["session_clean"])) {
	db_delete("simple_sys_session",array("id=@id@"),array("id"=>$id));
  } else {
    db_update("simple_sys_session",array("expiry"=>NOW-10),array("id=@id@"),array("id"=>$id));
  }
  return true;
}

function login_anonymous_session() {
  $_SESSION["serverid"] = _login_get_serverid();
  $_SESSION["username"] = "anonymous";
  $_SESSION["password"] = "";
  if (!isset($_SESSION["history"])) $_SESSION["history"] = array();
  $_SESSION["groups"] = array();
  $_SESSION["permission_sql"] = "r@right@_users like '%|anonymous|%'";
  $_SESSION["permission_sql_read"] = "rread_users like '%|anonymous|%'";
  $_SESSION["permission_sql_write"] = "rwrite_users like '%|anonymous|%'";
  $_SESSION["permission_sql_exception"] = "(rexception_users!='' and ".sql_regexp("rexception_users","anonymous","|@view@:@right@:%s|").")";
  $_SESSION["disabled_modules"] = array_flip(explode("|", DISABLED_MODULES));
  $_SESSION["folder_states"] = array();
  $_SESSION["day_begin"] = 25200; // 7:00 = 7*3600
  $_SESSION["day_end"] = 64800; // 18:00 = 18*3600
  $_SESSION["home_folder"] = "";
  $_SESSION["treevisible"] = true;
  $_SESSION["ip"] = _login_get_remoteaddr();
  $_SESSION["tickets"] = array("templates" => array("dbselect", "simple_templates", array("tplcontent","tplname"), array("tplname like @search@"),"tplname asc"));
  $base = dirname($_SERVER["SCRIPT_FILENAME"])."/";
  $_SESSION["ALLOWED_PATH"] = array( $base.SIMPLE_CACHE."/preview/" );
  foreach (explode(",",SIMPLE_IMPORT) as $folder) {
    if ($folder=="" or !is_dir($folder)) continue;
	if ($folder[0]!="/" and !strpos($folder,":")) $folder = $base.$folder;
	$_SESSION["ALLOWED_PATH"][] = rtrim(str_replace("\\","/",$folder),"/")."/";
  }
  if (!APC_SESSION and ($id = session_id()) and !db_count("simple_sys_session",array("id=@id@"),array("id"=>$id))) {
    db_insert("simple_sys_session",array("expiry"=>NOW+LOGIN_TIMEOUT,"id"=>$id));
  }
}

function login_handle_login($save_session=true) {
  session_set_cookie_params(2592000); // 1 month
  session_name(SESSION_NAME);
  if (empty($_REQUEST["iframe"]) and empty($_REQUEST["export"]) and empty($_REQUEST["import"]) and !isset($_REQUEST["plain"]) and $save_session) {
    session_set_save_handler("_login_session_none","_login_session_none","_login_session_read","_login_session_write","_login_session_destroy","_login_session_none");
	register_shutdown_function("session_write_close");
  } else {
    session_set_save_handler("_login_session_none","_login_session_none","_login_session_read","_login_session_none","_login_session_none","_login_session_none");
  }
  session_start();
  header("Cache-Control: private, max-age=1, must-revalidate");
  header("Pragma: private");
  
  if (!empty($_COOKIE[SESSION_NAME]) and empty($_SESSION)) session_regenerate_id();
  if (!empty($_SESSION["timezone"])) date_default_timezone_set($_SESSION["timezone"]);

  if (file_exists(SIMPLE_STORE."/maintenance.lck")) $maintenance = true; else $maintenance = false;

  if (!DISABLE_BASIC_AUTH and empty($_SESSION["username"]) and !empty($_SERVER["PHP_AUTH_USER"]) and !empty($_SERVER["PHP_AUTH_PW"])) {
	$_REQUEST["username"] = modify::strip_ntdomain($_SERVER["PHP_AUTH_USER"]);
	$_REQUEST["password"] = $_SERVER["PHP_AUTH_PW"];
  }
  $ip = _login_get_remoteaddr();
  if (!empty($_REQUEST["username"]) and !empty($_REQUEST["password"]) and (!$maintenance or sys_is_super_admin($_REQUEST["username"]))) {
    if (!isset($_COOKIE[SESSION_NAME]) and !empty($_REQUEST["loginform"])) sys_die('{t}Please activate cookies.{/t} <a href="index.php?logout">{t}Back{/t}</a>');

	$file = SIMPLE_CACHE."/ip/".str_replace(".","-",$ip);
	if (file_exists($file."_3") and $trials = file_get_contents($file."_3") and strlen($trials)>3 and filemtime($file."_3") > time()-900) {
	  $_REQUEST["logout"] = true;
	  sys_alert("{t}Too many wrong logins. Please wait 15 minutes.{/t}");
	} else if (login::validate_login($_REQUEST["username"],$_REQUEST["password"])) {
	  login::process_login($_REQUEST["username"],$_REQUEST["password"]);
  	} else {
	  touch($file,time()+3);
	  $_REQUEST["logout"] = true;
	  if (file_exists($file."_3") and filemtime($file."_3") < time()-1800) unlink($file."_3");
	  sys_file_append($file."_3","1");
	  sys_log_stat("wrong_login",1);
	}
  }
  if (!isset($_REQUEST["logout"]) and empty($_SESSION["username"]) and SETUP_AUTH=="ntlm" and SETUP_AUTH_NTLM_SSO) {
	if (login::validate_login("_invalid","")) login::process_login($_SERVER["REMOTE_USER"]);
  }
  if (!isset($_REQUEST["logout"]) and empty($_SESSION["username"]) and SETUP_AUTH=="htaccess" and !empty($_SERVER["REMOTE_USER"])) {
    $_SERVER["REMOTE_USER"] = modify::strip_ntdomain($_SERVER["REMOTE_USER"]);
	if (login::validate_login($_SERVER["REMOTE_USER"],"")) login::process_login($_SERVER["REMOTE_USER"]);
  }
  if ($maintenance and (empty($_SESSION["username"]) or !sys_is_super_admin($_SESSION["username"]))) {
	$_REQUEST["logout"] = true;
	sys_alert("{t}Maintenance mode{/t}: {t}Active{/t}.");
  }
  if (empty($_SESSION["username"]) and ENABLE_ANONYMOUS) login_anonymous_session();
  if (empty($_SESSION["username"]) and ENABLE_ANONYMOUS_CMS and MAIN_SCRIPT=="download.php") login_anonymous_session();
  if (isset($_REQUEST["logout"]) or (empty($_SESSION["username"]) and !ENABLE_ANONYMOUS) or
  	 (isset($_SESSION["ip"]) and $_SESSION["ip"]!=$ip and $ip!=$_SERVER["SERVER_ADDR"])) {
	login::show_login();
  }
}

function login_browser_detect() {
  $agent = "@".strtolower($_SERVER["HTTP_USER_AGENT"]);
  $version = array();
  sys::$browser["name"] = $agent;
  if (
  	(strpos($agent,"firefox") and preg_match("|(firefox)/([0-9]+\.[0-9])|", $agent,$version)) or
	(strpos($agent,"opera") and preg_match("|(opera).?([0-9]+\.[0-9])|", $agent,$version)) or
	(strpos($agent,"msie") and preg_match("|(msie) ([0-9]+\.[0-9])|", $agent,$version)) or
	(strpos($agent,"applewebkit") and preg_match("|(applewebkit)/([0-9]+)|", $agent,$version)) or
	(strpos($agent,"konqueror") and preg_match("|(konqueror).?([0-9]\.[0-9])|", $agent,$version)) or
	(strpos($agent,"thunderbird") and preg_match("|(thunderbird)/([0-9]+\.[0-9])|", $agent,$version)) or
	(strpos($agent,"miniredir") and preg_match("|(miniredir)/([0-9]\.[0-9])|", $agent,$version)) or
	(strpos($agent,"httpclient") and preg_match("|(httpclient)/([0-9]\.[0-9])|", $agent,$version)) or
	(strpos($agent,"curl") and preg_match("|(curl)/([0-9]+\.[0-9])|", $agent,$version)) or
	(strpos($agent,"apachebench") and preg_match("|(apachebench)/([0-9]\.[0-9])|", $agent,$version))
  ) {
    sys::$browser["name"] = $version[1];
	sys::$browser["ver"] = $version[2];
  } else if (strpos($agent,"mozilla") and preg_match("|rv:([0-9]\.[0-9]).*?gecko|", $agent,$version)) {
    sys::$browser["name"] = "mozilla";
	sys::$browser["ver"] = $version[1];
  } else if (preg_match("/googlebot|msnbot|yahoo|baidu|teoma/", $agent)) {
    sys::$browser["name"] = "search-engine";
	sys::$browser["ver"] = 20;
  }
  if (sys::$browser["name"]=="applewebkit") sys::$browser["name"] = "safari";
  sys::$browser["ver"] = str_replace(".","",sys::$browser["ver"]);
  if (strlen(sys::$browser["ver"])<2) sys::$browser["ver"] .= "00";
  sys::$browser["str"] = ucfirst(sys::$browser["name"])." ".sys::$browser["ver"];

  if (strpos($agent,"windows ce")) sys::$browser["platform"] = "wince";
    else if (strpos($agent,"windows")) sys::$browser["platform"] = "win";
    else if (strpos($agent,"macintosh")) sys::$browser["platform"] = "mac";
    else if (strpos($agent,"linux")) sys::$browser["platform"] = "linux";

  switch (sys::$browser["name"]) {
    case "firefox": case "msie": case "mozilla":
	  sys::$browser["comp"]["codeedit"] = true;
	  break;
	case "thunderbird":
	  $_REQUEST["iframe"] = 1;
	  sys::$browser["comp"]["htmledit"] = false;
	  sys::$browser["comp"]["javascript"] = false;
	  break;
  }
  if (preg_match("/iphone|nokia|samsung|android|webos|windows ce|symbian|midp/",$agent) &&
	  !preg_match("/gt-p1000|archos/",$agent)) {
	sys::$browser["is_mobile"] = true;
	$_REQUEST["tree"]="minimize";
  }
  if (preg_match("/iphone|ipad|android/",$agent)) sys::$browser["no_scrollbar"] = true;
  $min = array(
	"firefox"=>10, "msie"=>60, "mozilla"=>14, "search-engine"=>20, "opera"=>90, "safari"=>400,
	"konqueror"=>32, "thunderbird"=>15, "httpclient"=>30, "curl"=>60, "miniredir"=>51, "apachebench"=>20,
  );
  if (isset($min[sys::$browser["name"]]) and sys::$browser["ver"]>=$min[sys::$browser["name"]]) {
    return true;
  }
  return false;
}

function ______S_Q_L______() {}

function sql_explain($sql) {
  $result = "";
  $rows = sql_fetch("explain ".$sql);
  if (is_array($rows) and count($rows)>0) {
    foreach ($rows as $row) $result .= implode(" ",$row)."\n";
  }
  return $result;
}

function sql_concat($sql) {
  if (is_array($sql)) {
    foreach ($sql as $key=>$val) $sql[$key] = sql_concat($val);
	return $sql;
  }
  if (SETUP_DB_TYPE=="mysql") {
    return str_replace(";",",",$sql);
  } else { // pgsql, sqlite
    return str_replace(array(";;",";","concat"),array(",","||",""),$sql);
  }
}

function sql_regexp($col,$exps,$format="|%s|") {
  $reg = array();
  foreach ((array)$exps as $exp) $reg[] = preg_quote($exp);
  $reg = sql_quote(sprintf(preg_quote($format), "(".implode("|",$reg).")"));

  if (SETUP_DB_TYPE=="mysql") return $col." REGEXP '".$reg."'";
    else if (SETUP_DB_TYPE=="pgsql") return $col." ~ '".$reg."'";
    else return "REGEXP_LIKE(".$col.",'".$reg."')"; // sqlite
}

function sql_table_optimize($tab="") {
  if (SETUP_DB_TYPE=="mysql") {
    if ($tab=="") $data = sql_fetch("show tables like 'simple_%'"); else $data = array(array($tab));
    foreach ($data as $elem) {
      $table = array_pop($elem);
      if (!sys_strbegins($table,"simple_")) continue;
	  if ($table=="simple_sys_tree") sql_query("alter table simple_sys_tree order by lft");
      if (!sql_query(sprintf("check table %s",$table)) or
	  	  !sql_query(sprintf("analyze table %s",$table)) or
	  	  !sql_query(sprintf("optimize table %s",$table))) return false;
    }
  } else if (SETUP_DB_TYPE=="sqlite") {
    if (!sql_query("PRAGMA integrity_check; vacuum; analyze")) return false;
  } else if (SETUP_DB_TYPE=="pgsql") {
    if ($tab=="") {
	  $data = sql_fetch("select table_name from information_schema.tables where table_name like 'simple_%'");
	} else $data = array(array($tab));
    foreach ($data as $elem) {
      $table = array_pop($elem);
      if (!sql_query(sprintf("vacuum full analyze %s",$table))) return false;
    }
  }
  return true;
}

function sql_table_create($table) {
  if (SETUP_DB_TYPE=="mysql") {
    $sql = "CREATE TABLE %s (id NUMERIC(10) DEFAULT 0) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    if (sql_query(sprintf($sql,$table))) return true;
  } else { // pgsql / sqlite
    if (sql_query(sprintf("CREATE TABLE %s (id NUMERIC(10) DEFAULT 0)",$table))) return true;
  }
  return false;
}

function sql_limit($sql,$param1,$param2) {
  if (SETUP_DB_TYPE=="mysql") {
    if (sys_strbegins($sql,"show")) return $sql;
    return sprintf("%s limit %d,%d",$sql,$param1,$param2);
  } else {
    return sprintf("%s limit %d offset %d",$sql,$param2,$param1);
  }
}

function _sql_sqlite_match($str, $regex) {
  if ($str=="") return false;
  if (preg_match("!".$regex."!i", $str)) return true;
  return false;
}

function _sql_sqlite_shutdown() {
  session_write_close();
  if (!defined("NOSESSION")) define("NOSESSION",true);
}

function sql_connect($server,$username,$password,$database="") {
  if (SETUP_DB_TYPE=="mysql") {
    if (!$link = mysql_pconnect($server,$username,$password)) return false;
    if ($database!="" and !mysql_select_db($database,$link)) return false;
	sys::$db = $link;
    if (!sql_query("set names 'utf8', sql_mode = ''")) return false;
  } else if (SETUP_DB_TYPE=="pgsql") {
    $conn = "host='".addslashes($server)."' user='".addslashes($username)."' password='".addslashes($password)."'";
	if ($database!="") $conn .= " dbname='".addslashes($database)."'";
    if (!$link = pg_pconnect($conn)) return false;
	sys::$db = $link;
  } else if (SETUP_DB_TYPE=="sqlite") {
    try {
      $link = new PDO("sqlite:".SIMPLE_STORE."/sqlite3_".urlencode($database).".db","","",array(PDO::ATTR_PERSISTENT=>false));
	  $link->sqliteCreateFunction("REGEXP_LIKE", "_sql_sqlite_match", 2);
	  sys::$db = $link;
	}
	catch(Exception $e) {
	  sys::$db_error = $e->getMessage();
	  return false;
	}
    if (!defined("NOSESSION")) register_shutdown_function("_sql_sqlite_shutdown");
  }
  return true;
}

function sql_translate($sql) {
  if (SETUP_DB_TYPE=="pgsql") {
	if (strpos($sql," match ")) {
	  return str_replace(
		array("match (s.sindex) against (@search@ in boolean mode)", "match (s.sindex) against (@search@)"),
		array("s.sindex_fti @@ to_tsquery(@search@)", "rank(s.sindex_fti,to_tsquery(@search@))"),$sql);
	}
    if ($sql=="show full columns from @table@") return "select * from show_full_columns where table_name='@table@'";
    if ($sql=="show table status") return "select * from show_table_status";
    if ($sql=="show index from @table@") return "select * from show_index where tablename='@table@'";
    if ($sql=="show processlist") return "select * from show_processlist";
	if ($sql=="show status") return "select datname as \"Variable_name\",pg_size_pretty(pg_database_size(datname)) as \"Value\" from pg_database";
    if ($sql=="show variables") return "select name as \"Variable_name\",setting as \"Value\" from pg_settings";
  } else if (SETUP_DB_TYPE=="sqlite") {
	if (strpos($sql," match ")) {
	  return str_replace(array("match (s.sindex) against (@search@ in boolean mode)", "match (s.sindex) against (@search@)"),array("1=0","0"),$sql);
	}
    if ($sql=="show table status") return "select name as Name, '' as Rows, '' as Avg_row_length, '' as Data_length, '' as Index_length, '' as Data_free, ".
										  "'' as Create_time, '' as Update_time, '' as Check_time from sqlite_master order by name";
    if (in_array($sql,array("show processlist","show status","show variables","show full columns from @table@","show index from @table@"))) {
	  return "select 0";
	}
  }
  return $sql;
}

function sql_query($sql) {
  if ($sql=="") return true;
  if (DEBUG_SQL and !sys_strbegins($sql,"select ") and !strpos($sql,"simple_sys_stats")) debug_sql("INFO ".$sql,"");
  if (SETUP_DB_TYPE=="mysql") {
    return mysql_query($sql,sys::$db);
  } else if (SETUP_DB_TYPE=="pgsql") {
    return @pg_query(sys::$db,$sql);
  } else if (SETUP_DB_TYPE=="sqlite") {
    return sys::$db->query($sql);
  }
  return true;
}

function sql_fetch($sql,$one=false) {
  $data = array();
  if ($sql=="") return true;
  if (!$result = sql_query($sql)) return false;
  if (SETUP_DB_TYPE=="mysql") {
    if (@mysql_num_rows($result)==0) return array();
    while (($row = mysql_fetch_assoc($result))) $data[] = $row;
    mysql_free_result($result);
  } else if (SETUP_DB_TYPE=="pgsql") {
    if (pg_num_rows($result)==0) return array();
    while (($row = pg_fetch_assoc($result))) $data[] = $row;
    pg_free_result($result);
  } else if (SETUP_DB_TYPE=="sqlite") {
	$data = $result->fetchAll(PDO::FETCH_ASSOC);
	if (count($data)==0) return array();
  }
  if ($one) $data = $data[0];
  return $data;
}

function sql_error() {
  if (SETUP_DB_TYPE=="mysql") {
    if (!empty(sys::$db)) return mysql_errno(sys::$db)." ".mysql_error(sys::$db);
	return mysql_errno()." ".mysql_error();
  } else if (SETUP_DB_TYPE=="pgsql") {
    if (!empty(sys::$db)) return pg_last_error(sys::$db);
	return @pg_last_error();
  } else if (SETUP_DB_TYPE=="sqlite") {
    if (!empty(sys::$db)) return implode(" ",sys::$db->errorInfo());
  	return sys::$db_error;
  }
  return "";
}

function sql_fieldname($field,$blank=false) {
  if ($blank) return preg_replace("/[^a-zA-Z0-9_\., =]*/","",$field);
  return preg_replace("/[^a-zA-Z0-9_]*/","",$field);
}

function sql_quote($str) {
  if (SETUP_DB_TYPE=="mysql") {
    return mysql_real_escape_string($str,sys::$db);
  } else if (SETUP_DB_TYPE=="pgsql") {
    return pg_escape_string($str);
  } else if (SETUP_DB_TYPE=="sqlite") {
    return trim(sys::$db->quote($str),"'");
  }
  return $str;
}

function sql_genID($table) {
  if (strpos($table,"_nodb_")) return 0;
  $table = "simple_seq_".$table;
  if (SETUP_DB_TYPE=="mysql") {
    $next = sprintf("update %s set id=last_insert_id(id+1)",$table);
    if (!sql_query($next)) {
	  sql_query(sprintf("create table %s (id numeric(10) default 0) ENGINE=MyISAM",$table));
	  sql_query(sprintf("insert into %s values (0)",$table));
	  sql_query($next);
    }
    return mysql_insert_id(sys::$db);
  } else if (SETUP_DB_TYPE=="sqlite") {
	if (!sql_query(sprintf("insert into %s values (null); delete from %s",$table,$table))) {
	  sql_query(sprintf("create table %s (id integer primary key)",$table));
	  sql_query(sprintf("insert into %s values (null)",$table));
	}
	return sys::$db->lastInsertId();
  } else if (SETUP_DB_TYPE=="pgsql") {
    $next = sprintf("select nextval('%s')",$table);
    if (!$id = sql_fetch_one($next)) {
	  sql_query(sprintf("create sequence %s",$table));
	  $id = sql_fetch_one($next);
    }
    return $id["nextval"];
  }
  return 1;
}

function sql_fetch_one($sql) {
  return sql_fetch($sql,true);
}

function ______S_Y_S______() {}

function _sys_remove_handler($var) {
  $pos = strpos($var,":");
  $pos2 = strpos($var,"/");
  if ($pos>1 and $pos < $pos2) $var = substr($var,$pos2+1);
  return $var;
}

function _sys_request_uri() {
    // Yen modified
    return $_SERVER["SCRIPT_NAME"];
  //return $_SERVER["SCRIPT_NAME"]."?".$_SERVER["QUERY_STRING"];
}

// Encryption by Ari Kuorikoski (ari.kuorikoski § finebyte.com)
function _sys_keyED($txt,$encrypt_key) {
  $encrypt_key = md5($encrypt_key);
  $ctr=0;
  $tmp = "";
  for ($i=0;$i<strlen($txt);$i++){
	if ($ctr==strlen($encrypt_key)) $ctr=0;
	$tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
	$ctr++;
  }
  return $tmp;
}

function sys_array_shift($var) {
  return array_shift($var);
}

function sys_backtrace() {
  $e = new Exception();
  return $e->getTraceAsString();
}

function sys_is_internal_url($val) {
  if (($pos = strpos($val,"?"))) $val = substr($val,0,$pos);
  if (strpos($val,$_SERVER["SERVER_NAME"]) or strpos($val,$_SERVER["HTTP_HOST"]) or !strpos($val,"://")) return true;
  return false;
}

function sys_is_super_admin($str) {
  if (empty($str)) return false;
  if (!defined("SETUP_ADMIN_USER") or in_array($str, array(SETUP_ADMIN_USER, SETUP_ADMIN_USER2))) {
	return true;
  }
  return false;
}

function sys_validate_token() {
  if (!empty($_REQUEST["username"]) and !empty($_REQUEST["password"])) return true;
  if (empty($_REQUEST["token"]) or empty($_SESSION["tokens"])) return false;
  if (array_key_exists($_REQUEST["token"], $_SESSION["tokens"])) return true;
  return false;
}

/**
 * Checks if the current user is allowed to access the path in the local file system
 * @param string $dir: directory path
 * @return string empty string on success, error message on failure
 */
function sys_allowedpath($dir) {
  if (strpos($dir,"://") or $dir=="http:") return "";
  if (!is_dir($dir) or $dir=="/") return "{t}directory does not exist.{/t}";
  if (is_numeric($dir)) return "db-folder";
  $path_filename = rtrim(strtolower(str_replace("\\","/",realpath($dir))),"/")."/";
  $found = false;
  if (!empty($_SESSION["ALLOWED_PATH"])) {
    foreach ($_SESSION["ALLOWED_PATH"] as $path) {
      $path = rtrim(strtolower(str_replace("\\","/",realpath($path))),"/")."/";
      if (sys_strbegins($path_filename,$path)) $found = true;
    }
  }
  if ($found) {
    return "";
  } else {
    sys_warning(sprintf("{t}Directory is outside the allowed directory. (%s is outside %s){/t}",$path_filename,implode(", ",$_SESSION["ALLOWED_PATH"])));
	return "{t}wrong directory{/t}";
  }
}

function sys_error($error,$err_string="404 Not Found") {
  header("HTTP/1.1 ".$err_string);
  exit($error);
}

function sys_render_text($filename, $text) {
  $lines = explode("\n",wordwrap(str_replace("\r","",$text),80));
  $im = imagecreate(800, count($lines)*13+5);
  $black = imagecolorallocate($im, 255, 255, 255);
  imagecolortransparent($im, $black);
  $white = imagecolorallocate($im, 0, 0, 0);
  foreach ($lines as $key=>$line) imagestring($im, 4, 2, $key*13, $line, $white);
  imagepng($im, $filename);
  imagedestroy($im);
}

function sys_unlink($files) {
  $count = 0;
  foreach ((array)$files as $file) {
    if (!file_exists($file)) continue;
    unlink($file);
	$count++;
    if (file_exists($file.".lck")) sys_unlock($file,SETUP_ADMIN_USER);
} }

function sys_credentials($mfolder, $mountpoint="") {
  static $creds = array();
  if ($mountpoint!="") {
    $mountpoint = sys_parse_folder($mountpoint);
    if (empty($mountpoint["mfolder"])) return array();
	if ($mfolder=="") $mfolder = $mountpoint["mfolder"];
	$creds[$mfolder] = array("server"=>$mountpoint["mfolder"],"username"=>$mountpoint["user"],"password"=>$mountpoint["pass"],
	  "port"=>$mountpoint["port"],"ssl"=>$mountpoint["ssl"],"options"=>$mountpoint["options"]);
	return $mountpoint;
  }
  if (!isset($creds[$mfolder]) and isset($_SESSION["permission_sql_read"]) and $mountpoint=="" and $mfolder!="") {
  	$mp = db_select_value("simple_sys_tree","fmountpoint",array("id=@id@",$_SESSION["permission_sql_read"]),array("id"=>$mfolder));
	if (!empty($mp)) sys_credentials($mfolder,$mp);
  }
  if (!isset($creds[$mfolder])) {
    $creds[$mfolder] = array("server"=>"","username"=>"","password"=>"","port"=>"","ssl"=>"","options"=>"");
  }
  return $creds[$mfolder];
}

function sys_check_auth() {
  if (isset($_REQUEST["logout"]) or empty($_SESSION["username"])) {
    unset($_COOKIE[SESSION_NAME]);
    header("HTTP/1.1 401 Authorization Required");
    header("WWW-Authenticate: Basic realm='Simple Groupware'");
    exit;
  }
}

function sys_warning($message) {
  if (defined("NOCONTENT") and defined("NOSESSION")) sys_log_message_log("php-fail",$message);
	else if (defined("NOCONTENT")) $_SESSION["warning"][] = $message;
	else sys::$warning[] = $message;
}

function sys_notification($message) {
  if (defined("NOCONTENT")) {
	$_SESSION["notification"][] = $message;
  } else sys::$notification[] = $message;
}

function sys_alert($message) {
  sys::$alert[] = $message;
}

function sys_file_append($file,$str) {
  return @file_put_contents($file, $str, FILE_APPEND | LOCK_EX);
}

function sys_get_header($key) {
  if (function_exists("getallheaders")) {
    static $headers = null;
	if (!$headers) $headers = getallheaders();
	if ($key=="") return $headers;
	if (isset($headers[$key])) return $headers[$key];
  } else {
    if ($key=="User-Agent") $key = "HTTP_USER_AGENT";
	  else if ($key=="Content-Length") $key = "CONTENT_LENGTH";
	  else if ($key=="Authorization") $key = "HTTP_AUTHORIZATION";
	if (isset($_SERVER[$key])) return $_SERVER[$key]; // Content-length
  }
  return "";
}

function sys_https() {
  if (FORCE_SSL) return true;
  if (isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"]=="on") return true;
  return false;
}

function sys_touch($file,$time=-1) { // bug in PHP 5.x #41786
  if ($time==-1) $time = time();
  @touch($file,$time);
  if (filemtime($file)!=$time) {
	@touch($file,2*$time - filemtime($file));
  }
}

function sys_redirect($url) {
  if (sys::$alert) $_SESSION["alert"] = sys::$alert;
  if (sys::$notification) $_SESSION["notification"] = sys::$notification;
  if (sys::$warning) $_SESSION["warning"] = sys::$warning;
  if (headers_sent()) exit("<script>parent.document.location='".urlencode($url)."';</script>");
  header("Location: ".$url);
  exit;
}

function sys_build_meta($content,$data) {
  $result = array();
  $content = explode("\n",$content);
  foreach ($content as $line) {
    if (($pos = strpos($line,"="))) $result[substr($line,0,$pos)] = str_replace("\\n","\n",substr($line,$pos+1));
  }
  foreach ($result as $key=>$val) {
    if (!isset($data[$key])) {
      $data[$key] = $val;
    } else if ($key=="history") $data[$key] = $data[$key].$val;
  }
  return $data;
}

function sys_build_meta_str($data,$fields) {
  $out = "";
  foreach ($fields as $field) {
	if (isset($data[$field]) and $data[$field]!="") {
	  $out .= $field."=".str_replace(array("\r","\n"),array("","\\n"),$data[$field])."\n";
	}
  }
  return $out;
}

function sys_can_lock($file) {
  if (sys_strbegins($file,SIMPLE_STORE."/") and file_exists($file) and !file_exists($file.".lck")) {
    return true;
  } else return false;
}
function sys_can_unlock($file,$username) {
  $lck_user = sys_get_lock($file);
  if ($lck_user!="" and ($username==$lck_user or sys_is_super_admin($username))) return true; else return false;
}
function sys_get_lock($file) {
  if (file_exists($file.".lck")) return file_get_contents($file.".lck"); else return "";
}
function sys_lock($file,$username) {
  if (!sys_can_lock($file)) return;
  file_put_contents($file.".lck", $username, LOCK_EX);
  file_put_contents(SIMPLE_STORE."/locking/locks.txt", $file."\n", LOCK_EX);
}
function sys_unlock($file,$username) {
  if (!sys_can_unlock($file,$username)) return false;
  @unlink($file.".lck");
  $locks = str_replace($file."\n","",file_get_contents(SIMPLE_STORE."/locking/locks.txt"));
  file_put_contents(SIMPLE_STORE."/locking/locks.txt", $locks, LOCK_EX);
  return true;
}

function sys_find_bin($program) {
  if (strpos(PHP_OS,"WIN")===false) {
	$ret = sys_exec("which ".$program);
	if (file_exists($ret)) return $ret;
	if (file_exists(sys_custom("tools/bin_deb/".$program)) and USE_DEBIAN_BINARIES) {
	  $program = sys_custom("tools/bin_deb/".$program);
	  if (!is_executable($program)) chmod($program,0744);
	  putenv("HOME=".sys_custom_dir("./tools/bin_deb/"));
	}
	return $program;
  } else {
	if ($program=="dot") {
	  $file = sys_custom("tools/bin_win32/graphviz/bin/dot.exe");
	} else {
	  $file = sys_custom("tools/bin_win32/".$program.".exe");
	}
	if (file_exists($file)) return modify::realfilename($file);
	return $program.".exe";
  }
}

function sys_exec($cmd,$input="",$cwd=null) {
  if (!function_exists("proc_open")) return "ERROR {t}Cannot call 'proc_open'. Please remove 'proc_open' from 'disable_functions' in php.ini and disable 'safe_mode'.{/t}";
  $out = "";
  $tmp = SIMPLE_CACHE."/debug/sys_exec_".sha1($_SESSION["username"].NOW).".txt";
  $pipes = array();
  $descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("file", $tmp, "w")
  );
  if (strpos($cmd,"@file@")) {
    $tmp2 = modify::realfilename(SIMPLE_CACHE."/debug/sys_exec_".sha1($_SESSION["username"].NOW)."_2.txt");
	$cmd = str_replace("@file@",$tmp2,$cmd);
  } else $tmp2 = "";

  if (strpos($cmd,"@file_error@")) {
    $tmp3 = modify::realfilename(SIMPLE_CACHE."/debug/sys_exec_".sha1($_SESSION["username"].NOW)."_3.txt");
	$cmd = str_replace("@file_error@",$tmp3,$cmd);
  } else $tmp3 = "";

  $process = proc_open($cmd, $descriptorspec, $pipes, $cwd);
  if (is_resource($process) and isset($pipes[0])) {
    if ($input!="") fwrite($pipes[0], $input);
    fclose($pipes[0]);
	while (!feof($pipes[1])) $out .= fgets($pipes[1], 8192);
    fclose($pipes[1]);
	if ($tmp2!="" and file_exists($tmp2)) $out .= file_get_contents($tmp2);
    $return_value = proc_close($process);
	if ($return_value!=0) {
	  $error = file_get_contents($tmp);
	  if (file_exists($tmp3) and filesize($tmp3)>0) $error .= file_get_contents($tmp3);
	  if ($error!="") $out = "ERROR ".$error." ".$out;
	}
  } else $out = "proc_open: error";
  @unlink($tmp);
  if ($tmp2!="") @unlink($tmp2);
  if ($tmp3!="") @unlink($tmp3);
  return trim($out);
}

function sys_encrypt($txt,$key)	{
  srand((double)microtime()*1000000);
  $encrypt_key = md5(rand(0,32000));
  $ctr=0;
  $tmp = "";
  for ($i=0;$i<strlen($txt);$i++) {
	if ($ctr==strlen($encrypt_key)) $ctr=0;
	$tmp.= substr($encrypt_key,$ctr,1) . (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
	$ctr++;
  }
  return base64_encode(_sys_keyED($tmp,$key));
}

function sys_decrypt($txt,$key) {
  $txt = _sys_keyED(base64_decode($txt),$key);
  $tmp = "";
  for ($i=0;$i<strlen($txt);$i++){
	$md5 = substr($txt,$i,1);
	$i++;
	$tmp.= (substr($txt,$i,1) ^ $md5);
  }
  return $tmp;
}

function sys_cache_get_file($type, $id, $name, $create_dir=false) {
  $file = SIMPLE_CACHE."/".$type."/".sys_get_pathnum($id)."/".sha1($id).urlencode($name);
  if ($create_dir) sys_mkdir(dirname($file));
  return $file;
}

function sys_cache_get($cid) {
  if (isset(sys::$cache[$cid])) return sys::$cache[$cid];
  $data = false;
  if (APC) {
	$data = apc_fetch($cid.$_SESSION["username"]);
  } else {
	$cache_file = SIMPLE_CACHE."/schema_data/".substr($cid,0,strpos($cid,"_"))."/".sys_get_pathnum($cid)."/".$cid."_".$_SESSION["username"].".ser";
	if (file_exists($cache_file) and filemtime($cache_file) > time()) {
	  $data = unserialize(file_get_contents($cache_file));
	}
  }
  if ($data!==false) {
	sys::$cache[$cid] = $data;
  } else {
    if (DEBUG and !defined("NOCONTENT") and !isset($_REQUEST["iframe"])) echo "[cache-renew] ".$cid."\n";
  }
  return $data;
}

function sys_cache_set($cid,$data,$time) {
  sys::$cache[$cid] = $data;
  if (APC) {
	apc_store($cid.$_SESSION["username"], $data, $time);
  } else {
	$cache_file = SIMPLE_CACHE."/schema_data/".substr($cid,0,strpos($cid,"_"))."/".sys_get_pathnum($cid)."/".$cid."_".$_SESSION["username"].".ser";
	sys_mkdir(dirname($cache_file));
	file_put_contents($cache_file, serialize($data), LOCK_EX);
	touch($cache_file, $time+time());
  }
}

function sys_cache_remove($cid,$username=null) {
  if (isset(sys::$cache[$cid])) unset(sys::$cache[$cid]);
  if (!$username) $username = $_SESSION["username"];
  if (APC) {  
	apc_delete($cid.$username);
  } else {
	@unlink(SIMPLE_CACHE."/schema_data/".substr($cid,0,strpos($cid,"_"))."/".sys_get_pathnum($cid)."/".$cid."_".$username.".ser");
  }
}

function sys_parse_csv($file) {
  if (($data = sys_cache_get("csv_".sha1($file)))) return $data;
  if (sys_allowedpath(dirname($file))!="") return array();
  $rows = array();
  if (($handle = fopen($file, "r"))) {
    if (strpos($file,"iso")) $iso = true; else $iso = false;
    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
	  if ($iso) foreach (array_keys($data) as $key) $data[$key] = utf8_encode($data[$key]);
	  $rows[] = $data;
	}
    fclose($handle);
  } else {
    sys_warning(sprintf("{t}Cannot read the file %s.{/t}",$file));
	return array();
  }
  sys_cache_set("csv_".sha1($file),$rows,CSV_CACHE);
  return $rows;
}

function sys_csv_2_xml($file) {
  $rows = sys_parse_csv($file);
  $out = "<assets>";
  if (count($rows)>1) {
	$keys = array_shift($rows);
	foreach ($rows as $row) {
	  $out .= "<asset>\n";
	  foreach ($row as $key=>$val) {
		if (!empty($keys[$key])) $out .= "<".$keys[$key].">".modify::htmlquote($val)."</".$keys[$key].">\n";
	  }
	  $out .= "</asset>\n";
	}
  }
  return $out."</assets>";
}

function sys_get_xml($file, $data = array()) {
  if (!strpos($file,".csv")) $xml = file_get_contents($file); else $xml = sys_csv_2_xml($file);
  if (strlen($xml)<102400) {
    if (count($data)>0) {
	  foreach ($data as $key=>$val) $data[$key] = modify::htmlquote(is_array($val)?implode("|",$val):$val);
	  $xml = sys_replace($xml, $data);
	}
    if (DEBUG) $xml = sys_remove_trans($xml);
  }
  $xml = preg_replace("|<!--.*?-->|msi","",$xml);
  try {
    return @new SimpleXMLElement($xml);
  }
  catch(Exception $e) {
    sys_die("{t}Error{/t}: ".$file." ".$e->getMessage()." ".libxml_get_last_error()->message);
  }
  return new stdClass();
}

function sys_replace($str, $data) {
  $matches = "";
  $replace = array();
  if (preg_match_all("|@(.*?)@|i",$str,$matches,PREG_SET_ORDER)) {
	foreach ($matches as $match) {
	  if (count($match)==2) {
	    $req_key = $match[1];
		if (isset($data[$req_key])) $replace["@".$req_key."@"] = $data[$req_key];
  } } }
  return str_replace(array_keys($replace),$replace,$str);
}

function sys_parse_folder($folder,$key="") {
  $pos = strpos($folder,":");
  $folder = str_replace(array("%username%","%password%"),array($_SESSION["username"],sys_decrypt($_SESSION["password"],session_id())),$folder);
  $result = array(
    "handler" => substr($folder,0,$pos),
    "path" => substr($folder,$pos+1),
	"mountpoint" => substr($folder,strpos($folder,"/",strpos($folder,"@"))+1),
	"user"=>"", "port"=>"", "ssl"=>"", "pass"=>"", "options"=>""
  );
  $result["mfolder"] = substr($result["path"],0,strpos($result["path"],"/",strpos($folder,"@")));
  if (($pos = strrpos($result["mfolder"],"@"))) {
	$result["path"] = substr($result["path"],strrpos($result["path"],"@")+1);
	$user = explode(":",substr($result["mfolder"],0,$pos));
	$result["user"] = str_replace(array("%%","=="),array("@",":"),$user[0]);
	if (isset($user[1])) $result["pass"] = str_replace(array("%%","=="),array("@",":"),$user[1]);
	if (isset($user[2])) $result["port"] = $user[2];
	if (isset($user[3])) $result["ssl"] = $user[3];
	if (isset($user[4])) $result["options"] = str_replace(array("%%","=="),array("@",":"),$user[4]);
	$result["mfolder"] = substr($result["mfolder"],$pos+1);
  }
  if ($key!="") return $result[$key];
  return $result;
}

function sys_remove_handler($var) {
  if (!is_array($var)) {
    $var = _sys_remove_handler($var);
  } else {
    if (!empty($var["id"]) and is_array($var["id"])) {
	  foreach ($var["id"] as $key=>$val) $var["id"][$key] = _sys_remove_handler($val);
	} else if (!empty($var["id"])) {
	  $var["id"] = _sys_remove_handler($var["id"]);
	}
    if (!empty($var["folder"])) {
	  $str = $var["folder"];
	  $pos = strpos($str,":");
	  $pos2 = strpos($str,"/");
	  $var["handler"] = "";
	  $var["mfolder"] = "";
  	  if ($pos>1 and $pos < $pos2) {
		$var["handler"] = substr($str,0,$pos);
		$var["mfolder"] = substr($str,$pos+1,$pos2-$pos-1);
	  }
	  $var["folder"] = _sys_remove_handler($var["folder"]);
	}
    if (!empty($var["folders"])) {
	  foreach ($var["folders"] as $key=>$val) $var["folders"][$key] = _sys_remove_handler($val);
	}
	if (!empty($var["item"])) {
	  foreach (array_keys($var["item"]) as $key) {
	    $var["item"][$key] = _sys_remove_handler($var["item"][$key]);
  } } }
  return $var;
}

function sys_make_smartyhash() {
  $smarty_data = serialize(sys::$smarty->_tpl_vars);
  return "SGS".strlen($smarty_data).crc32($smarty_data);
}

function sys_date($format,$time=0) {
  if (DEBUG) $format = sys_remove_trans($format);
  if ($time==0) return date($format,NOW); else return date($format,$time);
}

function sys_getdate($time=0) {
  if ($time==0) $arr = getdate(NOW); else $arr = getdate($time);
  if ($arr["yday"]>58 and $arr["year"]%4==0 and $arr["year"]%100!=0) $arr["yday"]--;
  return $arr;
}

function sys_build_cacheoutput($smarty_hash,$cache_file) {
  header("ETag: \"".$smarty_hash."\"");
  if (!DEBUG and isset($_SERVER["HTTP_IF_NONE_MATCH"]) and stripslashes($_SERVER["HTTP_IF_NONE_MATCH"])=="\"".$smarty_hash."\"") {
	header("HTTP/1.1 304 Not changed");
	exit;
  }
  if (CORE_COMPRESS_OUTPUT and isset($_SERVER["HTTP_ACCEPT_ENCODING"]) and strpos($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")!==false and !@ini_get("zlib.output_compression")) {
	header("Content-Encoding: gzip");
  }
  echo file_get_contents($cache_file);
}

function sys_build_output($cache_file="") {
  $output = sys::$smarty->fetch("main.tpl");
  if (DEBUG) {
    $message = sys::$time_end;
    if (function_exists("memory_get_usage") and function_exists("memory_get_peak_usage")) {
	  $message .= " (".modify::filesize(memory_get_usage())." - ".modify::filesize(memory_get_peak_usage()).")";
	}
    $output = sys_remove_trans(preg_replace("|<title>(.*?)</title>|i","<title>".$message." - \\1</title>",$output));
  } else {
    $output = "<!-- ".sys::$time_end." secs -->".$output;
  }
  if (CORE_COMPRESS_OUTPUT and !@ini_get("zlib.output_compression") and !sys::$alert and
      isset($_SERVER["HTTP_ACCEPT_ENCODING"]) and strpos($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip")!==false and
	  count(sys::$alert)==0 and $GLOBALS["output"]=="") {
	header("Content-Encoding: gzip");
    $output = gzencode($output);
	$cache_file .= ".gz";
  }
  echo $output;
  if (strlen($cache_file)>3) file_put_contents($cache_file, $output, LOCK_EX);
}

function sys_strbegins($haystick, $needle) {
  if (strncmp($haystick,$needle,strlen($needle))==0) return true;
  return false;
}

function sys_contains($haystick, $needle) {
  if (strpos($haystick,$needle)===false) return false;
  return true;
}

function sys_die($str,$str2="") {
  $str = sys_remove_trans($str);
  echo "<html><body style='padding:0px;margin:0px;'><center>";
  if (sys::$alert) echo nl2br(sys_remove_trans(modify::quote(implode("<br>",sys::$alert))))."<br><br>";
  echo "<table style='width: 600px;'>";
  echo "<tr><td align='center' style='border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;'>Simple Groupware & CMS</td></tr>";
  echo "<tr><td align='center' style='border-bottom: 1px solid black;'>".modify::htmlquote($str)."</td></tr>";
  if ($str2!="") echo "<tr><td style='border-bottom: 1px solid black;'>".modify::htmlquote($str2)."</td></tr>";
  echo "</table>Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</center></body></html>";
  exit;
}

function sys_is_folderstate_open($path,$handler,$parent) {
  if (!empty($_SESSION["folder_states"][$handler.":".$parent."/".$path])) return true; else return false;
}

function sys_select_where($row,$where,$vars) {
  $add = true;
  if (!empty($vars["search"])) $where[] = "search";
  if (count($where)>0) {
	foreach ($where as $item) {
	  if ($item=="1=1") continue;
	  if (strpos($item," in ")) {
	    $item = explode(" in ",$item);
		$var = str_replace(array("@","(",")"),"",$item[1]);
		if (!isset($row[$item[0]]) or !isset($vars[$var])) continue;
	    if (!in_array($row[$item[0]],(array)$vars[$var])) $add = false;
	  } else if (strpos($item," not like ")) {
	    $item = explode(" not like ",$item);
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		$item[1] = $vars[$var];
		if (isset($row[$item[0]]) and preg_match('|^'.str_replace(array('\*','%'),array('.*','.*'),preg_quote($item[1])).'|i',$row[$item[0]])) $add = false;
	  } else if (strpos($item," like ")) {
	    $item = explode(" like ",$item);
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		$item[1] = $vars[$var];
		if (isset($row[$item[0]]) and !preg_match('|^'.str_replace(array('\*','%'),array('.*','.*'),preg_quote($item[1])).'|i',$row[$item[0]])) $add = false;
	  } else if (strpos($item," regexp ")) {
	    $item = explode(" regexp ",$item);
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		$item[1] = $vars[$var];
		if (isset($row[$item[0]]) and !preg_match('/'.$item[1].'/',$row[$item[0]])) $add = false;
	  } else if (strpos($item,">")) {
	    $item = explode(">",str_replace(" > ",">",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var]) or !isset($row[$item[0]])) continue;
	    if ($row[$item[0]] <= $vars[$var]) $add = false;
	  } else if (strpos($item,"<")) {
	    $item = explode("<",str_replace(" < ","<",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var]) or !isset($row[$item[0]])) continue;
	    if ($row[$item[0]] >= $vars[$var]) $add = false;
	  } else if (strpos($item,"!=")) {
	    $item = explode("!=",str_replace(" != ","!=",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var]) or !isset($row[$item[0]])) continue;
	    if ($row[$item[0]]==$vars[$var]) $add = false;
	  } else if (strpos($item,"=")) {
	    $item = explode("=",str_replace(" = ","=",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var]) or !isset($row[$item[0]])) continue;
	    if ($row[$item[0]]!=$vars[$var]) $add = false;
	  } else if ($item=="search") {
		$item = $GLOBALS["t"]["search"]["query"];
		if (!preg_match('|'.str_replace(array('\*'),array('.*'),preg_quote($item)).'|i',@implode(" ",$row))) $add = false;
  } } }
  return $add;
}

function sys_select($rows,$order,$limit,$fields) {
  if (count($rows)==0) return array();
  if ($order!="") {
	$GLOBALS["sort_field"] = explode(",",trim(str_replace(array("asc","desc",", "," ,"),array("","",",",","),$order)));
	$GLOBALS["sort_field_order"] = strpos($order,"desc")?-1:1;
	$count = count($GLOBALS["sort_field"]);
	foreach ($GLOBALS["sort_field"] as $key=>$sort) {
	  if (!in_array($sort,$fields)) {
	    $count--;
	    unset($GLOBALS["sort_field"][$key]);
	  }
	}
	if ($count>0) {
  	  usort($rows,"sys_cmp");
	  $rows = array_values($rows);
	}
  }
  if (count($limit)==2 and count($rows)>$limit[1]) $rows = array_slice($rows,$limit[0],$limit[1]);
  if (count($limit)==1 and count($rows)>$limit[0]) $rows = array_slice($rows,0,$limit[0]);
  return $rows;
}

function sys_cmp($a, $b) {
  foreach ($GLOBALS["sort_field"] as $field) {
    if (!isset($a[$field]) or !isset($a[$field])) return 0;
    if (!is_numeric($a[$field])) {
      $a[$field] = strtolower($a[$field]);
      $b[$field] = strtolower($b[$field]);
    }
    if ($a[$field] == $b[$field]) continue;
    return ($a[$field] < $b[$field]) ? $GLOBALS["sort_field_order"]*(-1) : $GLOBALS["sort_field_order"];
  }
  return 0;
}

function sys_threadsort($fields,$level,&$result,$arr,$arr2) {
  foreach ($fields as $field) {
	$field["tlevel"] = $level;
	if (!isset($result[$field["id"]]))	$result[$field["id"]] = $field;
	if (isset($arr2[$field["id"]]) and count($arr2[$field["id"]])>0) sys_threadsort($arr2[$field["id"]],$level+1,$result,$arr,$arr2);
  }
}

function sys_threadsort_cmp($a, $b) {
  if ($a["created"]==$b["created"]) return 0;
  if ($a["created"]<$b["created"]) return -1; else return 1;
}

function sys_threadsort_cmp2($a, $b) {
  if ($a["sorting"]==$b["sorting"]) return 0;
  if ($a["sorting"]<$b["sorting"]) return -1; else return 1;
}

function sys_mkdir($dir) {
  $old_umask = @umask(0);
  $result = @mkdir($dir,octdec(CHMOD_DIR),true);
  @umask($old_umask);
  return $result;
}
function sys_chmod($file_dir) {
  if (is_dir($file_dir)) $mode = CHMOD_DIR; else $mode = CHMOD_FILE;
  @chmod($file_dir, octdec($mode));
}

function sys_process_session_request() {
  if (!empty($_REQUEST["popup"]) and !empty($_REQUEST["iframe"])) unset($_REQUEST["iframe"]);
  if (!empty($_REQUEST["iframe"])) sys::$smarty->assign("iframe",1);

  $keep_vars = array("popup","onecategory","preview","lookup","eto");
  foreach ($keep_vars as $var) {
    if (empty($_REQUEST[$var])) continue;
	sys::$urladdon .= "&".$var."=".$_REQUEST[$var];
    sys::$smarty->assign($var,$_REQUEST[$var]);
  }
  $_SESSION["view"]["_".$GLOBALS["tfolder"]] = $GLOBALS["tview"];
  sys::$urladdon = "folder2=".rawurlencode($GLOBALS["tfolder"])."&view2=".$GLOBALS["tview"].sys::$urladdon;
  sys::$smarty->assign("urladdon",sys::$urladdon);

  if (!empty($_REQUEST["action_sys"]) and !empty($_SESSION["username"]) and sys_is_super_admin($_SESSION["username"])) {
    admin::process_action_sys();
  }
  if (isset($_REQUEST["style"])) $_SESSION["style"] = basename($_REQUEST["style"]);
  if (!isset($_SESSION["style"])) $_SESSION["style"] = DEFAULT_STYLE;
  sys::$smarty->assign("sys_style", $_SESSION["style"]);

  $table = $GLOBALS["table"];
  if ($GLOBALS["tview"]!=$table["view"]) $GLOBALS["tview"] = $table["view"];
  $tview = $GLOBALS["tview"];
  $tfolder = $GLOBALS["tfolder"];
  $tfolders = $GLOBALS["tfolders"];
  $tname = $GLOBALS["tname"];
  $tquota = $GLOBALS["tquota"];
  $anchor = $GLOBALS["sel_folder"]["anchor"];

  if (!empty($_REQUEST["reset_view"])) {
	$_SESSION[$tname][$tview] = array();
	$_SESSION["_".$tfolder] = array();
	$_SESSION["view"]["_".$tfolder] = $tview;
	$_SESSION[$tname]["_".$tfolder] = array();
  }
  $current_view = $table["views"][$tview];
  $cview = $current_view;
  $template = $tview;

  if ($current_view["TEMPLATE"]!="") $template = $current_view["TEMPLATE"];

  if (isset($current_view["SCHEMA"]) and $current_view["SCHEMA"]!="") {
    $table2 = db_get_schema(sys_find_module($current_view["SCHEMA"]));
	$current_view = array_shift($table2["views"]);
	// preserve in search, override for schema=x
	if (!empty($table["att"]["SQL_HANDLER"]) and empty($current_view["SQL_HANDLER"])) {
	  $current_view["SQL_HANDLER"] = $table["att"]["SQL_HANDLER"];
	}
	$GLOBALS["table"] = $table2; // needed for asset-functions and triggers
	$table["att"] = $table2["att"];
	if ($current_view["TEMPLATE"]!="") $template = $current_view["TEMPLATE"];
  }

  $GLOBALS["current_view"] = $current_view;

  $field_names = array();
  foreach ($current_view["fields"] as $key=>$field) {
	if (isset($field["NODB"]) and empty($current_view["SQL_HANDLER"])) continue;
	$field_names[] = $key;
  }

  if (!empty($_SESSION["alert"])) {
    sys::$alert = array_merge(sys::$alert,$_SESSION["alert"]);
	$_SESSION["alert"] = array();
  }
  if (!empty($_SESSION["notification"])) {
	sys::$notification = array_merge(sys::$notification,$_SESSION["notification"]);
	$_SESSION["notification"] = array();
  }
  if (!empty($_SESSION["warning"])) {
	sys::$warning = array_merge(sys::$warning,$_SESSION["warning"]);
	$_SESSION["warning"] = array();
  }
  if ($table["views"][$tview]["SCHEMA_MODE"]!="") {
    $tfolders = _build_merge_folders(array_keys($tfolders), $tfolder, $tview, true);
  }
  $dclick = $current_view["DOUBLECLICK"];
  if ($dclick=="") {
    if (in_array($template,array("display","free")) and isset($current_view["views"]["details"])) {
	  $dclick = "details";
	} else $dclick = "edit";
  }
  if (isset($current_view["MERGE_TABS"])) {
	unset($current_view["tabs"]);
    foreach (array_keys($current_view["fields"]) as $key) {
	  $current_view["fields"][$key]["SIMPLE_TAB"] = array("general");
	}
  }
  $tfield_1 = isset($current_view["TFIELD_1"])?$current_view["TFIELD_1"]:modify::get_required_field($current_view["fields"]);
  $tfield_2 = isset($current_view["TFIELD_2"])?$current_view["TFIELD_2"]:"";
  
  // TODO2 reduce ??
  $t = array(
	  "anchor"=>$anchor,
      "att"=>$table["att"],
	  "buttons"=>$current_view["buttons"],
	  "custom_name"=>$table["att"]["CUSTOM_NAME"],
	  "data"=>array(),
	  "default_sql"=>$current_view["DEFAULT_SQL"],
	  "disable_tabs"=>isset($current_view["DISABLE_TABS"])?$current_view["DISABLE_TABS"]:"",
	  "doubleclick"=>array_key_exists($dclick,$current_view["views"])?$dclick:"",
  	  "fields"=>$current_view["fields"],
  	  "fields_all"=>$table["fields"],
	  "fields_query"=>array_unique(array_merge(array($current_view["id"]),$field_names,array("created","lastmodified","createdby","lastmodifiedby","folder"))),
	  "field_1"=>$tfield_1,
	  "field_2"=>$tfield_2,
	  "filter"=>isset($current_view["FILTERS"])?$current_view["FILTERS"]:"",
	  "filters"=>$current_view["filters"],
	  "folder"=>$tfolder,
	  "folders"=>$tfolders,
	  "folder_preview"=>isset($_REQUEST["tpreview"]),
	  "function"=>isset($current_view["FUNCTION"])?$current_view["FUNCTION"]:"",
	  "id"=>$current_view["id"],
	  "isdbfolder"=>is_numeric($tfolder)?true:false,
	  "limit"=>$current_view["LIMIT"],
	  "links"=>$current_view["links"],
	  "linkstext"=>$current_view["linkstext"],
	  "load_css"=>isset($table["att"]["LOAD_CSS"])?$table["att"]["LOAD_CSS"]:"",
	  "load_js"=>isset($table["att"]["LOAD_JS"])?$table["att"]["LOAD_JS"]:"",
	  "lookup"=>isset($_REQUEST["lookup"])?$_REQUEST["lookup"]:"",
  	  "order"=>$current_view["ORDER"],
  	  "orderby"=>$current_view["ORDERBY"],
  	  "groupby"=>$current_view["GROUPBY"],
  	  "group"=>$current_view["GROUP"],
	  "handler"=>$current_view["SQL_HANDLER"],
	  "hidedata"=>$_SESSION["hidedata"],
	  "nosinglebuttons"=>isset($cview["NOSINGLEBUTTONS"])?$cview["NOSINGLEBUTTONS"]:"",
	  "notification"=>&sys::$notification,
	  "warning"=>&sys::$warning,
	  "noviewbuttons"=>isset($cview["NOVIEWBUTTONS"])?$cview["NOVIEWBUTTONS"]:"",
  	  "page"=>1,
	  "quota"=>$tquota,
	  "restore"=>$current_view["restore"],
	  "rights"=>$GLOBALS["sel_folder"]["rights"],
	  "vright"=>isset($cview["RIGHT"])?$cview["RIGHT"]:"",
	  "rowfilters"=>$current_view["rowfilters"],
	  "rowvalidates"=>$current_view["rowvalidates"],
	  "schema_mode"=>$current_view["SCHEMA_MODE"],
	  "singlebuttons"=>$current_view["singlebuttons"],
	  "sqllimit"=>array(),
	  "sqlorder"=>"",
	  "sqlvars"=>array("folder"=>$tfolder,"folders"=>array_keys($tfolders)),
	  "sqlvarsnoquote"=>array(),
	  "sqlwhere"=>$current_view["SQLWHERE"],
	  "sqlwhere_default"=>$current_view["SQLWHERE_DEFAULT"],
	  "subitem"=>0,
	  "tabs"=>isset($current_view["tabs"])?$current_view["tabs"]:array("general"=>array("NAME"=>"general")),
	  "template"=>"asset_".$template.".tpl",
	  "template_mode"=>isset($current_view["TEMPLATE_MODE"])?$current_view["TEMPLATE_MODE"]:"",
	  "title"=>$tname,
	  "view"=>$tview,
  	  "views"=>$table["views"][$tview]["views"]
  );
  $GLOBALS["t"] = $t;

  if (!empty($current_view["SCHEMA_MODE"])) sys_process_schema_request();
}

function sys_find_callback($class,$function) {
  $params = array();
  if (strpos($function,"|")) {
	$params = explode("|",$function);
	$function = array_shift($params);
  }
  if (strpos($function,"::")) list($class,$function) = explode("::",$function);
  if (class_exists("custom",false) and method_exists("custom",$function)) $class = "custom";
  if (!method_exists($class, $function)) {
    $message = sprintf("{t}Function does not exist: %s{/t}",$class."::".$function);
    sys_log_message_log("php-fail",$message);
    sys_die($message);
  }
  return array($class,$function,$params);
}

function sys_find_module($ftype) {
  return sys_custom("modules/schema".str_replace("/sys_","_sys/","/".$ftype).".xml");
}

function sys_scandir($path,$exclude=array(".","..")) {
  $files = scandir($path);
  if (is_dir(SIMPLE_CUSTOM.$path)) {
	$files = array_merge($files,scandir(SIMPLE_CUSTOM.$path));
	sort($files);
  }
  return array_diff($files,$exclude);
}

function sys_custom($file) {
  if (file_exists(SIMPLE_CUSTOM.$file)) return SIMPLE_CUSTOM.$file;
  if (file_exists(SIMPLE_EXT.$file)) return SIMPLE_EXT.$file;
  return $file;
}

function sys_custom_dir($dir) {
  if (is_dir(SIMPLE_CUSTOM.$dir)) return SIMPLE_CUSTOM.$dir;
  if (is_dir(SIMPLE_EXT.$dir)) return SIMPLE_EXT.$dir;
  return $dir;
}

function sys_process_output() {
  if (empty($_REQUEST["export"]) and empty($_REQUEST["import"])) {
	sys::shutdown();
	if (CORE_OUTPUT_CACHE) {
      $smarty_hash = sys_make_smartyhash();
      $cache_file = SIMPLE_CACHE."/output/".$_SESSION["username"]."_".$smarty_hash.".htm";
	  if (!sys::$alert and CORE_COMPRESS_OUTPUT and @filesize($cache_file.".gz")>0 and filemtime($cache_file.".gz")+OUTPUT_CACHE > time()) {
	    sys_build_cacheoutput($smarty_hash,$cache_file.".gz");
	  } else if (!sys::$alert and !CORE_COMPRESS_OUTPUT and @filesize($cache_file)>0 and filemtime($cache_file)+OUTPUT_CACHE > time()) {
	    sys_build_cacheoutput($smarty_hash,$cache_file);
	  } else {
	    sys_build_output($cache_file);
      }
	} else {
	  sys_build_output();
    }
  } else {
    if (sys::$alert) exit;
	if (method_exists("export",$_REQUEST["export"])) call_user_func(array("export", $_REQUEST["export"]));
	  else sys_die(sprintf("{t}Function does not exist: %s{/t}","export::".$_REQUEST["export"]));
  }
}

function sys_correct_quote($var,$use_null=false) {
  if (is_array($var)) {
    if (count($var)==0) return "''";
    foreach ($var as $key=>$key2) {
	  if (!is_numeric($key2)) $var[$key] = "'".sql_quote($key2)."'";
	}
	return implode(",",$var);
  } else if ($use_null and $var == null) {
    return "null";
  } else if (is_numeric($var)) {
    return "'".$var."'";
  } else return "'".sql_quote($var)."'";
}

function sys_message_box($subject, $ret) {
  $out = "<div style='position:absolute; z-index:10; width:50%; border:1px solid #666; padding:2px; margin:2px; background-color:#FFF;'>";
  $out .= "<div><a style='float:right;' onclick='javascript:this.parentNode.parentNode.style.display=\"none\";'>{t}Close{/t}</a></div>";
  $out .= "<div style='background-color:#EEE; margin-bottom:3px; font-weight:bold;'>".$subject."</div>";
  $out .= "<pre>".modify::htmlquote(wordwrap($ret))."</pre>";
  $out .= "</div>";
  if (DEBUG) $out = sys_remove_trans($out);
  echo $out;
}

function sys_get_pathnum($num) {
  if (!is_numeric($num)) $num = abs(crc32($num));
  return $num%2500;
}

function sys_build_filename($filename,$table="") {
  $filename = NOW."-".sha1(uniqid(rand(), true).uniqid(rand(), true))."--".urlencode(basename($filename));
  if ($table=="" or strpos($table,"_nodb_")) {
    return array(SIMPLE_CACHE."/upload/",$filename);
  }
  return array(SIMPLE_STORE."/".$table."/",$filename);
}

function sys_get_microtime() {
  list($usec, $sec) = explode(" ",microtime());
  return ((float)$usec + (float)$sec);
}

function sys_array_combine($array_keys, $array_values) {
  $t = array();
  $lg = count($array_keys);
  for ($i=0;$i<$lg;$i++) {
	$key = $array_keys[$i];
	$t[$key] = $array_values[$i];
  }
  return $t;
}

function sys_log_stat($action,$weight) {
  if (empty($_SESSION["username"])) return;
  if ($weight==0) return;
  db_insert("simple_sys_stats",array("id"=>sql_genID("simple_sys_stats")*100+$_SESSION["serverid"],"username"=>$_SESSION["username"], "loghour"=>sys_date("H"), "logday"=>sys_date("d"), "logweek"=>sys_date("W"), "logweekpart"=>floor((sys_date("w")*24+sys_date("H")+1)/6), "action"=>$action, "uri"=>substr(_sys_request_uri(),0,250),"weight"=>$weight),array("delay"=>true));
}

function sys_log_message_alert($component,$message) {
  sys_alert($message);
  sys_log_message($component,$message,"","","",false);
}

function sys_log_message_log($component,$message,$message_trace="") {
  sys_log_message($component,$message,$message_trace,"","",false);
}

function sys_log_message($component,$message,$message_trace,$username,$serverid,$forcedb,$time=0) {
  if ($username=="") {
    if (isset($_SESSION["username"])) $username = $_SESSION["username"]; else $username = "anonymous";
  }
  if ($serverid=="") {
    if (isset($_SESSION["serverid"])) $serverid = $_SESSION["serverid"]; else $serverid = "1";
  }
  if (USE_SYSLOG_FUNCTION) {
	syslog(LOG_WARNING, $_SERVER["SERVER_NAME"]." (".$_SERVER["SERVER_ADDR"].") ".$component.", user: ".
						$username."\r\n".$message."\r\n".$message_trace."\r\n");
	return;
  }
  if ($forcedb and defined("SETUP_DB_HOST") and !empty(sys::$db) and (is_resource(sys::$db) or is_object(sys::$db))) {
    $id = sql_genID("simple_sys_events")*100+$serverid;
    $row = db_select_first("simple_sys_tree","id","ftype=@ftype@","lft asc",array("ftype"=>"sys_events"));
    if (!empty($row["id"])) {
	  $error_sql = db_insert("simple_sys_events",array("created"=>$time,"servername"=>$_SERVER["SERVER_NAME"],"serverip"=>$_SERVER["SERVER_ADDR"],"username"=>$username,"id"=>$id,"component"=>$component,"message"=>$message,"message_trace"=>$message_trace));
	  if ($error_sql=="") {
        db_search_update("simple_sys_events",$id,array(),array("created"=>"datetime","component"=>"text","message"=>"text","username"=>"text","serverip"=>"text","servername"=>"text"));
	  } else {
	    echo modify::htmlquote($message)."<br>".$error_sql."<br>";
	  }
	}
  } else {
    $out = serialize(array($component,str_replace(array("\n","\r"),"",$message),str_replace(array("\n","\r"),"",nl2br($message_trace)),$username,$serverid,NOW));

	// current directory is changed in destructor
	chdir(dirname(__FILE__)."/../");
	if (sys_file_append(SIMPLE_CACHE."/debug/error.txt", $out."\r\n")) return;
	$message = $_SERVER["SERVER_NAME"]." (".$_SERVER["SERVER_ADDR"].") ".$component.", user: ".
			   $username."\r\n".$message."\r\n".$message_trace."\r\n";
	echo modify::htmlquote($message);
    @error_log($message, 3, SIMPLE_CACHE."/debug/php_error.log");
  }
}

function sys_remove_trans($str) {
  return str_replace(array("{t"."}","{"."/t}"),array("",""),$str);
}

function sys_process_schema_request() {
  $defaults = folder_get_default_values($GLOBALS["t"]["folder"]);
  if (!empty($_REQUEST["defaults"])) {
 	$defaults = array_merge(json_decode($_REQUEST["defaults"], true), $defaults);
  }
  if (empty($GLOBALS["t"]["fields"])) return;
  foreach (array_keys($GLOBALS["t"]["fields"]) as $key) {
    $field = &$GLOBALS["t"]["fields"][$key];
	if (isset($field["SIMPLE_DEFAULT_FUNCTION"])) {
	  list($class, $function, $params) = sys_find_callback("modify", $field["SIMPLE_DEFAULT_FUNCTION"]);
	  $field["SIMPLE_DEFAULT"] = call_user_func(array($class, $function), $params);
	}
	if (isset($defaults[$key])) $field["SIMPLE_DEFAULT"] = $defaults[$key];
	if (empty($field["DATA"])) continue;
	foreach ($field["DATA"] as $vkey=>$val) {
	  if (!isset($val["_FUNCTION_"])) continue;
	  list($class, $function, $params) = sys_find_callback("select", $val["_FUNCTION_"]);
	  $result = call_user_func(array($class, $function), $params, array(), true);
	  
	  if (!empty($result["_ticket_"])) {
		$field["SIMPLE_LOOKUP"][$vkey] = array(
		  "ticket"=>$result["_ticket_"],
		  "schema"=>$result["_params_"][1],
		  "overload"=>isset($result["_overload_"])
		);
		$_SESSION["tickets"][$result["_ticket_"]] = $result["_params_"];
		unset($result["_ticket_"]);
		unset($result["_params_"]);
		unset($result["_overload_"]);
	  }
	  $field["DATA"][$vkey] = $result;
} }	}

// Smarty-Workaround for calling member function as modifier
function modify() {
  $args = func_get_args();
  $func = array_shift($args);
  return call_user_func_array(array("modify", $func), $args);
}

// Call a function from a type class
// type, func, args
function call_type() {
  $args = func_get_args();
  $type = array_shift($args);
  $func = array_shift($args);
  return call_user_func_array(array("type_".$type, $func), $args);
}

function is_call_type($type) {
  static $call_type = array();
  static $basic_type = array("id", "text", "password", "checkbox", "time", "date", "datetime", "int", "float", 
    "pid", "folder", "textarea", "multitext", "select", "dateselect", "files");

  if (in_array($type, $basic_type)) return false;
  if (in_array($type, $call_type)) return true;

  if (file_exists(sys_custom("core/types/".$type.".php"))) {
    $call_type[] = $type;
    return true;
  }
  return false;
}