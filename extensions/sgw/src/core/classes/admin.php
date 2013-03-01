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

class admin {

private static $_restore_here = false;
private static $_restore_onlynewer = false;
private static $_restore_missing = false;
private static $_restore_folder = 0;

static function process_action_sys() {

  @set_time_limit(900);
  switch ($_REQUEST["action_sys"]) {
    case "maintenance":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  $lock_file = SIMPLE_STORE."/maintenance.lck";
	  if (!file_exists($lock_file)) {
	    touch($lock_file);
	    sys_log_message_alert("info","{t}Maintenance mode{/t}: {t}Active{/t}");
	  } else {
	    unlink($lock_file);
	    sys_log_message_alert("info","{t}Maintenance mode{/t}: {t}Inactive{/t}");
	  }
	  break;
    case "clear_locking":
	  self::_remove_locks();
	  dirs_create_empty_dir(SIMPLE_STORE."/locking");
	  sys_log_message_log("clean","{t}Locking{/t}");
	  break;
    case "clear_output":
	  dirs_create_empty_dir(SIMPLE_CACHE."/smarty");
	  dirs_create_empty_dir(SIMPLE_CACHE."/output");
	  dirs_create_empty_dir(SIMPLE_CACHE."/artichow");
	  dirs_create_empty_dir(SIMPLE_CACHE."/thumbs");
	  sys_log_message_log("clean","{t}Output{/t}");
	  break;
	case "clear_debug":
	  dirs_create_empty_dir(SIMPLE_CACHE."/debug");
	  sys_log_message_log("clean","{t}Debug-dir{/t}");
	  break;
	case "clear_cms":
	  dirs_create_empty_dir(SIMPLE_CACHE."/cms");
	  sys_log_message_log("clean","{t}CMS{/t}");
	  break;
	case "clear_ip":
	  dirs_create_empty_dir(SIMPLE_CACHE."/ip");
	  if (APC) apc_clear_cache("user");
	  sys_log_message_log("clean","IP");
	  break;
	case "clear_schema":
	  dirs_create_empty_dir(SIMPLE_CACHE."/schema");
	  if (APC) apc_clear_cache("user");
	  sys_log_message_log("clean","{t}Schema{/t}");
	  break;
	case "clear_schemadata":
	  dirs_create_empty_dir(SIMPLE_CACHE."/schema_data");
	  dirs_create_empty_dir(SIMPLE_CACHE."/preview");
	  if (APC) apc_clear_cache("user");
	  sys_log_message_log("clean","{t}Schema data{/t}");
	  break;
	case "clear_email": 
	  dirs_create_empty_dir(SIMPLE_CACHE."/imap");
	  dirs_create_empty_dir(SIMPLE_CACHE."/pop3");
	  sys_log_message_log("clean","{t}E-mail{/t}");
	  break;
	case "clean_notifications":
	  db_delete("simple_sys_notifications",array("sent='1'"),array());
	  sql_table_optimize("simple_sys_notifications");
	  sys_log_message_log("clean","{t}Notifications{/t}");
	  break;
	case "clear_upload":
	  dirs_create_empty_dir(SIMPLE_CACHE."/upload");
	  sys_log_message_log("clean","{t}Uploaded files{/t}");
	  break;
	case "clean_tables":
	  db_optimize_tables();
	  sys_log_message_log("clean","{t}Optimize Tables{/t}");
	  break;
	case "clean_statistics":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  
	  db_delete("simple_sys_stats",array(),array());
	  sql_table_optimize("simple_sys_stats");
	  sys_log_message_log("clean","{t}Statistics{/t}");
	  break;
	case "clean_events":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  
	  db_delete("simple_sys_events",array(),array());
	  $folder = db_select_value("simple_sys_tree","id","ftype=@type@",array("type"=>"sys_events"));
	  if (!empty($folder)) {
	    db_delete("simple_sys_search",array("folder=@folder@"),array("folder"=>$folder));
	  }
	  sql_table_optimize("simple_sys_events");
	  sql_table_optimize("simple_sys_search");
	  sys_log_message_log("clean","{t}Events{/t}");
	  break;
	case "clean_trash":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  
	  $trash = db_select_value("simple_sys_tree","id","anchor=@anchor@",array("anchor"=>"trash"));
	  if (!empty($trash)) {

		$folders = db_select("simple_sys_tree",array("id","fmountpoint"),"parent=@parent@","","",array("parent"=>$trash));
		if (is_array($folders) and count($folders)>0) {
		  foreach ($folders as $folder) {
		    if (!empty($folder["fmountpoint"])) continue;
			folders::delete($folder["id"]);
	  } } }
	  dirs_create_empty_dir(SIMPLE_STORE."/trash");
	  sys_log_message_log("clean","{t}Trash{/t}");
	  sys_redirect("index.php?".sys::$urladdon);
	  break;
	case "clean_cache": 
	  $dirs = array(SIMPLE_STORE."/cron", SIMPLE_CACHE."/imap", SIMPLE_CACHE."/pop3", SIMPLE_CACHE."/smarty",
					SIMPLE_CACHE."/cms", SIMPLE_CACHE."/cifs", SIMPLE_CACHE."/gdocs",
	  				SIMPLE_CACHE."/output", SIMPLE_CACHE."/artichow", SIMPLE_CACHE."/thumbs", SIMPLE_CACHE."/schema");
	  foreach ($dirs as $dir) self::_dirs_clean_dir($dir,2592000); // 30 days
	  
	  self::_remove_locks(86400);
	  $dirs = array(SIMPLE_CACHE."/schema_data", SIMPLE_CACHE."/preview", SIMPLE_STORE."/locking", SIMPLE_CACHE."/upload",
	  				SIMPLE_CACHE."/ip", SIMPLE_CACHE."/debug", SIMPLE_CACHE."/updater");
	  foreach ($dirs as $dir) self::_dirs_clean_dir($dir,86400); // 1 day
	  sys_log_message_log("clean","{t}Clean Cache{/t}");
	  sys_redirect("index.php?".sys::$urladdon);
	  break;
	case "edit_setup":
	  require("core/sysconfig.php");
	  exit;
	  break;
	case "clear_setup":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
  	  @unlink(SIMPLE_STORE."/config.php");
	  @unlink(SIMPLE_STORE."/config_old.php");
	  header("Location: index.php");
	  exit;
	  break;
	case "backup":
	  self::_create_backup($_SESSION["folder"]);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
	case "restore_newer":
  	  self::$_restore_onlynewer = true;
	  echo self::_restore($_REQUEST["file"]);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
	case "restore":
	  echo self::_restore($_REQUEST["file"]);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
    case "restore_here":
	  self::$_restore_here = true;
	  echo self::_restore($_REQUEST["file"]);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
    case "restore_missing":
	  self::$_restore_missing = true;
	  echo self::_restore($_REQUEST["file"]);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
    case "rebuild_search":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  self::rebuild_schema(true);
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
    case "clear_session":
	  if (!sys_validate_token()) sys_die("{t}Invalid security token{/t}");
	  db_delete("simple_sys_session",array(),array());
	  if (APC) apc_clear_cache("user");
	  sys_log_message_log("clean","{t}Sessions{/t}");
	  self::_out("<br><a href='index.php'>{t}Continue{/t}</a>");
	  exit;
	  break;
    case "phpinfo":
	  echo "System time: " . date("c") . "<br>";
	  echo "Database time: " . sgsml_parser::sql_date();
	  phpinfo();
	  exit;
	  break;
  }
}

static function apc_stats() {
  if (!APC) return "{t}not installed{/t}";
  $data = apc_sma_info();
  return modify::filesize($data["seg_size"]-$data["avail_mem"])." / ".modify::filesize($data["seg_size"]);
}

static function disk_stats() {
  $free = disk_free_space(realpath(SIMPLE_STORE));
  $total = disk_total_space(realpath(SIMPLE_STORE));
  return modify::filesize($total-$free)." / ".modify::filesize($total);
}

static function rebuild_schema($rebuild_search=false) {
  if ($rebuild_search) {
    self::_out(sprintf("{t}Processing %s ...{/t}","schema, search index"));
  } else {
	self::_out(sprintf("{t}Processing %s ...{/t}","schema"));
  }
  if (sgsml_parser::table_exists("simple_sys_search") and $rebuild_search) {
    db_delete("simple_sys_search",array(),array());
  }
  $files = array();
  $folders = array("modules/schema/", "modules/schema_sys/",
	SIMPLE_EXT."/modules/schema/", SIMPLE_EXT."/modules/schema_sys/", // ext overrides default
	SIMPLE_CUSTOM."/modules/schema/", SIMPLE_CUSTOM."/modules/schema_sys/" // custom overrides default and ext
  );
  foreach ($folders as $folder) {
	if (!is_dir($folder)) continue;
	foreach (scandir($folder) as $file) {
	  if (!strpos($file,".xml")) continue;
	  $files[$file] = $folder.$file;
	}
  }
  
  @set_time_limit(60*count($files));
  foreach ($files as $file) {
	self::_out(basename($file)." ",false);
	$schema = db_get_schema($file,"","",false);
	sys::$db_queries = array(); // reduce memory usage

	if (!$rebuild_search or sys_strbegins(basename($file),"nodb_") or empty($schema["views"]["display"])) continue;
	if (!empty($schema["att"]["SQL_HANDLER"]) or !empty($schema["att"]["NO_SEARCH_INDEX"])) continue;
	$table = $schema["att"]["NAME"];
	$fields = $schema["fields"];

	$rows = db_select($table,array("id","folder"),array(),"","");
	if (!is_array($rows) or count($rows)==0) continue;
	self::_out("... ",false);
	foreach ($rows as $row) {
	  if (folder_in_trash($row["folder"])) continue;
	  db_search_update($table,$row["id"],$fields);
	}
  }
  self::_out("<br>",false);
}

private static function _build_backupdata($folder_obj) {
  $ftype = $folder_obj["ftype"];
  $folder = $folder_obj["id"];
  self::_out("{t}Backup{/t}: ".$folder);
  $folder_path = self::_get_backup_folderpath($folder_obj);
  
  $schema_data = db_get_schema(sys_find_module($ftype));
  $tname = $schema_data["att"]["NAME"];
  
  $data = array();
  if (!strpos($tname,"_nodb_")) {
    $data = db_select($tname,"*","folder=@folder@","created asc","",array("folder"=>$folder));
  }
  $output = "<table name=\"".modify::htmlquote($tname)."\" folderpath=\"".modify::htmlquote($folder_path)."\">\n";
  $output .= "<assetfolder id=\"".$folder_obj["id"]."\">\n";
  foreach ($folder_obj as $akey=>$aval) {
	$output .= "<".$akey.">".modify::htmlquote($aval)."</".$akey.">\n";
  }
  $files = array();
  $output .= "</assetfolder>\n\n";
  if (is_array($data) and count($data)>0) {
    foreach ($data as $asset) {
	  $output .= "<asset id=\"".$asset["id"]."\">\n";
	  foreach ($asset as $akey=>$aval) {
	    $is_file = false;
	    if (!empty($schema_data["fields"][$akey]) and $schema_data["fields"][$akey]["SIMPLE_TYPE"]=="files") {
		  $file_arr = explode("|",$aval);
		  foreach ($file_arr as $file) if (!is_dir($file) and file_exists($file)) $files[] = $file;
		  $aval = str_replace(SIMPLE_STORE."/","",$aval);
		  $is_file = true;
		}
	    $output .= "<".$akey.($is_file?" is_file='true'":"").">".modify::htmlquote($aval)."</".$akey.">\n";
	  }
	  $output .= "</asset>\n\n";
    }
  }
  $output .= "</table>\n";

  $files_fields = array();
  foreach ($schema_data["fields"] as $field) {
    if ($field["SIMPLE_TYPE"]=="files") $files_fields[] = str_replace(SIMPLE_STORE."/","",$field["NAME"]);
  }
  return array("output"=>$output,"files"=>$files);
}

private static function _create_backup($folder) {
  $bfolder = db_select_first("simple_sys_tree","*","id=@id@","lft asc",array("id"=>$folder));
  $folders = db_select("simple_sys_tree","*","lft between @lft@ and @rgt@","lft asc","",array("lft"=>$bfolder["lft"],"rgt"=>$bfolder["rgt"]));
  if (empty($bfolder["id"]) or !is_array($folders)) return "";

  $folder_path = self::_get_backup_folderpath($bfolder);
  $rand_str = NOW."-".sha1(uniqid(rand(), true).uniqid(rand(), true));
  $tarfile = SIMPLE_STORE."/backup/".$rand_str."--".urlencode(str_replace("/","__",$folder_path))."--".sys_date("Y-m-d---H-i-s").".tar";
  $cachefile = SIMPLE_CACHE."/backup/".$rand_str."--".urlencode(str_replace("/","__",$folder_path))."--".sys_date("Y-m-d---H-i-s")."_content.xml";

  $files = array();
  file_put_contents($cachefile, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<data>\n", LOCK_EX);
  foreach ($folders as $folder) {
    $data = self::_build_backupdata($folder);
	$files = array_merge($files,$data["files"]);
	sys_file_append($cachefile, $data["output"]);
  }
  sys_file_append($cachefile, "</data>\n");

  $cmd = sys_find_bin("tar")." -cf ".modify::realfilename($tarfile);
  $files = array_merge(array($cachefile),$files);
  foreach ($files as $file) $cmd .= " ".str_replace("\\","/",modify::realfilename($file));
  
  if (DEBUG) self::_out("TAR: ".$cmd."\n\n");
  echo sys_exec($cmd);

  if (file_exists($cachefile)) unlink($cachefile);

  $message = "{t}Backup{/t}: ".$folder_path." [".$bfolder["id"]."]";
  sys_log_message_log("info",$message);
  self::_out($message);
  return "";
}

private static function _get_backup_folderpath($folder) {
  $parents = db_get_parents($folder);
  $parents[] = $folder;
  $folder_path = "";
  foreach ($parents as $parent) $folder_path .= "/".$parent["ftitle"];
  return $folder_path;
}

private static function _restore($filename) {
  ob_end_flush();
  $filename = str_replace(" ","+",$filename);
  $filename = SIMPLE_STORE."/backup/".basename($filename);
  if (!file_exists($filename)) return "{t}file not found.{/t} (".$filename.")";
  if (filesize($filename)==0) return "{t}No entries found.{/t} (".$filename.")";
  
  self::_out("{t}Extracting files{/t}: ".$filename);
  self::_out("");

  if (self::$_restore_here) {
    $ftitle = str_replace(array("__","---","--"),array("/"," "," "),substr(modify::basename($filename),0,-4));
	$ftitle = substr($ftitle,strrpos($ftitle,"/")+1);
    $id = folders::create(substr($ftitle,0,40),"blank","",$_SESSION["folder"],false);
    self::$_restore_folder = $id;
	self::_out("{t}Insert{/t}: simple_sys_tree: ".$ftitle." [".$id."]");
  }
  
  $result = sys_exec(sys_find_bin("tar")." -tf ".modify::realfilename($filename));
  $file_list = explode("\n",$result);

  if (count($file_list)==0) return "";
  $base_dir = SIMPLE_STORE."/restore_".NOW."/";
  sys_mkdir($base_dir);
  $cmd = "cd ".modify::realfilename($base_dir)." && ".sys_find_bin("tar")." -xf ".modify::realfilename($filename);
  if (DEBUG) self::_out("TAR: ".$cmd."\n\n");
  echo sys_exec($cmd);

  $update_ids = array();
  $update_folders = array();
  $restore_maps = array();
  
  $xml_file = array_shift($file_list);
  self::_out("{t}Parsing{/t}: ".$xml_file);

  $xml = simplexml_load_file($base_dir.$xml_file);
  
  foreach ($xml->table as $data) {
    $data = get_object_vars($data->assetfolder);
	unset($data["@attributes"]);
  
    $id = $data["id"];
	if (!empty($data["anchor"])) {
      $existing = db_select_first("simple_sys_tree",array("id","'' as lastmodified"),"anchor=@anchor@","",array("anchor"=>$data["anchor"]));
	  if (!empty($existing["id"])) unset($data["anchor"]);
	} else {
	  $existing = db_select_first("simple_sys_tree",array("id","lastmodified"),"id=@id@","",array("id"=>$id));	
	}
	if (!isset($data["fdescription"])) $data["fdescription"] = "";
	$ftype = $data["ftype"];
	$keys = array("fsizecount","fchsizecount","fcount","fchcount","ffcount","lft","rgt","flevel","folder","id","ftype");
	foreach ($keys as $key) unset($data[$key]);
	if (isset($restore_maps[$data["parent"]])) {
	  $data["parent"] = $restore_maps[$data["parent"]];
	}
    if (empty($existing["id"]) or self::$_restore_here) {
      $parent = db_select_value("simple_sys_tree","id","id=@id@",array("id"=>$data["parent"]));
	  if (empty($parent) or (count($restore_maps)==0 and self::$_restore_here)) {
	    $data["parent"] = self::$_restore_folder;
	  }
	  $id2 = folders::create($data["ftitle"],$ftype,$data["fdescription"],$data["parent"],false);
	  self::_out("{t}Insert{/t}: simple_sys_tree: ".$data["ftitle"]." [ID ".$id." -> parent/id: ".$data["parent"]."/".$id2."]");
	  $restore_maps[$id] = $id2;
	  $id = $id2;
	} else {
	  $restore_maps[$id] = $existing["id"];
	}
	
	if (!self::$_restore_missing and (!self::$_restore_onlynewer or $data["lastmodified"] > $existing["lastmodified"])) {
	  self::_out("{t}Update{/t}: simple_sys_tree ".$id);
	  $error = db_update("simple_sys_tree",$data,array("id=@id@"),array("id"=>$id));
  	  if ($error) self::_out($error);
	}
  }

  foreach ($xml->table as $table_item) {
	if (!isset($table_item->asset) or count($table_item->asset)==0) continue;

	foreach ($table_item->asset as $asset) {
      $table = $table_item["name"];
	  if ($table=="simple_sys_tree") continue;

	  $data = get_object_vars($asset);
	  unset($data["@attributes"]);
	  
	  foreach ($data as $dkey=>$val) {
	    $obj = $asset->$dkey;
	    if (!isset($obj["is_file"]) or $val=="") continue;
		$file_arr[$key] = "";
		$file_arr = explode("|",trim($val,"|"));
		foreach ($file_arr as $key=>$value) {
		  foreach ($file_list as $file) {
			if (basename($file)!=basename($value)) continue;
			$value = $base_dir.$file;
			break;
		  }
		  $file_arr[$key] = $value;
		}
		$data[$dkey] = "|".implode("|",$file_arr)."|";
	  }
	  $id = $data["id"];
      $existing = db_select_first($table,array("id","lastmodified"),"id=@id@","",array("id"=>$id));
	  $folder = $data["folder"];
	  if (isset($restore_maps[$folder])) $data["folder"] = $restore_maps[$folder];
	  
      if (empty($existing["id"]) or self::$_restore_here) {
	    if (self::$_restore_missing) {
          $data["id"] = $id;
	    } else {
          $data["id"] = sql_genID($table)*100 + $_SESSION["serverid"];
	    }
	    self::_out("{t}Insert{/t}: ".$table.": ".$data["id"]);
        $error = db_insert($table,$data);
	    if ($error) self::_out($error);
	    $update_folders[$data["folder"]] = $table;
	    $update_ids[$data["folder"]][] = $data["id"];
	  } else if (!self::$_restore_missing) {
	    if (!self::$_restore_onlynewer or $data["lastmodified"] > $existing["lastmodified"]) {
	      self::_out("{t}Update{/t}: ".$table." ".$id);
	  	  $error = db_update($table,$data,array("id=@id@"),array("id"=>$id));
  	      if ($error) self::_out($error);
	      $update_folders[$data["folder"]] = $table;
	      $update_ids[$data["folder"]][] = $id;
  }	} } }
  
  if (count($update_folders)>0) {
	foreach ($update_folders as $folder=>$table) {
	  if (strpos($table,"nodb_")) continue;
	  db_update_treesize($table,$folder);

	  $ftype = str_replace("simple_","",$table);
	  $schema = db_get_schema(sys_find_module($ftype));

	  if (empty($schema["views"]["display"])) continue;
	  if (!empty($schema["att"]["SQL_HANDLER"]) or !empty($schema["att"]["NO_SEARCH_INDEX"])) continue;
	  self::_out("... ");
		
	  $fields = $schema["fields"];
	  if (folder_in_trash($folder)) continue;
	  foreach ($update_ids[$folder] as $id) {
		self::_out("{t}Rebuild search index{/t}: ".$table." [".$id."]");
		db_search_update($table,$id,$fields);
  } } }
  self::_out("");
  $message = "{t}Restore complete{/t}: ".str_replace(array("__","---","--"),array("/","] ["," ["),substr(modify::basename($filename),0,-4))."]";
  sys_log_message_log("info",$message);
  self::_out($message);
  return "";
}

private static function _dirs_clean_dir($path,$olderthan) {
  if (is_dir($path."/")) dirs_delete_all($path,$olderthan,false);
  sys_mkdir($path);
  dirs_create_index_htm($path."/");
}

private static function _remove_locks($olderthan=0) {
  $lfile = SIMPLE_STORE."/locking/locks.txt";
  if (file_exists($lfile) and ($olderthan==0 or filectime($lfile)+$olderthan < time())) {
    $data = explode("\n",file_get_contents($lfile));
	foreach ($data as $file) {
	  if ($file!="" and file_exists($file.".lck")) unlink($file.".lck");
	}
  }
}

private static function _out($str,$newline = true) {
  if (DEBUG) $str = sys_remove_trans($str)." ".memory_get_usage(true);
  echo $str.($newline?"<br>":"")."\n";
  @ob_flush();
  flush();
}
}