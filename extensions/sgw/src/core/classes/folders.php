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

class folders {

static $rights = array("rread_users","rread_groups","rwrite_users","rwrite_groups",
	   "radmin_users","radmin_groups","rexception_users","rexception_groups");

static function create($ftitle,$ftype,$fdescription,$parent,$first,$optional=array()) {
  if ($ftype=="") $ftype = "blank";
  if (!array_key_exists($ftype,select::modules())) $ftype = "blank";
  $ftitle = ltrim($ftitle, "!^/");
  
  $levels = array(1953125,390625,78125,15625,625,125);
  db_lock_tree(true);
  if (isset($optional["root"]) and $optional["root"]) {
    $row = array("lft"=>-10000000,"rgt"=>10000000,"flevel"=>-1,"id"=>0);
  } else {
    $row = db_select_first("simple_sys_tree","*","id=@id@","",array("id"=>$parent));
  }
  $result = "";
  if (isset($row["lft"])) {
    $duplicate = db_select_value("simple_sys_tree","id",array("parent=@parent@","ftitle=@ftitle@"),array("ftitle"=>$ftitle,"parent"=>$row["id"]));
    if (empty($duplicate)) {
	  $parent = $row["id"];
	  $level = $row["flevel"]+1;
	  $children_count = $levels[$level%5];

	  // TODO2 sort alphabetically ?
	  /*	  
	  $last_child = db_select_first("simple_sys_tree",array("ftitle","id","lft","rgt"),array("parent=@parent@","ftitle<@ftitle@"),"lft desc",array("parent"=>$row["id"], "ftitle"=>$ftitle));
	  if (isset($last_child["id"])) {
		$row["lft"] = $last_child["rgt"];
		$row["rgt"] = $row["lft"];
	  } else {
		$first_child = db_select_first("simple_sys_tree",array("id","lft","rgt"),"parent=@parent@","lft asc",array("parent"=>$parent));
	    if (isset($first_child["id"])) $row["rgt"] = $first_child["lft"]-1; else $row["lft"] = $row["lft"]+floor(($row["rgt"]-$row["lft"])/4);
	  }
	  */

	  if (!$first) { // last child
        $last_child = db_select_first("simple_sys_tree",array("ftitle","id","lft","rgt"),"parent=@parent@","lft desc",array("parent"=>$row["id"]));
	    if (isset($last_child["id"])) {
		  $row["lft"] = $last_child["rgt"];
		} else {
		  $row["lft"] = $row["lft"]+floor(($row["rgt"]-$row["lft"])/4);
		}
	  } else { // child
        $first_child = db_select_first("simple_sys_tree",array("id","lft","rgt"),"parent=@parent@","lft asc",array("parent"=>$parent));
	    if (isset($first_child["id"])) {
		  $row["rgt"] = $first_child["lft"]-1;
		} else {
		  $row["lft"] = $row["lft"]+floor(($row["rgt"]-$row["lft"])/4);
		}
  	  }
	  $left = $row["lft"]+1;
	  $right = $left+1+$children_count;
	  $children_count += 2;
	  if ($right >= $row["rgt"]) {
        db_update("simple_sys_tree",array("rgt"=>"rgt+".$children_count),array("rgt>=@left@"),array("left"=>$left),array("quote"=>false, "no_defaults"=>true));
	    db_update("simple_sys_tree",array("lft"=>"lft+".$children_count),array("lft>=@left@"),array("left"=>$left),array("quote"=>false, "no_defaults"=>true));
	  }
      $id = sql_genID("simple_sys_tree")*100+$_SESSION["serverid"];
	  $data = array(
	    "lft"=>$left, "rgt"=>$right, "ftitle"=>$ftitle, "ftype"=>$ftype, "fdescription"=>$fdescription, "id"=>$id, "folder"=>$id, "flevel"=>$level, "parent"=>$parent,
		"fquota"=>0, "history"=>sprintf("{t}Item created by %s at %s{/t}",$_SESSION["username"],sys_date("{t}m/d/y g:i:s a{/t}"))
	  );
	  foreach ($row as $row_key=>$row_val) if (!isset($data[$row_key]) and $row_key[0]=="r") $data[$row_key] = $row_val; // get rights from parent
	  if (!empty($optional["mountpoint"])) $data["fmountpoint"] = str_replace("\\","/",$optional["mountpoint"]);
	  if (!empty($optional["anchor"])) $data["anchor"] = $optional["anchor"];
	  if (!empty($optional["icon"])) $data["icon"] = $optional["icon"];
	  if (!empty($optional["rights"])) $data = array_merge($data,$optional["rights"]);
	  if (!empty($optional["default_values"])) $data["default_values"] = $optional["default_values"];
      $error_sql = db_insert("simple_sys_tree",$data);
      if ($error_sql=="") {
	    db_search_update("simple_sys_tree",$id,array(),_folder_searchtypes());
		db_update_subfolder_count($parent);
	  }
      $result = $id;
    } else if (!isset($optional["noduplicate"])) $result = $duplicate;
  }
  db_lock_tree(false);
  return $result;
}

static function rename($folder,$ftitle,$type,$description=null,$icon=null,$notification=null) {
  if (!array_key_exists($type,select::modules())) $type = "";
  $ftitle = ltrim($ftitle, "!^/");

  $row = db_select_first("simple_sys_tree",array("id","lft","rgt","parent"),"id=@id@","",array("id"=>$folder));
  if (isset($row["lft"])) {
    $duplicate = db_select_value("simple_sys_tree","id",array("id!=@id@","parent=@parent@","ftitle=@ftitle@"),array("id"=>$row["id"],"parent"=>$row["parent"],"ftitle"=>$ftitle));
    if (empty($duplicate)) {
      $fields = array("ftitle"=>$ftitle);
      if ($type!="") $fields["ftype"] = $type;
      if ($description!==null) $fields["fdescription"] = $description;
      if ($icon!==null) $fields["icon"] = $icon;
      if ($notification!==null) $fields["notification"] = $notification;
      db_update("simple_sys_tree",$fields,array("id=@id@"),array("id"=>$folder));
      db_search_update("simple_sys_tree",$folder,array(),_folder_searchtypes());
	  return $folder;
    }
  }
  return "";
}

static function create_default_folders($file,$parent,$checkcount,$data=array()) {
  if ($checkcount) {
    $count = db_count("simple_sys_tree",array(),array());
    if ($count>0) return $parent;
  }
  $xml = sys_get_xml(sys_custom($file), $data);
  return self::_create_default_folder($xml, $parent, $data);
}

static function delete($folder) {
  $row = db_select_first("simple_sys_tree",array("id","rgt","lft","ftitle","parent"),"id=@id@","",array("id"=>$folder));
  $rows = array();
  if (!empty($row["id"])) {
	$rows = db_select("simple_sys_tree",array("id","ftype"),"lft between @left@ and @right@","lft asc","",array("left"=>$row["lft"],"right"=>$row["rgt"]));
  }
  if (!is_array($rows) or count($rows)==0) return "";
  if (!folder_in_trash($folder)) {
	$trash = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"trash"));
	if (empty($trash)) exit("{t}Error{/t}: {t}Trash folder not found.{/t}");
	$id = self::create(sys_date("{t}m/d/Y{/t}"),"blank","",$trash,true);
	$old_path = modify::getpath($folder);
	if (!self::move($row["id"],$id,true)) exit("{t}The folder cannot be deleted.{/t}");
	  
