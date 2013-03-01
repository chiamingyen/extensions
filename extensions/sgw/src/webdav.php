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

if ($_SERVER["REQUEST_METHOD"]=="PUT" and preg_match("!desktop\.ini\$|thumbs\.db\$!i", $_SERVER["REQUEST_URI"])) {
  header("HTTP/1.1 409 Conflict");
  exit;
}
if (preg_match("!desktop\.ini\$|folder\.jpg|folder.gif|target\.lnk\$|\.dll\$|thumbs\.db\$|sgdav/share!i", $_SERVER["REQUEST_URI"])) {
  header("HTTP/1.1 410 Gone");
  exit;
}
if ($_SERVER["REQUEST_METHOD"]=="HEAD") {
  header("HTTP/1.1 403 Forbidden");
  exit;
}
if ($_SERVER["REQUEST_METHOD"]=="GET" and $_SERVER["REQUEST_URI"]!="/sgdav/test") {

  webdav_forward("^/sgdav/(.+)/(Lock|Unlock)_([0-9]+)(_[^_]+)?_([0-9]+)\.(\w{2,3})", "files.php", "folder=/%s/&action=%s&item=%s&field=%s&subitem=%s&output=%s");
  
  webdav_forward("^/sgdav/(.+)/([0-9]+)(_[^_]+)?_([0-9]+)__.+", "download.php", "view=display&dispo=noinline&folder=/%s/&item=%s&field=%s&subitem=%s");
  
  webdav_forward("^/sgdav/(.+)/index\.html", "index.php", "view=details&export=html&folder=/%s/");
  
  webdav_forward("^/sgdav/(.+)/index\.xls", "index.php", "view=details&export=calc&folder=/%s/");

  webdav_forward("^/sgdav/.+/.+", "download.php", "view=display&dispo=noinline&item=session");
}

if ($_SERVER["REQUEST_METHOD"]=="PUT") {
  webdav_forward("^/sgdav/(.+)/([0-9]+)(_[^_]+)?_([0-9]+)__(.+)", "upload.php", "view=display&dispo=noinline&folder=/%s/&item=%s&field=%s&subitem=%s&filename=%s&action=PUT");

  webdav_forward("^/sgdav/.+/.+", "upload.php", "item=session&action=PUT");
}

if ($_SERVER["REQUEST_METHOD"]=="MOVE") {
  // destination: e.g. http://localhost/sgdav/Workspace/Demo/Files/6101_0__bla.jpg
  webdav_forward("\.tmp\$", "upload.php", "view=display&dispo=noinline&folder=/%s/&item=%s&field=%s&subitem=%s&action=MOVE&filename=".$_SERVER["REQUEST_URI"], "/sgdav/(.+)/([0-9]+)(_[^_]+)?_([0-9]+)__.+");

  webdav_forward("\.tmp\$", "upload.php", "view=display&dispo=noinline&item=session&action=MOVE&filename=".$_SERVER["REQUEST_URI"], "/sgdav/.+/.+");
}

if (in_array($_SERVER["REQUEST_METHOD"], array("LOCK", "UNLOCK"))) {
  webdav_forward("^/sgdav/(.+)/([0-9]+)(_[^_]+)?_([0-9]+)__(.+)", "files.php", "folder=/%s/&item=%s&field=%s&subitem=%s&action=".$_SERVER["REQUEST_METHOD"]);
}

define("MAIN_SCRIPT",basename($_SERVER["PHP_SELF"]));

define("NOCONTENT",true);
define("NOSESSION",true);
error_reporting(E_ALL);
@ignore_user_abort(0);

if (ini_get("register_globals")) {
  foreach (array_keys($GLOBALS) as $key) if (!in_array($key,array("GLOBALS","_REQUEST","_SERVER"))) unset($GLOBALS[$key]);
}

// WebDAV Specs: http://www.webdav.org/specs/rfc2518.html

// TODO2 rename/move file ?, implement digest ?
// path lookup cache ? protect get file-xy floods ?
// validate file extension ?

$dav_folder = "/sgdav";
$method = $_SERVER["REQUEST_METHOD"];

$path = str_replace("//","/",urldecode($_SERVER["REQUEST_URI"]));

