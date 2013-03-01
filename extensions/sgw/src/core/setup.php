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

@ini_set("display_errors","1");
@set_time_limit(1800);

header("Cache-Control: private, max-age=1, must-revalidate");
header("Pragma: no-cache");

define("CORE_VERSION","0_745");
define("CORE_VERSION_STRING","0.745");
define("CORE_SGSML_VERSION","4_45");
define("SIMPLE_CACHE","../simple_cache/");
define("SIMPLE_CUSTOM","../custom/");
define("SIMPLE_EXT","../ext/");
define("USE_SYSLOG_FUNCTION",0);
define("CHMOD_DIR",777);
define("CHMOD_FILE",666);
define("DB_SLOW",0.5);

define("NOW",time());
define("DEBUG",true);
define("DEBUG_SQL",false);
define("FORCE_SSL",false);
define("APC",false);
define("CSV_CACHE",300);
define("INDEX_LIMIT",16384);
define("FILE_TEXT_CACHE",15552000);
define("VIRUS_SCANNER","");

$core_output_cache = !DEBUG;
$core_compress_output = !DEBUG;

if (strpos(PHP_OS,"WIN")!==false) $sep = ";"; else $sep = ":";
$include_path = explode($sep,ini_get("include_path"));
if (!in_array(".",$include_path)) {
  setup_die(sprintf("{t}Please modify your php.ini or add an .htaccess file changing the setting '%s' to '%s' (current value is '%s') !{/t}","include_path",".".$sep.implode($sep,$include_path),ini_get("include_path")),1);
}

$phpversion = "5.2.0";
if (version_compare(PHP_VERSION, $phpversion, "<")) {
  setup_die(sprintf("{t}Setup needs php with at least version %s !{/t} (".PHP_VERSION.")",$phpversion),3);
}
if (version_compare(PHP_VERSION,'5.3','>') and !ini_get('date.timezone')) {
  date_default_timezone_set(@date_default_timezone_get());
}

require("core/functions.php");

$extensions = array("xml", "gd", "pcre", "session", "zlib", "SimpleXML");

$db_extensions = array("MySQL"=>"mysql", "PostgreSQL"=>"pgsql", "SQLite"=>"pdo_sqlite");

$GLOBALS["db_min_version"]["mysql"] = "5.00";
$GLOBALS["db_min_version"]["pgsql"] = "8.36";
$GLOBALS["db_min_version"]["sqlite"] = "3.00";

$on = array("1", "on", "On");
$off = array("0", "off", "Off", "");
$settings = array(
	"safe_mode" => $off, "file_uploads" => $on, "zlib.output_compression" => $off,
	"session.auto_start" => $off, "magic_quotes_runtime" => $off, "display_errors" => $on
);

if (strpos(php_uname("m"),"64")) $memorylimit = 24000000; else $memorylimit = 16000000;

if (!empty($_SERVER["SERVER_SOFTWARE"]) and !preg_match("/Apache|nginx|IIS/", $_SERVER["SERVER_SOFTWARE"])) {
  setup::error("{t}Please choose Apache as Web-Server.{/t} (".$_SERVER["SERVER_SOFTWARE"].")","2".$_SERVER["SERVER_SOFTWARE"]);
}

$memory = ini_get("memory_limit");
if (!empty($memory)) {
  $memory = (int)str_replace("m","000000",strtolower($memory));
  if ($memory < $memorylimit) setup::error(sprintf("{t}Please modify your php.ini or add an .htaccess file changing the setting '%s' to '%s' (current value is '%s') !{/t}","memory_limit",str_replace("000000","M",$memorylimit),ini_get("memory_limit")),4);
}

$old_file = SIMPLE_STORE."/config_old.php";
if (file_exists($old_file) and filemtime($old_file)>time()-86400) {
  setup::$config_old = str_replace("\r","",file_get_contents($old_file));
  
  $_REQUEST["auto_update"] = true;
  if (is_dir("../bin/core") or DEBUG) $_REQUEST["install"] = "yes";
  $_REQUEST["accept_gpl"] = "yes";
  $_REQUEST["language"] = setup::get_config_old("SETUP_LANGUAGE");
  $_REQUEST["lang"] = DEBUG ? "dev" : $_REQUEST["language"];
  $_REQUEST["admin_user"] = setup::get_config_old("SETUP_ADMIN_USER");
  $_REQUEST["admin_pw"] = setup::get_config_old("SETUP_ADMIN_PW");
  $_REQUEST["db_type"] = setup::get_config_old("SETUP_DB_TYPE");
  $_REQUEST["db_host"] = setup::get_config_old("SETUP_DB_HOST");
  $_REQUEST["db_name"] = setup::get_config_old("SETUP_DB_NAME");
  $_REQUEST["db_user"] = setup::get_config_old("SETUP_DB_USER");
  $_REQUEST["db_pw"] = sys_decrypt(setup::get_config_old("SETUP_DB_PW"),sha1(setup::get_config_old("SETUP_ADMIN_USER")));
}

define("USE_DEBIAN_BINARIES",setup::get_config_old("USE_DEBIAN_BINARIES",false,0));
define("SMTP_REMINDER",setup::get_config_old("SMTP_REMINDER",false,""));

$sys_extensions = get_loaded_extensions();

foreach($extensions as $key => $key2) if (!in_array($key2, $sys_extensions)) setup::error(sprintf("{t}Setup needs php-extension with name %s !{/t}",$key2),"5".$key2);

$GLOBALS["databases"] = array();
foreach ($db_extensions as $key => $key2) {
  if (in_array($key2, $sys_extensions)) $GLOBALS["databases"][$key] = str_replace("pdo_","",$key2);
}
if (count($GLOBALS["databases"])==0) setup::error(sprintf("{t}Setup needs a database-php-extension ! (%s){/t}",implode(", ",$db_extensions)),6);

foreach ($settings as $setting => $values) {
  if (!in_array(ini_get($setting), $values)) setup::error(sprintf("{t}Please modify your php.ini or add an .htaccess file changing the setting '%s' to '%s' (current value is '%s') !{/t}",$setting,$values[0],ini_get($setting)),"7".$setting);
}