	$data = array("rread_users"=>"", "rread_groups"=>"", "rwrite_users"=>"", "rwrite_groups"=>"",
	  "radmin_users"=>"", "radmin_groups"=>"", "rexception_users"=>"", "rexception_groups"=>"", "anchor"=>"");
	$data["history"] = sprintf("{t}Item deleted by %s at %s{/t}\n",$_SESSION["username"],sys_date("{t}m/d/y g:i:s a{/t}"));

   	foreach ($rows as $folder) {
	  db_update("simple_sys_tree",$data,array("id=@id@"),array("id"=>$folder["id"]));
	}
	db_update("simple_sys_tree",array("history"=>"{t}Origin{/t}: ".$old_path."\n"),array("id=@id@"),array("id"=>$rows[0]["id"]));

	sys_log_stat("deleted_folders",count($rows));
  } else {
  	foreach ($rows as $folder) {
	  if ($folder["ftype"]!="sys_tree") {
		$schema_data = db_get_schema(sys_find_module($folder["ftype"]));
		$tname = $schema_data["att"]["NAME"];
		if (!strpos($tname,"_nodb_")) {
		  $delete_fields = array();
		  foreach ($schema_data["fields"] as $key=>$field) if ($field["SIMPLE_TYPE"]=="files") $delete_fields[] = $key;
		  if (count($delete_fields)>0) {
		    $data = db_select($tname,$delete_fields,"folder=@folder@","created asc","",array("folder"=>$folder["id"]));
			if (is_array($data) and count($data)>0) {
			  foreach ($data as $ditem) {
			    foreach ($delete_fields as $field) {
				  $files = explode("|",$ditem[$field]);
				  sys_unlink($files);
		  } } } }
		  db_delete($tname,array("folder=@folder@"),array("folder"=>$folder["id"]));
		}
	  }
	  db_search_delete("simple_sys_tree",$folder["id"],$folder["id"]);
	  db_search_delete_folder($folder["id"]);
	  db_delete("simple_sys_tree",array("id=@id@"),array("id"=>$folder["id"]));
	}
	db_update_subfolder_count($row["parent"]);
  }
  
  $folder = $row["id"];
  if (isset($_SESSION["folder_states"][$folder])) {
	foreach ($_SESSION["folder_states"][$folder] as $child) {
	  unset($_SESSION["folder_states"][$child]);
	}
	unset($_SESSION["folder_states"][$folder]);
  }
  return $row["parent"];
}

