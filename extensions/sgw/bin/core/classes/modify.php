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

class modify {

static function match($data, $condition, $fields) {
  $match = true;
  $ops = array("eq","neq","lt","gt","like","nlike","starts","oneof");

  foreach (explode("||",$condition) as $filter) {
	$filter = explode("|",$filter);
	if (count($filter)!=3 or !isset($data[$filter[0]]) or !in_array($filter[1], $ops)) continue;
	$f_value = $filter[2];

	if (in_array($fields[$filter[0]]["SIMPLE_TYPE"],array("date","dateselect","time","datetime"))) {
	  $f_value = self::datetime_to_int($f_value);
	}
	$f_value = sgsml::scalarize((array)$f_value, $fields[$filter[0]]);
	$value = sgsml::scalarize($data[$filter[0]]["data"], $fields[$filter[0]]);

	if (sys_contains($f_value, "@")) {
	  $key = substr(trim($f_value, "|"), 1, -1);
	  if (!empty($_SESSION[$key])) $f_value = str_replace("@".$key."@", $_SESSION[$key], $f_value);
	}
	if ($filter[1]=="oneof") $f_value = explode(",", $f_value);

	switch ($filter[1]) {
	  case "neq": $match = ($value != $f_value); break;
	  case "oneof": $match = in_array($value, $f_value); break;
	  case "lt": $match = $value < $f_value; break;
	  case "gt": $match = $value > $f_value; break;
	  case "like": $match = sys_contains($value, $f_value); break;
	  case "nlike": $match = !sys_contains($value, $f_value); break;
	  case "starts": $match = sys_strbegins($value, $f_value); break;
	  default: $match = ($value == $f_value); break;
	}
	if (!$match) return false;
  }
  return $match;
}

static function recurrences($value, $params, $data=array()) {
  if (!empty($params[2])) $value = $data[$params[2]]["data"][0];
  if ($value=="" or $value[0]!="|") return $value;
  $begin = 0;
  if (!empty($params[1])) $begin = is_numeric($params[1])? $params[1] : strtotime($params[1]);

  $values = explode("|", trim($value, "|"));
  $result = array();
  foreach ($values as $key=>$val) {
    if ($val > $begin) $result[$key] = sys_date("m/d/Y", $val);
	if (!empty($params[0]) and count($result) >= $params[0]) {
	  $result[] = "...";
	  break;
	}
  }
  return implode(", ", $result);
}

static function get_email_address($value) {
  $value = str_replace(array("(",")","'","\""),"",$value);
  if (preg_match("|[^<\s]+@[^\s>]+|", $value, $match)) return $match[0];
  return $value;
}

static function dbvalue($value, $params, $vars) {
  $vars["value"] = $value;
  $result = db_select_value($params[0], $params[1], $params[2], $vars);
  if ($result=="") return $value; else return $result;
}

static function get_required_field($fields) {
  foreach ($fields as $field_name=>$field) {
	if (empty($field["REQUIRED"]) or $field["SIMPLE_TYPE"]=="files") continue;
	return $field_name;
  }
  return array_shift(array_keys($fields));
}

static function get_required_fields($fields) {
  $result = array();
  foreach ($fields as $field_name=>$field) {
	if (empty($field["REQUIRED"])) continue;
	if ($field["SIMPLE_TYPE"]=="files") return array(); // no quick add for files
    $result[$field_name] = $field["DISPLAYNAME"];
  }
  return $result;
}

static function get_form_token() {
  static $token = null;
  if ($token==null) {
	$token = md5(uniqid(rand(),true));
	$_SESSION["tokens"][$token] = 1;
	$_SESSION["tokens"] = array_slice($_SESSION["tokens"],-10);
  }
  return $token;
}

static function milestone($val,$args,$data) {
  if (!empty($data["milestone"]["data"][0])) return $val." ".self::htmlunquote("&diams;");
  return $val;
}

static function show_id($var) {
  return substr($var,0,-2);
}

static function folderstructure($var,$args,$data) {
  global $t;
  if (!empty($data["_id"]) and (!empty($t["filter"]) or !empty($t["sqlvars"]["item"]))) return self::getpath($data["_id"]);
  if (!isset($data["flevel"]) || !is_numeric($data["flevel"]["data"][0])) return $var;
  return str_repeat("|_ ", $data["flevel"]["data"][0]).$var;
}

static function numberformat($number,$params) {
  if ($number==0) return "";
  if (isset($params[0])) $decimals = $params[0]; else $decimals = 2;
  if (isset($params[1])) $separator = $params[1]; else $separator = ".";
  if (isset($params[2])) $thousands = $params[2]; else $thousands = "";
  return number_format($number,$decimals,$separator,$thousands);
}

static function pagename($str) {
  $arr = explode(".",$str);
  foreach ($arr as $key=>$str) $arr[$key] = ucfirst($str);
  if ($arr[0]=="Main") array_shift($arr);
  return implode(".",$arr);
}

static function contactid($var,$row) {
  if ($var!="") return $var;
  $contact_id = "";
  if (!empty($row["lastname"])) $contact_id .= substr($row["lastname"],0,3);
  if (!empty($row["firstname"])) $contact_id .= substr($row["firstname"][0],0,2);
  if (strlen($contact_id)<3) $contact_id .= str_repeat("_", 3-strlen($contact_id));
  $contact_id = strtoupper($contact_id);
  $i = 1;
  $id = $contact_id;
  while (validate::itemexists("simple_contacts",array("contactid"=>$id),1)) {
    $i++;
	$id = $contact_id.$i;
	if ($i>20) break;
  }
  return $id;
}

static function callfunc($func, $folder, $view) {
  list($class, $function, $params) = sys_find_callback("custom", $func);
  return call_user_func(array($class, $function),$folder,$view,$params);
}

static function stripslashes(&$val) {
  if (is_array($val)) array_walk($val,array("modify","stripslashes")); else $val = stripslashes($val);
}

static function dropglobals() {
  $valid = array("GLOBALS","_REQUEST", "_FILES","_SERVER","_COOKIE","_GET","_POST");
  foreach (array_keys($GLOBALS) as $key) if (!in_array($key,$valid)) unset($GLOBALS[$key]);
}

static function switch_items($data,$arr,$row=array()) {
  if (is_array($data)) { // rowfilter
    $row = $data;
	$data = "";
  }
  $default = $data;
  if (is_array($arr) and count($arr)>0) {
    foreach ($arr as $item) {
      $item = explode("=>",$item);
	  switch (count($item)) {
	    // last value by default
	    case 1: $default = $item[0]; break;
		// male=>white|blue; if field==male then white else blue
	  	case 2: if ($data==$item[0]) return $item[1];
		// gender=>male=>white|blue; if gender == male then white else blue
		case 3: if (is_array($row) and isset($row[$item[0]]["data"]) and in_array($item[1],$row[$item[0]]["data"])) return $item[2];
  } } }
  return $default;
}

static function isimportant($data,$arr) {
  if (isset($data[$arr[0]]["data"][0]) and $data[$arr[0]]["data"][0] == "2") {
    return $arr[1];
  }
  if (isset($data[$arr[0]]["data"][0]) and $data[$arr[0]]["data"][0] == "1") {
    return $arr[2];
  }
  return "";
}

static function isinpast($data,$arr) {
  if (!empty($data[$arr[0]]["data"][0]) and $data[$arr[0]]["data"][0] < NOW) {
    return $arr[1];
  }
  return "";
}

static function task_dependancy($task,$args,$row) {
  if (empty($row["dependancy"]["data"][0]) or empty($row["begin"]["data"]) or empty($row["ending"]["data"])) return $task;

  $item = db_select_first("simple_tasks",array("ending","begin"),"id=@id@","",array("id"=>(int)$task));
  if (!empty($item["ending"]) and !($item["ending"] <= $row["begin"]["data"][0] or $item["begin"] >= $row["ending"]["data"][0])) {
	return $task." [overlap]";
  }
  return $task;
}

static function buildbgcolor($data, $arr) {
  if (isset($data[$arr[0]])) {
    $value = $data[$arr[0]]["data"][0];
	if ($value!="") return "background-color:".$value.";";
  }
  return "";
}

static function buildseenstyle($data,$arr) {
  if (isset($data[$arr[0]])) {
    $value = $data[$arr[0]]["data"][0];
	if ($value=="" or $value=="0") return "font-weight:bold;";
  }
  return "";
}

static function implode($var) {
  return implode("|", $var);
}

static function explode($var) {
  if (is_array($var)) return $var;
  if ($var=="") return array();
  $var = explode("|",$var);
  $result = array();
  foreach ($var as $val) {
    $i = sys_contains($val,"_##_");
	$key = substr($val,0,$i-1);
	while (isset($result[$key])) $key .= " ";
	if ($i>0) $result[$key] = substr($val,$i+3);
	  else if ($val!="") $result[$val] = $val;
  }
  return $result;
}

static function getpath($folder) {
  return self::getpathfull($folder, true);
}

static function getpathfull($folder, $workspace=false, $spacer=" / ") {
  static $cache = array();
  if ($folder=="") return "";
  $cid = $folder.$workspace.$spacer;
  if (isset($cache[$cid])) return $cache[$cid];
  if (is_numeric($folder)) {
    $sel_folder = db_select_first("simple_sys_tree",array("id","rgt","lft","ftitle"),"id=@id@","",array("id"=>$folder));
	if (empty($sel_folder["id"])) return $folder;
  } else {
    $sel_folder = array("id"=>$folder, "ftitle"=>basename($folder));
  }
  $parents = db_get_parents($sel_folder);
  if (!is_array($parents) or count($parents)==0) {
    if (!is_numeric($folder)) return substr($folder,strpos($folder,"/")+1); else return $spacer.$sel_folder["ftitle"];
  }
  $result = "";
  if ($workspace) array_shift($parents);
  foreach ($parents as $parent) $result .= $spacer.$parent["ftitle"];
  $cache[$cid] = $result.$spacer.$sel_folder["ftitle"];
  return $cache[$cid];
}

static function searchindex($value) {
  $value = self::htmlunquote(strip_tags(str_replace("<br>"," ",$value)));
  $value = trim(str_replace(array(chr(255),"\r","\n")," ",$value));
  if ($value=="http://") $value="";
  return $value;
}

static function link($link,$row,$subitem=0,$urladdon="") {
  if (isset($row["folder"]["data"][0]) and $row["folder"]["data"][0]=="sys_tree") {
    $row["folder"] = $row["id"]["data"][0];
	$row["id"]["data"][0] = "";
  }
  $matches = "";
  $row["subitem"]["data"][0] = $subitem;
  if (!isset($row["username"])) $row["username"]["data"][0] = $_SESSION["username"];
  if (preg_match_all("|@(.*?)@|i",$link,$matches,PREG_SET_ORDER)) {
	foreach ($matches as $match) {
	  if (count($match)==2) {
		$req_key = $match[1];
		$replace = null;
		if (isset($row[$req_key]["data"][$subitem])) $replace = $row[$req_key]["data"][$subitem];
		  else if (is_array($row[$req_key]["data"])) $replace = $row[$req_key]["data"][0];
		  else if (isset($row[$req_key])) $replace = $row[$req_key];
		  else if (isset($row["_".$req_key])) $replace = $row["_".$req_key];
		  else if ($req_key=="token") $replace = self::get_form_token();

		if ($replace !== null) {
		  if (strpos($link,".php?") or strpos($link,"://")) $replace = urlencode(trim($replace)); else $replace = addslashes(trim($replace));
		  $link = str_replace("@".$req_key."@",$replace,$link);
	} } }
	if (!strpos($link,"norefer.php") and strpos($link,".php?") and $urladdon!="") $link .= "&".$urladdon;
  }
  if ($link=="ext/norefer.php?url=") $link = "";
  if (sys::$browser["is_mobile"] or sys::$browser["no_scrollbar"]) $link = str_replace("&iframe=1","",$link);
  return $link;
}

static function target($target) {
  if (in_array($target, array("pane", "pane2"))) {
	if (sys::$browser["is_mobile"] or sys::$browser["no_scrollbar"]) return "_top";
	if (!empty($_REQUEST["popup"]) or !empty($_REQUEST["preview"])) return "_top";
  }
  return $target;
}

static function date_translate($str) {
  $index = 1;
  $pattern = "";
  $replace = array();
  $preg = sys_remove_trans("m/d/Y");
  for ($i=0; $i<strlen($preg); $i++) {
    $token = $preg[$i];
	if ($token == "m") {
	  $pattern .= "(\d{1,2})";
	  $replace[0] = "\\".$index++;
	} else if ($token == "d") {
	  $pattern .= "(\d{1,2})";
	  $replace[1] = "\\".$index++;
	} else if ($token == "Y") {
	  $pattern .= "(\d{2,4})";
	  $replace[2] = "\\".$index++;
	} else if ($token == " ") {
	  $pattern .= " ?";
	} else {
      $pattern .= preg_quote($token);
	}
  }
  ksort($replace);
  return preg_replace("!".$pattern."(.*?)!",implode("/",$replace)."\\".$index,$str);
}

static function datetime_to_int($str) {
  if (empty($str)) return 0;
  if (is_array($str)) {
    foreach ($str as $key=>$item) {
	  if ($item=="") continue;
	  $item = strtotime(self::date_translate($item));
	  if ($item==0 or !is_numeric($item)) $item = NOW;
	  $str[$key] = $item;
	}
  } else {
	$str = strtotime(self::date_translate($str));
    if ($str==0 or !is_numeric($str)) $str = NOW;
  }
  return $str;
}

static function ical_datetime_to_int($val) {
  if (empty($val)) return 0;
  $val = str_replace(array("T","-"),"",$val);
  if (!preg_match ("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})Z?/", $val, $match)) return 0;
  if ($match[1]<1970) $match[1] = 1971;
  if (strpos($val,"Z")) { // Zulu time
    $val = gmmktime((int)$match[4], (int)$match[5], 0, (int)$match[2], (int)$match[3], (int)$match[1]);
  } else {
    $val = mktime((int)$match[4], (int)$match[5], 0, (int)$match[2], (int)$match[3], (int)$match[1]);
  }
  return $val;
}

static function nl2br($str, $norefer=true, $blockquote=false) {
  $length = strlen($str);
  if ($length>60) $str = preg_replace("|([^<>\s'\"/&]{60})|","\\1<wbr/>",$str);
  if ($length>200) $str = "<code>".$str." </code>";
  if ($length>20) {
    $from = array("!(https?:|ftp:|www|\S{2,50}@)\S{3,200}!i", "|\s_(\S{2,30})_|", "|\*(\S{2,30})\*|");
	if ($norefer) $link = "ext/norefer.php?url="; else $link = "";
	$to = array("<a target=\"_blank\" href=\"".$link."\\0\">\\0</a>", " <u>\\1</u>", " <b>\\1</b>");
	$str = preg_replace($from,$to,$str);
	if (strpos($str,"\n&gt;")) {
	  $rep = "<span><p><br><a href='#' onclick='showhide_inline(this.parentNode.parentNode.lastChild); return false;'>".
		"&gt; Show quoted text</a><br><div style='display:none;'>\\1</div></span>";

	  if ($blockquote) $rep = "<p><br><blockquote>\\1</blockquote>";
      $str = preg_replace("/\n&gt; ?/","<br>&gt; ",$str)."\n";
      $str = preg_replace("/(<br>&gt;.*?\n)/",$rep,$str);
	}
  }
  if ($length>4 and strpos($str,"[")!==false) {
    // Asset: [cms/id=123/name] [cms/id=123/name/view], [123] [123/view]
	// Folder: [/123] [/123/view]
	$view = "/?([^/\]]+)?";
	$replace = array("|\[([^/\]]+)/([^/\]]+)/([^/\]]+){$view}\]|","|\[(\d+){$view}\]|","|\[/(\d+){$view}\]|");
	$with = array("<a target='_top' href='index.php?view=\\4&find=\\1|\\2'>\\3</a>",
				  "<a target='_top' href='index.php?view=\\2&find=\\1'>#\\1</a>",
				  "<a target='_top' href='index.php?view=\\2&folder=\\1'>\\1</a>");
	$str = preg_replace($replace,$with,$str);
  }
  return str_replace(array("\n","\t","  "),array("<br>","&nbsp;&nbsp;&nbsp;&nbsp;","&nbsp; "),$str);
}

static function field($str) {
  if (is_array($str)) $str = implode($str);
  if (strlen($str)<5 or strpos($str,"[")===false) return $str;
  // Asset: [cms/id=123/name] [cms/id=123/name/view], [123] [123/view]
  // Folder: [/123] [/123/view]
  $view = "/?([^/\]]+)?";
  $replace = array("|\[([^/\]]+)/([^/\]]+)/([^/\]]+){$view}\]|","|\[(\d+){$view}\]|","|\[/(\d+){$view}\]|");
  $with = array("<a target='_top' href='index.php?view=\\4&find=\\1|\\2'>\\3</a>",
				"<a target='_top' href='index.php?view=\\2&find=\\1'>#\\1</a>",
				"<a target='_top' href='index.php?view=\\2&folder=\\1'>\\1</a>");
  return preg_replace($replace,$with,$str);
}

static function highlight_search($search) {
  $vals = str_replace(" ","|",preg_replace("/ +/"," ",$GLOBALS["t"]["search"]["query"]));
  if ($vals=="*") return $search;
  $preg = "/(".str_replace(array("/","*"),array("","[\\w]*"),$vals).")/i";
  $search = self::htmlquote($search);
  if (strlen($search)>500) {
    $pos = 0;
    if (preg_match($preg,$search,$match)) {
	  $pos = strpos($search,$match[0]);
	  if ($pos>25) $pos -= 25; else $pos = 0;
	}
	if ($pos > 25) {
	  $search = substr($search,0,25)." ... ".substr($search,$pos,470)." ...";
	} else $search = substr($search,0,500)." ...";
  }
  return preg_replace($preg,"<span style='background-color: #FFFF80;'>$0</span>",$search);
}

static function highlight_string($str) {
  if ($str=="") return "";
  $str = str_replace(array("  "),array("&nbsp; "),self::htmlquote($str));
  $str = preg_replace('/(&quot;.*?&quot;)/si',"<font color='#DD0000'>\\1</font>",$str);
  $result = "";
  foreach (explode("\n",$str) as $line) $result .= "<li>".$line."</li>";
  return "<htmlcode><code><font style='color:#0000DD;'><ol>".$result."</ol></font></code></htmlcode>";
}

static function htmlfield_noimages($text) {
  return self::htmlfield($text,false);
}

static function htmlfield($text,$allow_pics=true,$allow_iframe=false) {
  if (is_array($text)) {
    $allow_pics = $text[1];
    $text = $text[0];
  }
  $badtags = array("applet","area","base","body","button","embed","form","frame","frameset","head",
    "html","input","link","map","meta","object","script","select","style","textarea","title","!doctype");
  if (!$allow_iframe) $badtags[] = "iframe";
  $text = str_replace(chr(0),"",$text); // remove null-byte vulnerability
  $text = preg_replace(array("|<head[^>]*?>.*?</head>|si","|<script[^>]*?>.*?</script>|si","|<style[^>]*?>.*?</style>|si","|<!--.*?-->|si"),"",$text);

  $text = explode("<"," ".$text);
  $result = array_shift($text);
  $add_image_switch = false;

  foreach ($text as $v) {
    $v = explode(">",$v);
    $bad_tag = false;
	$tag_new = "";
    $tag_arr = explode(" ",trim(str_replace(array("\t","(")," ",$v[0])));
	foreach ($tag_arr as $key=>$item_str) {
	  $item = explode("=", $item_str, 2);
	  $item[0] = strtolower($item[0]);
	  if (strlen($item[0])!=0 and $item[0][0]=="/") $item[0] = substr($item[0],1);
	  if (preg_match("/^(\?|on|mce|background\$)/",$item[0])) continue;
	  if (!empty($item[1]) and stripos($item[1],"javascript:")) continue;
	  if (!$allow_pics and $item[0]=="src" and !sys_strbegins($item[1],"\"download.php")) {
		$item_str = "title".substr($item_str,3); // src="" => title=""
		$add_image_switch = true;
	  }
	  if ($key==0 and in_array($item[0],$badtags)) {
	    $bad_tag = true;
		break;
	  } else $tag_new .= " ".preg_replace("!(https?:|ftp:|www|mailto:\S+@)\S+!i", "ext/norefer.php?url=$0",$item_str);
	}
	if (!$bad_tag) $result .= "<".trim($tag_new).">";
	if (!empty($v[1])) $result .= $v[1];
  }

  if (strpos($result, "<blockquote>")) {
    $replace = array("<blockquote>", "</blockquote>");
	$with = array(
	  "<span><a href='#' onclick='showhide_inline(this.parentNode.lastChild); return false;'>&gt; Show quoted text</a><br><div style='display:none;'><blockquote>",
	  "</blockquote></div></span>",
	);
    $result = str_replace($replace, $with, $result);
  }
  if ($add_image_switch) return " <span><a href='#' onclick='display_images(); hide(this.parentNode); return false;'>&gt;&gt; Load external images</a><br/><br/></span>".$result;
  return $result;
}

static function striplinksforms($html) {
  $search = array ("!<input.*?>!si","!<select[^>]*?>.*?</select>!si","!<script[^>]*?>.*?</script>!si","!<form[^>]*?>.*?</form>!si","!<a[^>]*?>@</a>!si");
  $html = preg_replace($search, array("&nbsp;","","","&nbsp;",""), $html);
  $html = str_replace(array("> ","<noscript>","</noscript>","display:none"),array(">","","",""),$html);
  return $html;
}

static function threadsort($arr) {
  $arr2 = array();
  foreach ($arr as $key=>$value) {
    $arr[$key]["sorting"] = $key;
  }
  uasort($arr, "sys_threadsort_cmp");
  foreach ($arr as $value) {
    $arr2[$value["pid"]][] = $value;
  }
  $result = array();
  sys_threadsort($arr,0,$result,$arr,$arr2);
  $last_sort = 0;
  $elems = count($result);
  foreach ($result as $key=>$resultitem) {
    if ($resultitem["tlevel"]==0) $last_sort = $resultitem["sorting"]*$elems; else $last_sort++;
    $result[$key]["sorting"] = $last_sort;
  }
  uasort($result, "sys_threadsort_cmp2");
  return array_values($result);
}

static function truncate($value,$args) {
  if (strlen($value)>$args[0]) return substr($value,0,$args[0])." ..."; else return $value;
}

static function empty_str() {
  return "";
}

static function prepend($value,$args) {
  if (isset($args[0])) return str_replace("\\n", "\n", $args[0]).$value; else return $value;
}

static function append($value,$args) {
  if (isset($args[0])) return $value.str_replace("\\n", "\n", $args[0]); else return $value;
}

static function replace($unused,$args) {
  if (isset($args[0])) return str_replace("\\n", "\n", $args[0]); else return "";
}

static function blank() {
  return " ";
}

static function sha1($value) {
  if (str_replace("*","",$value)=="") return null;
  if (strlen($value)!=40) return sha1($value); else return $value;
}

static function hidepassword($value,$args) {
  return str_repeat("*",strlen($value));
}

static function storefloat($value) {
  return (float)$value;
}

static function storechecked($checked) {
  if (in_array($checked, array("1", "true", sys_remove_trans("{t}yes{t}")))) return 1;
  return 0;
}

static function storepercent($value) {
  if (!empty($value[0]) and strpos($value[0], "%")) $value[0] /= 100;
  return $value;
}

static function replacechecked($checked) {
  if ($checked=="") return "";
  if ($checked=="1") return "yes";
  return "no";
}

static function receipt($value) {
  if ($value=="yes") return " "; else return "";
}

static function _mydate($format,$date) {
  if (!is_numeric($date)) $date = strtotime($date);
  if ($date==0 or $date=="") return ""; else return sys_date($format,$date);
}

static function localdateformat($args,$format) {
  $format = str_replace(array("F","l","M"),array("#\F","#\l","#\M"),$format);
  $months = array("","January","February","March","April","May","June",
  				  "July","August","September","October","November","December");
  $short_months = array("","Jan","Feb","Mar","Apr","May","Jun",
  				  "Jul","Aug","Sep","Oct","Nov","Dec");
  $days = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
  return str_replace(array("#F","#l","#M"),array($months[ sys_date("n",$args) ],$days[ sys_date("w",$args) ],$short_months[ sys_date("n",$args) ]),self::dateformat($args,$format));
}

static function dateformat($args,$args2=null) {
  if (!is_array($args2) and $args2!="") return self::_mydate($args2,$args);
  if (is_array($args2)) {
    if (!empty($args2[0]) and !is_numeric($args2[0])) $args = strtotime($args2[0],$args);
	if ($args==0 or $args=="") return "";
	if (empty($args2[1])) $args2[1] = "m/d/Y";
	$date = @self::_mydate($args2[1],$args);
	if ($date=="" and $args!=0) return $args;
	return $date;
  }
  if (!is_array($args) or count($args)!=2) return "";
  if (!is_numeric($args[0])) $args[0] = strtotime($args[0]);
  return self::_mydate($args[1],$args[0]);
}

static function dateformat_request($args) {
  $field = array_shift($args);
  if (isset($_REQUEST[$field])) $args[0] = $_REQUEST[$field];
  return self::dateformat($args);
}

static function getusername($args) {
  return $_SESSION["username"];
}

static function getfolder($args) {
  return $_SESSION["folder"];
}

static function fillform($args) {
  if (!empty($_REQUEST[$args[0]])) {
    if (sys_strbegins($_REQUEST[$args[0]],"mailto:")) $_REQUEST[$args[0]] = substr($_REQUEST[$args[0]],7);
    return $_REQUEST[$args[0]];
  }
  if (!empty($args[1]) and $args[1]=="numeric") return "0"; else return "";
}

static function fillcontact($args,$data=array()) {
  if (!empty($_REQUEST["data"]) and !empty($args[0])) {
    $data = str_replace(array("(",")","'","\""),"",$_REQUEST["data"]);
	if ($args[0]=="email" and preg_match("|[^<\s]+@[^\s>]+|",$data,$match)) {
	  return $match[0];
	}
	if ($args[0]=="firstname") {
	  if (preg_match("|.+, (.+) .+@.+|",$data,$match)) return $match[1];
	  if (preg_match("|.+@.+ .+, (.+)|",$data,$match)) return $match[1];
	  if (preg_match("|(.+) .+? .+?@.+|",$data,$match)) return $match[1];
	  if (preg_match("|.+?@.+? (.+) .+|",$data,$match)) return $match[1];
	}
	if ($args[0]=="lastname") {
	  if (preg_match("|(.+), .+ .+@.+|",$data,$match)) return $match[1];
	  if (preg_match("|.+@.+ (.+), .+|",$data,$match)) return $match[1];

	  if (preg_match("|.+ (.+?) .+?@.+|",$data,$match)) return $match[1];
	  if (preg_match("|.+?@.+ .+ (.+)|",$data,$match)) return $match[1];
	}
	if ($args[0]=="contactid" and preg_match("|([a-z]{2}).*?[ -._@]([a-z]{2})|i",$data,$match)) {
	  return strtolower($match[2].$match[1]);
	}
  }
  return "";
}

static function embed_attachments($val,$args,$row) {
  return str_replace(array("@folder@", "@id@"),array($row["_folder"], $row["_id"]), $val);
}

static function shortemail($val) {
  $val = preg_replace("/\([^\)]+?\)/","\\1",$val); // a@b.c (de) => de
  $val = str_replace(array("(",")","\"","'",","),"",$val);
  $val2 = trim(strip_tags($val));
  if ($val2!="") return $val2; else return str_replace(array("<",">"),"",$val);
}

static function displayemail($val) {
  return trim(str_replace(array("(",")","\"","'",","),"",$val));
}

static function shortmessage_html($value,$args) {
  $value = trim(preg_replace("/^&gt;.*?$/m","",strip_tags($value)));
  if (strlen($value)>$args[0]) {
    $args[0] = strpos($value," ",$args[0]);
    if ($args[0]>0) $value = substr($value,0,$args[0])." ...";
  }
  return $value;
}

static function shortmessage($value,$args) {
  $replace = array("/^>.*?$/m", "/[=*]+/", "/[\s]+/u", "/-- .+\$/");
  $value = trim(preg_replace($replace, array("", "", " ", ""), $value));
  if (strlen($value)>$args[0]) {
    $pos = strpos($value," ",$args[0]);
    if ($pos > 0) $value = substr($value,0,$pos)." ...";
  }
  return $value;
}

static function htmlmessage($value) {
  if (($pos = stripos($value,"<body"))) $value = substr($value,$pos);
  $value = preg_replace(array("![\n\r]!i","!</tr>|<br ?/?>|<p>|</div>|<div[^>]*>!i"),array("","\n"),$value);
  $value = explode("\n",self::htmlunquote(strip_tags($value)));
  foreach ($value as $key=>$val) $value[$key] = trim($val);
  $value = preg_replace(array("/ +/","/\n{2,}/"),array(" ","\n\n"),implode("\n",$value));
  return $value;
}

static function replymessage($value,$args,$data) {
  if ($value!="") {
    $value = trim(str_replace("\n","\n> ","\n".wordwrap($value)));
    if (!empty($data["efrom"]["data"][0]) and !empty($data["created"])) {
	  $value = self::dateformat($data["created"],array("","m/d/Y")).", ".$data["efrom"]["data"][0].":\n".$value;
    }
	$value = "\n\n\n".$value;
  }
  if (isset($_REQUEST["return_receipt"])) $value = sprintf("Your message was read on %s.",sys_date("r")).$value;
  return $value;
}

static function forwardmessage($value,$args,$data) {
  $value = trim(str_replace("---\r\n".SMTP_FOOTER,"",$value));
  $val = "-------- Original message --------\n";
  if (!empty($data["subject"]["data"][0])) $val .= "Subject: ".$data["subject"]["data"][0]."\n";
  if (!empty($data["created"])) $val .= "Date: ".self::dateformat($data["created"],array("","m/d/Y"))."\n";
  if (!empty($data["efrom"]["data"][0])) $val .= "From: ".$data["efrom"]["data"][0]."\n";
  if (!empty($data["eto"]["data"][0])) $val .= "To: ".$data["eto"]["data"][0]."\n";
  if (!empty($data["cc"]["data"][0])) $val .= "Cc: ".$data["cc"]["data"][0]."\n";
  return "\n\n\n".$val."\n".$value;
}

static function copyfiles_totemp($value) {
  if ($value=="" or !file_exists($value)) return "";
  list($target,$filename) = sys_build_filename(self::basename($value));
  dirs_checkdir($target);
  $target .= $_SESSION["username"]."__".$filename;
  if (copy($value,$target)) return $target; else return "";
}

static function replyto($value,$args,$data) {
  if (isset($data["efrom"]["data"][0])) $value = $data["efrom"]["data"][0];
  return trim($value,", ");
}

static function replytoall($value,$args,$data) {
  if (isset($data["efrom"]["data"][0])) $value = $data["efrom"]["data"][0];
  if (isset($data["eto"]["data"][0])) $value .= ", ".$data["eto"]["data"][0];
  if (isset($data["cc"]["data"][0])) $value .= ", ".$data["cc"]["data"][0];
  return trim($value,", ");
}

static function replypid($value,$args,$data) {
  if (isset($data["_id"])) return $data["_id"];
  return $value;
}

static function replythreadid($value,$args,$data) {
  if (!empty($data["pid"]["data"][0])) return $data["pid"]["data"][0];
  if (isset($data["_id"])) return $data["_id"];
  return $value;
}

static function replysubject($value) {
  if (isset($_REQUEST["return_receipt"])) return "Read: ".$value;
  return "Re: ".$value;
}

static function replyfield($value) {
  return $value;
}

static function forwardsubject($value) {
  return "Fwd: ".$value;
}

static function shortdateformat($args) {
  if ($args==0) return "";
  $time_arr = sys_getdate();
  $format = "m/d/Y";
  $midnight = mktime(0,0,0,$time_arr["mon"],$time_arr["mday"],$time_arr["year"]);
  if ($midnight + 2678400 < $args) { // more than 31 days future
    $format = "m/d/Y";
  } else if ($midnight + 518400 < $args) { // more than 6 days future
    $format = "M j";
  } else if ($midnight + 86400 < $args) { // more than 1 day future
    $format = "M j, g:i a";
  } else if ($midnight < $args) { // today
    $format = "g:i a";
  } else if ($midnight - 518400 < $args) {// 6 days past
    $format = "M j, g:i a";
  } else if ($midnight - 2678400 < $args) {// 31 days past
    $format = "m/d/Y g:i a";
  }
  return self::_mydate($format,$args);
}

static function shortdatetimeformat($args) {
  if ($args==0) return "";
  $time_arr = sys_getdate();
  $format = "m/d/Y g:i a";
  $args_arr = sys_getdate($args);
  if (($args_arr["hours"]==0 and $args_arr["minutes"]==0) or ($args_arr["hours"]==23 and $args_arr["minutes"]==59)) {
    $format = "m/d/Y";
  } else {
    $midnight = mktime(0,0,0,$time_arr["mon"],$time_arr["mday"],$time_arr["year"]);
    if ($midnight + 2678400 < $args) { // more than 31 days future
      $format = "m/d/Y g:i a";
    } else if ($midnight + 86400 < $args) { // more than 1 day future
      $format = "M j, g:i a";
    } else if ($midnight < $args) { // today
      $format = "g:i a";
    } else if ($midnight - 518400 < $args) {// 6 days past
      $format = "M j, g:i a";
    }
  }
  return self::_mydate($format,$args);
}

static function percent($val) {
  if (!strpos($val,"%")) $val = ($val*100)."%";
  return $val;
}

static function filemtime($file) {
  if (empty($file) or !file_exists($file)) return "";
  return self::shortdateformat(filemtime($file));
}

static function filesize($bytes) {
  if (!is_numeric($bytes) and file_exists($bytes) and !strpos($bytes,"://")) $bytes = filesize($bytes);
  if (!is_numeric($bytes) or $bytes<0) return "";
  $names = "";
  for ($level = 0; $bytes >= 1024; $level++) $bytes /= 1024;
  switch ($level) {
    case 0: $suffix = (strlen($names)>0) ? $names[0] : "B"; break;
    case 1: $suffix = (strlen($names)>1) ? $names[1] : "KB"; break;
    case 2: $suffix = (strlen($names)>2) ? $names[2] : "MB"; break;
    case 3: $suffix = (strlen($names)>3) ? $names[3] : "GB"; break;
    case 4: $suffix = (strlen($names)>4) ? $names[4] : "TB"; break;
    default: $suffix = (isset($names[$level])) ? $names[$level] : ""; break;
  }
  if (empty($suffix)) return $bytes;
  return round($bytes,0)." ".$suffix;
}

static function duration($val, $params) {
  if ($val==0) return "";
  $steps = array(60,60,24,7,4.3,12,1);
  $units = array("sec"=>"","min"=>"min","hours"=>"hours","days"=>"days","weeks"=>"weeks","months"=>"months","years"=>"years");

  foreach ($units as $key=>$unit) {
    $step = array_shift($steps);
	if (($val*1.04) < $step and empty($params[0])) break;
	if (!empty($params[0]) and $key==$params[0]) break;
    $val /= $step;
  }
  return round($val,1)." ".$unit;
}

static function _getdata($data) {
  $result = array();
  if (!is_array($data) or count($data)==0) return array();
  foreach ($data as $key=>$val) $result[$key] = is_array($val) ? implode("|",$val["data"]) : $val;
  return $result;
}

static function linkselect($val,$params,$vars) {
  $result = array();
  $separator = str_replace("\\n", "\n", array_shift($params));
  $vars["value"] = $val;
  $data = select::dbselect($params, self::_getdata($vars));
  if (!is_array($data) or count($data)==0) return $val;

  foreach ($data as $key=>$value) $result[] = "[".$params[0]."/".$key."/".$value."]";
  $content = implode($separator, $result);
  if ($val!="") return $val.": ".$content; else return $content;
}

static function to_int($val) {
  return (int)$val;
}

static function showselect($val,$params,$vars) {
  $separator = str_replace("\\n", "\n", array_shift($params));
  $vars["value"] = $val;
  $content = implode($separator, select::dbselect($params, self::_getdata($vars)));
  if ($val!="") return $val.": ".$content; else return $content;
}

static function transfileperm($perms) {
  if ($perms=="") return "";
  $result = "";
  if(($perms & 0xC000) == 0xC000) $result = "s"; // Socket
    else if (($perms & 0xA000) == 0xA000) $result = "l"; // Symbolic Link
    else if (($perms & 0x8000) == 0x8000) $result = "-"; // Regular
    else if (($perms & 0x6000) == 0x6000) $result = "b"; // Block special
    else if (($perms & 0x4000) == 0x4000) $result = "d"; // Directory
    else if (($perms & 0x2000) == 0x2000) $result = "c"; // Character special
    else if (($perms & 0x1000) == 0x1000) $result = "p"; // FIFO pipe
    else $result = "u"; // UNKNOWN
   // owner
   $result .= (($perms & 0x0100) ? "r" : "-").(($perms & 0x0080) ? "w" : "-").
          (($perms & 0x0040) ? (($perms & 0x0800) ? "s" : "x"):(($perms & 0x0800) ? "S" : "-"));
   // group
   $result .= (($perms & 0x0020) ? "r" : "-").(($perms & 0x0010) ? "w" : "-").
          (($perms & 0x0008) ? (($perms & 0x0400) ? "s" : "x" ):(($perms & 0x0400) ? "S" : "-"));
   // world
   $result .= (($perms & 0x0004) ? "r" : "-").(($perms & 0x0002) ? "w" : "-").
          (($perms & 0x0001) ? (($perms & 0x0200) ? "t" : "x" ):(($perms & 0x0200) ? "T" : "-"));
   return $result;
}

static function urldecode($str) {
  return rawurldecode($str);
}

static function getfileext($filename) {
  if ($filename=="" or strpos($filename,".")===false) return $filename;
  $filename = str_replace("\\","/",basename($filename));
  return strtolower(substr($filename,-strpos(strrev($filename),'.')));
}

static function basename($filename) {
  if (strpos($filename,"|")) {
    $filename = explode("|",$filename);
    foreach (array_keys($filename) as $key) $filename[$key] = self::basename($filename[$key]);
	$filename = implode("|",$filename);
  } else {
    $filename = basename($filename);
    if (preg_match("/[0-9abcdef]+?--/",$filename)) {
      $filename = urldecode(substr($filename,strpos($filename,"--")+2));
    }
  }
  return $filename;
}

static function realfilename($filename,$quote=true) {
  $result = realpath(dirname($filename))."/".basename($filename);
  if (strpos(PHP_OS,"WIN")!==false) {
    if ($quote and strpos($result," ")) $result = "\"".$result."\"";
    $result = str_replace("/","\\",$result);
  } else {
    if ($quote and strpos($result," ")) $result = "'".$result."'";
  }
  return $result;
}

static function preview_bin($filename,$ext) {
  if (!function_exists("proc_open")) return "ERROR Cannot call 'proc_open'. Please remove 'proc_open' from 'disable_functions' in php.ini and disable 'safe_mode'.";
  $result = "";
  switch ($ext) {
    case "zip":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("unzip")." -l -V ".$src);
	  $result = substr($result,strpos($result,"\n")+1);
	  break;
	case "tar":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("tar")." -tf ".$src);
	  break;
	case "gz":
	case "tgz":
	  if (!strpos(strtolower($filename),".tar.gz") and !strpos(strtolower($filename),".tgz")) break;
	  $src = self::realfilename($filename);
	  $cmd = sys_find_bin("gzip")." -cd ".$src." | ".sys_find_bin("tar")." -t";
	  if (strpos(PHP_OS,"WIN")!==false) $cmd = str_replace("/","\\",$cmd);
	  $result = sys_exec($cmd);
	  break;
	case "ppt":
	  $tmp = SIMPLE_CACHE."/debug/sys_exec_".md5($_SESSION["username"].NOW).".ppt";
	  copy($filename,$tmp);
	  $result = sys_exec(sys_find_bin("ppthtml")." ".self::realfilename($tmp));
	  unlink($tmp);
	  if (($pos = strpos($result,"<BODY"))) $result = substr($result,$pos);
	  $result = utf8_decode(strip_tags($result));
	  break;
	case "doc":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("catdoc")." -d utf-8 ".$src);
	  break;
	case "xls":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("xls2csv")." -d utf-8 ".$src);
  	  // $result = sys_exec(sys_find_bin("xlhtml")." -nh ".$src);
	  break;
	case "docx":
	case "xlsx":
	case "pptx":
	case "ods": // oo-xls
	case "sxc":
	case "odt": // oo-doc
	case "sxw":
	case "odp": // oo-ppt
	case "sxi":
	  if ($ext=="docx") {
	    $file = "word/document.xml";
		$replace = array("</w:p>"=>"\n");
	  } else if ($ext=="xlsx") {
		$file = "xl/sharedStrings.xml";
		$replace = array("</si>"=>" ");
	  } else if ($ext=="pptx") {
	    $file = "ppt/slides/*.xml";
		$replace = array("</a:p>"=>"\n");
	  } else {
		$file = "content.xml";
		$replace = array("</text:p>"=>"\n");
	  }
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("unzip")." -p ".$src." ".$file);
	  $result = utf8_decode(strip_tags(str_replace(array_keys($replace),array_values($replace),$result)));
	  break;
	case "url":
	  preg_match("/^URL=(.+)/m", file_get_contents($filename), $match);
	  if (!empty($match[1])) {
		$result = "<a href='".modify::htmlquote(trim($match[1]))."' target='_blank'>".modify::basename(substr($filename,0,-4))."</a>";
	  }
	  break;
	case "pdf":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("pdfinfo")." ".$src);
	  $result .= sys_exec(sys_find_bin("pdftotext")." ".$src." @file@");
	  break;
	case "mp3":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("mp3info")." -x ".$src);
	  $result = substr($result,strpos($result,"\n")+1);
	  break;
	case "jpg":
	case "jpeg":
	  $src = self::realfilename($filename);
	  $result = sys_exec(sys_find_bin("exiv2")." ".$src);
	  $result = str_replace("\r","",$result)."\n";
	  $result = preg_replace("!(^.*No Exif.*|File ?name.*|File ?size.*|MIME type.*|.*?:\s*)\n!im","",$result);
	  $gps = sys_exec(sys_find_bin("exiv2")." -PEnv ".$src);
	  preg_match("!GPSLatitude\s+(\d+)/(\d+) (\d+)/(\d+) (\d+)/(\d+)!", $gps, $match);
	  preg_match("!GPSLongitude\s+(\d+)/(\d+) (\d+)/(\d+) (\d+)/(\d+)!", $gps, $match2);
	  if (is_array($match) and count($match)==7 and is_array($match2) and count($match2)==7) {
		$latitude = ($match[1]/$match[2]) + ($match[3]/$match[4]/60) + ($match[5]/$match[6]/3600);
		if (!preg_match("/GPSLatitudeRef\s+N/", $gps)) $latitude *= -1;
		$longitude = ($match2[1]/$match2[2]) + ($match2[3]/$match2[4]/60) + ($match2[5]/$match2[6]/3600);
		if (!preg_match("/GPSLongitudeRef\s+E/", $gps)) $longitude *= -1;
		$result .= "GPS: <a target='_blank' href='http://maps.google.com/?ll={$latitude},{$longitude}'>Google Maps</a>";
	  }
	  break;
  }
  return $result;
}

