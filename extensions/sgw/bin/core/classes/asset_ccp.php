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

// ccp = cut-copy-paste
class asset_ccp {

static function cutcopy_items($folder, $view, $items, $operation) {
  if (!is_array($items)) return array();

  $writeable = ($operation=="cut");
  $sgsml = new sgsml($folder, $view, $items, $writeable);

  if (!isset($sgsml->buttons[$operation])) return array();
  $tname = $sgsml->tname;
  
  $rows = $sgsml->get_rows(array("id"));
  if (is_array($rows) and count($rows)==0 and count($items)==0) return array();
  if (!is_array($rows) or count($rows)==0 or count($rows) < count($items)) exit("Item(s) not found or access denied.");
  $items = array();
  foreach ($rows as $row) $items[] = $row["id"];

  $unique_fields = array();
  $file_fields = array();
  $data_fields = array();
  foreach ($sgsml->fields as $field) {
	if (isset($field["DISABLE_CCP"])) continue;
	if (isset($field["KEY"]) or isset($field["IS_UNIQUE"])) $unique_fields[] = $field["NAME"];
	if ($field["SIMPLE_TYPE"]=="files") $file_fields[] = $field["NAME"];
	$data_fields[] = $field["NAME"];
  }

  $delete_mode = isset($sgsml->buttons["delete"])?"delete":"purge";
  return array("operation"=>$operation, "tname"=>$tname, "custom_name"=>$sgsml->att["CUSTOM_NAME"],
    "default_sql"=>$sgsml->current_view["DEFAULT_SQL"], "handler"=>$sgsml->handler, "items"=>$items, "folder"=>$folder,
	"folders"=>$sgsml->vars["folders"], "unique_fields"=>$unique_fields, "file_fields"=>$file_fields, "delete_mode"=>$delete_mode,
	"data_fields"=>$data_fields, "where"=>$sgsml->where, "vars_noquote"=>$sgsml->vars_noquote);
}

static function paste_items($folder, $ccp_data) {
  if (!is_array($ccp_data) or empty($ccp_data["items"])) return "";

  $sgsml = new sgsml($folder, "new");
  if (!isset($sgsml->buttons["paste"])) return "";
  
  $tname = $sgsml->tname;
  $sgsml->where = array("id in (@id@)", "folder in (@folders@)");

  $cut = ($ccp_data["operation"]=="cut");
  
  foreach ($ccp_data["folders"] as $key=>$value) {
    if ($value==$folder) continue;
	if (!db_get_right($value, $cut ? "write" : "read", "new")) unset($ccp_data["folders"][$key]);
  }

  if ($cut and $tname!=$ccp_data["tname"]) {
	$allowed = false;
	foreach (self::_get_mappings("->") as $mapping) {
	  if ("simple_".$mapping[0] == $ccp_data["tname"] and "simple_".$mapping[1] == $tname) {
		$allowed = true;
		break;
	  }
	}
	if (!$allowed) exit("Operation not allowed. (Data loss protection)");
	$messages = self::_copy($ccp_data, $folder, $sgsml, true);
  } else if ($cut and $tname==$ccp_data["tname"]) {
	$messages = self::_move($ccp_data, $folder, $sgsml);
  } else {
    $messages = self::_copy($ccp_data, $folder, $sgsml, false);
  }
  return implode("\n", $messages);
}

private static function _get_mappings($separator) {
  static $lines = array();
  
  if (count($lines)==0) {
	$content = file_get_contents(sys_custom("modules/schema/mappings.txt"));
	foreach (explode("\n", $content) as $line) {
	  if (!strpos($line, "=") or strpos($line, "->")) continue;
	  $mapping = explode("=", trim(str_replace("\r", "", $line)));

	  $replace = array();
	  foreach (explode(",", $mapping[1]) as $value) $replace[] = "\\1{$value}\\2";

	  // multiply alias occurrences
	  $content = preg_replace("/^(.*?)\{".$mapping[0]."\}(.*?)\$/m", implode("\n", $replace), $content);
	}
	$lines = explode("\n", $content);
  }
  $results = array();
  foreach ($lines as $line) {
    if (substr_count($line, $separator)!=1) continue;
	$results[] = explode($separator, trim(str_replace("\r", "", $line)));
  }
  return $results;
}
  
private static function _validate_value($tname, $field, $data, $key) {
  $error = array();
  if (in_array($field["SIMPLE_TYPE"],array("dateselect","select","files"))) {
	$data = explode("|",trim($data,"|"));
  }
  $key_name = isset($field["DISPLAYNAME"])?$field["DISPLAYNAME"]:$field["NAME"];
  if (isset($field["REQUIRED"]) and empty($data)) {
    $error[$key][] = "missing field [".$key_name."]";
  } else if (isset($field["VALIDATE"])) {
	foreach ($field["VALIDATE"] as $validate) {
	  list($class, $function, $params) = sys_find_callback("validate", $validate["FUNCTION"]);
	  foreach ((array)$data as $data_item) {
		if ($data_item!="" or isset($field["REQUIRED"])) {
		  $result = call_user_func(array($class, $function), $data_item, $params);
		  if ($result!="") $error[$key][] = $result." [".$key_name.",".$data_item."]";
  } } } }
  if (isset($field["KEY"]) or (isset($field["IS_UNIQUE"]) and !empty($data))) {
    if ($result = validate::itemexists($tname,array($key=>$data),-1) and $result!="") $error[$key][] = $result;
  }
  return $error;
}

private static function _paste_item_copyfile($file, $id, $tname) {
  list($target,$filename) = sys_build_filename(modify::basename($file),$tname);
  dirs_checkdir($target);
  $target .= sys_get_pathnum($id)."/";
  dirs_checkdir($target);
  $target .= $id.$filename;
  copy($file,$target);
  return $target;
}

private static function _restore_value($data, $fieldname, $restore_filters) {
  if (empty($restore_filters)) return $data[$fieldname];
  $value = explode("|", $data[$fieldname]);
  foreach ($restore_filters as $filter) {
	if (!empty($filter["VIEWS"]) and $filter["VIEWS"]!="all") continue;
	list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
	foreach (array_keys($value) as $key) {
	  $value[$key] = call_user_func(array($class, $function), $value[$key], $params, $data);
  } }
  return implode("|",$value);
}

private static function _move($ccp, $folder, $sgsml) {
  if (empty($sgsml->att["DISABLE_TRIGGER_CCP"]) and 
	 (!empty($sgsml->att["TRIGGER_NEW"]) or !empty($sgsml->att["TRIGGER_DELETE"]))) {
	$ccp["data_fields"] = array("*");
  } else if (in_array("notification",$ccp["data_fields"])) {
	$ccp["data_fields"] = array("id","folder","notification");
	foreach ($sgsml->fields as $key=>$field) {
	  if (isset($field["REQUIRED"]) and !in_array($key,$ccp["data_fields"])) {
		$ccp["data_fields"][] = $key;
	  }
	}
  } else $ccp["data_fields"] = array("id","folder");
  
  $vars = array("handler"=>$ccp["handler"],"sqlvarsnoquote"=>$ccp["vars_noquote"],"custom_name"=>$ccp["custom_name"],"default_sql"=>$ccp["default_sql"]);
  $rows = db_select($ccp["tname"],$ccp["data_fields"],$ccp["where"],"","",array("item"=>$ccp["items"],"folder"=>$ccp["folder"],"folders"=>$ccp["folders"]),$vars);
  if (is_array($rows) and count($rows) < count($ccp["items"])) exit("Item(s) not found or access denied.");

  $default_values = folder_get_default_values($folder);

  $messages = array();
  foreach ($rows as $row) {
	$folder_source = $row["folder"];
    if (empty($folder_source) or $folder_source==$folder or !db_get_right($folder_source,"write")) {
	  continue;
	}
	
	$sgsml->vars["id"] = array($row["id"]);
	$sgsml->vars["folders"] = array($folder_source);

	if (!empty($sgsml->att["DISABLE_TRIGGER_CCP"])) unset($sgsml->att["TRIGGER_EDIT"]);

	$result = 0;
	if (!is_numeric($folder)) {
	  $sgsml->vars["id"] = $row["id"];
	  $sgsml->vars["folder_source"] = $folder_source;
	  $sql_data = array_merge($default_values, array("folder"=>$folder));
	  $sql_data["history"] = sprintf("Item edited (%s) by %s at %s\n","@fields@",$_SESSION["username"],sys_date("m/d/y g:i:s a"));
	  $sql_data = $sgsml->build_history($sql_data, $row);
	  
	  $error_sql = db_update($sgsml->tname,$sql_data,array("id=@id@"),$sgsml->vars,array("handler"=>$sgsml->handler));
	  if ($error_sql!="") $messages[] = "SQL failed. ".$error_sql;

	  if (!empty($sgsml->att["TRIGGER_EDIT"]) and empty($sgsml->att["DISABLE_TRIGGER_CCP"])) {
		$return = asset_process_trigger($sgsml->att["TRIGGER_EDIT"],$row["id"],$row,$sgsml->tname);
		if ($return!="") $messages[] = "Trigger failed: ".$return;
	  }
	  if (!empty($row["notification"])) {
		$row["folder"] = $folder;
		$smtp_data = asset::build_notification($sgsml->tname,$sgsml->fields,$row,$sql_data,$row["id"]);
		$return = asset_process_trigger("sendmail",$row["id"],$smtp_data);
		if ($return!="") $messages[] = "Trigger failed: ".$return;
	  }
	} else {
	  $sql_data = array_merge($default_values, array("folder"=>$folder));
	  $result = $sgsml->update($sql_data, $row["id"]);
	}

	if (!is_numeric($result)) {
	  if (is_array($result) and count($result)>0) {
		$message = "Error pasting asset:";
		foreach ($result as $field=>$errors) {
		  foreach ($errors as $error) $message .= "\n".$error[0].": ".$error[1];
		}
	  } else {
	    $message = $result;
	  }
	  $messages[] = $message;
	} else {
	  sys_log_stat("moved_records",1);
	}
  }
  return $messages;
}

private static function _modifystore($store_arr,$value,$row) {
  if (!is_array($store_arr) or count($store_arr)==0) return $value;
  foreach ($store_arr as $store) {
	list($class, $function, $params) = sys_find_callback("modify", $store["FUNCTION"]);
	$value = call_user_func(array($class, $function), $value, $row, $params);
  }
  return $value;
}

private static function _copy($ccp, $folder, $sgsml, $delete) {
  $tname = $sgsml->tname;
  $vars = array("handler"=>$ccp["handler"],"sqlvarsnoquote"=>$ccp["vars_noquote"],"custom_name"=>$ccp["custom_name"],"default_sql"=>$ccp["default_sql"]);
  $rows = db_select($ccp["tname"],$ccp["data_fields"],$ccp["where"],"","",array("item"=>$ccp["items"],"folder"=>$ccp["folder"],"folders"=>$ccp["folders"]),$vars);
  if (is_array($rows) and count($rows) < count($ccp["items"])) exit("Item(s) not found or access denied.");
  
  foreach (self::_get_mappings("->") as $mapping) {
    if (!strpos($mapping[1],"=")) continue;
	$mapping[1] = explode(".",$mapping[1]);
	if ("simple_".$mapping[0]==$ccp["tname"] and "simple_".$mapping[1][0]==$tname) {
	  $sgsml->patch_fields(array_slice($mapping[1],1));
	}
  }
  $mappings = array();
  foreach (self::_get_mappings("|") as $mapping) {
    if (strpos($mapping[0],"->")) continue;
	$mapping1 = explode(".",$mapping[0]);
	$mapping2 = explode(".",$mapping[1]);
	$key = "simple_".$mapping1[0].".simple_".$mapping2[0];
	$mappings[$key][ $mapping2[1] ] = $mapping1[1];
	$key = "simple_".$mapping2[0].".simple_".$mapping1[0];
	$mappings[$key][ $mapping1[1] ] = $mapping2[1];
  }
  $default_values = folder_get_default_values($folder);

  $messages = array();
  foreach ($rows as $row) {
	if (empty($row["folder"]) or !db_get_right($row["folder"],"read")) continue;

	if (isset($mappings[$ccp["tname"].".".$tname])) {
	  foreach ($mappings[$ccp["tname"].".".$tname] as $to => $from) $row[$to] = $row[$from];
	}
	$row = array_merge($row, $default_values);
	
	foreach (array_keys($row) as $key) {
	  if (!isset($sgsml->fields[$key])) {
	    unset($row[$key]);
		continue;
	  }
	  $field = $sgsml->fields[$key];
	  if ((isset($field["KEY"]) or isset($field["IS_UNIQUE"])) and
		  !empty($row[$key]) and !isset($field["READONLYIN"])) {

		$val = $row[$key];
		$step = 1;
		while ($step<100 and validate::itemexists($tname, array($key=>$val), -1)!="") {
		  $step++;
		  $val = $row[$key]."_".$step;
		}
		$row[$key] = $val;
	  }
	  if (isset($field["RESTORE"])) $row[$key] = self::_restore_value($row, $key, $field["RESTORE"]);
	}
	
	$id = $row["id"];
	$row["id"] = -1;
	$row["folder"] = $folder;
	if (isset($row["syncid"])) $row["syncid"] = "";
	
	$newfiles = array();
	if (is_array($ccp["file_fields"]) and count($ccp["file_fields"])>0) {
	  foreach ($ccp["file_fields"] as $file_field) {
		if (!empty($row[$file_field])) {
		  $data_files = explode("|", trim($row[$file_field], "|"));
		  $row[$file_field] = array();
		  foreach ($data_files as $file) {
			if (!file_exists($file)) continue;
			$target = self::_paste_item_copyfile($file, $row["id"], $tname);
			$row[$file_field][] = $target;
			$newfiles[] = $target;
		  }
		  $row[$file_field] = implode("|", $row[$file_field]);
	} } }

	if (!empty($sgsml->att["DISABLE_TRIGGER_CCP"])) unset($sgsml->att["TRIGGER_NEW"]);
	
	$result = $sgsml->insert($row);
	if (!is_numeric($result)) {
	  if (is_array($result) and count($result)>0) {
		$message = "Error pasting asset:";
		foreach ($result as $field=>$errors) {
		  foreach ($errors as $error) $message .= "\n".$error[0].": ".$error[1];
		}
	  } else {
	    $message = $result;
	  }
	  $messages[] = $message;
	} else {
	  if ($delete) asset::delete_items($ccp["folder"],"display",array($id),$ccp["delete_mode"]);
	  sys_log_stat("copied_records",1);
	}
	foreach ($newfiles as $file) {
	  if (sys_strbegins($file, SIMPLE_CACHE."/upload/")) @unlink($file);
	}
  }
  return $messages;
}

}