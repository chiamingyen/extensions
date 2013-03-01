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

define("NOCONTENT",true);

require("index.php");
if (!sys_is_super_admin($_SESSION["username"])) sys_die("{t}Not allowed. Please log in as super administrator.{/t}");

if (empty($_REQUEST["console"])) $_REQUEST["console"] = "sys";

if (!empty($_GET['func'])) {
  if (!function_exists("json_encode")) require("lib/json/JSON.php");
  $result = call_user_func_array(array('funcs', $_GET['func']), explode(',', $_GET['params']));
  exit(json_encode($result));
}

$smarty = new Smarty;
$smarty->compile_dir = SIMPLE_CACHE."/smarty";
$smarty->template_dir = "templates";
$smarty->assign("console", $_REQUEST["console"]);

$code = "";
$tlimit = 0;
$mlimit = 0;
if (!empty($_REQUEST["code"])) {
  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
  $code = $_REQUEST["code"];
}
if (!empty($_REQUEST["name"])) {
  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
  $code = db_select_value("simple_sys_console", "command", "name=@name@", array("name"=>$_REQUEST["name"]));
}
if (!empty($_REQUEST["tlimit"])) {
  $tlimit = (int)$_REQUEST["tlimit"];
}
if (!empty($_REQUEST["mlimit"])) {
  $mlimit = (int)$_REQUEST["mlimit"];
}

$smarty->assign("code", $code);
$smarty->assign("tlimit", $tlimit ? $tlimit : "");
$smarty->assign("mlimit", $mlimit ? $mlimit : "");

if ($tlimit > 0) {
  set_time_limit($tlimit);
}
if ($mlimit > 0) {
  ini_set("memory_limit", $mlimit."M");
}

$start = microtime(true);
if ($_REQUEST["console"]=="php") {
  $content = "";
  if ($code!="") {
    ob_start();
	eval($code);  
	$content = ob_get_contents();
	ob_end_clean();
  }
  $title = "PHP Console (PHP ".phpversion().")";
  if ($content!="") $content = "<pre>".modify::htmlquote($content)."</pre>";
} else if ($_REQUEST["console"]=="sys") {
  $content = "";
  if ($code!="") $content = sys_exec(str_replace("\n","&",trim($code)));
  $title = "SYS Console:&nbsp; ".getcwd()." @ ".$_SERVER["SERVER_NAME"]."&nbsp; [".$_SERVER["SERVER_SOFTWARE"]."]";
  if ($content!="") $content = "<pre>".modify::htmlquote($content)."</pre>";
} else {
  $content = "";
  $title = "SQL Console:&nbsp; ".SETUP_DB_USER." @ ".SETUP_DB_NAME."&nbsp; [".SETUP_DB_TYPE." ".sgsml_parser::sql_version()."] ";
  $title .= sys_date("{t}m/d/y g:i:s a{/t}");
  if ($code!="") {
	if (($data = sql_fetch($code,false)) === false) {
      $content .= sql_error();
    } else if (is_array($data) and count($data)>0) {
      $content .= show_table($data, isset($_REQUEST["full_texts"]), isset($_REQUEST["vertical"]));
    } else if (SETUP_DB_TYPE=="mysql" and $num = mysql_affected_rows()) {
	  $content .= sprintf("{t}%s rows affected{/t}",$num);
	} else {
	  $content .= "{t}Empty{/t}";
	}
  }
  $smarty->assign("database", SETUP_DB_NAME);
  $smarty->assign("auto_complete", true);
}
if ($code!="") {
  $content .= "<br/>&nbsp;".sprintf("{t}%s secs{/t}", number_format(microtime(true)-$start, 4));
  $content .= ", ".sprintf("{t}%s M memory usage{/t}", number_format(memory_get_peak_usage(true)/1048576, 2));
}
if (DEBUG) $content = sys_remove_trans($content);

$smarty->assign("title", $title);
$smarty->assign("content", $content);
if (isset($_REQUEST["no_gui"])) {
  echo $content;
} else {
  $smarty->display("console.tpl");
}

function show_table($data, $full_texts=false, $vertical=true) {
  if (count($data)==0) return "";
  $limit = Max(round(220/count($data[0])),20);
  if ($vertical) $limit = 150;

  $content = "<table border='0' cellpadding='0' cellspacing='0'>";
  if (!$vertical) {
	$content .= "<tr>";
	foreach (array_keys($data[0]) as $value) {
	  if ($full_texts and strlen($value)>$limit) $value = substr($value,0,$limit)."...";
	  $content .= "<td nowrap><b>".modify::htmlquote($value)."</b></td>";
	}
	$content .= "</tr>";
  }
  foreach ($data as $dataset) {
	if ($vertical) {
	  foreach ($dataset as $key=>$value) {
		if ($value=="") continue;
		$content .= "<tr onmouseover='this.style.backgroundColor=\"#EFEFEF\";' onmouseout='this.style.backgroundColor=\"\";'>";
		$content .= "<td style='width:25%;' valign='top'><b>".modify::htmlquote($key)."</b></td>";
		$hint = $value;
		if (is_numeric($value) and strlen($value)==10) $value .= " / ".sys_date("Y-m-d H:i:s a", $value);
		if (!$full_texts and strlen($value)>$limit) $value = substr($value,0,$limit)."...";
		$content .= "<td title='".modify::htmlquote($hint)."'>".nl2br(modify::htmlquote($value))."</td></tr>";
	  }
	  $content .= "<tr><td colspan='2'><hr></td></tr>";
	} else {
	  $content .= "<tr onmouseover='this.style.backgroundColor=\"#EFEFEF\";' onmouseout='this.style.backgroundColor=\"\";'>";
	  foreach ($dataset as $key=>$value) {
		if ($vertical) {
		  if ($full_texts and strlen($key)>$limit) $key = substr($key,0,$limit)."...";
		  $content .= "<td nowrap><b>".modify::htmlquote($key)."</b></td>";
		}
		$hint = $value;
		if (is_numeric($value) and strlen($value)==10) $hint = sys_date("Y-m-d H:i:s a", $value);
		if (!$full_texts and strlen($value)>$limit) $value = substr($value,0,$limit)."...";
		$content .= "<td nowrap title='".modify::htmlquote($hint)."'>".modify::htmlquote($value)."</td>";
	  }
	  $content .= "</tr>";
	}
  }
  return $content."</table>";
}