static function previewfile($file,$table) {
  if (strpos($file,"_html.txt")) return "";
  $result = self::displayfile($table,$file);
  if (trim($result)!="") $result .= "<br>";
  return $result;
}

static function previewlink($file) {
  $target = SIMPLE_CACHE."/preview/".sha1($file)."--".self::basename($file);
  $ext = self::getfileext($file);
  if (!file_exists($target)) copy($file,$target);
  if ($ext=="ics") $type = "icalendar";
  if ($ext=="vcf") $type = "vcard";
  return "index.php?preview=1&markdate=all&folder=".$type.":/".$target;
}

static function displayfile($table,$filename,$index=false,$limit=true) {
  $size = @filesize($filename);
  $ext = self::getfileext($filename);
  if ($ext==basename($filename)) $ext=self::basename($filename);
  $txt_files = array(
    "ldif","log","css","csv","eml","rfc822","ini","reg","tsv","txt","ics","vcf","lang"
  );
  $code_files = array(
    "bas","bat","c","cmd","cpp","csh","inf","sh","vb","vbe","xml",
	"java","js","pas","php","pl","vbs","vcs","wsh","tpl","sql"
  );
  $bin_files = array("doc","docx","xls","xlsx","ppt","pptx","tar","zip","gz","tgz","pdf","mp3","odt",
    "sxw","ods","sxc","odp","sxi","jpg","jpeg","tif","url");
  $html_files = array("htm","html");

  $return = "";
  $return_html = "";
  $cid = str_replace("simple_","",$table)."_".sha1($filename.$size.@filemtime($filename));
  if (($return = sys_cache_get($cid))) {
    if (!$index and $limit and strlen($return)>FILE_TEXT_LIMIT) $return = substr($return,0,FILE_TEXT_LIMIT)." ...";
	return trim($return);
  }

  $type = "";
  if (in_array($ext,$txt_files)) $type = "text";
    else if (in_array($ext,$code_files)) $type = "code";
    else if (in_array($ext,$html_files)) $type = "html";
    else if (in_array($ext,$bin_files)) $type = "bin";

  if ($type!="" and file_exists($filename)) {
	if ($type=="bin") {
	  if (filesize($filename)!=0) {
		if (!sys_strbegins($filename,SIMPLE_STORE."/") and $result = validate::checkvirus($filename)) {
		  $return = "ERROR Virus scanner: ".$result;
		} else {
	      $return = trim(self::preview_bin($filename,$ext));
		}
	  }
	} else $return = trim(file_get_contents($filename,false,null,-1,$limit?FILE_TEXT_LIMIT:131072));

	if ($return!="") {
  	  if ($index) $rlimit = INDEX_LIMIT; else $rlimit = FILE_TEXT_LIMIT;
	  if ($limit and strlen($return) > $rlimit) $return = substr($return,0,$rlimit)." ...";
	  if (!self::detect_utf($return)) $return = utf8_encode($return);
	  if ($type=="html") $return_html = substr($return,0,strrpos($return,">"));
		else if ($type!="code") $return_html = nl2br(strip_tags($return, "<a><b><i>"));
		else $return_html = self::highlight_string($return);
	}
  }
  if ($return_html=="") $return_html = " ";
  if (!sys_strbegins($return,"ERROR ")) {
    sys_cache_set($cid,$return_html,FILE_TEXT_CACHE);
	if ($index) return $return; else return trim($return_html);
  } else {
    sys_log_message_log("php-fail","displayfile: ".$return);
  }
  if ($index) return "";
  return sprintf("Cannot create preview for %s.",$ext);
}

