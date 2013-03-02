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

class ajax {

/**
 * Returns the method's input arguments
 * 
 * @return mixed Returns the methods input arguments
 */
static function get_echo() {
  return func_get_args();
}

// TODO 2 implement calendar
/**
 * Returns assets from the database
 * 
 * @param int|string $folder Folder ID or String (/Workspace/.../)
 * @param string $view View name (e.g. display, details)
 * @param string $fields field1,field2 or * (optional)
 * @param string $order field-name asc|desc (optional)
 * @param int|string $limit Numeric limit or offset,limit (optional)
 * @param array $items Asset-IDs (optional)
 * @param string $filter Syntax: field|operation|search-string[||field|operation|search-string]
 *                       operation: like,nlike,starts,eq,neq,lt,gt,oneof
 * @param boolean $apply_filter Apply output filters (sgsML)
 * @param boolean $totals Output with totals: Array(total=>num of rows, rows=>...)
 * @return array Array with associative Arrays for each asset found
 */
static function asset_get_rows($folder,$view="display",$fields="*",$order="",$limit="",array $items=array(),$filter="",$apply_filter=false,$totals=false) {
  self::_require_access($folder, "read", $view);
  
  if (!preg_match("/^[a-z0-9 ,]+\$/i",$order)) $order = "";
  if (!preg_match("/^[a-z0-9 ,]+\$/i",$fields)) $fields = "*";
  if (!preg_match("/^[0-9 ,]+\$/i",$limit)) $limit = "20";

  $sgsml = new sgsml($folder,$view,$items,false);
  $sgsml->set_filter($filter);
  $rows = $sgsml->get_rows($fields, $order, explode(",", $limit));
  // TODO2 optimize?

  if ($rows==="error") throw new SoapFault("1", "SQL error");
  
  foreach ($rows as $key=>$row) {
	foreach ($row as $field_name=>$value) {
      if (!isset($sgsml->current_fields[$field_name])) {
		unset($rows[$key][$field_name]);
		continue;
      }
	  $rows[$key][$field_name] = $sgsml->restore_field($field_name, $value, $row);
	  if ($apply_filter) $rows[$key][$field_name] = $sgsml->filter_field($field_name, $value, $row);
	}
  }
  if ($totals) {
	return array(
      "total" => $sgsml->get_count(),
	  "rows" => $rows
	);
  }
  return $rows;
}

/**
 * Creates a new asset in the database
 * 
 * @param int|string $folder Folder ID or String (/Workspace/.../)
 * @param string $view View name (e.g. display, details)
 * @param array $data Associative Array containing one asset 
 * @return array|int succes: numeric ID, error: array( field_name => array( array( field_displayname, message ), ... ) ) 
 */
static function asset_insert($folder,$view,array $data) {
  self::_require_access($folder, "write", $view);
  $sgsml = new sgsml($folder,$view);
  if ($sgsml->current_view["SCHEMA_MODE"]!="new") exit("Access denied. ".sprintf("Invalid schema mode '%s'",$sgsml->current_view["SCHEMA_MODE"]));
  return $sgsml->insert($data);
}

/**
 * Changes as existing asset in the database
 * 
 * @param int|string $folder Folder ID or String (/Workspace/.../)
 * @param string $view View name (e.g. display, details)
 * @param array $data Associative Array containing one asset 
 * @param int $id Numeric ID of the asset
 * @return array|int succes: numeric ID, error: array( field_name => array( array( field_displayname, message ), ... ) ) 
 */
static function asset_update($folder,$view,array $data,$id) {
  self::_require_access($folder, "write", $view);
  $sgsml = new sgsml($folder,$view,array($id));
  if ($sgsml->current_view["SCHEMA_MODE"]!="edit") exit("Access denied. ".sprintf("Invalid schema mode '%s'",$sgsml->current_view["SCHEMA_MODE"]));
  return $sgsml->update($data,$id);
}

/**
 * Validates an asset
 * 
 * @param int|string $folder Folder ID or String (/Workspace/.../)
 * @param string $view View name (e.g. display, details)
 * @param array $data Associative Array containing one asset 
 * @param int $id Numeric ID of the asset (optional)
 * @return array valid: array(), invalid: array( field_name => array( array( field_displayname, message ), ... ) ) 
 */
static function asset_validate($folder,$view,array $data,$id=-1) {
  $sgsml = new sgsml($folder,$view,array(),false);
  return $sgsml->validate($data,$id);
}

/**
 * Deletes assets
 * 
 * @param int|string $folder Folder ID or String (/Workspace/.../)
 * @param string $view View name (e.g. display, details)
 * @param array $items Asset-IDs
 * @param string $mode delete, empty (complete folder), purge (no trash), purgeall (complete folder + no trash)
 * @return int Folder ID
 */
static function asset_delete($folder,$view,$items,$mode) {
  self::_require_access($folder, "write");
  asset::delete_items($folder, $view, $items, $mode);
  return $folder;
}

static function asset_cutcopy($folder,$view,$items,$operation) {
  if ($operation=="cut") {
    self::_require_access($folder, "write");
  } else { // copy
	self::_require_access($folder, "read");
  }
  $_SESSION["ccp_data"] = asset_ccp::cutcopy_items($folder, $view, $items, $operation);
  self::session_save();
}

static function asset_paste($folder) {
  self::_require_access($folder, "write");
  if (!empty($_SESSION["ccp_data"])) {
	$result = asset_ccp::paste_items($folder, $_SESSION["ccp_data"]);
	if ($result!="") exit($result);
  }
  $_SESSION["ccp_data"] = array();
  self::session_save();
  return $folder;
}

static function asset_ccp($folder, $view, $items, $target, $operation) {
  if ($operation=="cut") {
    self::_require_access($folder, "write");
  } else { // copy
	self::_require_access($folder, "read");
  }
  self::_require_access($target, "write");
  $ccp_data = asset_ccp::cutcopy_items($folder, $view, $items, $operation);
  if ($ccp_data) {
	$result = asset_ccp::paste_items($target, $ccp_data);
	if ($result!="") exit($result);
  }
  return $folder;
}

static function file_import($folder, $file, $output_func=false, $validate_only=false) {
  self::_require_access($folder, "write", "new");
  $import = new import();
  return $import->file($file, $folder, $output_func, $validate_only);
}

static function file_download($folder, $view, $id, $field, $subitem, $write) {
  self::_require_access($folder, "read", $view);
  
  $sgsml = new sgsml($folder, $view, (array)$id, $write);
  $data = $sgsml->get_rows(array("id", "folder", sql_fieldname($field)));
  if (empty($data[0][$field])) exit("Item(s) not found or access denied.");
  
  $files = explode("|", trim($data[0][$field], "|"));
  
  if (!is_numeric($subitem) and $subitem!="") {
    foreach ($files as $key=>$file) {
	  if (modify::basename($file) == $subitem) {
	    $subitem = $key;
	    break;
  } } }
  if (!is_numeric($subitem)) $subitem = 0;
  if (empty($files[$subitem])) exit("file not found in database.");

  $file = sys_remove_handler($files[$subitem]);
  if (!file_exists($file)) exit("file not found.");
  return $file;
}

static function file_lock($folder, $id, $field, $subitem) {
  self::_require_access($folder, "write", "edit");
  $filename = self::file_download($folder, "edit", $id, $field, $subitem, true);

  if (!sys_can_lock($filename)) exit("Access denied.");
  sys_lock($filename, $_SESSION["username"]);
  return $folder;
}

static function file_unlock($folder, $id, $field, $subitem) {
  self::_require_access($folder, "write", "edit");
  $filename = self::file_download($folder, "edit", $id, $field, $subitem, true);

  if (!sys_can_unlock($filename, $_SESSION["username"])) exit("Access denied.");
  sys_unlock($filename, $_SESSION["username"]);
  return $folder;
}

/**
 * Upload a file to the temp directory (data comes from php://input)
 * 
 * @param string $filename Filename
 * @return array Array( tmp_path=>Path of the file, basename=>filename, filesize=>filesize)
 */
static function upload_file($filename) {
  if (empty($filename) or empty($_SESSION["username"])) exit("Upload failed");
  if (strpos($filename,"://")) {
	$target = sgsml::getfile_url($filename);
  } else {
	$target = sgsml::getfile_upload($filename);
  }
  if ($target=="" or !file_exists($target)) {
	exit("Upload failed: Failed to write file to disk.");
  }
  return array("tmp_path"=>$target, "basename"=>modify::basename($target), "filesize"=>modify::filesize($target));
}

// TODO 2 add data handlers?
/**
 * Returns if a folder was modified
 * 
 * @param int $folder Folder ID
 * @param int $since Timestamp to check against
 * @return boolean True = modified, False = not modified
 */
static function folder_has_changed($folder,$since) {
  if (!is_numeric($folder)) return false;  
  self::_require_access($folder, "read");
  $modified = db_select_value("simple_sys_tree","lastmodified","id=@id@",array("id"=>$folder));
  if (empty($modified) or !is_numeric($modified) or !is_numeric($since) or $modified <= $since) return false;
  return true;
}

static function search_data($ticket, $search, $page, $ids=array()) {
  if ($ticket=="" or empty($_SESSION["tickets"][$ticket])) exit("[2] Your session has timed out.");
  $params = $_SESSION["tickets"][$ticket];
  list($class, $function, $unused) = sys_find_callback("select", array_shift($params));
  
  if (!empty($ids)) {
	$params[2][] = "id in (@ids@)";
  } else if (!empty($params[1])) {
	$where = array();
	foreach (array_unique($params[1]) as $field) $where[] = sql_concat($field)." like @search@";
	$params[2][] = "(".implode(" or ",$where).")";
  }
  return call_user_func(array($class, $function), $params, array("search"=>"%".$search."%", "ids"=>$ids, "page"=>$page));
}

static function chat_load($archive, $folder, $last, $room) {
  self::_require_access($folder, "read");
  $where = array("roomname=@room@",$_SESSION["permission_sql_read"]);
  $count = db_select_value("simple_chat","count(*) as count",$where,array("room"=>$room,"folder"=>$folder));
  if (empty($count)) exit("Access denied.");

  $where = array("room=@room@","id>@last@");
  $vars = array("room"=>$room,"last"=>$last);
  if (!$archive) {
    $where[] = "created>@created@";
	$vars["created"] = strtotime("today 00:00");
  }
  return db_select("simple_sys_chat2",array("id","message","createdby","bgcolor"),$where,"created asc","",$vars);
}

static function chat_add($folder, $room, $message) {
  self::_require_access($folder, "write");
  $where = array("roomname=@room@",$_SESSION["permission_sql_write"]);
  $count = db_select_value("simple_chat","id",$where,array("room"=>$room,"folder"=>$folder));
  if (empty($count)) exit("Access denied.");
  
  $id = sql_genID("simple_sys_chat2")*100+$_SESSION["serverid"];
  return db_insert("simple_sys_chat2", array("id"=>$id, "room"=>$room, "message"=>$message, "bgcolor"=>substr(sha1($_SESSION["username"]),0,6)));
}

static function tree_open($id) {
  if (empty($id) or !self::_tree_open_session($id)) return array();
  $sel_folder = folder_build_selfolder($id, "");
  $children = db_get_children($sel_folder);
  return array("level"=>$sel_folder["flevel"], "children"=>$children);
}

static function folder_add_offline($folder,$view,$folder_name) {
  $offline_folder = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"offline_".$_SESSION["username"]));
  if (empty($offline_folder)) exit(sprintf("Item not found. (%s)","Offline folders"));

  // TODO2 parameters for calendar, only future events ?
  $url = "index.php?folder=".rawurlencode($folder)."&view=".$view."&iframe=1&markdate=all&session_remove_request";

  $duplicate = db_select_value("simple_offline","id",array("folder=@folder@","url=@url@"),array("url"=>$url,"folder"=>$offline_folder));
  if (!empty($duplicate)) exit(sprintf("Variable already exists in table. (%s)","url=".$url));

  $id = sql_genID("simple_offline")*100+$_SESSION["serverid"];
  $data = array("id"=>$id, "url"=>$url, "folder"=>$offline_folder, "bookmarkname"=>$folder_name);

  $error_sql = db_insert("simple_offline",$data);
  if ($error_sql=="") {
    db_update_treesize("simple_offline",$offline_folder);
	db_search_update("simple_offline",$id,array(),array("url"=>"text", "bookmarkname"=>"text"));
	sys_log_stat("new_records",1);
  }
}

