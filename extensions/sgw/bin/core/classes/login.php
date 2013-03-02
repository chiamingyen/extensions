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

class login {

static function validate_login($username,$password) {
  if ($username=="") {
    sys_log_message_alert("login",sprintf("Login failed from %s. (no username submitted)",_login_get_remoteaddr()));
	return false;
  }
  if ($username==SETUP_ADMIN_USER and sha1($password)==SETUP_ADMIN_PW) return true;
  if (SETUP_ADMIN_USER2!="" and $username==SETUP_ADMIN_USER2 and sha1($password)==SETUP_ADMIN_PW2) return true;
  if (sys_is_super_admin($username)) {
    sys_log_message_alert("login", sprintf("Login failed from %s.", _login_get_remoteaddr()));
	return false;
  }
  switch (SETUP_AUTH) {
    case "sql":
	  if (SELF_REGISTRATION && !empty($_REQUEST["signupform"])) {
		$data = $_REQUEST;
	    $data["password"] = $password;
		$data["self_registration"] = "1";
		if (!empty($data["email"])) $data["notification"] = $data["email"];
		if (SELF_REGISTRATION_CONFIRM) {
		  $data["activated"] = "0";
		  self::create_user($username,$data);
		  sys_die("Item successfully created.<br>
				   Self registration needs confirmation by an administrator.<br>
				   <a href='index.php'>Continue</a>");
		} else {
		  self::create_user($username,$data);
		}
	  }
	  if (!self::validate_login_sql($username,$password)) return false;
	  break;
	case "htaccess":
	  if ($username != $_SERVER["REMOTE_USER"]) return false;
	  if (SETUP_AUTH_AUTOCREATE) self::create_user($username);
	  break;
	case "ldap":
	  if (!self::validate_login_ldap($username,$password)) return false;
	  break;
	case "imap":
	  if (!self::validate_login_imap($username,$password)) return false;
	  if (SETUP_AUTH_AUTOCREATE) self::create_user($username);
	  break;
	case "smtp":
	  if (!self::validate_login_smtp($username,$password)) return false;
	  if (SETUP_AUTH_AUTOCREATE) self::create_user($username);
	  break;
	case "ntlm":
	  if ($username=="_invalid") $username = "";
	  if (!self::validate_login_ntlm($username,$password)) return false;
	  $username = $_SERVER["REMOTE_USER"];
	  if (SETUP_AUTH_AUTOCREATE) self::create_user($username);
	  break;
	case "gdata":
	  if (!self::validate_login_gdata($username,$password)) return false;
	  if (SETUP_AUTH_AUTOCREATE) self::create_user($username);
	  break;
  }
  if (SETUP_AUTH!="sql") {
	$data = array("activated"=>1, "neverexp"=>1, "pwdexpires"=>0, "password"=>"invalid", "lastmodifiedby"=>"auth_".SETUP_AUTH);
	db_update("simple_sys_users", $data, array("username=@username@"), array("username"=>$username));
  }
  
  $row = db_select_first("simple_sys_users",array("activated","neverexp","expires","pwdexpires"),"username=@username@","",array("username"=>$username));
  if (!isset($row["activated"])) {
    sys_log_message_alert("login",sprintf("Login failed from %s. (%s not in database)",_login_get_remoteaddr(),$username));
	return false;
  }
  if ($row["activated"]==0) {
    sys_log_message_alert("login",sprintf("Login failed from %s. (%s is not activated)",_login_get_remoteaddr(),$username));
	return false;
  }
  if ($row["neverexp"]==0 and NOW>$row["expires"]) {
    sys_log_message_alert("login",sprintf("Login failed from %s. (account of %s has expired)",_login_get_remoteaddr(),$username));
	return false;
  }
  return true;
}

static function validate_login_sql($username,$password) {
  $count = db_select_value("simple_sys_users","count(*) as count",array("username=@username@","password=@password@"),array("username"=>$username,"password"=>sha1($password)));
  if (!empty($count)) return true;
  sys_log_message_alert("login",sprintf("Login failed from %s. (sql) (Username: %s, wrong password)",_login_get_remoteaddr(),$username));
  return false;
}

static function validate_login_imap($username,$password) {
  $hostname = explode(":",SETUP_AUTH_HOSTNAME_IMAP);
  if (!isset($hostname[1])) $hostname[1] = 143;

  if (isset($hostname[2]) and !extension_loaded("openssl")) {
    sys_log_message_alert("login",sprintf("%s is not compiled / loaded into PHP.","IMAP / OpenSSL"));
    return false;
  }
  $imap = new Net_IMAP();
  if (PEAR::isError($e = $imap->connect((isset($hostname[2])?$hostname[2]."://":"").$hostname[0], $hostname[1]))) {
    sys_log_message_alert("login",sprintf("Connection error: %s [%s] (Username: %s, %s)", _login_get_remoteaddr(), "IMAP", $username, $e->getMessage()));
	return false;
  } else if (PEAR::isError($e = $imap->login($username, $password))) {
    sys_log_message_alert("login",sprintf("Login failed from %s. (imap) (Username: %s, %s)",_login_get_remoteaddr(),$username,$e->getMessage()));
	return false;
  }
  return true;
}

static function validate_login_smtp($username,$password) {
  $hostname = explode(":",SETUP_AUTH_HOSTNAME_SMTP);
  if (!isset($hostname[1])) $hostname[1] = 25;

  if (isset($hostname[2]) and !extension_loaded("openssl")) {
    sys_log_message_alert("login",sprintf("%s is not compiled / loaded into PHP.","SMTP / OpenSSL"));
    return false;
  }
  $smtp = new Net_SMTP((isset($hostname[2])?$hostname[2]."://":"").$hostname[0],$hostname[1]);
  if (PEAR::isError($e = $smtp->connect(10)) or PEAR::isError($e = $smtp->auth($username, $password))) {
    sys_log_message_alert("login",sprintf("Login failed from %s. (smtp) (Username: %s, %s)",_login_get_remoteaddr(),$username,$e->getMessage()));
    return false;
  } else return true;
}

static function validate_login_ntlm($username,$password) {
  if (!function_exists("java_get_base")) require("lib/java/java.php");
  if (!function_exists("java_require")) {
    sys_log_message_alert("login",sprintf("%s is not compiled / loaded into PHP.","PHP/Java Bridge"));
	return false;
  }
  java_require("jcifs-1.3.8_tb.jar");
  $conf = new JavaClass("jcifs.Config");

  $conf->setProperty("jcifs.smb.client.responseTimeout", "5000");
  $conf->setProperty("jcifs.resolveOrder","LMHOSTS,DNS");
  $conf->setProperty("jcifs.smb.client.soTimeout","10000");
  $conf->setProperty("jcifs.smb.lmCompatibility", "0");
  $conf->setProperty("jcifs.smb.client.useExtendedSecurity", false);

  $auth = sys_get_header("Authorization");
  if (empty($auth) and $username=="") {
    header("WWW-Authenticate: NTLM");
	$_REQUEST["logout"] = true;
	return false;
  }
  $session = new JavaClass("jcifs.smb.SmbSession");
  if (!empty($auth) and $username=="") {
    $result = $session->loginNtlm(SETUP_AUTH_HOSTNAME_NTLM,$auth);
  } else {
	$result = new Java("jcifs.smb.NtlmPasswordAuthentication","",$username,$password);
  }
  if (is_string(java_values($result))) {
    header("WWW-Authenticate: NTLM ".$result);
	header("HTTP/1.0 401 Unauthorized");
	exit;
  }
  $username = $result->getUsername();
  if (SETUP_AUTH_NTLM_SHARE) {
	$w = new Java("jcifs.smb.SmbFile",SETUP_AUTH_NTLM_SHARE,$result);
	$message = $w->canListFiles();
	if ($message == "Invalid access to memory location.") {
	  header("Location: index.php");
	  exit;
	}
  } else {
    $message = $session->logon(SETUP_AUTH_HOSTNAME_NTLM,$result);
  }
  if ($message!="" or $username=="") {
	sys_log_message_alert("login",sprintf("Login failed from %s. (ntlm) (Username: %s, %s)",_login_get_remoteaddr(),$username,$message));
	return false;
  }
  $_SERVER["REMOTE_USER"] = modify::strip_ntdomain($username);
  if (empty($_REQUEST["folder"])) $_REQUEST["redirect"] = 1;
  return true;
}

static function validate_login_gdata($username,$password) {
  if (!extension_loaded("openssl")) {
    sys_log_message_alert("login",sprintf("%s is not compiled / loaded into PHP.","gdata / OpenSSL"));
    return false;
  }
  if (SETUP_AUTH_DOMAIN_GDATA) $username .= "@".SETUP_AUTH_DOMAIN_GDATA;
  $url_auth = "https://www.google.com/accounts/ClientLogin?Email=".urlencode($username)."&Passwd=".urlencode($password)
    ."&accountType=HOSTED_OR_GOOGLE&source=SimpleGroupware&service=writely";
	
  $context = stream_context_create(array('http'=>array('timeout'=>5)));
  $result = file($url_auth, 0, $context);
  if (!empty($result[2])) return true;
  return false;
}

static function validate_login_ldap($username,$password) {
  if (!function_exists("ldap_connect")) sys_die(sprintf("%s is not compiled / loaded into PHP.","LDAP"));

  $hostname = SETUP_AUTH_HOSTNAME_LDAP;
  $username = preg_replace("/[\\\\*()#!|&=<>~ ]/", "", $username);
  if (empty($username)) sys_die("LDAP: no username submitted");

  if (!($ds=ldap_connect($hostname))) sys_die(sprintf("LDAP connection to host %s failed. (anonymous)",$hostname));
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

  if (SETUP_AUTH_LDAP_USER!="") {
    if (!@ldap_bind($ds,SETUP_AUTH_LDAP_USER,SETUP_AUTH_LDAP_PW)) {
	  sys_die(sprintf("LDAP connection to host %s failed.",$hostname));
	}
  } else {
    if (@ldap_bind($ds, $username.(SETUP_AUTH_DOMAIN!=""?"@".SETUP_AUTH_DOMAIN:""), $password)) {
	  if (SETUP_AUTH_AUTOCREATE) trigger::create_ldap_user($ds,"",$username,"sAMAccountName");
      return true;
    } else if (SETUP_AUTH_DOMAIN!="") {
	  sys_log_message_alert("login",sprintf("Login failed from %s. (ldap-ad) (%s)",_login_get_remoteaddr(),ldap_error($ds)));
	  return false;
	} else if (!@ldap_bind($ds)) sys_die("LDAP anonymous connection failed.");
  }
  $base_dn = SETUP_AUTH_BASE_DN;
  if ($base_dn=="") {
    $result_id = @ldap_read($ds,"","(objectclass=*)",array("namingContexts"));
    $attrs = ldap_get_attributes($ds, ldap_first_entry($ds,$result_id));
	if (isset($attrs["namingContexts"]) and is_array($attrs["namingContexts"])) {
	  $base_dn = $attrs["namingContexts"][0];
	}
  }
  if ($base_dn=="") sys_die("LDAP: no base DN given");
  if (SETUP_AUTH_LDAP_UID=="") sys_die("LDAP: no UID given");
  $res = ldap_search($ds,$base_dn,SETUP_AUTH_LDAP_UID."=".$username);
  
  $message = "";
  if ($res) {
	if (ldap_count_entries($ds,$res)==1) {
      $dn = ldap_get_dn($ds, ldap_first_entry($ds,$res));
      if (@ldap_bind($ds, $dn, $password)) {
		if (SETUP_AUTH_AUTOCREATE) trigger::create_ldap_user($ds,$base_dn,$username,SETUP_AUTH_LDAP_UID);
		return true;
	  }
	} else {
	  $message = "User not found. base_dn: ".$base_dn." Search: ".SETUP_AUTH_LDAP_UID."=".$username;
	}
  }
  if ($message=="") $message = ldap_error($ds);
  sys_log_message_alert("login",sprintf("Login failed from %s. (ldap) (%s)",_login_get_remoteaddr(),$message));
  return false;
}

static function show_login() {
  if (!empty($_SESSION["username"])) self::process_logout();
  if (isset($_COOKIE[SESSION_NAME])) unset($_COOKIE[SESSION_NAME]);
  if (!defined("NOCONTENT") and empty($_REQUEST["iframe"])) {
    define("NOCONTENT",true);
	if (sys::$browser["str"]!="unknown") {
	  if (!empty($_REQUEST["page"])) sys::$smarty->assign("page",$_REQUEST["page"]);
	  if (!empty($_REQUEST["find"]) and !empty($_REQUEST["view"])) {
        sys::$smarty->assign("login",array("",$_REQUEST["view"],$_REQUEST["find"]));
	  } else if (!empty($_REQUEST["folder"]) and !empty($_REQUEST["view"])) {
        sys::$smarty->assign("login",array($_REQUEST["folder"],$_REQUEST["view"]));
      } else if (!empty($_REQUEST["folder2"]) and !empty($_REQUEST["view2"])) {
        sys::$smarty->assign("login",array($_REQUEST["folder2"],$_REQUEST["view2"]));
      }
      if (isset($_REQUEST["item"]) and is_array($_REQUEST["item"]) and count($_REQUEST["item"])>0) {
		sys::$smarty->assign("login_item","&item[]=".implode("&item[]=",$_REQUEST["item"]));
	  }
	  $output = ob_get_contents();
	  ob_end_clean();
	  if ($output!='') sys_alert($output);
      if (sys::$alert) sys::$smarty->assign("alert", sys::$alert);
      sys::$smarty->assign("sys",array(
	    "browser"=>sys::$browser,
		"version"=>CORE_VERSION,
		"style"=>DEFAULT_STYLE
	  ));
	  sys::$smarty->display("login.tpl");
	  exit;
	}
  }
  // @see http://php.net/manual/en/features.http-auth.php
  header("HTTP/1.1 401 Authorization Required");
  header("WWW-Authenticate: Basic realm=\"Simple Groupware\"");
  exit;
}

static function process_login($username,$password="") {
  $id = session_id();
  if (!APC_SESSION and $id and (empty($_SESSION["username"]) or $_SESSION["username"]!=$username)) {
    $row = db_select_first("simple_sys_session",array("id","data","expiry"),"username=@username@","lastmodified desc",array("username"=>$username));
    if (!empty($row["id"])) {
	  $_SESSION = array();
      session_decode(rawurldecode($row["data"]));
	  if ($row["expiry"] < NOW) db_delete("simple_sys_session",array("id=@id@"),array("id"=>$row["id"]));
	}
	if (!db_count("simple_sys_session",array("id=@id@"),array("id"=>$id))) {
      db_insert("simple_sys_session",array("expiry"=>NOW+LOGIN_TIMEOUT,"id"=>$id));
	}
  }
  $_SESSION["serverid"] = _login_get_serverid();
  $_SESSION["username"] = $username;
  if ($password!="") $_SESSION["password"] = sys_encrypt($password,$id);
  
  if (!isset($_SESSION["history"])) $_SESSION["history"] = array();
  $_SESSION["groups"] = array();
  $_SESSION["folder_states"] = array();

  $base = dirname($_SERVER["SCRIPT_FILENAME"])."/";
  if (sys_is_super_admin($_SESSION["username"])) {
    $_SESSION["ALLOWED_PATH"] = array(
      $base.SIMPLE_STORE."/home/", $base.SIMPLE_CACHE."/debug/", $base.SIMPLE_STORE."/trash/",
	  $base.SIMPLE_CACHE."/preview/", $base.SIMPLE_STORE."/backup/"
	);
  } else {
    $_SESSION["ALLOWED_PATH"] = array(
	  $base.SIMPLE_STORE."/home/".$_SESSION["username"]."/", $base.SIMPLE_CACHE."/preview/"
	);
  }
  foreach (explode(",",SIMPLE_IMPORT) as $folder) {
    if ($folder=="" or !is_dir($folder)) continue;
	if ($folder[0]!="/" and !strpos($folder,":")) $folder = $base.$folder;
	$_SESSION["ALLOWED_PATH"][] = rtrim(str_replace("\\","/",$folder),"/")."/";
  }
  
  // TODO2 put in extra function and configure it with setup to fetch groups from somewhere else

  if (sys_is_super_admin($_SESSION["username"])) {
    $_SESSION["permission_sql"] = "1=1";
	$_SESSION["permission_sql_exception"] = "1=0";
	$_SESSION["disabled_modules"] = array();
  } else {
    $_SESSION["permission_sql"] = sql_regexp("r@right@_users",array($username,"anonymous"));
    $_SESSION["permission_sql_exception"] = "(rexception_users!='' and ".sql_regexp("rexception_users",array($username,"anonymous"),"|@view@:@right@:%s|").")";
	$_SESSION["disabled_modules"] = array_flip(explode("|", DISABLED_MODULES));

	$rows = db_select("simple_sys_groups","groupname",array("activated=1","members like @username_sql@"),"","",array("username_sql"=>"%|".$username."|%"));
    if (is_array($rows) and count($rows)>0) {
      foreach ($rows as $val) $_SESSION["groups"][] = $val["groupname"];
	  $_SESSION["permission_sql"] = "(".$_SESSION["permission_sql"]." or ".sql_regexp("r@right@_groups",$_SESSION["groups"]).")";
	  $_SESSION["permission_sql_exception"] = "(".$_SESSION["permission_sql_exception"]." or (rexception_groups!='' and ".sql_regexp("rexception_groups",$_SESSION["groups"],"|@view@:@right@:%s|")."))";
    }
  }
  $_SESSION["permission_sql_read"] = str_replace("@right@","read",$_SESSION["permission_sql"]);
  $_SESSION["permission_sql_write"] = str_replace("@right@","write",$_SESSION["permission_sql"]);
  $_SESSION["ip"] = _login_get_remoteaddr();
  $_SESSION["tickets"] = array("templates" => array("dbselect", "simple_templates", array("tplcontent","tplname"), array("tplname like @search@"),"tplname asc"));
  $_SESSION["treevisible"] = true;

  $row = db_select_first("simple_sys_users","*","username=@username@","",array("username"=>$username));
  if (!empty($row["cal_day_begin"])) {
    $_SESSION["day_begin"] = sys_date("G",$row["cal_day_begin"]-1)*3600;
    $_SESSION["day_end"] = sys_date("G",$row["cal_day_end"])*3600;
  } else {
    $_SESSION["day_begin"] = 25200; // 7:00 = 7*3600
    $_SESSION["day_end"] = 64800; // 18:00 = 18*3600
  }
  if (!empty($row["enabled_modules"])) {
	$row["enabled_modules"] = array_flip(explode("|", trim($row["enabled_modules"], "|")));
	$_SESSION["disabled_modules"] = array_diff_key($_SESSION["disabled_modules"], $row["enabled_modules"]);
  }
  
  if (!empty($row["timezone"])) $_SESSION["timezone"] = $row["timezone"]; else $_SESSION["timezone"] = "";

  if (!empty($row["home_folder"])) {
    $_SESSION["home_folder"] = "index.php?folder=".rawurlencode($row["home_folder"]);
  } else {
    if (sys_is_super_admin($username)) $anchor = "system"; else $anchor = "home_".$username;
	$_SESSION["home_folder"] = "index.php?folder=^".$anchor;
  }
  if ($id or isset($_REQUEST["login"])) {
    sys_log_stat("logins",1);
    sys_log_message_log("login",sprintf("login %s from %s with %s",$_SESSION["username"],$_SESSION["ip"],sys::$browser["str"]));
  }
  trigger::login();

  if (!empty($row["pwdexpires"]) and $row["pwdexpires"]<NOW) {
	sys_warning(sprintf("Password expired. (password of %s has expired)",$username));
	self::_redirect("index.php?view=changepwd&find=asset|simple_sys_users|1|username=".$_SESSION["username"]);
  } else if (!empty($_REQUEST["page"]))  {
    if (CMS_REAL_URL) $url = CMS_REAL_URL.$_REQUEST["page"];
      else $url = "cms.php?page=".$_REQUEST["page"];
	self::_redirect($url);
  } else if (!empty($_REQUEST["redirect"]))  {
	self::_redirect($_SESSION["home_folder"]);
  }
}

static function create_user($username, $data=array()) {
  $data["username"] = $username;
  $data["createdby"] = "auth_".SETUP_AUTH;
  if (empty($data["password"])) $data["password"] = "invalid";
  if (empty($data["email"])) $data["email"] = $username;
  if (!strpos($data["email"], "@")) $data["email"] .= "@invalid.local";
  if (empty($_SESSION["username"])) {
    $_SESSION["username"] = "anonymous";
    $_SESSION["permission_sql"] = "1=1";
    $_SESSION["permission_sql_read"] = "1=1";
	$_SESSION["groups"] = array();
    $_SESSION["serverid"] = _login_get_serverid();
  }
  $row_id = db_select_value("simple_sys_users","id","username=@username@",array("username"=>$username));
  if (!empty($row_id)) return;
  $row = db_select_first("simple_sys_tree","id","ftype=@ftype@","lft asc",array("ftype"=>"sys_users"));
  if (empty($row["id"])) return;
  $sgsml = new sgsml($row["id"], "new");
  $result = $sgsml->insert($data);
	  
  if (is_numeric($result)) {
    trigger::addgroupmember(0, array("username"=>$username), array("users_self_registration"));
	sys_notification("Item successfully created. (".$result.")");

  } else if (is_array($result) and count($result)>0) {
    $message = array();
    foreach ($result as $errors) {
	  foreach ($errors as $error) $message[] = $error[0].": ".$error[1];
	}
	sys_log_message_alert("login", implode("\n",$message));
  }
}

static function process_logout() {
  trigger::logout();
  if ($_SESSION["username"]!="anonymous") sys_log_message_log("login",sprintf("logout %s",$_SESSION["username"]));
  session_destroy();
}

private static function _redirect($url) {
  session_write_close();
  sys_redirect($url);
}

static function browser_detect_toString() {
  $s = new Smarty();
  $s->compile_dir = SIMPLE_CACHE."/smarty";
  $s->template_dir = "templates";
  $s->assign("agent", modify::htmlquote($_SERVER["HTTP_USER_AGENT"]));
  return $s->fetch("compatibility.tpl");
}
}