static function str_to_hex($str) {
  $result = "";
  for ($i=0; $i<strlen($str); $i++) {
	$result .= dechex(ord($str[$i]))." ";
  }
  return $result;
}

static function detect_utf($str) {
  if ($str=="") return false;
  return preg_match('%(?:
    [\xC2-\xDF][\x80-\xBF]
    |\xE0[\xA0-\xBF][\x80-\xBF]
    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
    |\xED[\x80-\x9F][\x80-\xBF]
    |\xF0[\x90-\xBF][\x80-\xBF]{2}
    |[\xF1-\xF3][\x80-\xBF]{3}
    |\xF4[\x80-\x8F][\x80-\xBF]{2}
  )+%xs', $str);
}

static function iframe_file($filename) {
  $ext = self::getfileext($filename);
  $files = array("ics","vcf");
  if (in_array($ext,$files)) return true;
  return false;
}

static function spreadsheet_file($filename) {
  $ext = self::getfileext($filename);
  $files = array("csv","xls");
  if (in_array($ext,$files)) return true;
  return false;
}

static function image_file($filename) {
  $ext = self::getfileext($filename);
  $img_files = array(
    "gif","jpg","jpeg","png","bmp","emf","eps","ico","pcd","pcx","psd","svg","tif","tiff","wmf","xpm"
  );
  if (in_array($ext,$img_files)) return true;
  return false;
}