class funcs {
  static function get_databases($prefix) {
    $result = array();
    $prefix = sql_quote($prefix);
	if (SETUP_DB_TYPE=="mysql")	{
	  $query = "SHOW databases like '{$prefix}%'";
	} else if (SETUP_DB_TYPE=="sqlite") {
	  return array();
	} else if (SETUP_DB_TYPE=="pgsql") {
	  $query = "SELECT datname FROM pg_database WHERE datname like '{$prefix}%'";
	}
	$data = sql_fetch($query);
	foreach ($data as $row) $result[] = array(implode($row).'.',implode($row));
	return $result;
  }
  static function get_tables($prefix,$add_db = true) {
    $result = array();
	if (!strpos($prefix,'.')) $prefix .= ".";
	$prefix = explode('.', $prefix);
	if ($prefix[0]=='') return array();
	$database = sql_quote($prefix[0]);
	$prefix = sql_quote($prefix[1]);

	if (SETUP_DB_TYPE=="mysql")	{
	  $query = "SELECT table_name,table_rows,table_comment
				FROM information_schema.tables
				WHERE table_schema = '{$database}'
				AND table_name like '{$prefix}%'";
	} else if (SETUP_DB_TYPE=="sqlite") {
	  $query = "select name as table_name,0 as table_rows, '' as table_comment from sqlite_master where type='table' and name like '{$prefix}%'";
	} else if (SETUP_DB_TYPE=="pgsql") {
	  $query = "SELECT table_name,
	  			(select reltuples from pg_class where relname = table_name) as table_rows,
	  			'' as table_comment
				FROM information_schema.tables	
				WHERE table_schema = 'public'
				AND table_catalog = '{$database}'
				AND table_name not like 'show_%'
				AND table_name like '{$prefix}%'";
	}
	$data = sql_fetch($query);
	$letters = range('a', 'k');
    $result[] = array("","---- {$database} ----");
	foreach ($data as $row) {
	  $letter = '';
	  $count = count($result);
	  for ($i=0; $i<strlen($count); $i++) $letter .= $letters[substr($count,$i,1)];
	  if ($add_db) $alias = $database.'.'; else $alias = ''; 
      $result[] = array("{$alias}{$row["table_name"]} {$letter}","{$row["table_name"]} ({$row["table_rows"]} rows {$row["table_comment"]})");
    }
	if (count($result)==1) return array();
    return $result;
  }

  static function get_columns($prefix,$alias) {
    $result = array();
	$prefix .= '..';
	$prefix = explode('.', $prefix);
	if ($prefix[0]=='' or $prefix[1]=='') return array();
	$database = sql_quote($prefix[0]);
	$table = sql_quote($prefix[1]);
	$prefix = sql_quote($prefix[2]);

	if (SETUP_DB_TYPE=="mysql")	{
	  $query = "SELECT column_name, column_type, column_comment
				FROM information_schema.columns
				WHERE table_schema = '{$database}'
				  AND table_name = '{$table}'
				  AND column_name like '{$prefix}%'";
	  $data = sql_fetch($query);
	} else if (SETUP_DB_TYPE=="sqlite") {
	  $data = sql_fetch("PRAGMA table_info({$table})");
	  if (is_array($data) and count($data)>0) {
	    foreach ($data as $key=>$row) {
	      $data[$key]["column_name"] = $row["name"];
	      $data[$key]["column_type"] = $row["type"];
		  $data[$key]["column_comment"] = "";
	    }
	  }
	} else if (SETUP_DB_TYPE=="pgsql") {
	  $query = "SELECT column_name, data_type||' '||coalesce(character_maximum_length,0) as column_type, '' as column_comment
				FROM information_schema.columns
				WHERE table_schema = 'public'
				  AND table_catalog = '{$database}'
				  AND table_name = '{$table}'
				  AND column_name like '{$prefix}%'";
	  $data = sql_fetch($query);
	}
    $result[] = array("","---- {$table} ({$database}) ----");
	if (is_array($data) and count($data)>0) {
	  foreach ($data as $row) {
        $result[] = array("{$alias}{$row["column_name"]}","{$row["column_name"]} ({$row["column_type"]} {$row["column_comment"]})");
      }
	}
    return $result;
  }
}