if (!isset($_SERVER["SERVER_ADDR"]) or $_SERVER["SERVER_ADDR"]=="") $_SERVER["SERVER_ADDR"]="127.0.0.1";

clearstatcache();
if (!is_writable(SIMPLE_CACHE."/") or !is_writable(SIMPLE_STORE."/")) {
  $message = sprintf("[1] {t}Please give write access to %s and %s{/t}",SIMPLE_CACHE."/",SIMPLE_STORE."/");
  $message .= sprintf("\n{t}If file system permissions are ok, please check the configurations of %s if present.{/t}", "SELinux, suPHP, Suhosin");
  setup::error($message,8);
}
if (!is_writable("../bin/")) setup::error(sprintf("[2] {t}Please give write access to %s{/t}","../bin/"),9);
if (!DEBUG and !is_writable("../bin/index.php")) setup::error(sprintf("[3] {t}Please give write access to %s{/t}","../bin/index.php"),10);
if (!is_readable("../lang/")) setup::error(sprintf("[2] {t}Please give read access to %s{/t}","../lang/"),11);
if (!is_readable("../import/")) setup::error(sprintf("[2] {t}Please give read access to %s{/t}","../import/"),111);

if (count(setup::$errors)>0) {
  setup::display_errors(false);
} else {
  $lang_dir = "lang/";
  if (!is_dir($lang_dir)) $lang_dir = "../lang/";
  if (DEBUG and !isset($_REQUEST["install"]) and !isset($_REQUEST["lang"])) {
	echo "
	  <html><head><title>Simple Groupware & CMS</title>
	  <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
	  </head><body><center>
	  <h2>Simple Groupware & CMS ".CORE_VERSION_STRING."</h2><hr>
	  <table style='width:500px;'><tr><td>
	  <a href='index.php?lang=en'>English</a><br><br>
	";
    $handle=opendir($lang_dir);
    while (($file = readdir($handle))) {
      if ($file!="." and $file!=".." and !sys_strbegins($file,"master") and strpos($file,".lang")>0) {
	    $file_data = file_get_contents($lang_dir.$file);
	    preg_match("|{t}!_Language{/t}\n(.*?)\n|",$file_data,$match);
	    if (!empty($match[1])) $lang_str = $match[1]; else $lang_str = "unknown (".$file.")";
        if (ord($file_data[0])!=239) $lang_str = utf8_encode($lang_str);
	    $files[$lang_str] = $file;
	  }
	}
	closedir($handle);
	asort($files);
	$i=0;
	foreach ($files as $lang_str=>$file) {
	  $i++;
	  echo "<a href='index.php?lang=".str_replace(".lang","",$file)."'>".$lang_str."</a><br><br>";
	  if ($i == ceil(count($files)/2)) echo "</td><td valign='top' align='right'>";
	}
	echo "
	  <a href='index.php?lang=dev'>Developer</a>
	  </td></tr></table>
	  <hr>
	  <img src='http://www.simple-groupware.de/cms/logos.php?v=".CORE_VERSION."&d=".PHP_VERSION."_".PHP_OS."' style='width:1px; height:1px;'>
	  <a href='http://www.simple-groupware.de/cms/Main/Installation' target='_blank'>Installation manual</a> / 
	  <a href='http://www.simple-groupware.de/cms/Main/Update' target='_blank'>Update manual</a><hr>
	  <a href='http://www.simple-groupware.de/cms/Main/Documentation' target='_blank'>Documentation</a> / 
	  <a href='http://www.simple-groupware.de/cms/Main/FAQ' target='_blank'>FAQ</a><hr>
	  </center>
	  </body></html>";
	exit;
  }
  if (DEBUG and !isset($_REQUEST["install"]) and $_REQUEST["lang"]!="dev") {
	setup::out("Building translations<br>");
    setup::build_trans($_REQUEST["lang"],"./","../bin/");
	setup::build_customizing(SIMPLE_CUSTOM."customize.php");
	setup::out('<br><a href="../bin/index.php">Continue</a>',false);
    if (function_exists("memory_get_usage") and function_exists("memory_get_peak_usage")) {
	  setup::out("<!-- ".modify::filesize(memory_get_usage())." - ".modify::filesize(memory_get_peak_usage())." -->",false);
	}
	exit;
  } else {
    dirs_create_default_folders();
    if (isset($_REQUEST["install"]) and isset($_REQUEST["accept_gpl"]) and $_REQUEST["accept_gpl"]=="yes") {
      install();
    } else {
      show_form();
} } }

function setup_die($str,$err) {
  echo '
    <html><body style="padding:0px;margin:0px;"><center><br>
	<img src="http://www.simple-groupware.de/cms/logos.php?v='.CORE_VERSION.'&d='.PHP_VERSION.'_'.PHP_OS.'&e='.$err.'" start="width:1px; height:1px;">
    <div style="border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">Simple Groupware Setup</div>
	<br>{t}Error{/t}:<br>
	<error>'.htmlspecialchars($str, ENT_QUOTES).'</error>
	<br><br>
	<a href="index.php">{t}Relaunch Setup{/t}</a>
	<br><br><hr>
	<a href="http://www.simple-groupware.de/cms/Main/Installation" target="_blank">Installation manual</a> / 
	<a href="http://www.simple-groupware.de/cms/Main/Update" target="_blank">Update manual</a><hr>
	<a href="http://www.simple-groupware.de/cms/Main/Documentation" target="_blank">Documentation</a> / 
	<a href="http://www.simple-groupware.de/cms/Main/FAQ" target="_blank">FAQ</a><hr><br>
  ';
  phpinfo();
  echo '</center></body></html>';
  exit;
}