static function folder_options($folder) {
  self::_require_access($folder, "write");
  self::_smarty_init();

  $sel_folder = folder_build_selfolder($folder,"");
  sys::$smarty->assign( array(
  	"sys_schemas" => select::modules(),
  	"sys_icons" => select::icons_modules(),
	"isdbfolder" => is_numeric($folder),
	"folder" => array(
	  "name"=>$sel_folder["ftitle"], "description"=>$sel_folder["fdescription"],
	  "type"=>$sel_folder["ftype"], "assets"=>$sel_folder["fcount"], "icon"=>$sel_folder["icon"],
	  "notification"=>$sel_folder["notification"], "id"=>$folder
	),
  ) );
  return sys::$smarty->fetch("ajax_folder_options.tpl");
}

static function folder_mountpoint($folder) {
  self::_require_access($folder, "write");
  self::_smarty_init();
  $sel_folder = folder_build_selfolder($folder,"");
  sys::$smarty->assign("mountpoint", $sel_folder["fmountpoint"]);
  sys::$smarty->assign("mountpoints", select::mountpoints());
  return sys::$smarty->fetch("ajax_folder_mountpoint.tpl");
}

static function folder_info($folder) {
  self::_require_access($folder, "read");
  self::_smarty_init();
  $sel_folder = folder_build_selfolder($folder,"");
  
  if (!is_numeric($folder)) {
	$vars = sys_parse_folder($folder);
	$handler = "lib_".$vars["handler"];
	$values = call_user_func(array($handler,"folder_info"),$vars["mountpoint"],$vars["mfolder"]);
	$sel_folder = array_merge($sel_folder, $values);
  }
  $info = array("Name"=>$sel_folder["ftitle"], "Type"=>ucfirst($sel_folder["ftype"]),
  			   "Level"=>$sel_folder["flevel"], "Quota"=>modify::filesize($sel_folder["quota"]["quota"]),
			   "Quota (remaining)"=>modify::filesize($sel_folder["quota"]["remain"]),
			   "Folders"=>$sel_folder["ffcount"], "Size"=>modify::filesize($sel_folder["fsizecount"]),
			   "Size (children)"=>modify::filesize($sel_folder["fchsizecount"]), "Assets"=>$sel_folder["fcount"],
			   "Assets (children)"=>$sel_folder["fchcount"]);
  
  sys::$smarty->assign("info", $info);
  return sys::$smarty->fetch("ajax_folder_info.tpl");
}

