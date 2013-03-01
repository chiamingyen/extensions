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

class sgsml_customizer {

static function ajax_get_field($type, $field) {
  $field = sys_array_shift(self::_get_obj($type, "/table/field[@name='".$field."']"));
  if (empty($field)) return array();

  $result = array();
  foreach ($field->attributes() as $key=>$value) {
	if ($key == "separator") $key = "fseparator";
	$result[$key] = (string)$value;
  }
  if (isset($field->KEY)) $result["fkey"] = true;
  if (isset($field->INDEX)) $result["findex"] = true;
  if (isset($field->INDEX_FULLTEXT)) $result["findex_fulltext"] = true;

  if (isset($field->notin)) {
	$result["notin"] = (string)$field->notin->attributes()->views;
  }
  if (isset($field->hiddenin)) {
	$result["hiddenin"] = (string)$field->hiddenin->attributes()->views;
  }
  if (isset($field->onlyin)) {
	$result["onlyin"] = (string)$field->onlyin->attributes()->views;
  }
  if (isset($field->readonlyin)) {
	$result["readonlyin"] = (string)$field->readonlyin->attributes()->views;
  }
  if (isset($field->description)) {
	$result["description_title"] = (string)$field->description->attributes()->title;
	$result["description_hint"] = (string)$field->description->attributes()->hint;
	$result["description_value"] = (string)$field->description->attributes()->value;
  }
  return $result;
}

static function ajax_select_fields($module) {
  return self::select_fields(array($module));
}

static function select_fields($params) {
  $module = "blank";
  if (!empty($params[0])) $module = $params[0];

  $fields = self::_get_obj($module, "/table/field/@name");
  if (empty($fields)) return array();

  $result = array();
  foreach ($fields as $field) $result[(string)$field] = (string)$field;
  return $result;
}

static function ajax_select_views($module) {
  return self::select_views(array($module));
}

static function select_views($params) {
  $module = "blank";
  if (!empty($params[0])) $module = $params[0];

  $views = self::_get_obj($module, "/table/view[not(@schema)]");
  if (empty($views)) return array();

  $result = array();
  foreach ($views as $view) $result[(string)$view["name"]] = (string)$view["displayname"];
  return $result;
}

static function ajax_select_tabs($module) {
  return self::select_tabs(array($module));
}

static function select_tabs($params) {
  $module = "blank";
  if (!empty($params[0])) $module = $params[0];

  $tabs = self::_get_obj($module, "/table/tab");
  if (empty($tabs)) return array();

  $result = array();
  foreach ($tabs as $tab) {
	$result[(string)$tab["name"]] = !empty($tab["displayname"])?(string)$tab["displayname"]:ucfirst($tab["name"]);
  }
  return $result;
}

static function select_types() {
  $types = array("text", "textarea", "checkbox", "files", "date", "time", "datetime", "select",
	"dateselect", "int", "float", "password", "id", "folder", "pid", "multitext");
  $customs = sys_scandir("core/types/", array(".","..","default.php"));
  foreach ($customs as $custom) $types[] = str_replace(".php", "", $custom);
  return array_combine($types, $types);
}

static function trigger_build_field($id, $data, $unused, $table) {
  $schema = self::_get_custom_field($data);
  $schema = trim(str_replace(">", ">\n", substr($schema, strpos($schema, ">")+1)));
  db_update($table,array("custom_schema"=>$schema),array("id=@id@"),array("id"=>$id));
}

private static function _get_obj($module, $xpath="") {
  $content = sgsml_parser::file_get_contents(sys_find_module($module),$module,"");
  $obj = new SimpleXMLElement($content);
  if ($xpath!="") return $obj->xpath($xpath);
  return $obj;
}

private static function _get_custom_field($data) {
  $xml = new SimpleXMLElement("<field/>");
  $xml["name"] = $data["field"];
  $xml["customized"] = "true";
  
  $tags_views = array("notin", "hiddenin", "onlyin", "readonlyin");
  foreach ($tags_views as $tag) {
	if (!empty($data[$tag])) $xml->addChild($tag)->addAttribute("views", $data[$tag]);
  }
  $tags_keys = array("fkey", "findex", "findex_fulltext");
  foreach ($tags_keys as $tag) {
	if (!empty($data[$tag])) $xml->addChild(strtoupper(substr($tag,1)));
  }
  $attributes_bool = array("required", "is_unique", "allow_custom", "sum", "average", "hidden", "editable",
	"notinall", "nowrap", "is_unique_with_trash", "no_search_index", "disable_ccp", "nodb");
  foreach ($attributes_bool as $attribute) {
	if (!empty($data[$attribute])) $xml[$attribute] = "true";
  }
  $attributes = array("simple_type", "simple_size", "simple_tab", "displayname", "simple_default", "form",
	"simple_default_function", "simple_file_size", "width", "height", "input_height", "db_type", "db_size");
  foreach ($attributes as $attribute) {
	if ($data[$attribute]!="") $xml[$attribute] = $data[$attribute];
  }
  $maps = array("fbefore"=>"before", "fseparator"=>"separator");
  foreach ($maps as $map => $attribute) {
	if (!empty($data[$map])) $xml[$attribute] = $data[$map];
  }
  if (!empty($data["description_value"])) {
	$tag = $xml->addChild("description");
	$tag["hint"] = $data["description_hint"];
	$tag["value"] = $data["description_value"];
	$tag["title"] = $data["description_title"];
  }

  $merge_subtags = array("data", "filter", "validate", "link", "linktext", "store", "restore");
  $objs = self::_get_obj($data["module"], "/table/field[@name='".$data['field']."']");
	
  if (!empty($objs)) {
	foreach ($objs[0] as $tag => $obj) {
	  if (!in_array($tag, $merge_subtags)) continue;
	  $child = $xml->addChild($tag);
	  foreach ($obj->attributes() as $key=>$value) $child[$key] = $value;
	}
  }
  return $xml->asXML();
}
}
?>