if ($method == "OPTIONS") {
  header("DAV: 1,2");
  header("MS-Author-Via: DAV");
  header("Allow: OPTIONS,GET,HEAD,POST,DELETE,PROPFIND,COPY,MOVE,PUT,LOCK,UNLOCK");
  header("Content-Type: httpd/unix-directory");
  exit;
}

// MS Word locks non existing files
if ($method == "LOCK") {
  exit('<?xml version="1.0" encoding="utf-8"?>
<D:prop xmlns:D="DAV:"><D:activelock><D:timeout>Second-7200</D:timeout></D:activelock></D:prop>');
}

$content_length = webdav_get_header("Content-Length");
if ($content_length > 20*1024*1024) { // filesize > 20M
  header("HTTP/1.1 413 Request Entity Too Large");
  exit;
}
if ($_SERVER["REQUEST_URI"]=="/sgdav/test") exit("ok");

$lock_ext = "vbs";
$agent = webdav_get_header("User-Agent");
if (!empty($agent) and (strpos($agent,"davfs2")!==false or strpos($agent,"Linux"))) $lock_ext = "sh";

define("SIMPLE_STORE","../simple_store");
@include(SIMPLE_STORE."/config.php");
if (!defined("SETUP_DB_HOST")) exit;
if (!ENABLE_WEBDAV) {
  header("HTTP/1.1 403 Forbidden");
  exit("WebDAV disabled");
}

require("core/functions.php");

set_error_handler("debug_handler");
if (!isset($_SERVER["SERVER_ADDR"]) or $_SERVER["SERVER_ADDR"]=="") $_SERVER["SERVER_ADDR"]="127.0.0.1";
if (ini_get("magic_quotes_gpc")!==false and get_magic_quotes_gpc()) modify::stripslashes($_REQUEST);

if (!sql_connect(SETUP_DB_HOST, SETUP_DB_USER, sys_decrypt(SETUP_DB_PW,sha1(SETUP_ADMIN_USER)), SETUP_DB_NAME)) {
  header("HTTP/1.1 503 Service Unavailable");
  $err = sprintf("{t}Cannot connect to database %s on %s.{/t}\n",SETUP_DB_NAME,SETUP_DB_HOST).sql_error();
  trigger_error($err,E_USER_ERROR);
  exit($err);
}

login_handle_login();

$export_files = array("index.html","index.xls");

$out = "";
$result = 0;
switch ($method) {
  case "PROPFIND":
    list($result,$out) = webdav_propfind($path,$dav_folder);
    break;
  case "MOVE":
	@ignore_user_abort(1);
	$result = 409;
    if (empty($_SERVER["HTTP_DESTINATION"])) break;

    $db_path = substr($path,strlen($dav_folder));
	list($id,$left,$parent) = webdav_process_folder_string($db_path."/");
  
    if ($id==0 and $left==1) {
	  // TODO2 implement
	  // $newfile = modify::basename(urldecode($_SERVER["HTTP_DESTINATION"]));
	} else if ($id!=0 and $left==0) {
	  $result = webdav_rename_folder($id, $parent, basename(urldecode($_SERVER["HTTP_DESTINATION"])));
	}
    break;
  case "MKCOL":
    @ignore_user_abort(1);
    $db_path = substr($path,strlen($dav_folder));
  	$result = webdav_create_folder($db_path);
    break;
  case "PROPPATCH":
    // http://msdn.microsoft.com/en-us/library/aa142976%28EXCHG.65%29.aspx
	$result = 207;
	$out = '<?xml version="1.0"?>'."\n";
	$out .= '<a:multistatus xmlns:a="DAV:"><a:response><a:propstat><a:status>HTTP/1.1 200 OK</a:status></a:propstat></a:response></a:multistatus>';
	break;
  default: 
    $result = 405;
	break;
}

switch ($result) {
  case 201: header("HTTP/1.1 201 Created"); break;
  case 207:	header("HTTP/1.1 207 Multi-Status"); break;
  case 403: header("HTTP/1.1 403 Forbidden"); break;
  case 404: header("HTTP/1.1 404 Not Found"); break;
  case 405: header("HTTP/1.1 405 Method not allowed"); break;
  case 409: header("HTTP/1.1 409 Conflict"); break;
}
if (DEBUG_WEBDAV) {
  debug_file($_SERVER);
  debug_file(webdav_get_header());
  debug_file($out);
}
if ($out!="") echo $out;

function webdav_forward($preg_uri, $target, $url, $preg_dest="") {
  if (!preg_match("!".$preg_uri."!", $_SERVER["REQUEST_URI"], $match)) return;
  if ($preg_dest!="" and !preg_match("!".$preg_dest."!", $_SERVER["HTTP_DESTINATION"], $match)) return;
  array_shift($match);
  $url = vsprintf($url, $match);
  parse_str($url, $_REQUEST);
  include $target;
  exit;
}

function webdav_rename_folder($id, $parent, $frenametitle) {
  if ($frenametitle=="") return 409;
  $folder = db_select_value("simple_sys_tree","id",array("parent=@parent@","ftitle=@title@"),array("parent"=>$parent,"title"=>$frenametitle));
  if (empty($folder) and db_get_right($id,"write")) {
    $result = folders::rename($id,$frenametitle,"");
    if ($result!="") return 201;
  }
  return 403;
}
  
function webdav_create_folder($db_path) {  
  list($id,$left,$unused) = webdav_process_folder_string(dirname($db_path)."/");
  $title = basename($db_path);
  if ($left!=0 or $id==0 or $title=="") return 409;
  
  $folder = db_select_value("simple_sys_tree","id",array("parent=@parent@","ftitle=@title@"),array("parent"=>$id,"title"=>$title));
  if (empty($folder) and db_get_right($id,"write")) {
    folders::create($title,"files","",$id,false);
	sys_log_stat("new_folders",1);
	return 201;
  }
  return 403;
}

function webdav_response($path,$created,$modified,$size,$is_dir,$can_write,$lockedby) {
  if ($created==0) $created = NOW-3600;
  if ($modified==0) $modified = NOW-3600;
  $data = array(
    "path"=>$path,
	"ctime"=>gmdate("Y-m-d\TH:i:s\Z",$created),
	"mtime"=>gmdate("D, d M Y H:i:s",$modified),
  );
  return '
<D:response xmlns:lp1="DAV:" xmlns:lp2="http://apache.org/dav/props/" xmlns:ns1="urn:schemas-microsoft-com:">
<D:href>'.modify::htmlquote(dirname($data["path"])."/".rawurlencode(basename($data["path"]))).'</D:href>
<D:propstat>
<D:prop>'.
(!$is_dir?"<ns1:Win32FileAttributes>".($can_write?"00000080":"00002001")."</ns1:Win32FileAttributes>":"").'
<lp1:creationdate>'.$data["ctime"].'</lp1:creationdate>
<lp1:getlastmodified>'.$data["mtime"].'</lp1:getlastmodified>'.
(!$is_dir?"<lp1:getcontentlength>".$size."</lp1:getcontentlength>":"").'
<lp1:getetag>"'.md5($data["path"]).'"</lp1:getetag>
<lp1:displayname>'.modify::htmlquote(basename($data["path"])).'</lp1:displayname>'.
($is_dir?"<lp1:resourcetype><D:collection/></lp1:resourcetype>":"<lp1:resourcetype/>").'
<lp2:executable>T</lp2:executable>
<D:supportedlock>
<D:lockentry><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry>
<D:lockentry><D:lockscope><D:shared/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry>
</D:supportedlock>
'.($lockedby==""?"<D:lockdiscovery/>":'<D:lockdiscovery>
<D:activelock>
<D:locktype><D:write/></D:locktype>
<D:lockscope><D:exclusive/></D:lockscope>
<D:depth>infinity</D:depth>
<D:owner>'.$lockedby.'</D:owner>
<D:timeout>Infinite</D:timeout>
<D:locktoken>opaquelocktoken:1</D:locktoken>
</D:activelock>
</D:lockdiscovery>').
($is_dir?"<D:getcontenttype>httpd/unix-directory</D:getcontenttype>":"").'
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
';
}

function webdav_propfind($path,$dav_folder) {
  if ($path[strlen($path)-1]!="/") $path .= "/";
  $db_path = substr($path,strlen($dav_folder));

  if (!empty($_SERVER["HTTP_DEPTH"])) $multiple = true; else $multiple = false;
  $is_dir = true;
  $is_file = false;
  $id = 0;
  $file = "";
  if (in_array(basename($path),$GLOBALS["export_files"])) {
	$path = substr($path,0,-1);
	$is_dir = false;
	$is_file = true;
  }
  if (!$is_file and $path!=$dav_folder."/") {
    list($id,$left,$parent) = webdav_process_folder_string($db_path);
    if ($id==0) {
	  $path = substr($path,0,-1);
	  $is_dir = false;
	  $file = webdav_find_file($left,$path,$parent);
	  if ($file=="" and !strpos($path,".".$GLOBALS["lock_ext"])) return array(404,"");
	}
  }
  if (!$is_file and $is_dir and is_numeric($id)) {
    header("ETag: \"".base64_encode($path)."\""); 
    header("Content-Type: text/xml; charset=utf-8");
    $out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<D:multistatus xmlns:D=\"DAV:\">";
	$out .= webdav_response($path,0,0,0,$is_dir,true,"");

    $folders = array();
    if ($multiple) {
	  $folders = db_select("simple_sys_tree",array("ftitle","created","lastmodified"),array("parent=@parent@",$_SESSION["permission_sql_read"]),"lft asc","",array("parent"=>$id));
      if (is_array($folders) and count($folders)>0) {
	    foreach ($folders as $folder) {
	      $cpath = $path.$folder["ftitle"]."/";
	      $out .= webdav_response($cpath,$folder["created"],$folder["lastmodified"],0,true,true,"");
	    }
      }
      $cfolder = db_select_first("simple_sys_tree",array("id","ftype","fcount"),array("id=@id@",$_SESSION["permission_sql_read"]),"",array("id"=>$id));
	  if (!empty($cfolder["id"]) and $cfolder["fcount"]>0) {
	    $out .= webdav_build_files($path,$cfolder);
	  }
	}
  } else {
    if (file_exists($file)) {
	  $created = filectime($file);
	  $modified = filemtime($file);
	  $size = filesize($file);
	} else {
	  $created = 0;
	  $modified = 0;
	  $size = 0;
	}
    header("Content-Type: text/xml; charset=utf-8");
    $out = "<?xml version='1.0' encoding='utf-8'?>\n<D:multistatus xmlns:D='DAV:'>";
    $out .= webdav_response($path,$created,$modified,$size,$is_dir,true,"");
  }
  $out .= "</D:multistatus>";
  return array(207,$out);
}

function webdav_find_file($left,$path,$parent) {
  if ($left!=1) return "";

  $file = basename($path);
  $path = dirname($path);
  $local_file = SIMPLE_CACHE."/upload/".$_SESSION["username"].sha1($path)."--".urlencode($file);
  if (file_exists($local_file)) return $local_file;

  $local_file .= ".link";
  if (file_exists($local_file)) {
    $link = file($local_file);
    return $link[1];
  }
  
  if (!preg_match("!^(\d+)(_[^_]+)?_(\d+)__(.+)!",$file,$match)) return "";

  $cfolder = db_select_first("simple_sys_tree",array("id","ftype","fcount"),"id=@id@","",array("id"=>$parent));
  if (empty($cfolder["id"]) or $cfolder["fcount"]==0) return "";

  if (empty($match[2])) $field = "filedata"; else $field = ltrim($match[2],"_");
  $row_field = db_select_value("simple_".$cfolder["ftype"],$field,"id=@id@",array("id"=>$match[1]));
  if (empty($row_field)) return "";
  
  $files = explode("|",trim($row_field,"|"));

  if (empty($files[$match[3]]) or !strpos($files[$match[3]],"--".urlencode($match[4]))) return "";
  
  return $files[$match[3]];
}

function webdav_build_files($path,$cfolder) {
  $out = "";
  $tview = "details";
  foreach ($GLOBALS["export_files"] as $val) {
    $out .= webdav_response($path.$val,NOW,NOW,0,false,false,"");
  }
  $schemafile = sys_find_module($cfolder["ftype"]);
  $table = db_get_schema($schemafile,$cfolder["id"],$tview);
  $tview = sys_array_shift(array_keys($table["views"]));
  $tname = $table["att"]["NAME"];
  $current_view = $table["views"][$tview];

  $writeable = db_get_right($cfolder["id"],"write");
  $file_fields = array();
  foreach ($current_view["fields"] as $key=>$field) {
    if (isset($field["SIMPLE_TYPE"]) and $field["SIMPLE_TYPE"]=="files") $file_fields[] = $key;
  }
  if (empty($file_fields)) return $out;
  $fields = $file_fields;
  $fields[] = "id";
  $fields[] = "created";
  $fields[] = "lastmodified";
  $vars = array("folders" => $cfolder["id"]);
  $vars_noquote = array("permission_sql_read_nq" => $_SESSION["permission_sql_read"]);
  $where = $current_view["SQLWHERE"];
  foreach ($where as $key=>$value) {
	if (strpos($value,"@item@")) $where[$key] = "1=1";
  }
  $rows = db_select($tname,$fields,$where,"","",$vars,array("sqlvarsnoquote"=>$vars_noquote));
  if (!is_array($rows) or count($rows)==0) return $out;
  foreach ($rows as $row) {
    foreach ($file_fields as $field) {
	  if ($row[$field]=="") continue;
	  $row[$field] = explode("|",trim($row[$field],"|"));
	  foreach ($row[$field] as $key=>$file) {
		if ($file=="") continue;
		if ($writeable) {
		  $can_unlock = sys_can_unlock($file,$_SESSION["username"]);
		  $can_lock = sys_can_lock($file);
		} else {
		  $can_unlock = false;
		  $can_lock = false;
		}
		if (!$can_lock) $lockedby = sys_get_lock($file); else $lockedby = "";
		$created = 0;
		$modified = 0;
		if (file_exists($file)) {
		  $created = filectime($file);
		  $modified = filemtime($file);
		}
		if ($field=="filedata") $f_field = ""; else $f_field = "_".$field;
	    $f_key = "_".$key;
		$filename = $row["id"].$f_field.$f_key."__".modify::basename($file);
		$out .= webdav_response($path.$filename,$created,$modified,@filesize($file),false,$can_lock or $can_unlock,$lockedby);
		if (!ENABLE_WEBDAV_LOCKING) continue;
		if ($can_lock) {
		  $out .= webdav_response($path."Lock_".$row["id"].$f_field.$f_key.".".$GLOBALS["lock_ext"],$created,NOW,0,false,false,"system");
		} else if ($can_unlock) {
		  $out .= webdav_response($path."Unlock_".$row["id"].$f_field.$f_key.".".$GLOBALS["lock_ext"],$created,NOW,0,false,false,"system");
  } } } }
  return $out;
}

function webdav_get_header($key="") {
  if (function_exists("getallheaders")) {
    $headers = getallheaders();
	if ($key=="") return $headers;
	if (isset($headers[$key])) return $headers[$key];
  } else {
    if ($key=="User-Agent") $key = "HTTP_USER_AGENT";
	  else if ($key=="Content-Length") $key = "CONTENT_LENGTH";
	  else if ($key=="Authorization") $key = "HTTP_AUTHORIZATION";
	if (isset($_SERVER[$key])) return $_SERVER[$key];
  }
  return "";
}

function webdav_process_folder_string($folder) {
  $parent = 0;
  $parent_last = 0;
  $nodes = explode("/",$folder);
  $left = count($nodes);
  foreach ($nodes as $node) {
    $left--;
    if ($node=="") continue;
	$where = array("ftitle=@title@", "parent=@parent@", $_SESSION["permission_sql_read"]);
	$vars = array("title"=>$node,"parent"=>$parent);
    $row_id = db_select_value("simple_sys_tree","id",$where,$vars);
    if (!empty($row_id)) {
	  $parent_last = $parent;
	  $parent = $row_id;
	} else {
	  return array(0,$left,$parent);
	}
  }
  return array($parent,$left,$parent_last);
}