static function folder_create($folder, $title, $type, $description, $icon, $first=false) {
  if ($title=="") return "";

  if (!is_numeric($folder) and strpos($folder,"*")) {
    $folders = folders_from_path($folder);
	if (!is_array($folders) or count($folders)==0) return "";
	foreach ($folders as $folder_item) self::_require_access($folder_item, "write");
	
    foreach ($folders as $folder_item) {
      self::folder_create($folder_item, $title, $type, $description, $icon, $first);
    }
	return $folder.$title."/";
  }
  self::_require_access($folder, "write");
  if (!is_numeric($folder)) {
	$url = sys_parse_folder($folder);
	$handler = "lib_".$url["handler"];

	self::require_method("create_folder", $handler);	
	$title = str_replace(array(".","\\","/"),"",$title);
	$return = call_user_func(array($handler,"create_folder"),$title,$url["mountpoint"],$url["mfolder"]);
	if ($return=="ok") return $folder.$title."/";
	  else if ($return!="") exit($return);
  } else {
    $new_folder = folders::create($title,$type,$description,$folder,$first,array("noduplicate"=>true,"icon"=>$icon));
    if ($new_folder=="") exit("Folder already exists.");
	if ($folder != $new_folder) sys_log_stat("new_folders",1);
	return $new_folder;
  }
  return "";
}

