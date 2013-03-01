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

class sgsml {

function __construct($folder,$view,$items=array(),$writeable=true) {

  // Mountpoint
  $folders = array($folder);
  if (!is_numeric($folder)) {
 	$url = sys_parse_folder($folder);
	$type = "sys_nodb_".$url["handler"];
	$mfolder = $url["mfolder"];
	sys_credentials($mfolder);
  } else {
    $row = db_select_first("simple_sys_tree",array("ftype","folders"),"id=@id@","",array("id"=>$folder));
    if (empty($row["ftype"])) throw new Exception("{t}Folder not found.{/t}");
	$type = $row["ftype"];
	if ($row["folders"]!="") {
	  $folders = array();
	  foreach (explode("|",trim($row["folders"],"|")) as $val) {
		if (empty($val) or !db_get_right($val, $writeable ? "write" : "read", $view)) continue;
		$folders[] = $val;
	  }
	}
	$mfolder = "";
  }
  
  if (isset($_SESSION["disabled_modules"][$type])) exit("{t}Module disabled.{/t}");

  $this->schema = db_get_schema(sys_find_module($type),$folder,$view);
  $view = sys_array_shift(array_keys($this->schema["views"]));
  $this->current_view = &$this->schema["views"][$view];
  
  if (isset($this->current_view["SCHEMA"]) and $this->current_view["SCHEMA"]!="") {
    $this->schema = db_get_schema(sys_find_module($this->current_view["SCHEMA"]),"",$view);
	$view = sys_array_shift(array_keys($this->schema["views"]));
	$this->current_view = &$this->schema["views"][$view];
  }
  $this->folder = $folder;
  $this->view = $view;
  $this->fields = &$this->schema["fields"];
  $this->att = &$this->schema["att"];
  
  $this->tname = $this->att["NAME"];
  $this->where = $this->current_view["SQLWHERE"];
  $this->handler = $this->current_view["SQL_HANDLER"];
  $this->buttons = $this->current_view["buttons"];
  $this->rowvalidates = $this->current_view["rowvalidates"];
  $this->rowfilters = $this->current_view["rowfilters"];
  $this->current_fields = &$this->current_view["fields"];
  $this->notification = true;

  $this->vars = array("item"=>$items, "folder"=>$this->folder, "folders"=>$folders, "mfolder"=>$mfolder);

  $this->vars_noquote = array();
  if (!empty($this->att["ENABLE_ASSET_RIGHTS"])) {
    if ($writeable) $this->where[] = "@permission_sql_write_nq@";
	$this->vars_noquote["permission_sql_read_nq"] = $_SESSION["permission_sql_read"];
	$this->vars_noquote["permission_sql_write_nq"] = $_SESSION["permission_sql_write"];
  }

  if (is_array($this->where) and count($this->where)>0) {
	foreach ($this->where as $key=>$val) {
	  if (!preg_match_all("|@(.*?)@|i",$val,$matches,PREG_SET_ORDER)) continue;
	  foreach ($matches as $match) {
		if (count($match)!=2) continue;
		$wkey = $match[1];
		if (empty($this->vars[$wkey]) and empty($this->vars_noquote[$wkey])) $this->where[$key] = "1=1";
  } } }
}

/**
 * Changes the current sgsML fields with an array of path steps
 *
 * @param array $patch_array Steps, e.g. array(origin, SIMPLE_DEFAULT=email)
 */
function patch_fields(array $patch_array) {
  $pointer = $this->fields;
  foreach ($patch_array as $elem) {
	$elem = explode("=", $elem, 2);
	if (count($elem)==2) {
	  $pointer[$elem[0]] = $elem[1];
	  break;
	}
	$pointer = &$pointer[$elem[0]];
  }	
}

function get_rows($fields, $order="", $limit="") {
  $optional = array("handler"=>$this->handler,"sqlvarsnoquote"=>$this->vars_noquote,"default_sql"=>$this->current_view["DEFAULT_SQL"],"custom_name"=>$this->att["CUSTOM_NAME"]);
  return db_select($this->tname,$fields,$this->where,$order,$limit,$this->vars,$optional);
}

function get_count() {
  $optional = array("handler"=>$this->handler,"sqlvarsnoquote"=>$this->vars_noquote,"default_sql"=>$this->current_view["DEFAULT_SQL"],"custom_name"=>$this->att["CUSTOM_NAME"]);
  
  // TODO optimize
  return db_count($this->tname,$this->where,$this->vars,$optional);
}

function set_filter($filters) {
  if ($filters=="") return;
  $ops = array("eq","neq","lt","gt","like","nlike","starts","oneof");
  foreach (explode("||",$filters) as $key=>$filter) {
	$filter = explode("|",$filter);
	if (count($filter)!=3 or !isset($this->current_fields[$filter[0]])) continue;
	if (!in_array($filter[1],$ops) or trim($filter[2])=="") continue;

	if ($this->current_fields[$filter[0]]["SIMPLE_TYPE"]=="checkbox") {
	  $filter[2] = $filter[2]==sys_remove_trans("{t}yes{/t}")?"1":"0";
	}
	if ($filter[1]=="oneof") $filter[2] = explode(",",$filter[2]);
	if (in_array($this->current_fields[$filter[0]]["SIMPLE_TYPE"],array("date","dateselect","time","datetime"))) {
	  $filter[2] = modify::datetime_to_int($filter[2]);
	}
	$op = "=";
	switch ($filter[1]) {
	  case "neq": $op = "!="; break;
	  case "oneof": $op = "in"; break;
	  case "lt": $op = "<"; break;
	  case "gt": $op = ">"; break;
	  case "like": $op = "like"; $filter[2] = "%".$filter[2]."%"; break;
	  case "nlike": $op = "not like"; $filter[2] = "%".$filter[2]."%"; break;
	  case "starts": $op = "like"; $filter[2] = $filter[2]."%"; break;
	}
	$this->vars["filter_value_".$key] = $filter[2];
	$this->where[] = $filter[0]." ".$op." (@filter_value_".$key."@)";
  }
}

function insert(array &$data) {
  return $this->_save($data);
}

function update(array &$data, $id) {
  return $this->_save($data, $id);
}

function validate($data, $id=-1) {
  list($rdata, $unused, $error) = $this->_complete_data($data, $id);
  if ($error) return $error;
  return $this->_validate($rdata, $id);
}

function filter_field($field_name, $value, $row) {
  if ($field_name=="" or !isset($this->fields[$field_name])) return $value;

  $field = $this->current_fields[$field_name];
  $type = $field["SIMPLE_TYPE"];
  if (!empty($field["NO_CHECKS"])) return $value;

  $filters = array();
  if (isset($this->current_view["filters"][$field_name])) {
	$filters = $this->current_view["filters"][$field_name];
  }

  if (is_call_type($type)) {
	$filters[] = array("FUNCTION"=>"modify::nl2br");
	$filters[] = array("FUNCTION"=>"modify::htmlquote");

/* TODO implement
    $filters[] = array("FUNCTION"=>"type_".$type."::render_page");
	$filters[] = array("FUNCTION"=>"modify::htmlfield");
	$filters[] = array("FUNCTION"=>"modify::htmlquote");
	$filters[] = array("FUNCTION"=>"type_".$type."::render_value");
	$values[$key] = call_user_func(array($class, $function), $val, $value, $params, self::_explode($row));
*/
  } else if ($type == "textarea") {
	$filters[] = array("FUNCTION"=>"modify::nl2br");
	$filters[] = array("FUNCTION"=>"modify::htmlquote");
  } else {
	$filters[] = array("FUNCTION"=>"modify::field");
	$filters[] = array("FUNCTION"=>"modify::htmlquote");
  }
  if (empty($filters)) return $value;
  
  if (sgsml::type_is_multiple($type)) {
    $values = explode("|",trim($value,"|"));
  } else {
    $values = (array)$value;
  }

  foreach ($filters as $filter) {
	list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
	if (isset($filter["TYPE"])) continue; // TODO implement
	foreach ($values as $key=>$value) {
	  $values[$key] = call_user_func(array($class, $function), $value, $params, self::_explode($row));
	}
  }
  return $values;
}

function restore_field($field_name, $value, $row) {
  if ($field_name=="" or !isset($this->fields[$field_name])) return $value;
  if (empty($this->current_view["restore"][$field_name])) return $value;
  
  $restore_filters = $this->current_view["restore"][$field_name];
  $field = $this->fields[$field_name];

  $value = explode("|",trim($value,"|"));
  foreach ($restore_filters as $filter) {
    list($class, $function, $params) = sys_find_callback("modify", $filter["FUNCTION"]);
	foreach (array_keys($value) as $key) {
	  $value[$key] = call_user_func(array($class, $function), $value[$key], $params, $row, $field_name);
	}
  }
  return self::scalarize($value, $field);
}

function validate_field($field_name, $value, $id=-1) {
  if ($field_name=="" or !isset($this->fields[$field_name])) return array();

  $error = array();  
  $field = $this->fields[$field_name];
  $key_name = isset($field["DISPLAYNAME"])?$field["DISPLAYNAME"]:$field["NAME"];

  $value = (array)$value;
  $value_str = self::scalarize($value, $field);
  
  if (isset($field["REQUIRED"]) and $value_str=="") $error[] = array($key_name,"{t}missing field{/t}");
  if (isset($field["VALIDATE"])) {
	foreach ($field["VALIDATE"] as $validate) {
      list($class, $function, $params) = sys_find_callback("validate", $validate["FUNCTION"]);
	  foreach ($value as $value_item) {
	    if ($value_item!="" or isset($field["REQUIRED"])) {
		  $result = call_user_func(array($class, $function), $value_item, $params, $field);
		  if ($result!="") $error[] = array($key_name,$result);
  } } } }
  
  if (isset($field["KEY"]) or ((isset($field["IS_UNIQUE"]) or isset($field["IS_UNIQUE_WITH_TRASH"])) and $value_str!="")) {
    $check_trash = isset($field["IS_UNIQUE_WITH_TRASH"]);
	if ($result = validate::itemexists($this->tname, array($field_name=>$value_str), $id, $check_trash) and $result!="") {
	  $error[] = array($key_name,$result);
  } }
  return $error;
}

function get_fields_by_type($type) {
  if ($type=="") return array();
  $type = (array)$type;
  $fields = array();
  foreach ($this->fields as $key=>$field) {
	if (in_array($field["SIMPLE_TYPE"],$type)) $fields[] = $key;
  }
  return $fields;
}

function build_history($new_data, $old_data) {
  $cdata = "";
  $cfields = array();
  $no_history = array("created","createdby","lastmodified","lastmodifiedby","history","notification_summary","dsize","id");
  
  foreach ($new_data as $data_key=>$data_value) {
	$field = $this->fields[$data_key];
	if (isset($old_data[$data_key]) and strcmp(trim($old_data[$data_key], "|"), trim($data_value, "|"))==0) {
	  unset($new_data[$data_key]);
	  continue;
	}
	if (in_array($data_key, $no_history)) continue;
	if (isset($field["SIMPLE_TYPE"]) and (strlen($data_value)>0 or !empty($old_data[$data_key]))) {
	  $data_value = asset::build_history($field["SIMPLE_TYPE"], $data_value, @$old_data[$data_key]);
	}
	if (!empty($field["NO_SEARCH_INDEX"])) $data_value = "";
	if (!empty($field["DISPLAYNAME"])) $key = $field["DISPLAYNAME"]; else $key = $data_key;

	if (trim($data_value)!="") $cdata .= $key.": ".$data_value."\n";
	$cfields[] = $key;
  }
  $new_data["history"] = str_replace("@fields@", implode(", ", $cfields), $new_data["history"]).$cdata."\n";  
  return $new_data;
}

private function _complete_data($data, $id=-1) {
  $data_row = array();
  $insert = ($id>0 or !is_numeric($id)) ? false : true;
  if (!$insert) {
	$this->vars["item"] = (array)$id;
	$data_row = array_pop($this->get_rows("*"));
	if (!$data_row) return array(false,false,self::_error("{t}Data{/t}","{t}Item(s) not found or access denied.{/t}"));
  }
  $rdata = array();
  foreach ($this->current_fields as $field_name => $field) {
    $rdata[$field_name] = "";
    if (isset($data[$field_name])) {
	  $rdata[$field_name] = $data[$field_name];
	} else if ($insert) {
	  $rdata[$field_name] = $field["SIMPLE_DEFAULT"];
	  if (!empty($field["SIMPLE_DEFAULT_FUNCTION"])) {
	    list($class, $function, $params) = sys_find_callback("modify", $field["SIMPLE_DEFAULT_FUNCTION"]);
		$rdata[$field_name] = call_user_func(array($class, $function), $params);
	  }
	} else if (isset($data_row[$field_name])) {
	  $rdata[$field_name] = $this->restore_field($field_name,$data_row[$field_name],$data);
	}
	if (isset($data[$field_name."_remove"])) {
	  $rdata[$field_name] = str_replace("|".$rdata[$field_name]."|","|",$data_row[$field_name]);
	}
	if (isset($data[$field_name."_append"])) {
	  $rdata[$field_name] = $data_row[$field_name].$rdata[$field_name];
	}
	if (isset($data[$field_name."_prepend"])) {
	  $rdata[$field_name] = $rdata[$field_name].$data_row[$field_name];
	}
	if (self::type_is_multiple($field["SIMPLE_TYPE"]) and !is_array($rdata[$field_name])) {
	  $rdata[$field_name] = array_unique(explode("|",trim($rdata[$field_name],"|")));
	}
	if ($field["SIMPLE_TYPE"]=="id") $rdata[$field_name] = $id ? $id : -1;
	
	if ($field["SIMPLE_TYPE"]=="files" and !empty($rdata[$field_name])) { // download from URL
	  foreach ($rdata[$field_name] as $filekey=>$file) {
	    if (!preg_match("|^https?://.+|i",$file)) continue;
		$target = self::getfile_url($file);
		if ($target=="" or !file_exists($target)) {
		  return array(false,false,self::_error("{t}Data{/t}","{t}Upload failed{/t}: {t}The url doesn't exist.{/t} ".$file));
		}
		$rdata[$field_name][$filekey] = $target;
  } } }
  return array($rdata, $data_row, false);
}

private function _save(array &$data, $id=-1) {
  $insert = ($id>0 or !is_numeric($id)) ? false : true;
  if (count($data)==0) return array();
  
  if (!empty($this->att["DEFAULT_SQL"]) and $this->att["DEFAULT_SQL"]=="no_select") {
    return self::_error("{t}Module{/t}","{t}Access denied.{/t}");
  }

  if (!empty($data["folder"])) {
	// check permissions
	if (!db_get_right($data["folder"], "write", $this->view)) return self::_error("{t}Folder{/t}","{t}Access denied.{/t}","folder");
	$this->folder = $data["folder"];
  } else {
	$data["folder"] = $this->folder;
  }

  // fill data array
  list($rdata, $data_row, $error) = $this->_complete_data($data, $id);
  if ($error) return $error;

  // validate
  if (($result = $this->_validate($rdata,$id))) return $result;
  
  if ($insert) {
	$id = sql_genID($this->tname)*100+$_SESSION["serverid"];
	$sql_data = array("id"=>$id,"dsize"=>0,"history"=>sprintf("{t}Item created by %s at %s{/t}\n",$_SESSION["username"],sys_date("{t}m/d/y g:i:s a{/t}")));
  } else {
    $sql_data = array("dsize"=>0,"history"=>sprintf("{t}Item edited (%s) by %s at %s{/t}\n","@fields@",$_SESSION["username"],sys_date("{t}m/d/y g:i:s a{/t}")));
  }

  // count sizes, move files to store, delete old files
  foreach ($this->current_fields as $field_name => $field) {
    if ($field["SIMPLE_TYPE"]=="id") continue;
	
	if ($field["SIMPLE_TYPE"]=="files" and !empty($rdata[$field_name])) {
	
	  foreach ($rdata[$field_name] as $val) {
	    if (file_exists($val)) $sql_data["dsize"] += filesize($val);
	  }

	  // TODO 2 store handler?
	  if (!empty($data_row[$field_name])) {
	    $data_old = explode("|",trim($data_row[$field_name],"|"));
	    foreach ($data_old as $filekey=>$file) {
	      if (in_array($file,$rdata[$field_name])) continue;
		  if (ARCHIVE_DELETED_FILES and file_exists($file)) {
			$i = 1;
			$m = "";
			$trash_name = SIMPLE_STORE."/trash/".$this->folder."_".$id."_";
			$trash_file = modify::basename($file);
			while (file_exists($trash_name.$m.$trash_file)) $m = ($i++)."_";
			rename($file, $trash_name.$m.$trash_file);
			touch($trash_name.$m.$trash_file);
		  } else {
			@unlink($file);
	  } } }

	  foreach ($rdata[$field_name] as $filekey=>$file) {
	    if ($file=="") {
		  unset($rdata[$field_name][$filekey]);
		  $data[$field_name] = implode("|", $rdata[$field_name]);
		  continue;
		}
		if (file_exists(SIMPLE_CACHE."/upload/".basename($file))) {
		  $filebase = modify::basename(basename($file));
	  	  list($target,$filename) = sys_build_filename($filebase, $this->tname);

		  dirs_checkdir($target);
		  $target .= sys_get_pathnum($id)."/";
		  dirs_checkdir($target);
		  $target .= md5($id).$filename;
		
		  rename(SIMPLE_CACHE."/upload/".basename($file), $target);
		  $rdata[$field_name][$filekey] = $target;
		  $data[$field_name] = implode("|", $rdata[$field_name]);
		}
	  }
	  $basenames = array();
	  foreach (array_reverse($rdata[$field_name]) as $filekey=>$file) {
	    $basename = modify::basename($file);
	    if (isset($basenames[$basename])) {
		  $old_filekey = $basenames[$basename];
		  $basename = preg_replace("|_rev\d+|","",$basename);
		  $base = $basename;
		  $i = 1;
		  while (isset($basenames[$basename])) {
		    if (($pos = strrpos($base,"."))) {
			  $basename = substr($base,0,$pos)."_rev".($i++).substr($base,$pos);
			} else $basename = $base."_rev".($i++);
		  }
		  $target = str_replace(modify::basename($file), $basename, $file);
		  if (rename($file,$target)) {
			// swap
		    $rdata[$field_name][$filekey] = $rdata[$field_name][$old_filekey];
			$rdata[$field_name][$old_filekey] = $target;
			$data[$field_name] = implode("|", $rdata[$field_name]);
		  }
		}
		$basenames[$basename] = $filekey;
	  }
	}
	
	if (!empty($field["STORE"]) and is_array($field["STORE"])) {
	  foreach ($field["STORE"] as $store) {
	    list($class, $function, $params) = sys_find_callback("modify", $store["FUNCTION"]);
		$rdata[$field_name] = call_user_func(array($class, $function), $rdata[$field_name], $rdata, $params);
	  }
	}
	if (!isset($sql_data[$field_name]) and !is_null($rdata[$field_name])) $sql_data[$field_name] = $rdata[$field_name];
  }
  
  // transform
  foreach ($sql_data as $key=>$value) {
    $sql_data[$key] = self::scalarize($value, $this->fields[$key]);
  }
  
  // reduce to new values
  $sys_fields = array("history"=>"","dsize"=>"","seen"=>"");
  foreach ($sql_data as $data_key=>$data_value) {
    if (isset($sys_fields[$data_key])) continue;

	$addfield = true;
	$field = $this->fields[$data_key];
	if (!isset($this->current_fields[$data_key])) $addfield = false;
	if (isset($field["NOTINALL"])) $addfield = false;
	if (isset($field["NOTIN"]) and in_array($this->view,$field["NOTIN"])) $addfield = false;
	if (isset($field["READONLYIN"]) and (in_array($this->view,$field["READONLYIN"]) or in_array("all",$field["READONLYIN"]))) {
	  $addfield = false;
	}
	if (isset($field["ONLYIN"])) {
	  if (in_array($this->view,$field["ONLYIN"])) $addfield = true; else $addfield = false;
	}
	if (!$addfield) unset($sql_data[$data_key]);
  }
  
  // build history  
  $sql_data = $this->build_history($sql_data, $data_row);
  if (!array_diff(array_keys($sql_data), array("history", "seen"))) $sql_data = array();

  // save in db
  if ($insert) {
	$error_sql = db_insert($this->tname,$sql_data,array("handler"=>$this->handler));
	if ($error_sql!="") return self::_error("{t}SQL failed.{/t}",$error_sql);
	if ($this->notification) sys_notification("{t}Item successfully created.{/t} (".$id.")");
  } else {
	if (count($sql_data)==0) return $id;
	$error_sql = db_update($this->tname,$sql_data,array("id=@id@"),array("id"=>$id,"folder"=>$this->folder),array("handler"=>$this->handler));
	if ($error_sql!="") return self::_error("{t}SQL failed.{/t}",$error_sql);
	if ($this->notification) sys_notification("{t}Item successfully updated.{/t} (".(is_numeric($id)?$id:1).")");
  }

  if (empty($this->handler)) {
	db_update("simple_sys_tree",array("history"=>"[".$id."/details] ".$sql_data["history"]),array("id=@id@"),array("id"=>$this->folder));
	db_update_treesize($this->tname,$this->folder);
	
	if (!$insert and $this->folder!=$data_row["folder"]) {
	  db_update("simple_sys_tree",array("history"=>"[".$id."/details] ".$sql_data["history"]),array("id=@id@"),array("id"=>$data_row["folder"]));
	  db_update_treesize($this->tname,$data_row["folder"]);
	  db_search_delete($this->tname,$id,$data_row["folder"]);
	}
	if (empty($this->att["NO_SEARCH_INDEX"])) db_search_update($this->tname,$id,$this->fields);
    sys_log_stat($insert ? "new_records" : "changed_records",1);
  }

  // call triggers
  $trigger = "";
  if ($insert and !empty($this->att["TRIGGER_NEW"])) $trigger = $this->att["TRIGGER_NEW"];
  if (!$insert and !empty($this->att["TRIGGER_EDIT"])) $trigger = $this->att["TRIGGER_EDIT"];
  
  if ($trigger and ($result = asset_process_trigger($trigger,$id,$rdata,$this->tname))) {
	return self::_error("{t}Trigger failed{/t}",$result);
  }

  // send notification
  $tree_notification = db_select_value("simple_sys_tree","notification","id=@id@",array("id"=>$this->folder));
  if ($tree_notification!="") $rdata["notification"] .= ",".$tree_notification;
  
  if (!$insert and $this->folder!=$data_row["folder"]) {
	$tree_notification = db_select_value("simple_sys_tree","notification","id=@id@",array("id"=>$data_row["folder"]));
	if ($tree_notification!="") $rdata["notification"] .= ",".$tree_notification;
  }
  
  if (!empty($rdata["notification"])) {
    $rdata["notification"] = trim($rdata["notification"],",");
	$smtp_data = asset::build_notification($this->att["NAME"],$this->current_fields,$rdata,$sql_data,$id,$data_row);
  	if (($result = asset_process_trigger("sendmail",$id,$smtp_data))) return self::_error("{t}Trigger failed{/t}",$result);
  }

  // update stats
  if (!empty($this->handler)) {
	foreach ($sql_data as $data_key=>$data_value) {
	  $field = $this->fields[$data_key];
	  if ($field["SIMPLE_TYPE"]!="files") continue;
	  foreach (explode("|",$data_value) as $file) {
		if (sys_strbegins($file, SIMPLE_CACHE."/upload/")) @unlink($file);
  } } }
  return $id;
}

private function _validate($data, $id) {
  $error = array();
  foreach (array_keys($this->current_fields) as $field_name) {
	if (($result = $this->validate_field($field_name,$data[$field_name],$id))) $error[$field_name] = $result;
  }
  foreach ($this->rowvalidates as $validate) {
    list($class, $function, $params) = sys_find_callback("validate", $validate["FUNCTION"]);
	$result = call_user_func(array($class, $function), $data, $params);
	if ($result!="") {
	  $vfields = array();
	  foreach (explode("|",$validate["FIELDS"]) as $vfield) {
		$tfield = $this->fields[$vfield];
		$vfields[] = isset($tfield["DISPLAYNAME"])?$tfield["DISPLAYNAME"]:$tfield["NAME"];
		$error[$vfield][] = array();
	  }
	  $error[$validate["FIELDS"]][] = array(implode(", ",$vfields),$result);
	}
  }
  return $error;
}

static function type_is_multiple($type) {
  if (in_array($type,array("files","select","dateselect"))) return true;
  return false;
}

static function getfile_url($url) {
  $filename = self::_url_getfilename($url);
  list($target,$filename) = sys_build_filename($filename);
  dirs_checkdir($target);
  $target .= $_SESSION["username"]."__".$filename;

  if (sys_is_internal_url($url)) {
	$vars = array();
	parse_str(parse_url($url, PHP_URL_QUERY), $vars);
	if (!empty($vars["folder2"]) and !empty($vars["item"]) and !empty($vars["field"])) {
	  $source = ajax::file_download($vars["folder2"], @$vars["view2"], $vars["item"], $vars["field"], @$vars["subitem"], false);
	  if (file_exists($source) and copy($source, $target)) return $target;
	}
  }
  if ($f_in = @fopen($url,"rb") and $f_out = fopen($target,"wb")) {
	while (!feof($f_in)) fwrite($f_out, fread($f_in, 8192));
	fclose($f_out);
	fclose($f_in);
	return $target;
  }
  return "";
}

static function getfile_upload($filename) {
  list($target,$filename) = sys_build_filename($filename);
  dirs_checkdir($target);
  $target .= $_SESSION["username"]."__".$filename;

  if (($fp = fopen("php://input", "r")) and ($ft = fopen($target, "w"))) {
    while (!feof($fp)) fwrite($ft, fread($fp, 1024));
	fclose($ft);
	fclose($fp);
	return $target;
  }
  return "";
}

static function scalarize($value, $field) {
  if (is_array($value)) {
	$value = implode("|",$value);
	if (!self::type_is_multiple($field["SIMPLE_TYPE"]) or $value=="") return $value;
	if (empty($field["SIMPLE_SIZE"]) or $field["SIMPLE_SIZE"]!="1") return "|".$value."|";
  }
  return $value;
}

private static function _error($name,$desc,$field="") {
  if ($field=="") $field = $name;
  return array($field=>array(array($name,$desc)));
}

private static function _explode($row) {
  $result = array();
  foreach ($row as $key=>$value) {
    $value = explode("|",trim($value,"|"));
    $result[$key] = array("data"=>$value, "filter"=>$value);
  }
  return $result;
}

private static function _url_getfilename($url) {
  $filename = basename($url);
  if ($filename=="") $filename = "default.txt";
  if (preg_match("|filename=(.*?)&|",$url,$match) and isset($match[1])) {
    $filename = rawurldecode($match[1]);
  } else {
    $filename = preg_replace("|([^a-z0-9-_.])|i","_",$filename);
	$ext = modify::getfileext($filename);
	if ($ext=="" or strlen($ext)>5) $filename .= ".txt";
	if (strlen($filename)>50) $filename = substr($filename,strlen($filename)-50);
  }
  return $filename;
}

}