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

// TODO2 fix resize iframe with images (graphviz)

define("NOCONTENT",true);
define("NOSESSION",true);
require("index.php");

sys_check_auth();
login_browser_detect();

$folder_offline = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"offline_".$_SESSION["username"]));
$rows = db_select("simple_offline","*","folder=@folder@","id asc","",array("folder"=>(int)$folder_offline));
if (!is_array($rows)) exit("No entries found.");

foreach ($rows as $key=>$row) $rows[$key] = populate_row($row);
uasort($rows, "sort_rows");  
  
sys::$smarty->assign("sync", isset($_REQUEST["sync"]) ? 1 : 0);
sys::$smarty->assign("sys", array(
  "app_title"=>APP_TITLE,
  "username"=>$_SESSION["username"],
  "browser"=>sys::$browser,
  "style"=>$_SESSION["style"]
));
sys::$smarty->assign("rows", $rows);
sys::$smarty->display("offline.tpl");

function sort_rows($a, $b) {
  if ($a["path"]==$b["path"]) return 0;
  if ($a["path"]<$b["path"]) return -1; else return 1;
}

function populate_row($row) {
  $row["view"] = "display";
  $row["folder"] = 0;
  if (($pos=strpos($row["url"],"?"))) {
    $url = array();
	parse_str(substr($row["url"],$pos+1),$url);
	$row = array_merge($row,$url);
  }
  $row["path"] = modify::getpath($row["folder"]);
  return $row;
}