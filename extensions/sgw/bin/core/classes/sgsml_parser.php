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

class sgsml_parser {

static function file_get_contents($file,$schema,$custom_schema) {
  $content = preg_replace("|<!--.*?-->|msi","",file_get_contents($file));
  if (strpos($content,"<?xml")===false) $content = '<?xml version="1.0" encoding="utf-8"?>'."\n".$content;
  $is_sys = strpos($file,"_sys/");
  
  if (!sys_strbegins($schema,"nodb_")) $content = self::_change_schema($content);
  $content = self::_change_schema_nodb($content,$schema,$is_sys);

  $diff_path = sys_custom_dir(substr($file,0,-4)."/");
  if (is_dir($diff_path)) {
	foreach (scandir($diff_path) as $file) {
	  if ($file[0]==".") continue;
	  $obj = new SimpleXMLElement($content);
	  self::_merge_simplexml($obj, new SimpleXMLElement(file_get_contents($diff_path.$file)));
	  $content = $obj->asXml();
	}
  }
  if ($custom_schema!="") {
    $obj = new SimpleXMLElement($content);
    self::_merge_simplexml($obj, new SimpleXMLElement($custom_schema));
	$content = $obj->asXml();
  }
  if (DEBUG) $content = sys_remove_trans($content);
  return $content;
}

static function parse_schema($data,$tname,$cache_time,$cache_file) {
  $parser = xml_parser_create("utf-8");
  xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
  $values = "";
  $tags = "";
  
  xml_parse_into_struct($parser,$data,$values,$tags);
  xml_parser_free($parser);

  $tables = array();
  $extra_types = array("TAB","SINGLEBUTTON","VIEWBUTTON","VIEW","ROWFILTER","ROWVALIDATE");
  $i = 0;
  if (!isset($tags["TABLE"]) or !is_array($tags["TABLE"]) or count($tags["TABLE"])!=2) return array();

  $tag = $tags["TABLE"][0];
  $tfields = array();
  if ($values[$tag]["type"]=="open" and $values[$tags["TABLE"][1]]["type"]=="close" and isset($values[$tag]["attributes"]["NAME"]) and $values[$tag]["attributes"]["NAME"]!="" and $tags["TABLE"][1]-$tag>3) {
	$ttemp = array_slice($values,$tag,$tags["TABLE"][1]-$tag);
	$att = $values[$tag]["attributes"];
	$tmarker = "";
	foreach (array_keys($ttemp) as $tkey) {
	  if ($ttemp[$tkey]["tag"]=="FIELD") $i = 2;
	    else if ($ttemp[$tkey]["tag"]=="VIEW") $i = 1;
	    else if ($ttemp[$tkey]["tag"]=="TAB") $i = 3;
	    else if ($ttemp[$tkey]["tag"]=="VIEWBUTTON") $i = 4;
	    else if ($ttemp[$tkey]["tag"]=="SINGLEBUTTON") $i = 5;
	    else if ($ttemp[$tkey]["tag"]=="ROWFILTER") $i = 6;
	    else if ($ttemp[$tkey]["tag"]=="ROWVALIDATE") $i = 7;

	  if ($i>0 and $ttemp[$tkey]["type"]=="complete" and isset($ttemp[$tkey]["attributes"]["NAME"]) and $ttemp[$tkey]["attributes"]["NAME"]!="" and (isset($ttemp[$tkey]["attributes"]["SIMPLE_TYPE"]) or in_array($ttemp[$tkey]["tag"],$extra_types))) {
	    $tfields[$i][$ttemp[$tkey]["attributes"]["NAME"]] = $ttemp[$tkey]["attributes"];

	  } else if ($i>0 and $ttemp[$tkey]["type"]=="open" and isset($ttemp[$tkey]["attributes"]["NAME"]) and $ttemp[$tkey]["attributes"]["NAME"]!="" and (isset($ttemp[$tkey]["attributes"]["SIMPLE_TYPE"]) or in_array($ttemp[$tkey]["tag"],$extra_types))) {
	    $tmarker = $ttemp[$tkey]["attributes"]["NAME"];
		$tfields[$i][$tmarker] = $ttemp[$tkey]["attributes"];

	  } else if ($i>0 and $ttemp[$tkey]["type"]=="close") {
	    $tmarker = "";
		$i = 0;
	  } else {
	    if ($tmarker!="" and $ttemp[$tkey]["type"]=="complete") {
		  if (isset($ttemp[$tkey]["attributes"]) and count($ttemp[$tkey]["attributes"])>0) {
		    $tfields[$i][$tmarker][$ttemp[$tkey]["tag"]][] = $ttemp[$tkey]["attributes"];
		  } else {
		    $tfields[$i][$tmarker][$ttemp[$tkey]["tag"]] = "";
  } } } } }
  if (count($tfields)>1 and count($att)>0) {
    $views = $tfields[1];
	$fields = $tfields[2];
	$tabs = (isset($tfields[3])?$tfields[3]:array("general"=>""));
		
	$buttons = (isset($tfields[4])?$tfields[4]:array());
	$singlebuttons = (isset($tfields[5])?$tfields[5]:array());
	$rowfilters = (isset($tfields[6])?$tfields[6]:array());
	$rowvalidates = (isset($tfields[7])?$tfields[7]:array());
		
	foreach ($buttons as $bkey=>$button) {
	  if (empty($buttons[$bkey]["ICON"]) and file_exists(sys_custom("ext/icons/".$button["NAME"].".gif"))) {
	    $buttons[$bkey]["ICON"] = $button["NAME"].".gif";
	  }
	}
    foreach ($singlebuttons as $bkey=>$button) {
	  if (empty($singlebuttons[$bkey]["ICON"]) and file_exists(sys_custom("ext/icons/".$button["NAME"].".gif"))) {
	    $singlebuttons[$bkey]["ICON"] = $button["NAME"].".gif";
	  }
	}
    foreach ($fields as $key=>$field) {
	  if (!empty($field["LINK"])) {
	    foreach ($field["LINK"] as $lkey=>$link) {
		  $icon = isset($link["ICON"])?$link["ICON"]:"";
		  $pos = isset($link["ALIGN"])?$link["ALIGN"]:"";
		  $link = $link["VALUE"];
		  if ($link[0]=="@") $link = array("_blank",substr($link,1),$icon,$pos);
			else if ($link[0]=="#") $link = array("pane",substr($link,1),$icon,$pos);
			else if ($link[0]=="%") $link = array("pane2",substr($link,1),$icon,$pos);
			else $link = array("_top",$link,$icon,$pos);
		  $fields[$key]["LINK"][$lkey]["VALUE"] = $link;
		}
	  }
	  if (!empty($field["LINKTEXT"])) {
	    foreach ($field["LINKTEXT"] as $lkey=>$link) {
		  $link = $link["VALUE"];
		  if ($link[0]=="@") $link = array("_blank",substr($link,1));
			else if ($link[0]=="#") $link = array("pane",substr($link,1));
			else if ($link[0]=="%") $link = array("pane2",substr($link,1));
			else $link = array("_top",$link);
		  $fields[$key]["LINKTEXT"][$lkey]["VALUE"] = $link;
		}
      }
	  if (isset($fields[$key]["ONLYIN"][0]["VIEWS"])) $fields[$key]["ONLYIN"] = explode("|",$fields[$key]["ONLYIN"][0]["VIEWS"]);
	  if (isset($fields[$key]["NOTIN"][0]["VIEWS"])) $fields[$key]["NOTIN"] = explode("|",$fields[$key]["NOTIN"][0]["VIEWS"]);
	  if (isset($fields[$key]["READONLYIN"][0]["VIEWS"])) $fields[$key]["READONLYIN"] = explode("|",$fields[$key]["READONLYIN"][0]["VIEWS"]);
	  if (isset($fields[$key]["HIDDENIN"][0]["VIEWS"])) $fields[$key]["HIDDENIN"] = explode("|",$fields[$key]["HIDDENIN"][0]["VIEWS"]);
	  if (isset($fields[$key]["SIMPLE_TYPE"]) and $fields[$key]["SIMPLE_TYPE"]=="id") $att["ID"] = $key;
	  if (!isset($fields[$key]["SIMPLE_TAB"])) $fields[$key]["SIMPLE_TAB"] = array(key($tabs)); else $fields[$key]["SIMPLE_TAB"] = explode("|",$fields[$key]["SIMPLE_TAB"]);
	  if (isset($fields[$key]["DATA"])) {
	    $values = array();
		$titles = array();
		foreach ($fields[$key]["DATA"] as $data_item) {
		  if (isset($data_item["VALUES"])) {
			$vals = array();
			foreach (explode("|",$data_item["VALUES"]) as $value) {
			  $value = explode("_##_",$value);
			  $vals[$value[0]] = isset($value[1])?$value[1]:$value[0];
			}
			if (isset($data_item["REVERSE"])) {
			  $vals2 = str_replace("_##_", "=>", $data_item["VALUES"]);
			  $fields[$key]["FILTER"][] = array("VIEWS"=>"all", "FUNCTION"=>"switch_items|".$vals2);
			}
			if (isset($data_item["SORT"])) {
			  switch($data_item["SORT"]) {
				case "asc":
				  asort($vals);
				  if (($skey = array_search(sys_remove_trans("Other"),$vals))) {
					unset($vals[$skey]);
					$vals[$skey] = "Other";
				  }
				  break;
				case "desc":
				  arsort($vals);
				  break;
			  }
			}
			$values[] = $vals;
		  }
		  // TODO reverse function?
		  if (isset($data_item["FUNCTION"])) $values[] = array("_FUNCTION_"=>$data_item["FUNCTION"]);
		  if (isset($data_item["TITLE"])) $titles[] = $data_item["TITLE"]; else $titles[] = "";
		}
		$fields[$key]["DATA"] = $values;
		$fields[$key]["DATA_TITLE"] = $titles;
	  }
	  if (!isset($fields[$key]["SIMPLE_DEFAULT"])) $fields[$key]["SIMPLE_DEFAULT"] = "";
	    else $fields[$key]["SIMPLE_DEFAULT"] = str_replace("\\n","\n",$fields[$key]["SIMPLE_DEFAULT"]);
	}

	$tables["att"] = $att;
	$tables["views"] = $views;
	$tables["fields"] = $fields;

	$tables["data"] = array(
	  "tabs"=>$tabs,
	  "buttons"=>$buttons,
	  "singlebuttons"=>$singlebuttons,
	  "rowfilters"=>$rowfilters,
	  "rowvalidates"=>$rowvalidates
	);
	
	$att = &$tables["att"];
	$tabs = &$tables["data"]["tabs"];
	$rowfilters = &$tables["data"]["rowfilters"];
	$rowvalidates = &$tables["data"]["rowvalidates"];
	$singlebuttons = &$tables["data"]["singlebuttons"];
	$buttons = &$tables["data"]["buttons"];
	$fields = &$tables["fields"];

	if (!isset($att["ID"])) $att["ID"] = "id";
	if (!isset($att["SQL_HANDLER"])) $att["SQL_HANDLER"] = "";
	if (!isset($att["GROUP"])) $att["GROUP"] = "";
	if (!isset($att["DEFAULT_SQL"])) $att["DEFAULT_SQL"] = "";
	if (!isset($att["NOSQLWHERE"])) $att["NOSQLWHERE"] = "";
	if (!isset($att["NOSQLFOLDER"])) $att["NOSQLFOLDER"] = "";
	if (!empty($att["CUST_NAME"])) $att["CUSTOM_NAME"] = $att["CUST_NAME"];
	if (!isset($att["CUSTOM_NAME"])) $att["CUSTOM_NAME"] = "";
	if (!isset($att["TEMPLATE"])) $att["TEMPLATE"] = "";
	if (!isset($att["SCHEMA_MODE"])) $att["SCHEMA_MODE"] = "";
	if (!isset($att["GROUPBY"])) $att["GROUPBY"] = "";
	if (!isset($att["ORDERBY"])) $att["ORDERBY"] = $att["ID"];
	if (!isset($att["ORDER"])) $att["ORDER"] = "asc";
	if (!isset($att["LIMIT"])) $att["LIMIT"] = 20;
	if (!isset($att["ENABLE_CALENDAR"])) $att["ENABLE_CALENDAR"] = "";
	if (!isset($att["HIDE_CALENDAR"])) $att["HIDE_CALENDAR"] = "";
	if (!isset($att["WHERE"])) $att["WHERE"] = array(); else $att["WHERE"] = array($att["WHERE"]);
	if (!isset($att["DOUBLECLICK"])) $att["DOUBLECLICK"] = "";

	foreach (array_keys($tables["views"]) as $key) {
	  if (empty($att["DEFAULT_VIEW"])) $att["DEFAULT_VIEW"] = $key;
		  
	  $view = &$tables["views"][$key];
	  $view["views"] = &$tables["views"];
		  
	  if (empty($view["ICON"]) and file_exists(sys_custom("ext/icons/".$view["NAME"].".gif"))) {
	    $view["ICON"] = $view["NAME"].".gif";
	  }
	  $view["modulename"] = &$att["MODULENAME"];
	  $view["id"] = &$att["ID"];
	  $view["filters"] = array();
	  $view["restore"] = array();
	  $view["rowfilters"] = array();
	  $view["rowvalidates"] = array();
	  $view["fields"] = array();
	  $view["links"] = array();
	  $view["linkstext"] = array();
	  $view["filters"] = array();
	  $view["buttons"] = array();
	  $view["singlebuttons"] = array();
	  $view["SQLWHERE"] = array();
	  $view["SQLWHERE_DEFAULT"] = array();

	  if (isset($view["HIDE_TABS"])) {
	    $view["tabs"] = array();
	    $h_tabs = explode("|",$view["HIDE_TABS"]);
	    foreach (array_keys($tabs) as $tkey) {
		  if (!in_array($tkey,$h_tabs) and !in_array("all",$h_tabs)) {
		    $view["tabs"][$tkey] = &$tabs[$tkey];
		  }
		}
	  } else $view["tabs"] = &$tabs;
	  
	  if (count($rowfilters)>0) {
	    foreach ($rowfilters as $rkey=>$rowfilter) {
		  $r_views = explode("|",$rowfilter["VIEWS"]);
		  if (in_array($key,$r_views) or in_array("all",$r_views)) {
		    $view["rowfilters"][] = &$rowfilters[$rkey];
	  } } }
	  if (count($rowvalidates)>0) {
	    foreach (array_keys($rowvalidates) as $rkey) {
		  $view["rowvalidates"][] = &$rowvalidates[$rkey];
		}
	  }
	  foreach (array_keys($fields) as $fkey) {
	    $field = &$fields[$fkey];
		$addfield = true;
		if (!empty($field["MULTIPLE"])) $field["SEPARATOR"] = $field["MULTIPLE"];
		if (!empty($field["SEPARATOR"])) $field["SEPARATOR"] = str_replace("\\n","\n",$field["SEPARATOR"]);

		if (isset($field["NOTINALL"])) $addfield = false;
		if (isset($field["NOTIN"]) and in_array($key,$field["NOTIN"])) $addfield = false;
		if (isset($field["ONLYIN"])) {
		  if (in_array($key,$field["ONLYIN"])) $addfield = true; else $addfield = false;
		}
		
		if (!empty($view["SHOWONLY"])) {
		  if (!in_array($field["NAME"], explode("|", $view["SHOWONLY"]))) $addfield = false;
		}

		if ($addfield) {
		  if (!empty($field["READONLYIN"]) and in_array("all",$field["READONLYIN"])) {
		    $field["READONLYIN"]["all"] = "true";
		  }
		  if (!empty($field["READONLYIN"]) and in_array($key,$field["READONLYIN"])) {
		    $field["READONLYIN"][$key] = "true";
		  }
		  
		  if (!empty($field["HIDDEN"]) or (!empty($field["HIDDENIN"]) and in_array("all",$field["HIDDENIN"]))) {
		    $field["HIDDENIN"]["all"] = "true";
		  }
		  if (!empty($field["HIDDENIN"]) and in_array($key,$field["HIDDENIN"])) {
		    $field["HIDDENIN"][$key] = "true";
		  }
		  $view["fields"][$field["NAME"]] = &$field;
		}
		
		if (!empty($field["LINK"])) {
		  foreach ($field["LINK"] as $lkey=>$link) {
			if (empty($link["VIEWS"])) {
			  $view["links"][$field["NAME"]] = &$field["LINK"][$lkey]["VALUE"];
			} else {
			  $fviews = explode("|",$link["VIEWS"]);
			  if (in_array($key,$fviews) or in_array("all",$fviews)) {
				$view["links"][$field["NAME"]] = &$field["LINK"][$lkey]["VALUE"];
		} } } }

		if (!empty($field["LINKTEXT"])) {
		  foreach ($field["LINKTEXT"] as $lkey=>$link) {
		    if (empty($link["VIEWS"])) {
			  $view["linkstext"][$field["NAME"]] = &$field["LINKTEXT"][$lkey]["VALUE"];
			} else {
			  $fviews = explode("|",$link["VIEWS"]);
			  if (in_array($key,$fviews) or in_array("all",$fviews)) {
				$view["linkstext"][$field["NAME"]] = &$field["LINKTEXT"][$lkey]["VALUE"];
		} } } }
		if (isset($field["FILTER"])) {
		  foreach ($field["FILTER"] as $fikey=>$filter) {
		    $fviews = explode("|",$filter["VIEWS"]);
		    if (in_array($key,$fviews) or in_array("all",$fviews)) {
			  $view["filters"][$field["NAME"]][] = &$field["FILTER"][$fikey];
		} } }
		if (isset($field["RESTORE"])) {
		  foreach ($field["RESTORE"] as $rekey=>$restore) {
		    if (!empty($restore["VIEWS"])) {
		      $fviews = explode("|",$restore["VIEWS"]);
		      if (!in_array($key,$fviews) and !in_array("all",$fviews)) continue;
		    }
		    $view["restore"][$field["NAME"]][] = &$field["RESTORE"][$rekey];
	  } } }
		  
      if (!isset($view["SQL_HANDLER"])) $view["SQL_HANDLER"] = $att["SQL_HANDLER"];
      if (!isset($view["GROUP"])) $view["GROUP"] = $att["GROUP"];
      if (!isset($view["DEFAULT_SQL"])) $view["DEFAULT_SQL"] = $att["DEFAULT_SQL"];
      if (!isset($view["NOSQLFOLDER"])) $view["NOSQLFOLDER"] = $att["NOSQLFOLDER"];
      if (!isset($view["NOSQLWHERE"])) $view["NOSQLWHERE"] = $att["NOSQLWHERE"];
      if (!isset($view["TEMPLATE"])) $view["TEMPLATE"] = $att["TEMPLATE"];
      if (!isset($view["SCHEMA_MODE"])) $view["SCHEMA_MODE"] = $att["SCHEMA_MODE"];
      if (!isset($view["GROUPBY"])) $view["GROUPBY"] = $att["GROUPBY"];
      if (!isset($view["ORDERBY"])) $view["ORDERBY"] = $att["ORDERBY"];
      if (!isset($view["ORDER"])) $view["ORDER"] = $att["ORDER"];
      if (!isset($view["LIMIT"])) $view["LIMIT"] = $att["LIMIT"];
      if (!isset($view["ENABLE_CALENDAR"])) $view["ENABLE_CALENDAR"] = $att["ENABLE_CALENDAR"];
      if (!isset($view["HIDE_CALENDAR"])) $view["HIDE_CALENDAR"] = $att["HIDE_CALENDAR"];
      if (!isset($view["DOUBLECLICK"])) $view["DOUBLECLICK"] = $att["DOUBLECLICK"];
      if (empty($view["WHERE"])) $view["WHERE"] = $att["WHERE"]; else $view["WHERE"] = array_merge(array($view["WHERE"]),$att["WHERE"]);

	  if (!empty($view["NOVIEWBUTTONS"]) and !in_array($view["NOVIEWBUTTONS"],array("all","true"))) {
		$f_no_buttons = explode("|",$view["NOVIEWBUTTONS"]);
	    $view["NOVIEWBUTTONS"] = ""; // != all|true
	  } else $f_no_buttons = array();
	  
	  foreach ($buttons as $bkey=>$button) {
	    $addit = true;
	    if (isset($button["VIEWS"]) and !in_array($key,explode("|",$button["VIEWS"]))) $addit = false;
		if (in_array($bkey,$f_no_buttons)) $addit = false;
		if ($addit) $view["buttons"][$bkey] = &$buttons[$bkey];
	  }
	  
	  if (!empty($view["NOSINGLEBUTTONS"]) and !in_array($view["NOSINGLEBUTTONS"],array("all","true"))) {
		$f_no_singlebuttons = explode("|",$view["NOSINGLEBUTTONS"]);
	    $view["NOSINGLEBUTTONS"] = ""; // != all|true
	  } else $f_no_singlebuttons = array();
	  
	  foreach ($singlebuttons as $bkey=>$button) {
	    $addit = true;
	    if (isset($button["VIEWS"])) {
		  $sviews = explode("|",$button["VIEWS"]);
		  if (!in_array($key, $sviews) and !in_array("all", $sviews)) $addit = false;
		}
		if (in_array($bkey,$f_no_singlebuttons)) $addit = false;
		if ($addit) $view["singlebuttons"][$bkey] = &$singlebuttons[$bkey];
	  }

	  $view["SQLWHERE"][] = "id in (@item@)";
      if (isset($fields["folder"]) and empty($view["NOSQLFOLDER"])) {
	    $view["SQLWHERE"][] = "folder in (@folders@)";
	    $view["SQLWHERE_DEFAULT"][] = "folder in (@folders@)";
	  }
      if (empty($view["NOSQLWHERE"]) and count($view["WHERE"])>0) {
	    $view["SQLWHERE"] = array_merge($view["SQLWHERE"], $view["WHERE"]);
		$view["SQLWHERE_DEFAULT"] = array_merge($view["SQLWHERE_DEFAULT"], $view["WHERE"]);
	  }
	  $tables[$key] = array("view"=>$key,"att"=>&$att,"views"=>array($key=>&$view),"fields"=>&$fields);
    }
  	if (!sys_strbegins($tname,"nodb_") and !self::_apply_schema($att["NAME"],$fields)) sys_die("Modifying database failed.");
  }
  if (!isset($att["NAME"]) or !isset($att["DEFAULT_VIEW"]) or !isset($views[$att["DEFAULT_VIEW"]]) or count($fields)==0) return array();
  
  if (count($tables)>0) {
	if (APC) {
	  apc_store("sgsml".basename($cache_file).$cache_time, $tables);
	} else {
	  file_put_contents($cache_file, serialize($tables), LOCK_EX);
	  sys_touch($cache_file,$cache_time);
	}
  }
  return $tables;
}

static function sql_version() {
  $version = "";
  if (SETUP_DB_TYPE=="mysql") {
    $version = mysql_get_server_info(sys::$db);
    $version = (int)substr(str_replace(".","",$version),0,3);
  } else if (SETUP_DB_TYPE=="pgsql") {
    $version = sql_fetch_one("show server_version");
	$version = (int)substr(str_replace(".","",$version["server_version"]),0,3);
  } else if (SETUP_DB_TYPE=="sqlite") {
    $version = sys::$db->getAttribute(PDO::ATTR_SERVER_VERSION);
    $version = (int)substr(str_replace(".","",$version),0,3);
  }
  if (strlen($version)<3) $version .= "0";
  return $version;
}

static function sql_date() {
  if (SETUP_DB_TYPE!="sqlite") {
	$row = sql_fetch_one("SELECT now()");
  } else {
	$row = sql_fetch_one("SELECT datetime('now', 'localtime')");
  }
  return array_shift($row);
}

static function table_get_indexes($table) {
  $indexes = array();
  if (SETUP_DB_TYPE=="mysql") {
    $sql = sprintf("show index from %s",$table);
    if (($dbindexes = sql_fetch($sql)) === false) return false;
    foreach ($dbindexes as $index) {
	  $type = "index";
	  if ($index["Key_name"] == "PRIMARY") $type = "primary";
	  if (!isset($indexes[$index["Column_name"]])) $indexes[$index["Column_name"]] = array("primary"=>false,"index"=>false);
	  $indexes[$index["Column_name"]][$type] = true;
    }
  } else if (SETUP_DB_TYPE=="sqlite") {
    $sql = sprintf("PRAGMA index_list(%s)",$table);
    if (($dbindexes = sql_fetch($sql)) === false) return false;
    foreach ($dbindexes as $index) {
	  $type = "index";
	  if (strpos($index["name"],"primary")) $type = "primary";
      $sql = sprintf("PRAGMA index_info(%s)",$index["name"]);
      if (($index_infos = sql_fetch($sql)) === false) return false;
	  foreach ($index_infos as $index_info) {
	    $indexes[$index_info["name"]] = array("primary"=>false,"index"=>false);
		$indexes[$index_info["name"]][$type] = true;
	  }
    }
  } else if (SETUP_DB_TYPE=="pgsql") {
    $sql = sprintf("select indexname,indexdef from pg_indexes where tablename='%s'",$table);
    if (($dbindexes = sql_fetch($sql)) === false) return false;
    foreach ($dbindexes as $index) {
	  $type = "index";
	  if (strpos($index["indexdef"],"UNIQUE")) $type = "primary";
	  preg_match("/\((.*?)\)/",$index["indexdef"],$match);
	  $keys = explode(",",str_replace(array("\"","_fti"),"",$match[1]));
	  foreach ($keys as $key) {
	    if (!isset($indexes[$key])) $indexes[$key] = array("primary"=>false,"index"=>false);
	    $indexes[$key][$type] = true;
  } } }
  return $indexes;
}

static function table_change_primary_key($schema_name,$primaries) {
  if (SETUP_DB_TYPE=="sqlite") {
    if (sql_query(sprintf("CREATE UNIQUE INDEX %s_primary ON %s (%s)",$schema_name,$schema_name,$primaries))) return true;
  } else {
    if (sql_query(sprintf("ALTER TABLE %s ADD PRIMARY KEY (%s)",$schema_name,$primaries))) return true;
  }
  return false;
}

static function table_drop_index($table, $type) {
  if (SETUP_DB_TYPE=="mysql") {
    if (sql_query(sprintf("ALTER TABLE %s DROP %s",$table,$type))) return true;
  } else if (SETUP_DB_TYPE=="sqlite") {
    if (strpos($type,"PRIMARY")!==false) $type = $table."_primary";
    if (sql_query(sprintf("DROP INDEX IF EXISTS %s",$type))) return true;
  } else if (SETUP_DB_TYPE=="pgsql") {
    if ($type=="PRIMARY KEY") {
      if (sql_query(sprintf("ALTER TABLE %s DROP CONSTRAINT %s",$table,$table."_pkey"))) return true;
	} else {
	  if (sql_query(sprintf("DROP INDEX %s",$type))) return true;
	}
  }
  return false;
}

static function table_add_index($type, $name, $table, $field) {
  if (SETUP_DB_TYPE=="mysql") {
    if (sql_query(sprintf("CREATE %s INDEX %s ON %s(%s)",$type,$name,$table,$field))) return true;
  } else if (SETUP_DB_TYPE=="sqlite") {
    if ($type=="FULLTEXT") $type = "";
	if (sql_query(sprintf("CREATE %s INDEX IF NOT EXISTS %s ON %s(%s)",$type,$name,$table,$field))) return true;
  } else if (SETUP_DB_TYPE=="pgsql") {
    if ($type=="FULLTEXT") {
	  if (!sql_query(sprintf("ALTER TABLE %s ADD COLUMN %s_fti tsvector",$table,$field))) return false;
	  if (!sql_query(sprintf("CREATE INDEX %s ON %s USING gin(%s_fti)",$name,$table,$field)) and
	      !sql_query(sprintf("CREATE INDEX %s ON %s USING gist(%s_fti)",$name,$table,$field))) return false;
	  if (sql_query(sprintf("CREATE TRIGGER %s_update BEFORE UPDATE OR INSERT ON %s FOR EACH ROW EXECUTE PROCEDURE tsearch2(%s_fti, %s)",$field,$table,$field,$field))) return true;
	} else {
      if (sql_query(sprintf("CREATE INDEX %s ON %s(%s)",$name,$table,$field))) return true;
	}
  }
  return false;
}

static function table_rename($table,$new_table) {
  if (self::table_exists($new_table)) return true;
  if (!self::table_exists($table)) return true;
  if (sql_query(sprintf("alter table %s rename to %s",$table,$new_table))) return true;
  return false;
}

static function table_column_rename($table,$column,$new_column) {
  if (self::table_column_exists($table,$new_column)) return true;
  if (!self::table_column_exists($table,$column)) return true;
  if (SETUP_DB_TYPE=="mysql") {
    $type = "";
    $sql = sprintf("show columns from %s",$table);
    if (($dbcolumns = sql_fetch($sql)) === false) return false;
    foreach ($dbcolumns as $col) {
      if ($col["Field"]==$column) $type = str_replace(",0","",$col["Type"]);
    }
    if (sql_query(sprintf("alter table %s change %s %s %s",$table,$column,$new_column,$type))) return true;
  } else if (SETUP_DB_TYPE=="sqlite") {
    if (!sql_query(sprintf("alter table %s add column %s",$table,$new_column))) return false;
    if (sql_query(sprintf("update %s set %s=%s,%s=''",$table,$new_column,$column,$column))) return true;
  } else { // pgsql
    if (sql_query(sprintf("alter table %s rename column %s to %s",$table,$column,$new_column))) return true;
  }
  return false;
}

static function table_change_column($table,$field_name,$ftype) {
  $type = "";
  if (SETUP_DB_TYPE=="mysql") {
    $sql = sprintf("show columns from %s",$table);
    if (($dbcolumns = sql_fetch($sql)) === false) {
	  return false;
	}
    foreach ($dbcolumns as $column) {
      if ($column["Field"]==$field_name) $type = str_replace(",0","",$column["Type"]);
    }
    if ($type!=str_replace(" default 0","",$ftype)) {
      if (!sql_query(sprintf("ALTER TABLE %s MODIFY %s %s",$table,$field_name,$ftype))) {
		return false;
	  }
    }
  } else if (SETUP_DB_TYPE=="pgsql") {
    if (($dbcolumns=pg_meta_data(sys::$db,$table)) === false) return false;
    foreach ($dbcolumns as $key=>$column) {
      if ($key==$field_name) $type = $column["type"];
    }
	if (strpos($type,"default 0")) $addon = sprintf(", ALTER %s set default 0",$field_name); else $addon = "";
	$ftype = str_replace(" default 0","",$ftype);
    if ($type!=$ftype) {
      if (!sql_query(sprintf("ALTER TABLE %s ALTER %s TYPE %s",$table,$field_name,$ftype).$addon)) return false;
    }
  }
  // sqlite: no change
  return true;
}

static function table_add_column($schema_name,$field_name,$ftype,$default) {
  if (sql_query(sprintf("ALTER TABLE %s ADD %s %s",$schema_name,$field_name,$ftype))) {
    if ($default!="" and !sys_strbegins($default,"|#")) {
	  $default = sys_correct_quote($default);
	  if (!sql_query(sprintf("update %s set %s=%s",$schema_name,$field_name,$default))) return false;
	}
	return true;
  }
  return false;
}

static function table_exists($table) {
  if (sql_query(sprintf("SELECT 1 FROM %s LIMIT 1",$table))) return true;
  return false;
}

static function table_column_translate($table, $column, $vals) {
  if (!self::table_column_exists($table, $column)) return;
  foreach ($vals as $key=>$val) {
	db_update($table,array($column=>$val),array($column."=@val@"),array("val"=>sys_remove_trans($key)),array("no_defaults"=>1));
  }
}
  
static function table_column_exists($table,$column) {
  if (sql_query(sprintf("SELECT %s FROM %s LIMIT 1",$column,$table))) return true;
  return false;
}

static function create_database($database) {
  if (SETUP_DB_TYPE=="mysql") {
    return sql_query(sprintf("create database %s",$database));
  } else if (SETUP_DB_TYPE=="pgsql") {
    return sql_query(sprintf("create database %s TEMPLATE template0 encoding='UTF8'",$database));
  } else {
    return true;
  }
}

private static function _merge_simplexml(&$schema, $custom_schema) {
  foreach ($custom_schema->attributes() as $attr_key => $attr_value) {
	if (isset($schema->attributes()->$attr_key)) {
	  $schema->attributes()->$attr_key = $attr_value;
	} else {
	  $schema->addAttribute($attr_key, $attr_value);
	}
  }
  foreach ($custom_schema->children() as $custom_child) {
	$tag = $custom_child->getName();
	$name = $custom_child->attributes()->name;
	$exists = $schema->xpath("/table/{$tag}[@name='{$name}']");
	
	if (!empty($exists)) {
	  $children = $exists[0]->children();
	  for ($i=count($children); $i>0; $i--) unset($children[0]);
		
	  $att = $exists[0]->attributes();
	  for ($i=count($att); $i>0; $i--) unset($att[0]);
	
	  self::_merge_simplexml($exists[0], $custom_child);
	} else {
	  $ref_node = null;
	  if ($custom_child->attributes()->before) {
	    $tag = $custom_child->getName();
		$name = $custom_child->attributes()->before;
   		$ref_node = $schema->xpath("/table/{$tag}[@name='{$name}']");
	  }
	  if (!empty($ref_node)) {
		$ref_node_dom = dom_import_simplexml($ref_node[0]);
		$custom_child_dom = dom_import_simplexml($custom_child);
		$node2 = $ref_node_dom->ownerDocument->importNode($custom_child_dom, true);
		$ref_node_dom->parentNode->insertBefore($node2, $ref_node_dom);
	  } else {
		$schema_temp = $schema->addChild($custom_child->getName());
		self::_merge_simplexml($schema_temp, $custom_child);
} } } }

private static function _apply_schema($schema_name,$schema_fields) {
  $types = self::_get_types();
  $text = $types["text"];
  $types = array(
    "text"=>$types["string"], "password"=>$types["string"], "checkbox"=>$types["bool"],
	"time"=>$types["date"], "date"=>$types["date"], "datetime"=>$types["date"],
	"id"=>$types["id"], "folder"=>$types["id"], "pid"=>$types["id"], "float"=>$types["float"], "int"=>$types["int"],
	"textarea"=>$types["text"], "dateselect"=>$types["text"], "select"=>$types["text"], "multitext"=>$types["text"], 
	"files"=>$types["text"], "dateselect_small"=>$types["string"], "select_small"=>$types["string"], "files_small"=>$types["string"],
  );
  $buffer = array();
  if (!self::table_exists($schema_name) and !sql_table_create($schema_name)) {
    $buffer[] = sprintf("SQL FAILED: %s\n","table_exists").sql_error();
  }
  foreach ($schema_fields as $field) {
	if (!isset($field["SIMPLE_TYPE"]) or isset($field["NODB"])) continue;
    $type = $field["SIMPLE_TYPE"];
	if (!empty($field["SIMPLE_SIZE"])) {
	  if ($type=="dateselect" and $field["SIMPLE_SIZE"]=="1") $type = "dateselect_small";
	  if ($type=="select" and $field["SIMPLE_SIZE"]=="1") $type = "select_small";
	  if ($type=="files" and $field["SIMPLE_SIZE"]=="1") $type = "files_small";
	}
	$ftype = $text;
	if (isset($types[$type])) $ftype = $types[$type];
	if (isset($field["DB_TYPE"])) $ftype[0] = $field["DB_TYPE"];
	if (isset($field["DB_SIZE"])) $ftype[1] = $field["DB_SIZE"];
	if (self::table_column_exists($schema_name,$field["NAME"])) {
	  $result = self::table_change_column($schema_name,$field["NAME"],sprintf($ftype[0],$ftype[1]));
	} else {
	  if (!empty($field["SIMPLE_DEFAULT"])) {
	    $default = $field["SIMPLE_DEFAULT"];
		if ($field["SIMPLE_TYPE"]=="select") {
		  $default = trim($default,"|");
		  if (substr_count($default,"|")!=0) $default = "|".$default."|";
		}
		if ($field["SIMPLE_TYPE"]=="checkbox") $default = str_replace("checked","1",$default);
	  } else $default = "";

	  if (!empty($field["STORE"]) and is_array($field["STORE"])) {
		foreach ($field["STORE"] as $store) {
		  list($class, $function, $params) = sys_find_callback("modify", $store["FUNCTION"]);
		  $default = call_user_func(array($class, $function), $default, array(), $params);
	  } }
	  $result = self::table_add_column($schema_name,$field["NAME"],sprintf($ftype[0],$ftype[1]),$default);
	}
	if (!$result) $buffer[] = sprintf("SQL FAILED: %s\n","sql_table_modify - ".$schema_name." - ".$field["NAME"]).sql_error();
  }
  $buffer = array_merge($buffer,self::_apply_indexes($schema_name,$schema_fields));
  if (count($buffer)!=0) {
    sys_alert(implode("\n\n",$buffer));
	return false;
  }
  return true;
}

private static function _apply_indexes($schema_name,$schema_fields) {
  $buffer = array();
  if (($indexes = self::table_get_indexes($schema_name)) === false) {
    $buffer[] = sprintf("SQL FAILED: %s\n","table_get_indexes").sql_error();
  }
  $primaries = array();
  foreach ($schema_fields as $field) {
	if (isset($field["INDEX"]) and (!isset($indexes[$field["NAME"]]) or !$indexes[$field["NAME"]]["index"])) {
	  if (!self::table_add_index("", "ind_".str_replace(array("simple_"),"",$schema_name)."_".$field["NAME"], $schema_name, $field["NAME"])) {
	    $buffer[] = sprintf("SQL FAILED: %s\n","table_add_index - ".$schema_name." - ".$field["NAME"]).sql_error();
	  }
	}
	if (isset($field["INDEX_FULLTEXT"]) and (!isset($indexes[$field["NAME"]]) or !$indexes[$field["NAME"]]["index"])) {
	  if (!self::table_add_index("FULLTEXT","ind_".str_replace("simple_","",$schema_name)."_".$field["NAME"],$schema_name,$field["NAME"])) {
	    $buffer[] = sprintf("SQL FAILED: %s\n","table_add_index_fulltext").sql_error();
	  }
	}
	if (isset($field["KEY"])) $primaries[] = $field["NAME"];
  }
  if (count($primaries)>0) {
    $change = false;
    foreach ($primaries as $primary) {
	  if (!isset($indexes[$primary]) or !$indexes[$primary]["primary"]) $change = true;
	}
	if ($change) {
	  self::table_drop_index($schema_name,"PRIMARY KEY");
	  if (!self::table_change_primary_key($schema_name,implode(",",$primaries))) {
	    $buffer[] = sprintf("SQL FAILED: %s\n","table_change_primary_key").sql_error();
  } } }
  if (!sql_table_optimize($schema_name)) $buffer[] = sprintf("SQL FAILED: %s\n","sql_table_optimize").sql_error();
  return $buffer;
}

private static function _change_schema_nodb($schema_content,$schema,$is_sys) {
  $result = "";
  if (!preg_match("|<table[^>]*? disable_rights=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="rights" displayname="Rights" schema="sys_nodb_rights" visibility="active" nosinglebuttons="true" noviewbuttons="true" right="admin"></view>
	  <view name="rights_edit" displayname="Edit rights" schema="sys_nodb_rights_edit" visibility="active" nosinglebuttons="true" noviewbuttons="true" schema_mode="edit" right="admin" template="edit"></view>
    ';
  }
  if (strpos($schema_content,' changeseen="true"')) {
    $result .= '
	  <field name="seen" simple_type="checkbox" hidden="true"/>
	  <rowfilter name="filter_sys1" views="display" type="_fgstyle" function="buildseenstyle|seen"/>
	';
  }
  if (!strpos($schema_content,'<field name="created"')) $result .= '<field name="created" simple_type="datetime" notinall="true"/>'."\n";
  if (!strpos($schema_content,'<field name="createdby"')) $result .= '<field name="createdby" simple_type="text" notinall="true"/>'."\n";
  if (!strpos($schema_content,'<field name="lastmodified"')) $result .= '<field name="lastmodified" simple_type="datetime" notinall="true"/>'."\n";
  if (!strpos($schema_content,'<field name="lastmodifiedby"')) $result .= '<field name="lastmodifiedby" simple_type="text" notinall="true"/>'."\n";
  if (!strpos($schema_content,'<field name="dsize"')) $result .= '<field name="dsize" simple_type="int" notinall="true"/>'."\n";
  if (!strpos($schema_content,'<field name="history"')) $result .= '<field name="history" displayname="History" simple_type="textarea" notinall="true"><onlyin views="history" /></field>'."\n";

  // needs to be text for smtp (validation would fail)
  if (!strpos($schema_content,'<field name="folder"')) $result .= '<field name="folder" displayname="Folder" simple_type="folder" simple_default_function="getfolder" hidden="true"></field>';
  if (!strpos($schema_content,'<field name="id"')) $result .= '<field name="id" simple_type="id" hidden="true"></field>'."\n";

  $matches = "";
  preg_match_all('|simple_tab="(.*?)"|msi',$schema_content,$matches);
  if (count($matches)>0) {
    $tabs = array();
	foreach ($matches[1] as $tab) $tabs = array_merge($tabs,explode("|",$tab));
	foreach (array_unique($tabs) as $tab) {
	  if (!strpos($schema_content,'<tab name="'.$tab.'"')) $result .= '<tab name="'.$tab.'"/>'."\n";
	}
  }
  if (!preg_match("|<table[^>]*? disable_schema=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="schema" displayname="Schema" schema="sys_nodb_schema" visibility="bottom" nosinglebuttons="true" noviewbuttons="true" template_mode="flat" right="admin"></view>
    ';
  }
  if (!strpos($schema_content,'<tab name="general"')) $result .= '<tab name="general" displayname="General" />'."\n";

  if (preg_match("|<table[^>]*? enable_new=\"true\".*?>|msi",$schema_content)) $result = '
	<viewbutton name="paste" displayname="Paste" onclick="asset_action(\'paste\', 45000);" right="write" accesskey="v"/>
	<view name="edit_as_new" displayname="Edit as new" template="edit" schema_mode="edit_as_new" nosinglebuttons="true" noviewbuttons="true" right="write" accesskey="r"></view>
	<view name="new" displayname="New" template="edit" limit="1" default_sql="no_select" schema_mode="new" nosinglebuttons="true" noviewbuttons="true" right="write" accesskey="n"></view>
  '.$result;

  if (preg_match("|<table[^>]*? enable_new_only=\"true\".*?>|msi",$schema_content)) {
    $result = '
	  <viewbutton name="paste" displayname="Paste" onclick="asset_action(\'paste\', 45000);" right="write" accesskey="v"/>
	  <view name="new" displayname="New" template="edit" limit="1" default_sql="no_select" schema_mode="new" nosinglebuttons="true" noviewbuttons="true" right="write" accesskey="n"></view>
    '.$result;
  }
  if (preg_match("|<table[^>]*? disable_paste=\"true\".*?>|msi",$schema_content)) {
    $result = preg_replace("!<viewbutton name=\"paste\"[^>]+>!","",$result);
  }

  if (!preg_match("|<table[^>]*? disable_copy=\"true\".*?>|msi",$schema_content)) $result = '
	<viewbutton name="copy" displayname="Copy" onclick="if (asset_form_selected()) asset_action(\'copy\');" accesskey="c"/>
  '.$result;
  
  if ((preg_match("|<table[^>]*? enable_purge=\"true\".*?>|msi",$schema_content) or
  	  preg_match("|<table[^>]*? enable_delete=\"true\".*?>|msi",$schema_content)) and 
	  !preg_match("|<table[^>]*? disable_cut=\"true\".*?>|msi",$schema_content)
  ) $result = '
	<viewbutton name="cut" displayname="Cut" onclick="if (asset_form_selected()) asset_action(\'cut\');" right="write" accesskey="x"/>
  '.$result;

  if (preg_match("|<table[^>]*? enable_purge=\"true\".*?>|msi",$schema_content)) $result .= '
	<viewbutton name="purge" displayname="Delete" onclick="if (asset_form_selected() &amp;&amp; confirm(\'Really delete the dataset(s) ?\')) asset_action(\'purge\');" right="write" accesskey="d"/>
  ';
  if (preg_match("|<table[^>]*? enable_delete=\"true\".*?>|msi",$schema_content)) $result .= '
	<viewbutton name="delete" displayname="Delete" onclick="if (asset_form_selected() &amp;&amp; confirm(\'Really delete the dataset(s) ?\')) asset_action(\'delete\');" right="write" accesskey="d"/>
  ';
  if (preg_match("|<table[^>]*? enable_edit=\"true\".*?>|msi",$schema_content)) {
    if (preg_match("!<table[^>]*? enable_asset_rights=\"(full|owner_write|owner_read)\".*?>!msi",$schema_content)) {
	  $addon = "where=\"@permission_sql_write_nq@\"";
	} else $addon = "";
	$result = '
	  <view name="edit" displayname="Edit" schema_mode="edit" showinsingleview="true" right="write" enable_calendar="" accesskey="e" '.$addon.'></view>
    '.$result;
  }

  if (preg_match("|<table[^>]*? enable_empty=\"true\".*?>|msi",$schema_content)) $result .= '
	<viewbutton name="empty" displayname="Empty folder" onclick="if (confirm(\'Really empty the folder ?\') &amp;&amp; confirm(\'REALLY delete ALL datasets ?\')) asset_action(\'empty\', 30000);" right="write" accesskey="y"/>
  ';
  if (preg_match("|<table[^>]*? enable_purgeall=\"true\".*?>|msi",$schema_content)) $result .= '
	<viewbutton name="purgeall" displayname="Empty folder" onclick="if (confirm(\'Really empty the folder ?\') &amp;&amp; confirm(\'REALLY delete ALL datasets ?\')) asset_action(\'purgeall\', 30000);" right="write" accesskey="y"/>
  ';
  
  if (!preg_match("|<table[^>]*? disable_search=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="search" displayname="Search" template="display" schema="sys_search" visibility="active" nosinglebuttons="true" noviewbuttons="true" nosqlfolder="true"></view>
    ';
  }
  $schema_content = str_replace("</table>",$result."</table>",$schema_content);

  if (!preg_match("|<table[^>]*? name=\".*?>|msi",$schema_content)) {
    $schema_content = str_replace("<table ","<table name=\"simple_".($is_sys?"sys_":"").$schema."\" ",$schema_content);
  }
  if (!preg_match("|<table[^>]*? modulename=\".*?>|msi",$schema_content)) {
    $schema_content = str_replace("<table ","<table modulename=\"".($is_sys?"sys_":"").$schema."\" ",$schema_content);
  }
  $schema_content = self::_change_schema_types($schema_content);
  return $schema_content;
}

private static function _change_schema_types($schema_content) {
  $schema_content = preg_replace('|(<field [^>]*?)/>|msi',"\\1></field>",$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? name="id"[^>]*?>)(.*?</field>)|msi',"\\1<KEY/>\\2",$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? name="folder"[^>]*?>)(.*?</field>)|msi',"\\1<INDEX/>\\2",$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? name="pid"[^>]*?>)(.*?</field>)|msi',"\\1<INDEX/>\\2",$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="float"[^>]*?>)(.*?</field>)|msi','\\1<validate function="float"/><store function="storefloat"/>\\2',$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="int"[^>]*?>)(.*?</field>)|msi','\\1<validate function="integer"/>\\2',$schema_content);

  $schema_content = preg_replace('|(<field[^>]*? simple_type="date"[^>]*?>)(.*?</field>)|msi','\\1<validate function="date"/><store function="datetime_to_int"/><restore function="dateformat"/>\\2',$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="dateselect"[^>]*?>)(.*?</field>)|msi','\\1<validate function="date"/><store function="datetime_to_int"/><restore function="dateformat"/>\\2',$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="time"[^>]*?>)(.*?</field>)|msi','\\1<validate function="time"/><store function="datetime_to_int"/><restore function="dateformat||g:i a"/>\\2',$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="datetime"[^>]*?>)(.*?</field>)|msi','\\1<validate function="datetime"/><store function="datetime_to_int"/><restore function="dateformat||m/d/Y g:i a"/>\\2',$schema_content);

  $schema_content = preg_replace('|(<field[^>]*? simple_type="checkbox"[^>]*?>)(.*?</field>)|msi','\\1<filter views="all" function="replacechecked"/><store function="storechecked"/>\\2',$schema_content);
  $schema_content = preg_replace('|(<field[^>]*? simple_type="password"[^>]*?>)(.*?</field>)|msi','\\1<store function="sha1"/><validate function="password"/><restore views="all" function="hidepassword"/>\\2',$schema_content);

  $schema_content = preg_replace('|(<field[^>]*? simple_type="files"[^>]*?>)(.*?</field>)|msi','\\1<filter views="all" function="basename"/><validate function="checkvirus"/><validate function="check_uploaded_file"/>\\2',$schema_content);

  return $schema_content;
}

private static function _change_schema($schema_content) {
  $result = "";
  // database specific 
  
  if (preg_match("|<table[^>]*? enable_asset_rights=\"full\".*?>|msi",$schema_content)) {
    $schema_content = str_replace("<table ","<table where=\"@permission_sql_read_nq@\" ",$schema_content);
    $result .= '
	  <tab name="general" displayname="General" />
	  <tab name="permissions" displayname="Permissions" />
	  <field name="rread_users" displayname="Read access (users)" simple_type="select" no_search_index="true" simple_default="anonymous" simple_tab="permissions">
    	<data title="Users" function="dbselect|simple_sys_users|username,concat(lastname;\' \';firstname)||lastname asc|10"/>
		<data title="Default" values="anonymous"/>
		<link value="index.php?find=asset|simple_sys_users||username=@rread_users@&amp;view=details"/>
	  </field>
	  <field name="rread_groups" displayname="Read access (groups)" simple_type="select" no_search_index="true" simple_tab="permissions">
	    <data function="dbselect|simple_sys_groups|groupname,groupname||groupname asc|10"/>
		<link value="index.php?find=asset|simple_sys_groups||groupname=@rread_groups@&amp;view=display"/>
	  </field>
	  <field name="rwrite_users" displayname="Write access (users)" simple_type="select" no_search_index="true" simple_default="anonymous" simple_tab="permissions">
	    <data title="Users" function="dbselect|simple_sys_users|username,concat(lastname;\' \';firstname)||lastname asc|10"/>
		<data title="Default" values="anonymous"/>
		<link value="index.php?find=asset|simple_sys_users||username=@rwrite_users@&amp;view=details"/>
	  </field>
	  <field name="rwrite_groups" displayname="Write access (groups)" simple_type="select" no_search_index="true" simple_tab="permissions">
    	<data function="dbselect|simple_sys_groups|groupname,groupname||groupname asc|10"/>
		<link value="index.php?find=asset|simple_sys_groups||groupname=@rwrite_groups@&amp;view=display"/>
	  </field>
    ';
  }
  if (preg_match("|<table[^>]*? enable_asset_rights=\"owner_write\".*?>|msi",$schema_content)) {
    $result .= '
	  <tab name="general" displayname="General" />
	  <tab name="permissions" displayname="Permissions" />
	  <field name="rwrite_users" displayname="Write access (users)" simple_type="select" no_search_index="true" simple_default="anonymous" simple_default_function="getusername" simple_tab="permissions">
	    <data title="Users" function="dbselect|simple_sys_users|username,concat(lastname;\' \';firstname)||lastname asc|10"/>
		<data title="Default" values="anonymous"/>
		<link value="index.php?find=asset|simple_sys_users||username=@rwrite_users@&amp;view=details"/>
	  </field>
	  <field name="rwrite_groups" displayname="Write access (groups)" simple_type="select" no_search_index="true" simple_tab="permissions">
    	<data function="dbselect|simple_sys_groups|groupname,groupname||groupname asc|10"/>
		<link value="index.php?find=asset|simple_sys_groups||groupname=@rwrite_groups@&amp;view=display"/>
	  </field>
    ';
  }  
  
  if (preg_match("|<table[^>]*? enable_asset_rights=\"owner_read\".*?>|msi",$schema_content)) {
    $schema_content = str_replace("<table ","<table where=\"@permission_sql_read_nq@\" ",$schema_content);
    $result .= '
	  <tab name="general" displayname="General" />
	  <tab name="permissions" displayname="Permissions" />
	  <field name="rread_users" displayname="Read access (users)" simple_type="select" no_search_index="true" simple_default="anonymous" simple_default_function="getusername" simple_tab="permissions">
    	<data title="Users" function="dbselect|simple_sys_users|username,concat(lastname;\' \';firstname)||lastname asc|10"/>
		<data title="Default" values="anonymous"/>
		<link value="index.php?find=asset|simple_sys_users||username=@rread_users@&amp;view=details"/>
	  </field>
	  <field name="rread_groups" displayname="Read access (groups)" simple_type="select" no_search_index="true" simple_tab="permissions">
	    <data function="dbselect|simple_sys_groups|groupname,groupname||groupname asc|10"/>
		<link value="index.php?find=asset|simple_sys_groups||groupname=@rread_groups@&amp;view=display"/>
	  </field>
	  <field name="rwrite_users" displayname="Write access (users)" simple_type="select" no_search_index="true" simple_default="anonymous" simple_default_function="getusername" simple_tab="permissions">
	    <data title="Users" function="dbselect|simple_sys_users|username,concat(lastname;\' \';firstname)||lastname asc|10"/>
		<data title="Default" values="anonymous"/>
		<link value="index.php?find=asset|simple_sys_users||username=@rwrite_users@&amp;view=details"/>
	  </field>
	  <field name="rwrite_groups" displayname="Write access (groups)" simple_type="select" no_search_index="true" simple_tab="permissions">
    	<data function="dbselect|simple_sys_groups|groupname,groupname||groupname asc|10"/>
		<link value="index.php?find=asset|simple_sys_groups||groupname=@rwrite_groups@&amp;view=display"/>
	  </field>
    ';
  }  
  
  if (!preg_match("|<table[^>]*? disable_history=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="history" displayname="History" visibility="button" template="details" accesskey="i" icon="history.png" show_preview="true"></view>
    ';
  }

  if (!preg_match("|<table[^>]*? disable_structure=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="structure" displayname="Structure" schema="sys_nodb_structure" visibility="bottom" nosinglebuttons="true" noviewbuttons="true" right="admin"></view>
    ';
  }
  if (!preg_match("|<table[^>]*? disable_index=\"true\".*?>|msi",$schema_content)) {
    $result .= '
	  <view name="index" displayname="Index" schema="sys_nodb_index" visibility="bottom" nosinglebuttons="true" noviewbuttons="true" right="admin"></view>
    ';
  }

  if (!preg_match("|<table[^>]*? disable_bgcolor=\"true\".*?>|msi",$schema_content) and !strpos($schema_content,'<field name="bgcolor"')) {
    $result .= '<rowfilter name="filter_sys0" views="all" type="_bgstyle" function="buildbgcolor|bgcolor"/>
      <field name="bgcolor" displayname="Color" simple_type="select" simple_size="1" hidden="true" editable="true">
      <data sort="asc" values="#DDDDFF_##_blue|#CCFFCC_##_green|#FFDDFF_##_magenta|#FFDDAA_##_orange|#FFCCCC_##_red|#FFFFDD_##_yellow|#FFFFFF_##_white"/>
	  </field>'."\n";
  }

  if (!preg_match("|<table[^>]*? disable_notification=\"true\".*?>|msi",$schema_content) and !strpos($schema_content,'<field name="notification"')) {
    $result .= '
	<field name="notification" displayname="Notification" simple_type="multitext" separator=", " hidden="true" editable="true">
      <data title="Users" function="dbselect|simple_sys_users|email,concat(lastname;\' \';firstname)|length(email)!=0 and activated=1|lastname asc|10"/>
      <data title="Groups" function="dbselect|simple_sys_groups|concat(\'@\';groupname),groupname|length(members)!=0 and activated=1|groupname asc|10"/>
      <data title="Contacts" function="dbselect|simple_contacts|email,concat(lastname;\' \';firstname)|length(email)!=0|lastname asc|10"/>
      <data title="Contact groups" function="dbselect|simple_contactgroups|concat(\'@\';groupname),groupname|length(members)!=0|groupname asc|10"/>
	  <description value="sys_alert(\'Syntax:\nabc@doecorp.com, cc:abcd@doecorp.com, bcc:abcde@diecorp.com,\n@Group, cc:@Group1, bcc:@Group2\');"/>
    </field>
    <field name="notification_summary" displayname="Notification summary" simple_type="text" hidden="true" editable="true">
    </field>'."\n";
  }
  
  $schema_content = str_replace("</table>",$result."</table>",$schema_content);

  return $schema_content;
}

private static function _get_types() {
  return array(
    "string"=>array("varchar(%s)","255"),
    "text"=>array("text",""), // = 64k
    "int"=>array("decimal(%s) default 0","10"),
    "date"=>array("decimal(%s)","10"),
    "bool"=>array("decimal(%s) default 0","1"),
    "id"=>array("decimal(%s)","15"),
    "float"=>array("float","")
  );
}

}