static function exif_file($filename) {
  $ext = self::getfileext($filename);
  $img_files = array("jpg","jpeg");
  if (in_array($ext,$img_files)) return true;
  return false;
}

static function htmlquote($string) {
  return htmlspecialchars($string, ENT_QUOTES);
}

static function htmlunquote($value) {
  return str_replace(chr(255)," ",html_entity_decode($value,ENT_QUOTES,"UTF-8"));
}

// htmlquote = htmlspecialchars
// field = auto-link assets
// nl2br = <code> + link URLs + hide ">" blocks + \n to <br> + textwrap {60}
// htmlfield = strip bad tags
static function urladdon_quote($var) {
  $var = preg_replace("/\{\\\$(.*?)\}/","{\$\\1|modify::htmlquote}",$var);
  $from = array(".php?","_php","|modify::field|modify::htmlquote","|no_check|modify::htmlquote",
  				"|modify::htmlfield_noimages|modify::htmlquote", "|modify::htmlfield|modify::htmlquote", "|modify::nl2br|modify::htmlquote");
  $to = array(".php?{\$urladdon}&",".php","|modify::htmlquote|modify::field","",
  				"|modify::htmlfield_noimages","|modify::htmlfield","|modify::htmlquote|modify::nl2br");
  return str_replace($from,$to,$var);
}