static function folder_rename($folder, $title, $type, $description, $icon, $notification) {
  if ($title=="") return "";
  self::_require_access($folder, "write");
  if (!is_numeric($folder)) {
	$url = sys_parse_folder($folder);
	$handler = "lib_".$url["handler"];

	self::require_method("rename_folder", $handler);
	$title = str_replace(array(".","\\","/"),"",$title);
	$return = call_user_func(array($handler,"rename_folder"),$title,$url["mountpoint"],$url["mfolder"]);
	if ($return=="ok") return dirname($folder)."/".$title."/";
	  else if ($return!="") exit($return);
  } else {
	$row = db_select_first("simple_sys_tree",array("notification","ftype"),"id=@id@","",array("id"=>$folder));
	if (empty($row["ftype"])) exit("Folder not found.");
	if ($notification!="" and ($notification!=$row["notification"] or $type!=$row["ftype"])) {
	  $schema = db_get_schema(sys_find_module($type));
	  if (!empty($schema["att"]["ENABLE_ASSET_RIGHTS"]) and $schema["att"]["ENABLE_ASSET_RIGHTS"]!="owner_write") {
		self::_require_access($folder, "admin");
	  }
	}
	$result = folders::rename($folder,$title,$type,$description,$icon,trim($notification));
    if ($result=="") exit("Folder already exists.");
	return $folder;
  }
  return "";
}

