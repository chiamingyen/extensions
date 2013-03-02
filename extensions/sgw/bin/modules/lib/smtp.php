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

if (!class_exists("Mail_mimePart",false)) require("lib/mail/mimePart.php");

class lib_smtp extends lib_default {

static function insert($path,$data,$mfolder,$sendtofrom=true,$use_mail_function=false,$return_headers=false) {
  @set_time_limit(300); // 5min.
  $from = "";
  $creds = sys_credentials($mfolder);
  if ($creds["server"]=="" and !$use_mail_function) return "Error: no credentials";
  if ($creds["options"]!="") {
    foreach (explode("|",$creds["options"]) as $option) {
	  if (strpos($option,"@")) $from = $option; else $data["name"] = $option;
    }
  }
  if (!$creds["port"]) $creds["port"] = 25;
  if ($creds["ssl"] and !extension_loaded("openssl")) return sprintf("%s is not compiled / loaded into PHP.","OpenSSL");
  if (empty($data["message"])) $data["message"] = "";
  
  $ctype = "text/plain";
  $message = $data["message"]."\n\n";
  if (SMTP_FOOTER) $message .= "--\n".SMTP_FOOTER;
  if (!empty($data["message_html"])) {
	$ctype = "text/html";
	$message = $data["message_html"]."<br><br>\n";
	if (SMTP_FOOTER) $message .= "--<br>".nl2br(modify::htmlquote(SMTP_FOOTER));
  }

  $email = array();
  if (!empty($data["attachment"])) {
    $email = new Mail_mimePart("This is a multi-part message in MIME format.", array("content_type"=>"multipart/mixed"));
    $email->addSubPart($message, array("content_type"=>$ctype,"charset"=>"UTF-8","encoding"=>"7bit"));
    $attachments = explode("|",$data["attachment"]);
	foreach ($attachments as $attachment) {
	  if (file_exists($attachment) and filesize($attachment)>0) {
	    $mime = "application/octet-stream";
		$encoding = "base64";
	  	switch (modify::getfileext($attachment)) {
		  case "gif": $mime = "image/gif"; break;
		  case "jpeg":
		  case "jpg": $mime = "image/jpeg"; break;
		  case "png": $mime = "image/png"; break;
		  case "txt": $mime = "text/plain"; break;
		  case "html":
		  case "htm": $mime = "text/html"; break;
		  case "ics": $mime = "text/calendar"; break; // $encoding = "7bit"; 
		}
	    $email->addSubPart(file_get_contents($attachment), array(
		  "content_type"=>$mime."; name=\"".modify::basename($attachment)."\"", "encoding"=>$encoding,
		  "disposition"=>"attachment", "dfilename"=>modify::basename($attachment),
	    ));
	  }
	}
	$email = $email->encode();
  }

  if ($from=="") {
    if (!empty($data["efrom"])) {
	  $from = $data["efrom"];
	} else if ($creds["username"]!="") {
      $from = $creds["username"];
      if (!strpos($from,"@")) $from .= "@".$creds["server"];
	} else {
      $from = db_select_value("simple_sys_users","email",array("username=@username@","length(email)!=0"),array("username"=>$_SESSION["username"]));
	  if (empty($from)) $from = "unknown@invalid.local";
  } }

  if (!isset($data["bcc"])) $data["bcc"] = "";
  if (!isset($data["cc"])) $data["cc"] = "";
  
  $rcpt_str = $data["eto"].",".$data["cc"].",".$data["bcc"];
  if ($sendtofrom) $rcpt_str .= ",".$from;
  $rcpt = self::_build_rcpt($rcpt_str);

  $matches = array();
  preg_match_all("/bcc:[^, ]+\s*,?\s*/i",$data["eto"],$matches);
  if (!empty($matches[0])) {
	$data["eto"] = trim(str_replace($matches[0],"",$data["eto"]),", ");
  }
  
  $matches = array();
  preg_match_all("/cc:([^, ]+)\s*,?\s*/i",$data["eto"],$matches);
  if (!empty($matches[0])) {
    $data["cc"] = trim($data["cc"].",".implode(",",$matches[1]),",");
	$data["eto"] = trim(str_replace($matches[0],"",$data["eto"]),", ");
  }
  
  $headers = array(
  	"Subject: ".$data["subject"], "From: ".(!empty($data["name"])?$data["name"]:"")." <".$from.">",
    "To: ".$data["eto"], "Date: ".sys_date("r"),
    "Mime-Version: 1.0", "X-Mailer: Simple Groupware ".CORE_VERSION_STRING,
    "Content-Type: ".(!empty($data["attachment"])?$email["headers"]["Content-Type"]:$ctype."; charset=UTF-8")
  );
  if (!empty($data["cc"])) $headers[] = "Cc: ".$data["cc"];
  if (!empty($data["receipt"])) $headers[] = "Disposition-Notification-To: ".$from;
  $headers = self::_encodeHeaders($headers);

  if ($use_mail_function) {
    array_shift($headers);
    if (!mail(implode(", ",$rcpt),$data["subject"],($data["attachment"]?$email["body"]:$message),implode("\r\n",$headers))) {
	  return sprintf("Smtp-error %s: %s","mail()","error");
    }
  } else {
    $smtp = new Net_SMTP($creds["ssl"]?(strtolower($creds["ssl"])."://".$creds["server"]):$creds["server"],$creds["port"]);
    if (PEAR::isError($e = $smtp->connect(10))) {
	  return sprintf("Smtp-error %s: %s","conn",$e->getMessage());
    }
    if ($creds["username"]!="" and !empty($smtp->_esmtp['AUTH']) and PEAR::isError($e = $smtp->auth($creds["username"], $creds["password"]))) {
	  return sprintf("Smtp-error %s: %s","auth",$e->getMessage());
    }
    if (PEAR::isError($e = $smtp->mailFrom($from))) {
	  return sprintf("Smtp-error %s: %s","from",$e->getMessage()." [".$from."]");
    }
    foreach ($rcpt as $to) {
      if (PEAR::isError($e = $smtp->rcptTo($to))) {
	    return sprintf("Smtp-error %s: %s","to",$e->getMessage()." [".$to."]");
      }
    }
    if (PEAR::isError($e = $smtp->data(implode("\r\n",$headers)."\r\n\r\n".(!empty($data["attachment"])?$email["body"]:$message)))) {
	  return sprintf("Smtp-error %s: %s","data",$e->getMessage());
    }
    $smtp->disconnect();
  }
  
  if ($sendtofrom and !empty($data["attachment"])) {
    $attachments = explode("|",$data["attachment"]);
	foreach ($attachments as $attachment) @unlink($attachment);
  }
  if ($return_headers) return $headers;
  return "";
}

private static function _encodeHeaders($input) {
  foreach ($input as $hdr_name => $hdr_value) {
    $hdr_value = str_replace(array("\r","\n","\t",chr(0)),"",$hdr_value);
	$hdr_value = preg_replace("!(<CR>|<LF>|%0A|%0D|0x0A|0x0D)!i","",$hdr_value);
    preg_match_all("/(\w*[\x80-\xFF]+\w*)/", $hdr_value, $matches);
    foreach ($matches[1] as $value) {
      $replacement = preg_replace("/([\x80-\xFF])/e",'"=".strtoupper(dechex(ord("\1")))',$value);
      $hdr_value = str_replace($value,"=?UTF-8?Q?".$replacement."?=",$hdr_value);
    }
    $input[$hdr_name] = trim($hdr_value);
  }
  return $input;
}

private static function _build_rcpt($rcpt) {
  $result = array();
  $rcpt = str_ireplace(array("mailto:","bcc:","cc:","(",")"),"",$rcpt);
  $rcpt = preg_replace('/("[^@"]+")/',"",$rcpt);
  $rcpt = self::_group_lookup(explode(",",$rcpt));
  foreach ($rcpt as $val) {
	if (preg_match("/([\S]*?@[\S]*)/",$val,$match)) {
	  $result[] = str_replace(array("<",">"),"",$match[1]);
	}
  }
  return array_unique($result);
}

private static function _group_lookup($rcpt_arr) {
  $rcpt_to = array();
  foreach ($rcpt_arr as $to) {
    $to = trim(str_replace(array("\r","\n","\t"),"",$to));
    if ($to=="") continue;
	if ($to[0]=="@") {
	  $to = substr($to,1);
	  $members = db_select_value("simple_sys_groups","members",array("groupname=@name@","activated=1"),array("name"=>$to));
	  if (!empty($members)) {
	    $member_ids = explode("|",trim($members,"|"));
		foreach ($member_ids as $id) {
	      $email = db_select_value("simple_sys_users","email",array("username=@username@","length(email)!=0","activated=1"),array("username"=>$id));
		  if (!empty($email)) $rcpt_to[$email] = "";
		}
	  } else {
  	    $members = db_select_value("simple_contactgroups","members","groupname=@name@",array("name"=>$to));
	    if (!empty($members)) {
	      $member_ids = explode("|",trim($members,"|"));
		  foreach ($member_ids as $id) {
		    $email = db_select_value("simple_contacts","email",array("contactid=@contactid@","length(email)!=0"),array("contactid"=>$id));
		    if (!empty($email)) $rcpt_to[$email] = "";
	  } } }
	} else {
	  $rcpt_to[$to] = "";
	}
  }
  return array_keys($rcpt_to);
}

}