static function strip_ntdomain($username) {
  if (($pos = strpos($username,"\\"))) $username = substr($username,$pos+1);
  if (($pos = strpos($username,"@"))) $username = substr($username,0,$pos);
  return strtolower($username);
}

static function dbselect($params) {
  return select::dbselect($params);
}

static function calendar_repeat($params,$args,$data) {
  if (empty($data["recurrence"]["data"][0])) return "";
  return $params;
}

static function utf8_encode($str,$charset="") {
  if ($str=="") return "";
  if (!strpos($charset, "125") and self::detect_utf($str)) return $str; // windows 125x

  if ($charset=="iso-8859-1") $charset = "windows-1252"; // map invalid characters to 1252
  if ($charset!="" and function_exists("iconv")) return iconv($charset,"utf-8",$str);

  // problem with iso8859-15, cp-1252
  return utf8_encode(str_replace(array(chr(128),chr(164),chr(153),chr(150),chr(147),chr(148)),array(" Euro"," Euro"," (tm)","-","\"","\""),$str));
}

// Copyright (c) 2002-2003 Richard Heyes
// Copyright (c) 2003-2005 The PHP Group
static function decode_subject($input) {
  $i = 0;
  if ($input!="" and strpos($input,"=?")!==false) {
    $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
	$charset = "";
   	while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
      $text = $matches[4];
	  $charset = strtolower($matches[2]);
      switch (strtolower($matches[3])) { // encoding
   	    case "b": $text = base64_decode($text); break;
   	    case "q":
          $text = str_replace("_"," ",$text);
       	  preg_match_all("/=([a-f0-9]{2})/i",$text,$matches2);
 	      foreach ($matches2[1] as $value) $text = str_replace('='.$value, chr(hexdec($value)), $text);
          break;
   	  }
      $input = str_replace($matches[1],$text,$input);
	  if ($i>4) break;
	  $i++;
    }
	if ($i>0 and preg_match("/iso|ascii|125|cp|windows|koi/",$charset)) $input = self::utf8_encode($input,$charset);
  }
  if ($i==0 and $input!="") $input = self::utf8_encode($input);
  return $input;
}