static function folder_set_mountpoint($folder, $mountpoint) {
  if (!is_numeric($folder)) return "";
  self::_require_access($folder, "write");
  if (MOUNTPOINT_REQUIRE_ADMIN or preg_match("/%username%|%password%/",$mountpoint)) self::_require_access($folder, "admin");
  $mps = select::mountpoints();
  $url = sys_parse_folder($mountpoint);
  if (empty($url["handler"])) {
	$mountpoint = "";
  } else if (!isset($mps["sys_nodb_".$url["handler"]])) {
    exit("Access denied.");
  }
  db_update("simple_sys_tree",array("fmountpoint"=>$mountpoint),array("id=@id@"),array("id"=>$folder));
  return $folder;
}

static function folder_applyrights($folder) {
  if (!is_numeric($folder)) return "";
  self::_require_access($folder, "admin");

  $rights = array("rread_users","rread_groups","rwrite_users","rwrite_groups",
  			"radmin_users","radmin_groups","rexception_users","rexception_groups");
  $data = array();
  $row = db_select_first("simple_sys_tree",array_merge(array("id","lft","rgt"),$rights),"id=@id@","",array("id"=>$folder));
  if (!empty($row["lft"])) {
    foreach ($rights as $right) $data[$right] = $row[$right];
	$permission = str_replace("@right@","admin",$_SESSION["permission_sql"]);
	db_update("simple_sys_tree",$data,array("(lft between @left@+1 and @right@-1)",$permission),array("left"=>$row["lft"],"right"=>$row["rgt"]));
  }
  return count($data)>0;
}

static function folder_delete($folder) {
  self::_require_access($folder, "write");
  self::tree_close($folder);
  if (!is_numeric($folder)) {
	$url = sys_parse_folder($folder);
	$handler = "lib_".$url["handler"];
	
	self::require_method("delete_folder", $handler);
	$return = call_user_func(array($handler,"delete_folder"),$url["mountpoint"],$url["mfolder"]);
	if ($return=="ok") return dirname($folder)."/";
	  else if ($return!="") exit($return);
  } else {
    return folders::delete($folder);
  }
  return "";
}

static function folder_moveup($folder) {
  if (!is_numeric($folder)) return $folder;
  self::_require_access($folder, "write");
  folders::moveupdown("up",$folder);
  return $folder;
}

static function folder_movedown($folder) {
  if (!is_numeric($folder)) return $folder;
  self::_require_access($folder, "write");
  folders::moveupdown("down",$folder);
  return $folder;
}

static function folder_cut($folder) {
  self::_require_access($folder, "write");
  $_SESSION["ccp_folder"] = array("cut"=>$folder);
  self::session_save();
}

static function folder_copy($folder) {
  self::_require_access($folder, "write");
  $_SESSION["ccp_folder"] = array("copy"=>$folder);
  self::session_save();
}

static function folder_paste($folder) {
  if (empty($_SESSION["ccp_folder"])) exit("Item not found.");
  $source = implode("",$_SESSION["ccp_folder"]);
  self::_require_access($source, "write");
  self::_require_access($folder, "write");
  if (isset($_SESSION["ccp_folder"]["cut"])) {
    if (!folders::move($source, $folder)) exit("The folder cannot be moved.");
  } else {
    if (!folders::copy($source, $folder)) exit("The folder cannot be copied.");
  }
  unset($_SESSION["ccp_folder"]);
  self::session_save();
  return $folder;
}