public static function copy($source,$folder) {
  if (empty($source) or empty($folder)) return false;
  if (!is_numeric($folder) and $pos = strpos($folder,":")) {
    $handler = "lib_".substr($folder,0,$pos);
	if (method_exists($handler,"copy_folder")) return call_user_func(array($handler,"copy_folder"),$folder);
	return false;
  }
  db_lock_tree(true);
  $source = db_select_first("simple_sys_tree",array("id","lft","rgt","ftitle","parent"),"id=@id@","",array("id"=>$source));
  $target = db_select_first("simple_sys_tree",array("id","lft","rgt"),"id=@id@","",array("id"=>$folder));

  $success = false;
  if (!empty($source["lft"]) and !empty($target["lft"]) and !($source["lft"]<=$target["lft"] and $source["rgt"]>=$target["rgt"])) {
    $source_folders = db_select("simple_sys_tree","*","lft between @left@ and @right@","lft asc","",array("left"=>$source["lft"],"right"=>$source["rgt"]));
	$parent_mapping = array($source["parent"] => $target["id"]);
	foreach ($source_folders as $source_folder) {
	  $data = array(
		"noduplicate"=>true,
		"nolock"=>true,
		"mountpoint"=>$source_folder["fmountpoint"],
		"rights"=>array_intersect_key($source_folder,self::$rights)
	  );
	  if (!isset($parent_mapping[$source_folder["parent"]])) continue;

	  $step = 1;
	  $parent = "";
	  $val = $source_folder["ftitle"];
	  while ($step<100 and empty($parent)) {
		$step++;
		if ($parent_mapping[$source_folder["parent"]] == $folder) $first = true; else $first = false;
		$parent = self::create($val,$source_folder["ftype"],$source_folder["fdescription"],$parent_mapping[$source_folder["parent"]],$first,$data);
		$val = $source_folder["ftitle"]."_".$step;
	  }
	  if (!empty($parent)) $parent_mapping[$source_folder["id"]] = $parent;
	}
	foreach ($source_folders as $source_folder) {
	  if (!isset($parent_mapping[$source_folder["id"]])) continue;
	  $ccp_data = asset_ccp::cutcopy_items($source_folder["id"], "display", array(), "copy");
	  if ($ccp_data) asset_ccp::paste_items($parent_mapping[$source_folder["id"]], $ccp_data);
	}
	db_update_subfolder_count($target["id"]);
	$success = true;
  }
  db_lock_tree(false);
  return $success;
}

