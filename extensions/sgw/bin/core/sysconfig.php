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

if (!defined("MAIN_SCRIPT")) exit;
if (!sys_is_super_admin($_SESSION["username"])) die("Not allowed. Please log in as super administrator.");

  ob_start();
  echo '
    <html>
    <head>
	<title>Simple Groupware</title>
	<style>
		body, h2, img, div, table.data, a {
		  background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif;
		}
		a,input,textarea { color: #0000FF; }
		input {
		  font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px;
		  vertical-align: middle; padding-left: 5px; padding-right: 5px; border-radius: 10px;
		}
		textarea {
		  font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA;
		  padding-left: 5px; padding-right: 5px; border-radius: 10px;
		}
		.checkbox, .radio { border: 0px; background-color: transparent; }
		.submit { color: #0000FF; background-color: #FFFFFF; width: 230px; font-weight: bold; }
	</style>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <body>
  ';
  $inputs = array(
    "app_title"=>array("Application title"),
    "cms_title"=>array("CMS homepage title"),
	"---",
    "setup_db_host"=>array("Database Hostname / IP"),
    "setup_db_user"=>array("Database User"),
    "setup_db_pw"=>array("Database Password"),
    "setup_db_name"=>array("Database Name"),
	"---",
    "setup_admin_user"=>array("Admin Username"),
    "setup_admin_pw"=>array("Admin Password"),
    "setup_admin_user2"=>array("Admin Username (2)"),
    "setup_admin_pw2"=>array("Admin Password (2)"),
	"---",
    "virus_scanner"=>array("Virus scanner"),
    "virus_scanner_params"=>array("Virus scanner: parameters"),
    "virus_scanner_display"=>array("Virus scanner: display filter"),
	"smtp_reminder"=>array("SMTP mail reminder"),
	"smtp_notification"=>array("SMTP mail notification"),
	"session_name"=>array("Session name in cookie"),
	"cms_homepage"=>array("Home page in the CMS (cms.php)"),
	"cms_real_url"=>array("Real URL format in the CMS (cms.php)"),
  );
  $auths = array(
    "sql"=>array("SQL",false,""),
	"htaccess"=>array("Apache based (.htaccess, Basic, Digest, etc.)",false,""),
	"ntlm"=>array("NTLM (Windows / Samba)",true,""),
	"ldap"=>array("LDAP",true,"(ldaps://server/ | ldap://server/)"),
	"imap"=>array("IMAP",true,"(server[:port[:ssl|tls]])"),
	"smtp"=>array("SMTP",true,"(server[:port[:ssl|tls]])"),
	"gdata"=>array("Google Apps",true,""),
  );
  $bools = array(
	"DISABLE_BASIC_AUTH"=>"Disable basic authentication",
    "ENABLE_ANONYMOUS"=>"Enable anonymous access",
	"ENABLE_ANONYMOUS_CMS"=>"Enable anonymous CMS",
    "SELF_REGISTRATION"=>"Enable self registration",
	"SELF_REGISTRATION_CONFIRM"=>"Self registration needs confirmation by an administrator.",
	"MOUNTPOINT_REQUIRE_ADMIN"=>"Require admin access to set mountpoints",
    "SETUP_AUTH_AUTOCREATE"=>"Enable automatic user creation<br>[htaccess, NTLM, LDAP, IMAP, SMTP]",
	"ARCHIVE_DELETED_FILES"=>"Archive deleted files",
    "SYNC4J"=>"Enable Sync4j / Funambol",
	"SYNC4J_REMOTE_DELETE"=>"Sync4j remote delete items",
	"ENABLE_WEBDAV"=>"Enable WebDAV",
	"ENABLE_WEBDAV_LOCKING"=>"Enable WebDAV file locking scripts",
	"ENABLE_EXT_MAILCLIENT"=>"Enable external mail client for 'mailto:' links",
	"USE_MAIL_FUNCTION"=>"Use the mail() function for sending mails (insecure)",
	"USE_SYSLOG_FUNCTION"=>"Use syslog() function for logging events",
	"USE_DEBIAN_BINARIES"=>"Use Debian binaries (Warning: not latest versions)",
	"FORCE_SSL"=>"Force SSL",
	"CHECK_DOS"=>"Check DoS Attacks",
	"CORE_COMPRESS_OUTPUT"=>"Compress output",
	"CORE_OUTPUT_CACHE"=>"Cache output",
	"APC_SESSION"=>"Use APC for session storage",
    "MENU_AUTOHIDE"=>"Automatically hide the menu",
	"TREE_AUTOHIDE"=>"Automatically hide the tree",
	"FIXED_FOOTER"=>"Fix paging bar to page bottom",
    "FDESC_IN_CONTENT"=>"Show folder description in content area",
	"DEBUG"=>"",
	"DEBUG_SQL"=>"",
	"DEBUG_IMAP"=>"",
	"DEBUG_POP3"=>"",
	"DEBUG_SMTP"=>"",
	"DEBUG_JAVA"=>"",
	"DEBUG_WEBDAV"=>"",
	"DEBUG_JS"=>"",
  );
  $caches = array(
    "ASSET_PAGE_LIMIT"=>"Maximum number of assets per page", "FOLDER_REFRESH"=>"Folder refresh period", 
    "LOGIN_TIMEOUT"=>"Session timeout", "LOCKING"=>"",
    "SYSTEM_SLOW"=>"", "DB_SLOW"=>"", "CMS_SLOW"=>"",
	"OUTPUT_CACHE"=>"",
    "CSV_CACHE"=>"","LDIF_CACHE"=>"","BOOKMARKS_CACHE"=>"","ICALENDAR_CACHE"=>"","RSS_CACHE"=>"","VCARD_CACHE"=>"",
	"XML_CACHE"=>"","IMAP_CACHE"=>"","IMAP_LIST_CACHE"=>"","IMAP_MAIL_CACHE"=>"","POP3_LIST_CACHE"=>"",
	"POP3_MAIL_CACHE"=>"","GDOCS_CACHE"=>"","GDOCS_LIST_CACHE"=>"","GDOCS_PREVIEW_LIMIT"=>"","CIFS_PREVIEW_LIMIT"=>"",
	"LDAP_LIST_CACHE"=>"","CMS_CACHE"=>"", "FILE_TEXT_LIMIT"=>"", "FILE_TEXT_CACHE"=>"", "INDEX_LIMIT"=>"",
	"SIMPLE_CACHE"=>"","SIMPLE_CUSTOM"=>"","SIMPLE_EXT"=>"","SIMPLE_IMPORT"=>"","CHMOD_DIR"=>"","CHMOD_FILE"=>""
  );
  $textareas = array(
	"smtp_footer"=>array("SMTP mail footer"),
	"invalid_extensions"=>array("Invalid file extensions")
  );
  $selects = array(
    "default_style"=>array("Default theme",select::themes()),
    "weekstart"=>array("Week start",array(
      "0"=>"Su", "1"=>"Mo", "2"=>"Tu", "3"=>"We",
      "4"=>"Th", "5"=>"Fr", "6"=>"Sa"
	)),
	"timezone"=>array("Time zone, current time: ".sys_date("g:i a"), select::timezones(true)),
  );
  $modules = select::modules_all();
  asort($modules);
  $multi_selects = array(
    "disabled_modules"=>array("Disabled modules",$modules),
  );
  echo '
    <div style="float:right;">
	<a href="http://www.simple-groupware.de/cms/AdministrationConfiguration" target="_blank">Help</a>
	</div>
    <div style="border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">
	Simple Groupware '.CORE_VERSION_STRING.'
	</div>
	<br>
	<div style="color:#FF0000;">
  ';
  $show_form = true;
  if (!empty($_REQUEST["setup_language"])) {
    $error = false;
	if (!sys_validate_token()) {
	  $error = true;
	  echo "Invalid security token";
	}
    if ($_REQUEST["setup_admin_user"]!=SETUP_ADMIN_USER and $validate=validate::username($_REQUEST["setup_admin_user"]) and $validate!="") {
	  $error = true;
	  echo "Admin Username: validation failed ".$validate;
	}
    if ($_REQUEST["setup_admin_user2"]!="" and $_REQUEST["setup_admin_user2"]!=SETUP_ADMIN_USER2 and $validate=validate::username($_REQUEST["setup_admin_user2"]) and $validate!="") {
	  $error = true;
	  echo "Admin Username (2): validation failed ".$validate;
	}
    if (!is_numeric($_REQUEST["login_timeout"]) or $_REQUEST["login_timeout"] <= 60) {
	  $error = true;
	  echo "Session timeout: validation failed";
	}
    if ($_REQUEST["setup_db_host"]=="") {
	  $error = true;
	  echo "missing field: Database Hostname / IP";
	}
    if ($_REQUEST["setup_db_user"]=="") {
	  $error = true;
	  echo "missing field: Database User";
	}
    if ($_REQUEST["setup_db_name"]=="") {
	  $error = true;
	  echo "missing field: Database Name";
	}
	$no_hash = false;
    if ($_REQUEST["setup_admin_pw"]=="" and $_REQUEST["setup_admin_user"]==SETUP_ADMIN_USER) {
	  $_REQUEST["setup_admin_pw"] = SETUP_ADMIN_PW;
	  $no_hash = true;
	} else if (strlen($_REQUEST["setup_admin_pw"])<5) {
	  $error = true;
	  echo "Admin Password: Password must be not null, min 5 characters.";
	}
	$no_hash2 = false;
    if ($_REQUEST["setup_admin_pw2"]=="" and $_REQUEST["setup_admin_user2"]==SETUP_ADMIN_USER2) {
	  $_REQUEST["setup_admin_pw2"] = SETUP_ADMIN_PW2;
	  $no_hash2 = true;
	} else if (strlen($_REQUEST["setup_admin_pw2"])<5 and $_REQUEST["setup_admin_user2"]!="") {
	  $error = true;
	  echo "Admin Password (2): Password must be not null, min 5 characters.";
	}
    if (empty($_REQUEST["setup_auth"])) {
	  $error = true;
	  echo "missing field: Authentication Mode";
	}
    if (!sql_connect($_REQUEST["setup_db_host"], $_REQUEST["setup_db_user"], $_REQUEST["setup_db_pw"], $_REQUEST["setup_db_name"])) {
	  $error = true;
      echo "Connection to database failed.\n".sql_error();
    }
	if (empty($_REQUEST["simple_cache"]) or !is_dir($_REQUEST["simple_cache"])) {
	  $error = true;
	  echo "SIMPLE_CACHE: validation failed ".$_REQUEST["simple_cache"];
	}
	if (empty($_REQUEST["simple_custom"]) or !is_dir($_REQUEST["simple_custom"])) {
	  $error = true;
	  echo "SIMPLE_CUSTOM: validation failed ".$_REQUEST["simple_custom"];
	}
	if (empty($_REQUEST["simple_ext"]) or !is_dir($_REQUEST["simple_ext"])) {
	  $error = true;
	  echo "SIMPLE_EXT: validation failed ".$_REQUEST["simple_ext"];
	}
    if (!empty($_REQUEST["apc_session"]) and !APC) {
	  $error = true;
	  echo sprintf("Please install the php-extension with name '%s'.", "apc");
	}
	if (empty($_REQUEST["setup_auth_ldap_groups"])) {
	  $_POST["setup_auth_ldap_groups"] = 0;
	  $_REQUEST["setup_auth_ldap_groups"] = 0;
	}
	if (!empty($_REQUEST["sync4j"])) {
	  echo sprintf("Processing %s ...","Funambol schema")."<br>";
	  if (SETUP_DB_TYPE=="mysql") {
		$data = preg_replace("!/\*.+?\*/!s","",file_get_contents("tools/funambolv7_syncML/mysql/funambol.sql"));
		$data = sys_remove_trans($data);
		if (($msg = db_query(explode(";",$data)))) {
		  $error = true;
		  echo "funambol.sql [mysql]: ".$msg."<br>";
		}
	  } else if (SETUP_DB_TYPE=="pgsql") {
	    $data = file_get_contents("tools/funambolv7_syncML/postgresql/funambol.sql");
		$data = sys_remove_trans($data);
		if (($msg = db_query($data))) {
		  $error = true;		
		  echo "funambol.sql [pgsql]: ".$msg."<br>";
		}
	  } else {
		$error = true;
		echo "Funambol only works with MySQL and PostgreSQL.<br>";
	  }	  
	}
    if (!$error) {
	  $out = array();
	  $out[] = "<?php";
	  $out[] = "define('CORE_VERSION','".CORE_VERSION."');";
	  $out[] = "define('CORE_VERSION_STRING','".CORE_VERSION_STRING."');";
	  $out[] = "define('CORE_SGSML_VERSION','".CORE_SGSML_VERSION."');";
	  $out[] = "define('SETUP_DB_TYPE','".SETUP_DB_TYPE."');";
      foreach ($_POST as $key=>$val) {
	    $val = $_REQUEST[$key];
		if (is_array($val)) $val = implode("|", $val);
	    if (in_array($key, array("action_sys","token"))) continue;
		if ($key=="invalid_extensions") $val = trim(preg_replace("|\s*,\s*|", ",", $val));
	    if ($key=="setup_db_pw") $val = sys_encrypt($val,sha1($_REQUEST["setup_admin_user"]));
	    if (!$no_hash and $key=="setup_admin_pw") $val = sha1($val);
	    if (!$no_hash2 and $key=="setup_admin_pw2") $val = sha1($val);
	    if (!is_numeric($val)) {
		  if (strpos($val,"\n") or strpos($val,"'")) {
			$val = "base64_decode('".base64_encode($val)."')";
		  } else {
			$val = "'".$val."'";
		  }
		}
		$key = strtoupper($key);
		if (isset($bools[$key])) {
		  if ($val=="1") $val = "true"; else $val = "false";
		}
		$out[] = "define('".$key."',".$val.");";
	  }
	  $out[] = "if (TIMEZONE!='') date_default_timezone_set(TIMEZONE);\n".
			   "  elseif (version_compare(PHP_VERSION,'5.3','>') and !ini_get('date.timezone')) date_default_timezone_set(@date_default_timezone_get());";
	  $out[] = "if (!ini_get('display_errors')) @ini_set('display_errors','1');";
	  $out[] = "define('NOW',time());";
	  $out[] = "define('APC',function_exists('apc_store') and ini_get('apc.enabled'));";
	  $out[] = "?>";
	  file_put_contents(SIMPLE_STORE."/config.php", implode("\n",$out), LOCK_EX);

	  if (SIMPLE_CACHE!=$_REQUEST["simple_cache"]) {
	    dirs_clear_caches(SIMPLE_CACHE);
	    dirs_clear_caches($_REQUEST["simple_cache"]);
	  }
	  if (SIMPLE_CUSTOM!=$_REQUEST["simple_custom"]) {
	    dirs_clear_custom($_REQUEST["simple_custom"]);
	  }
	  if (SIMPLE_EXT!=$_REQUEST["simple_ext"]) {
	    dirs_clear_custom($_REQUEST["simple_ext"]);
	  }
	  sys_log_message_log("info",sprintf("Setup: setup-data written to %s.",SIMPLE_STORE."/config.php"));
	  echo sprintf('Setup: setup-data written to %s.',SIMPLE_STORE."/config.php");
	  $show_form = false;
	}
	echo "<br><br>";
  }
  echo '
	<a href="index.php">Back</a><br><br>
    </div>
  ';
  
  if ($show_form) {
	  echo '
		<form action="index.php" method="post">
		<input type="hidden" value="'.modify::get_form_token().'" name="token">
		<input type="hidden" value="edit_setup" name="action_sys">
		<input type="hidden" value="en" name="setup_language">
		<table class="data">
	  ';
	  foreach ($inputs as $key=>$input) {
	    if ($input == "---") {
		  echo '
			<tr>
			<td><div style="border-top:1px solid #cccccc; width:100%; margin:10px 0px;"></div></td>
			<td><div style="border-top:1px solid #cccccc; width:450px; margin:10px 0px;"></div></td>
			</tr>
		  ';
		  continue;
		}
	    echo '
		  <tr>
		    <td>'.$input[0].' '.(isset($input[1])?'<a href="#" onclick="alert(\''.str_replace("\n","\\n",$input[1]).'\'); return false;">?</a>':'').'</td>
			<td>
		';
		if ($key=="setup_db_pw") {
		    echo '<input type="password" size="60" maxlength="255" id="'.$key.'" name="'.$key.'" value="'.quote(sys_decrypt(SETUP_DB_PW,sha1(SETUP_ADMIN_USER))).'"><br/>';
			echo '<input id="'.$key.'_check" type="checkbox" onclick="document.getElementById(\''.$key.'\').type = this.checked ? \'text\':\'password\';"><label for="'.$key.'_check">Show password</label>';
		} else if ($key=="setup_admin_pw" or $key=="setup_admin_pw2") {
		    echo '<input type="password" size="60" maxlength="255" id="'.$key.'" name="'.$key.'" value=""><br/>';
			echo '<input id="'.$key.'_check" type="checkbox" onclick="document.getElementById(\''.$key.'\').type = this.checked ? \'text\':\'password\';"><label for="'.$key.'_check">Show password</label>';
		} else {
		    echo '<input type="text" size="60" maxlength="255" name="'.$key.'" value="'.quote(constant(strtoupper($key))).'">';
		}
		echo '
			</td>
		  </tr>
		';
	  }
	  echo '
		<tr>
		  <td>Database</td>
		  <td>'.quote(SETUP_DB_TYPE).'</td>
		</tr>
		<tr>
		  <td><div style="border-top:1px solid #cccccc; width:100%; margin:10px 0px;"></div></td>
		  <td><div style="border-top:1px solid #cccccc; width:450px; margin:10px 0px;"></div></td>
		</tr>
		<tr>
		  <td nowrap valign="top">Authentication Mode</td>
		  <td>
		  	<table class="data" style="background-color: #FFFFFF; border: 0px; margin: 0px;">
	  ';
	  foreach ($auths as $key=>$auth) {
	    echo '
		<tr>
		<td><input type="Radio" class="radio" name="setup_auth" value="'.$key.'" '.(SETUP_AUTH==$key?"checked":"").'></td>
		';
		if (!$auth[1]) {
		  echo '<td colspan="2">'.$auth[0].'</td>';
		  echo '</tr>';
		} else {
		  if ($key=="gdata") {
		    echo '
		      <td>'.$auth[0].'</td>
			  <td>Domain</td><td><input type="Text" name="setup_auth_domain_gdata" value="'.quote(SETUP_AUTH_DOMAIN_GDATA).'"></td><td></td>
			  </tr>';
		  } else {
			echo '
			  <td>'.$auth[0].'</td>
			  <td>Host</td><td><input type="Text" name="setup_auth_hostname_'.$key.'" value="'.quote(constant("SETUP_AUTH_HOSTNAME_".strtoupper($key))).'"></td><td>'.$auth[2].'</td>
			  </tr>';
		  }
		  if ($key=="imap") {
		    echo '
		      <tr><td colspan="2"></td>
			  <td>Domain</td><td><input type="Text" name="setup_auth_domain_imap" value="'.quote(SETUP_AUTH_DOMAIN_IMAP).'"></td><td></td>
			  </tr>';
		  }
		  if ($key=="ldap") {
			echo '<tr><td colspan="2"></td><td>Domain</td><td><input type="Text" name="setup_auth_domain" value="'.quote(SETUP_AUTH_DOMAIN).'"></td><td>(Active Directory)</td></tr>';
			echo '<tr><td colspan="2"></td><td>Base DN</td><td><input type="Text" name="setup_auth_base_dn" value="'.quote(SETUP_AUTH_BASE_DN).'"></td><td>(if not autodetected with namingContexts)</td></tr>';
			echo '<tr><td colspan="2"></td><td>User DN</td><td><input type="Text" name="setup_auth_ldap_user" value="'.quote(SETUP_AUTH_LDAP_USER).'"></td><td>(LDAP without anonymous access)</td></tr>';
			echo '<tr><td colspan="2"></td><td>Password</td><td><input type="Text" name="setup_auth_ldap_pw" value="'.quote(SETUP_AUTH_LDAP_PW).'"></td><td>(LDAP without anonymous access)</td></tr>';
			echo '<tr><td colspan="2"></td><td>UID</td><td><input type="Text" name="setup_auth_ldap_uid" value="'.quote(SETUP_AUTH_LDAP_UID).'"></td><td>(LDAP attribute for usernames, e.g. uid,cn)</td></tr>';
			echo '<tr><td colspan="2"></td><td>Rooms</td><td><input type="Text" name="setup_auth_ldap_room" value="'.quote(SETUP_AUTH_LDAP_ROOM).'"></td><td>(LDAP attribute for rooms)</td></tr>';
			echo '<tr><td colspan="2"></td><td>MemberOf</td><td><input type="Text" name="setup_auth_ldap_memberof" value="'.quote(SETUP_AUTH_LDAP_MEMBEROF).'"></td><td>(LDAP attribute for group memberships)</td></tr>';
			echo '<tr><td colspan="2"></td><td>Use LDAP Groups</td><td><input type="Checkbox" name="setup_auth_ldap_groups" class="checkbox" value="1" '.(SETUP_AUTH_LDAP_GROUPS?"checked":"").'></td></tr>';
		  }
		  if ($key=="ntlm") {
			echo '<tr><td colspan="2"></td><td>Share</td><td><input type="Text" name="setup_auth_ntlm_share" value="'.quote(SETUP_AUTH_NTLM_SHARE).'"></td><td>(smb://server/share/)</td></tr>';
			echo '<input type="hidden" name="setup_auth_ntlm_sso" value="0">
			<tr><td colspan="2"></td><td>Single sign-on</td><td><input type="checkbox" name="setup_auth_ntlm_sso" class="checkbox" value="1" '.(SETUP_AUTH_NTLM_SSO?"checked":"").'></td></tr>';
		  }
		}
	  }
	  echo '
			</table>
		  </td>
		</tr>
		<tr>
		  <td><div style="border-top:1px solid #cccccc; width:100%; margin:10px 0px;"></div></td>
		  <td><div style="border-top:1px solid #cccccc; width:450px; margin:10px 0px;"></div></td>
		</tr>
	  ';
	  foreach ($selects as $key=>$val) {
	    echo '
		  <tr>
		    <td>'.$val[0].'</td>
		    <td><select name="'.$key.'">
	    ';
	    foreach ($val[1] as $key2=>$val2) {
	      echo '<option value="'.$key2.'" '.(constant(strtoupper($key))==$key2?"selected":"").'> '.$val2;
	    }
	    echo '	  
		    </select>
		  </tr>
	    ';
	  }
	  foreach ($multi_selects as $key=>$val) {
	    echo '
		  <tr>
		    <td>'.$val[0].'</td>
		    <td>
			  <table class="data"><tr><td>
			  <input type="hidden" name="'.$key.'[]" value=""/>
	    ';
		$i=0;
	    foreach ($val[1] as $key2=>$val2) {
		  if ($val2[0]==" ") continue;
		  $checked = in_array($key2, explode("|", constant(strtoupper($key)))) ? "checked" : "";
		  echo '<input type="checkbox" name="'.$key.'[]" value="'.$key2.'" '.$checked.'> '.$val2.'<br>';
		  $i++;
		  if ($i%13 == 0) echo '</td><td>&nbsp; &nbsp;</td><td valign="top">';
	    }
	    echo '
			  </td></tr></table>
			</td>
		  </tr>
	    ';
	  }
	  foreach ($textareas as $key=>$input) {
	    echo '
		  <tr>
		    <td>'.$input[0].'</td>
		    <td><textarea name="'.$key.'" style="width:450px; height:100px;">'.quote(constant(strtoupper($key))).'</textarea></td>
		  </tr>
		';
	  }
	  echo '
		<tr>
		  <td><div style="border-top:1px solid #cccccc; width:100%; margin:10px 0px;"></div></td>
		  <td><div style="border-top:1px solid #cccccc; width:450px; margin:10px 0px;"></div></td>
		</tr>
	  ';
	  foreach ($bools as $key=>$val) {
	    echo '
		<input type="hidden" name="'.strtolower($key).'" value="">
		<tr>
		  <td style="width:26%;">'.($val?$val:$key).'</td>
		  <td><input type="Checkbox" class="checkbox" name="'.strtolower($key).'" value="1" '.(constant($key)?"checked":"").'></td>
		</tr>
	    ';
	  }
	  echo '
		<tr>
		  <td><div style="border-top:1px solid #cccccc; width:100%; margin:10px 0px;"></div></td>
		  <td><div style="border-top:1px solid #cccccc; width:450px; margin:10px 0px;"></div></td>
		</tr>
	  ';
	  foreach ($caches as $key=>$val) {
	    echo '
		<tr>
		  <td>'.($val?$val:$key).'</td>
		  <td><input type="Text" size="15" maxlength="50" name="'.strtolower($key).'" value="'.quote(constant($key)).'"></td>
		</tr>
	    ';
	  }
	  echo '
		</table>
	    <div style="border-bottom: 1px solid black;">&nbsp;</div>
		<br>
		<input type="submit" value="   S a v e   " class="submit"><br>
		</form>
	  ';
  }
  
  echo '
    <div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div>
	</div>
	</body></html>
  ';
  $out = ob_get_contents();
  ob_end_clean();
  echo sys_remove_trans($out);
  

function quote($str) {
  $str = str_replace("\\n","\n",$str);
  return modify::htmlquote($str);
}

function dirs_clear_custom($custom) {
  dirs_checkdir($custom);
  dirs_checkdir($custom."/ext/");
  @file_put_contents($custom."/.htaccess", "Order deny,allow\nDeny from all\n", LOCK_EX);
  @file_put_contents($custom."/ext/.htaccess", "Order deny,allow\nAllow from all\n", LOCK_EX);
}

function dirs_clear_caches($cache) {
  $empty_dir = array(
	$cache, $cache."/debug", $cache."/imap", $cache."/pop3", $cache."/ip", $cache."/artichow",
	$cache."/output", $cache."/schema", $cache."/schema_data", $cache."/smarty", $cache."/thumbs",
	$cache."/upload", $cache."/backup"
  );
  foreach ($empty_dir as $dir) dirs_create_empty_dir($dir);
  @file_put_contents($cache."/.htaccess", "Order deny,allow\nDeny from all\n", LOCK_EX);
}