static function folder_ccp($source, $target, $operation) {
  self::_require_access($source, "write");
  self::_require_access($target, "write");
  
  if ($operation=="cut") {
    if (!folders::move($source, $target)) exit("The folder cannot be moved.");
  } else {
    if (!folders::copy($source, $target)) exit("The folder cannot be copied.");
  }
  return $source;
}

static function tree_set_folders($folder,$folders) {
  if (!is_numeric($folder)) return "";
  if (!is_array($folders) or count($folders)==0) $folders = array();
  self::_require_access($folder, "write");
  $folders = "|".implode("|",array_merge(array($folder),$folders))."|";
  db_update("simple_sys_tree",array("folders"=>$folders),array("id=@id@"),array("id"=>$folder));
  return $folder;
}

static function tree_get_category($type,$folder,$folders) {
  $where = array("ftype=@type@","id!=@id@",$_SESSION["permission_sql_read"]);
  $vars = array("type"=>$type,"id"=>$folder);
  $rows = db_select("simple_sys_tree","id",$where,"lft asc","200",$vars);
  if (is_array($rows) and count($rows)>0) {
    foreach ($rows as $key=>$row) {
	  $rows[$key]["path"] = modify::getpath($row["id"]);
    }
  }
  self::_smarty_init();
  sys::$smarty->assign( array(
    "items" => $rows,
	"folder" => $folder,
	"folders" => $folders,
	"style" => $_SESSION["style"]
  ) );
  return sys::$smarty->fetch("ajax_folder_categories.tpl");
}

static function tree_close($folder) {
  if (!isset($_SESSION["folder_states"][$folder])) return;
  foreach ($_SESSION["folder_states"][$folder] as $child) {
	unset($_SESSION["folder_states"][$child]);
  }
  unset($_SESSION["folder_states"][$folder]);
  self::session_save();
}

static function require_method($func,$handler="ajax") {
  if (!method_exists($handler, $func)) {
    exit(sprintf("Function does not exist: %s","ajax::".$func));
  }
}

private static function _tree_open_session($item) {
  if (is_numeric($item)) {
	$where = array("id=@id@",$_SESSION["permission_sql_read"]);
	$item_arr = db_select_first("simple_sys_tree",array("id","lft","rgt"),$where,"lft asc",array("id"=>$item));
  } else $item_arr = array("id"=>$item);
  if (empty($item_arr["id"])) return false;

  $_SESSION["folder_states"][$item] = array(1);    
  $parents = db_get_parents($item_arr);
  if (is_array($parents) and count($parents)>0) {
	foreach ($parents as $parent) {
	  $id = $parent["id"];
	  if (!isset($_SESSION["folder_states"][$id]) or !in_array($item,$_SESSION["folder_states"][$id])) {
	    $_SESSION["folder_states"][$id][] = $item;
  } } }
  self::session_save();
  return true;
}

private static function _smarty_init() {
  require("lib/smarty/Smarty.class.php");
  sys::$smarty = new Smarty;
  sys::$smarty->compile_dir = SIMPLE_CACHE."/smarty";
  sys::$smarty->template_dir = "templates";
  sys::$smarty->config_dir = "templates";
  sys::$smarty->compile_check = false;
  sys::$smarty->register_prefilter(array("ajax","urladdon_quote"));
}

static function urladdon_quote($var) {
  return preg_replace("/\{\\\$(.*?)\}/","{\$\\1|modify::htmlquote}",$var);
}

static function session_save() {
  static $saved = false;
  if (ini_get("suhosin.session.encrypt") or $saved) return;
  if (APC_SESSION) {
	apc_store("sess".session_id(), session_encode(), LOGIN_TIMEOUT);
  } else {
	$data = array("username"=>$_SESSION["username"], "data"=>rawurlencode(session_encode()));
	db_update("simple_sys_session",$data,array("id=@id@"),array("id"=>session_id()));
  }
  $saved = true;
}

protected static function _require_access(&$folder, $right="read", $view="") {
  // /Workspace/ => 101
  $folder = folder_from_path($folder);

  if (!db_get_right($folder,$right,$view)) {
	if ($right == "read") $right = "read access";
	if ($right == "write") $right = "write access";
	if ($right == "admin") $right = "admin access";
    exit("Access denied. ".sprintf("missing right: %s",$right." (".$folder.")"));
  }
}

}