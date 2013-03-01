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

require("lib/mail/POP3.php");
require("lib/mail/mimeDecode.php");

class lib_pop3 extends lib_default {

static function count($path,$where,$vars,$mfolder) {
  $cid = "pop3_count_".md5(serialize(sys_credentials($mfolder)));
  if (($count = sys_cache_get($cid)) !== false) return $count;
  if (!$pop3 = self::_connect($mfolder) or !$pop3) return 0;
  if (PEAR::isError($count = $pop3->numMsg())) return 0;
  sys_cache_set($cid,$count,POP3_LIST_CACHE);
  return $count;
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  $cid = "pop3_".md5(serialize(sys_credentials($mfolder)));
  $datas = self::_get_datas($mfolder, $cid);
  if (!isset($datas[0])) return array();
  $rows = array();
  foreach ($datas[0] as $key=>$data) {
    $msg_id = $datas[1][$key]["msg_id"];
	$msg_uid = $datas[1][$key]["uidl"];
	$structure = array();
	$row = array();
	$row_id = "pop3_fields_".md5(implode("_",$fields).$msg_uid);
	
	if (!$row = sys_cache_get($row_id)) {
	  if (!$pop3 = self::_connect($mfolder) or !$pop3) return array();
	  foreach ($fields as $field) {
	    switch ($field) {
	      case "id": $row[$field] = $path."/?".md5($msg_uid); break;
	      case "folder": $row[$field] = $path; break;
  		  case "searchcontent": $row[$field] = $data["subject"]." ".$data["from"]; break;
		  case "subject": $row[$field] = !empty($data["subject"])?$data["subject"]:"- {t}Empty{/t} -"; break;
	 	  case "efrom": $row[$field] = isset($data["from"])?$data["from"]:""; break;
	      case "eto": $row[$field] = isset($data["to"])?$data["to"]:""; break;
  		  case "cc": $row[$field] = isset($data["cc"])?$data["cc"]:""; break;
		  case "receipt": $row[$field] = !empty($data["disposition-notification-to"])?"1":"0"; break;
	  	  case "created":
	      case "lastmodified": $row[$field] = isset($data["date"])?strtotime($data["date"]):"0"; break;
		  case "lastmodifiedby": $row[$field] = ""; break;
	  	  case "dsize":
		  case "size": $row[$field] = $datas[1][$key]["size"]; break;
		  case "headers":
  		    $row[$field] = "";
	  	    foreach ($data as $data_key=>$data_item) $row[$field] .= ucfirst($data_key).": ".(is_array($data_item)?implode("\n  ",$data_item):$data_item)."\n";
		    break;
		  case "message_html":
  		  case "message":
		    if ($field=="message_html") $html = true; else $html = false;
	  		$row[$field] = "";
			if (empty($structure)) {
			  $input = $pop3->getMsg($msg_id);
			  $decode = new Mail_mimeDecode($input);
			  $structure = self::_parse_structure($decode->decode(array("include_bodies"=>true,"decode_bodies"=>true)),1,$msg_uid);
			  
			  $raw_file = sys_cache_get_file("pop3", $msg_uid, "--original.eml.txt", true);
			  file_put_contents($raw_file, $input);
			}
			$file_index = -1;
			foreach ($structure as $skey=>$item) {
			  $is_attachment = self::_is_attachment($item);
			  if ($is_attachment) $file_index++;
			  $content = "";
			  if (sys_strbegins($item["contenttype"],"multipart") and $skey!=0) {
				if (isset($item["header"]["subject"])) {
				  $content = self::_drawheader($item["header"]);
				  
				  if (!sys_contains($item["charset"], "utf")) {
					$content = modify::utf8_encode($content, $item["charset"]);
				  }
				  if ($html) $content .= modify::nl2br(modify::htmlquote(trim($content)), false, true);
				}
			  } else {
				if (!$is_attachment and $item["size"]>0 and strpos($item["contenttype"],"text/")!==false) {
				  $data_body = $item["body"];
				  
				  if (!strpos("#".$item["charset"], "utf")) {
					$data_body = modify::utf8_encode($data_body, $item["charset"]);
				  }
				  if ($html) {
					if ($content!="") $content .= "<hr>";
				    if ($item["contenttype"]!="text/html") {
					  $data_body = modify::nl2br(modify::htmlquote(trim($data_body)), false, true);
					  if ($item["contenttype"]!="text/plain") $content .= "<b>[".$item["contenttype"]."]</b><br>";
		  			}
					$item["level"] = substr_count($item["id"],".");
				    $content .= "<div class='external_content' style='margin-left:".($item["level"]*20)."px;'><code>".$data_body."</code></div>";
				  } else {
					if ($content!="") $content .= "\n";
					if ($item["contenttype"]=="text/html") {
					  $data_body = modify::htmlmessage($data_body);
					} else if ($item["contenttype"]!="text/plain") {
					  $content .= "[".$item["contenttype"]."]\n";
					}
					$content .= trim($data_body)."\n";
				  }
				} else if (strpos($item["contenttype"],"image/")!==false and !empty($item["cid"])) {
				  $url = "download.php?folder=@folder@&view=attachment_show&field=attachment&item[]=@id@&subitem=".$file_index;
				  $row[$field] = str_replace("cid:".$item["cid"],$url,$row[$field]);
				}
			  }
			  if ($row[$field]!="" and $content!="") {
			    if ($html) $row[$field] .= "<hr><br>"; else $row[$field] .= "\n----\n\n";
			  }
			  $row[$field] .= $content;
		    }
			break;
		  case "attachment":
			$row[$field] = "";
			if (empty($structure)) {
			  $input = $pop3->getMsg($msg_id);
			  $decode = new Mail_mimeDecode($input);
			  $structure = self::_parse_structure($decode->decode(array("include_bodies"=>true,"decode_bodies"=>true)),1,$msg_uid);

			  $raw_file = sys_cache_get_file("pop3", $msg_uid, "--original.eml.txt", true);
			  file_put_contents($raw_file, $input);
			}
			$files = array();
			foreach ($structure as $item) {
			  if ($item["disposition"]=="attachment") {
			    $files[] = sys_cache_get_file("pop3", $msg_uid, "--".$item["name"]);
			  }
			}
			$files[] = sys_cache_get_file("pop3", $msg_uid, "--original.eml.txt");
		    if (count($files)>0) $row[$field] = "|".implode("|",$files)."|";
			break;
		}
	  }
	  sys_cache_set($row_id,$row,POP3_MAIL_CACHE);
	}
	$row["_msg_id"] = $msg_id;
	$row["seen"] = file_exists(sys_cache_get_file("pop3", md5($msg_uid), "seen_".$mfolder))?"1":"";

	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  return sys_select($rows,$order,$limit,$fields);
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  if (!$pop3 = self::_connect($mfolder) or !$pop3) return "error";

  $datas = $pop3->getListing();
  foreach ($datas as $data) {
    if ($vars["id"]==$path."/?".md5($data["uidl"])) {
  	  if (PEAR::isError($result = $pop3->deleteMsg($data["msg_id"]))) {
	    exit(sprintf("{t}Pop3-error: %s{/t} [2]",$result->getMessage()));
	  }
	  break;
    }
  }
  $pop3->disconnect();
  $creds = sys_credentials($mfolder);
  sys_cache_remove("pop3_".md5(serialize($creds)));
  sys_cache_remove("pop3_count_".md5(serialize($creds)));
  return "";
}

static function update($path,$data,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";
  if (isset($data["seen"])) {
    $id = substr($vars["id"],strpos($vars["id"],"?")+1);
	$file = sys_cache_get_file("pop3", $id, "seen_".$mfolder, true);
    touch($file);
  }
  return "";
}

private static function _connect($mfolder) {
  static $cache = array();
  if (empty($cache[$mfolder])) {
	$creds = sys_credentials($mfolder);
	if ($creds["server"]=="") return false;
  
	if (!$creds["port"]) $creds["port"] = 110;
	if ($creds["ssl"] and !extension_loaded("openssl")) {
	  sys_warning(sprintf("{t}%s is not compiled / loaded into PHP.{/t}","OpenSSL"));
	  return false;
	}
	$pop3 = new Net_POP3();
	if (PEAR::isError($result = $pop3->connect(($creds["ssl"]?$creds["ssl"]."://".$creds["server"]:$creds["server"]), $creds["port"]))) {
	  sys_warning(sprintf("{t}Connection error: %s [%s]{/t}", $result->getMessage(), "POP3"));
	  return false;
	}
	if (PEAR::isError($ret = $pop3->login($creds["username"], $creds["password"], "USER"))) {
	  sys_warning(sprintf("{t}Pop3-error: %s{/t} [1]",$ret->getMessage()));
	  return false;
	}
	$cache[$mfolder] = $pop3;
  }
  return $cache[$mfolder];
}

private static function _get_datas($mfolder,$cid) {
  if (($datas = sys_cache_get($cid))) return $datas;
  if (!$pop3 = self::_connect($mfolder) or !$pop3) return 0;
  $datas = array();
  $datas[0] = array();
  $datas[1] = $pop3->getListing();
  foreach ($datas[1] as $mail) {
    $datas[0][] = $pop3->getParsedHeaders($mail["msg_id"],"");
  }
  sys_cache_set($cid,$datas,POP3_LIST_CACHE);
  return $datas;
}

private static function _is_attachment($item) {
  if ($item["disposition"]=="attachment") return true;
  // TODO2 add limit for HTML mails?
  // if ($item["size"] > 16384) return true;
  if (strpos($item["contenttype"],"text/")===false and strpos($item["contenttype"],"multipart/")===false and
      $item["contenttype"]!="message/rfc822" and $item["contenttype"]!="invalid") {
	return true;
  }
  return false;
}

private static function _parse_structure($structure,$id,$uid,$level=0) {
  $result = array();
  $result["contenttype"] = strtolower($structure->ctype_primary."/".$structure->ctype_secondary);
  $result["disposition"] = "inline";
  if (!empty($structure->headers["content-disposition"])) {
    $result["disposition"] = $structure->headers["content-disposition"];
	if (($pos = strpos($result["disposition"],";"))) $result["disposition"] = substr($result["disposition"],0,$pos);
  }
  $result["charset"] = "";
  if (isset($structure->ctype_parameters["charset"])) {
    $result["charset"] = strtolower($structure->ctype_parameters["charset"]);
  }
  $result["name"] = str_replace(".","_",$id)."_".$structure->ctype_primary.".".str_replace("plain","txt",$structure->ctype_secondary);
  
  if (!empty($structure->ctype_parameters["name"])) $result["name"] = modify::decode_subject($structure->ctype_parameters["name"]);
  if (!empty($structure->d_parameters["filename"])) $result["name"] = modify::decode_subject($structure->d_parameters["filename"]);
  if (!empty($structure->headers["content-id"])) $result["cid"] = trim($structure->headers["content-id"],"<>");
  $result["id"] = $id;
  $result["body"] = "";
  $result["size"] = 0;
  if (isset($structure->body)) {
    $result["size"] = strlen($structure->body);
    if (!self::_is_attachment($result) and $result["size"]>0 and $structure->ctype_primary=="text") {
      $result["body"] = &$structure->body;
	}
	if ($result["contenttype"]=="text/calendar") {
	  $result["disposition"] = "attachment";
	  if (!strpos($result["name"],".ics")) $result["name"] = "appointment.ics";
	}
    if (self::_is_attachment($result)) {
	  $result["disposition"] = "attachment";
      $local = sys_cache_get_file("pop3", $uid, "--".$result["name"], true);
      if (!file_exists($local)) {
		file_put_contents($local, $structure->body, LOCK_EX);
  } } }
  $result["header"] = array();
  if (isset($structure->headers)) {
    if (isset($structure->headers["subject"])) $result["header"] = $structure->headers;
  }
  
  $results = array($result);
  if (isset($structure->parts)) {
    foreach ($structure->parts as $key=>$part) {
	  $results = array_merge($results,self::_parse_structure($part,$id.".".($key+1),$uid,$level+1));
	}
  }
  if (count($results)>2) {
    if ($results[1]["contenttype"]=="text/plain" and $results[2]["contenttype"]=="text/html") {
	  // hide text/plain if text/plain is already present
	  $results[1]["contenttype"] = "invalid";
    }
  }
  return $results;
}

private static function _drawheader($headers) {
  $content = "Subject: ".modify::decode_subject($headers["subject"])."\n";
  $content .= "Date: ".$headers["date"]."\n";
  $content .= "From: ".modify::decode_subject($headers["from"])."\n";
  $content .= "To: ".modify::decode_subject($headers["to"])."\n";
  return $content;
}
}