function show_form() {
  ob_start();
  $globals = ini_get("register_globals");
  $mb_string = !in_array("mbstring",get_loaded_extensions());
  
  echo '
    <html><head>
	<title>Simple Groupware & CMS</title>
	<style>
		body, h2, img, div, table.data,a {
		  background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif;
		}
		a,input,select { color: #0000FF; }
		input {
		  font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px;
		  vertical-align: middle; padding-left: 5px; padding-right: 5px; border-radius: 10px;
		}
		.logo {
		  border-radius:10px; border:1px solid #AAAAAA; width:532px; height:300px;
		}
		.logo_image { width:512px; height:280px; }
		select { font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA;	}
		input:focus { border: 1px solid #FF0000; }
		.checkbox,.radio { border: 0px; background-color: transparent; }
		.submit { color: #0000FF; background-color: #FFFFFF; width: 230px; font-weight: bold; }
		table.data td,table.data td.data { padding-left: 5px; padding-right: 5px; }
		table.data tr.fields td { color: #FFFFFF; background-color: #B6BDD2; padding: 2px; }
		#sgs_logo { width: 100%; height: 98%; background-color: #FFFFFF; -moz-transition:opacity 3s; -webkit-transition:opacity 3s; -o-transition:opacity 3s; }
		.logo_table { color:#FFFFFF; background-image:url(ext/images/sgs_logo_bg.jpg); width:512px; height:280px; border-radius:5px; }
		.font {
			text-shadow: -1px -1px 0px #101010, 1px 1px 0px #505050;
			font-family: Coustard, serif;
		}
		@font-face {
		  font-family:"Coustard";
		  src:local("Coustard"), url("ext/images/sgs_logo.woff") format("woff");
		}
	</style>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script>
	function opacity() {
	  getObj("sgs_logo").style.opacity = 1;
	  setTimeout(activate,3000);
	}
	function getObj(id) {
	  return document.getElementById(id);
	}
	function activate() {
	  getObj("sgs_logo").style.display="none";
	  getObj("setup").style.display="";
	}
	function change_input_type(id,checked) {
	  var obj = getObj(id);
	  obj.type = checked ? "text":"password";
	}
	function change_db_type(obj) {
	  var val = obj.options ? (obj.options[obj.selectedIndex].value) : obj.value;
	  var ids = ["db_host_row", "db_user_row", "db_pw_row"];
	  for (var i=0; i<ids.length; i++) {
		getObj(ids[i]).style.display = (val == "sqlite") ? "none" : "";
	  }
	}
	</script>
    </head>
    <body onload="'.((empty($_REQUEST["install"]))?'opacity();':'activate();').'">

    <div id="sgs_logo" style="'.(!empty($_REQUEST["install"])?"display:none;":"").'opacity:0;" onclick="activate();">
    <table style="width:100%; height:95%;"><tr><td align="center">
      <table><tr><td align="right">
      <table class="logo">
	    <tr><td align="center" valign="middle">
		  <table class="logo_table">
		  <tr style="height:45px;"><td colspan="2" align="center" valign="top" class="font" style="font-size:80%"><b>Simple Groupware Solutions</b></td></tr>
		  <tr><td colspan="2" align="center" class="font" style="font-size:170%;"><b>Simple Groupware<br>'.CORE_VERSION_STRING.'</b></td></tr>
		  <tr style="height:50px;">
			<td valign="bottom" style="font-size:80%">Photo from<br><b>Axel Kristinsson</b></td>
			<td align="right" valign="bottom" style="font-size:80%">Thomas Bley<br><b>(C) 2002-2012</b></b></td>
		  </tr>
		  </table>
	    </td></tr>
      </table>
      </td></tr></table>
    </td></tr></table>
    </div>
    <div id="setup" style="display:none;">
    <div style="border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">Simple Groupware '.CORE_VERSION_STRING.'</div>
    <br>
	<div style="color:#ff0000; margin-left:6px;"><b>
	'.($globals?sprintf("{t}Warning{/t}: {t}Please modify your php.ini or add an .htaccess file changing the setting '%s' to '%s' (current value is '%s') !{/t}<br><br>","register_globals","0",$globals):"").'
	'.($mb_string?sprintf("{t}Warning{/t}: {t}Please install the php-extension with name '%s'.{/t}<br><br>","mbstring"):"").'
	'.((isset($_REQUEST["install"]) and empty($_REQUEST["accept_gpl"]))?"&nbsp;=&gt; {t}To continue installing Simple Groupware you must check the box under the license{/t}<br><br>":"").'
	</b></div>
	<form action="index.php" method="post">
	<input type="hidden" value="{t}en{/t}" name="language">
	<table class="data">
	<tr id="db_host_row">
	  <td><label for="db_host">{t}Database Hostname / IP{/t}</label></td>
	  <td><input type="Text" value="localhost" size="30" maxlength="50" name="db_host" id="db_host"></td>
	</tr>
	<tr id="db_user_row">
	  <td><label for="db_user">{t}Database User{/t}</label></td>
	  <td><input type="Text" value="root" size="30" maxlength="50" name="db_user" id="db_user"></td>
	</tr>
	<tr id="db_pw_row">
	  <td><label for="db_pw">{t}Database Password{/t}</label></td>
	  <td><input type="text" value="" size="30" maxlength="50" name="db_pw" id="db_pw"></td>
	</tr>
	<tr>
	  <td><label for="db_name">{t}Database Name{/t}</label></td>
	  <td><input type="Text" value="sgs_'.CORE_VERSION.'" size="30" maxlength="50" name="db_name" id="db_name" required="true"></td>
	</tr>
	<tr>
	  <td><label for="db_type">{t}Database{/t}</label></td>
	  <td>
  ';
  if (count($GLOBALS["databases"])>1) {
    echo '<select name="db_type" id="db_type" onchange="change_db_type(this);">';
    foreach ($GLOBALS["databases"] as $key=>$key2) echo '<option value="'.$key2.'"> '.$key;
    echo '</select>';
  }	else {
    foreach ($GLOBALS["databases"] as $key=>$key2) echo '<input type="hidden" name="db_type" id="db_type" value="'.$key2.'"> '.$key;
  }
  echo '
	  <script>change_db_type(getObj("db_type"));</script>
	  </td>
	</tr>
	<tr>
	  <td><label for="admin_user">{t}Admin Username{/t}</label></td>
	  <td><input type="text" value="admin" size="30" maxlength="50" name="admin_user" id="admin_user" required="true"></td>
	</tr>
	<tr>
	  <td><label for="admin_pw">{t}Admin Password{/t}</label></td>
	  <td><input type="text" value="" size="30" maxlength="50" name="admin_pw" id="admin_pw" required="true"></td>
	</tr>
	<tr>
	  <td><label for="folders">{t}Folder structure{/t}</label></td>
	  <td>
		<select name="folders" id="folders">
		  <option value="modules/core/folders.xml">{t}Install demo folders{/t}
		  <option value="modules/core/folders_small.xml">{t}Install default folder structure{/t}
		  <option value="modules/core/folders_minimal.xml">{t}Install minimal folder structure{/t}
		</select>
	  </td>
	</tr>
	</table>
    <div style="border-bottom: 1px solid black;">&nbsp;</div>
	<h2>GNU GPL {t}License{/t} Version 2</h2>
	<h4>
	<a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">{t}More information about the GNU GPL{/t}</a><br>
	<a href="http://www.gnu.org/licenses/translations.html" target="_blank">{t}Translations of the GNU GPL{/t}</a><br> 
	<a href="http://www.gnu.org/licenses/gpl-faq.html" target="_blank">{t}GNU GPL Frequently Asked Questions{/t}</a>
	<br>
	</h4>
	<font color="#ff0000">*** {t}To continue installing Simple Groupware you must check the box under the license{/t} ***</font><br><br>
	{t}Please read the following license agreement. Use the scroll bar to view the rest of this agreement.{/t}<br>
    <div style="border-bottom: 1px solid black;">&nbsp;</div>
	<pre>'.trim(file_get_contents("LICENSE.txt")).'</pre>
    <div style="border-bottom: 1px solid black;">&nbsp;</div>
	<br>
	<div style="border: 2px solid #FF0000; width:400px;">&nbsp; <input onclick="if (this.checked) this.parentNode.style.border=\'2px solid #00A000\'; else this.parentNode.style.border=\'2px solid #FF0000\';" type="Checkbox" class="checkbox" name="accept_gpl" id="accept_gpl" value="yes" style="margin: 0px;" accesskey="a" required="true"> <label for="accept_gpl">{t}I Accept the GNU GENERAL PUBLIC LICENSE VERSION 2{/t}</label></div>
	<br><br>
	<input type="submit" name="install" value="{t}I n s t a l l{/t}" class="submit" style="width:400px;"><br><br>
	</form>
    <div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div>
	</div></body></html>
  ';
  $out = ob_get_contents();
  ob_end_clean();
  echo sys_remove_trans($out);
}

function install() {
  echo '
    <html>
    <head>
	<title>Simple Groupware & CMS</title>
	<style>
		body, h2, img, div, table.data, a { background-color: #FFFFFF; color: #666666; font-size: 13px; font-family: Arial, Helvetica, Verdana, sans-serif; }
		a,input { color: #0000FF; }
		input {
		  font-size: 11px; background-color: #F5F5F5; border: 1px solid #AAAAAA; height: 18px; vertical-align: middle;
		  padding-left: 5px; padding-right: 5px; border-radius: 10px;
		}
		.submit { color: #0000FF; background-color: #FFFFFF; width: 230px; font-weight: bold; }
	</style>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <body>
    <div style="border-bottom: 1px solid black; letter-spacing: 2px; font-size: 18px; font-weight: bold;">Simple Groupware '.CORE_VERSION_STRING.'</div>
	<br>
  ';

  $_SESSION["groups"] = array();
  $_SESSION["serverid"] = 1;
  $_SESSION["username"] = "setup";
  $_SESSION["password"] = "";
  $_SESSION["permission_sql"] = "1=1";
  $_SESSION["permission_sql_read"] = "1=1";
  $_SESSION["permission_sql_write"] = "1=1";
  
  @unlink(SIMPLE_STORE."/setup.txt");
  if ($validate=validate::username($_REQUEST["admin_user"]) and $validate!="") setup::error("{t}Admin Username{/t} - {t}validation failed{/t} ".$validate,30);
  if ($_REQUEST["db_host"]=="") setup::error(sprintf("{t}missing field{/t}: %s","{t}Database Hostname / IP{/t}"),31);
  if ($_REQUEST["db_user"]=="") setup::error(sprintf("{t}missing field{/t}: %s","{t}Database User{/t}"),32);
  if ($_REQUEST["db_name"]=="") setup::error(sprintf("{t}missing field{/t}: %s","{t}Database Name{/t}"),33);
  if ($_REQUEST["admin_pw"]=="") setup::error(sprintf("{t}missing field{/t}: %s","{t}Admin Password{/t}"),34);
  if ($_REQUEST["admin_pw"]!="" and strlen($_REQUEST["admin_pw"])<5) setup::error("{t}Admin Password{/t}: {t}Password must be not null, min 5 characters.{/t}","34b");

  define("SETUP_DB_TYPE",$_REQUEST["db_type"]);

  if (!@sql_connect($_REQUEST["db_host"], $_REQUEST["db_user"], $_REQUEST["db_pw"], $_REQUEST["db_name"])) {
    if (!sql_connect($_REQUEST["db_host"], $_REQUEST["db_user"], $_REQUEST["db_pw"])) setup::error("{t}Connection to database failed.{/t}\n".sql_error(),35);
    if (empty(sys::$db)) setup::display_errors(true);
	if (!sgsml_parser::create_database($_REQUEST["db_name"])) setup::error("{t}Creating database failed.{/t}\n".sql_error(),36);
  }
  if (!sql_connect($_REQUEST["db_host"], $_REQUEST["db_user"], $_REQUEST["db_pw"], $_REQUEST["db_name"]) or empty(sys::$db)) {
    setup::error("{t}Connection to database failed.{/t}\n".sql_error(),37);
	setup::display_errors(true);
  }

  if (!$version = sgsml_parser::sql_version()) setup::error(sprintf("{t}Could not determine database-version.{/t}"),38);
  $database_version_min_int = (int)substr(str_replace(".","",$GLOBALS["db_min_version"][SETUP_DB_TYPE]),0,3);
  if ($version < $database_version_min_int) setup::error(sprintf("{t}Wrong database-version (%s). Please use at least %s !{/t}",$version,$GLOBALS["db_min_version"][SETUP_DB_TYPE]),"20".SETUP_DB_TYPE);

  if (sgsml_parser::table_column_exists("simple_sys_tree","id")) {
    echo '<img src="http://www.simple-groupware.de/cms/logo.php?v='.CORE_VERSION.$_REQUEST["language"].'&u=1&p='.PHP_VERSION.'_'.PHP_OS.'&d='.SETUP_DB_TYPE.$version.'" style="width:1px; height:1px;">';
  } else {
    echo '<img src="http://www.simple-groupware.de/cms/logo.php?v='.CORE_VERSION.$_REQUEST["language"].'&u=0&p='.PHP_VERSION.'_'.PHP_OS.'&d='.SETUP_DB_TYPE.$version.'" style="width:1px; height:1px;">';
  }
  
  if (SETUP_DB_TYPE=="pgsql") {
  	if (!sql_query("SELECT ''::tsvector;")) {
	  setup::error("{t}Please install 'tsearch2' for the PostgreSQL database.{/t}\n(Run <postgresql>/share/contrib/tsearch2.sql)\n".sql_error(),21);
	}
    if (!sql_query(file_get_contents("modules/core/pgsql.sql"))) setup::error("pgsql.sql: ".sql_error(),50);
  }
  setup::out(sprintf("{t}Processing %s ...{/t}","schema updates"));

  // 0.730
  $status = array("{t}completed{/t}"=>"completed","{t}confirmed{/t}"=>"confirmed","{t}booked{/t}"=>"booked", "{t}canceled{/t}"=>"canceled");
  sgsml_parser::table_column_translate("simple_timesheets", "status", $status);
  sgsml_parser::table_column_translate("simple_expenses", "status", $status);

  // completed=0 && status=unconfirmed -> status=open
  // completed=1 && status=unconfirmed -> status=completed
  if (sgsml_parser::table_column_exists("simple_timesheets","completed")) {
	db_update("simple_timesheets",array("status"=>"open"),array("completed=0", "status=@status@"),array("status"=>"{t}unconfirmed{/t}"),array("no_defaults"=>1));
	db_update("simple_timesheets",array("status"=>"completed"),array("completed=1", "status=@status@"),array("status"=>"{t}unconfirmed{/t}"),array("no_defaults"=>1));
  }
  if (sgsml_parser::table_column_exists("simple_expenses","completed")) {
	db_update("simple_expenses",array("status"=>"open"),array("completed=0", "status=@status@"),array("status"=>"{t}unconfirmed{/t}"),array("no_defaults"=>1));
	db_update("simple_expenses",array("status"=>"completed"),array("completed=1", "status=@status@"),array("status"=>"{t}unconfirmed{/t}"),array("no_defaults"=>1));
  }
  
  // 0.662
  $priority = array("{t}lowest{/t}"=>"1", "{t}low{/t}"=>"2", "{t}normal{/t}"=>"3", "{t}urgent{/t}"=>"4", "{t}immediate{/t}"=>"5");
  sgsml_parser::table_column_translate("simple_calendar", "priority", $priority);
  sgsml_parser::table_column_translate("simple_tasks", "priority", $priority);
  sgsml_parser::table_column_translate("simple_helpdesk", "priority", $priority);
  sgsml_parser::table_column_translate("simple_projects", "priority", $priority);

  // 0.658
  if (!sgsml_parser::table_column_rename("simple_emails","attachments","attachment")) setup::error("rename[10]: ".sql_error(),1152);
  if (!sgsml_parser::table_column_rename("simple_helpdesk","attachments","attachment")) setup::error("rename[9]: ".sql_error(),1153);

  // 0.400
  if (!sgsml_parser::table_column_rename("simple_projects","started","begin")) setup::error("rename[8]: ".sql_error(),152);
  if (!sgsml_parser::table_column_rename("simple_projects","finished","ending")) setup::error("rename[7]: ".sql_error(),153);

  // 0.220
  if (!sgsml_parser::table_column_rename("simple_gallery","title","filename")) setup::error("rename[5]: ".sql_error(),52);
  if (!sgsml_parser::table_column_rename("simple_gallery","attachment","filedata")) setup::error("rename[6]: ".sql_error(),53);

  // 0.219
  if (!sgsml_parser::table_column_rename("simple_calendar","end","ending")) setup::error("rename[1]: ".sql_error(),54);
  if (!sgsml_parser::table_column_rename("simple_contactactivities","end","ending")) setup::error("rename[2]: ".sql_error(),55);
  if (!sgsml_parser::table_column_rename("simple_tasks","end","ending")) setup::error("rename[3]: ".sql_error(),56);
  if (!sgsml_parser::table_rename("simple_sys_chat","simple_sys_chat2")) setup::error("rename[4]: ".sql_error(),57);

  if (count(setup::$errors)>0) setup::display_errors(true);

  // 0.720
  if (sgsml_parser::table_column_exists("simple_sys_custom_fields","id")) {
	setup::out(sprintf("{t}Processing %s ...{/t}","customization fields"));
	$rows = db_select("simple_sys_custom_fields","*","activated=1","","");
	if (is_array($rows) and count($rows)>0) {
	  foreach ($rows as $row) sgsml_customizer::trigger_build_field($row["id"], $row, null, "simple_sys_custom_fields");
	}
  }

  if (SETUP_DB_TYPE=="sqlite") {
	sql_query("begin");
	admin::rebuild_schema(false);
	sql_query("commit");
  } else {
	admin::rebuild_schema(false);
  }

  // process funambol schema views on sgs update
  if (setup::get_config_old("SYNC4J",false,0) == "1") {
    setup::out(sprintf("{t}Processing %s ...{/t}","Funambol schema"));
	if (SETUP_DB_TYPE=="mysql") {
	  $data = preg_replace("!/\*.+?\*/!s","",file_get_contents("tools/funambolv7_syncML/mysql/funambol.sql"));
	  $data = sys_remove_trans($data);
	  if (($msg = db_query(explode(";",$data)))) setup::error("funambol.sql [mysql]: ".$msg." ".sql_error(),100);
	} else if (SETUP_DB_TYPE=="pgsql") {
	  $data = file_get_contents("tools/funambolv7_syncML/postgresql/funambol.sql");
	  $data = sys_remove_trans($data);
	  if (($msg = db_query($data))) setup::error("funambol.sql [pgsql]: ".$msg." ".sql_error(),101);
	}
  }

  // change ftype, 0.646
  db_update("simple_sys_tree",array("ftype"=>"replace(ftype,'sys_nosql_','sys_nodb_')"),array("ftype like 'sys_nosql_%'"),array(),array("quote"=>false));
  
  // change anchor for rooms, 0.310
  db_update("simple_sys_tree",array("anchor"=>"locations"),array("ftype='locations'","flevel=2","ftitle='{t}Rooms{/t}'"),array());

  // change anchor for demo, debug folder, workspace, organisation, 0.292
  db_update("simple_sys_tree",array("anchor"=>"demo"),array("ftype='blank'","flevel=1","ftitle='{t}Demo{/t}'"),array());
  db_update("simple_sys_tree",array("anchor"=>"debug"),array("ftype='blank'","flevel=2","ftitle='{t}Debug{/t}'"),array());
  db_update("simple_sys_tree",array("anchor"=>"workspace"),array("ftype='blank'","flevel=0"),array());
  db_update("simple_sys_tree",array("anchor"=>"organisation"),array("ftype='blank'","flevel=1","ftitle='{t}Organisation{/t}'"),array());
  db_update("simple_sys_tree",array("anchor"=>"system"),array("ftype='sys_nodb_admin'","flevel=1","ftitle='{t}System{/t}'"),array());

  // remove sys_nodb_processes 0.721
  db_update("simple_sys_tree",array("ftype"=>"blank"),array("ftype='sys_nodb_processes'"),array());
  
  // 0.664
  if (!file_exists(SIMPLE_STORE."/setup_emails")) {
	setup::out(sprintf("{t}Processing %s ...{/t}","emails message"));
	$rows = db_select("simple_emails","*",array("message_html='' and message!=''"),"","");
	if (is_array($rows) and count($rows)>0) {
	  foreach ($rows as $row) trigger::createemail($row["id"],$row);
	}
	touch(SIMPLE_STORE."/setup_emails");
  }

  // change System folder to administration menu, 0.242
  db_update("simple_sys_tree",array("ftype"=>"sys_nodb_admin"),array("anchor=@anchor@"),array("anchor"=>"system"));

  setup::out(sprintf("{t}Processing %s ...{/t}","sessions"));
  db_delete("simple_sys_session",array(),array());

  // 0.704
  if (!file_exists(SIMPLE_STORE."/setup_notify")) {
    $notifications = array(
	  "simple_tasks"=>"closed='0'",
	  "simple_contacts"=>"birthday!=''",
	  "simple_contactactivities"=>"finished='0'",
	  "simple_sys_users"=>"activated='1'",
	);
	foreach ($notifications as $table=>$where) {
	  setup::out(sprintf("{t}Processing %s ...{/t}",$table));
	  $rows = db_select($table,"*",array($where,"notification!=''"),"","");
	  if (!is_array($rows) or count($rows)==0) continue;
	  foreach ($rows as $row) trigger::notify($row["id"],$row,array(),$table);
	}
	touch(SIMPLE_STORE."/setup_notify");
  }

  if (!file_exists(SIMPLE_STORE."/setup_duration")) {
	setup::out(sprintf("{t}Processing %s ...{/t}","tasks duration"));
	$rows = db_select("simple_tasks","*",array(),"","");
	if (is_array($rows) and count($rows)>0) {
	  foreach ($rows as $row) trigger::duration($row["id"],$row,false,"simple_tasks");
	}

	setup::out(sprintf("{t}Processing %s ...{/t}","projects duration"));
	$rows = db_select("simple_projects","*",array(),"","");
	if (is_array($rows) and count($rows)>0) {
	  foreach ($rows as $row) trigger::createeditproject($row["id"],$row);
	}
	touch(SIMPLE_STORE."/setup_duration");
  }

  setup::out(sprintf("{t}Processing %s ...{/t}","appointments"));
  $rows = db_select("simple_calendar","*",array(),"","");
  if (is_array($rows) and count($rows)>0) {
	foreach ($rows as $row) trigger::calcappointment($row["id"],$row,null,"simple_calendar");
  }

  setup::out(sprintf("{t}Processing %s ...{/t}","folder structure"));
  $count = db_select_value("simple_sys_tree","id",array());
  if (empty($count)) {
	$folders = "modules/core/folders.xml";
	if (!empty($_REQUEST["folders"]) and file_exists(sys_custom($_REQUEST["folders"]))) {
	  $folders = $_REQUEST["folders"];
	}
	if (SETUP_DB_TYPE=="sqlite") {
	  sql_query("begin");
	  folders::create_default_folders($folders,0,true);
	  sql_query("commit");
	} else {
	  folders::create_default_folders($folders,0,true);
	}
  }

  setup::out(sprintf("{t}Processing %s ...{/t}","default groups"));
  $groups = array("admin_calendar","admin_news","admin_projects","admin_bookmarks","admin_contacts",
				  "admin_inventory","admin_helpdesk","admin_organisation","admin_files","admin_payroll",
				  "admin_surveys","admin_hr","admin_intranet","users_self_registration");
  foreach ($groups as $group) trigger::creategroup($group);

  $parent = folder_from_path("^system");
  if (!empty($parent)) {
    $row_id = folder_from_path("~sys_nodb_backups");
    if (empty($row_id)) {
	  folders::create("{t}Backups{/t}","sys_nodb_backups","",$parent,false);
    }
    $row_id = folder_from_path("^trash");
    if (empty($row_id)) {
	  folders::create("{t}Trash{/t}","blank","",$parent,false,array("anchor"=>"trash"));
    }
    $row_id = folder_from_path("~sys_notifications");
    if (empty($row_id)) {
	  folders::create("{t}Notifications{/t}","sys_notifications","{t}Delivery{/t}: cron.php",$parent,false);
    }
    $row_id = folder_from_path("^customize");
    if (empty($row_id)) {
	  folders::create("{t}Customize{/t}","blank","",$parent,false,array("anchor"=>"customize"));
    }
    $row_id = folder_from_path("~sys_console");
    if (empty($row_id)) {
	  $id = folders::create("{t}Console{/t}","sys_console","",$parent,false,array());
	  folders::import_data("../import/data_console.xml", $id);
    }
  }
  
  $parent = folder_from_path("^customize");
  $row_id = folder_from_path("~sys_custom_fields");
  if (empty($row_id) and !empty($parent)) {
	folders::create("{t}Fields{/t}","sys_custom_fields","{t}Customization rules\nfor modules based on sgsML{/t}",$parent,false);
  }
  $parent = folder_from_path("^workspace");
  $row_id = folder_from_path("^extensions");
  if (!empty($parent) and empty($row_id)) {
	folders::create("{t}Extensions{/t}","blank","",$parent,false,array("anchor"=>"extensions"));
  }

  setup::out(sprintf("{t}Processing %s ...{/t}","config.php"));

  $vars_static = array(
	"CORE_VERSION"=>"'".CORE_VERSION."'",
	"CORE_VERSION_STRING"=>"'".CORE_VERSION_STRING."'",
	"CORE_SGSML_VERSION"=>"'".CORE_SGSML_VERSION."'",
	"SETUP_LANGUAGE"=>"'".$_REQUEST["language"]."'",
	"SETUP_DB_TYPE"=>"'".SETUP_DB_TYPE."'",
	"SETUP_DB_HOST"=>"'".$_REQUEST["db_host"]."'",
	"SETUP_DB_NAME"=>"'".$_REQUEST["db_name"]."'",
	"SETUP_DB_USER"=>"'".$_REQUEST["db_user"]."'",
	"SETUP_DB_PW"=>"'".sys_encrypt($_REQUEST["db_pw"],sha1($_REQUEST["admin_user"]))."'",
	"SETUP_ADMIN_USER"=>"'".$_REQUEST["admin_user"]."'",
	"SETUP_ADMIN_PW"=>"'".(isset($_REQUEST["auto_update"])?$_REQUEST["admin_pw"]:sha1($_REQUEST["admin_pw"]))."'",
  );
  $session_name = md5("simple_session_".CORE_VERSION.dirname($_SERVER["PHP_SELF"]));
  $vars = array(
	"APP_TITLE"=>"'Simple Groupware & CMS'",
	"CMS_TITLE"=>"'PmWiki & Simple Groupware'",
	"SETUP_ADMIN_USER2"=>"''", "SETUP_ADMIN_PW2"=>"''",
	"SETUP_AUTH"=>"'sql'", "SETUP_AUTH_AUTOCREATE"=>"0",
	"SETUP_AUTH_DOMAIN"=>"''", "SETUP_AUTH_DOMAIN_GDATA"=>"''", "SETUP_AUTH_DOMAIN_IMAP"=>"''",
	"SETUP_AUTH_LDAP_USER"=>"''", "SETUP_AUTH_LDAP_PW"=>"''", "SETUP_AUTH_BASE_DN"=>"''", "SETUP_AUTH_LDAP_UID"=>"'uid'",
	"SETUP_AUTH_LDAP_MEMBEROF"=>"'memberOf'", "SETUP_AUTH_LDAP_ROOM"=>"''",	"SETUP_AUTH_LDAP_GROUPS"=>"0",
	"SETUP_AUTH_HOSTNAME_LDAP"=>"''", "SETUP_AUTH_HOSTNAME_IMAP"=>"''",
	"SETUP_AUTH_HOSTNAME_SMTP"=>"''", "SETUP_AUTH_HOSTNAME_NTLM"=>"''", "SETUP_AUTH_NTLM_SHARE"=>"''", "SETUP_AUTH_NTLM_SSO"=>"0", 
	"CHECK_DOS"=>"1", "FORCE_SSL"=>"0", "ENABLE_WEBDAV_LOCKING"=>"0", "ENABLE_WEBDAV"=>"1",
	"ENABLE_ANONYMOUS"=>"1", "ENABLE_ANONYMOUS_CMS"=>"1", "DISABLE_BASIC_AUTH"=>"0", "MOUNTPOINT_REQUIRE_ADMIN"=>"0", 
	"SELF_REGISTRATION"=>"0", "SELF_REGISTRATION_CONFIRM"=>"0", "DISABLED_MODULES"=>"''",
	"ENABLE_EXT_MAILCLIENT"=>"0", "USE_DEBIAN_BINARIES"=>"0", "USE_MAIL_FUNCTION"=>"0", "USE_SYSLOG_FUNCTION"=>"0",
	"DEBUG_SQL"=>"false", "DEBUG_IMAP"=>"false", "DEBUG_POP3"=>"false", "DEBUG_JS"=>"false", 
	"DEBUG_SMTP"=>"false", "DEBUG_JAVA"=>"false", "DEBUG_WEBDAV"=>"false",
	"LOCKING"=>"900", "FOLDER_REFRESH"=>"5", "LOGIN_TIMEOUT"=>"7200", "SESSION_NAME"=>"'".$session_name."'", "DEFAULT_STYLE"=>"'core'",
	"WEEKSTART"=>"0", "OUTPUT_CACHE"=>"86400", "CSV_CACHE"=>"300", "LDIF_CACHE"=>"300", "BOOKMARKS_CACHE"=>"300", "ICALENDAR_CACHE"=>"300",
	"RSS_CACHE"=>"600", "VCARD_CACHE"=>"300", "XML_CACHE"=>"300",
	"IMAP_CACHE"=>"300", "IMAP_LIST_CACHE"=>"30", "IMAP_MAIL_CACHE"=>"15552000",
	"POP3_LIST_CACHE"=>"30", "POP3_MAIL_CACHE"=>"15552000",
	"GDOCS_CACHE"=>"300", "GDOCS_LIST_CACHE"=>"30", "GDOCS_PREVIEW_LIMIT"=>"5242880", "CIFS_PREVIEW_LIMIT"=>"10485760",
	"FILE_TEXT_LIMIT"=>"2000", "FILE_TEXT_CACHE"=>"15552000", "CMS_CACHE"=>"86400", "LDAP_LIST_CACHE"=>"120", "INDEX_LIMIT"=>"16384",
	"VIRUS_SCANNER"=>"''", "VIRUS_SCANNER_PARAMS"=>"''", "VIRUS_SCANNER_DISPLAY"=>"''",
	"SYNC4J_REMOTE_DELETE"=>"0", "SYNC4J"=>"0", "ARCHIVE_DELETED_FILES"=>"1",
	"SMTP_FOOTER"=>"'Sent with Simple Groupware http://www.simple-groupware.de/'",
	"SMTP_REMINDER"=>"'Simple Groupware {t}Reminder{/t}'",
	"SMTP_NOTIFICATION"=>"'Simple Groupware {t}Notification{/t}'",
	"CORE_COMPRESS_OUTPUT"=>($GLOBALS["core_compress_output"]?"true":"false"),
	"CORE_OUTPUT_CACHE"=>($GLOBALS["core_output_cache"]?"true":"false"),
	"APC_SESSION"=>"false","MENU_AUTOHIDE"=>"false","TREE_AUTOHIDE"=>"false","FIXED_FOOTER"=>"false","FDESC_IN_CONTENT"=>"false",
	"CMS_HOMEPAGE"=>"'HomePage'", "CMS_REAL_URL"=>"''", "DEBUG"=>(DEBUG?"true":"false"),
	"SIMPLE_CACHE"=>"'".SIMPLE_CACHE."'", "SIMPLE_CUSTOM"=>"'".SIMPLE_CUSTOM."'", "SIMPLE_IMPORT"=>"'../import/'",
	"SIMPLE_EXT"=>"'".SIMPLE_EXT."'", "TIMEZONE"=>"''", "ASSET_PAGE_LIMIT"=>"100",
	"SYSTEM_SLOW"=>"2", "DB_SLOW"=>"0.5", "CMS_SLOW"=>"2", "CHMOD_DIR"=>"777", "CHMOD_FILE"=>"666",
	"INVALID_EXTENSIONS"=>"'386,adb,ade,asd,asf,asp,asx,bas,bat,bin,cab,ceo,cgi,chm,cmd,com,cpl,crt,csc,dat,dbx,dll,drv,".
		"ema,eml,exe,fon,hlp,hta,hto,htt,img,inf,isp,jse,jsp,ins,lnk,mbx,mda,mdt,mdx,mdw,mdz,mht,".
		"msc,msg,msi,mso,mst,msp,obj,ocx,oft,ole,ovl,ovr,php,pif,pl,prf,pst,reg,rm,rtf,scr,scs,sct,shb,".
		"shm,shs,sht,sys,tbb,tbi,uin,vb,vbe,vbs,vbx,vsw,vxd,wab,wsc,wsf,wsh,xl,xla,xsd'",
  );
  if ($_REQUEST["language"] == 'ar') $vars["DEFAULT_STYLE"] = "rtl";
  
  $out = array();
  $out[] = "<?"."php";
  foreach ($vars_static as $key=>$var) $out[] = "define('".$key."',".$var.");";
  foreach ($vars as $key=>$var) {
	$var = setup::get_config_old($key,true,$var);
	$out[] = "define('".$key."',".$var.");";
  }
  $out[] = "if (TIMEZONE!='') date_default_timezone_set(TIMEZONE);\n".
		   "  elseif (version_compare(PHP_VERSION,'5.3','>') and !ini_get('date.timezone')) date_default_timezone_set(@date_default_timezone_get());";
  $out[] = "if (!ini_get('display_errors')) @ini_set('display_errors','1');";
  $out[] = "define('NOW',time());";
  $out[] = "define('APC',function_exists('apc_store') and ini_get('apc.enabled'));";
  $out[] = "?>";

  if (file_put_contents(SIMPLE_STORE."/config.php", implode("\n",$out), LOCK_EX)) {
	if (!file_exists(SIMPLE_STORE."/config.php") or filesize(SIMPLE_STORE."/config.php")==0) {
	  sys_die("cannot write to: ".SIMPLE_STORE."/config.php");
	}
	chmod(SIMPLE_STORE."/config.php", 0600);
	sys_log_message_log("info",sprintf("{t}Setup: setup-data written to %s.{/t}",SIMPLE_STORE."/config.php"));
	setup::out('<br><a href="index.php">{t}C O N T I N U E{/t}</a><br><finished>');
	if (function_exists("memory_get_usage") and function_exists("memory_get_peak_usage")) {
	  setup::out("<!-- ".modify::filesize(memory_get_usage())." - ".modify::filesize(memory_get_peak_usage())." -->",false);
	}
    setup::out('<div style="border-top: 1px solid black;">Powered by Simple Groupware, Copyright (C) 2002-2012 by Thomas Bley.</div></div></body></html>',false);
  } else sys_die("cannot write to: ".SIMPLE_STORE."/config.php");

  db_optimize_tables();
}

function dirs_create_default_folders() {
  setup::dirs_create_htaccess(SIMPLE_STORE."/");
  setup::dirs_create_htaccess("../old/");
  setup::dirs_create_dir(SIMPLE_EXT);
  setup::dirs_create_dir(SIMPLE_STORE."/home");
  setup::dirs_create_dir(SIMPLE_STORE."/backup");
  setup::dirs_create_dir(SIMPLE_STORE."/syncml");
  setup::dirs_create_dir(SIMPLE_STORE."/trash");
  setup::dirs_create_dir(SIMPLE_STORE."/cron");

  $empty_dir = array(
    SIMPLE_STORE."/locking",
	SIMPLE_CACHE, SIMPLE_CACHE."/debug", SIMPLE_CACHE."/imap", SIMPLE_CACHE."/pop3",
	SIMPLE_CACHE."/ip", SIMPLE_CACHE."/artichow", SIMPLE_CACHE."/output",
	SIMPLE_CACHE."/schema", SIMPLE_CACHE."/schema_data", SIMPLE_CACHE."/smarty",
	SIMPLE_CACHE."/thumbs", SIMPLE_CACHE."/upload", SIMPLE_CACHE."/backup",
	SIMPLE_CACHE."/preview", SIMPLE_CACHE."/cifs", SIMPLE_CACHE."/gdocs", SIMPLE_CACHE."/cms"
  );
  foreach ($empty_dir as $dir) dirs_create_empty_dir($dir);
  setup::dirs_create_htaccess(SIMPLE_CACHE."/");
  
  $host = setup::get_config_old("MEMCACHE_HOST");
  $port = setup::get_config_old("MEMCACHE_PORT");
  if ($host and $port) memcache_connect($host, $port)->flush();;
}