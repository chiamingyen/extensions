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

class lib_imap extends lib_default {

static $cache = array();

static function get_dirs($path, $parent, $recursive) {
  $tree = array();
  self::_get_dirs($path, 1, 0, $parent, $recursive, $tree);
  return $tree;
}

static function default_values($path) {
  $imap_path = substr($path,strpos($path,"/")+1);
  if (basename($imap_path)=="Drafts") return array("sendnow"=>0);
  return array();
}

static function count($path,$where,$vars,$mfolder) {
  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return 0;
  $cid = "imap_count_".md5($path.serialize(sys_credentials($mfolder)));
  if (($count = sys_cache_get($cid)) !== false) return $count;
  if (!($imap = self::_connect($mfolder))) return 0;
  if (PEAR::isError($count = $imap->getNumberOfMessages($imap_path))) return 0;
  sys_cache_set($cid,$count,IMAP_LIST_CACHE);
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return array();
  $count = self::count($path,$where,$vars,$mfolder);

  $creds = sys_credentials($mfolder);
  $cid = "imap_".md5($path.$count.serialize($creds));
  
  if ($count > 100 or isset($vars["search"]) or in_array("SORT",self::_get_capabilities($mfolder))) {
	$datas = self::_get_datas_sort($mfolder,$imap_path,$cid,$order,$limit,$where,$vars,$count);
	if (isset($vars["search"])) unset($vars["search"]);
	$where = array();
	$limit = array();
  } else {
    $datas = self::_get_datas($mfolder,$imap_path,$cid,self::count($path,$where,$vars,$mfolder));
  }
  $rows = array();
  foreach ($datas as $key=>$data) {
	$row = array();
	$row["_msg_id"] = $datas[$key]["msg_id"];
	$row["_msg_uid"] = str_replace(array("<", ">"), "", @$datas[$key]["message-id"].$datas[$key]["uidl"]);
	$structure = array();
	foreach ($fields as $field) {
	  switch ($field) {
	      case "id": $row[$field] = $path."/?".$datas[$key]["uidl"]; break;
	      case "folder": $row[$field] = $path; break;
  		  case "searchcontent": $row[$field] = $data["subject"]." ".$data["from"]; break;
		  case "subject": $row[$field] = !empty($data["subject"])?$data["subject"]:"- {t}Empty{/t} -"; break;
	 	  case "efrom": $row[$field] = isset($data["from"])?$data["from"]:""; break;
	      case "eto": $row[$field] = isset($data["to"])?$data["to"]:""; break;
  		  case "cc": $row[$field] = isset($data["cc"])?$data["cc"]:""; break;
		  case "attachment": $row[$field] = ""; break;
		  case "attachment_show": $row[$field] = ""; break;
		  case "message": $row[$field] = ""; break;
		  case "message_html": $row[$field] = ""; break;
		  case "receipt": $row[$field] = !empty($data["disposition-notification-to"])?"1":"0"; break;
	  	  case "created":
	      case "lastmodified": $row[$field] = isset($data["date"])?strtotime($data["date"]):"0"; break;
	  	  case "lastmodifiedby": $row[$field] = ""; break;
		  // Answered Flagged Deleted Seen Draft
		  case "flags": $row[$field] = strtolower(str_replace("\\","",implode(" ",$datas[$key]["flags"]))); break;
	  	  case "dsize":
		  case "size": $row[$field] = $datas[$key]["size"]; break;
		  case "headers":
  		    $row[$field] = "";
	  	    foreach ($data as $data_key=>$data_item) $row[$field] .= ucfirst($data_key).": ".(is_array($data_item)?implode("\n  ",$data_item):$data_item)."\n";
		    break;
	  }
	}
	$row["seen"] = (int)(in_array("\\Seen", $datas[$key]["flags"]) || sys_cache_get("imap_seen_".$mfolder."_".$datas[$key]["uidl"]));
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }

  $rows = sys_select($rows,$order,$limit,$fields);
  if (count($rows)>0) {
    foreach ($rows as $key=>$row) {
 	  $structure = "";
      if (in_array("message_html",$fields)) {
		$rows[$key]["message_html"] = self::_getmessage($row["_msg_id"],$row["_msg_uid"],$mfolder,$imap_path,"imap_message_html_".md5($row["_msg_uid"]),true);
	  }
      if (in_array("message",$fields)) {
		$rows[$key]["message"] = self::_getmessage($row["_msg_id"],$row["_msg_uid"],$mfolder,$imap_path,"imap_message_".md5($row["_msg_uid"]),false);
	  }
      if (in_array("attachment_show",$fields)) {
		$rows[$key]["attachment_show"] = "";
		$files = array();
	    $structure = self::_getstructure($row["_msg_id"],$mfolder,$imap_path,"imap_structure_".md5($row["_msg_uid"]));
	    foreach ($structure as $item) {
		  if (self::_is_attachment($item)) $files[] = $item["name"]." (~".modify::filesize($item["size"]).")";
	    }
		if (DEBUG) $files[] = "original.eml.txt";
	    if (count($files)>0) $rows[$key]["attachment_show"] = "|".implode("|",$files)."|";
	  }
      if (in_array("attachment",$fields)) {
		$rows[$key]["attachment"] = "";
	    $structure = self::_getstructure($row["_msg_id"],$mfolder,$imap_path,"imap_structure_".md5($row["_msg_uid"]));
	    $files = array();
		foreach ($structure as $item) {
		  if (self::_is_attachment($item)) {
	        $local = sys_cache_get_file("imap", $row["_msg_uid"], "--".$item["name"], true);
		    if (!file_exists($local) and $imap=self::_connect($mfolder)) {
  	  		  $imap->selectMailbox($imap_path);
			  $imap->getBodyPart($row["_msg_id"],$item["id"],$item["encoding"],$local);
			}
 		    $files[] = $local;
		  }
		}
	    $local = sys_cache_get_file("imap", $row["_msg_uid"], "--original.eml.txt", true);
	    if (!file_exists($local) and $imap=self::_connect($mfolder)) {
		  $imap->selectMailbox($imap_path);
		  $imap->getBodyPart($row["_msg_id"],"","",$local);
		}
	    $files[] = $local;
		
		if (count($files)>0) $rows[$key]["attachment"] = "|".implode("|",$files)."|";
  } } }
  return $rows;
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return "error";

  if (!$imap = self::_connect($mfolder)) return "error";
  if (PEAR::isError($result = $imap->selectMailbox($imap_path))) {
    exit(sprintf("[5] {t}Connection error: %s [%s]{/t}", $result->getMessage(), "IMAP"));
  }
  $id = substr($vars["id"],strpos($vars["id"],"/?")+2);  
  if (PEAR::isError($ids = $imap->search("UID ".$id))) {
    exit(sprintf("[5b] {t}Imap-error: %s{/t}",$ids->getMessage()));
  }
  if (!is_array($ids) or count($ids)==0) return "";

  $dest_path = "";
  if (!sys_strbegins($imap_path."/","Trash/") and !sys_strbegins($imap_path."/","INBOX/Trash/")) {
    $dest_path = substr($path,0,strpos($path,"/"))."/INBOX/Trash/";
    if (PEAR::isError($result = $imap->copyMessages("INBOX".$imap->delimiter."Trash", $ids[0]))) {
	  $dest_path = substr($path,0,strpos($path,"/"))."/Trash/";
      if (PEAR::isError($result = $imap->copyMessages("Trash", $ids[0]))) {
	    $imap->createMailbox("INBOX".$imap->delimiter."Trash");
		sys_cache_remove("imap_boxes_".md5($path.serialize(sys_credentials($mfolder))));
		if (PEAR::isError($result = $imap->copyMessages("INBOX".$imap->delimiter."Trash", $ids[0]))) {
	      exit(sprintf("[7] {t}Imap-error: %s{/t}",$result->getMessage()));
  } } } }
  if (PEAR::isError($result = $imap->deleteMsg($ids[0]))) {
	exit(sprintf("[6] {t}Imap-error: %s{/t}",$result->getMessage()));
  }
  if (PEAR::isError($result = $imap->cmdExpunge())) {
	exit(sprintf("[9] {t}Imap-error: %s{/t}",$result->getMessage()));
  }
  sys_cache_remove("imap_count_".md5($path.serialize(sys_credentials($mfolder))));
  if ($dest_path!="") sys_cache_remove("imap_count_".md5($dest_path.serialize(sys_credentials($mfolder))));
  return "";
}

static function update($path,$data,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  $id = substr($vars["id"],strpos($vars["id"],"/?")+2);  
  
  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return "error";

  if (!$imap = self::_connect($mfolder)) return "error";

  if (isset($data["seen"])) {
	sys_cache_set("imap_seen_".$mfolder."_".$id,1,IMAP_CACHE);
	if (PEAR::isError($result = $imap->selectMailbox($imap_path))) return "error";
	$imap->cmdUidFetch($id, "BODY[TEXT]");
	return "";
  }
  
  $source_path = substr($vars["folder_source"],strpos($vars["folder_source"],"/",strpos($vars["folder_source"],"/")+1)+1,-1);
  if ($source_path=="") return "error";

  // TODO optimize
  if (PEAR::isError($result = $imap->selectMailbox($imap_path))) {
	$imap->createMailbox($imap_path);
	$imap->subscribeMailbox($imap_path);
	sys_cache_remove("imap_boxes_".md5(dirname($path)."/".serialize(sys_credentials($mfolder))));
  }

  if (PEAR::isError($result = $imap->selectMailbox($source_path))) {
    sys_warning(sprintf("[10] {t}Connection error: %s [%s]{/t}", $result->getMessage(), "IMAP"));
	return "error";
  }
  
  if (PEAR::isError($ids = $imap->search("UID ".$id))) {
    sys_warning(sprintf("[5b] {t}Imap-error: %s{/t}",$ids->getMessage()));
	return "error";
  }
  if (!is_array($ids) or count($ids)==0) return "";

  if (PEAR::isError($result = $imap->copyMessages($imap_path, $ids[0]))) {
    sys_warning(sprintf("[12] {t}Imap-error: %s{/t}",$result->getMessage()));
    return "error";
  }
  if (PEAR::isError($result = $imap->deleteMsg($ids[0]))) {
    sys_warning(sprintf("[13] {t}Imap-error: %s{/t}",$result->getMessage()));
    return "error";
  }

  if (PEAR::isError($result = $imap->cmdExpunge())) {
    sys_warning(sprintf("[14] {t}Imap-error: %s{/t}",$result->getMessage()));
	return "error";
  }
  $cid = serialize(sys_credentials($mfolder));
  sys_cache_remove("imap_count_".md5(substr($path,0,strpos($path,"/"))."/".$source_path."/".$cid));
  sys_cache_remove("imap_count_".md5($path.$cid));
  return "";
}

static function insert($path,$data,$mfolder) {
  if (empty($data["efrom"]) or $data["efrom"]=="none") return "{t}No sender specified.{/t}";

  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return "error".$path;

  if (!$imap = self::_connect($mfolder)) return "error";
  @set_time_limit(120); // 2min

  if (PEAR::isError($result = $imap->selectMailbox($imap_path))) {
    sys_warning(sprintf("[15] {t}Connection error: %s [%s]{/t}", $result->getMessage(), "IMAP"));
	return "error";
  }
  
  foreach (array("attachment","message","message_html","subject","efrom","eto") as $key) {
    if (!isset($data[$key])) $data[$key] = "";
  }
  $ctype = "text/plain";
  $message = $data["message"];
  if (!empty($data["message_html"])) {
	$ctype = "text/html";
	$message = $data["message_html"];
  }
  
  $email = array();
  if ($data["attachment"]!="") {
	if (!class_exists("Mail_mimePart",false)) require("lib/mail/mimePart.php");

    $email = new Mail_mimePart("This is a multi-part message in MIME format.", array("content_type"=>"multipart/mixed"));
    $email->addSubPart($message, array("content_type"=>$ctype,"charset"=>"UTF-8","encoding"=>"base64"));
    $attachments = explode("|",$data["attachment"]);
	foreach ($attachments as $attachment) {
	  if (file_exists($attachment) and filesize($attachment)>0) {
	    $mime = "application/octet-stream";
	  	switch (modify::getfileext($attachment)) {
		  case "gif": $mime = "image/gif"; break;
		  case "jpeg": 
		  case "jpg": $mime = "image/jpeg"; break;
		  case "png": $mime = "image/png"; break;
		  case "txt": $mime = "text/plain"; break;
		  case "html": case "htm": $mime = "text/html"; break;
		}
	    $email->addSubPart(file_get_contents($attachment), array(
		  "content_type"=>$mime."; name=\"".modify::basename($attachment)."\"", "encoding"=>"base64",
		  "disposition"=>"attachment", "dfilename"=>modify::basename($attachment),
	    ));
	  }
	}
	$email = $email->encode();
  }

  $headers = array(
	"Subject: ".$data["subject"], "From: ".$data["efrom"],
    "To: ".$data["eto"], "Date: ".sys_date("r", $data["created"]), 
    "Mime-Version: 1.0", "X-Mailer: Simple Groupware ".CORE_VERSION,
	"Message-ID: <".uniqid("sgs", true)."@".$_SERVER["SERVER_ADDR"].">",
    "Content-Type: ".($data["attachment"]?$email["headers"]["Content-Type"]:$ctype."; charset=UTF-8")
  );
  if (!$data["attachment"]) $headers[] = "Content-Transfer-Encoding: base64";
  if (!empty($data["receipt"])) $headers[] = "Disposition-Notification-To: ".$data["efrom"];
  $headers = self::_encodeHeaders($headers);

  $message = implode("\r\n",$headers)."\r\n\r\n".($data["attachment"]?$email["body"]:base64_encode($message));
  $message = str_replace(array("\n","\r\r\n"),"\r\n",$message); // rfc822 compliance

  if (PEAR::isError($result = $imap->appendMessage($message))) {
    sys_log_message_alert("php", sprintf("[16] {t}Imap-error: %s{/t}",$result->getMessage()));
	return "error";
  }
  sys_cache_remove("imap_count_".md5($path.serialize(sys_credentials($mfolder))));
  return "";
}

static function rename_folder($title,$path,$mfolder) {
  $imap_path = substr($path,strpos($path,"/")+1);
  if ($imap_path=="") return "error".$path;
  $imap_path_new = dirname($imap_path)."/".$title."/";
  if ($imap_path_new==$imap_path) return "";
  
  if (!$imap = self::_connect($mfolder)) return "error";
  if (!PEAR::isError($imap->selectMailbox($imap_path_new))) return "";

  if (PEAR::isError($result = $imap->renameMailbox($imap_path, $imap_path_new))) {
	return sprintf("[24] {t}Imap-error: %s{/t}",$result->getMessage());
  }
  sys_cache_remove("imap_boxes_".md5(dirname($path)."/".serialize(sys_credentials($mfolder))));
  
  if (PEAR::isError($exists = $imap->selectMailbox($imap_path_new))) {
	return sprintf("[25] {t}Imap-error: %s{/t}",$exists->getMessage());
  }
  return "ok";
}

static function create_folder($title,$parent,$mfolder) {
  $imap_path = substr($parent,strpos($parent,"/")+1);
  $imap_path .= $title;
  if (!$imap = self::_connect($mfolder)) return "error";
  if (!PEAR::isError($imap->selectMailbox($imap_path))) return "";

  if (PEAR::isError($result = $imap->createMailbox($imap_path)) or PEAR::isError($result = $imap->subscribeMailbox($imap_path))) {
    return sprintf("[18] {t}Imap-error: %s{/t}",$result->getMessage());
  }
  sys_cache_remove("imap_boxes_".md5($parent.serialize(sys_credentials($mfolder))));

  if (PEAR::isError($exists = $imap->selectMailbox($imap_path))) {
    return sprintf("[19] {t}Imap-error: %s{/t}",$exists->getMessage());
  }
  return "ok";
}

static function delete_folder($path,$mfolder) {
  $imap_path = substr($path,strpos($path,"/")+1,-1);
  if ($imap_path=="") return "error".$path;
  if (!$imap = self::_connect($mfolder)) return "error";

  if (PEAR::isError($result = $imap->deleteMailbox($imap_path))) {
	return sprintf("[21] {t}Imap-error: %s{/t}",$result->getMessage());
  }
  sys_cache_remove("imap_boxes_".md5(dirname($path)."/".serialize(sys_credentials($mfolder))));
  if (PEAR::isError($imap->selectMailbox($imap_path))) return "ok";
  return "";
}

private static function _quote($str) {
  return preg_replace("/([\"\\\\])/", "\\\\$1", $str);
}

private static function _connect($mfolder) {
  if (empty(self::$cache[$mfolder])) {
    $creds = sys_credentials($mfolder);
	if ($creds["server"]=="") return false;
	
	if (!$creds["port"]) $creds["port"] = 143;
	if ($creds["ssl"] and !extension_loaded("openssl")) {
	  sys_warning(sprintf("[0] {t}%s is not compiled / loaded into PHP.{/t}","OpenSSL"));
	  return false;
	}

	$imap = new Net_IMAP();
	if (PEAR::isError($result = $imap->connect(($creds["ssl"]?$creds["ssl"]."://".$creds["server"]:$creds["server"]), $creds["port"]))) {
	  sys_warning(sprintf("[1] {t}Connection error: %s [%s]{/t}", $result->getMessage(), "IMAP"));
	  return false;
	}
	if (PEAR::isError($ret = $imap->login(self::_quote($creds["username"]), self::_quote($creds["password"])))) {
	  sys_warning(sprintf("[2] {t}Imap-error: %s{/t}",$ret->getMessage()));
	  return false;
	}
	self::$cache[$mfolder] = $imap;
  }
  return self::$cache[$mfolder];
}

private static function _get_dirs($path, $left, $level, $parent, $recursive, &$tree) {
  $right = $left+1;
  $creds = sys_credentials($parent);
  if ($recursive and sys_is_folderstate_open($path,"imap",$parent)) {
    $cid = "imap_boxes_".md5($path.serialize($creds));
    if (!($imap_boxes = sys_cache_get($cid)) and !is_array($imap_boxes)) {
	  if (!$imap = self::_connect($parent)) return $right+1;
	  $imap_path = substr($path,strpos($path,"/")+1);
	  if (strpos($creds["options"],"subscribed=true")!==false) {
	    $cid2 = "imap_".md5($parent.serialize($creds));
	    $imap_boxes = self::_get_mailboxes_subscribed($imap,$imap_path,$cid2);
	  } else {
	    $imap_boxes = self::_get_mailboxes($imap,$imap_path);
	  }
      sys_cache_set($cid,$imap_boxes,IMAP_CACHE);
	}
	if (count($imap_boxes)>0) {
	  foreach ($imap_boxes as $mailbox) {
		$right = self::_get_dirs($path.$mailbox."/", $right, $level+1, $parent, true, $tree);
	  }
	}
  } else $right = $right+2;

  $title = basename($path);
  $icon = "";
  if ($level==0) $icon = "sys_nodb_imap.png";
  
  /* TODO2 find icons
  $ltitle = strtolower($title);
  if ($ltitle=="sent") $icon = "";
  if ($ltitle=="drafts") $icon = "";
  if ($ltitle=="trash") $icon = "sys_nodb_imap_trash.png";
  if (strpos($ltitle,"junk")!==false) $icon = "";
  */
  $tree[$left] = array("id"=>$path,"lft"=>$left,"rgt"=>$right,"flevel"=>$level,"ftitle"=>$title,"ftype"=>"sys_nodb_imap","icon"=>$icon);
  return $right+1;
}

private static function _encodeHeaders($input) {
  foreach ($input as $hdr_name => $hdr_value) {
    preg_match_all("/(\w*[\x80-\xFF]+\w*)/", $hdr_value, $matches);
    foreach ($matches[1] as $value) {
      $replacement = preg_replace("/([\x80-\xFF])/e",'"=".strtoupper(dechex(ord("\1")))',$value);
      $hdr_value = str_replace($value,"=?UTF-8?Q?".$replacement."?=",$hdr_value);
    }
    $input[$hdr_name] = $hdr_value;
  }
  return $input;
}

private static function _get_mailboxes($imap,$path) {
  if (PEAR::isError($boxes = $imap->getMailboxes($path,3))) {
    sys_log_message_alert("php", sprintf("[20] {t}Imap-error: %s{/t}",$boxes->getMessage()));
	return array();
  }
  $delimiter = $imap->delimiter;
  if (count($boxes)>0) natcasesort($boxes);
  if (count($boxes)>100) $boxes = array_slice($boxes,0,100);
  foreach ($boxes as $key=>$value) {
    if (($pos = strrpos($value,$delimiter))) $boxes[$key] = substr($value,$pos+1);
  }
  return $boxes;
}

private static function _get_mailboxes_subscribed($imap,$path,$cid) {
  $delimiter = $imap->delimiter;
  if (!$boxes = sys_cache_get($cid) and !is_array($boxes)) {
	if (PEAR::isError($boxes = $imap->getMailboxesSubscribed())) {
	  sys_log_message_alert("php", sprintf("[20] {t}Imap-error: %s{/t}",$boxes->getMessage()));
	  return array();
	}
    if (count($boxes)>0) natcasesort($boxes);
    if (count($boxes)>100) $boxes = array_slice($boxes,0,100);
    sys_cache_set($cid,$boxes,IMAP_CACHE);
  }
  $result = array();
  if ($delimiter!="/") $path = str_replace("/",$delimiter,$path);
  foreach ($boxes as $value) {
    if ($path=="" or sys_strbegins($value,$path)) {
	  if ($path!="") $value = substr($value,strlen($path));
	  if (($pos = strpos($value,$delimiter))) $value = substr($value,0,$pos);
	  $result[$value] = "";
	}
  }
  return array_keys($result);
}

private static function _get_capabilities($mfolder) {
  $cid = "imap_capa_".md5(serialize(sys_credentials($mfolder)));
  if (($capa = sys_cache_get($cid)) !== false) return $capa;
  if (!($imap = self::_connect($mfolder))) return array();
  $capabilities = $imap->_serverSupportedCapabilities;
  sys_cache_set($cid,$capabilities,IMAP_LIST_CACHE);
  return $capabilities;
}

private static function _get_datas($mfolder,$imap_path,$cid,$limit) {
  if ($limit==0) return array();
  $datas = sys_cache_get($cid);
  if (is_array($datas)) return $datas;

  if (!$imap = self::_connect($mfolder)) return array();
  if (PEAR::isError($imap->selectMailbox($imap_path))) return array();

  $i = 1;
  $step = 25; // fetch headers step by step for better performance
  $datas = array();
  while ($i < 5000) {
	$max = $i+$step-1;
	if ($max > $limit) $max = $limit;
	$data = $imap->getParsedHeaders($i.":".$max,"");
	if (count($data)!=0) $datas = array_merge($datas,$data); else break;
	if ($max >= $limit) break;
	$i += $step;
  }
  sys_cache_set($cid,$datas,IMAP_LIST_CACHE);
  return $datas;
}

private static function _get_search_where($where,$vars) {
  $search_map = array("any"=>"TEXT", "message"=>"BODY", "subject"=>"SUBJECT", "efrom"=>"FROM", "created"=>"DATE", "cc"=>"CC", "eto"=>"TO", "size"=>"SIZE");
  $search = array();
  if (!empty($vars["search"])) $where[] = "search";
  if (count($where)>0) {
	foreach ($where as $item) {
	  if ($item=="1=1") continue;
	  $item = str_replace(array(" not like "," like "),array("!=","="),$item);
	  if (strpos($item," in ")) {
	    $item = explode(" in ",$item);
		$var = str_replace(array("@","(",")"),"",$item[1]);
		if (!isset($vars[$var])) continue;
		if ($item[0]=="id") {
	      $ids = array();
		  foreach ($vars[$var] as $id) $ids[] = substr($id,strpos($id,"/?")+2);
		  $search[] = "UID ".implode(",",$ids);
		}
	  } else if (strpos($item,">")) {
	    $item = explode(">",str_replace(" > ",">",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		if ($item[0]=="size") $search[] = "LARGER ".(int)$vars[$var];
		if ($item[0]=="created") $search[] = "SINCE ".sys_date("j-M-Y",$vars[$var]);
	  } else if (strpos($item,"<")) {
	    $item = explode("<",str_replace(" < ","<",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		if ($item[0]=="size") $search[] = "SMALLER ".(int)$vars[$var];
		if ($item[0]=="created") $search[] = "BEFORE ".sys_date("j-M-Y",$vars[$var]);
	  } else if (strpos($item,"!=")) {
	    $item = explode("!=",str_replace(" != ","!=",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		if ($item[0]=="created") {
		  $search[] = "NOT ON ".sys_date("j-M-Y",$vars[$var]);
		} else {
		  if (!isset($search_map[$item[0]])) $item[0] = "any";
		  $search[] = "NOT ".$search_map[$item[0]]." \"".addslashes($vars[$var])."\"";
		}
	  } else if (strpos($item,"=")) {
	    $item = explode("=",str_replace(" = ","=",$item));
		$var = str_replace("@","",$item[1]);
		if (!isset($vars[$var])) continue;
		if ($item[0]=="created") {
		  $search[] = "ON ".sys_date("j-M-Y",$vars[$var]);
		} else {
		  if (!isset($search_map[$item[0]])) $item[0] = "any";
		  $search[] = $search_map[$item[0]]." \"".addslashes($vars[$var])."\"";
		}
	  } else if ($item=="search") {
		$search[] = "TEXT \"".addslashes($vars["search"])."\"";
  } } }
  return str_replace("%","",implode(" ",$search));
}

private static function _get_sort_query($order) {
  $sort_map = array("subject"=>"SUBJECT", "efrom"=>"FROM", "created"=>"DATE", "cc"=>"CC", "eto"=>"TO", "size"=>"SIZE");
  $s_order = explode(" ",$order);
  $query = "REVERSE DATE";
  if (isset($sort_map[$s_order[0]])) {
	$query = ($s_order[1]=="desc"?"REVERSE ":"").$sort_map[$s_order[0]];
  }
  return $query;
}

private static function _get_datas_sort($mfolder,$imap_path,$cid,$order,$s_limit,$where,$vars,$count) {
  $cid = $cid.md5($order.implode($s_limit).serialize($vars));
  $datas = sys_cache_get($cid);
  if (is_array($datas)) return $datas;
  if (!$imap = self::_connect($mfolder)) return array();

  if (PEAR::isError($imap->selectMailbox($imap_path))) return array();
  
  $search = self::_get_search_where($where,$vars);
  if ($search!="") {
    if (DEBUG_IMAP) echo "Search: ".$search."<br>\n";
	
	if (!in_array("SORT",self::_get_capabilities($mfolder))) {
	  if (PEAR::isError($ids = $imap->search($search))) {
	    sys_warning(sprintf("[2c] {t}Imap-error: %s{/t}",$ids->getMessage()));
	    return array();
	  }
	  if (!sys_strbegins($order,"created")) {
	    sys_warning(sprintf("{t}Imap-error: %s{/t}","{t}Server does not support sorting{/t}"));
	  }
	  if ($order=="created asc") rsort($ids); else sort($ids);
	} else {
      $query = self::_get_sort_query($order);
	  if (PEAR::isError($ids = $imap->sort($query,$search))) {
	    sys_warning(sprintf("[2cc] {t}Imap-error: %s{/t}",$ids->getMessage()));
	    return array();
	  }
	}
	if (isset($vars["search"])) _asset_process_pages(count($ids));
  } else if (!in_array("SORT",self::_get_capabilities($mfolder))) {
	if (!sys_strbegins($order,"created")) {
	  sys_warning(sprintf("{t}Imap-error: %s{/t}","{t}Server does not support sorting{/t}"));
	}
	if ($order=="created asc") {
      $ids = range($s_limit[0]+1,$s_limit[0]+$s_limit[1]);
	} else {
      $ids = range($count-$s_limit[0],max($count+1-$s_limit[0]-$s_limit[1], 1));
	}
  } else {
    $query = self::_get_sort_query($order);
	if (PEAR::isError($ids = $imap->sort($query))) {
	  sys_warning(sprintf("[2b] {t}Imap-error: %s{/t}",$ids->getMessage()));
	  return array();
	}
  }
  if (count($s_limit)==2 and count($ids) > $s_limit[1]) $ids = array_slice($ids,$s_limit[0],$s_limit[1]);
  if (count($ids)>0) {
	$datas = $imap->getParsedHeaders(implode(",",$ids));
	sys_cache_set($cid,$datas,IMAP_LIST_CACHE);
  } else $datas = array();
  return $datas;
}

private static function _getstructure($msg_id,$mfolder,$imap_path,$cid) {
  if (($structure = sys_cache_get($cid))) return $structure;
  if (!$imap = self::_connect($mfolder)) return array();
  if (PEAR::isError($imap->selectMailbox($imap_path))) {
	$structure = array();
  } else {
	$structure = $imap->getStructure($msg_id);
  }
  sys_cache_set($cid,$structure,IMAP_MAIL_CACHE);
  return $structure;
}

private static function _getmessage($msg_id,$msg_uid,$mfolder,$imap_path,$cid,$html) {
  if (($message = sys_cache_get($cid))) return $message;
  if (!$imap = self::_connect($mfolder)) return array();
  $message = "";
  if (!PEAR::isError($imap->selectMailbox($imap_path))) {
    $structure = self::_getstructure($msg_id,$mfolder,$imap_path,"imap_structure_".md5($msg_uid));
	$file_index = -1;
	foreach ($structure as $item) {
	  $is_attachment = self::_is_attachment($item);
	  if ($is_attachment) $file_index++;
	  $content = "";
	  if ($item["contenttype"]=="message/rfc822") {
	    $headers = $imap->getParsedHeaders($msg_id, $item["id"]?$item["id"].".":"");
		$content = self::_drawheader($headers[0]);

		if (!sys_contains($item["charset"], "utf")) {
	      $content = modify::utf8_encode($content, $item["charset"]);
		}
		if ($html) $content = modify::nl2br(modify::htmlquote(trim($content)), false, true);

	  } else if (!$is_attachment and $item["size"]>0 and strpos($item["contenttype"],"text/")!==false) {
		$data_body = $imap->getBodyPart($msg_id,$item["id"],$item["encoding"]);
		
		if (!sys_contains($item["charset"], "utf")) {
	      $data_body = modify::utf8_encode($data_body, $item["charset"]);
		}
		if ($html) {
		  if ($content!="") $content .= "<hr>";
		  if ($item["contenttype"]!="text/html") {
			$data_body = modify::nl2br(modify::htmlquote(trim($data_body)), false, true);
			if ($item["contenttype"]!="text/plain") $content .= "<b>[".$item["contenttype"]."]</b><br>";
		  }
		  $content .= "<div class='external_content' style='margin-left:".($item["level"]*20)."px;'><code>".$data_body."</code></div>";
		} else { // text
		  if ($content!="") $content .= "\n";
		  if ($item["contenttype"]=="text/html") {
			$data_body = modify::htmlmessage($data_body);
		  } else if ($item["contenttype"]!="text/plain") {
			$content .= "[".$item["contenttype"]."]\n";
		  }
		  $content .= trim($data_body)."\n";
		}	  
	  } else if (strpos($item["contenttype"],"image/")!==false and $item["cid"]!="") {
	    $url = "download.php?folder=@folder@&view=attachment_show&field=attachment&item[]=@id@&subitem=".$file_index;
	    $message = str_replace("cid:".$item["cid"], $url, $message);
	  }
	  if ($message!="" and $content!="") {
		if ($html) $content = "<hr><br>".$content; else $content = "\n----\n\n".$content;
	  }
	  $message .= $content;
 	} 
  }
  sys_cache_set($cid,$message,IMAP_MAIL_CACHE);
  return $message;
}

private static function _drawheader($headers) {
  $content = "";
  if (isset($headers["subject"])) $content .= "Subject: ".$headers["subject"]."\n";
  if (isset($headers["date"])) $content .= "Date: ".$headers["date"]."\n";
  if (isset($headers["from"])) $content .= "From: ".$headers["from"]."\n";
  if (isset($headers["to"])) $content .= "To: ".$headers["to"]."\n";
  return $content;
}

private static function _is_attachment($item) {
  if ($item["disposition"]=="attachment") return true;
  // TODO2 add limit for HTML mails?
  //  if ($item["size"] > 16384) return true;
  if (strpos($item["contenttype"],"text/")===false and $item["contenttype"]!="message/rfc822" and $item["contenttype"]!="invalid") return true;
  return false;
}

}