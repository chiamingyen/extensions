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

class lib_gdocs extends lib_default {

static function get_dirs($path, $parent, $recursive) {
  $tree = array();
  self::_get_dirs($path, 1, 0, $parent, $recursive, $tree);
  return $tree;
}

private static function _fix_namespace($content, $tags) {
  $content = str_replace("<feed xmlns=", "<feed ns=", $content);
  return preg_replace("!(</?|\s)(".implode("|", $tags)."):!", "\\1\\2_", $content);
}

static function folder_info($path, $mfolder) {
  $url = "https://docs.google.com/feeds/metadata/default";
  $http_response_header = array();
  $response = file_get_contents($url, false, self::_get_context($mfolder));
  try {
	$xml = new SimpleXMLElement(self::_fix_namespace($response, array("docs", "gd")));
  }
  catch (Exception $e) {
	exit("Error ".implode("\n", $e->getMessage())."\n".$response." ".$http_response_header[0]);	
  }
  $result = array();
  $result["quota"]["quota"] = (int)$xml->gd_quotaBytesTotal;
  $result["quota"]["remain"] = $xml->gd_quotaBytesTotal-$xml->gd_quotaBytesUsed;
  if ($path==sys_remove_trans("docs.google.com/Trash/")) {
	$result["fsizecount"] = (int)$xml->docs_quotaBytesUsedInTrash;
  }
  return $result;
}

static function count($path,$where,$vars,$mfolder) {
  return count(self::_select_xml($path, $mfolder));
}

private static function _get_docs_path($path, $mfolder) {
  $path = explode("/", $path);
  $boxes = self::_get_boxes($mfolder);
  
  array_shift($path);
  if ($path[0]==sys_remove_trans("Trash")) return "-/trashed";
  if ($path[0]==sys_remove_trans("Shared")) return "-/-mine";
  
  $current = "folder%3Aroot";
  foreach ($path as $elem) {
	if ($elem=="" or !isset($boxes[$current])) continue;
	$key = array_search($elem, $boxes[$current]);
	if ($key) $current = $key;
  }
  return $current;
}

private static function _select_xml($path, $mfolder) {
  $creds = sys_credentials($mfolder);
  $cid = "gdocs_xml_".md5(serialize($creds).$path);
  if (($entries = sys_cache_get($cid))) return new SimpleXMLElement($entries);

  $url = "https://docs.google.com/feeds/default/private/full/".
	self::_get_docs_path($path, $mfolder)."/contents?showfolders=false";
  $limit = 0;
  while ($url!="" and $limit<10) {
	$http_response_header = array();
	$content = @file_get_contents($url, false, self::_get_context($mfolder));
	try {
	  if (!strpos($http_response_header[0], "200")) throw new Exception($http_response_header[0]);
	  $xml = new SimpleXMLElement(self::_fix_namespace($content, array("docs", "gd")));
	  foreach ($xml->entry as $entry) $entries .= $entry->asXML();
	  $url = (string)@array_shift($xml->xpath("/feed/link[@rel='next']/@href"));
	} catch (Exception $e) {
	  sys_warning("Error [select_xml] ".$e->getMessage());
	  return array();
	}
	$limit++;
  }
  sys_cache_set($cid, "<e>".$entries."</e>", GDOCS_LIST_CACHE);
  return new SimpleXMLElement("<e>".$entries."</e>");
}

static function select($path,$fields,$where,$order,$limit,$vars,$mfolder) {
  if ($fields==array("*")) $fields = array("id", "folder");
  $rows = array();
  $entries = self::_select_xml($path, $mfolder);
  foreach ($entries as $entry) {
	$ext = modify::getfileext($entry->title);
	$row = array();
	foreach ($fields as $field) {
	  switch ($field) {
	    case "filedata":
	    case "id": $row[$field] = basename($entry->id); break;
	    case "folder": $row[$field] = $path; break;
	    case "filedata_show":
	    case "filename":
		case "searchcontent": $row[$field] = (string)$entry->title; break;
		case "fileext": $row[$field] = $ext; break;
		case "created": $row[$field] = strtotime($entry->published); break;
		case "lastmodified": $row[$field] = strtotime($entry->updated); break;
		case "lastmodifiedby": $row[$field] = (string)$entry->author->name; break;
		case "filesize": $row[$field] = (int)$entry->gd_quotaBytesUsed; break;
		default: $row[$field] = ""; break;
	  }
	}
	$row["_lastmodified"] = strtotime($entry->updated);
	$row["_url"] = (string)$entry->content["src"];
	$row["_filename"] = (string)$entry->title;

	$meta = sys_build_meta($entry->docs_description,array());
	if (empty($meta)) $meta["description"] = (string)$entry->docs_description;
    $row = array_merge($row, $meta);
	
	if (sys_select_where($row,$where,$vars)) $rows[] = $row;
  }
  
  $rows = sys_select($rows,$order,$limit,$fields);
  if (count($rows)>0 and in_array("filedata",$fields)) {
	foreach ($rows as $key=>$row) {
	  $filename = sys_cache_get_file("gdocs", $row["id"].$row["_lastmodified"], "--".modify::basename($row["_filename"]), true);
	  if (!file_exists($filename) and (!isset($row["filesize"]) or $row["filesize"]<GDOCS_PREVIEW_LIMIT)) {
		$fout = fopen($filename, "wb");
		$fin = fopen($row["_url"], "rb", false, self::_get_context($mfolder));
		if (is_resource($fin) and is_resource($fout)) {
		  while (!feof($fin)) fwrite($fout, fread($fin, 8192));
		  fclose($fin);
		  fclose($fout);
		}
	  }
	  $rows[$key]["filedata"] = $filename;
	}
  }
  return $rows;
}

static function delete($path,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "error";

  $url = "https://docs.google.com/feeds/default/private/full/".$vars["id"];
  if (basename($path)==sys_remove_trans("Trash")) $url .= "?delete=true";
  $context = self::_get_context_action($mfolder, "DELETE");

  $http_response_header = array();
  $response = file_get_contents($url, false, $context);
  sys_cache_remove("gdocs_xml_".md5(serialize(sys_credentials($mfolder)).$path));
  
  if (!strpos($http_response_header[0], "200")) {
	exit("Error ".implode("\n", $http_response_header)."\n".$vars["id"]."\n".$response);
  }
  return "";
}

static function insert($path,$data,$mfolder) {
  $source = $data["filedata"];
  if (!is_dir($source) and file_exists($source)) {

	$url = "https://docs.google.com/feeds/upload/create-session/default/private/full/".
	  self::_get_docs_path($path, $mfolder)."/contents?convert=false";

	$drop = array("filedata", "folder", "created", "lastmodified", "handler", "mfolder", "dsize", "id");
	$meta = sys_build_meta_str($data, array_diff(array_keys($data), $drop));

	$content = "<?xml version='1.0' encoding='UTF-8'?>".
	  "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:docs='http://schemas.google.com/docs/2007'>".
	  "<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/docs/2007#document'/>".
	  "<title>".modify::htmlquote(modify::basename($source))."</title>".
	  "<docs:description>".modify::htmlquote($meta)."</docs:description></entry>";

	$header = "X-Upload-Content-Type: application/octet-stream\r\n";
	$context = self::_get_context_action($mfolder, "POST", $content, $header);
	$http_response_header = array();
	$response = file_get_contents($url, false, $context);
	
	preg_match("/Location: (.+)/m", implode("\n", $http_response_header), $match);

	if (!strpos($http_response_header[0], "200") or empty($match[1])) {
	  return "Error [insert] ".implode("\n", $http_response_header)."\n".$response;
	}
	$header = "POST ".$match[1]." HTTP/1.0\r\n";
	$header .= "Host: docs.google.com\r\n";
	$header .= "Content-Length: ".filesize($source)."\r\n\r\n";
	
	$errorNumber = 0;
	$errorString = "";
	$fp = fsockopen("ssl://docs.google.com", "443", $errorNumber, $errorString, 5);
	$fin = fopen($source, "rb");
	if (is_resource($fp) and is_resource($fin)) {
	  fwrite($fp, $header);
	  while (!feof($fin)) fwrite($fp, fread($fin, 8192));
	  $resp = "";
	  while (!feof($fp)) $resp .= fread($fp, 8192);
	  fclose($fp);
	  fclose($fin);
	  if (!sys_strbegins($resp, "HTTP/1.0 201")) return "Error [insert2] ".$resp;
	} else {
	  return "Error [insert3] ".$errorString." ".$errorNumber;
	}
	sys_cache_remove("gdocs_xml_".md5(serialize(sys_credentials($mfolder)).$path));
  }
  return "";
}

static function _move_file($file, $source, $target, $mfolder) {
  $base = "https://docs.google.com/feeds/default/private/full/";
  $source = substr($source,strpos($source,"/")+1);
	
  $url = $base.self::_get_docs_path($source, $mfolder)."/contents/".$file;
  $context = self::_get_context_action($mfolder, "DELETE");

  $http_response_header = array();
  $response = file_get_contents($url, false, $context);
  sys_cache_remove("gdocs_xml_".md5(serialize(sys_credentials($mfolder)).$source));
  
  if (!strpos($http_response_header[0], "200")) {
	return "Error [update] ".implode("\n", $http_response_header)."\n".$file."\n".$response;
  }
  $content = "<?xml version='1.0' encoding='UTF-8'?>".
	"<entry xmlns='http://www.w3.org/2005/Atom'>".
	"<id>".$base.$file."</id></entry>";
	
  $url = $base.self::_get_docs_path($target, $mfolder)."/contents";
  $context = self::_get_context_action($mfolder, "POST", $content);

  $http_response_header = array();
  $response = file_get_contents($url, false, $context);
  sys_cache_remove("gdocs_xml_".md5(serialize(sys_credentials($mfolder)).$target));
  
  if (!strpos($http_response_header[0], "201")) {
	return "Error [update2] ".implode("\n", $http_response_header)."\n".$file."\n".$response;
  }
  return "";
}

static function update($path,$data,$where,$vars,$mfolder) {
  if (empty($vars["id"])) return "";
  if (!empty($data["filedata"])) $source = $data["filedata"]; else $source = $vars["id"];

  if (!empty($vars["folder_source"])) {
	return self::_move_file($vars["id"], $vars["folder_source"], $path, $mfolder);
  }
  
  $drop = array("filedata", "folder", "lastmodified", "handler", "mfolder", "dsize");
  $meta = sys_build_meta_str($data, array_diff(array_keys($data), $drop));

  $content = "<?xml version='1.0' encoding='UTF-8'?>".
	"<entry xmlns='http://www.w3.org/2005/Atom' xmlns:docs='http://schemas.google.com/docs/2007'>".
	"<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/docs/2007#document'/>".
	"<title>".modify::htmlquote(modify::basename($source))."</title>".
	"<docs:description>".modify::htmlquote($meta)."</docs:description></entry>";

  if (file_exists($source) and sys_strbegins($source, SIMPLE_CACHE."/upload/")) {
	$url = "https://docs.google.com/feeds/upload/create-session/default/private/full/".
	  $vars["id"]."?convert=false";

	$header = "X-Upload-Content-Type: application/octet-stream\r\n";
	$context = self::_get_context_action($mfolder, "PUT", $content, $header);
	$http_response_header = array();
	$response = file_get_contents($url, false, $context);
	
	preg_match("/Location: (.+)/m", implode("\n", $http_response_header), $match);

	if (!strpos($http_response_header[0], "200") or empty($match[1])) {
	  return "Error [update] ".implode("\n", $http_response_header)."\n".$response;
	}

	$header = "PUT ".$match[1]." HTTP/1.0\r\n";
	$header .= "Host: docs.google.com\r\n";
	$header .= "Content-Length: ".filesize($source)."\r\n\r\n";
	
	$errorNumber = 0;
	$errorString = "";
	$fp = fsockopen("ssl://docs.google.com", "443", $errorNumber, $errorString, 5);
	$fin = fopen($source, "rb");
	if (is_resource($fp) and is_resource($fin)) {
	  fwrite($fp, $header);
	  while (!feof($fin)) fwrite($fp, fread($fin, 8192));
	  $resp = "";
	  while (!feof($fp)) $resp .= fread($fp, 8192);
	  fclose($fp);
	  fclose($fin);
	  if (!sys_strbegins($resp, "HTTP/1.0 200")) return "Error [update2] ".$resp;
	} else {
	  return "Error [update3] ".$errorString." ".$errorNumber;
	}

  } else {
	$url = "https://docs.google.com/feeds/default/private/full/".$vars["id"]."?convert=false";

	$context = self::_get_context_action($mfolder, "PUT", $content);
	$http_response_header = array();
	$response = file_get_contents($url, false, $context);
  
	if (!strpos($http_response_header[0], "200")) {
	  return "Error [update4] ".$http_response_header."\n".$response;
	}
  }
  sys_cache_remove("gdocs_xml_".md5(serialize(sys_credentials($mfolder)).$path));
  return "";
}

static function create_folder($title,$parent,$mfolder) {
  $url = "https://docs.google.com/feeds/default/private/full/".self::_get_docs_path($parent, $mfolder)."/contents";
  $content = "<?xml version='1.0' encoding='UTF-8'?>".
	"<entry xmlns='http://www.w3.org/2005/Atom'>".
	"<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/docs/2007#folder'/>".
	"<title>".modify::htmlquote($title)."</title></entry>";

  $context = self::_get_context_action($mfolder, "POST", $content);
  $http_response_header = array();
  file_get_contents($url, false, $context);

  sys_cache_remove("gdocs_boxes_".md5(serialize(sys_credentials($mfolder))));
  if (strpos($http_response_header[0], "201")) {
	return "ok";
  } else {
	exit("Error ".implode("\n", $http_response_header)." ".$parent);
  }
  return "";
}

static function rename_folder($title,$path,$mfolder) {
  $url = "https://docs.google.com/feeds/default/private/full/".self::_get_docs_path($path, $mfolder);
  $content = "<?xml version='1.0' encoding='UTF-8'?>".
	"<entry xmlns='http://www.w3.org/2005/Atom'>".
	"<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/docs/2007#folder'/>".
	"<title>".modify::htmlquote($title)."</title></entry>";

  $context = self::_get_context_action($mfolder, "PUT", $content);
  $http_response_header = array();
  file_get_contents($url, false, $context);

  sys_cache_remove("gdocs_boxes_".md5(serialize(sys_credentials($mfolder))));
  if (!strpos($http_response_header[0], "200")) {
	exit("Error ".implode("\n", $http_response_header)." ".$path);
  }
  return "ok";
}

static function delete_folder($path,$mfolder) {
  $url = "https://docs.google.com/feeds/default/private/full/".self::_get_docs_path($path, $mfolder);
  $http_response_header = array();
  file_get_contents($url, false, self::_get_context_action($mfolder, "DELETE"));

  sys_cache_remove("gdocs_boxes_".md5(serialize(sys_credentials($mfolder))));
  if (!strpos($http_response_header[0], "200")) {
	exit("Error ".implode("\n", $http_response_header)." ".$path);
  }
  return "ok";
}

private static function _get_boxes($mfolder) {
  $cid = "gdocs_boxes_".md5(serialize(sys_credentials($mfolder)));
  if (($boxes = sys_cache_get($cid))) return $boxes;
  $boxes = array();
  $limit = 0;
  $url = "https://docs.google.com/feeds/default/private/full/-/folder";
  while ($url!="" and $limit<10) {
	$content = @file_get_contents($url, false, self::_get_context($mfolder));
	try {
	  $xml = new SimpleXMLElement(str_replace("<feed xmlns=", "<feed ns=", $content));
	  $url = (string)@array_shift($xml->xpath("/feed/link[@rel='next']/@href"));
	}
	catch (Exception $unused) { return array(); }
	foreach ($xml->entry as $entry) {
	  $parent = "folder%3Aroot";
	  if (strpos($entry->link[0]["rel"], "#parent")) {
		$parent = basename($entry->link[0]["href"]);
	  }
	  $id = basename($entry->id);
	  $boxes[$parent][$id] = str_replace("@", "", $entry->title);
	}
	$limit++;
  }
  foreach (array_keys($boxes) as $key) natcasesort($boxes[$key]);
  $boxes["folder%3Aroot"]["trash"] = sys_remove_trans("Trash");
  $boxes["folder%3Aroot"]["shared"] = sys_remove_trans("Shared");
  sys_cache_set($cid, $boxes, GDOCS_CACHE);
  return $boxes;
}

private static function _get_dirs($path, $left, $level, $parent, $recursive, &$tree) {
  $right = $left+1;
  if ($recursive and sys_is_folderstate_open($path,"gdocs",$parent)) {
	// TODO optimize
	$docs_path = self::_get_docs_path($path, $parent);
	$docs_boxes = self::_get_boxes($parent);

	if (!empty($docs_boxes[$docs_path])) {
	  foreach ($docs_boxes[$docs_path] as $id) {
		$right = self::_get_dirs($path.$id."/", $right, $level+1, $parent, true, $tree);
	  }
	}
  } else $right = $right+2;

  $title = basename($path);
  $icon = "";
  if ($level==0) $icon = "sys_nodb_gdocs.png";
  $tree[$left] = array("id"=>$path,"lft"=>$left,"rgt"=>$right,"flevel"=>$level,"ftitle"=>$title,"ftype"=>"sys_nodb_gdocs","icon"=>$icon);
  return $right+1;
}

private static function _get_context($mfolder) {
  $opts = array("method"=>"GET", "header"=>self::_get_auth($mfolder), "timeout"=>5);
  return stream_context_create(array("http"=>$opts));
}

private static function _get_context_action($mfolder, $method, $content="", $header="") {
  $header = self::_get_auth($mfolder)."Content-Type: application/atom+xml\r\n".$header;
  if ($method=="DELETE" or $method=="PUT") $header = "If-Match: *\r\n".$header;
  $opts = array("method"=>$method, "header"=>$header, "content"=>$content, "timeout"=>5, "max_redirects"=>"0", "ignore_errors"=>"1");
  return stream_context_create(array("http"=>$opts));
}

private static function _get_auth($mfolder, $match=true) {
  $cid = "gdocs_".$mfolder;
  static $conn = array();
  if (($auth = sys_cache_get($cid))) return $auth;

  $creds = sys_credentials($mfolder);
  $url_auth = "https://www.google.com/accounts/ClientLogin?Email=".urlencode($creds["username"])."&Passwd=".urlencode($creds["password"]).
	"&accountType=HOSTED_OR_GOOGLE&source=SimpleGroupware&service=writely";
  $http_response_header = array();
  $response = @file_get_contents($url_auth);
  preg_match("/^Auth=(.+)/m", $response, $match);
	
  $auth = "";
  if (!empty($match[1])) {
	$auth = "GData-Version: 3.0\r\nAuthorization: GoogleLogin auth=".trim($match[1])."\r\n";
	sys_cache_set($cid, $auth, GDOCS_CACHE);
  } else if (!isset($conn[$cid])) {
	sys_warning(sprintf("Connection error: %s [%s]",$http_response_header[0], "Google Docs"));
  }
  $conn[$cid] = true;
  return $auth;
}
}