// Tidy up HTML produced by TinyMCE
// Contributed by paulzarucki (02/2010), cleanup by tbley
static function html_tidy($var) {
  $replace = array(
    "/(\n|\r|\t)/s", // remove linefeeds tabs #1
	"/ mce_[^=]+=\"[^\"]+\"([^>]*)>/si", // TinyMCE markup #2
	"/> +</s", // #3
//	"/<p[^>]*>(<br[^>]*>)+/si", // #4 <p><br>+ => <p>
	"/(<br[^>]*>)+(<\/(p|li|td|th)>)/si", // paste from OpenOffice #5
	"|<p[^>]*></p>|si", // #6 remove <p></p>
	"|(<br[^>]*>)+</|si", // #7 <br>+</ => <br></
	"/(<br[^>]*>){2,}/si", // paste from Word #8 <br><br>+ => <br><br>
	"/ +/s", // #9
	"!(<(/?div|/?table|/?tbody|tr|p|h|/?ol|/?ul|li))!si", // HTML pretty-print #10
	"!(</(p|h\d)>)!si", // #11
  );
  $with = array(
    " ", // #1
	"\\1>", // #2
	"><", // #3
//	"<p>", // #4
	"\\2", // #5
	"", // #6
	"<br></", // #7
	"<br><br>", // #8
	" ", // #9
	"\n\\1", // #10
	"\\1\n", // #11
  );
  return preg_replace($replace,$with,$var);
}

