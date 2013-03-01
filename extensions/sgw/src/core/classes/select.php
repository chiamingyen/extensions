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

class select {

static function disabled_modules() {
  if (DISABLED_MODULES=="") return array();
  return array_intersect_key(self::modules_all(), array_flip(explode("|", DISABLED_MODULES)));
}

static function modules_all() {
  return array_merge(self::modules(true), self::mountpoints(true));
}

static function mountpoints($admin=false) {
  $results = array(
	"sys_nodb_bookmarks" => "{t}Bookmarks{/t}",
	"sys_nodb_csv_contacts" => "{t}Contacts{/t} (CSV)",
	"sys_nodb_ldif_contacts" => "{t}Contacts{/t} (LDIF)",
	"sys_nodb_icalendar" => "iCalendar",
	"sys_nodb_csv_calendar" => "{t}Calendar{/t} (CSV)",
	"sys_nodb_pmwiki" => "PmWiki",
	"sys_nodb_fs" => "{t}Files{/t}",
	"sys_nodb_cifs" => "CIFS",
	"sys_nodb_gdocs" => "Google Docs",
	"sys_nodb_rss" => "RSS",
	"sys_nodb_vcard" => "vCard",
	"sys_nodb_xml" => "XML",
	"sys_nodb_ldap" => "LDAP",
	"sys_nodb_imap" => "IMAP",
	"sys_nodb_pop3" => "POP3",
	"sys_nodb_smtp" => "SMTP"
  );
  asort($results);
  if (!$admin and isset($_SESSION["disabled_modules"])) return array_diff_key($results, $_SESSION["disabled_modules"]);
  return $results;
}

static function modules($admin=false) {
  $results = array();

  if (!isset($_SESSION["username"]) or !isset($_SESSION["disabled_modules"]) or sys_is_super_admin($_SESSION["username"])) {
	$admin = true;
  }
  $data = file_get_contents(sys_custom("modules/schema/modules.txt"));
  
  if ($admin) {
	$data .= "\n".file_get_contents(sys_custom("modules/schema_sys/modules.txt"));
  }
  if (file_exists(sys_custom("modules/schema/modules_ext.txt"))) {
	$data .= "\n{t}Extensions{/t}\n".file_get_contents(sys_custom("modules/schema/modules_ext.txt"));
  }
  if ($admin and file_exists(sys_custom("modules/schema_sys/modules_ext.txt"))) {
	$data .= "\n".file_get_contents(sys_custom("modules/schema_sys/modules_ext.txt"));
  }
  $groups = explode("\n\n",$data);
  foreach ($groups as $group) {
	$result = array();
    $group = explode("\n",$group);
	foreach ($group as $module) {
	  if ($module=="") continue;
	  $module = explode("|",$module);
	  if (!isset($module[1])) $result[] = " ".$module[0];
		else $result[ $module[0] ] = $module[1];
	}
	if (!DEBUG) asort($result);
	$results = array_merge($results, $result);
  }
  if (!$admin) return array_diff_key($results, $_SESSION["disabled_modules"]);
  return $results;
}

static function icons() {
  $files = sys_scandir("ext/icons/",array(".","..","unused","folder_icons.php"));
  return array_combine($files,$files);
}

static function icons_modules() {
  $files = sys_scandir("ext/modules/",array(".","..","unused","folder_icons.php"));
  return array_combine($files,$files);
}

static function timezones($default = false) {
  $timezones = timezone_identifiers_list();
  $timezones = array_combine($timezones,$timezones);
  if ($default) $timezones = array_merge(array("" => "{t}Default{/t}"), $timezones);
  return $timezones;
}

static function themes() {
  return array(
	"core"=>"simple core", "core_tree_icons"=>"simple core tree icons", "contrast"=>"simple contrast",
	"water"=>"simple water", "lake"=>"simple lake", "beach"=>"simple beach", "paradise"=>"simple paradise",
	"earth"=>"simple earth", "sunset"=>"simple sunset",	"nature"=>"simple nature",
	"desert"=>"simple desert", "black"=>"simple black", "rtl"=>"simple right-to-left"
  );
}

static function cms_templates() {
  $files = sys_scandir("templates/cms/",array(".","..","rss.tpl","sitemap.tpl"));
  return array_combine($files,$files);
}

static function dbselect($params,$vars=array(),$ticket=false) {
  $result = array();
  if (count($params)<4) return array();
  
  $vars["username"] = $_SESSION["username"];
  foreach ($vars as $key=>$val) {
	if (!is_array($val) and $val!="") $vars[$key."_sql"] = "%|".$val."|%";
  }
  if (!is_array($params[1])) $params[1] = explode(",",$params[1]);
  if (!is_array($params[2])) {
	if ($params[2]=="") $params[2] = array(); else $params[2] = array($params[2]);
  }
  if (empty($params[4])) $params[4] = 100;
  if (!is_array($params[4])) $params[4] = array(0, $params[4]);
  if (!empty($vars["page"]) and is_numeric($vars["page"])) $params[4][0] = ($vars["page"]-1)*$params[4][1];

  $table = $params[0];
  $where = $params[2];
  
  if (empty($params[5]) or $params[5]!="no_permissions") {
	$table = $params[0].", (select id as tid from simple_sys_tree where @rights@) e";
	$where[] = "folder=tid";

	$ftype = str_replace("simple_", "", $params[0]);
	static $asset_perm = null;
	if ($asset_perm==null) $asset_perm = explode("\n", file_get_contents(sys_custom("modules/core/select_asset_perm.txt")));
	if (in_array($ftype, $asset_perm)) $where[] = "@rights@";
  }
  $optional = array("sqlvarsnoquote"=>array("rights"=>$_SESSION["permission_sql_read"]));
  $rows = db_select($table, sql_concat($params[1]), $where, $params[3], $params[4], $vars, $optional);
  if (is_array($rows) and count($rows)>0) {
	foreach ($rows as $row) {
	  $data = array_shift($row);
	  if ($params[1][0]==$params[1][1]) $data2 = $data; else $data2 = rtrim(array_shift($row));
	  if (trim($data2)=="") $data2 = $data;
	  if (strlen($data2)>30) $data2 = substr($data2,0,30)."...";
	  if ($data!=$data2 and $data!="") {
		if (strlen($data)<20) $data2 .= " (".$data.")"; else $data2 .= " (".substr($data,0,20)."...)";
	  }
	  while (isset($result[$data])) $data .= " ";
	  $result[$data] = $data2;
	}
  }
  if (!empty($params[4])) {
	$count = db_count($table, $where, $vars, $optional);
	if ($count > $params[4][0]+$params[4][1]) $result["_overload_"] = true;
  }
  if ($ticket) {
	array_unshift($params, "dbselect");
	$result["_ticket_"] = "custom_".md5(serialize($params));
	$result["_params_"] = $params;
  }
  return $result;
}

static function other_users() {
  $values = array_diff(array(SETUP_ADMIN_USER, SETUP_ADMIN_USER2, "cron", "anonymous"), array(""));
  return array_combine($values, $values);
}

}