public static function move($source,$folder) {
  if (empty($source) or empty($folder)) return false;
  if (!is_numeric($folder) and $pos = strpos($folder,":")) {
    $handler = "lib_".substr($folder,0,$pos);
	if (method_exists($handler,"move_folder")) return call_user_func(array($handler,"move_folder"),$folder);
	return false;
  }
  $source = db_select_first("simple_sys_tree",array("id","lft","rgt","flevel","ftitle","parent"),"id=@id@","",array("id"=>$source));
  if (empty($source["parent"]) or $source["parent"]==$folder) return false;
  
  db_lock_tree(true);
  $target = db_select_first("simple_sys_tree",array("id","lft","rgt","flevel"),"id=@id@","",array("id"=>$folder));

  $success = false;
  $duplicate = "1";
  $new_title = $source["ftitle"];
  if (!empty($source["lft"]) and !empty($target["lft"]) and !($source["lft"]<=$target["lft"] and $source["rgt"]>=$target["rgt"])) {
	$step = 1;
	while ($step<100) {
	  $step++;
      $duplicate = db_select_value("simple_sys_tree","id",array("parent=@parent@","ftitle=@ftitle@"),array("ftitle"=>$new_title,"parent"=>$target["id"]));
	  if (empty($duplicate)) break;
	  $new_title = $source["ftitle"]."_".$step;
	}
  }
  if (empty($duplicate)) {
	$ids = db_select("simple_sys_tree","id","lft between @left@ and @right@","","",array("left"=>$source["lft"],"right"=>$source["rgt"]));
	if (is_array($ids) and count($ids)>0) {
	  foreach ($ids as $cid) unset($_SESSION["folder_states"][$cid["id"]]);
	}
    $last_child = db_select_first("simple_sys_tree",array("id","rgt"),"parent=@parent@","lft desc",array("parent"=>$target["id"]));
	if (isset($last_child["id"])) $left = $last_child["rgt"]; else $left = $target["lft"];
	$right = $target["rgt"];

	if (($source["rgt"]-$source["lft"]+1) >= ($right-$left)) {
	  $diff = ($source["rgt"] - $source["lft"]) * 2;
      db_update("simple_sys_tree",array("rgt"=>"rgt+".$diff),array("rgt>=@right@"),array("right"=>$right),array("quote"=>false, "no_defaults"=>true));
	  db_update("simple_sys_tree",array("lft"=>"lft+".$diff),array("lft>=@right@"),array("right"=>$right),array("quote"=>false, "no_defaults"=>true));
	  $right += $diff;
	  $source = db_select_first("simple_sys_tree",array("id","lft","rgt","flevel","parent"),"id=@id@","",array("id"=>$source["id"]));
	}
	$diff = floor(($right - $left - $source["rgt"] + $source["lft"]) / 2) + $left - $source["lft"];
	$level_diff = $target["flevel"] - $source["flevel"] + 1;

	if ($diff < 0) $diff = "-".abs($diff); else $diff = "+".$diff;
	if ($level_diff < 0) $level_diff = "-".abs($level_diff); else $level_diff = "+".$level_diff;
    db_update("simple_sys_tree",array("parent"=>$target["id"],"ftitle"=>$new_title),array("id=@id@"),array("id"=>$source["id"]));
	db_update("simple_sys_tree",array("lft"=>"lft".$diff,"rgt"=>"rgt".$diff,"flevel"=>"flevel".$level_diff),array("lft between @left@ and @right@"),array("left"=>$source["lft"],"right"=>$source["rgt"]),array("quote"=>false));
    db_update_subfolder_count($source["parent"]);
    db_update_subfolder_count($target["id"]);
	$success = true;
  }
  db_lock_tree(false);
  return $success;
}