// Simplify HTML, works on html_tidy
// Contributed by paulzarucki (02/2010), cleanup by tbley
static function html_simplify($var) {
  $var = preg_replace("!<(tr|th|/div)[^>]*?>!si","<br>",$var);
  $var = preg_replace("!<(/?)(p|table|h\d)[^>]*>!si","<\\1p>",$var);
  $var = preg_replace("!</?(col|span|div|font|tbody|thead|tr|th|td)[^>]*>!si"," ",$var);
  $var = preg_replace("|p>\s*<br>|si","p>",$var);
  return preg_replace("|(<br>\s){2,}|si","<br><br>",$var);
}

// A simple HTML to text converter
// Contributed by paulzarucki (02/2010), cleanup by tbley
static function html2text($var) {
  $var = preg_replace("/(\n|\r)/s"," ",$var); // remove linefeeds and carriage returns
  $var = preg_replace("/(<[^ >]+)[^>]*/s","\\1",$var); // remove attributes
  $var = preg_replace("/(<br> *)+/si","<br>",$var); // remove unwanted line breaks
  $var = preg_replace("|<br></|si","</",$var);
  $var = preg_replace(array("/(<(td|th|li)>) *<p>/si","!</p> *(</(td|th|li)>)!si"),"\\1",$var); // remove unwanted paragraphs

  $var = preg_replace("/<li>/si","\n\t* ",$var); // tag replacement
  $var = preg_replace("/<br>/si","\n\n",$var);
  $var = preg_replace("!</?(h\d|table|tr|div|ol|ul|p)>!si","\n",$var);
  $var = preg_replace("/<(th|td)>/si","\t",$var);
  $var = preg_replace("/<[^>]+>/s","",$var); // remove all remaining tags
  $var = preg_replace("/ *\n */s","\n",$var); // remove unwanted blanks

  $var = str_ireplace(array("&nbsp;","&amp;","\t"),array(" ","&"," "),$var);
  $var = preg_replace("/ +/s"," ",$var);
  $var = preg_replace("/\n{2,}/s","\n\n",$var);
  return str_replace(array("\n","\t"),array("<br>"," "),trim($var));
}
}