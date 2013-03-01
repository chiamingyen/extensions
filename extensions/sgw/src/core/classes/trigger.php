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

class trigger {

static function login() {
  db_update("simple_sys_users",array("status"=>"online"),array("username=@username@","coalesce(status,'')!='ooo'"),array("username"=>$_SESSION["username"]));
  return "";
}

static function logout() {
  db_update("simple_sys_users",array("status"=>"offline"),array("username=@username@","coalesce(status,'')!='ooo'"),array("username"=>$_SESSION["username"]));
  return "";
}

// syntax: trigger_new="exec:cmd"
// e.g. trigger_new="exec:echo @fieldname@ >/tmp/example.txt"
static function exec($id, $data, $params) {
  $cmd = implode(":",$params);
  if (preg_match_all("|@(.*?)@|i",$cmd,$matches,PREG_SET_ORDER)) {
	foreach ($matches as $match) {
	  if (count($match)!=2) continue;
	  $req_key = $match[1];
	  if (isset($data[$req_key])) {
		$cmd = str_replace("@".$req_key."@",escapeshellarg($data[$req_key]),$cmd);
  } } }
  if ($cmd=="") return "";
  sys_notification(sprintf("{t}System command executed: %s{/t}", $cmd));
  return sys_exec($cmd);
}

static function notify($id, $data, $params, $table) {
  db_notification_delete($table, $id);
  if (empty($data["notification"]) or folder_in_trash($data["folder"])) return "";
  if (class_exists("notify") and method_exists("notify", $table)) {
	return call_user_func(array("notify", $table), $id, $data, $params, $table);
  }
  return "";
}

static function duration($id, $data, $params, $table) {
  if ($data["ending"]==0 or $data["begin"]==0) return "";
  $duration = $data["ending"] - $data["begin"];
  if (!empty($params[0])) $duration += $params[0];
  if (!empty($data["pause"])) $duration -= ($data["pause"]*3600);
  db_update($table,array("duration"=>$duration),array("id=@id@"),array("id"=>$id));
  return "";
}

static function createedituser($id, $data) {
  if ($_SESSION["username"]==$data["username"]) login::process_login($data["username"]);
  return "";
}

static function createuser($id, $data) {
  $folder = SIMPLE_STORE."/home/".$data["username"]."/";
  if (!is_dir($folder)) {
	sys_mkdir($folder);
	sys_notification(sprintf("{t}Folder created.{/t} (%s)", $folder));
  }
  self::createedituser($id,$data);
  return "";
}

static function deleteuser($id, $data) {
  $count = db_select_value("simple_sys_users","count(*) as count","username=@username@",array("username"=>$data["username"]));
  $row_id = folder_from_path("^home_".$data["username"]);
  if (!empty($count) and !empty($row_id)) {
	folders::delete($row_id);
	db_update("simple_sys_groups",array("members"=>"replace(members,'|".sql_quote($data["username"])."|','|')"),array("members like @username@"),array("username"=>"%|".$data["username"]."|%"),array("quote"=>false));
	sys_notification(sprintf("{t}Folder structure moved to trash.{/t} (%s)", $data["username"]));
  }
  db_update("simple_sys_users",array("activated"=>"0"),array("id=@id@"),array("id"=>$id));
  // TODO trash home folder on local fs?
  return "";
}

static function increase_pwdexpire($id) {
  $expire = db_select_value("simple_sys_users","pwdexpires","id=@id@",array("id"=>$id));
  if (!empty($expire) and $expire < NOW) {
    $expires = NOW + 7776000; // 90 days
	db_update("simple_sys_users",array("pwdexpires"=>$expires),array("id=@id@"),array("id"=>$id));
  }
  return "";
}

static function deletegroup($id) {
  db_update("simple_sys_groups",array("activated"=>"0"),array("id=@id@"),array("id"=>$id));
  return "";
}

static function deletegroup_by_name($name) {
  db_update("simple_sys_groups",array("activated"=>"0"),array("groupname=@name@"),array("name"=>$name));
  sys_notification(sprintf("{t}Group deactivated.{/t} (%s)", $name));
  return "";
}

static function createeditforum($id) {
  db_update("simple_forum",array("threadid"=>$id),array("id=@id@","threadid=0"),array("id"=>$id));
  return "";
}

static function createeditcms($id, $data, $unused, $table) {
  db_update($table,array("lastmodified"=>"lastmodified+1"),array("data like @pagename@"),array("pagename"=>"%(:include ".$data["pagename"]."%"),array("quote"=>false,"no_defaults"=>1));

  $rows = db_select($table,"pagename",array("data like @content@","staticcache='1'"),"","",array("content"=>"%(:include ".$data["pagename"]."%"));
  if (is_array($rows)) {
    $rows[] = array("pagename"=>$data["pagename"]);
    foreach ($rows as $row) {
      $dir = SIMPLE_CACHE."/cms/".urlencode(strtolower($row["pagename"]));
	  if (is_dir($dir)) {
	    if (DEBUG) echo "delete ".$dir;
	    dirs_delete_all($dir,0,false);
} } } }

static function deletecms($id, $data, $unused, $table) {
  db_update($table,array("activated"=>"0"),array("id=@id@"),array("id"=>$id));
  $dir = SIMPLE_CACHE."/cms/".urlencode($data["pagename"]);
  if (is_dir($dir)) dirs_delete_all($dir,0,true);
  return "";
}

// e.g. addgroupmember(0, array("username"=>$username), array($group))
static function addgroupmember($id, $data, $params) {
  if (empty($params[0]) or empty($data["username"])) return "";
  $groupname = $params[0];
  $members = db_select_value("simple_sys_groups","members","groupname=@name@",array("name"=>$groupname));
  if (!empty($members)) $members = explode("|",trim($members,"|")); else $members = array();
  $members[] = $data["username"];
  $members = implode("|",array_unique($members));
  db_update("simple_sys_groups",array("members"=>"|".$members."|"),array("groupname=@name@"),array("name"=>$groupname));
  return "";
}

static function setgroupmembers($group, $members) {
  if ($members!="") $members = "|".trim($members,"|")."|";
  db_update("simple_sys_groups",array("members"=>$members),array("groupname=@group@"),array("group"=>$group));
  // TODO notify?
}

static function creategroup($name) {
  $row_id = db_select_value("simple_sys_groups","id","groupname=@name@",array("name"=>$name));
  $folder = folder_from_path("~sys_groups");
  
  if (empty($row_id) and !empty($folder)) {
	$id = sql_genID("simple_sys_groups")*100+$_SESSION["serverid"];
    $data = array("id"=>$id, "groupname"=>$name, "activated"=>1, "folder"=>$folder);
    $error_sql = db_insert("simple_sys_groups",$data);
	if ($error_sql=="") {
	  db_update_treesize("simple_sys_groups",$folder);
	  db_search_update("simple_sys_groups",$id,array(),array("groupname"=>"text"));
	  sys_log_stat("new_records",1);
	  sys_notification(sprintf("{t}Group created.{/t} (%s)", $name));
	} else return $error_sql;
  }
  return "";
}

static function editgroup($id, $data) {
  if ($data["members"]!="") $data["members"] = "|".trim($data["members"],"|")."|";
  if (sys_strbegins($data["groupname"],"project_")) {
	$project = substr($data["groupname"],8);
	db_update("simple_projects",array("participants"=>$data["members"]),array("projectname=@project@"),array("project"=>$project));
	sys_notification("{t}Update{/t} {t}Participants{/t}: {t}Project{/t} ".$project);
  }
  if (sys_strbegins($data["groupname"],"department_")) {
	$department = substr($data["groupname"],11);
	db_update("simple_departments",array("members"=>$data["members"]),array("departmentname=@department@"),array("department"=>$department));
	sys_notification("{t}Update{/t} {t}Members{/t}: {t}Department{/t} ".$department);
  }
  return "";
}

static function createeditproject($id, $data) {
  if ($data["finishsched"]!=0 and $data["startsched"]!=0) {
	db_update("simple_projects",array("duration"=>$data["finishsched"]-$data["startsched"]),array("id=@id@"),array("id"=>$id));
  }
  self::creategroup("project_".$data["projectname"]);
  self::setgroupmembers("project_".$data["projectname"], $data["participants"]);
  return "";
}

static function createeditdepartment($id, $data) {
  self::creategroup("department_".$data["departmentname"]);
  self::setgroupmembers("department_".$data["departmentname"], $data["members"]);
  return "";
}

static function deleteproject($id, $data) {
  $count = db_select_value("simple_projects","count(*) as count","projectname=@projectname@",array("projectname"=>$data["projectname"]));
  $row_id = folder_from_path("^projects_".$data["projectname"]);

  if (!empty($count) and !empty($row_id)) {
	folders::delete($row_id);
	sys_notification(sprintf("{t}Folder structure moved to trash.{/t} (%s)", $data["projectname"]));
  }
  self::deletegroup_by_name("project_".$data["projectname"]);
  return "";
}

static function deletedepartment($id, $data) {
  $count = db_select_value("simple_departments","count(*) as count","departmentname=@departmentname@",array("departmentname"=>$data["departmentname"]));
  $row_id = folder_from_path("^departments_".$data["departmentname"]);

  if (!empty($count) and !empty($row_id)) {
	folders::delete($row_id);
	sys_notification(sprintf("{t}Folder structure moved to trash.{/t} (%s)", $data["departmentname"]));
  }
  self::deletegroup_by_name("department_".$data["departmentname"]);
  return "";
}

static function deletechat($id, $data) {
  $count = db_select_value("simple_chat","count(*) as count","roomname=@roomname@",array("roomname"=>$data["roomname"]));
  if ($count==1) db_delete("simple_sys_chat2",array("room=@room@"),array("room"=>$data["roomname"]));
  return "";
}

static function sendmail_pop3($id, $data) {
  return self::sendmail($id, $data, null, "", true);
}

static function sendmail($id, $data, $unused, $table, $to_self=false) {
  if (isset($data["sendnow"]) and $data["sendnow"]==0) return "";

  $row = self::sendmail_getconn($_SESSION["username"],$data["efrom"]);
  if (USE_MAIL_FUNCTION or !empty($row["smtp"])) {
    if (!empty($row["smtp"])) sys_credentials($data["folder"], "smtp:".$row["smtp"]."/");
    if (!empty($row["email"])) $data["efrom"] = $row["email"];
    if (!empty($row["name"])) $data["name"] = $row["name"];
	$result = lib_smtp::insert("",$data,$data["folder"],$to_self,USE_MAIL_FUNCTION,true);
	if (is_array($result)) {
	  if ($table!="" and !strpos($table, "_nodb_")) db_update($table,array("headers"=>implode("\n", $result)),array("id=@id@"),array("id"=>$id));
	  $result = "";
	}
	if (empty($result) and sys_strbegins($data["subject"],SMTP_NOTIFICATION)) {
	  sys_notification(sprintf("{t}Notificaiton sent to: %s{/t}", $data["eto"]));
    }
  } else {
    $result = sprintf("{t}Mail identities{/t}: {t}SMTP not configured for %s{/t}",$_SESSION["username"]);
  }
  return $result;
}

static function createemail($id, $data, $unused, $table) {
  if (empty($data["message_html"]) and !empty($data["message"])) {
	$message_html = nl2br(modify::htmlquote(trim($data["message"])));
	db_update($table,array("message_html"=>$message_html),array("id=@id@"),array("id"=>$id));
  }
  if (empty($data["message"]) and !empty($data["message_html"])) {
	$message = modify::htmlmessage($data["message_html"]);
	db_update($table,array("message"=>$message),array("id=@id@"),array("id"=>$id));
  }
  return "";
}

static function calcappointment($id, $data, $unused, $table) {
  if ($data["begin"] > $data["ending"]) {
    $tmp = $data["begin"];
	$data["begin"] = $data["ending"];
	$data["ending"] = $tmp;
  }
  if (isset($data["allday"]) and $data["allday"]=="1") {
    $begin_arr = sys_getdate($data["begin"]);
    $data["begin"] = mktime(0,0,0,$begin_arr["mon"],$begin_arr["mday"],$begin_arr["year"]);
    $end_arr = sys_getdate($data["ending"]);
    $data["ending"] = mktime(23,59,0,$end_arr["mon"],$end_arr["mday"],$end_arr["year"]);
  }
  $repeatbegin = 0;
  $repeatend = 0;
  if (!empty($data["recurrence"])) {
    switch ($data["recurrence"]) {
	case "weeks":
      $repeatbegin = sys_date("w",$data["begin"]);
      $repeatend = sys_date("w",$data["ending"]);
	  break;
	case "months":
      $repeatbegin = sys_date("j",$data["begin"]);
      $repeatend = sys_date("j",$data["ending"]);
	  break;
	case "years":
      $repeatbegin = sys_date("z",$data["begin"]);
      $repeatend = sys_date("z",$data["ending"]);
	  if ($repeatbegin > 58) $repeatbegin--; // leap year
	  if ($repeatend > 58) $repeatend++;
	  break;
    }
  }

  $datas = array("begin"=>$data["begin"], "ending"=>$data["ending"], "duration"=>$data["ending"] - $data["begin"],
	"repeatbegin"=>$repeatbegin, "repeatend"=>$repeatend);

  $begin = strtotime("00:00:00", $data["begin"]);
  $days = min(ceil(($data["ending"] - $begin) / 86400), 31);

  $occurs = array();
  $occurs_weeks = array();
  self::_get_occurrence($begin, $days, $occurs, $occurs_weeks);
  $diff = $data["begin"] - $begin;
  $max_recurrence = strtotime("+3 years", NOW);
  
  $recurs = array();
  if (!empty($data["recurrence"])) {
	$recurs[] = $begin + $diff;
	$excludes = explode("|",$data["repeatexcludes"]);
	$counter = 0;
	while ($data["repeatcount"]!=1 and $counter < 150 and $begin < $max_recurrence) {
	  $begin = strtotime("+".(int)($data["repeatinterval"])." ".$data["recurrence"], $begin);

	  if ($data["repeatuntil"]!=0 and $begin > $data["repeatuntil"]) break;
	  if (in_array($begin, $excludes)) continue;

	  self::_get_occurrence($begin, $days, $occurs, $occurs_weeks);
	  $recurs[] = $begin + $diff;
	  if ($data["repeatcount"]!=0) $data["repeatcount"]--;
	  $counter++;
	}
  }
  if (!empty($occurs)) {
	$datas["until"] = strtotime(preg_replace("!(\d{2})(\d{2})(\d{2})!", "\\1-\\2-\\3", $occurs[count($occurs)-1]));
  } else {
	$datas["until"] = $data["ending"];
  }
  $datas["occurs"] = self::_scalarize($occurs);
  $datas["occurs_weeks"] = self::_scalarize(array_unique($occurs_weeks));
  $datas["recurs"] = self::_scalarize($recurs);
  
  if ($id!="") {
    db_update($table, $datas, array("id=@id@"), array("id"=>$id));
	self::notify($id, array_merge($data, $datas), array(), $table);
  } else {
	return $datas;
  }
  return "";
}

private static function _scalarize($value) {
  if (empty($value)) return "";
  return "|".implode("|", $value)."|";
}

private static function _get_occurrence($begin, $days, &$occurs, &$occurs_weeks) {
  for ($i=0; $i<$days; $i++) {
	$occurs[] = date("ymd", $begin);
	$occurs_weeks[] = date("y\wW", $begin);
	$begin += 86400;
  }
}

static function runxml($id, $data, $params) {
  list($file, $parent_anchor) = $params;
  $home = folder_from_path("^".$parent_anchor);
  if (!empty($home) and file_exists(sys_custom($file))) {
	sys_notification(sprintf("{t}Processing %s ...{/t}", $file));
    $folder = folders::create_default_folders($file,$home,false,$data);
	sys_notification(sprintf("{t}Folder structure created.{/t} (%s)", modify::getpath($folder)." / "));
  }
  return "";
}

static function create_ldap_user($ds, $base_dn, $username, $uid) {
  if ($base_dn=="") {
    $result_id = @ldap_read($ds,"","(objectclass=*)",array("namingContexts"));
    $attrs = ldap_get_attributes($ds, ldap_first_entry($ds,$result_id));
	$base_dn = $attrs["namingContexts"][0];
  }
  $res = ldap_search($ds,$base_dn,$uid."=".$username);
  if (ldap_count_entries($ds,$res)==1) {
	$data = ldap_get_attributes($ds, ldap_first_entry($ds,$res));
	if (is_array($data) and count($data)!=0) self::_create_ldap_user($username,$data);
  }
}

static function http_post($host, $port, $path, $data) {
  $errorNumber = 0;
  $errorString = "";
  if (($fp = @fsockopen($host, $port, $errorNumber, $errorString))) {
	$req = array();
	$req[] = "POST ".$path." HTTP/1.0";
    $req[] = "Host: ".$host;
    $req[] = "Content-Type: application/x-www-form-urlencoded";
    $req[] = "Content-Length: ".strlen($data);
	$req[] = "";
    $req[] = $data;
    fwrite($fp, implode("\r\n", $req));
	$resp = "";
	while (!feof($fp)) $resp .= fread($fp,8192);
	fclose($fp);
	return $resp;
  } else {
	return "ERROR: ".$errorString." ".$errorNumber;
  }
}

static function sendmail_getconn($username, $efrom) {
  $concat = sql_concat("concat(firstname;' ';lastname)");
  if ($efrom!="") {
    $row = db_select_first("simple_sys_identities",array("smtp","email","name"),array("email=@email@","users like @username_sql@"),"",array("username_sql"=>"%|".$username."|%","email"=>$efrom));
    if (empty($row["smtp"]) and !USE_MAIL_FUNCTION) {
	  $row["smtp"] = db_select_value("simple_sys_users","smtp","username=@username@",array("username"=>$username));
    }
  } else {
    $row = db_select_first("simple_sys_users",array("smtp","email","$concat as name"),"username=@username@","",array("username"=>$username));
    if (empty($row["smtp"]) and !USE_MAIL_FUNCTION) {
      $row = db_select_first("simple_sys_identities",array("smtp","email","name"),"users like @username_sql@","",array("username_sql"=>"%|".$username."|%"));
    }
  }
  return $row;
}

static function createedit_payroll($id, $data, $unused, $table) {
  $cdata = array();
  if ($data["status"]!="open") {
	if ($data["rwrite_users"]!="") $cdata["rwrite_users"] = "";
	if ($data["rwrite_groups"]!="" and $data["rwrite_groups"]!="admin_payroll") $cdata["rwrite_groups"] = "";
	if (count($cdata)>0) {
	  db_update($table,$cdata,array("id=@id@"),array("id"=>$id));
	  sys_notification("{t}Asset marked as read-only.{/t}");
	}
	if (self::_add_asset_permission_group($table,$id,array_merge($data,$cdata),"admin_payroll")) {
	  sys_notification(sprintf("{t}Permissions added for: %s{/t}", "admin_payroll"));
	}
  }
  return "";
}

private static function _createlocation($name) {
  $row_id = db_select_value("simple_locations","id","locationname=@name@",array("name"=>$name));
  $folder = folder_from_path("^locations");

  if (empty($row_id) and !empty($folder)) {
	$id = sql_genID("simple_locations")*100+$_SESSION["serverid"];
    $data = array("id"=>$id, "locationname"=>$name, "folder"=>$folder);
    $error_sql = db_insert("simple_locations",$data);
	if ($error_sql=="") {
	  db_update_treesize("simple_locations",$folder);
	  db_search_update("simple_locations",$id,array(),array("locationname"=>"text"));
	  sys_log_stat("new_records",1);
	} else return $error_sql;
  }
  return "";
}

private static function _create_ldap_user($username, $data) {
  $cdata = array();

  $mapping = array(
	"c"=>"country", "department"=>"department", "description"=>"jobdesc",
	"facsimiletelephonenumber"=>"fax", "fax"=>"fax", "givenname"=>"firstname", "ipphone"=>"skype",
	"l"=>"city", "mail"=>"email", "mobile"=>"mobile", "pager"=>"pager", "postalcode"=>"zipcode",
    "sn"=>"lastname", "st"=>"state", "street"=>"street", "streetaddress"=>"street",
    "telephonenumber"=>"phone", "wwwhomepage"=>"homepage", SETUP_AUTH_LDAP_ROOM=>"location"
  );
  foreach ($data as $key=>$val) {
    $key = strtolower($key);
    if (isset($mapping[$key]) and !empty($val[0])) $cdata[$mapping[$key]] = $val[0];
  }
  
  $username = strtolower($username);
  login::create_user($username,$cdata);
  if (!empty($cdata["location"])) self::_createlocation($cdata["location"]);

  if (isset($data[SETUP_AUTH_LDAP_MEMBEROF])) $groups = $data[SETUP_AUTH_LDAP_MEMBEROF]; else $groups = array();
  
  if (SETUP_AUTH_LDAP_GROUPS and is_array($groups) and count($groups)>1) {
	array_shift($groups);
	self::_create_ldap_groups($groups, $username);
  }
}

private static function _create_ldap_groups($groups, $username) {
  db_update("simple_sys_groups",array("members"=>"replace(members,'|".sql_quote($username)."|','|')"),array("members like @username@","createdby='auth_ldap'"),array("username"=>"%|".$username."|%"),array("quote"=>false));
  foreach ($groups as $group) {
	$group = ldap_dn2ufn($group);
	$group = substr($group,0,strpos($group,","));
	if (empty($group)) continue;
	
	// decode 2-byte unicode characters
	$group = preg_replace("/\\\\([A-F0-9]{2})/e",'chr(hexdec("\1"))',$group);
	self::creategroup($group);
	self::addgroupmember(0, array("username"=>$username), array($group));
  }
}

private static function _add_asset_permission_group($table, $id, $data, $group) {
  $cdata = array();
  if (!in_array($group,explode("|",$data["rread_groups"]))) {
	$cdata["rread_groups"] = $data["rread_groups"]."|".$group;
  }
  if (!in_array($group,explode("|",$data["rwrite_groups"]))) {
	$cdata["rwrite_groups"] = $data["rwrite_groups"]."|".$group;
  }
  if (count($cdata)==0) return false;
  foreach ($cdata as $key=>$val) $cdata[$key] = "|".trim($val,"|")."|";
  db_update($table,$cdata,array("id=@id@"),array("id"=>$id));
  return true;
}

}