public static function moveupdown($fmoveupdown,$path) {
  db_lock_tree(true);
  $row = db_select_first("simple_sys_tree",array("id","lft","rgt","flevel","parent"),"id=@id@","",array("id"=>$path));
  if (isset($row["lft"])) {
    if ($fmoveupdown == "up") {
      $lower = $row;
      $upper = db_select_first("simple_sys_tree",array("id","lft","rgt"),array("parent=@row_parent@","lft<@row_lft@"),"lft desc",array("row_parent"=>$row["parent"],"row_lft"=>$row["lft"]));
    }
    if ($fmoveupdown == "down") {
      $upper = $row;
      $lower = db_select_first("simple_sys_tree",array("id","lft","rgt"),array("parent=@row_parent@","lft>@row_rgt@"),"lft asc",array("row_parent"=>$row["parent"],"row_rgt"=>$row["rgt"]));
    }
    if (isset($lower["lft"]) and isset($upper["lft"])) {
      $distance = $lower["lft"]-$upper["lft"];
      $distance2 = $lower["rgt"]-$upper["rgt"];

	  $lowers = db_select("simple_sys_tree","id","lft between @lft@ and @rgt@","lft desc","",array("lft"=>$lower["lft"],"rgt"=>$lower["rgt"]));
	  $uppers = db_select("simple_sys_tree","id","lft between @lft@ and @rgt@","lft desc","",array("lft"=>$upper["lft"],"rgt"=>$upper["rgt"]));

	  if (is_numeric($distance) and $distance!=0 and is_array($lowers) and count($lowers)>0) {
	    foreach ($lowers as $lowers_item) db_update("simple_sys_tree",array("lft"=>"lft-".$distance,"rgt"=>"rgt-".$distance),array("id=@id@"),array("id"=>$lowers_item["id"]),array("quote"=>false, "no_defaults"=>true));
	  }
	  if (is_numeric($distance2) and $distance2!=0 and is_array($uppers) and count($uppers)>0) {
	    foreach ($uppers as $uppers_item) db_update("simple_sys_tree",array("lft"=>"lft+".$distance2,"rgt"=>"rgt+".$distance2),array("id=@id@"),array("id"=>$uppers_item["id"]),array("quote"=>false, "no_defaults"=>true));
  } } }
  db_lock_tree(false);
}

private static function _create_default_folder($xml, $parent, $data_full) {
  $attrs = $xml->attributes();
  if (!isset($attrs["type"])) $attrs["type"] = "blank";

  $addon = (array)$attrs;
  $addon = array_shift($addon);
  foreach (self::$rights as $right) {
    if (isset($attrs[$right])) {
	  if ($attrs[$right]!="") $addon["rights"][$right] = "|".$attrs[$right]."|"; else $addon["rights"][$right] = "";
	}
  }
  if ($parent==0) $addon["root"] = true;
  $folder = self::create($attrs["name"],(string)$attrs["type"],str_replace("\\n","\n",$attrs["description"]),$parent,false,$addon);

  $data_full["parent"] = $parent;
  $data_full["folder"] = $folder;
  
  if (!empty($attrs["data"])) { // data=filename
    self::_create_default_folder_xml_data(sys_get_xml($attrs["data"], $data_full), $folder);
  }
  if (isset($xml->assets)) { // <assets><asset>...
    self::_create_default_folder_xml_data($xml->assets, $folder);
  }
  if (isset($xml->folder)) {
    foreach ($xml->folder as $xml_folder) self::_create_default_folder($xml_folder, $folder, $data_full);
  }
  return $folder;
}

public static function import_data($file, $folder, $data=array()) {
  self::_create_default_folder_xml_data(sys_get_xml($file, $data), $folder);
}

private static function _create_default_folder_xml_data($xml, $folder) {
  $assets = sys_array_shift(get_object_vars($xml));
  if (is_object($assets)) $assets = array($assets);  
  if (!is_array($assets) or count($assets)==0) return;
  @set_time_limit(60);

  $sgsml = new sgsml($folder, "new");
  $sgsml->notification = false;

  foreach ($assets as $asset) {
	$data = get_object_vars($asset);
	if (isset($data["@attributes"])) unset($data["@attributes"]);
  	$result = $sgsml->insert($data);

	if (DEBUG and !is_int($result)) print_r($result);
	if (DEBUG) echo " @".memory_get_usage(true);
	sys::$cache = array();
	sys::$db_queries = array();
  }
}

}